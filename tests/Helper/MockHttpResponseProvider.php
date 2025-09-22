<?php

namespace App\Tests\Helper;

use Symfony\Component\HttpClient\Response\MockResponse;

class MockHttpResponseProvider
{
    public function __invoke(string $method, string $url, array $options = []): MockResponse
    {
        if (str_contains($url, '/api/v3/klines')) {
            return $this->createKlinesMockResponse($options['query'] ?? []);
        }

        if (str_contains($url, '/api/v3/ticker/price')) {
            return $this->createPriceMockResponse($options['query'] ?? []);
        }

        return new MockResponse('{"error": "Endpoint not mocked"}', ['http_code' => 404]);
    }

    private function createKlinesMockResponse(array $query): MockResponse
    {
        $symbol = $query['symbol'] ?? 'BTCEUR';
        $limit = (int) ($query['limit'] ?? 288);

        $basePrice = $this->getBasePriceForSymbol($symbol);
        $baseTime = isset($query['startTime'])
            ? (int) $query['startTime']
            : (time() - 24 * 60 * 60) * 1000; // 24 hours ago

        $klines = [];
        for ($i = 0; $i < $limit; $i++) {
            $timestamp = $baseTime + ($i * 5 * 60 * 1000); // 5-minute intervals
            $price = $basePrice + (rand(-1000, 1000) / 100);

            $klines[] = [
                $timestamp, // Open time
                sprintf('%.8f', $price), // Open price
                sprintf('%.8f', $price + (rand(0, 500) / 100)), // High price
                sprintf('%.8f', $price - (rand(0, 500) / 100)), // Low price
                sprintf('%.8f', $price + (rand(-200, 200) / 100)), // Close price
                sprintf('%.8f', rand(100, 1000) / 100), // Volume
                $timestamp + (5 * 60 * 1000) - 1, // Close time
                sprintf('%.8f', rand(10000, 50000) / 100), // Quote asset volume
                rand(10, 100), // Number of trades
                sprintf('%.8f', rand(50, 500) / 100), // Taker buy base asset volume
                sprintf('%.8f', rand(5000, 25000) / 100), // Taker buy quote asset volume
                "0" // Ignore
            ];
        }

        return new MockResponse(json_encode($klines));
    }

    private function createPriceMockResponse(array $query): MockResponse
    {
        $symbol = $query['symbol'] ?? 'BTCEUR';
        $price = sprintf('%.8f', $this->getBasePriceForSymbol($symbol));

        return new MockResponse(json_encode([
            'symbol' => $symbol,
            'price' => $price
        ]));
    }

    private function getBasePriceForSymbol(string $symbol): float
    {
        $rates = TestConstants::DEFAULT_RATES;
        return isset($rates[$symbol]) ? (float) $rates[$symbol] : 100000.0;
    }
}