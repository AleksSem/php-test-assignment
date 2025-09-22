<?php

declare(strict_types=1);

namespace App\Monitoring\Tracing;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Decorator driver that adds tracing capabilities to SQL operations.
 */
final class SqlTracingDriver implements Driver
{
    public function __construct(
        private readonly Driver $decoratedDriver,
        private readonly SqlTracingCollector $tracingCollector
    ) {}

    public function connect(array $params): SqlTracingConnection
    {
        $connection = $this->decoratedDriver->connect($params);
        return new SqlTracingConnection($connection, $this->tracingCollector);
    }

    public function getDatabasePlatform(): AbstractPlatform
    {
        return $this->decoratedDriver->getDatabasePlatform();
    }

    public function getSchemaManager(Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return $this->decoratedDriver->getSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return $this->decoratedDriver->getExceptionConverter();
    }
}
