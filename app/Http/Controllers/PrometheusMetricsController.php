<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class PrometheusMetricsController extends Controller
{
    /**
     * Exposition endpoint for Prometheus metrics.
     */
    public function index(Request $request): Response
    {
        if (!config('prometheus.enabled', true)) {
            return response('Prometheus metrics disabled', 404);
        }

        $registry = app(CollectorRegistry::class);
        $renderer = new RenderTextFormat();

        // Get metrics as text
        $metrics = $renderer->render($registry->getMetricFamilySamples());

        return response($metrics, 200, [
            'Content-Type' => RenderTextFormat::MIME_TYPE,
        ]);
    }
}