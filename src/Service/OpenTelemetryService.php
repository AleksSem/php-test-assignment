<?php

namespace App\Service;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use Psr\Log\LoggerInterface;

class OpenTelemetryService
{
    private TracerProvider $tracerProvider;
    private TracerInterface $tracer;
    private MeterProviderInterface $meterProvider;
    private MeterInterface $meter;

    public function __construct(
        private readonly string $serviceName,
        private readonly string $serviceVersion,
        private readonly string $tracesEndpoint,
        private readonly string $metricsEndpoint,
        private readonly string $environment,
        private readonly LoggerInterface $logger,
    ) {
        $this->initializeTracing();
        $this->initializeMetrics();
    }

    private function initializeTracing(): void
    {
        try {
            $resource = ResourceInfoFactory::defaultResource()->merge(
                ResourceInfo::create(Attributes::create([
                    'service.name' => $this->serviceName,
                    'service.version' => $this->serviceVersion,
                    'deployment.environment' => $this->environment,
                ]))
            );

            $transport = (new PsrTransportFactory())->create($this->tracesEndpoint, 'application/x-protobuf');
            $spanExporter = new SpanExporter($transport);

            $this->tracerProvider = new TracerProvider(
                [new SimpleSpanProcessor($spanExporter)],
                null,
                $resource
            );

            $this->tracer = $this->tracerProvider->getTracer($this->serviceName);

            $this->logger->info('OpenTelemetry tracing initialized successfully');
        } catch (\Throwable $e) {
            $this->logger->error('Failed to initialize OpenTelemetry: {error}', [
                'error' => $e->getMessage()
            ]);

            // Fallback to no-op tracer
            $this->tracerProvider = new TracerProvider();
            $this->tracer = $this->tracerProvider->getTracer($this->serviceName);
        }
    }

    private function initializeMetrics(): void
    {
        try {
            $resource = ResourceInfoFactory::defaultResource()->merge(
                ResourceInfo::create(Attributes::create([
                    'service.name' => $this->serviceName,
                    'service.version' => $this->serviceVersion,
                    'deployment.environment' => $this->environment,
                ]))
            );

            $transport = (new PsrTransportFactory())->create($this->metricsEndpoint, 'application/x-protobuf');
            $metricExporter = new MetricExporter($transport);

            $reader = new ExportingReader($metricExporter);

            $this->meterProvider = MeterProvider::builder()
                ->setResource($resource)
                ->addReader($reader)
                ->build();

            $this->meter = $this->meterProvider->getMeter($this->serviceName);

            $this->logger->info('OpenTelemetry metrics initialized successfully');
        } catch (\Throwable $e) {
            $this->logger->error('Failed to initialize OpenTelemetry metrics: {error}', [
                'error' => $e->getMessage()
            ]);

            // Fallback to no-op meter
            $this->meterProvider = MeterProvider::builder()->build();
            $this->meter = $this->meterProvider->getMeter($this->serviceName);
        }
    }

    final public function startSpan(string $name, array $attributes = []): \OpenTelemetry\API\Trace\SpanInterface
    {
        $spanBuilder = $this->tracer->spanBuilder($name);

        foreach ($attributes as $key => $value) {
            $spanBuilder->setAttribute($key, (string) $value);
        }

        // Use current context to create child spans
        $spanBuilder->setParent(Context::getCurrent());
        return $spanBuilder->startSpan();
    }

    final public function getTracer(): TracerInterface
    {
        return $this->tracer;
    }

    final public function getMeter(): MeterInterface
    {
        return $this->meter;
    }

    final public function logTrace(string $operation, array $context = []): void
    {
        $span = $this->startSpan($operation);

        foreach ($context as $key => $value) {
            $span->setAttribute($key, (string) $value);
        }

        $span->end();
    }

    final public function incrementCounter(string $name, int|float $value = 1, array $attributes = []): void
    {
        $counter = $this->meter->createCounter($name);
        $counter->add($value, $attributes);
    }

    final public function recordHistogram(string $name, int|float $value, array $attributes = []): void
    {
        $histogram = $this->meter->createHistogram($name);
        $histogram->record($value, $attributes);
    }

    final public function updateGauge(string $name, int|float $value, array $attributes = []): void
    {
        $gauge = $this->meter->createUpDownCounter($name);
        $gauge->add($value, $attributes);
    }
}