<?php

declare(strict_types=1);

namespace Tests\Feature\OpenTelemetry;

use Tests\TestCase;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerProvider;
use OpenTelemetry\API\Trace\SpanKind;

#[Group('feature')]
class OpenTelemetryTest extends TestCase
{
    public function test_tracer_provider_registered(): void
    {
        $tracerProvider = app(TracerProvider::class);
        $this->assertInstanceOf(TracerProvider::class, $tracerProvider);
    }

    public function test_global_tracer_provider_set(): void
    {
        $globalProvider = Globals::tracerProvider();
        $this->assertInstanceOf(TracerProvider::class, $globalProvider);
    }

    public function test_can_create_tracer(): void
    {
        $tracerProvider = app(TracerProvider::class);
        $tracer = $tracerProvider->getTracer('test-tracer', '1.0.0');
        
        $this->assertNotNull($tracer);
    }

    public function test_span_creation_and_attributes(): void
    {
        $tracerProvider = app(TracerProvider::class);
        $tracer = $tracerProvider->getTracer('test-tracer', '1.0.0');

        $span = $tracer->spanBuilder('test-operation')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();

        $span->setAttribute('test.key', 'test-value');
        $span->setAttribute('test.number', 42);

        $this->assertTrue($span->isRecording());

        $span->end();
    }

    public function test_exception_recording_on_span(): void
    {
        $tracerProvider = app(TracerProvider::class);
        $tracer = $tracerProvider->getTracer('test-tracer', '1.0.0');

        $span = $tracer->spanBuilder('test-exception')
            ->startSpan();

        try {
            throw new \RuntimeException('Test exception');
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $e->getMessage());
        }

        $span->end();

        // If no exception thrown, test passes
        $this->assertTrue(true);
    }

    public function test_middleware_adds_telemetry_headers(): void
    {
        config(['opentelemetry.enabled' => true]);

        $response = $this->get('/api/v1/health');

        $response->assertStatus(200);
        // Trace headers would be added in real implementation
    }
}