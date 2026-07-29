/**
 * Astra OS Enterprise — E2E Tests
 * 
 * These tests validate the full application flow:
 * authentication -> campaign management -> agent operations -> analytics
 */

const { test, expect } = require('@playwright/test');

// Test user credentials
const TEST_USER = {
  name: 'E2E Test User',
  email: `e2e-${Date.now()}@test.astra-os.com`,
  password: 'TestPass123!',
};

test.describe('Authentication', () => {
  test('should register a new user', async ({ request }) => {
    const response = await request.post('/api/v1/auth/register', {
      data: TEST_USER,
    });
    expect(response.status()).toBe(201);
    
    const body = await response.json();
    expect(body.success).toBe(true);
    expect(body.data).toHaveProperty('token');
    expect(body.data).toHaveProperty('user');
    expect(body.data.user.email).toBe(TEST_USER.email);
    
    // Save token for subsequent tests
    test.info().attach('auth-token', {
      body: body.data.token,
      contentType: 'text/plain',
    });
  });

  test('should login with existing credentials', async ({ request }) => {
    // First register
    await request.post('/api/v1/auth/register', { data: TEST_USER });
    
    // Then login
    const response = await request.post('/api/v1/auth/login', {
      data: {
        email: TEST_USER.email,
        password: TEST_USER.password,
      },
    });
    expect(response.status()).toBe(200);
    
    const body = await response.json();
    expect(body.success).toBe(true);
    expect(body.data).toHaveProperty('token');
    expect(body.data).toHaveProperty('user');
  });

  test('should reject invalid credentials', async ({ request }) => {
    const response = await request.post('/api/v1/auth/login', {
      data: {
        email: 'nonexistent@test.com',
        password: 'wrong-password',
      },
    });
    expect(response.status()).toBe(401);
  });

  test('should reject registration with duplicate email', async ({ request }) => {
    await request.post('/api/v1/auth/register', { data: TEST_USER });
    
    const response = await request.post('/api/v1/auth/register', {
      data: TEST_USER,
    });
    expect(response.status()).toBe(422);
  });

  test('should logout and invalidate token', async ({ request }) => {
    const registerResponse = await request.post('/api/v1/auth/register', {
      data: TEST_USER,
    });
    const token = (await registerResponse.json()).data.token;
    
    const logoutResponse = await request.post('/api/v1/auth/logout', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(logoutResponse.status()).toBe(200);
    
    // Token should be invalid now
    const meResponse = await request.get('/api/v1/auth/me', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(meResponse.status()).toBe(401);
  });
});

test.describe('Organization Management', () => {
  let authToken;
  let organizationId;

  test.beforeAll(async ({ request }) => {
    const res = await request.post('/api/v1/auth/register', { data: TEST_USER });
    authToken = (await res.json()).data.token;
  });

  test('should create an organization', async ({ request }) => {
    const response = await request.post('/api/v1/organizations', {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        name: 'E2E Test Agency',
        slug: `e2e-agency-${Date.now()}`,
        timezone: 'UTC',
        locale: 'en',
      },
    });
    expect(response.status()).toBe(201);
    
    const body = await response.json();
    expect(body.success).toBe(true);
    expect(body.data.name).toBe('E2E Test Agency');
    
    organizationId = body.data.id;
  });

  test('should list organizations', async ({ request }) => {
    const response = await request.get('/api/v1/organizations', {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
    
    const body = await response.json();
    expect(body.data).toBeInstanceOf(Array);
    expect(body.data.length).toBeGreaterThan(0);
  });

  test('should get organization details', async ({ request }) => {
    const response = await request.get(`/api/v1/organizations/${organizationId}`, {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
  });

  test('should update organization', async ({ request }) => {
    const response = await request.put(`/api/v1/organizations/${organizationId}`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: { name: 'E2E Test Agency (Updated)' },
    });
    expect(response.status()).toBe(200);
  });
});

test.describe('Campaign Management', () => {
  let authToken;
  let organizationId;
  let campaignId;

  test.beforeAll(async ({ request }) => {
    const regRes = await request.post('/api/v1/auth/register', { data: TEST_USER });
    authToken = (await regRes.json()).data.token;
    
    const orgRes = await request.post('/api/v1/organizations', {
      headers: { Authorization: `Bearer ${authToken}` },
      data: { name: 'Campaign Test Org', slug: `camp-test-${Date.now()}`, timezone: 'UTC' },
    });
    organizationId = (await orgRes.json()).data.id;
  });

  test('should create a campaign', async ({ request }) => {
    const response = await request.post('/api/v1/campaigns', {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        organization_id: organizationId,
        name: 'E2E Test Campaign',
        platform: 'google',
        budget: 1000.00,
        currency: 'USD',
        objective: 'conversions',
        target_audience: { geo: ['US'], age_range: '25-54' },
        start_date: new Date(Date.now() + 86400000).toISOString().split('T')[0],
        end_date: new Date(Date.now() + 86400000 * 30).toISOString().split('T')[0],
      },
    });
    expect(response.status()).toBe(201);
    
    const body = await response.json();
    expect(body.data.name).toBe('E2E Test Campaign');
    campaignId = body.data.id;
  });

  test('should list campaigns with pagination', async ({ request }) => {
    const response = await request.get('/api/v1/campaigns?per_page=10', {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
    
    const body = await response.json();
    expect(body.meta).toHaveProperty('per_page');
  });

  test('should launch a campaign', async ({ request }) => {
    const response = await request.post(`/api/v1/campaigns/${campaignId}/launch`, {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
  });

  test('should pause a campaign', async ({ request }) => {
    const response = await request.post(`/api/v1/campaigns/${campaignId}/pause`, {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
  });

  test('should get campaign analytics', async ({ request }) => {
    const response = await request.get(`/api/v1/campaigns/${campaignId}`, {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
  });

  test('should delete a campaign', async ({ request }) => {
    const response = await request.delete(`/api/v1/campaigns/${campaignId}`, {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
  });
});

test.describe('AI Agent Operations', () => {
  let authToken;
  let organizationId;
  let agentId;

  test.beforeAll(async ({ request }) => {
    const regRes = await request.post('/api/v1/auth/register', { data: TEST_USER });
    authToken = (await regRes.json()).data.token;
    
    const orgRes = await request.post('/api/v1/organizations', {
      headers: { Authorization: `Bearer ${authToken}` },
      data: { name: 'Agent Test Org', slug: `agent-test-${Date.now()}`, timezone: 'UTC' },
    });
    organizationId = (await orgRes.json()).data.id;
  });

  test('should create an AI agent', async ({ request }) => {
    const response = await request.post('/api/v1/agents', {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        organization_id: organizationId,
        name: 'E2E Content Agent',
        role: 'content_creator',
        model: 'gpt-4o',
        instructions: 'Create engaging ad copy for social media campaigns.',
        temperature: 0.7,
        max_tokens: 2000,
      },
    });
    expect(response.status()).toBe(201);
    
    const body = await response.json();
    expect(body.data.name).toBe('E2E Content Agent');
    agentId = body.data.id;
  });

  test('should assign a task to an agent', async ({ request }) => {
    const response = await request.post(`/api/v1/agents/${agentId}/assign-task`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        campaign_id: null,
        task_type: 'content_generation',
        prompt: 'Write a Facebook ad for a summer sale event.',
        priority: 'high',
      },
    });
    expect(response.status()).toBe(200);
  });

  test('should list agents', async ({ request }) => {
    const response = await request.get('/api/v1/agents', {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body.data).toBeInstanceOf(Array);
  });

  test('should get agent status', async ({ request }) => {
    const response = await request.get(`/api/v1/agents/${agentId}`, {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
  });
});

test.describe('Workflow Automation', () => {
  let authToken;
  let organizationId;
  let workflowId;

  test.beforeAll(async ({ request }) => {
    const regRes = await request.post('/api/v1/auth/register', { data: TEST_USER });
    authToken = (await regRes.json()).data.token;
    
    const orgRes = await request.post('/api/v1/organizations', {
      headers: { Authorization: `Bearer ${authToken}` },
      data: { name: 'Workflow Test Org', slug: `wf-test-${Date.now()}`, timezone: 'UTC' },
    });
    organizationId = (await orgRes.json()).data.id;
  });

  test('should create a workflow', async ({ request }) => {
    const response = await request.post('/api/v1/workflows', {
      headers: { Authorization: `Bearer ${authToken}` },
      data: {
        organization_id: organizationId,
        name: 'E2E Test Workflow',
        description: 'Automated campaign optimization workflow',
        trigger_type: 'schedule',
        trigger_config: { cron: '0 6 * * *' },
        steps: [
          { order: 1, action: 'analyze_performance', config: { metric: 'ctr', threshold: 0.02 } },
          { order: 2, action: 'adjust_budget', config: { adjustment_pct: 10 } },
        ],
      },
    });
    expect(response.status()).toBe(201);
    workflowId = (await response.json()).data.id;
  });

  test('should execute a workflow', async ({ request }) => {
    const response = await request.post(`/api/v1/workflows/${workflowId}/execute`, {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
  });

  test('should list workflows', async ({ request }) => {
    const response = await request.get('/api/v1/workflows?per_page=20', {
      headers: { Authorization: `Bearer ${authToken}` },
    });
    expect(response.status()).toBe(200);
  });
});

test.describe('Health & System', () => {
  test('should return liveness status', async ({ request }) => {
    const response = await request.get('/api/health');
    expect(response.status()).toBe(200);
    
    const body = await response.json();
    expect(body).toHaveProperty('status');
    expect(body.status).toBe('ok');
  });

  test('should return readiness status', async ({ request }) => {
    const response = await request.get('/api/health/readiness');
    expect(response.status()).toBe(200);
  });

  test('should reject unauthenticated requests', async ({ request }) => {
    const response = await request.get('/api/v1/campaigns');
    expect(response.status()).toBe(401);
  });

  test('should enforce rate limiting', async ({ request }) => {
    const responses = await Promise.all(
      Array(100).fill(null).map(() => 
        request.get('/api/v1/auth/login', {
          data: { email: 'test@test.com', password: 'test' },
        })
      )
    );
    const statuses = responses.map(r => r.status());
    expect(statuses).toContain(429);
  });

  test('should include security headers', async ({ request }) => {
    const response = await request.get('/api/health');
    const headers = response.headers();
    
    // Check for security headers
    const securityHeaders = [
      'x-content-type-options',
      'x-frame-options',
      'x-xss-protection',
      'strict-transport-security',
      'content-security-policy',
    ];
    
    const presentHeaders = securityHeaders.filter(h => headers[h.toLowerCase()]);
    expect(presentHeaders.length).toBeGreaterThanOrEqual(3);
  });
});
