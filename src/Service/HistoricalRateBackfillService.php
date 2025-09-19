<?php

namespace App\Service;

use App\Repository\CryptoRateRepository;
use Psr\Log\LoggerInterface;

final readonly class HistoricalRateBackfillService
{
    public function __construct(
        private BinanceApiClientService $binanceClient,
        private CryptoRateRepository $cryptoRateRepository,
        private CryptoRatePersistenceService $persistence,
        private LoggerInterface $logger,
        private array $supportedPairs,
    ) {
    }

    /**
     * Backfill historical rates from Binance API
     */
    public function backfillHistoricalRates(int $days, ?string $specificPair = null): array
    {
        $endTime = new \DateTimeImmutable();
        $startTime = $endTime->modify("-{$days} days");

        $pairs = $specificPair ? [$specificPair => $this->supportedPairs[$specificPair]] : $this->supportedPairs;
        $totalInserted = 0;
        $pairsProcessed = [];

        foreach ($pairs as $pair => $symbol) {
            try {
                $this->logger->info('Starting backfill for {pair} from {start} to {end}', [
                    'pair' => $pair,
                    'start' => $startTime->format('Y-m-d H:i:s'),
                    'end' => $endTime->format('Y-m-d H:i:s')
                ]);

                $klines = $this->binanceClient->fetchKlines($symbol, '5m', $startTime, $endTime);
                $inserted = $this->saveHistoricalRates($pair, $klines);

                $totalInserted += $inserted;
                $pairsProcessed[] = $pair;

                $this->logger->info('Backfilled {count} records for {pair}', [
                    'count' => $inserted,
                    'pair' => $pair
                ]);

                usleep(100000);

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
     * Save historical rates to database, avoiding duplicates
     */
    private function saveHistoricalRates(string $pair, array $klines): int
    {
        $ratesToSave = [];

        foreach ($klines as $kline) {
            $timestamp = new \DateTimeImmutable('@' . ($kline[0] / 1000));
            $closePrice = $kline[4];

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