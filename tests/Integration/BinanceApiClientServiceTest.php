<?php

namespace App\Tests\Integration;

use App\Service\BinanceApiClientService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportException;

class BinanceApiClientServiceTest extends TestCase
{
    private BinanceApiClientService $service;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testFetchCurrentPriceSuccess(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'symbol' => 'BTCEUR',
            'price' => '98606.63000000'
        ]));

        $httpClient = new MockHttpClient($mockResponse);

        $this->service = new BinanceApiClientService(
            $httpClient,
            $this->logger,
            'https://api.binance.com/api/v3/ticker/price',
            'https://api.binance.com/api/v3/klines'
        );

        $result = $this->service->fetchCurrentPrice('BTCEUR');

        $this->assertEquals('98606.63000000', $result);
    }

    public function testFetchCurrentPriceWithInvalidResponse(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'symbol' => 'BTCEUR'
            // Missing 'price' field
        ]));

        $httpClient = new MockHttpClient($mockResponse);

        $this->service = new BinanceApiClientService(
            $httpClient,
            $this->logger,
            'https://api.binance.com/api/v3/ticker/price',
            'https://api.binance.com/api/v3/klines'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Price not found in Binance API response');

        $this->service->fetchCurrentPrice('BTCEUR');
    }

    public function testFetchCurrentPriceWithHttpError(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 500]);

        $httpClient = new MockHttpClient($mockResponse);

        $this->service = new BinanceApiClientService(
            $httpClient,
            $this->logger,
            'https://api.binance.com/api/v3/ticker/price',
            'https://api.binance.com/api/v3/klines'
        );

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to fetch current price for symbol {symbol}: {error}',
                $this->isType('array')
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch rate from Binance API');

        $this->service->fetchCurrentPrice('BTCEUR');
    }

    public function testFetchCurrentPriceWithNetworkError(): void
    {
        $httpClient = new MockHttpClient(function () {
            throw new TransportException('Network error');
        });

        $this->service = new BinanceApiClientService(
            $httpClient,
            $this->logger,
            'https://api.binance.com/api/v3/ticker/price',
            'https://api.binance.com/api/v3/klines'
        );

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->expectException(\RuntimeException::class);

        $this->service->fetchCurrentPrice('BTCEUR');
    }

    public function testFetchKlinesSuccess(): void
    {
        $mockKlinesData = [
            [
                1640995200000, // Open time
                "98600.00000000", // Open price
                "98700.00000000", // High price
                "98500.00000000", // Low price
                "98606.63000000", // Close price
                "1.23456789", // Volume
                1640995259999, // Close time
                "121317.07000000", // Quote asset volume
                55, // Number of trades
                "0.61728394", // Taker buy base asset volume
                "60658.53500000", // Taker buy quote asset volume
                "0" // Ignore
            ]
        ];

        $mockResponse = new MockResponse(json_encode($mockKlinesData));

        $httpClient = new MockHttpClient($mockResponse);

        $this->service = new BinanceApiClientService(
            $httpClient,
            $this->logger,
            'https://api.binance.com/api/v3/ticker/price',
            'https://api.binance.com/api/v3/klines'
        );

        $startTime = new \DateTimeImmutable('2025-09-21 00:00:00');
        $endTime = new \DateTimeImmutable('2025-09-21 23:59:59');

        $result = $this->service->fetchKlines('BTCEUR', '5m', $startTime, $endTime);

        $this->assertEquals($mockKlinesData, $result);
    }

    public function testFetchKlinesWithError(): void
    {
        $httpClient = new MockHttpClient(function () {
            throw new TransportException('API Error');
        });

        $this->service = new BinanceApiClientService(
            $httpClient,
            $this->logger,
            'https://api.binance.com/api/v3/ticker/price',
            'https://api.binance.com/api/v3/klines'
        );

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to fetch klines for symbol {symbol}: {error}',
                $this->isType('array')
            );

        $startTime = new \DateTimeImmutable('2025-09-21 00:00:00');
        $endTime = new \DateTimeImmutable('2025-09-21 23:59:59');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch klines from Binance API');

        $this->service->fetchKlines('BTCEUR', '5m', $startTime, $endTime);
    }
}