<?php

/**
 * Quick test runner for domain unit tests.
 * Runs each test class manually. Includes stub for ramsey/uuid since
 * Composer dependencies are not installed in this environment.
 */

declare(strict_types=1);

// --- Ramsey\Uuid stub (minimal, for test purposes only) ---
namespace Ramsey\Uuid {
    interface UuidInterface
    {
        public function toString(): string;
        public function __toString(): string;
        public function equals(UuidInterface $other): bool;
        public function getHex(): string;
    }

    class Uuid
    {
        public static function uuid4(): UuidInterface
        {
            $data = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
            $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
            return new class($uuid) implements UuidInterface {
                public function __construct(private readonly string $uuid) {}
                public function toString(): string { return $this->uuid; }
                public function __toString(): string { return $this->uuid; }
                public function equals(UuidInterface $other): bool { return $this->uuid === $other->toString(); }
                public function getHex(): string { return str_replace('-', '', $this->uuid); }
            };
        }
    }
}

namespace {
    // --- Bootstrap ---
    require __DIR__ . '/vendor/autoload.php';

    // Register PSR-4 for test classes
    spl_autoload_register(function (string $class): void {
        $testsPrefix = 'Tests\\';
        if (str_starts_with($class, $testsPrefix)) {
            $relativeClass = substr($class, strlen($testsPrefix));
            $file = __DIR__ . '/tests/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    });

    // --- Test Helpers ---
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
        if (!$condition) throw new RuntimeException($message);
    }

    function assertFalse(bool $condition, string $message = 'Assertion failed'): void {
        if ($condition) throw new RuntimeException($message);
    }

    function assertNull(mixed $value, string $message = 'Expected null'): void {
        if ($value !== null) throw new RuntimeException($message . ', got: ' . get_debug_type($value));
    }

    function assertNotNull(mixed $value, string $message = 'Expected non-null'): void {
        if ($value === null) throw new RuntimeException($message);
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

    function assertIsArray(mixed $value, string $message = ''): void {
        if (!is_array($value)) throw new RuntimeException(($message ?: 'Expected array') . ', got: ' . get_debug_type($value));
    }

    function assertEmpty(mixed $value, string $message = 'Expected empty'): void {
        if (!empty($value)) throw new RuntimeException($message . ', got: ' . get_debug_type($value));
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
        }
    }

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
    use Ramsey\Uuid\UuidInterface;

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

    test('throws for spaces in middle', function () {
        expectException(InvalidArgumentException::class, fn() => new Email('user @example.com'));
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

    test('throws for whitespace currency', function () {
        expectException(InvalidArgumentException::class, fn() => new Money(100.0, '   '));
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

    test('multiplies by zero', function () {
        $result = (new Money(100.0))->multiply(0.0);
        assertSame(0.0, $result->getAmount());
    });

    test('throws when adding different currencies', function () {
        expectException(InvalidArgumentException::class,
            fn() => (new Money(100.0, 'USD'))->add(new Money(50.0, 'EUR')));
    });

    test('throws when subtracting different currencies', function () {
        expectException(InvalidArgumentException::class,
            fn() => (new Money(100.0, 'USD'))->subtract(new Money(30.0, 'EUR')));
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

    test('immutability on subtract', function () {
        $original = new Money(100.0);
        $original->subtract(new Money(30.0));
        assertSame(100.0, $original->getAmount());
    });

    test('immutability on multiply', function () {
        $original = new Money(100.0);
        $original->multiply(3.0);
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

    test('accepts long country name', function () {
        $a = new Address('1 High St', 'London', 'Greater London', 'EC1A 1BB', 'United Kingdom');
        assertSame('United Kingdom', $a->getCountry());
    });

    test('accepts empty optional fields', function () {
        $a = new Address('', '', '', '', 'US');
        assertSame('', $a->getStreet());
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

    test('equality via toArray comparison', function () {
        $a = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');
        $b = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');
        assertSame($a->toArray(), $b->toArray());
    });

    test('inequality via toArray', function () {
        $a = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');
        $b = new Address('2 Main St', 'NYC', 'NY', '10001', 'US');
        assertTrue($a->toArray() !== $b->toArray());
    });

    test('accepts special characters', function () {
        $a = new Address('Calle 123 #4-56', 'São Paulo', 'SP', '01001-000', 'BR');
        assertSame('São Paulo', $a->getCity());
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

    test('null defaults for optional fields', function () {
        $org = new Organization('Min', 'min', 1);
        assertNull($org->getDescription());
        assertNull($org->getLogo());
        assertNull($org->getWebsite());
        assertSame([], $org->getSettings());
    });

    test('accepts empty name', function () {
        $org = new Organization('', 'empty', 1);
        assertSame('', $org->getName());
    });

    test('accepts zero owner_id', function () {
        $org = new Organization('Zero', 'zero', 0);
        assertSame(0, $org->getOwnerId());
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

    test('stores constructor values', function () {
        $budget = new Money(5000.0, 'USD');
        $start = new DateTimeImmutable('+10 days');
        $c = new Campaign('Q3 Launch', 'conversions', $budget, ['gender' => 'all'], ['google'],
            $start, $start->modify('+20 days'), 5, 42, ['priority' => 'high']);
        assertSame('Q3 Launch', $c->getName());
        assertSame('conversions', $c->getObjective());
        assertSame(5, $c->getOrganizationId());
        assertSame(42, $c->getCreatedBy());
        assertSame(['priority' => 'high'], $c->getMetadata());
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
        assertNotNull($c->getPausedAt());
    });

    test('pauses scheduled campaign', function () {
        $c = createDraftCampaign(['startDate' => new DateTimeImmutable('+30 days')]);
        $c->launch(); // scheduled
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
        assertNotNull($c->getArchivedAt());
    });

    test('archives from active (pauses first)', function () {
        $c = createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
        $c->launch();
        $c->archive();
        assertSame('archived', $c->getStatus());
        assertNotNull($c->getPausedAt()); // archive pauses active first
    });

    test('throws when launching archived', function () {
        expectException(\RuntimeException::class, function () {
            $c = createDraftCampaign();
            $c->archive();
            $c->launch();
        });
    });

    test('throws when pausing draft', function () {
        expectException(\RuntimeException::class, function () {
            $c = createDraftCampaign();
            $c->pause();
        });
    });

    test('throws when archiving already archived', function () {
        expectException(\RuntimeException::class, function () {
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
        $budget = new Money(10000.0, 'USD');
        $c = new Campaign('Test', 'conversions', $budget, ['age' => ['25-40']], ['facebook'],
            new DateTimeImmutable('+10 days'), new DateTimeImmutable('+40 days'), 3, 1);
        $c->setId(10);
        $array = $c->toArray();
        assertSame(10, $array['id']);
        assertSame('Test', $array['name']);
        assertSame('draft', $array['status']);
        assertSame(3, $array['organization_id']);
        assertNull($array['launched_at']);
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
            ['strategy', 'planning'], ['temperature' => 0.7]);
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
    });

    test('AgentRole labels', function () {
        assertSame('CEO', AgentRole::ceo()->getLabel());
        assertSame('Marketing Director', AgentRole::marketingDirector()->getLabel());
    });

    test('AgentRole equality', function () {
        assertTrue(AgentRole::ceo()->equals(AgentRole::ceo()));
        assertFalse(AgentRole::ceo()->equals(AgentRole::specialist()));
    });

    test('AgentRole invalid throws', function () {
        expectException(InvalidArgumentException::class, fn() => AgentRole::fromString('invalid_role_xyz'));
        expectException(InvalidArgumentException::class, fn() => AgentRole::fromString(''));
    });

    test('AgentRole normalizes to lowercase', function () {
        assertSame('ceo', AgentRole::fromString('CEO')->getValue());
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

    test('AutonomyLevel equality and toString', function () {
        assertTrue((new AutonomyLevel('full_auto'))->equals(new AutonomyLevel('full_auto')));
        assertSame('semi_auto', (string) new AutonomyLevel('semi_auto'));
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

    test('agent has timestamps', function () {
        $a = new Agent('TS', 'specialist', 'desc', 'gpt-4o', 1);
        assertNotNull($a->getCreatedAt());
        assertNotNull($a->getUpdatedAt());
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
        assertCount(1, $wf->getDomainEvents());
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

    test('WorkflowNode creates with defaults', function () {
        $wfId = Uuid::uuid4();
        $node = WorkflowNode::create($wfId, 'delay', ['duration' => 3600], ['x' => 10, 'y' => 20], 'Wait');
        assertSame('delay', $node->getType());
        assertSame(['x' => 10, 'y' => 20], $node->getPosition());
        assertSame('Wait', $node->getLabel());
        assertSame(['duration' => 3600], $node->getConfig());
    });

    test('WorkflowEdge creates with condition label', function () {
        $wfId = Uuid::uuid4();
        $edge = WorkflowEdge::create($wfId, 'src-1', 'tgt-1', 'approved');
        assertSame('approved', $edge->getLabel());
        assertSame('src-1', $edge->getSourceNodeId());
        assertSame('tgt-1', $edge->getTargetNodeId());
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
        $wf->activate();
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

        $wf2 = Workflow::create(Uuid::uuid4(), 'Archive Active');
        $wf2->activate();
        $wf2->archive();
        assertSame('archived', $wf2->getStatus());
    });

    test('renames', function () {
        $wf = Workflow::create(Uuid::uuid4(), 'Old');
        $wf->rename('New');
        assertSame('New', $wf->getName());
    });

    test('rename throws for empty', function () {
        expectException(InvalidArgumentException::class, function () {
            $wf = Workflow::create(Uuid::uuid4(), 'Name');
            $wf->rename('');
        });
    });

    test('sets description to null', function () {
        $wf = Workflow::create(Uuid::uuid4(), 'Desc', 'Some desc');
        $wf->setDescription(null);
        assertNull($wf->getDescription());
    });

    test('increments version', function () {
        $wf = Workflow::create(Uuid::uuid4(), 'Versioned');
        assertSame(1, $wf->getVersion());
        $wf->incrementVersion();
        assertSame(2, $wf->getVersion());
        $wf->incrementVersion();
        assertSame(3, $wf->getVersion());
    });

    test('has timestamps', function () {
        $wf = Workflow::create(Uuid::uuid4(), 'TS');
        assertNotNull($wf->getCreatedAt());
        assertNotNull($wf->getUpdatedAt());
    });

    test('accepts null campaign ID', function () {
        $wf = Workflow::create(Uuid::uuid4(), 'No Campaign');
        assertNull($wf->getCampaignId());
    });

    test('reconstitutes from persistence', function () {
        $id = Uuid::uuid4();
        $orgId = Uuid::uuid4();
        $createdAt = new DateTimeImmutable('2024-01-01');
        $updatedAt = new DateTimeImmutable('2024-06-01');

        $wf = Workflow::reconstitute(
            $id, $orgId, null, 'Recon', 'persisted',
            [], [], 'active', 3, [],
            $createdAt, $updatedAt
        );
        assertSame($id->toString(), $wf->getIdString());
        assertSame($orgId->toString(), $wf->getOrganizationId()->toString());
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
        assertTrue($post->isScheduled());
        assertSame($scheduledAt, $post->getScheduledAt());
    });

    test('creates post with campaign id', function () {
        $campId = Uuid::uuid4();
        $post = SocialPost::create(Uuid::uuid4(), 'With Campaign', campaignId: $campId);
        assertSame($campId->toString(), $post->getCampaignId()->toString());
    });

    test('creates post with media attachments', function () {
        $media = ['https://example.com/img.jpg', 'https://example.com/img2.png'];
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

    test('publishes from scheduled', function () {
        $post = SocialPost::create(Uuid::uuid4(), 'Scheduled Pub',
            scheduledAt: new DateTimeImmutable('+1 day'));
        $post->publish('scheduled-post-id');
        assertSame('published', $post->getStatus());
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

    test('overwrites previous metrics', function () {
        $post = SocialPost::create(Uuid::uuid4(), 'Overwrite');
        $post->updateMetrics(['likes' => 10]);
        $post->updateMetrics(['likes' => 99]);
        assertSame(['likes' => 99], $post->getMetrics());
    });

    test('updates content', function () {
        $post = SocialPost::create(Uuid::uuid4(), 'Original');
        $post->setContent('Updated');
        assertSame('Updated', $post->getContent());
    });

    test('has timestamps', function () {
        $post = SocialPost::create(Uuid::uuid4(), 'TS');
        assertNotNull($post->getCreatedAt());
        assertNotNull($post->getUpdatedAt());
    });

    test('preserves created_at after publish', function () {
        $post = SocialPost::create(Uuid::uuid4(), 'Stable');
        $createdAt = $post->getCreatedAt();
        $post->publish('pid');
        assertSame($createdAt, $post->getCreatedAt());
    });

    test('reconstitutes from persistence', function () {
        $id = Uuid::uuid4();
        $accountId = Uuid::uuid4();
        $createdAt = new DateTimeImmutable('2024-01-01');
        $updatedAt = new DateTimeImmutable('2024-06-01');

        $post = SocialPost::reconstitute(
            $id, $accountId, null, 'Content', [], null, null,
            'draft', null, [], $createdAt, $updatedAt
        );
        assertSame($id->toString(), $post->getId()->toString());
        assertSame($accountId->toString(), $post->getAccountId()->toString());
        assertSame('draft', $post->getStatus());
        assertSame($createdAt, $post->getCreatedAt());
        assertSame($updatedAt, $post->getUpdatedAt());
    });

    // ============================================================
    // TRAITS TEST
    // ============================================================
    echo "\n=== HasTimestamps Trait ===\n";

    test('timestamps null before init', function () {
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

    test('multiple markAsUpdated calls', function () {
        $e = new class { use HasTimestamps; };
        $e->initializeTimestamps();
        usleep(2000);
        $e->markAsUpdated();
        $t1 = $e->getUpdatedAt();
        usleep(2000);
        $e->markAsUpdated();
        $t2 = $e->getUpdatedAt();
        assertTrue($t2 > $t1);
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

    test('explicit setCreatedAt preserved by initializeTimestamps', function () {
        $e = new class { use HasTimestamps; };
        $explicit = new DateTimeImmutable('2022-01-01');
        $e->setCreatedAt($explicit);
        $e->initializeTimestamps();
        assertSame($explicit, $e->getCreatedAt()); // ??= guard
    });

    echo "\n=== HasDomainEvents Trait ===\n";

    test('starts with no events', function () {
        $e = new class { use HasDomainEvents; };
        assertEmpty($e->getDomainEvents());
    });

    test('records and retrieves events', function () {
        $e = new class { use HasDomainEvents; };
        $event = new \App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'test');
        $e->recordDomainEvent($event);
        assertCount(1, $e->getDomainEvents());
        assertSame($event, $e->getDomainEvents()[0]);
    });

    test('records multiple events in order', function () {
        $e = new class { use HasDomainEvents; };
        $e1 = new \App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'first');
        $e2 = new \App\Domain\Workflow\Events\WorkflowActivated('2');
        $e->recordDomainEvent($e1);
        $e->recordDomainEvent($e2);
        assertCount(2, $e->getDomainEvents());
        assertSame($e1, $e->getDomainEvents()[0]);
        assertSame($e2, $e->getDomainEvents()[1]);
    });

    test('clears events', function () {
        $e = new class { use HasDomainEvents; };
        $e->recordDomainEvent(new \App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'test'));
        $e->clearDomainEvents();
        assertEmpty($e->getDomainEvents());
    });

    test('continues recording after clear', function () {
        $e = new class { use HasDomainEvents; };
        $e->recordDomainEvent(new \App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'first'));
        $e->clearDomainEvents();
        $e2 = new \App\Domain\Workflow\Events\WorkflowActivated('1');
        $e->recordDomainEvent($e2);
        assertCount(1, $e->getDomainEvents());
        assertSame($e2, $e->getDomainEvents()[0]);
    });

    test('independent per instance', function () {
        $a = new class { use HasDomainEvents; };
        $b = new class { use HasDomainEvents; };
        $a->recordDomainEvent(new \App\Domain\Workflow\Events\WorkflowCreated('1', '2', 'a'));
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
}
