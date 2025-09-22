<?php

namespace App\Tests\Integration;

use App\Entity\CryptoRate;
use App\Service\BinanceApiService;
use App\Service\BinanceApiClientService;
use App\Tests\Helper\FastIntegrationTestCase;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Integration test for the complete crypto rates workflow
 * Tests the end-to-end process of fetching, storing, and retrieving crypto rates
 * Uses HTTP mocks to prevent real API calls
 */
class CryptoRatesWorkflowTest extends FastIntegrationTestCase
{
    private BinanceApiService $binanceApiService;
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $this->binanceApiService = $container->get(BinanceApiService::class);
        $this->application = new Application(static::$kernel);
    }

    public function testCompleteUpdateRatesWorkflow(): void
    {
        // Verify database is initially empty
        $initialCount = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count([]);
        $this->assertEquals(0, $initialCount);

        // Execute update rates via service
        $this->binanceApiService->updateRates();

        // Verify rates were saved
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();

        $this->assertGreaterThan(0, count($savedRates));

        // Verify we have rates for all supported pairs
        $pairs = array_unique(array_map(fn(CryptoRate $rate) => $rate->getPair(), $savedRates));
        $this->assertContains('EUR/BTC', $pairs);
        $this->assertContains('EUR/ETH', $pairs);
        $this->assertContains('EUR/LTC', $pairs);

        // Verify rate format and data quality
        foreach ($savedRates as $rate) {
            $this->assertNotEmpty($rate->getPair());
            $this->assertIsNumeric($rate->getRate());
            $this->assertGreaterThan(0, (float) $rate->getRate());
            $this->assertInstanceOf(DateTimeImmutable::class, $rate->getTimestamp());
            $this->assertInstanceOf(DateTimeImmutable::class, $rate->getCreatedAt());
        }
    }

    public function testCompleteBackfillWorkflow(): void
    {
        // Execute backfill for 1 day
        $result = $this->binanceApiService->backfillHistoricalRates(1);

        // Verify backfill result structure
        $this->assertArrayHasKey('total_inserted', $result);
        $this->assertArrayHasKey('pairs_processed', $result);
        $this->assertArrayHasKey('start_date', $result);
        $this->assertArrayHasKey('end_date', $result);

        $this->assertGreaterThan(0, $result['total_inserted']);
        $this->assertIsArray($result['pairs_processed']);
        $this->assertNotEmpty($result['pairs_processed']);

        // Verify data was actually saved to database
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();

        $this->assertCount($result['total_inserted'], $savedRates);

        // Verify we have historical data spread over time
        $timestamps = array_map(fn(CryptoRate $rate) => $rate->getTimestamp(), $savedRates);
        $uniqueTimestamps = array_unique(array_map(fn(DateTimeImmutable $dt) => $dt->getTimestamp(), $timestamps));
        $this->assertGreaterThan(1, count($uniqueTimestamps), 'Should have rates at different timestamps');
    }

    public function testUpdateRatesCommand(): void
    {
        $command = $this->application->find('app:update-crypto-rates');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Cryptocurrency rates updated successfully!', $commandTester->getDisplay());

        // Verify data was saved
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();
        $this->assertGreaterThan(0, count($savedRates));
    }

    public function testBackfillCommand(): void
    {
        $command = $this->application->find('app:backfill-crypto-rates');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute(['days' => '1']);

        $this->assertEquals(0, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Historical rates backfilled successfully!', $output);
        $this->assertStringContainsString('Total records inserted:', $output);

        // Verify data was saved
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();
        $this->assertGreaterThan(0, count($savedRates));
    }

    public function testBackfillCommandWithSpecificPair(): void
    {
        $command = $this->application->find('app:backfill-crypto-rates');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            'days' => '1',
            '--pair' => 'EUR/BTC'
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Pairs processed: EUR/BTC', $commandTester->getDisplay());

        // Verify only EUR/BTC data was saved
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();

        $pairs = array_unique(array_map(fn(CryptoRate $rate) => $rate->getPair(), $savedRates));
        $this->assertEquals(['EUR/BTC'], $pairs);
    }

    public function testServiceIntegrationWithDatabase(): void
    {
        // Test that services properly integrate with the database layer
        $this->binanceApiService->updateRates();

        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();

        // Verify data was saved and is accessible via repository methods
        $this->assertGreaterThan(0, count($savedRates));

        // Test repository queries work with the saved data
        $repository = $this->entityManager->getRepository(CryptoRate::class);

        foreach (['EUR/BTC', 'EUR/ETH', 'EUR/LTC'] as $pair) {
            $latest = $repository->findLatestRate($pair);
            $this->assertInstanceOf(CryptoRate::class, $latest);
            $this->assertEquals($pair, $latest->getPair());
        }
    }

    public function testRepositoryMethodsWithRealData(): void
    {
        // Populate some test data
        $this->binanceApiService->backfillHistoricalRates(1, 'EUR/BTC');

        $repository = $this->entityManager->getRepository(CryptoRate::class);

        // Test last 24 hours method
        $last24hRates = $repository->findRatesForLast24Hours('EUR/BTC');
        $this->assertNotEmpty($last24hRates);

        foreach ($last24hRates as $rate) {
            $this->assertEquals('EUR/BTC', $rate->getPair());
            $this->assertGreaterThanOrEqual((new DateTimeImmutable('-24 hours'))->getTimestamp(), $rate->getTimestamp()->getTimestamp());
        }

        // Test find for specific day
        $today = new DateTimeImmutable();
        $todayRates = $repository->findRatesForDay('EUR/BTC', $today);

        foreach ($todayRates as $rate) {
            $this->assertEquals('EUR/BTC', $rate->getPair());
            $this->assertEquals($today->format('Y-m-d'), $rate->getTimestamp()->format('Y-m-d'));
        }

        // Test latest rate
        $latestRate = $repository->findLatestRate('EUR/BTC');
        $this->assertInstanceOf(CryptoRate::class, $latestRate);
        $this->assertEquals('EUR/BTC', $latestRate->getPair());

        // Test rate exists
        $exists = $repository->rateExists('EUR/BTC', $latestRate->getTimestamp());
        $this->assertTrue($exists);

        $notExists = $repository->rateExists('EUR/BTC', new DateTimeImmutable('1990-01-01'));
        $this->assertFalse($notExists);
    }

    public function testDuplicateHandling(): void
    {
        // Run backfill twice to test duplicate handling
        $result1 = $this->binanceApiService->backfillHistoricalRates(1, 'EUR/BTC');
        $result2 = $this->binanceApiService->backfillHistoricalRates(1, 'EUR/BTC');

        // Second run should insert 0 new records (all duplicates)
        $this->assertEquals(0, $result2['total_inserted']);

        // Total count should equal first run only
        $totalRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count(['pair' => 'EUR/BTC']);

        $this->assertEquals($result1['total_inserted'], $totalRates);
    }

    public function testDataConsistency(): void
    {
        // Populate data
        $this->binanceApiService->updateRates();

        $repository = $this->entityManager->getRepository(CryptoRate::class);
        $allRates = $repository->findAll();

        // Verify no duplicate pair+timestamp combinations
        $pairTimestampCombos = [];
        foreach ($allRates as $rate) {
            $combo = $rate->getPair() . '|' . $rate->getTimestamp()->format('Y-m-d H:i:s');
            $this->assertFalse(in_array($combo, $pairTimestampCombos), 'Found duplicate pair+timestamp combination');
            $pairTimestampCombos[] = $combo;
        }

        // Verify all rates have valid data
        foreach ($allRates as $rate) {
            $this->assertNotEmpty($rate->getPair());
            $this->assertMatchesRegularExpression('/^[\d.]+$/', $rate->getRate());
            $this->assertGreaterThan(0, (float) $rate->getRate());
            $this->assertInstanceOf(DateTimeImmutable::class, $rate->getTimestamp());
            $this->assertInstanceOf(DateTimeImmutable::class, $rate->getCreatedAt());
        }
    }

    public function testErrorHandlingInWorkflow(): void
    {
        // This test verifies that the workflow is resilient to partial failures
        // The mock HTTP client should provide consistent responses, but we can test edge cases

        $repository = $this->entityManager->getRepository(CryptoRate::class);

        // Test backfill with invalid pair (should not crash)
        $result = $this->binanceApiService->backfillHistoricalRates(1, 'INVALID/PAIR');

        // Should handle gracefully and return empty result
        $this->assertEquals(0, $result['total_inserted']);
        $this->assertEquals([], $result['pairs_processed']);

        // Database should remain clean
        $totalRates = $repository->count([]);
        $this->assertEquals(0, $totalRates);
    }
}