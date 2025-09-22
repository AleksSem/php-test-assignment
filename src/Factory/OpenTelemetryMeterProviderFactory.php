<?php

namespace App\Factory;

use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;

class OpenTelemetryMeterProviderFactory
{
    public function __construct(
        private readonly PsrTransportFactory $transportFactory,
        private readonly string $contentType = 'application/json'
    ) {}

    public function create(ResourceInfo $resource, string $endpoint): MeterProviderInterface
    {
        $transport = $this->transportFactory->create($endpoint, $this->contentType);
        $exporter = new MetricExporter($transport);
        $reader = new ExportingReader($exporter);

        return MeterProvider::builder()
            ->setResource($resource)
            ->addReader($reader)
            ->build();
    }
}