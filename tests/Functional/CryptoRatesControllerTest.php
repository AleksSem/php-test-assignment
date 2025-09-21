<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CryptoRatesControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLast24HoursWithValidPair(): void
    {
        $this->client->request('GET', '/api/rates/last-24h?pair=EUR/BTC');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('pair', $data);
        $this->assertArrayHasKey('chart', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertEquals('EUR/BTC', $data['pair']);

        // Test chart structure
        $this->assertArrayHasKey('labels', $data['chart']);
        $this->assertArrayHasKey('datasets', $data['chart']);
        $this->assertIsArray($data['chart']['labels']);
        $this->assertIsArray($data['chart']['datasets']);

        // Test dataset structure
        if (!empty($data['chart']['datasets'])) {
            $dataset = $data['chart']['datasets'][0];
            $this->assertArrayHasKey('label', $dataset);
            $this->assertArrayHasKey('data', $dataset);
            $this->assertArrayHasKey('borderColor', $dataset);
            $this->assertEquals('Exchange Rate', $dataset['label']);
        }
    }

    public function testLast24HoursWithInvalidPair(): void
    {
        $this->client->request('GET', '/api/rates/last-24h?pair=INVALID');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
        $this->assertEquals('Validation failed', $data['error']);
    }

    public function testLast24HoursWithMissingPair(): void
    {
        $this->client->request('GET', '/api/rates/last-24h');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation failed', $data['error']);
    }

    public function testDayWithValidParameters(): void
    {
        $this->client->request('GET', '/api/rates/day?pair=EUR/BTC&date=2025-09-21');

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('pair', $data);
        $this->assertArrayHasKey('date', $data);
        $this->assertArrayHasKey('chart', $data);
        $this->assertArrayHasKey('count', $data);

        $this->assertEquals('EUR/BTC', $data['pair']);
        $this->assertEquals('2025-09-21', $data['date']);
    }

    public function testDayWithInvalidDate(): void
    {
        $this->client->request('GET', '/api/rates/day?pair=EUR/BTC&date=invalid-date');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation failed', $data['error']);
    }

    public function testDayWithMissingDate(): void
    {
        $this->client->request('GET', '/api/rates/day?pair=EUR/BTC');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation failed', $data['error']);
    }

    public function testAllSupportedPairs(): void
    {
        $supportedPairs = ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'];

        foreach ($supportedPairs as $pair) {
            $this->client->request('GET', '/api/rates/last-24h?pair=' . urlencode($pair));

            $this->assertResponseIsSuccessful();

            $data = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertEquals($pair, $data['pair']);
        }
    }

    public function testChartDataPrecision(): void
    {
        $this->client->request('GET', '/api/rates/last-24h?pair=EUR/BTC');

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($data['chart']['datasets'][0]['data'])) {
            $rate = $data['chart']['datasets'][0]['data'][0];

            // Check that rate is a string with proper decimal precision
            $this->assertIsString($rate);
            $this->assertMatchesRegularExpression('/^\d+\.\d{8}$/', $rate);
        }
    }

    public function testTimestampFormats(): void
    {
        // Test 24h format
        $this->client->request('GET', '/api/rates/last-24h?pair=EUR/BTC');
        $data24h = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($data24h['chart']['labels'])) {
            $label = $data24h['chart']['labels'][0];
            // Should be in format "Sep-21 12:22"
            $this->assertMatchesRegularExpression('/^[A-Z][a-z]{2}-\d{2} \d{2}:\d{2}$/', $label);
        }

        // Test day format
        $this->client->request('GET', '/api/rates/day?pair=EUR/BTC&date=2025-09-21');
        $dataDay = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($dataDay['chart']['labels'])) {
            $label = $dataDay['chart']['labels'][0];
            // Should be in format "12:22"
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $label);
        }
    }
}