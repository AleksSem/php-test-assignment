<?php

declare(strict_types=1);

namespace App\Monitoring\Metrics;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * Decorator pattern implementation for SQL statement with metrics collection.
 * Wraps prepared statement operations to provide monitoring capabilities.
 */
final class SqlMetricsStatement implements Statement
{
    public function __construct(
        private readonly Statement $statement,
        private readonly SqlMetricsCollector $metricsCollector,
        private readonly string $sql
    ) {}

    public function bindValue($param, $value, $type = ParameterType::STRING): void
    {
        $this->statement->bindValue($param, $value, $type);
    }

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null): void
    {
        $this->statement->bindParam($param, $variable, $type, $length);
    }

    public function execute($params = null): Result
    {
        return $this->metricsCollector->collectMetrics(
            $this->sql,
            fn(): Result => $this->statement->execute($params)
        );
    }
}
