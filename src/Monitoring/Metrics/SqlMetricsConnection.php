<?php

declare(strict_types=1);

namespace App\Monitoring\Metrics;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * Decorator pattern implementation for SQL connection with metrics collection.
 * Wraps database connection operations to provide monitoring capabilities.
 */
final class SqlMetricsConnection implements Connection
{
    public function __construct(
        private readonly Connection $connection,
        private readonly SqlMetricsCollector $metricsCollector
    ) {}

    public function prepare(string $sql): Statement
    {
        return new SqlMetricsStatement(
            $this->connection->prepare($sql),
            $this->metricsCollector,
            $sql
        );
    }

    public function query(string $sql): Result
    {
        return $this->metricsCollector->collectMetrics(
            $sql,
            fn(): Result => $this->connection->query($sql)
        );
    }

    public function exec(string $sql): int
    {
        return $this->metricsCollector->collectMetrics(
            $sql,
            fn(): int => $this->connection->exec($sql)
        );
    }

    public function lastInsertId($name = null): string|int
    {
        return $this->connection->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    public function quote($value, $type = ParameterType::STRING): string
    {
        return $this->connection->quote($value, $type);
    }

    public function getServerVersion(): string
    {
        return $this->connection->getServerVersion();
    }

    public function getNativeConnection(): mixed
    {
        return $this->connection->getNativeConnection();
    }
}
