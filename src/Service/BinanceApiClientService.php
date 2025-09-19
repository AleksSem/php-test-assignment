<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class BinanceApiClientService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $binanceApiUrl,
        private string $binanceKlinesUrl,
    ) {
    }

    /**
     * Fetches current price for a symbol from Binance API
     */
    public function fetchCurrentPrice(string $symbol): string
    {
        try {
            $response = $this->httpClient->request('GET', $this->binanceApiUrl, [
                'query' => ['symbol' => $symbol],
                'timeout' => 10
            ]);

            $data = $response->toArray();

            if (!isset($data['price'])) {
                throw new \RuntimeException('Price not found in Binance API response');
            }

            return $data['price'];
        } catch (ExceptionInterface $e) {
            $this->logger->error('Failed to fetch current price for symbol {symbol}: {error}', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to fetch rate from Binance API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetches historical klines data from Binance API
     */
    public function fetchKlines(
        string $symbol,
        string $interval,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): array {
        try {
            $response = $this->httpClient->request('GET', $this->binanceKlinesUrl, [
                'query' => [
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'startTime' => $startTime->getTimestamp() * 1000,
                    'endTime' => $endTime->getTimestamp() * 1000,
                    'limit' => 1000
                ],
                'timeout' => 30
            ]);

            return $response->toArray();
        } catch (ExceptionInterface $e) {
            $this->logger->error('Failed to fetch klines for symbol {symbol}: {error}', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to fetch klines from Binance API: ' . $e->getMessage(), 0, $e);
        }
    }
}