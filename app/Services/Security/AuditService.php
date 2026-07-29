<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service AuditService
 *
 * Centralised audit logging service that records security-relevant events
 * to the database, filters sensitive fields, coallesces batch writes,
 * and enforces retention policies.
 *
 * Unlike the per-request AuditLogger middleware, this service is designed
 * for explicit invocation from domain services, commands, and job handlers
 * so that every security-sensitive operation is audited regardless of the
 * HTTP layer.
 */
final class AuditService
{
    /**
     * Severity levels.
     */
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Sensitive field names that will be redacted from audit details.
     *
     * @var list<string>
     */
    private array $sensitiveFields;

    /**
     * In-memory buffer for batch writes.
     *
     * @var list<array<string, mixed>>
     */
    private array $batchBuffer = [];

    /**
     * Maximum batch size before auto-flushing.
     */
    private const BATCH_SIZE = 100;

    public function __construct()
    {
        $this->sensitiveFields = config('security.audit.sensitive_fields', [
            'password',
            'token',
            'secret',
            'authorization',
        ]);
    }

    // ------------------------------------------------------------------ Public API

    /**
     * Log a security-relevant event.
     *
     * @param  array<string, mixed>  $details
     */
    public function log(
        string $eventType,
        string $severity = self::SEVERITY_INFO,
        ?string $userId = null,
        array $details = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLog {
        $sanitized = $this->redactSensitiveFields($details);

        $record = AuditLog::create([
            'user_id' => $userId,
            'action' => $eventType,
            'entity_type' => $sanitized['entity_type'] ?? 'system',
            'entity_id' => $sanitized['entity_id'] ?? null,
            'new_values' => $sanitized,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);

        Log::channel('audit')->log(
            $this->severityToPsr($severity),
            'Security event: ' . $eventType,
            [
                'event_type' => $eventType,
                'severity' => $severity,
                'user_id' => $userId,
                'audit_log_id' => $record->getKey(),
            ],
        );

        return $record;
    }

    /**
     * Convenience method to log an event from an HTTP request context.
     *
     * @param  array<string, mixed>  $details
     */
    public function logRequest(
        string $eventType,
        Request $request,
        string $severity = self::SEVERITY_INFO,
        array $details = [],
    ): AuditLog {
        return $this->log(
            $eventType,
            $severity,
            $request->user()?->getKey(),
            $details,
            $request->ip(),
            $request->userAgent(),
        );
    }

    /**
     * Log a model lifecycle event (created / updated / deleted / restored).
     *
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $changes
     */
    public function logModelEvent(
        string $eventType,
        Model $model,
        array $original = [],
        array $changes = [],
        ?string $userId = null,
    ): AuditLog {
        return $this->log(
            $eventType,
            $this->determineModelEventSeverity($eventType),
            $userId,
            [
                'entity_type' => $model::class,
                'entity_id' => $model->getKey(),
                'original' => $original,
                'changes' => $changes,
            ],
        );
    }

    /**
     * Log a failed authentication attempt.
     */
    public function logFailedAuth(
        string $identifier,
        string $method,
        ?string $ipAddress = null,
        array $details = [],
    ): AuditLog {
        return $this->log(
            'auth.failed',
            self::SEVERITY_WARNING,
            null,
            array_merge($details, [
                'entity_type' => 'auth',
                'identifier_type' => $method,
                'identifier' => $this->redactIdentifier($identifier),
            ]),
            $ipAddress,
        );
    }

    /**
     * Log an authorization failure (403).
     */
    public function logUnauthorizedAccess(
        ?string $userId,
        string $resource,
        ?string $permission = null,
        ?string $ipAddress = null,
    ): AuditLog {
        return $this->log(
            'auth.unauthorized',
            self::SEVERITY_WARNING,
            $userId,
            [
                'entity_type' => 'authorization',
                'resource' => $resource,
                'permission' => $permission,
            ],
            $ipAddress,
        );
    }

    /**
     * Add an event to the batch buffer.  Call flush() to persist.
     *
     * @param  array<string, mixed>  $data
     */
    public function batch(array $data): void
    {
        $this->batchBuffer[] = $this->redactSensitiveFields($data);

        if (count($this->batchBuffer) >= self::BATCH_SIZE) {
            $this->flush();
        }
    }

    /**
     * Persist all buffered audit events in a single transaction.
     *
     * @return int Number of records written.
     */
    public function flush(): int
    {
        if ($this->batchBuffer === []) {
            return 0;
        }

        $buffer = $this->batchBuffer;
        $this->batchBuffer = [];
        $count = 0;

        try {
            DB::transaction(function () use ($buffer, &$count): void {
                foreach ($buffer as $row) {
                    AuditLog::create($row);
                    $count++;
                }
            });
        } catch (\Throwable $e) {
            Log::error('Batch audit flush failed: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * Purge audit records older than the configured retention period.
     *
     * @return int Number of deleted records.
     */
    public function enforceRetentionPolicy(): int
    {
        $days = (int) config('security.audit.retention_days', 90);
        $cutoff = Carbon::now()->subDays($days);

        $deleted = AuditLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        if ($deleted > 0) {
            Log::channel('audit')->info('Audit retention policy enforced', [
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoff->toIso8601String(),
                'retention_days' => $days,
            ]);
        }

        return $deleted;
    }

    // ------------------------------------------------------------------ Internal helpers

    /**
     * Redact sensitive fields from an array of details.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private function redactSensitiveFields(array $details): array
    {
        $redacted = [];

        foreach ($details as $key => $value) {
            if ($this->isSensitive($key)) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactSensitiveFields($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Check whether a key name matches a sensitive field pattern.
     */
    private function isSensitive(string $key): bool
    {
        $lower = strtolower($key);

        foreach ($this->sensitiveFields as $pattern) {
            if (str_contains($lower, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redact most of an identifier for privacy while leaving enough
     * for debugging (e.g. "j***@example.com").
     */
    private function redactIdentifier(string $identifier): string
    {
        $length = mb_strlen($identifier);

        if ($length <= 3) {
            return str_repeat('*', $length);
        }

        $prefix = mb_substr($identifier, 0, 1);
        $suffix = mb_substr($identifier, -1);

        return $prefix . str_repeat('*', $length - 2) . $suffix;
    }

    /**
     * Map application severity to PSR-3 log level.
     */
    private function severityToPsr(string $severity): string
    {
        return match ($severity) {
            self::SEVERITY_CRITICAL => 'critical',
            self::SEVERITY_WARNING => 'warning',
            default => 'info',
        };
    }

    /**
     * Determine the base severity for a model lifecycle event.
     */
    private function determineModelEventSeverity(string $eventType): string
    {
        return match ($eventType) {
            'model.deleted', 'model.restored' => self::SEVERITY_WARNING,
            default => self::SEVERITY_INFO,
        };
    }
}
