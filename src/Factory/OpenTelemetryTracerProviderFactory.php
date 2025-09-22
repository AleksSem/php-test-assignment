<?php

namespace App\Factory;

use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

class OpenTelemetryTracerProviderFactory
{
    public function __construct(
        private readonly PsrTransportFactory $transportFactory,
        private readonly string $contentType = 'application/json'
    ) {}

    public function create(ResourceInfo $resource, string $endpoint): TracerProvider
    {
        $transport = $this->transportFactory->create($endpoint, $this->contentType);
        $exporter = new SpanExporter($transport);
        $processor = new SimpleSpanProcessor($exporter);

        return new TracerProvider([$processor], null, $resource);
    }
}