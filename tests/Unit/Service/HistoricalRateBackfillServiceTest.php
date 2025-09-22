<?php

namespace App\Tests\Unit\Service;

use App\Repository\CryptoRateRepository;
use App\Service\BinanceApiClientService;
use App\Service\CryptoRatePersistenceService;
use App\Service\HistoricalRateBackfillService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class HistoricalRateBackfillServiceTest extends TestCase
{
    private BinanceApiClientService|MockObject $binanceClient;
    private CryptoRateRepository|MockObject $cryptoRateRepository;
    private CryptoRatePersistenceService|MockObject $persistence;
    private LoggerInterface|MockObject $logger;
    private HistoricalRateBackfillService $service;

    private array $supportedPairs = [
        'EUR/BTC' => 'BTCEUR',
        'EUR/ETH' => 'ETHEUR',
        'EUR/LTC' => 'LTCEUR'
    ];

    protected function setUp(): void
    {
        $this->binanceClient = $this->createMock(BinanceApiClientService::class);
        $this->cryptoRateRepository = $this->createMock(CryptoRateRepository::class);
        $this->persistence = $this->createMock(CryptoRatePersistenceService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new HistoricalRateBackfillService(
            $this->binanceClient,
            $this->cryptoRateRepository,
            $this->persistence,
            $this->logger,
            $this->supportedPairs,
            '5m'
        );
    }

    public function testBackfillHistoricalRatesSuccess(): void
    {
        $days = 7;
        $mockKlines = [
            [1703505600000, '98600.00', '98700.00', '98500.00', '98606.63', '1.5', 1703505899999, '147909.945', 25, '0.75', '73954.9725', '0'],
            [1703505900000, '98606.63', '98750.00', '98550.00', '98650.50', '2.1', 1703506199999, '207166.05', 30, '1.05', '103583.025', '0']
        ];

        // With 7 days and 3-day chunks: 3 chunks per pair * 3 pairs = 9 calls
        $this->binanceClient
            ->expects($this->exactly(9))
            ->method('fetchKlines')
            ->willReturn($mockKlines);

        $this->cryptoRateRepository
            ->expects($this->exactly(18)) // 2 klines * 9 calls
            ->method('rateExists')
            ->willReturn(false); // No duplicates

        $this->persistence
            ->expects($this->exactly(9)) // One call per chunk
            ->method('saveRatesBatch')
            ->willReturn(2); // 2 rates per chunk

        $this->logger
            ->expects($this->exactly(6)) // 3 pairs * 2 log calls (start + end)
            ->method('info');

        $result = $this->service->backfillHistoricalRates($days);

        $this->assertEquals(18, $result['total_inserted']); // 2 rates * 9 chunks
        $this->assertEquals(['EUR/BTC', 'EUR/ETH', 'EUR/LTC'], $result['pairs_processed']);
        $this->assertArrayHasKey('start_date', $result);
        $this->assertArrayHasKey('end_date', $result);
    }

    public function testBackfillHistoricalRatesWithSpecificPair(): void
    {
        $days = 3;
        $specificPair = 'EUR/BTC';
        $mockKlines = [
            [1703505600000, '98600.00', '98700.00', '98500.00', '98606.63', '1.5', 1703505899999, '147909.945', 25, '0.75', '73954.9725', '0']
        ];

        // 3 days = 1 chunk for one pair
        $this->binanceClient
            ->expects($this->once())
            ->method('fetchKlines')
            ->with('BTCEUR', '5m', $this->isInstanceOf(DateTimeImmutable::class), $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn($mockKlines);

        $this->cryptoRateRepository
            ->expects($this->once())
            ->method('rateExists')
            ->willReturn(false);

        $this->persistence
            ->expects($this->once())
            ->method('saveRatesBatch')
            ->with($this->callback(function (array $rates) {
                return count($rates) === 1 && $rates[0]['pair'] === 'EUR/BTC';
            }))
            ->willReturn(1);

        $result = $this->service->backfillHistoricalRates($days, $specificPair);

        $this->assertEquals(1, $result['total_inserted']);
        $this->assertEquals(['EUR/BTC'], $result['pairs_processed']);
    }

    public function testBackfillHistoricalRatesWithDuplicates(): void
    {
        $days = 1;
        $mockKlines = [
            [1703505600000, '98600.00', '98700.00', '98500.00', '98606.63', '1.5', 1703505899999, '147909.945', 25, '0.75', '73954.9725', '0'],
            [1703505900000, '98606.63', '98750.00', '98550.00', '98650.50', '2.1', 1703506199999, '207166.05', 30, '1.05', '103583.025', '0']
        ];

        // 1 day = 1 chunk per pair * 3 pairs = 3 calls
        $this->binanceClient
            ->expects($this->exactly(3))
            ->method('fetchKlines')
            ->willReturn($mockKlines);

        // 2 klines * 3 pairs = 6 calls
        $this->cryptoRateRepository
            ->expects($this->exactly(6))
            ->method('rateExists')
            ->willReturn(true); // Assume all are duplicates for simplicity

        $this->persistence
            ->expects($this->exactly(3))
            ->method('saveRatesBatch')
            ->with([]) // All duplicates, so empty arrays
            ->willReturn(0);

        $result = $this->service->backfillHistoricalRates($days);

        $this->assertEquals(0, $result['total_inserted']); // All duplicates
    }

    public function testBackfillHistoricalRatesWithApiError(): void
    {
        $days = 1;

        // 1 day = 1 chunk per pair * 3 pairs = 3 calls
        $this->binanceClient
            ->expects($this->exactly(3))
            ->method('fetchKlines')
            ->willThrowException(new \Exception('API Error'));

        $this->logger
            ->expects($this->exactly(3))
            ->method('info'); // Start messages

        $this->logger
            ->expects($this->exactly(3))
            ->method('error'); // Error messages

        $this->persistence
            ->expects($this->never())
            ->method('saveRatesBatch');

        $result = $this->service->backfillHistoricalRates($days);

        $this->assertEquals(0, $result['total_inserted']);
        $this->assertEquals([], $result['pairs_processed']);
    }


    public function testBackfillHistoricalRatesWithInvalidKlineData(): void
    {
        $days = 1;
        $mockKlines = [
            [1703505600000, '98600.00', '98700.00', '98500.00', '98606.63', '1.5', 1703505899999, '147909.945', 25, '0.75', '73954.9725', '0'],
            ['invalid', 'data'], // Invalid kline data
            [1703505900000, '98606.63', '98750.00', '98550.00', '98650.50', '2.1', 1703506199999, '207166.05', 30, '1.05', '103583.025', '0']
        ];

        // 1 day for 1 specific pair = 1 chunk = 1 call
        $this->binanceClient
            ->expects($this->once())
            ->method('fetchKlines')
            ->willReturn($mockKlines);

        $this->cryptoRateRepository
            ->expects($this->exactly(2)) // Only for valid klines
            ->method('rateExists')
            ->willReturn(false);

        $this->persistence
            ->expects($this->once())
            ->method('saveRatesBatch')
            ->with($this->callback(function (array $rates) {
                return count($rates) === 2; // Only 2 valid rates
            }))
            ->willReturn(2);

        $this->logger
            ->expects($this->once())
            ->method('warning'); // Warning for invalid kline

        $result = $this->service->backfillHistoricalRates($days, 'EUR/BTC');

        $this->assertEquals(2, $result['total_inserted']);
    }

    public function testBackfillHistoricalRatesDateRange(): void
    {
        $days = 5;

        // 5 days with 3-day chunks: 2 chunks per pair * 3 pairs = 6 calls
        $this->binanceClient
            ->expects($this->exactly(6))
            ->method('fetchKlines')
            ->with(
                $this->anything(),
                '5m',
                $this->isInstanceOf(DateTimeImmutable::class),
                $this->isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn([]);

        $this->persistence
            ->expects($this->exactly(6))
            ->method('saveRatesBatch')
            ->willReturn(0);

        $result = $this->service->backfillHistoricalRates($days);

        $this->assertEquals(0, $result['total_inserted']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $result['start_date']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $result['end_date']);
    }

    public function testBackfillHistoricalRatesWithEmptyKlines(): void
    {
        $days = 1;

        // 1 day = 1 chunk per pair * 3 pairs = 3 calls
        $this->binanceClient
            ->expects($this->exactly(3))
            ->method('fetchKlines')
            ->willReturn([]);

        $this->cryptoRateRepository
            ->expects($this->never())
            ->method('rateExists');

        $this->persistence
            ->expects($this->exactly(3))
            ->method('saveRatesBatch')
            ->with([])
            ->willReturn(0);

        $result = $this->service->backfillHistoricalRates($days);

        $this->assertEquals(0, $result['total_inserted']);
        $this->assertEquals(['EUR/BTC', 'EUR/ETH', 'EUR/LTC'], $result['pairs_processed']);
    }
}