<?php

namespace App\Tests\Functional;

use App\Tests\Helper\BaseWebTestCase;
use App\Tests\Helper\TestConstants;

class CryptoRatesControllerTest extends BaseWebTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFullTestFixtures();
        $this->loadAdditionalFixtures();
    }

    private function loadAdditionalFixtures(): void
    {
        $supportedPairs = array_keys(array_slice(TestConstants::DEFAULT_SUPPORTED_PAIRS, 0, 3, true));
        foreach ($supportedPairs as $pair) {
            if ($pair !== 'EUR/BTC') {
                $this->fixtureLoader->loadLast24HoursRates($pair);
            }
        }
    }

    public function testLast24HoursWithValidPair(): void
    {
        $this->client->request('GET', '/api/rates/last-24h?pair=EUR/BTC');

        $data = $this->assertSuccessfulApiResponse();

        $this->assertArrayHasKey('pair', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertEquals('EUR/BTC', $data['pair']);

        $this->assertChartStructure($data);

        // Test dataset structure
        if (!empty($data['chart']['datasets'])) {
            $this->assertDatasetStructure($data['chart']['datasets'][0]);
        }
    }

    public function testLast24HoursWithInvalidPair(): void
    {
        $this->client->request('GET', '/api/rates/last-24h?pair=INVALID');

        $this->assertValidationError();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('details', $data);
    }

    public function testLast24HoursWithMissingPair(): void
    {
        $this->client->request('GET', '/api/rates/last-24h');

        $this->assertValidationError();
    }

    public function testDayWithValidParameters(): void
    {
        $this->client->request('GET', '/api/rates/day?pair=EUR/BTC&date=' . TestConstants::TEST_DATES['DEFAULT_DATE']);

        $data = $this->assertSuccessfulApiResponse();

        $this->assertArrayHasKey('pair', $data);
        $this->assertArrayHasKey('date', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertEquals('EUR/BTC', $data['pair']);
        $this->assertEquals(TestConstants::TEST_DATES['DEFAULT_DATE'], $data['date']);

        $this->assertChartStructure($data);
    }

    public function testDayWithInvalidDate(): void
    {
        $this->client->request('GET', '/api/rates/day?pair=EUR/BTC&date=invalid-date');

        $this->assertValidationError();
    }

    public function testDayWithMissingDate(): void
    {
        $this->client->request('GET', '/api/rates/day?pair=EUR/BTC');

        $this->assertValidationError();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportedPairsProvider')]
    public function testSupportedPairSupport(string $pair): void
    {
        $this->client->request('GET', "/api/rates/last-24h?pair={$pair}");

        $data = $this->assertSuccessfulApiResponse();
        $this->assertEquals($pair, $data['pair']);
    }

    public static function supportedPairsProvider(): array
    {
        $supportedPairs = array_keys(array_slice(TestConstants::DEFAULT_SUPPORTED_PAIRS, 0, 3, true));
        return array_map(fn($pair) => [$pair], $supportedPairs);
    }

    public function testChartDataPrecision(): void
    {
        $this->client->request('GET', '/api/rates/last-24h?pair=EUR/BTC');

        $data = $this->assertSuccessfulApiResponse();

        if (!empty($data['chart']['datasets'][0]['data'])) {
            $rate = $data['chart']['datasets'][0]['data'][0];
            $this->assertIsString($rate);
            $this->assertRatePrecision($rate);
        }
    }

    public function test24HourTimestampFormat(): void
    {
        $this->client->request('GET', '/api/rates/last-24h?pair=EUR/BTC');
        $data = $this->assertSuccessfulApiResponse();

        $this->assertChartStructure($data);

        if (!empty($data['chart']['labels'])) {
            $this->assert24HourTimeFormat($data['chart']['labels'][0]);
        }
    }

    public function testDayTimestampFormat(): void
    {
        $this->client->request('GET', '/api/rates/day?pair=EUR/BTC&date=' . TestConstants::TEST_DATES['DEFAULT_DATE']);
        $data = $this->assertSuccessfulApiResponse();

        $this->assertChartStructure($data);

        if (!empty($data['chart']['labels'])) {
            $this->assertDayTimeFormat($data['chart']['labels'][0]);
        }
    }
}