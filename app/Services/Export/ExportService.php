<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Infrastructure\Persistence\Models\Agent;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\Workflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportService
{
    /**
     * Available export types and their associated model classes.
     */
    private const EXPORT_TYPES = [
        'campaigns' => Campaign::class,
        'agents' => Agent::class,
        'workflows' => Workflow::class,
    ];

    /**
     * Column mapping for each export type.
     *
     * @var array<string, array>
     */
    private const EXPORT_COLUMNS = [
        'campaigns' => [
            'id', 'name', 'objective', 'status', 'budget_amount', 'budget_currency',
            'platforms', 'start_date', 'end_date', 'target_audience', 'metadata',
            'created_at', 'updated_at',
        ],
        'agents' => [
            'id', 'name', 'role', 'model_config', 'autonomy_level',
            'status', 'is_active', 'created_at', 'updated_at',
        ],
        'workflows' => [
            'id', 'name', 'description', 'status', 'is_active',
            'trigger_type', 'trigger_config', 'created_at', 'updated_at',
        ],
    ];

    /**
     * Export data as CSV.
     *
     * @param string      $type   Export type (campaigns, agents, workflows).
     * @param array       $filters Associative array of query filters.
     * @param string|null $orgId  Optional organization ID to scope the query.
     */
    public function toCsv(string $type, array $filters = [], ?string $orgId = null): StreamedResponse
    {
        $columns = $this->getColumns($type);
        $data = $this->queryData($type, $filters, $orgId);

        $response = new StreamedResponse(function () use ($data, $columns) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, $columns);

            // Data rows
            foreach ($data as $row) {
                $rowData = [];
                foreach ($columns as $column) {
                    $rowData[] = $this->formatValue($row->{$column} ?? null);
                }
                fputcsv($handle, $rowData);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$type}_export_" . date('Y-m-d_His') . '.csv"',
        ]);

        return $response;
    }

    /**
     * Export data as JSON.
     *
     * @return array{data: Collection, meta: array}
     */
    public function toJson(string $type, array $filters = [], ?string $orgId = null): array
    {
        $data = $this->queryData($type, $filters, $orgId);

        return [
            'data' => $data->values(),
            'meta' => [
                'type' => $type,
                'total' => $data->count(),
                'exported_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
        ];
    }

    /**
     * Download a CSV template for the given export type.
     */
    public function template(string $type): StreamedResponse
    {
        $columns = $this->getColumns($type);

        $response = new StreamedResponse(function () use ($columns, $type) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            // Add one example row
            $exampleRow = array_map(function ($col) {
                return $this->exampleValue($col);
            }, $columns);
            fputcsv($handle, $exampleRow);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$type}_import_template.csv\"",
        ]);

        return $response;
    }

    /**
     * Get valid export types.
     *
     * @return array<string, string>
     */
    public function getValidTypes(): array
    {
        return self::EXPORT_TYPES;
    }

    /**
     * Get export columns for a type.
     */
    public function getColumns(string $type): array
    {
        if (! isset(self::EXPORT_COLUMNS[$type])) {
            throw new \InvalidArgumentException("Unknown export type: {$type}");
        }

        return self::EXPORT_COLUMNS[$type];
    }

    /**
     * Query data with optional filters and organization scope.
     */
    private function queryData(string $type, array $filters, ?string $orgId): Collection
    {
        $modelClass = self::EXPORT_TYPES[$type] ?? null;

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown export type: {$type}");
        }

        /** @var Builder $query */
        $query = $modelClass::query();

        // Scope to organization
        if ($orgId) {
            $query->where('organization_id', $orgId);
        }

        // Apply date range filter
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Apply status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('id', 'like', "%{$filters['search']}%");
            });
        }

        Log::info('Export query executed', [
            'type' => $type,
            'filters' => $filters,
            'org_id' => $orgId,
        ]);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Format a value for CSV output.
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    /**
     * Get an example value for a column for template generation.
     */
    private function exampleValue(string $column): string
    {
        $examples = [
            'id' => 'uuid-example-1234',
            'name' => 'Example Name',
            'objective' => 'brand_awareness',
            'status' => 'draft',
            'budget_amount' => '5000.00',
            'budget_currency' => 'USD',
            'description' => 'Example description text',
            'role' => 'marketing_analyst',
            'autonomy_level' => 'semi_autonomous',
            'is_active' => 'Yes',
            'model_config' => '{"provider":"openai","model":"gpt-4o"}',
            'platforms' => '["facebook","instagram"]',
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'target_audience' => '{"age_range":"25-45","location":"US"}',
            'trigger_type' => 'schedule',
            'trigger_config' => '{"cron":"0 9 * * 1"}',
            'metadata' => '{}',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $examples[$column] ?? '';
    }
}
