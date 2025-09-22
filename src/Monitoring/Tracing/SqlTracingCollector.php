<?php

declare(strict_types=1);

namespace App\Monitoring\Tracing;

use App\Monitoring\Metrics\SqlQueryTypeDetector;
use App\Monitoring\OpenTelemetryService;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Collects and processes SQL query tracing for OpenTelemetry.
 */
final class SqlTracingCollector
{
    public function __construct(
        private readonly OpenTelemetryService $openTelemetryService,
        private readonly SqlQueryTypeDetector $queryTypeDetector
    ) {}

    public function startQuerySpan(string $sql, array $params = []): SpanInterface
    {
        $queryType = $this->queryTypeDetector->detect($sql);

        $span = $this->openTelemetryService->startSpan("db.query.$queryType", [
            'db.system' => 'mysql',
            'db.statement' => $sql,
            'db.operation' => $queryType,
        ]);

        if (!empty($params)) {
            $span->setAttribute('db.params.count', count($params));
        }

        return $span;
    }

    public function finishQuerySpan(SpanInterface $span, float $duration, ?string $error = null): void
    {
        $span->setAttribute('db.duration', $duration);

        if ($error !== null) {
            $span->setAttributes([
                'error' => true,
                'error.message' => $error,
            ]);
            $span->setStatus(StatusCode::STATUS_ERROR, $error);
        }

        $span->end();
    }
}
