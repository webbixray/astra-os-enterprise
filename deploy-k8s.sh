#!/bin/bash
# Astra OS Enterprise v2.1.0 - Kubernetes (MicroK8s) Deployment
# Run this on VPS if using Kubernetes instead of Docker Compose

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Check MicroK8s
log_info "Checking MicroK8s..."
if ! command -v microk8s &> /dev/null; then
    log_error "MicroK8s not found. Install with: snap install microk8s --classic"
    exit 1
fi

# Enable required addons
log_info "Enabling MicroK8s addons..."
microk8s enable dns storage ingress metallb:10.64.140.43-10.64.140.49
microk8s enable helm3 prometheus

# Wait for addons
log_info "Waiting for addons to be ready..."
microk8s status --wait-ready

# Create namespace
log_info "Creating astraos namespace..."
microk8s kubectl apply -f k8s/base/namespace.yaml

# Apply ExternalSecrets (if using AWS Secrets Manager)
if [ -f k8s/base/external-secrets/namespace.yaml ]; then
    log_info "Installing ExternalSecrets operator..."
    microk8s kubectl apply -f k8s/base/external-secrets/
fi

# Apply base manifests
log_info "Applying base Kubernetes manifests..."
microk8s kubectl apply -k k8s/overlays/production

# Wait for deployments
log_info "Waiting for deployments..."
microk8s kubectl rollout status deployment/astra-os-app -n astraos --timeout=300s
microk8s kubectl rollout status deployment/astra-os-horizon -n astraos --timeout=300s

# Run migrations
log_info "Running database migrations..."
POD=$(microk8s kubectl get pod -n astraos -l app=astra-os,tier=backend -o jsonpath='{.items[0].metadata.name}')
microk8s kubectl exec -n astraos "$POD" -- php artisan migrate --force

# Optimize Laravel
log_info "Optimizing Laravel..."
microk8s kubectl exec -n astraos "$POD" -- php artisan config:cache
microk8s kubectl exec -n astraos "$POD" -- php artisan route:cache
microk8s kubectl exec -n astraos "$POD" -- php artisan view:cache
microk8s kubectl exec -n astraos "$POD" -- php artisan event:cache
microk8s kubectl exec -n astraos "$POD" -- php artisan storage:link

# Check pods
log_info "Checking pod status..."
microk8s kubectl get pods -n astraos -o wide

# Check services
log_info "Checking services..."
microk8s kubectl get svc -n astraos

# Health check
log_info "Running health check..."
sleep 10
if microk8s kubectl exec -n astraos "$POD" -- curl -sf http://localhost/api/v1/health >/dev/null; then
    log_info "✅ Health check passed"
else
    log_error "Health check failed"
    microk8s kubectl logs -n astraos "$POD"
    exit 1
fi

log_info "✅ Kubernetes deployment complete!"
log_info "Access via ingress or port-forward:"
log_info "  microk8s kubectl port-forward -n astraos svc/astra-os 8000:80"