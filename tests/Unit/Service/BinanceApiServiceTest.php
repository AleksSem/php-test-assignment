<?php

namespace App\Tests\Unit\Service;

use App\Entity\CryptoRate;
use App\Repository\CryptoRateRepository;
use App\Service\BinanceApiClientInterface;
use App\Service\BinanceApiService;
use App\Service\CryptoRatePersistenceService;
use App\Service\HistoricalRateBackfillService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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
        $this->binanceClient = $this->createMock(BinanceApiClientInterface::class);
        $this->repository = $this->createMock(CryptoRateRepository::class);
        $this->persistence = $this->createMock(CryptoRatePersistenceService::class);
        $this->backfillService = $this->createMock(HistoricalRateBackfillService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $supportedPairs = [
            'EUR/BTC' => 'BTCEUR',
            'EUR/ETH' => 'ETHEUR',
            'EUR/LTC' => 'LTCEUR'
        ];

        $this->service = new BinanceApiService(
            $this->binanceClient,
            $this->repository,
            $this->persistence,
            $this->backfillService,
            $this->logger,
            $supportedPairs
        );
    }

    public function testUpdateRatesSuccess(): void
    {
        $this->binanceClient
            ->expects($this->exactly(3))
            ->method('fetchCurrentPrice')
            ->willReturnOnConsecutiveCalls('98606.63000000', '3804.28000000', '97.74000000');

        $this->persistence
            ->expects($this->exactly(3))
            ->method('saveRate')
            ->with(
                $this->logicalOr('EUR/BTC', 'EUR/ETH', 'EUR/LTC'),
                $this->logicalOr('98606.63000000', '3804.28000000', '97.74000000'),
                $this->isInstanceOf(\DateTimeImmutable::class)
            );

        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->with(
                'Updated rate for {pair}: {rate}',
                $this->isType('array')
            );

        $this->service->updateRates();
    }

    public function testUpdateRatesWithApiFailure(): void
    {
        $this->binanceClient
            ->expects($this->exactly(3))
            ->method('fetchCurrentPrice')
            ->willThrowException(new \RuntimeException('API Error'));

        $this->persistence
            ->expects($this->never())
            ->method('saveRate');

        $this->logger
            ->expects($this->exactly(3))
            ->method('error')
            ->with(
                'Failed to update rate for {pair}: {error}',
                $this->isType('array')
            );

        $this->service->updateRates();
    }

    public function testGetRatesForLast24Hours(): void
    {
        $expectedRates = [
            $this->createCryptoRate('EUR/BTC', '98606.63000000'),
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
        $date = new \DateTimeImmutable('2025-09-21');
        $expectedRates = [
            $this->createCryptoRate('EUR/BTC', '98606.63000000'),
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
        $cryptoRate->setTimestamp(new \DateTimeImmutable());

        return $cryptoRate;
    }
}