<?php

namespace App\Tests\Helper;

use App\Entity\CryptoRate;
use App\Tests\Fixtures\CryptoRateFixtures;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class FixtureLoader
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function loadBasicCryptoRates(): array
    {
        $rates = CryptoRateFixtures::createMultiPairRates();

        foreach ($rates as $rate) {
            $this->entityManager->persist($rate);
        }

        $this->entityManager->flush();

        return $rates;
    }

    public function loadLast24HoursRates(string $pair = 'EUR/BTC'): array
    {
        $rates = CryptoRateFixtures::createLast24HoursRates($pair);

        foreach ($rates as $rate) {
            $this->entityManager->persist($rate);
        }

        $this->entityManager->flush();

        return $rates;
    }

    public function loadDayRates(string $pair = 'EUR/BTC', ?DateTimeImmutable $date = null): array
    {
        $rates = CryptoRateFixtures::createDayRates($pair, $date);

        foreach ($rates as $rate) {
            $this->entityManager->persist($rate);
        }

        $this->entityManager->flush();

        return $rates;
    }

    public function loadChartDataSample(): array
    {
        $rates = CryptoRateFixtures::createChartDataSample();

        foreach ($rates as $rate) {
            $this->entityManager->persist($rate);
        }

        $this->entityManager->flush();

        return $rates;
    }

    public function loadTimestampSequence(string $pair = 'EUR/BTC', int $count = 5, string $interval = '+5 minutes'): array
    {
        $rates = CryptoRateFixtures::createTimestampSequence($pair, $count, $interval);

        foreach ($rates as $rate) {
            $this->entityManager->persist($rate);
        }

        $this->entityManager->flush();

        return $rates;
    }

    public function loadMinimalFixtures(): array
    {
        $rates = CryptoRateFixtures::createMultiPairRates();

        foreach ($rates as $rate) {
            $this->entityManager->persist($rate);
        }

        $this->entityManager->flush();

        return $rates;
    }

    public function loadAllFixtures(): array
    {
        $allRates = [];

        // Load basic rates for all pairs
        $allRates['basic'] = $this->loadBasicCryptoRates();

        // Load 24h data for BTC
        $allRates['last24h_btc'] = $this->loadLast24HoursRates('EUR/BTC');

        // Load chart data sample
        $allRates['chart_sample'] = $this->loadChartDataSample();

        return $allRates;
    }

    public function persistEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }

    public function clearDatabase(): void
    {
        DatabaseTestHelper::resetDatabase($this->entityManager);
    }
}