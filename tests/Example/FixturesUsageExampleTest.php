<?php

namespace App\Tests\Example;

use App\Tests\Fixtures\CryptoRateFixtures;
use App\Tests\Fixtures\RequestFixtures;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Example test demonstrating how to use Fixtures
 *
 * This file shows practical examples of using fixtures in tests
 */
class FixturesUsageExampleTest extends TestCase
{
    public function testCryptoRateFixturesBasicUsage(): void
    {
        // Create a valid BTC rate
        $btcRate = CryptoRateFixtures::createBtcRate('98606.63000000');

        $this->assertEquals('EUR/BTC', $btcRate->getPair());
        $this->assertEquals('98606.63000000', $btcRate->getRate());
        $this->assertInstanceOf(DateTimeImmutable::class, $btcRate->getTimestamp());
    }

    public function testCryptoRateFixturesAllPairs(): void
    {
        // Create rates for all supported pairs
        $btc = CryptoRateFixtures::createBtcRate();
        $eth = CryptoRateFixtures::createEthRate();
        $ltc = CryptoRateFixtures::createLtcRate();

        $this->assertEquals('EUR/BTC', $btc->getPair());
        $this->assertEquals('EUR/ETH', $eth->getPair());
        $this->assertEquals('EUR/LTC', $ltc->getPair());
    }

    public function testCryptoRateFixturesTimeSeries(): void
    {
        // Create a sequence of rates with timestamps
        $rates = CryptoRateFixtures::createTimestampSequence('EUR/BTC', 5, '+5 minutes');

        $this->assertCount(5, $rates);

        // Verify timestamps are sequential
        for ($i = 1, $iMax = count($rates); $i < $iMax; $i++) {
            $this->assertGreaterThan(
                $rates[$i - 1]->getTimestamp(),
                $rates[$i]->getTimestamp()
            );
        }
    }

    public function testCryptoRateFixturesChartData(): void
    {
        // Get sample chart data
        $chartRates = CryptoRateFixtures::createChartDataSample();

        $this->assertCount(5, $chartRates);

        // Verify all are BTC rates
        foreach ($chartRates as $rate) {
            $this->assertEquals('EUR/BTC', $rate->getPair());
        }
    }

    public function testCryptoRateFixturesLast24Hours(): void
    {
        // Create 24 hours of rate data
        $rates = CryptoRateFixtures::createLast24HoursRates('EUR/BTC');

        $this->assertCount(24, $rates);

        // Verify time span is approximately 24 hours
        $firstTime = $rates[0]->getTimestamp();
        $lastTime = $rates[count($rates) - 1]->getTimestamp();
        $timeDiff = $lastTime->getTimestamp() - $firstTime->getTimestamp();

        $this->assertGreaterThanOrEqual(23 * 3600, $timeDiff); // At least 23 hours
        $this->assertLessThanOrEqual(24 * 3600, $timeDiff);    // At most 24 hours
    }

    public function testRequestFixturesBasicUsage(): void
    {
        // Create valid requests
        $cryptoRequest = RequestFixtures::createValidCryptoRatesRequest('EUR/BTC', '2025-09-21');
        $last24hRequest = RequestFixtures::createValidLast24HoursRequest('EUR/BTC');

        $this->assertEquals('EUR/BTC', $cryptoRequest->getPair());
        $this->assertEquals('2025-09-21', $cryptoRequest->getDate());
        $this->assertEquals('EUR/BTC', $last24hRequest->getPair());
    }

    public function testRequestFixturesValidation(): void
    {
        // Test invalid requests
        $invalidPair = RequestFixtures::createInvalidPairRequest();
        $emptyPair = RequestFixtures::createEmptyPairRequest();
        $invalidDate = RequestFixtures::createInvalidDateRequest();

        $this->assertEquals('INVALID/PAIR', $invalidPair->getPair());
        $this->assertEquals('', $emptyPair->getPair());
        $this->assertEquals('invalid-date', $invalidDate->getDate());
    }

    public function testRequestFixturesAllSupportedPairs(): void
    {
        // Get all supported pair requests
        $requests = RequestFixtures::getAllSupportedPairRequests();

        $this->assertArrayHasKey('EUR/BTC', $requests);
        $this->assertArrayHasKey('EUR/ETH', $requests);
        $this->assertArrayHasKey('EUR/LTC', $requests);
    }

    public function testCombinedFixturesUsage(): void
    {
        // Example: Create rates and requests together
        $rates = CryptoRateFixtures::createMultiPairRates();
        $requests = RequestFixtures::getAllSupportedPairLast24HRequests();

        $this->assertCount(3, $rates);  // BTC, ETH, LTC rates
        $this->assertCount(3, $requests); // BTC, ETH, LTC requests

        // Verify pairs match
        $ratePairs = array_map(fn($rate) => $rate->getPair(), $rates);
        $requestPairs = array_map(fn($req) => $req->getPair(), array_values($requests));

        sort($ratePairs);
        sort($requestPairs);

        $this->assertEquals($ratePairs, $requestPairs);
    }

    public function testFixturesConstants(): void
    {
        // Test fixture constants
        $supportedPairs = CryptoRateFixtures::getSupportedPairs();
        $unsupportedPairs = CryptoRateFixtures::getUnsupportedPairs();

        $this->assertContains('EUR/BTC', $supportedPairs);
        $this->assertContains('EUR/ETH', $supportedPairs);
        $this->assertContains('EUR/LTC', $supportedPairs);

        $this->assertContains('USD/BTC', $unsupportedPairs);
        $this->assertContains('INVALID', $unsupportedPairs);
    }
}