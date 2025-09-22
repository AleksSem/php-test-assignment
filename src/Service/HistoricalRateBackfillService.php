<?php

namespace App\Service;

use App\Helper\BinanceHelper;
use App\Repository\CryptoRateRepository;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use DateInterval;

readonly class HistoricalRateBackfillService
{
    public function __construct(
        private BinanceApiClientService $binanceClient,
        private CryptoRateRepository $cryptoRateRepository,
        private CryptoRatePersistenceService $persistence,
        private LoggerInterface $logger,
        private array $supportedPairs,
        private string $cryptoRatesInterval,
    ) {
    }

    /**
     * Backfill historical rates from Binance API
     */
    public function backfillHistoricalRates(int $days, ?string $specificPair = null): array
    {
        $endTime = new DateTimeImmutable();
        $startTime = $endTime->modify("-{$days} days");

        $pairs = $this->supportedPairs;
        if ($specificPair !== null) {
            $pairs = [];
            if (isset($this->supportedPairs[$specificPair])) {
                $pairs = [$specificPair => $this->supportedPairs[$specificPair]];
            }
        }
        $totalInserted = 0;
        $pairsProcessed = [];

        foreach ($pairs as $pair => $symbol) {
            try {
                $this->logger->info('Starting backfill for {pair} from {start} to {end}', [
                    'pair' => $pair,
                    'start' => $startTime->format('Y-m-d H:i:s'),
                    'end' => $endTime->format('Y-m-d H:i:s')
                ]);

                $inserted = $this->backfillRatesForPair($pair, $symbol, $startTime, $endTime);

                $totalInserted += $inserted;
                $pairsProcessed[] = $pair;

                $this->logger->info('Backfilled {count} records for {pair}', [
                    'count' => $inserted,
                    'pair' => $pair
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to backfill {pair}: {error}', [
                    'pair' => $pair,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_inserted' => $totalInserted,
            'pairs_processed' => $pairsProcessed,
            'start_date' => $startTime->format('Y-m-d'),
            'end_date' => $endTime->format('Y-m-d')
        ];
    }

    /**
     * Backfill rates for a specific pair, handling large date ranges with pagination
     */
    private function backfillRatesForPair(string $pair, string $symbol, DateTimeImmutable $startTime, DateTimeImmutable $endTime): int
    {
        $totalInserted = 0;
        $currentStartTime = $startTime;

        // With 1000 API limit and 5m interval: ~3 days per request, use 3 days chunks for safety
        $chunkInterval = new DateInterval('P3D'); // 3 days

        while ($currentStartTime < $endTime) {
            $currentEndTime = $currentStartTime->add($chunkInterval);
            if ($currentEndTime > $endTime) {
                $currentEndTime = $endTime;
            }

            $this->logger->debug('Fetching chunk for {pair} from {start} to {end}', [
                'pair' => $pair,
                'start' => $currentStartTime->format('Y-m-d H:i:s'),
                'end' => $currentEndTime->format('Y-m-d H:i:s')
            ]);

            $klines = $this->binanceClient->fetchKlines($symbol, $this->cryptoRatesInterval, $currentStartTime, $currentEndTime);
            $inserted = $this->saveHistoricalRates($pair, $klines);
            $totalInserted += $inserted;

            $currentStartTime = $currentEndTime;
            usleep(100000); // 100ms delay to avoid rate limits
        }

        return $totalInserted;
    }

    /**
     * Save historical rates to database, avoiding duplicates
     */
    private function saveHistoricalRates(string $pair, array $klines): int
    {
        $ratesToSave = [];

        foreach ($klines as $kline) {
            try {
                $timestamp = BinanceHelper::createDateTimeFromBinanceTimestamp($kline[0]);
                $closePrice = $kline[4];
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to process kline data for {pair}: {error}', [
                    'pair' => $pair,
                    'kline' => $kline,
                    'error' => $e->getMessage()
                ]);
                continue;
            }

            if (!$this->cryptoRateRepository->rateExists($pair, $timestamp)) {
                $ratesToSave[] = [
                    'pair' => $pair,
                    'rate' => $closePrice,
                    'timestamp' => $timestamp
                ];
            }
        }

        return $this->persistence->saveRatesBatch($ratesToSave);
    }
}
