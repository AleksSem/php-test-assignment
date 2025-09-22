<?php

declare(strict_types=1);

namespace App\Monitoring\Metrics;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Decorator pattern implementation for SQL driver with metrics collection.
 * Wraps database driver operations to provide monitoring capabilities.
 */
final class SqlMetricsDriver implements Driver
{
    public function __construct(
        private readonly Driver $driver,
        private readonly SqlMetricsCollector $metricsCollector
    ) {}

    public function connect(array $params): Connection
    {
        $connection = $this->driver->connect($params);
        return new SqlMetricsConnection($connection, $this->metricsCollector);
    }

    public function getDatabasePlatform(): AbstractPlatform
    {
        return $this->driver->getDatabasePlatform();
    }

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return $this->driver->getSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): Driver\API\ExceptionConverter
    {
        return $this->driver->getExceptionConverter();
    }
}
