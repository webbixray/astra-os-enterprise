<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Export\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class ImportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ImportService $importService,
    ) {}

    /**
     * Import data from JSON payload.
     *
     * POST /api/v1/import/{type}
     *
     * Body: {
     *   "data": [...rows...],
     *   "dry_run": true|false,
     *   "organization_id": "optional-org-id"
     * }
     */
    public function import(Request $request, string $type): JsonResponse
    {
        $validTypes = $this->importService->getValidTypes();

        if (! isset($validTypes[$type])) {
            return $this->error(
                "Invalid import type. Valid types: " . implode(', ', array_keys($validTypes)),
                422,
            );
        }

        $validator = Validator::make($request->all(), [
            'data' => ['required', 'array', 'min:1'],
            'data.*' => ['required', 'array'],
            'dry_run' => ['sometimes', 'boolean'],
            'organization_id' => ['sometimes', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $dryRun = $validated['dry_run'] ?? false;
        $orgId = $validated['organization_id'] ?? $request->user()->organization_id;

        $result = $this->importService->import(
            type: $type,
            rows: $validated['data'],
            orgId: $orgId,
            dryRun: $dryRun,
        );

        $statusCode = $result->hasErrors() ? 422 : 200;

        return response()->json([
            'success' => ! $result->hasErrors(),
            'message' => $dryRun
                ? 'Dry-run validation completed.'
                : ($result->hasErrors() ? 'Import completed with errors.' : 'Import completed successfully.'),
            'data' => $result->toArray(),
            'errors' => $result->hasErrors() ? $result->toArray()['errors'] : null,
        ], $statusCode);
    }

    /**
     * Validate import data without persisting (convenience alias).
     *
     * POST /api/v1/import/{type}/validate
     */
    public function validate(Request $request, string $type): JsonResponse
    {
        $request->merge(['dry_run' => true]);

        return $this->import($request, $type);
    }

    /**
     * Get expected columns for an import type.
     *
     * GET /api/v1/import/{type}/columns
     */
    public function columns(string $type): JsonResponse
    {
        try {
            $columns = $this->importService->getExpectedColumns($type);

            return $this->success([
                'type' => $type,
                'columns' => $columns,
                'required' => $columns,
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * List available import types.
     *
     * GET /api/v1/import/types
     */
    public function types(): JsonResponse
    {
        $types = array_keys($this->importService->getValidTypes());

        return $this->success($types);
    }
}
