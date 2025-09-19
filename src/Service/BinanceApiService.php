<?php

namespace App\Service;

use App\Repository\CryptoRateRepository;
use Psr\Log\LoggerInterface;

final readonly class BinanceApiService
{
    public function __construct(
        private BinanceApiClientService $binanceClient,
        private CryptoRateRepository $cryptoRateRepository,
        private CryptoRatePersistenceService $persistence,
        private HistoricalRateBackfillService $backfillService,
        private LoggerInterface $logger,
        private array $supportedPairs,
    ) {
    }

    /**
     * Fetches currency rates from Binance API and saves them to database
     */
    public function updateRates(): void
    {
        $timestamp = new \DateTimeImmutable();

        foreach ($this->supportedPairs as $pair => $symbol) {
            try {
                $rate = $this->binanceClient->fetchCurrentPrice($symbol);
                $this->persistence->saveRate($pair, $rate, $timestamp);

                $this->logger->info('Updated rate for {pair}: {rate}', [
                    'pair' => $pair,
                    'rate' => $rate
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update rate for {pair}: {error}', [
                    'pair' => $pair,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Gets rates for the last 24 hours
     */
    public function getRatesForLast24Hours(string $pair): array
    {
        return $this->cryptoRateRepository->findRatesForLast24Hours($pair);
    }

    /**
     * Gets rates for specified day
     */
    public function getRatesForDay(string $pair, \DateTimeImmutable $date): array
    {
        return $this->cryptoRateRepository->findRatesForDay($pair, $date);
    }

    /**
     * Backfill historical rates from Binance API
     */
    public function backfillHistoricalRates(int $days, ?string $specificPair = null): array
    {
        return $this->backfillService->backfillHistoricalRates($days, $specificPair);
    }

}
