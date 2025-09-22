<?php

namespace App\Tests\Unit\Service;

use App\Entity\CryptoRate;
use App\Repository\CryptoRateRepository;
use App\Service\BinanceApiClientService;
use App\Service\BinanceApiService;
use App\Service\CryptoRatePersistenceService;
use App\Service\HistoricalRateBackfillService;
use App\Tests\Helper\TestConstants;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;

class BinanceApiServiceTest extends TestCase
{

    private BinanceApiService $service;
    private MockObject $binanceClient;
    private MockObject $repository;
    private MockObject $persistence;
    private MockObject $backfillService;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->binanceClient = $this->createMock(BinanceApiClientService::class);
        $this->repository = $this->createMock(CryptoRateRepository::class);
        $this->persistence = $this->createMock(CryptoRatePersistenceService::class);
        $this->backfillService = $this->createMock(HistoricalRateBackfillService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $supportedPairs = array_slice(TestConstants::DEFAULT_SUPPORTED_PAIRS, 0, 3, true);

        $this->service = new BinanceApiService(
            $this->binanceClient,
            $this->repository,
            $this->persistence,
            $this->backfillService,
            $this->logger,
            $supportedPairs,
            TestConstants::INTERVALS['DEFAULT']
        );
    }

    public function testUpdateRatesSuccess(): void
    {
        $mockKlinesData = [
            [
                1640995200000, // Open time
                "98600.00000000", // Open price
                "98700.00000000", // High price
                "98500.00000000", // Low price
                TestConstants::DEFAULT_RATES['BTCEUR'], // Close price
                "1.23456789", // Volume
                1640995259999, // Close time
                "121317.07000000", // Quote asset volume
                55, // Number of trades
                "0.61728394", // Taker buy base asset volume
                "60658.53500000", // Taker buy quote asset volume
                "0" // Ignore
            ]
        ];

        // No existing rates found
        $this->repository
            ->expects($this->exactly(3))
            ->method('findLatestRate')
            ->willReturn(null);

        $this->binanceClient
            ->expects($this->exactly(3))
            ->method('fetchKlines')
            ->willReturn($mockKlinesData);

        $this->repository
            ->expects($this->exactly(3))
            ->method('rateExists')
            ->willReturn(false);

        $this->persistence
            ->expects($this->exactly(3))
            ->method('saveRate')
            ->with(
                $this->logicalOr('EUR/BTC', 'EUR/ETH', 'EUR/LTC'),
                TestConstants::DEFAULT_RATES['BTCEUR'],
                $this->isInstanceOf(DateTimeImmutable::class)
            );

        $this->service->updateRates();
    }

    public function testUpdateRatesWithApiFailure(): void
    {
        $this->repository
            ->expects($this->exactly(3))
            ->method('findLatestRate')
            ->willReturn(null);

        $this->binanceClient
            ->expects($this->exactly(3))
            ->method('fetchKlines')
            ->willThrowException(new RuntimeException('API Error'));

        $this->persistence
            ->expects($this->never())
            ->method('saveRate');

        $this->logger
            ->expects($this->exactly(3))
            ->method('error')
            ->with(
                'Failed to update rates for {pair}: {error}',
                $this->isArray()
            );

        $this->service->updateRates();
    }

    public function testGetRatesForLast24Hours(): void
    {
        $expectedRates = [
            $this->createCryptoRate('EUR/BTC', TestConstants::DEFAULT_RATES['BTCEUR']),
            $this->createCryptoRate('EUR/BTC', '98654.08000000')
        ];

        $this->repository
            ->expects($this->once())
            ->method('findRatesForLast24Hours')
            ->with('EUR/BTC')
            ->willReturn($expectedRates);

        $result = $this->service->getRatesForLast24Hours('EUR/BTC');

        $this->assertEquals($expectedRates, $result);
    }

    public function testGetRatesForDay(): void
    {
        $date = new DateTimeImmutable(TestConstants::TEST_DATES['DEFAULT_DATE']);
        $expectedRates = [
            $this->createCryptoRate('EUR/BTC', TestConstants::DEFAULT_RATES['BTCEUR']),
        ];

        $this->repository
            ->expects($this->once())
            ->method('findRatesForDay')
            ->with('EUR/BTC', $date)
            ->willReturn($expectedRates);

        $result = $this->service->getRatesForDay('EUR/BTC', $date);

        $this->assertEquals($expectedRates, $result);
    }

    public function testBackfillHistoricalRates(): void
    {
        $expectedResult = ['backfilled' => 100];

        $this->backfillService
            ->expects($this->once())
            ->method('backfillHistoricalRates')
            ->with(7, 'EUR/BTC')
            ->willReturn($expectedResult);

        $result = $this->service->backfillHistoricalRates(7, 'EUR/BTC');

        $this->assertEquals($expectedResult, $result);
    }

    private function createCryptoRate(string $pair, string $rate): CryptoRate
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair($pair);
        $cryptoRate->setRate($rate);
        $cryptoRate->setTimestamp(new DateTimeImmutable());

        return $cryptoRate;
    }
}