<?php

namespace App\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class FastIntegrationTestCase extends KernelTestCase
{
    use DatabaseTransactionTrait;

    protected EntityManagerInterface $entityManager;
    protected FixtureLoader $fixtureLoader;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel(['environment' => 'test']);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Always setup database for each test
        DatabaseTestHelper::setupTestDatabase(self::$kernel);

        // Create fixture loader
        $this->fixtureLoader = new FixtureLoader($this->entityManager);

        // Start transaction for fast cleanup
        $this->beginDatabaseTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction - much faster than DELETE statements
        $this->rollbackDatabaseTransaction();

        parent::tearDown();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function loadBasicFixtures(): array
    {
        $fixtureLoader = new FixtureLoader($this->entityManager);
        return $fixtureLoader->loadBasicCryptoRates();
    }

    protected function loadLast24HoursFixtures(string $pair = 'EUR/BTC'): array
    {
        $fixtureLoader = new FixtureLoader($this->entityManager);
        return $fixtureLoader->loadLast24HoursRates($pair);
    }

    protected function loadAllFixtures(): array
    {
        $fixtureLoader = new FixtureLoader($this->entityManager);
        return $fixtureLoader->loadAllFixtures();
    }
}