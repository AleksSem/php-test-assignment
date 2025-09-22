<?php

declare(strict_types=1);

namespace App\Monitoring\Metrics;

use App\Monitoring\PrometheusService;

/**
 * Template method pattern implementation for SQL metrics collection.
 * Provides consistent metrics gathering across different SQL execution contexts.
 */
final class SqlMetricsCollector
{
    public function __construct(
        private readonly PrometheusService $prometheusService,
        private readonly SqlQueryTypeDetector $queryTypeDetector
    ) {}

    /**
     * Executes a callable while collecting SQL metrics.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws \Throwable
     */
    public function collectMetrics(string $sql, callable $operation): mixed
    {
        $startTime = microtime(true);
        $queryType = $this->queryTypeDetector->detect($sql);
        $tableName = $this->queryTypeDetector->extractTableName($sql);
        $context = $this->queryTypeDetector->getQueryContext($sql);

        try {
            $result = $operation();
            $this->recordSuccessMetrics($queryType, $tableName, $context, $startTime);
            return $result;
        } catch (\Throwable $exception) {
            $this->recordErrorMetrics($queryType, $tableName, $context, $startTime);
            throw $exception;
        }
    }

    private function recordSuccessMetrics(string $queryType, string $tableName, string $context, float $startTime): void
    {
        $this->prometheusService->incrementSqlQueries($queryType, $tableName, $context);
        $this->recordDuration($queryType, $tableName, $context, $startTime);
    }

    private function recordErrorMetrics(string $queryType, string $tableName, string $context, float $startTime): void
    {
        $this->prometheusService->incrementSqlQueries($queryType . '_error', $tableName, $context);
        $this->recordDuration($queryType, $tableName, $context, $startTime);
    }

    private function recordDuration(string $queryType, string $tableName, string $context, float $startTime): void
    {
        $duration = microtime(true) - $startTime;
        $this->prometheusService->observeSqlQueryDuration($queryType, $duration, $tableName, $context);
    }
}
