# Production Deployment Guide

## Overview

This guide covers production deployment of Astra OS using Docker and Kubernetes on cloud infrastructure.

## Architecture

```
Internet
    │
    ▼
Cloudflare / CDN
    │
    ▼
Load Balancer (ALB/Nginx Ingress)
    │
    ├── Web Pod (Nginx)
    ├── App Pod (PHP-FPM)
    ├── Queue Worker (PHP CLI)
    ├── Scheduler (PHP CLI)
    │
    ├── PostgreSQL (RDS or StatefulSet)
    └── Redis (ElastiCache or Deployment)
```

## Prerequisites

- **Kubernetes cluster** (EKS, AKS, GKE, or self-managed)
- **PostgreSQL 16+** (RDS recommended for production)
- **Redis 7+** (ElastiCache recommended for production)
- **Container registry** (GHCR, Docker Hub, ECR)
- **Domain name** with DNS configured
- **SSL certificate** (Let's Encrypt via cert-manager)

## Deployment Steps

### 1. Infrastructure Setup

#### PostgreSQL (RDS)
```hcl
# Terraform example
resource "aws_db_instance" "astraos" {
  engine         = "postgres"
  engine_version = "16"
  instance_class = "db.r6g.large"
  db_name        = "astraos"
  username       = var.db_username
  password       = var.db_password
  storage_encrypted = true
  backup_retention_period = 30
  performance_insights_enabled = true
}
```

#### Redis (ElastiCache)
```hcl
resource "aws_elasticache_cluster" "astraos" {
  cluster_id           = "astraos-redis"
  engine               = "redis"
  node_type            = "cache.r6g.large"
  num_cache_nodes      = 2
  parameter_group_name = "default.redis7"
  engine_version       = "7.0"
}
```

### 2. Environment Configuration

Create production secrets:

```bash
# Generate application key
php artisan key:generate --show

# Create K8s secret
kubectl create secret generic astraos-secrets \
  --namespace=astraos \
  --from-literal=APP_KEY=base64:... \
  --from-literal=DB_PASSWORD=... \
  --from-literal=REDIS_PASSWORD=... \
  --from-literal=OPENAI_API_KEY=...
```

### 3. Deploy Application

```bash
# Deploy base resources
kubectl apply -k k8s/base

# Or deploy production overlay
kubectl apply -k k8s/overlays/production

# Verify deployment
kubectl get pods -n astraos
kubectl get svc -n astraos
```

### 4. Database Migrations

```bash
# Run migrations
kubectl exec -n astraos deploy/prod-astraos-app -- php artisan migrate --force

# Seed data (if needed)
kubectl exec -n astraos deploy/prod-astraos-app -- php artisan db:seed --force
```

### 5. Configure DNS

```dns
api.astraos.io  →  ALB DNS name or Ingress IP
```

### 6. SSL/TLS

```bash
# Install cert-manager
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.14.0/cert-manager.yaml

# Create ClusterIssuer for Let's Encrypt
cat <<EOF | kubectl apply -f -
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: admin@astraos.io
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: nginx
EOF
```

### 7. Monitoring Setup

```bash
# Install Prometheus Stack
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm install prometheus prometheus-community/kube-prometheus-stack

# Configure alerts (see alerts/ directory)
```

## Scaling

### Horizontal Pod Autoscaling

The HPA automatically scales based on CPU and memory utilization:

```yaml
minReplicas: 2
maxReplicas: 20
metrics:
  - cpu: target 70%
  - memory: target 80%
```

### Database Scaling

- **Read Replicas**: Add PostgreSQL read replicas for reporting queries
- **Connection Pooling**: Use PgBouncer for connection management
- **Partitioning**: Partition large tables (campaign_analytics, audit_logs)

## Backup Strategy

### Database Backups
- Automated daily snapshots (RDS automated backups)
- Point-in-time recovery enabled (7+ days)
- Weekly exports to S3 for off-site storage

### Application Backups
- Storage exports to S3 (daily)
- Configuration backup (Git + K8s manifests)
- Secrets backup (encrypted in Vault/Secrets Manager)

## Security Checklist

- [ ] Database in private subnet
- [ ] Redis in private subnet with AUTH
- [ ] HTTPS enforced
- [ ] WAF enabled (Cloudflare/AWS WAF)
- [ ] Security groups restricted
- [ ] Secrets encrypted at rest
- [ ] Audit logging enabled
- [ ] Regular security updates
- [ ] Penetration testing completed

## Performance Tuning

### PHP-FPM
```ini
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 15
```

### PostgreSQL
```ini
shared_buffers = '2GB'
effective_cache_size = '6GB'
work_mem = '64MB'
maintenance_work_mem = '512MB'
random_page_cost = 1.1
```

### Redis
```ini
maxmemory 2gb
maxmemory-policy allkeys-lru
```

## Troubleshooting

### Common Issues

**Pod CrashLoopBackOff**
```bash
kubectl logs -n astraos deploy/astraos-app --previous
kubectl describe pod -n astraos <pod-name>
```

**Database Connection Errors**
```bash
kubectl exec -n astraos deploy/astraos-app -- php artisan db:monitor
```

**Queue Backlog**
```bash
kubectl scale -n astraos deploy/queue-worker --replicas=5
```

### Health Check

```bash
# Application health
curl https://api.astraos.io/api/v1/health

# Pod health
kubectl get pods -n astraos -w
```
