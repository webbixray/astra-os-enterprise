<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Infrastructure\Persistence\Models\Agent;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\Workflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

final class ImportService
{
    /**
     * Available import types and their associated model classes.
     */
    private const IMPORT_TYPES = [
        'campaigns' => Campaign::class,
        'agents' => Agent::class,
        'workflows' => Workflow::class,
    ];

    /**
     * Validation rules for each import type.
     *
     * @var array<string, array>
     */
    private const IMPORT_RULES = [
        'campaigns' => [
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['required', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:draft,active,paused,archived'],
            'budget_amount' => ['sometimes', 'numeric', 'min:0'],
            'budget_currency' => ['sometimes', 'string', 'max:3'],
            'platforms' => ['sometimes'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'target_audience' => ['sometimes'],
            'metadata' => ['sometimes'],
        ],
        'agents' => [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['sometimes', 'string', 'max:100'],
            'model_config' => ['sometimes'],
            'autonomy_level' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'string', 'max:50'],
            'is_active' => ['sometimes'],
        ],
        'workflows' => [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:50'],
            'is_active' => ['sometimes'],
            'trigger_type' => ['sometimes', 'string', 'max:50'],
            'trigger_config' => ['sometimes'],
        ],
    ];

    /**
     * Column mapping for transforming CSV/JSON column names to model attributes.
     *
     * @return array<string, callable>
     */
    private function getColumnTransformers(): array
    {
        return [
            'is_active' => fn ($value) => in_array(strtolower((string) $value), ['yes', 'true', '1', 'active'], true),
            'platforms' => fn ($value) => is_string($value) ? json_decode($value, true) ?? [$value] : (array) $value,
            'target_audience' => fn ($value) => is_string($value) ? json_decode($value, true) ?? [] : (array) $value,
            'metadata' => fn ($value) => is_string($value) ? json_decode($value, true) ?? [] : (array) $value,
            'model_config' => fn ($value) => is_string($value) ? json_decode($value, true) ?? [] : (array) $value,
            'trigger_config' => fn ($value) => is_string($value) ? json_decode($value, true) ?? [] : (array) $value,
        ];
    }

    /**
     * Chunk size for batch insertion.
     */
    private const CHUNK_SIZE = 100;

    /**
     * Import data from an array of rows.
     *
     * @param string      $type      Import type (campaigns, agents, workflows).
     * @param array       $rows      Array of associative arrays representing rows.
     * @param string|null $orgId     Optional organization ID to associate with imported records.
     * @param bool        $dryRun    When true, validate only — do not persist.
     *
     * @return ImportResult
     */
    public function import(string $type, array $rows, ?string $orgId = null, bool $dryRun = false): ImportResult
    {
        if (! isset(self::IMPORT_TYPES[$type])) {
            throw new \InvalidArgumentException("Unknown import type: {$type}");
        }

        $result = new ImportResult();
        $rules = self::IMPORT_RULES[$type];
        $modelClass = self::IMPORT_TYPES[$type];
        $chunks = array_chunk($rows, self::CHUNK_SIZE);

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();

            try {
                foreach ($chunk as $rowIndex => $row) {
                    $globalIndex = ($chunkIndex * self::CHUNK_SIZE) + $rowIndex;
                    $row = $this->transformRow($row);

                    $validator = Validator::make($row, $rules);

                    if ($validator->fails()) {
                        $result->addError($globalIndex, $validator->errors()->toArray());
                        continue;
                    }

                    $row = array_merge($row, $this->getDefaults($type));

                    if ($orgId) {
                        $row['organization_id'] = $orgId;
                    }

                    if (! $dryRun) {
                        /** @var Model $model */
                        $model = new $modelClass();
                        $fillable = $model->getFillable();

                        // Only fill attributes that are fillable
                        $dataToFill = array_intersect_key($row, array_flip($fillable));
                        $model->fill($dataToFill);
                        $model->save();

                        $result->addImported($globalIndex, (string) $model->id);
                    } else {
                        $result->addValid($globalIndex);
                    }
                }

                if (! $dryRun) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Import chunk failed', [
                    'type' => $type,
                    'chunk' => $chunkIndex,
                    'error' => $e->getMessage(),
                ]);
                $result->addChunkError($chunkIndex, $e->getMessage());

                // On failure, try remaining rows individually
                $this->importIndividualRemaining($type, $chunk, $chunkIndex, $orgId, $dryRun, $result);
            }
        }

        return $result;
    }

    /**
     * Validate import rows without persisting (dry-run).
     *
     * @return ImportResult
     */
    public function validate(string $type, array $rows, ?string $orgId = null): ImportResult
    {
        return $this->import($type, $rows, $orgId, dryRun: true);
    }

    /**
     * Get the expected CSV columns for a type.
     *
     * @return array<string>
     */
    public function getExpectedColumns(string $type): array
    {
        if (! isset(self::IMPORT_RULES[$type])) {
            throw new \InvalidArgumentException("Unknown import type: {$type}");
        }

        return array_keys(self::IMPORT_RULES[$type]);
    }

    /**
     * Get valid import types.
     *
     * @return array<string, string>
     */
    public function getValidTypes(): array
    {
        return self::IMPORT_TYPES;
    }

    /**
     * Transform row values using column transformers.
     */
    private function transformRow(array $row): array
    {
        foreach ($this->getColumnTransformers() as $column => $transformer) {
            if (array_key_exists($column, $row)) {
                $row[$column] = $transformer($row[$column]);
            }
        }

        return $row;
    }

    /**
     * Get default values for an import type.
     */
    private function getDefaults(string $type): array
    {
        return match ($type) {
            'campaigns' => [
                'status' => 'draft',
                'budget_currency' => 'USD',
                'platforms' => [],
                'target_audience' => [],
                'metadata' => [],
            ],
            'agents' => [
                'status' => 'idle',
                'is_active' => true,
                'autonomy_level' => 'supervised',
                'model_config' => ['provider' => 'openai', 'model' => 'gpt-4o'],
            ],
            'workflows' => [
                'status' => 'draft',
                'is_active' => false,
            ],
            default => [],
        };
    }

    /**
     * Fallback: import remaining rows individually after a chunk failure.
     */
    private function importIndividualRemaining(
        string $type,
        array $chunk,
        int $chunkIndex,
        ?string $orgId,
        bool $dryRun,
        ImportResult $result,
    ): void {
        $modelClass = self::IMPORT_TYPES[$type];
        $rules = self::IMPORT_RULES[$type];

        foreach ($chunk as $rowIndex => $row) {
            $globalIndex = ($chunkIndex * self::CHUNK_SIZE) + $rowIndex;

            // Skip rows already processed
            if ($result->hasProcessed($globalIndex)) {
                continue;
            }

            try {
                $row = $this->transformRow($row);
                $validator = Validator::make($row, $rules);

                if ($validator->fails()) {
                    $result->addError($globalIndex, $validator->errors()->toArray());
                    continue;
                }

                $row = array_merge($row, $this->getDefaults($type));

                if ($orgId) {
                    $row['organization_id'] = $orgId;
                }

                if (! $dryRun) {
                    /** @var Model $model */
                    $model = new $modelClass();
                    $fillable = $model->getFillable();
                    $dataToFill = array_intersect_key($row, array_flip($fillable));
                    $model->fill($dataToFill);
                    $model->save();

                    $result->addImported($globalIndex, (string) $model->id);
                } else {
                    $result->addValid($globalIndex);
                }
            } catch (\Throwable $e) {
                $result->addError($globalIndex, ['exception' => $e->getMessage()]);
            }
        }
    }
}

/**
 * Import result value object.
 */
final class ImportResult
{
    /**
     * @var array<int, array{row: int, id?: string}>
     */
    private array $imported = [];

    /**
     * @var array<int, array{row: int, errors: array}>
     */
    private array $errors = [];

    /**
     * @var array<int, int>
     */
    private array $valid = [];

    /**
     * @var array<int, string>
     */
    private array $chunkErrors = [];

    /**
     * @var array<int, true>
     */
    private array $processed = [];

    public function addImported(int $rowIndex, string $id): void
    {
        $this->imported[] = ['row' => $rowIndex, 'id' => $id];
        $this->processed[$rowIndex] = true;
    }

    public function addError(int $rowIndex, array $errors): void
    {
        $this->errors[] = ['row' => $rowIndex, 'errors' => $errors];
        $this->processed[$rowIndex] = true;
    }

    public function addValid(int $rowIndex): void
    {
        $this->valid[] = $rowIndex;
        $this->processed[$rowIndex] = true;
    }

    public function addChunkError(int $chunkIndex, string $message): void
    {
        $this->chunkErrors[] = ['chunk' => $chunkIndex, 'error' => $message];
    }

    public function hasProcessed(int $rowIndex): bool
    {
        return isset($this->processed[$rowIndex]);
    }

    public function getImportedCount(): int
    {
        return count($this->imported);
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function getValidCount(): int
    {
        return count($this->valid);
    }

    public function totalRows(): int
    {
        return $this->getImportedCount() + $this->getErrorCount() + $this->getValidCount();
    }

    public function hasErrors(): bool
    {
        return $this->getErrorCount() > 0 || ! empty($this->chunkErrors);
    }

    public function toArray(): array
    {
        return [
            'success' => ! $this->hasErrors(),
            'summary' => [
                'total_rows' => $this->totalRows(),
                'imported' => $this->getImportedCount(),
                'validated' => $this->getValidCount(),
                'errors' => $this->getErrorCount(),
                'chunk_errors' => count($this->chunkErrors),
            ],
            'imported' => $this->imported,
            'errors' => $this->errors,
            'chunk_errors' => $this->chunkErrors,
        ];
    }
}
