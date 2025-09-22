<?php

namespace App\Tests\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

trait DatabaseTrait
{
    protected FixtureLoader $fixtureLoader;

    protected function setupDatabase(EntityManagerInterface $entityManager): void
    {
        DatabaseTestHelper::setupTestDatabase(self::$kernel);
        DatabaseTestHelper::resetDatabase($entityManager);
        $this->fixtureLoader = new FixtureLoader($entityManager);
    }

    protected function loadMinimalFixtures(): array
    {
        return $this->fixtureLoader->loadMinimalFixtures();
    }

    protected function loadBasicFixtures(): array
    {
        return $this->fixtureLoader->loadBasicCryptoRates();
    }

    protected function loadLast24HoursFixtures(string $pair = 'EUR/BTC'): array
    {
        return $this->fixtureLoader->loadLast24HoursRates($pair);
    }

    protected function loadAllFixtures(): array
    {
        return $this->fixtureLoader->loadAllFixtures();
    }

    protected function clearDatabase(): void
    {
        DatabaseTestHelper::resetDatabase($this->getEntityManager());
    }

    abstract protected function getEntityManager(): EntityManagerInterface;
}