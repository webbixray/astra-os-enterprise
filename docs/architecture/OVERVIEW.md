# Architecture Overview

> C4-Style Architecture for Astra OS - Enterprise AI-Powered Marketing Platform

## System Context (Level 1)

```mermaid
graph TB
    User([Marketing Team]) --> AstraOS[Astra OS System]
    AstraOS --> Meta[Meta Ads API]
    AstraOS --> Google[Google Ads API]
    AstraOS --> LinkedIn[LinkedIn Ads API]
    AstraOS --> TikTok[TikTok Ads API]
    AstraOS --> OpenAI[OpenAI API]
    AstraOS --> Anthropic[Anthropic API]
    AstraOS --> Email[Email System]
    AstraOS --> Slack[Slack Webhooks]
```

## Container Diagram (Level 2)

```mermaid
graph TB
    subgraph "Astra OS"
        API[API Gateway<br/>Nginx]
        APP[Laravel Application<br/>PHP 8.4]
        QUEUE[Queue Worker<br/>Redis + PHP]
        SCHED[Scheduler<br/>Cron + PHP]
        
        subgraph "Services"
            CAMPAIGN[Campaign Service]
            AGENT[Agent Service]
            WORKFLOW[Workflow Engine]
            SOCIAL[Social Service]
            ANALYTICS[Analytics Service]
        end
        
        subgraph "Storage"
            PG[(PostgreSQL<br/>Primary Database)]
            RC[(Redis Cache<br/>Session + Cache)]
        end
    end
    
    API --> APP
    APP --> CAMPAIGN
    APP --> AGENT
    APP --> WORKFLOW
    APP --> SOCIAL
    APP --> ANALYTICS
    APP --> RC
    APP --> PG
    QUEUE --> RC
    QUEUE --> PG
    SCHED --> PG
```

## Component Diagram (Level 3)

### Campaign Service
```mermaid
graph LR
    CRUD[Campaign CRUD] --> OP[Optimization Engine]
    CRUD --> PLAT[Platform Sync]
    OP --> INS[Insights Engine]
    OP --> BUDGET[Budget Manager]
    PLAT --> META[Meta Adapter]
    PLAT --> GOOGLE[Google Adapter]
    PLAT --> LI[LinkedIn Adapter]
    PLAT --> TT[TikTok Adapter]
```

### Agent Service
```mermaid
graph LR
    CEO[CEO Agent] --> DIR[Director Agent]
    DIR --> SPEC[Specialist Agent]
    ORCH[Orchestrator] --> CEO
    MEM[Memory Store] --> ORCH
    TASK[Task Manager] --> ORCH
    AI[AI Provider Gateway] --> ORCH
```

### Workflow Engine
```mermaid
graph LR
    DEF[Workflow Definition] --> EXEC[Execution Engine]
    DEF --> NODE[Node Registry]
    EXEC --> STATE[State Machine]
    EXEC --> EVAL[Condition Evaluator]
    EXEC --> TIMER[Timer Service]
    STATE --> ACT[Action Executor]
    STATE --> APPR[Approval Handler]
```

## Data Flow

```mermaid
sequenceDiagram
    participant User
    participant API as API Gateway
    participant App as Laravel App
    participant AI as AI Provider
    participant DB as PostgreSQL
    participant Cache as Redis
    
    User->>API: Create Campaign Request
    API->>App: Authenticated Request
    App->>DB: Store Campaign
    App->>AI: Generate Creative (async)
    AI->>App: Creative Assets
    App->>DB: Update with Creative
    App->>Cache: Invalidate Campaign Cache
    App->>API: Response
    API->>User: Campaign Created
    
    Note over App,AI: Agent processes task via queue
    App->>Cache: Queue Task
    Cache->>AI: Process via Worker
```

## Deployment Architecture

```mermaid
graph TB
    subgraph "Kubernetes Cluster"
        subgraph "Production Namespace"
            APP1[App Pod 1]
            APP2[App Pod 2]
            APP3[App Pod 3]
            WEB1[Web Pod 1]
            WEB2[Web Pod 2]
            PG[(PostgreSQL<br/>StatefulSet)]
            RC[(Redis<br/>Deployment)]
            QW1[Queue Worker 1]
            QW2[Queue Worker 2]
        end
    end
    
    LB[Load Balancer] --> WEB1
    LB --> WEB2
    WEB1 --> APP1
    WEB1 --> APP2
    WEB2 --> APP2
    WEB2 --> APP3
    APP1 --> PG
    APP2 --> PG
    APP3 --> PG
    APP1 --> RC
    APP2 --> RC
    APP3 --> RC
    QW1 --> RC
    QW2 --> RC
```

## Technology Stack

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| Language | PHP | 8.4 | Application runtime |
| Framework | Laravel | 13 | Web framework |
| Database | PostgreSQL | 16 | Primary data store |
| Cache | Redis | 7 | Cache, sessions, queues |
| Web Server | Nginx | 1.27 | Reverse proxy |
| Containers | Docker | latest | Local development |
| Orchestration | Kubernetes | 1.28+ | Production orchestration |
| CI/CD | GitHub Actions | - | Automated pipeline |
| AI | OpenAI / Anthropic | - | AI agent backend |
| Monitoring | Prometheus/Grafana | - | Observability (planned) |
