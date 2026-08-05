<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProvider as SdkTracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\Exporter\OTLP\SpanExporter;
use OpenTelemetry\Exporter\OTLP\MetricsExporter;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\PushController;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/opentelemetry.php',
            'opentelemetry'
        );

        // Register tracer provider
        $this->app->singleton(TracerProvider::class, function () {
            if (!config('opentelemetry.enabled', false)) {
                return new class implements TracerProvider {
                    public function getTracer(string $name, string $version = null, string $schemaUrl = null): \OpenTelemetry\API\Trace\TracerInterface {
                        return new \OpenTelemetry\API\Trace\NoopTracer();
                    }
                };
            }

            $exporter = new SpanExporter(
                transport: (new OtlpHttpTransportFactory())->create(
                    config('opentelemetry.otlp.endpoint', 'http://localhost:4318/v1/traces'),
                    config('opentelemetry.otlp.headers', [])
                )
            );

            $processor = new BatchSpanProcessor($exporter);

            $resource = ResourceInfoFactory::createResource(
                [
                    'service.name' => config('opentelemetry.service_name', 'astra-os'),
                    'service.version' => config('app.version', '1.0.0'),
                    'deployment.environment' => config('app.env', 'local'),
                ]
            );

            return new SdkTracerProvider(
                resource: $resource,
                spanProcessor: $processor
            );
        });

        // Register meter provider
        $this->app->singleton(MeterProvider::class, function () {
            if (!config('opentelemetry.enabled', false)) {
                return new class implements \OpenTelemetry\API\Metrics\MeterProviderInterface {
                    public function getMeter(string $name, string $version = null, string $schemaUrl = null): \OpenTelemetry\API\Metrics\MeterInterface {
                        return new \OpenTelemetry\API\Metrics\NoopMeter();
                    }
                };
            }

            $exporter = new MetricsExporter(
                transport: (new OtlpHttpTransportFactory())->create(
                    config('opentelemetry.otlp.endpoint', 'http://localhost:4318/v1/metrics'),
                    config('opentelemetry.otlp.headers', [])
                )
            );

            $pushController = new PushController($exporter, 60000); // Push every 60 seconds

            $resource = ResourceInfoFactory::createResource(
                [
                    'service.name' => config('opentelemetry.service_name', 'astra-os'),
                    'service.version' => config('app.version', '1.0.0'),
                    'deployment.environment' => config('app.env', 'local'),
                ]
            );

            $meterProvider = new MeterProvider(
                resource: $resource
            );

            // Register push controller to periodically export metrics
            $pushController->register($meterProvider);

            return $meterProvider;
        });

        // Set global tracer provider
        Globals::setTracerProvider($this->app->make(TracerProvider::class));
        Globals::setMeterProvider($this->app->make(MeterProvider::class));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!config('opentelemetry.enabled', false)) {
            return;
        }

        // Register middleware for automatic HTTP tracing
        $this->app['router']->pushMiddlewareToGroup('api', \App\Http\Middleware\OpenTelemetryMiddleware::class);

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/opentelemetry.php' => config_path('opentelemetry.php'),
        ], 'opentelemetry-config');
    }
}