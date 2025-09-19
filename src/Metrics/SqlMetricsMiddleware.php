<?php

declare(strict_types=1);

namespace App\Metrics;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Factory pattern implementation for SQL metrics middleware.
 * Creates decorated drivers with metrics collection capabilities.
 */
final class SqlMetricsMiddleware implements Middleware
{
    public function __construct(
        private readonly SqlMetricsCollector $metricsCollector
    ) {}

    public function wrap(Driver $driver): Driver
    {
        return new SqlMetricsDriver($driver, $this->metricsCollector);
    }
}