<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Export\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ExportService $exportService,
    ) {}

    /**
     * Export data as CSV or JSON.
     *
     * GET /api/v1/export/{type}?format=csv&status=active&date_from=...
     */
    public function export(Request $request, string $type): StreamedResponse|JsonResponse
    {
        $validTypes = $this->exportService->getValidTypes();

        if (! isset($validTypes[$type])) {
            return $this->error(
                "Invalid export type. Valid types: " . implode(', ', array_keys($validTypes)),
                422,
            );
        }

        $format = $request->query('format', 'csv');

        if (! in_array($format, ['csv', 'json'], true)) {
            return $this->error('Format must be "csv" or "json".', 422);
        }

        $filters = $request->only(['status', 'date_from', 'date_to', 'search']);
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        // Determine organization scope from auth context or explicit parameter
        $orgId = $request->query('organization_id') ?? $request->user()->organization_id;

        if ($format === 'json') {
            $result = $this->exportService->toJson($type, $filters, $orgId);

            return $this->success(
                data: $result['data'],
                extra: ['meta' => $result['meta']],
            );
        }

        return $this->exportService->toCsv($type, $filters, $orgId);
    }

    /**
     * Download an import template CSV.
     *
     * GET /api/v1/export/{type}/template
     */
    public function template(string $type): StreamedResponse|JsonResponse
    {
        $validTypes = $this->exportService->getValidTypes();

        if (! isset($validTypes[$type])) {
            return $this->error(
                "Invalid export type. Valid types: " . implode(', ', array_keys($validTypes)),
                422,
            );
        }

        return $this->exportService->template($type);
    }

    /**
     * List available export types.
     *
     * GET /api/v1/export/types
     */
    public function types(): JsonResponse
    {
        $types = [];
        foreach ($this->exportService->getValidTypes() as $key => $class) {
            $types[] = [
                'type' => $key,
                'model' => class_basename($class),
                'columns' => $this->exportService->getColumns($key),
            ];
        }

        return $this->success($types);
    }
}
