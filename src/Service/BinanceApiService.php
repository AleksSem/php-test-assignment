<?php

namespace App\Service;

use App\Helper\BinanceHelper;
use App\Repository\CryptoRateRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class BinanceApiService
{
    public function __construct(
        private BinanceApiClientService $binanceClient,
        private CryptoRateRepository $cryptoRateRepository,
        private CryptoRatePersistenceService $persistence,
        private HistoricalRateBackfillService $backfillService,
        private LoggerInterface $logger,
        private array $supportedPairs,
        private string $cryptoRatesInterval,
    ) {
    }

    /**
     * Fetches currency rates from Binance API and saves them to database
     * Fills any missing data since the last recorded entry using 5m intervals
     */
    public function updateRates(): void
    {
        foreach ($this->supportedPairs as $pair => $symbol) {
            try {
                $this->fillMissingRates($pair, $symbol);
            } catch (Throwable $e) {
                $this->logger->error('Failed to update rates for {pair}: {error}', [
                    'pair' => $pair,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Fills missing rates for a specific pair since last recorded entry
     */
    private function fillMissingRates(string $pair, string $symbol): void
    {
        $latestRate = $this->cryptoRateRepository->findLatestRate($pair);
        $now = new DateTimeImmutable();

        // If no data exists, start from 24 hours ago
        if (!$latestRate) {
            $startTime = $now->modify('-24 hours');
            $this->logger->info('No previous data found for {pair}, starting from 24 hours ago', ['pair' => $pair]);
        } else {
            // Start from the last recorded timestamp
            $startTime = $latestRate->getTimestamp();
        }

        $endTime = $now;

        $this->logger->info('Filling missing rates for {pair} from {start} to {end}', [
            'pair' => $pair,
            'start' => $startTime->format('Y-m-d H:i:s'),
            'end' => $endTime->format('Y-m-d H:i:s')
        ]);

        // Fetch klines data with configured interval
        $klines = $this->binanceClient->fetchKlines($symbol, $this->cryptoRatesInterval, $startTime, $endTime);

        // Process and save each kline
        $savedCount = 0;
        foreach ($klines as $kline) {
            try {
                $timestamp = BinanceHelper::createDateTimeFromBinanceTimestamp($kline[0]);
                $closePrice = $kline[4]; // Close price
            } catch (Throwable $e) {
                $this->logger->warning('Failed to process kline data for {pair}: {error}', [
                    'pair' => $pair,
                    'kline' => $kline,
                    'error' => $e->getMessage()
                ]);
                continue;
            }

            // Check if this rate already exists
            if (!$this->cryptoRateRepository->rateExists($pair, $timestamp)) {
                $this->persistence->saveRate($pair, $closePrice, $timestamp);
                $savedCount++;
            }
        }

        $this->logger->info('Successfully filled {count} new intervals for {pair}', [
            'count' => $savedCount,
            'pair' => $pair
        ]);
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
    public function getRatesForDay(string $pair, DateTimeImmutable $date): array
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
