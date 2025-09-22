<?php

declare(strict_types=1);

namespace App\Monitoring\Tracing;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use Throwable;

/**
 * Decorator connection that adds tracing to SQL queries.
 */
final class SqlTracingConnection implements Connection
{
    public function __construct(
        private readonly Connection $decoratedConnection,
        private readonly SqlTracingCollector $tracingCollector
    ) {}

    public function prepare(string $sql): Statement
    {
        $statement = $this->decoratedConnection->prepare($sql);
        return new SqlTracingStatement($statement, $this->tracingCollector, $sql);
    }

    public function query(string $sql): Result
    {
        $startTime = microtime(true);
        $span = $this->tracingCollector->startQuerySpan($sql);

        try {
            $result = $this->decoratedConnection->query($sql);
            $duration = microtime(true) - $startTime;
            $this->tracingCollector->finishQuerySpan($span, $duration);
            return $result;
        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->tracingCollector->finishQuerySpan($span, $duration, $e->getMessage());
            throw $e;
        }
    }

    public function exec(string $sql): int
    {
        $startTime = microtime(true);
        $span = $this->tracingCollector->startQuerySpan($sql);

        try {
            $result = $this->decoratedConnection->exec($sql);
            $duration = microtime(true) - $startTime;
            $this->tracingCollector->finishQuerySpan($span, $duration);
            return $result;
        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->tracingCollector->finishQuerySpan($span, $duration, $e->getMessage());
            throw $e;
        }
    }

    public function lastInsertId($name = null): string|false
    {
        return $this->decoratedConnection->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        $span = $this->tracingCollector->startQuerySpan('BEGIN TRANSACTION');
        try {
            $result = $this->decoratedConnection->beginTransaction();
            $this->tracingCollector->finishQuerySpan($span, 0);
            return $result;
        } catch (Throwable $e) {
            $this->tracingCollector->finishQuerySpan($span, 0, $e->getMessage());
            throw $e;
        }
    }

    public function commit(): bool
    {
        $span = $this->tracingCollector->startQuerySpan('COMMIT');
        try {
            $result = $this->decoratedConnection->commit();
            $this->tracingCollector->finishQuerySpan($span, 0);
            return $result;
        } catch (Throwable $e) {
            $this->tracingCollector->finishQuerySpan($span, 0, $e->getMessage());
            throw $e;
        }
    }

    public function rollBack(): bool
    {
        $span = $this->tracingCollector->startQuerySpan('ROLLBACK');
        try {
            $result = $this->decoratedConnection->rollBack();
            $this->tracingCollector->finishQuerySpan($span, 0);
            return $result;
        } catch (Throwable $e) {
            $this->tracingCollector->finishQuerySpan($span, 0, $e->getMessage());
            throw $e;
        }
    }

    public function quote($value, $type = ParameterType::STRING): string
    {
        return $this->decoratedConnection->quote($value, $type);
    }

    public function getNativeConnection()
    {
        return $this->decoratedConnection->getNativeConnection();
    }
}
