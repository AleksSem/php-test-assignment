<?php

namespace App\Monitoring;

use App\Factory\OpenTelemetryMeterProviderFactory;
use App\Factory\OpenTelemetryResourceFactory;
use App\Factory\OpenTelemetryTracerProviderFactory;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use Psr\Log\LoggerInterface;

class OpenTelemetryService
{
    private ?TracerInterface $tracer = null;
    private ?MeterInterface $meter = null;
    private bool $initialized = false;

    public function __construct(
        private readonly string $serviceName,
        private readonly string $serviceVersion,
        private readonly string $tracesEndpoint,
        private readonly string $metricsEndpoint,
        private readonly string $environment,
        private readonly LoggerInterface $logger,
        private readonly OpenTelemetryResourceFactory $resourceFactory,
        private readonly OpenTelemetryTracerProviderFactory $tracerProviderFactory,
        private readonly OpenTelemetryMeterProviderFactory $meterProviderFactory,
    ) {
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            $this->initializeComponents();
        } catch (\Throwable $e) {
            $this->logger->error('OpenTelemetry initialization failed', ['exception' => $e->getMessage()]);
        }

        $this->initialized = true;
    }

    private function initializeComponents(): void
    {
        $resource = $this->resourceFactory->create(
            $this->serviceName,
            $this->serviceVersion,
            $this->environment
        );

        $tracerProvider = $this->tracerProviderFactory->create($resource, $this->tracesEndpoint);
        $this->tracer = $tracerProvider->getTracer($this->serviceName);

        $meterProvider = $this->meterProviderFactory->create($resource, $this->metricsEndpoint);
        $this->meter = $meterProvider->getMeter($this->serviceName);
    }


    public function startSpan(string $name, array $attributes = []): SpanInterface
    {
        $this->ensureInitialized();

        $spanBuilder = $this->tracer->spanBuilder($name);
        foreach ($attributes as $key => $value) {
            $spanBuilder->setAttribute($key, (string) $value);
        }

        return $spanBuilder->setParent(Context::getCurrent())->startSpan();
    }

    public function getTracer(): TracerInterface
    {
        $this->ensureInitialized();
        return $this->tracer;
    }

    public function getMeter(): MeterInterface
    {
        $this->ensureInitialized();
        return $this->meter;
    }

}
