<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: Report
 *
 * Represents a scheduled or on-demand analytics report configuration.
 * Reports define what data to include, how it should be formatted, and
 * who should receive it. Supports export in PDF, CSV, and XLSX formats.
 *
 * @package App\Domain\Analytics\Entities
 */
class Report
{
    use HasTimestamps;

    /** @var string Scheduled report. */
    public const string TYPE_SCHEDULED = 'scheduled';

    /** @var string On-demand / one-time report. */
    public const string TYPE_ON_DEMAND = 'on_demand';

    /** @var array<int, string> Valid report types. */
    public const array VALID_TYPES = [
        self::TYPE_SCHEDULED,
        self::TYPE_ON_DEMAND,
    ];

    /** @var string PDF format. */
    public const string FORMAT_PDF = 'pdf';

    /** @var string CSV format. */
    public const string FORMAT_CSV = 'csv';

    /** @var string Excel format. */
    public const string FORMAT_XLSX = 'xlsx';

    /** @var array<int, string> Valid formats. */
    public const array VALID_FORMATS = [
        self::FORMAT_PDF,
        self::FORMAT_CSV,
        self::FORMAT_XLSX,
    ];

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $organizationId;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private readonly string $type;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var string|null
     */
    private ?string $schedule;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $lastRunAt;

    /**
     * @var string
     */
    private string $format;

    /**
     * @var array<int, string>
     */
    private array $recipients;

    /**
     * @param UuidInterface          $id
     * @param UuidInterface          $organizationId
     * @param string                 $name
     * @param string                 $type
     * @param array<string, mixed>  $config
     * @param string|null            $schedule
     * @param DateTimeImmutable|null $lastRunAt
     * @param string                 $format
     * @param array<int, string>    $recipients
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $organizationId,
        string $name,
        string $type,
        array $config,
        ?string $schedule,
        ?DateTimeImmutable $lastRunAt,
        string $format,
        array $recipients
    ) {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->name = $name;
        $this->type = $type;
        $this->config = $config;
        $this->schedule = $schedule;
        $this->lastRunAt = $lastRunAt;
        $this->format = $format;
        $this->recipients = $recipients;
    }

    /**
     * Create a new Report.
     *
     * @param UuidInterface          $organizationId
     * @param string                 $name
     * @param string                 $type
     * @param array<string, mixed>  $config
     * @param string|null            $schedule
     * @param string                 $format
     * @param array<int, string>    $recipients
     * @return self
     */
    public static function create(
        UuidInterface $organizationId,
        string $name,
        string $type = self::TYPE_ON_DEMAND,
        array $config = [],
        ?string $schedule = null,
        string $format = self::FORMAT_PDF,
        array $recipients = []
    ): self {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Report name cannot be empty.');
        }

        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid report type: "%s".', $type)
            );
        }

        if (!in_array($format, self::VALID_FORMATS, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid report format: "%s".', $format)
            );
        }

        $report = new self(
            Uuid::uuid4(),
            $organizationId,
            $name,
            $type,
            $config,
            $schedule,
            null,
            $format,
            $recipients
        );

        $report->initializeTimestamps();

        return $report;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface          $id
     * @param UuidInterface          $organizationId
     * @param string                 $name
     * @param string                 $type
     * @param array<string, mixed>  $config
     * @param string|null            $schedule
     * @param DateTimeImmutable|null $lastRunAt
     * @param string                 $format
     * @param array<int, string>    $recipients
     * @param DateTimeImmutable     $createdAt
     * @param DateTimeImmutable     $updatedAt
     * @return self
     */
    public static function reconstitute(...$args): self
    {
        $report = new self(...array_slice($args, 0, 9));
        $report->setCreatedAt($args[9]);
        $report->setUpdatedAt($args[10]);

        return $report;
    }

    /**
     * Record that the report has been run.
     *
     * @return void
     */
    public function markAsRun(): void
    {
        $this->lastRunAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    /**
     * Update the report configuration.
     *
     * @param array<string, mixed> $config
     * @return void
     */
    public function updateConfig(array $config): void
    {
        $this->config = $config;
        $this->markAsUpdated();
    }

    /**
     * Set the report schedule.
     *
     * @param string|null $schedule Cron expression or null to remove scheduling.
     * @return void
     */
    public function setSchedule(?string $schedule): void
    {
        $this->schedule = $schedule;
        $this->markAsUpdated();
    }

    /**
     * Set the output format.
     *
     * @param string $format
     * @return void
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
        $this->markAsUpdated();
    }

    /**
     * Set the email recipients.
     *
     * @param array<int, string> $recipients
     * @return void
     */
    public function setRecipients(array $recipients): void
    {
        $this->recipients = $recipients;
        $this->markAsUpdated();
    }

    /**
     * Add a recipient.
     *
     * @param string $recipient
     * @return void
     */
    public function addRecipient(string $recipient): void
    {
        if (!in_array($recipient, $this->recipients, true)) {
            $this->recipients[] = $recipient;
            $this->markAsUpdated();
        }
    }

    // ---- Getters ----

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return string|null
     */
    public function getSchedule(): ?string
    {
        return $this->schedule;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getLastRunAt(): ?DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @return array<int, string>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->type === self::TYPE_SCHEDULED;
    }

    /**
     * @return bool
     */
    public function isOnDemand(): bool
    {
        return $this->type === self::TYPE_ON_DEMAND;
    }
}
