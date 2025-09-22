<?php

namespace App\Service;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class BinanceApiClientService implements BinanceApiClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $binanceKlinesUrl,
        private int $binanceKlinesTimeout,
        private int $binanceKlinesLimit,
    ) {
    }

    /**
     * Fetches historical klines data from Binance API
     */
    public function fetchKlines(
        string $symbol,
        string $interval,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime
    ): array {
        try {
            $response = $this->httpClient->request('GET', $this->binanceKlinesUrl, [
                'query' => [
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'startTime' => $startTime->getTimestamp() * 1000,
                    'endTime' => $endTime->getTimestamp() * 1000,
                    'limit' => $this->binanceKlinesLimit
                ],
                'timeout' => $this->binanceKlinesTimeout
            ]);

            return $response->toArray();
        } catch (Throwable $e) {
            $this->logger->error('Failed to fetch klines for symbol {symbol}: {error}', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException('Failed to fetch klines from Binance API: ' . $e->getMessage(), 0, $e);
        }
    }
}
