<?php

declare(strict_types=1);

namespace App\Monitoring\Metrics;

/**
 * Strategy pattern implementation for SQL query type detection.
 * Provides centralized logic for identifying SQL operation types.
 */
final class SqlQueryTypeDetector
{
    private const QUERY_TYPE_MAP = [
        'SELECT' => 'SELECT',
        'INSERT' => 'INSERT',
        'UPDATE' => 'UPDATE',
        'DELETE' => 'DELETE',
        'CREATE' => 'CREATE',
        'ALTER' => 'ALTER',
        'DROP' => 'DROP',
    ];

    private const DEFAULT_QUERY_TYPE = 'OTHER';

    public function detect(string $sql): string
    {
        $normalizedSql = $this->normalizeSql($sql);

        foreach (self::QUERY_TYPE_MAP as $prefix => $type) {
            if (str_starts_with($normalizedSql, $prefix)) {
                return $type;
            }
        }

        return self::DEFAULT_QUERY_TYPE;
    }

    public function extractTableName(string $sql): string
    {
        $normalizedSql = $this->normalizeSql($sql);

        // Extract table name from different SQL operations
        if (str_starts_with($normalizedSql, 'SELECT')) {
            if (preg_match('/FROM\s+(\w+)/i', $normalizedSql, $matches)) {
                return $matches[1];
            }
        } elseif (str_starts_with($normalizedSql, 'INSERT')) {
            if (preg_match('/INSERT\s+INTO\s+(\w+)/i', $normalizedSql, $matches)) {
                return $matches[1];
            }
        } elseif (str_starts_with($normalizedSql, 'UPDATE')) {
            if (preg_match('/UPDATE\s+(\w+)/i', $normalizedSql, $matches)) {
                return $matches[1];
            }
        } elseif (str_starts_with($normalizedSql, 'DELETE')) {
            if (preg_match('/DELETE\s+FROM\s+(\w+)/i', $normalizedSql, $matches)) {
                return $matches[1];
            }
        }

        return 'unknown';
    }

    public function getQueryContext(string $sql): string
    {
        $normalizedSql = $this->normalizeSql($sql);

        // Identify common query patterns
        if (str_contains($normalizedSql, 'CRYPTO_RATE')) {
            if (str_contains($normalizedSql, 'TIMESTAMP >= ?')) {
                return 'historical_query';
            } elseif (str_contains($normalizedSql, 'TIMESTAMP BETWEEN')) {
                return 'date_range_query';
            } elseif (str_contains($normalizedSql, 'INSERT INTO')) {
                return 'rate_insert';
            }
            return 'crypto_rate_operation';
        }

        if (str_contains($normalizedSql, 'DOCTRINE_MIGRATION')) {
            return 'migration';
        }

        if (str_contains($normalizedSql, 'INFORMATION_SCHEMA')) {
            return 'schema_check';
        }

        return 'general';
    }

    private function normalizeSql(string $sql): string
    {
        return trim(strtoupper($sql));
    }
}
