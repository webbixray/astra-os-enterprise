<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SemConv\TraceAttributes;
use OpenTelemetry\Context\Context;

class OpenTelemetryMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('opentelemetry.enabled', false)) {
            return $next($request);
        }

        $tracer = Globals::tracerProvider()->getTracer(
            config('opentelemetry.service_name', 'astra-os'),
            config('app.version', '1.0.0')
        );

        // Start a new span for the HTTP request
        $span = $tracer->spanBuilder($request->method() . ' ' . $request->path())
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();

        $scope = $span->activate();

        try {
            // Add request attributes
            $span->setAttribute(TraceAttributes::HTTP_METHOD, $request->method());
            $span->setAttribute(TraceAttributes::HTTP_URL, $request->fullUrl());
            $span->setAttribute(TraceAttributes::HTTP_SCHEME, $request->getScheme());
            $span->setAttribute(TraceAttributes::HTTP_TARGET, $request->getRequestUri());
            $span->setAttribute(TraceAttributes::HTTP_HOST, $request->getHost());
            $span->setAttribute(TraceAttributes::HTTP_FLAVOR, $request->getProtocolVersion());
            $span->setAttribute(TraceAttributes::NET_HOST_NAME, $request->getHost());
            $span->setAttribute(TraceAttributes::NET_HOST_PORT, $request->getPort());

            // Add user context if authenticated
            if ($request->user()) {
                $span->setAttribute('user.id', $request->user()->id);
                $span->setAttribute('user.email', $request->user()->email ?? '');
            }

            // Add organization context if available
            if ($request->route('organization')) {
                $span->setAttribute('organization.id', $request->route('organization')->id ?? '');
            }

            $response = $next($request);

            // Add response attributes
            $span->setAttribute(TraceAttributes::HTTP_STATUS_CODE, $response->getStatusCode());
            $span->setAttribute(TraceAttributes::HTTP_RESPONSE_CONTENT_LENGTH, $response->getContentLength() ?? 0);

            // Mark span as error for 5xx responses
            if ($response->getStatusCode() >= 500) {
                $span->setStatus(StatusCode::STATUS_ERROR);
                $span->setAttribute(TraceAttributes::ERROR_TYPE, 'http_' . $response->getStatusCode());
            }

            return $response;
        } catch (\Throwable $e) {
            // Record exception on span
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->end();
            $scope->detach();
        }
    }
}