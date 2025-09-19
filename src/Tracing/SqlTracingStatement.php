<?php

declare(strict_types=1);

namespace App\Tracing;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * Decorator statement that adds tracing to prepared SQL queries.
 */
final class SqlTracingStatement implements Statement
{
    private array $params = [];

    public function __construct(
        private readonly Statement $decoratedStatement,
        private readonly SqlTracingCollector $tracingCollector,
        private readonly string $sql
    ) {}

    public function bindValue($param, $value, $type = ParameterType::STRING): bool
    {
        $this->params[$param] = $value;
        $result = $this->decoratedStatement->bindValue($param, $value, $type);
        return $result !== null ? $result : true;
    }

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null): bool
    {
        $this->params[$param] = &$variable;
        $result = $this->decoratedStatement->bindParam($param, $variable, $type, $length);
        return $result !== null ? $result : true;
    }

    public function execute($params = null): Result
    {
        $startTime = microtime(true);
        $finalParams = $params ?? $this->params;
        $span = $this->tracingCollector->startQuerySpan($this->sql, $finalParams);

        try {
            $result = $this->decoratedStatement->execute($params);
            $duration = microtime(true) - $startTime;
            $this->tracingCollector->finishQuerySpan($span, $duration);
            return $result;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->tracingCollector->finishQuerySpan($span, $duration, $e->getMessage());
            throw $e;
        }
    }
}