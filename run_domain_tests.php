<?php

/**
 * Quick test runner for domain unit tests.
 * Runs each test class manually using PHP reflection.
 */

declare(strict_types=1);

require __DIR__ . '/test_bootstrap.php';

use App\Domain\Common\ValueObjects\Email;
use App\Domain\Common\ValueObjects\Money;
use App\Domain\Common\ValueObjects\Address;
use App\Domain\Common\Traits\HasTimestamps;
use App\Domain\Common\Traits\HasDomainEvents;
use App\Domain\Organization\Entities\Organization;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Agent\Entities\Agent;
use App\Domain\Agent\ValueObjects\AgentRole;
use App\Domain\Agent\ValueObjects\AutonomyLevel;
use App\Domain\Workflow\Entities\Workflow;
use App\Domain\Workflow\Entities\WorkflowNode;
use App\Domain\Workflow\Entities\WorkflowEdge;
use App\Domain\Workflow\Entities\WorkflowExecution;
use App\Domain\Social\Entities\SocialPost;
use InvalidArgumentException;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

$passed = 0;
$failed = 0;
$errors = [];

function test(string $name, callable $fn): void {
    global $passed, $failed, $errors;
    try {
        $fn();
        echo "  PASS: {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  FAIL: {$name}\n";
        echo "    " . $e->getMessage() . "\n";
        echo "    in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
        $errors[] = $name . ': ' . $e->getMessage();
    }
}

function assertTrue(bool $condition, string $message = 'Assertion failed'): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertFalse(bool $condition, string $message = 'Assertion failed'): void {
    if ($condition) {
        throw new RuntimeException($message);
    }
}

function assertNull(mixed $value, string $message = 'Expected null'): void {
    if ($value !== null) {
        throw new RuntimeException($message . ', got: ' . get_debug_type($value));
    }
}

function assertNotNull(mixed $value, string $message = 'Expected non-null'): void {
    if ($value === null) {
        throw new RuntimeException($message);
    }
}

function assertSame(mixed $expected, mixed $actual, string $message = ''): void {
    if ($expected !== $actual) {
        throw new RuntimeException(
            ($message ? $message . ': ' : '') .
            'Expected ' . var_export($expected, true) .
            ' but got ' . var_export($actual, true)
        );
    }
}

function assertCount(int $expected, iterable $items, string $message = ''): void {
    $actual = is_array($items) ? count($items) : iterator_count($items);
    if ($actual !== $expected) {
        throw new RuntimeException(
            ($message ? $message . ': ' : '') .
            "Expected count {$expected} but got {$actual}"
        );
    }
}

function assertInstanceOf(string $class, mixed $object, string $message = ''): void {
    if (!$object instanceof $class) {
        throw new RuntimeException(
            ($message ? $message . ': ' : '') .
            "Expected instance of {$class}, got " . get_debug_type($object)
        );
    }
}

function assertIsString(mixed $value, string $message = ''): void {
    if (!is_string($value)) {
        throw new RuntimeException(($message ?: 'Expected string') . ', got: ' . get_debug_type($value));
    }
}

function assertIsArray(mixed $value, string $message = ''): void {
    if (!is_array($value)) {
        throw new RuntimeException(($message ?: 'Expected array') . ', got: ' . get_debug_type($value));
    }
}

function expectException(string $exceptionClass, callable $fn): void {
    try {
        $fn();
        throw new RuntimeException("Expected exception {$exceptionClass} but none was thrown");
    } catch (Throwable $e) {
        if (!$e instanceof $exceptionClass) {
            throw new RuntimeException(
                "Expected exception {$exceptionClass} but got " . get_class($e) . ': ' . $e->getMessage()
            );
        }
        // OK - exception was thrown as expected
    }
}

// ============================================================
// EMAIL TEST
// ============================================================
echo "\n=== Email Value Object ===\n";

test('creates valid email', function () {
    $email = new Email('user@example.com');
    assertSame('user@example.com', $email->getValue());
});

test('normalizes to lowercase', function () {
    $email = new Email('User@Example.COM');
    assertSame('user@example.com', $email->getValue());
});

test('trims whitespace', function () {
    $email = new Email('  user@example.com  ');
    assertSame('user@example.com', $email->getValue());
});

test('toString returns value', function () {
    $email = new Email('user@example.com');
    assertSame('user@example.com', $email->toString());
    assertSame('user@example.com', (string) $email);
});

test('extracts local part', function () {
    $email = new Email('john.doe@example.com');
    assertSame('john.doe', $email->getLocalPart());
});

test('extracts domain', function () {
    $email = new Email('john.doe@example.co.uk');
    assertSame('example.co.uk', $email->getDomain());
});

test('equality', function () {
    $a = new Email('user@example.com');
    $b = new Email('user@example.com');
    assertTrue($a->equals($b));
    assertFalse($a->equals(new Email('other@example.com')));
});

test('case insensitive equality', function () {
    assertTrue((new Email('User@Example.COM'))->equals(new Email('user@example.com')));
});

test('throws for empty string', function () {
    expectException(InvalidArgumentException::class, fn() => new Email(''));
});

test('throws for missing @', function () {
    expectException(InvalidArgumentException::class, fn() => new Email('userexample.com'));
});

test('throws for missing domain', function () {
    expectException(InvalidArgumentException::class, fn() => new Email('user@'));
});

test('throws for domain without dot', function () {
    expectException(InvalidArgumentException::class, fn() => new Email('user@example'));
});

// ============================================================
// MONEY TEST
// ============================================================
echo "\n=== Money Value Object ===\n";

test('creates with default currency', function () {
    $m = new Money(100.0);
    assertSame(100.0, $m->getAmount());
    assertSame('USD', $m->getCurrency());
});

test('creates with custom currency', function () {
    $m = new Money(250.50, 'EUR');
    assertSame(250.50, $m->getAmount());
    assertSame('EUR', $m->getCurrency());
});

test('creates zero amount', function () {
    $m = new Money(0.0);
    assertSame(0.0, $m->getAmount());
});

test('throws for negative amount', function () {
    expectException(InvalidArgumentException::class, fn() => new Money(-1.0));
});

test('throws for empty currency', function () {
    expectException(InvalidArgumentException::class, fn() => new Money(100.0, ''));
});

test('adds same currency', function () {
    $result = (new Money(100.0))->add(new Money(50.0));
    assertSame(150.0, $result->getAmount());
});

test('subtracts same currency', function () {
    $result = (new Money(100.0))->subtract(new Money(30.0));
    assertSame(70.0, $result->getAmount());
});

test('multiplies', function () {
    $result = (new Money(100.0))->multiply(2.5);
    assertSame(250.0, $result->getAmount());
});

test('throws when adding different currencies', function () {
    expectException(InvalidArgumentException::class, fn() => (new Money(100.0, 'USD'))->add(new Money(50.0, 'EUR')));
});

test('compares greater/less than', function () {
    assertTrue((new Money(200.0))->isGreaterThan(new Money(100.0)));
    assertTrue((new Money(50.0))->isLessThan(new Money(100.0)));
});

test('equality', function () {
    assertTrue((new Money(100.0, 'USD'))->equals(new Money(100.0, 'USD')));
    assertFalse((new Money(100.0, 'USD'))->equals(new Money(200.0, 'USD')));
    assertFalse((new Money(100.0, 'USD'))->equals(new Money(100.0, 'EUR')));
});

test('immutability on add', function () {
    $original = new Money(100.0);
    $original->add(new Money(50.0));
    assertSame(100.0, $original->getAmount());
});

test('serializes to array', function () {
    assertSame(['amount' => 99.99, 'currency' => 'GBP'], (new Money(99.99, 'GBP'))->toArray());
});

// ============================================================
// ADDRESS TEST
// ============================================================
echo "\n=== Address Value Object ===\n";

test('creates full address', function () {
    $a = new Address('123 Main St', 'San Francisco', 'CA', '94105', 'US');
    assertSame('123 Main St', $a->getStreet());
    assertSame('San Francisco', $a->getCity());
    assertSame('CA', $a->getState());
    assertSame('94105', $a->getPostalCode());
    assertSame('US', $a->getCountry());
});

test('toArray serialization', function () {
    $a = new Address('456 Oak Ave', 'Austin', 'TX', '73301', 'US');
    assertSame([
        'street' => '456 Oak Ave',
        'city' => 'Austin',
        'state' => 'TX',
        'postal_code' => '73301',
        'country' => 'US',
    ], $a->toArray());
});

test('__toString formatting', function () {
    $a = new Address('123 Main St', 'San Francisco', 'CA', '94105', 'US');
    assertSame('123 Main St, San Francisco, CA 94105, US', (string) $a);
});

test('equality', function () {
    $a = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');
    $b = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');
    assertTrue($a->equals($b));
    assertFalse($a->equals(new Address('2 Main St', 'NYC', 'NY', '10001', 'US')));
});

// ============================================================
// ORGANIZATION TEST
// ============================================================
echo "\n=== Organization Entity ===\n";

test('creates with required fields', function () {
    $org = new Organization('Astra Corp', 'astra-corp', 1);
    assertSame('Astra Corp', $org->getName());
    assertSame('astra-corp', $org->getSlug());
    assertSame(1, $org->getOwnerId());
    assertNull($org->getId());
});

test('creates with optional fields', function () {
    $org = new Organization('Full Org', 'full-org', 42,
        'A full organization', 'logo.png', 'https://example.com',
        ['timezone' => 'UTC', 'locale' => 'en_US']
    );
    assertSame('Full Org', $org->getName());
    assertSame('A full organization', $org->getDescription());
    assertSame('logo.png', $org->getLogo());
    assertSame('https://example.com', $org->getWebsite());
    assertSame(['timezone' => 'UTC', 'locale' => 'en_US'], $org->getSettings());
});

test('sets and gets id', function () {
    $org = new Organization('Test', 'test', 1);
    $org->setId(99);
    assertSame(99, $org->getId());
});

test('has timestamps', function () {
    $org = new Organization('TS', 'ts', 1);
    assertNotNull($org->getCreatedAt());
    assertNotNull($org->getUpdatedAt());
});

test('updateSettings merges correctly', function () {
    $org = new Organization('Org', 'org', 1, settings: ['timezone' => 'UTC']);
    $org->updateSettings(['locale' => 'en_US']);
    assertSame(['timezone' => 'UTC', 'locale' => 'en_US'], $org->getSettings());
});

test('updateSettings overwrites existing keys', function () {
    $org = new Organization('Org', 'org', 1, settings: ['timezone' => 'UTC', 'locale' => 'fr_FR']);
    $org->updateSettings(['locale' => 'de_DE']);
    assertSame(['timezone' => 'UTC', 'locale' => 'de_DE'], $org->getSettings());
});

test('serializes to array', function () {
    $org = new Organization('My Org', 'my-org', 7, 'Desc', 'logo.png', 'https://my.org', ['key' => 'val']);
    $org->setId(5);
    $array = $org->toArray();
    assertSame(5, $array['id']);
    assertSame('My Org', $array['name']);
    assertSame('my-org', $array['slug']);
    assertSame('Desc', $array['description']);
    assertSame(7, $array['owner_id']);
});

// ============================================================
// CAMPAIGN TEST
// ============================================================
echo "\n=== Campaign Entity ===\n";

function createDraftCampaign(array $ov = []): Campaign {
    $future = new DateTimeImmutable('+30 days');
    return new Campaign(
        $ov['name'] ?? 'Test Campaign',
        $ov['objective'] ?? 'brand_awareness',
        $ov['budget'] ?? new Money(10000.0, 'USD'),
        $ov['targetAudience'] ?? ['age' => ['18-65']],
        $ov['platforms'] ?? ['facebook'],
        $ov['startDate'] ?? $future,
        $ov['endDate'] ?? $future->modify('+60 days'),
        $ov['organizationId'] ?? 1,
        $ov['createdBy'] ?? null,
        $ov['metadata'] ?? [],
    );
}

test('creates as draft', function () {
    $c = createDraftCampaign();
    assertSame('draft', $c->getStatus());
    assertNull($c->getLaunchedAt());
});

test('launches from draft as scheduled (future date)', function () {
    $c = createDraftCampaign(['startDate' => new DateTimeImmutable('+30 days')]);
    $c->launch();
    assertSame('scheduled', $c->getStatus());
    assertNotNull($c->getLaunchedAt());
});

test('launches from draft as active (past date)', function () {
    $c = createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
    $c->launch();
    assertSame('active', $c->getStatus());
});

test('pauses active campaign', function () {
    $c = createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
    $c->launch();
    $c->pause();
    assertSame('paused', $c->getStatus());
});

test('resumes from paused', function () {
    $c = createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
    $c->launch();
    $c->pause();
    $c->launch();
    assertSame('active', $c->getStatus());
});

test('archives from draft', function () {
    $c = createDraftCampaign();
    $c->archive();
    assertSame('archived', $c->getStatus());
});

test('throws when launching archived', function () {
    expectException(RuntimeException::class, function () {
        $c = createDraftCampaign();
        $c->archive();
        $c->launch();
    });
});

test('throws when pausing draft', function () {
    expectException(RuntimeException::class, function () {
        $c = createDraftCampaign();
        $c->pause();
    });
});

test('throws when archiving already archived', function () {
    expectException(RuntimeException::class, function () {
        $c = createDraftCampaign();
        $c->archive();
        $c->archive();
    });
});

test('updates budget', function () {
    $c = createDraftCampaign();
    $newBudget = new Money(25000.0, 'USD');
    $c->updateBudget($newBudget);
    assertSame($newBudget, $c->getBudget());
});

test('serializes to array', function () {
    $c = createDraftCampaign(['name' => 'Serialize Test', 'organizationId' => 5]);
    $c->setId(10);
    $array = $c->toArray();
    assertSame(10, $array['id']);
    assertSame('Serialize Test', $array['name']);
    assertSame('draft', $array['status']);
    assertSame(5, $array['organization_id']);
});

// ============================================================
// AGENT TEST
// ============================================================
echo "\n=== Agent Entity & Value Objects ===\n";

test('creates agent with required fields', function () {
    $a = new Agent('Analyst', 'analytics_director', 'Handles analytics', 'gpt-4o', 10);
    assertSame('Analyst', $a->getName());
    assertSame('analytics_director', $a->getRole());
    assertSame('gpt-4o', $a->getModel());
    assertSame(10, $a->getOrganizationId());
    assertSame('idle', $a->getStatus());
});

test('sets capabilities and config', function () {
    $a = new Agent('Full', 'ceo', 'CEO agent', 'gpt-4o', 1,
        ['strategy', 'planning'], ['temperature' => 0.7]
    );
    assertSame(['strategy', 'planning'], $a->getCapabilities());
    assertSame(['temperature' => 0.7], $a->getConfiguration());
});

test('changes status', function () {
    $a = new Agent('Agent', 'specialist', 'desc', 'gpt-4o', 1);
    $a->setStatus('busy');
    assertSame('busy', $a->getStatus());
});

test('sets id', function () {
    $a = new Agent('Agent', 'specialist', 'desc', 'gpt-4o', 1);
    $a->setId(42);
    assertSame(42, $a->getId());
});

test('AgentRole fromString', function () {
    assertSame('ceo', AgentRole::fromString('ceo')->getValue());
    assertSame('marketing_director', AgentRole::marketingDirector()->getValue());
    assertSame('specialist', AgentRole::specialist()->getValue());
    assertSame('CEO', AgentRole::ceo()->getLabel());
});

test('AgentRole invalid throws', function () {
    expectException(InvalidArgumentException::class, fn() => AgentRole::fromString('invalid_role_xyz'));
    expectException(InvalidArgumentException::class, fn() => AgentRole::fromString(''));
});

test('AutonomyLevel defaults to advisory', function () {
    $level = new AutonomyLevel();
    assertSame('advisory', $level->getValue());
    assertTrue($level->isAdvisory());
});

test('AutonomyLevel named checks', function () {
    assertTrue((new AutonomyLevel('semi_auto'))->isSemiAuto());
    assertTrue((new AutonomyLevel('full_auto'))->isFullAuto());
});

test('AutonomyLevel invalid throws', function () {
    expectException(InvalidArgumentException::class, fn() => new AutonomyLevel('ultra_auto'));
});

test('agent serializes to array', function () {
    $a = new Agent('Ser', 'ad_director', 'desc', 'gpt-4o-mini', 3, ['ad_management']);
    $a->setId(15);
    $a->setStatus('busy');
    $array = $a->toArray();
    assertSame(15, $array['id']);
    assertSame('Ser', $array['name']);
    assertSame('busy', $array['status']);
});

// ============================================================
// WORKFLOW TEST
// ============================================================
echo "\n=== Workflow Aggregate Root ===\n";

test('creates as draft with events', function () {
    $wf = Workflow::create(Uuid::uuid4(), 'Test WF', 'desc');
    assertSame('draft', $wf->getStatus());
    assertTrue($wf->isDraft());
    assertSame(1, $wf->getVersion());
    assertNotNull($wf->getId());
    assertCount(1, $wf->getDomainEvents()); // WorkflowCreated
    assertInstanceOf(\App\Domain\Workflow\Events\WorkflowCreated::class, $wf->getDomainEvents()[0]);
});

test('throws for empty name', function () {
    expectException(InvalidArgumentException::class, fn() => Workflow::create(Uuid::uuid4(), ''));
    expectException(InvalidArgumentException::class, fn() => Workflow::create(Uuid::uuid4(), '   '));
});

test('adds nodes and edges', function () {
    $wf = Workflow::create(Uuid::uuid4(), 'Node WF');
    $node1 = WorkflowNode::create($wf->getId(), 'trigger');
    $node2 = WorkflowNode::create($wf->getId(), 'action');
    $wf->addNode($node1);
    $wf->addNode($node2);
    assertCount(2, $wf->getNodes());

    $edge = WorkflowEdge::create($wf->getId(), $node1->getId()->toString(), $node2->getId()->toString());
    $wf->addEdge($edge);
    assertCount(1, $wf->getEdges());
});

test('activates from draft and records event', function () {
    $wf = Workflow::create(Uuid::uuid4(), 'Activate WF');
    $wf->clearDomainEvents();
    $wf->activate();
    assertSame('active', $wf->getStatus());
    assertCount(1, $wf->getDomainEvents());
    assertInstanceOf(\App\Domain\Workflow\Events\WorkflowActivated::class, $wf->getDomainEvents()[0]);
});

test('activate is idempotent', function () {
    $wf = Workflow::create(Uuid::uuid4(), 'Idempotent');
    $wf->clearDomainEvents();
    $wf->activate();
    $wf->clearDomainEvents();
    $wf->activate(); // no-op
    assertEmpty($wf->getDomainEvents());
});

test('pauses from active', function () {
    $wf = Workflow::create(Uuid::uuid4(), 'Pause WF');
    $wf->activate();
    $wf->pause();
    assertTrue($wf->isPaused());
});

test('throws when pausing from draft', function () {
    expectException(InvalidArgumentException::class, function () {
        $wf = Workflow::create(Uuid::uuid4(), 'No Pause');
        $wf->pause();
    });
});

test('archives from any state', function () {
    $wf = Workflow::create(Uuid::uuid4(), 'Archive WF');
    $wf->archive();
    assertSame('archived', $wf->getStatus());
});

test('renames', function () {
    $wf = Workflow::create(Uuid::uuid4(), 'Old');
    $wf->rename('New');
    assertSame('New', $wf->getName());
});

test('increments version', function () {
    $wf = Workflow::create(Uuid::uuid4(), 'Versioned');
    assertSame(1, $wf->getVersion());
    $wf->incrementVersion();
    assertSame(2, $wf->getVersion());
});

test('reconstitutes from persistence', function () {
    $id = Uuid::uuid4();
    $orgId = Uuid::uuid4();
    $createdAt = new DateTimeImmutable('2024-01-01');
    $updatedAt = new DateTimeImmutable('2024-06-01');
    $wf = Workflow::reconstitute($id, $orgId, null, 'Recon', 'persisted', [], [], 'active', 3, [], $createdAt, $updatedAt);
    assertSame($id->toString(), $wf->getIdString());
    assertSame('Recon', $wf->getName());
    assertSame('active', $wf->getStatus());
    assertSame(3, $wf->getVersion());
    assertSame($createdAt, $wf->getCreatedAt());
    assertSame($updatedAt, $wf->getUpdatedAt());
    assertEmpty($wf->getDomainEvents());
});

// ============================================================
// SOCIAL POST TEST
// ============================================================
echo "\n=== SocialPost Entity ===\n";

test('creates draft post', function () {
    $post = SocialPost::create(Uuid::uuid4(), 'Hello world!');
    assertSame('draft', $post->getStatus());
    assertTrue($post->isDraft());
    assertNull($post->getScheduledAt());
    assertEmpty($post->getMedia());
    assertEmpty($post->getMetrics());
});

test('creates scheduled post with future date', function () {
    $scheduledAt = new DateTimeImmutable('+7 days');
    $post = SocialPost::create(Uuid::uuid4(), 'Scheduled', scheduledAt: $scheduledAt);
    assertSame('scheduled', $post->getStatus());
    assertSame($scheduledAt, $post->getScheduledAt());
});

test('creates post with media attachments', function () {
    $media = ['https://example.com/img.jpg'];
    $post = SocialPost::create(Uuid::uuid4(), 'With media', media: $media);
    assertSame($media, $post->getMedia());
});

test('schedules an existing draft', function () {
    $post = SocialPost::create(Uuid::uuid4(), 'Draft');
    $future = new DateTimeImmutable('+3 days');
    $post->schedule($future);
    assertSame('scheduled', $post->getStatus());
    assertSame($future, $post->getScheduledAt());
});

test('publishes from draft', function () {
    $post = SocialPost::create(Uuid::uuid4(), 'Publish');
    $post->publish('platform-post-123');
    assertSame('published', $post->getStatus());
    assertTrue($post->isPublished());
    assertSame('platform-post-123', $post->getPlatformPostId());
    assertNotNull($post->getPublishedAt());
});

test('marks as failed', function () {
    $post = SocialPost::create(Uuid::uuid4(), 'Fail');
    $post->fail();
    assertSame('failed', $post->getStatus());
});

test('updates metrics', function () {
    $post = SocialPost::create(Uuid::uuid4(), 'Metrics');
    $post->updateMetrics(['likes' => 42, 'shares' => 10]);
    assertSame(['likes' => 42, 'shares' => 10], $post->getMetrics());
});

test('reconstitutes from persistence', function () {
    $id = Uuid::uuid4();
    $accountId = Uuid::uuid4();
    $createdAt = new DateTimeImmutable('2024-01-01');
    $updatedAt = new DateTimeImmutable('2024-06-01');
    $post = SocialPost::reconstitute(
        $id, $accountId, null, 'Content', [], null, null, 'draft', null, [], $createdAt, $updatedAt
    );
    assertSame($id->toString(), $post->getId()->toString());
    assertSame('draft', $post->getStatus());
    assertSame($createdAt, $post->getCreatedAt());
});

// ============================================================
// TRAITS TEST
// ============================================================
echo "\n=== HasTimestamps Trait ===\n";

$creatable = new class {
    use \App\Domain\Common\Traits\HasTimestamps;
    public function __construct() {}
};

test('timestamps null before init', function () use ($creatable) {
    // Create fresh instance
    $e = new class { use HasTimestamps; };
    assertNull($e->getCreatedAt());
    assertNull($e->getUpdatedAt());
});

test('initializeTimestamps sets both', function () {
    $e = new class { use HasTimestamps; };
    $e->initializeTimestamps();
    assertNotNull($e->getCreatedAt());
    assertNotNull($e->getUpdatedAt());
});

test('initializeTimestamps preserves createdAt', function () {
    $e = new class { use HasTimestamps; };
    $e->initializeTimestamps();
    $original = $e->getCreatedAt();
    usleep(2000);
    $e->initializeTimestamps();
    assertSame($original, $e->getCreatedAt());
});

test('markAsUpdated changes updatedAt', function () {
    $e = new class { use HasTimestamps; };
    $e->initializeTimestamps();
    $original = $e->getUpdatedAt();
    usleep(2000);
    $e->markAsUpdated();
    assertTrue($e->getUpdatedAt() > $original);
});

test('setCreatedAt/setUpdatedAt for reconstitution', function () {
    $e = new class { use HasTimestamps; };
    $created = new DateTimeImmutable('2024-01-01');
    $updated = new DateTimeImmutable('2024-06-01');
    $e->setCreatedAt($created);
    $e->setUpdatedAt($updated);
    assertSame($created, $e->getCreatedAt());
    assertSame($updated, $e->getUpdatedAt());
});

echo "\n=== HasDomainEvents Trait ===\n";

test('starts with no events', function () {
    $e = new class { use HasDomainEvents; };
    assertEmpty($e->getDomainEvents());
});

test('records and retrieves events', function () {
    $e = new class { use HasDomainEvents; };
    $event = new App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'test');
    $e->recordDomainEvent($event);
    assertCount(1, $e->getDomainEvents());
    assertSame($event, $e->getDomainEvents()[0]);
});

test('records multiple events in order', function () {
    $e = new class { use HasDomainEvents; };
    $e1 = new App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'first');
    $e2 = new App\Domain\Workflow\Events\WorkflowActivated('2');
    $e->recordDomainEvent($e1);
    $e->recordDomainEvent($e2);
    assertCount(2, $e->getDomainEvents());
    assertSame($e1, $e->getDomainEvents()[0]);
    assertSame($e2, $e->getDomainEvents()[1]);
});

test('clears events', function () {
    $e = new class { use HasDomainEvents; };
    $e->recordDomainEvent(new App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'test'));
    $e->clearDomainEvents();
    assertEmpty($e->getDomainEvents());
});

test('independent per instance', function () {
    $a = new class { use HasDomainEvents; };
    $b = new class { use HasDomainEvents; };
    $a->recordDomainEvent(new App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'a'));
    assertCount(1, $a->getDomainEvents());
    assertCount(0, $b->getDomainEvents());
});

// ============================================================
// SUMMARY
// ============================================================
echo "\n========================================\n";
echo "RESULTS: {$passed} passed, {$failed} failed\n";
if ($failed > 0) {
    echo "\nFAILURES:\n";
    foreach ($errors as $i => $error) {
        echo "  {$i}) {$error}\n";
    }
    exit(1);
}
echo "ALL TESTS PASSED!\n";
