<?php

namespace App\Monitoring;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;
use Prometheus\RenderTextFormat;

class PrometheusService
{
    private Counter $httpRequestsTotal;
    private Histogram $httpRequestDuration;
    private Counter $sqlQueriesTotal;
    private Histogram $sqlQueryDuration;

    public function __construct(
        private readonly CollectorRegistry $registry,
        private readonly string $appName,
        private readonly string $appVersion,
        private readonly array $httpDurationBuckets,
        private readonly array $sqlDurationBuckets,
    ) {

        $this->httpRequestsTotal = $this->registry->getOrRegisterCounter(
            $this->appName,
            'http_requests_total',
            'Total number of HTTP requests',
            ['method', 'route', 'status_code']
        );

        $this->httpRequestDuration = $this->registry->getOrRegisterHistogram(
            $this->appName,
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route'],
            $this->httpDurationBuckets
        );

        $this->sqlQueriesTotal = $this->registry->getOrRegisterCounter(
            $this->appName,
            'sql_queries_total',
            'Total number of SQL queries',
            ['query_type', 'table', 'context']
        );

        $this->sqlQueryDuration = $this->registry->getOrRegisterHistogram(
            $this->appName,
            'sql_query_duration_seconds',
            'SQL query duration in seconds',
            ['query_type', 'table', 'context'],
            $this->sqlDurationBuckets
        );
    }

    public function incrementHttpRequests(string $method, string $route, int $statusCode): void
    {
        $this->httpRequestsTotal->inc([
            'method' => $method,
            'route' => $route,
            'status_code' => (string) $statusCode
        ]);
    }

    public function observeHttpRequestDuration(string $method, string $route, float $duration): void
    {
        $this->httpRequestDuration->observe($duration, [
            'method' => $method,
            'route' => $route
        ]);
    }

    public function incrementSqlQueries(string $queryType, string $tableName = 'unknown', string $context = 'general'): void
    {
        $this->sqlQueriesTotal->inc([
            'query_type' => $queryType,
            'table' => $tableName,
            'context' => $context
        ]);
    }

    public function observeSqlQueryDuration(string $queryType, float $duration, string $tableName = 'unknown', string $context = 'general'): void
    {
        $this->sqlQueryDuration->observe($duration, [
            'query_type' => $queryType,
            'table' => $tableName,
            'context' => $context
        ]);
    }

    public function render(): string
    {
        $appInfo = "# {$this->appName} v{$this->appVersion}\n";
        $renderer = new RenderTextFormat();
        return $appInfo . $renderer->render($this->registry->getMetricFamilySamples());
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }
}
