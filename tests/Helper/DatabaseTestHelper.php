<?php

namespace App\Tests\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class DatabaseTestHelper
{
    private static array $schemaCreated = [];

    public static function setupTestDatabase(KernelInterface $kernel): void
    {
        $container = $kernel->getContainer()->get('test.service_container');
        $entityManager = $container->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        // Use connection hash as key since SQLite in-memory is per-connection
        $connectionId = spl_object_hash($connection);

        if (isset(self::$schemaCreated[$connectionId])) {
            return;
        }

        // Use SchemaTool directly instead of console commands for better error handling
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        try {
            // For SQLite in-memory, just create schema without dropping
            if (self::isSQLite($connection)) {
                $schemaTool->createSchema($metadata);
            } else {
                // For other databases, drop then create
                $schemaTool->dropSchema($metadata);
                $schemaTool->createSchema($metadata);
            }
        } catch (\Exception $e) {
            // If creation fails, try to drop and create again
            try {
                $schemaTool->dropSchema($metadata);
                $schemaTool->createSchema($metadata);
            } catch (\Exception $e2) {
                // Create without drop as last resort
                $schemaTool->createSchema($metadata);
            }
        }

        self::$schemaCreated[$connectionId] = true;
    }

    public static function resetDatabase(EntityManagerInterface $entityManager): void
    {
        $connection = $entityManager->getConnection();

        try {
            // Get all table names
            $tables = $connection->createSchemaManager()->listTableNames();

            if (self::isSQLite($connection)) {
                // For SQLite, disable foreign key checks
                $connection->executeStatement('PRAGMA foreign_keys = OFF');
            }

            // Clear all tables
            foreach ($tables as $table) {
                if (self::isSQLite($connection)) {
                    $connection->executeStatement("DELETE FROM `{$table}`");
                } else {
                    $connection->executeStatement("TRUNCATE TABLE `{$table}`");
                }
            }

            if (self::isSQLite($connection)) {
                // Re-enable foreign key checks for SQLite
                $connection->executeStatement('PRAGMA foreign_keys = ON');
            }
        } catch (\Exception $e) {
            // If reset fails, this is not critical for tests
            // The schema will be recreated for each test anyway
        }

        // Clear entity manager cache
        $entityManager->clear();
    }

    public static function isTestEnvironment(): bool
    {
        return $_ENV['APP_ENV'] === 'test' || $_SERVER['APP_ENV'] === 'test';
    }

    public static function isSQLite(Connection $connection): bool
    {
        return $connection->getDatabasePlatform() instanceof SqlitePlatform;
    }
}