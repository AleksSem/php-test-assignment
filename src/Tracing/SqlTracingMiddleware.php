<?php

declare(strict_types=1);

namespace App\Tracing;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Factory pattern implementation for SQL tracing middleware.
 * Creates decorated drivers with OpenTelemetry tracing capabilities.
 */
final class SqlTracingMiddleware implements Middleware
{
    public function __construct(
        private readonly SqlTracingCollector $tracingCollector
    ) {}

    public function wrap(Driver $driver): Driver
    {
        return new SqlTracingDriver($driver, $this->tracingCollector);
    }
}