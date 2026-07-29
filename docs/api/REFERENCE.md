# API Reference

> Version: 1.0 | Base URL: `/api/v1`

## Authentication

All API requests (except auth and health) require a Bearer token obtained via Sanctum.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## Endpoints

### Health Check

**GET** `/api/v1/health`

Public endpoint to verify API availability.

```json
{
  "status": "healthy",
  "version": "1.0.0",
  "timestamp": "2026-07-30T12:00:00Z"
}
```

---

### Authentication

#### Register
**POST** `/api/v1/auth/register`

Create a new user account.

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword",
  "password_confirmation": "securepassword"
}
```

#### Login
**POST** `/api/v1/auth/login`

Authenticate and receive access token.

```json
{
  "email": "john@example.com",
  "password": "securepassword"
}
```

**Response:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

#### Logout
**POST** `/api/v1/auth/logout`

Revoke current access token.

#### Current User
**GET** `/api/v1/auth/me`

Get authenticated user profile with organization.

---

### Organizations

All routes require `auth:sanctum` middleware.

#### Dashboard
**GET** `/api/v1/organizations/{organization}/dashboard`

Get organization dashboard summary.

---

### Members

#### List Members
**GET** `/api/v1/organizations/{organization}/members`

#### Invite Member
**POST** `/api/v1/organizations/{organization}/members`

```json
{
  "email": "member@example.com",
  "role": "member"
}
```

#### Remove Member
**DELETE** `/api/v1/organizations/{organization}/members/{member}`

---

### Campaigns

#### List Campaigns
**GET** `/api/v1/organizations/{organization}/campaigns`

Query Parameters:
- `status` - Filter by status (draft, active, paused, completed)
- `objective` - Filter by objective
- `platform` - Filter by platform
- `per_page` - Pagination (default: 15)

#### Create Campaign
**POST** `/api/v1/organizations/{organization}/campaigns`

```json
{
  "name": "Summer Sale 2026",
  "objective": "conversions",
  "budget_amount": 50000,
  "budget_currency": "USD",
  "target_audience": {
    "age": ["18-35"],
    "locations": ["US", "CA"]
  },
  "platforms": ["meta", "google"],
  "start_date": "2026-06-01",
  "end_date": "2026-08-31"
}
```

#### Get Campaign
**GET** `/api/v1/organizations/{organization}/campaigns/{campaign}`

#### Update Campaign
**PUT** `/api/v1/organizations/{organization}/campaigns/{campaign}`

#### Delete Campaign
**DELETE** `/api/v1/organizations/{organization}/campaigns/{campaign}`

---

### Campaign Creatives

#### List Creatives
**GET** `/api/v1/organizations/{organization}/campaigns/{campaign}/creatives`

#### Create Creative
**POST** `/api/v1/organizations/{organization}/campaigns/{campaign}/creatives`

```json
{
  "type": "image",
  "content": {
    "headline": "Summer Sale!",
    "body": "Get 25% off everything",
    "cta": "Shop Now"
  },
  "variant": "variant_a"
}
```

---

### Campaign Insights

**GET** `/api/v1/organizations/{organization}/campaigns/{campaign}/insights`

### Campaign Analytics

**GET** `/api/v1/organizations/{organization}/campaigns/{campaign}/analytics`

Query Parameters:
- `from` - Start date (YYYY-MM-DD)
- `to` - End date (YYYY-MM-DD)
- `source` - Data source filter

---

### Agents

#### List Agents
**GET** `/api/v1/organizations/{organization}/agents`

#### Create Agent
**POST** `/api/v1/organizations/{organization}/agents`

```json
{
  "name": "Campaign Optimizer",
  "role": "specialist",
  "model_config": {
    "provider": "openai",
    "model": "gpt-4o-mini",
    "temperature": 0.5
  },
  "autonomy_level": "supervised",
  "parent_agent_id": null
}
```

#### Get Agent
**GET** `/api/v1/organizations/{organization}/agents/{agent}`

#### Update Agent
**PUT** `/api/v1/organizations/{organization}/agents/{agent}`

#### Delete Agent
**DELETE** `/api/v1/organizations/{organization}/agents/{agent}`

#### List Agent Tasks
**GET** `/api/v1/organizations/{organization}/agents/{agent}/tasks`

#### Create Agent Task
**POST** `/api/v1/organizations/{organization}/agents/{agent}/tasks`

```json
{
  "type": "campaign_optimization",
  "input": {
    "action": "optimize_budget",
    "campaign_id": "uuid"
  }
}
```

---

### Workflows

#### List Workflows
**GET** `/api/v1/organizations/{organization}/workflows`

#### Create Workflow
**POST** `/api/v1/organizations/{organization}/workflows`

```json
{
  "name": "Campaign Launch Pipeline",
  "description": "End-to-end campaign launch workflow",
  "nodes": [...],
  "edges": [...]
}
```

#### Get Workflow
**GET** `/api/v1/organizations/{organization}/workflows/{workflow}`

#### Update Workflow
**PUT** `/api/v1/organizations/{organization}/workflows/{workflow}`

#### Delete Workflow
**DELETE** `/api/v1/organizations/{organization}/workflows/{workflow}`

#### Execute Workflow
**POST** `/api/v1/organizations/{organization}/workflows/{workflow}/execute`

#### List Executions
**GET** `/api/v1/organizations/{organization}/workflows/{workflow}/executions`

---

### Workflow Templates

#### List Templates
**GET** `/api/v1/workflow-templates`

#### Get Template
**GET** `/api/v1/workflow-templates/{template}`

---

### Social Media

#### List Accounts
**GET** `/api/v1/organizations/{organization}/social/accounts`

#### Connect Account
**POST** `/api/v1/organizations/{organization}/social/accounts`

```json
{
  "platform": "meta",
  "access_token": "EAATom..."
}
```

#### Disconnect Account
**DELETE** `/api/v1/organizations/{organization}/social/accounts/{account}`

#### List Posts
**GET** `/api/v1/organizations/{organization}/social/posts`

#### Create Post
**POST** `/api/v1/organizations/{organization}/social/posts`

```json
{
  "account_id": "uuid",
  "content": "Check out our new product!",
  "media": [...],
  "scheduled_at": "2026-08-01T10:00:00Z"
}
```

#### Update Post
**PUT** `/api/v1/organizations/{organization}/social/posts/{post}`

#### List Mentions
**GET** `/api/v1/organizations/{organization}/social/mentions`

---

### Reports

#### List Reports
**GET** `/api/v1/organizations/{organization}/reports`

#### Create Report
**POST** `/api/v1/organizations/{organization}/reports`

```json
{
  "name": "Weekly Performance",
  "type": "weekly",
  "config": {
    "metrics": ["impressions", "clicks", "conversions"],
    "campaigns": ["all"]
  },
  "format": "pdf",
  "recipients": ["team@example.com"]
}
```

#### Get Report
**GET** `/api/v1/organizations/{organization}/reports/{report}`

#### Generate Report
**POST** `/api/v1/organizations/{organization}/reports/{report}/generate`

---

### Settings

#### Get Settings
**GET** `/api/v1/organizations/{organization}/settings`

#### Update Settings
**PUT** `/api/v1/organizations/{organization}/settings`

---

### Audit Logs

#### List Audit Logs
**GET** `/api/v1/organizations/{organization}/audit-logs`

---

## Error Codes

| Status | Code | Description |
|--------|------|-------------|
| 400 | INVALID_REQUEST | Malformed request body |
| 401 | UNAUTHENTICATED | Missing or invalid token |
| 403 | FORBIDDEN | Insufficient permissions |
| 404 | NOT_FOUND | Resource not found |
| 422 | VALIDATION_ERROR | Request validation failed |
| 429 | TOO_MANY_REQUESTS | Rate limit exceeded |
| 500 | SERVER_ERROR | Internal server error |

## Rate Limiting

- **Authenticated**: 60 requests per minute
- **Public**: 30 requests per minute

Rate limit headers are included in every response:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1700000000
```
