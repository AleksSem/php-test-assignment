<?php

namespace App\Tests\Integration\Database;

use App\Entity\CryptoRate;
use App\Repository\CryptoRateRepository;
use App\Tests\Helper\FastIntegrationTestCase;

class DatabaseFixturesTest extends FastIntegrationTestCase
{
    private CryptoRateRepository $cryptoRateRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cryptoRateRepository = $this->entityManager->getRepository(CryptoRate::class);
    }

    public function testDatabaseSchemaCreation(): void
    {
        // Verify tables exist by checking repository works
        $this->assertInstanceOf(CryptoRateRepository::class, $this->cryptoRateRepository);

        // Test basic database operations
        $count = $this->cryptoRateRepository->count([]);
        $this->assertEquals(0, $count);
    }

    public function testBasicFixturesLoading(): void
    {
        // Load basic fixtures
        $rates = $this->loadBasicFixtures();

        $this->assertCount(3, $rates);

        // Verify data was persisted
        $count = $this->cryptoRateRepository->count([]);
        $this->assertEquals(3, $count);

        // Verify different pairs exist
        $btcRate = $this->cryptoRateRepository->findOneBy(['pair' => 'EUR/BTC']);
        $ethRate = $this->cryptoRateRepository->findOneBy(['pair' => 'EUR/ETH']);
        $ltcRate = $this->cryptoRateRepository->findOneBy(['pair' => 'EUR/LTC']);

        $this->assertNotNull($btcRate);
        $this->assertNotNull($ethRate);
        $this->assertNotNull($ltcRate);
    }

    public function testLast24HoursFixtures(): void
    {
        // Load 24 hours of data
        $rates = $this->loadLast24HoursFixtures('EUR/BTC');

        $this->assertCount(24, $rates);

        // Verify data was persisted
        $count = $this->cryptoRateRepository->count(['pair' => 'EUR/BTC']);
        $this->assertEquals(24, $count);

        // Verify time ordering
        $persistedRates = $this->cryptoRateRepository->findBy(
            ['pair' => 'EUR/BTC'],
            ['timestamp' => 'ASC']
        );

        for ($i = 1, $iMax = count($persistedRates); $i < $iMax; $i++) {
            $this->assertLessThan(
                $persistedRates[$i]->getTimestamp(),
                $persistedRates[$i - 1]->getTimestamp()
            );
        }
    }

    public function testAllFixturesLoading(): void
    {
        // Load all fixtures
        $allRates = $this->loadAllFixtures();

        $this->assertArrayHasKey('basic', $allRates);
        $this->assertArrayHasKey('last24h_btc', $allRates);
        $this->assertArrayHasKey('chart_sample', $allRates);

        // Verify total count
        $totalCount = $this->cryptoRateRepository->count([]);
        $expectedCount = count($allRates['basic']) + count($allRates['last24h_btc']) + count($allRates['chart_sample']);
        $this->assertEquals($expectedCount, $totalCount);
    }

    public function testDatabaseResetBetweenTests(): void
    {
        // Load some data
        $this->loadBasicFixtures();
        $count = $this->cryptoRateRepository->count([]);
        $this->assertGreaterThan(0, $count);

        // Clear database
        $this->fixtureLoader->clearDatabase();

        // Verify database is empty
        $count = $this->cryptoRateRepository->count([]);
        $this->assertEquals(0, $count);
    }

}