<?php

namespace App\Tests\Unit\Service;

use App\Entity\CryptoRate;
use App\Service\CryptoRatePersistenceService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CryptoRatePersistenceServiceTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private CryptoRatePersistenceService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new CryptoRatePersistenceService($this->entityManager);
    }

    public function testSaveRate(): void
    {
        $pair = 'EUR/BTC';
        $rate = '98606.63000000';
        $timestamp = new DateTimeImmutable('2023-12-25 10:00:00');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (CryptoRate $cryptoRate) use ($pair, $rate, $timestamp) {
                return $cryptoRate->getPair() === $pair
                    && $cryptoRate->getRate() === $rate
                    && $cryptoRate->getTimestamp() == $timestamp;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->saveRate($pair, $rate, $timestamp);
    }

    public function testSaveRatesBatchWithSmallBatch(): void
    {
        $rates = [
            [
                'pair' => 'EUR/BTC',
                'rate' => '98606.63000000',
                'timestamp' => new DateTimeImmutable('2023-12-25 10:00:00')
            ],
            [
                'pair' => 'EUR/ETH',
                'rate' => '3804.28000000',
                'timestamp' => new DateTimeImmutable('2023-12-25 10:05:00')
            ]
        ];

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(CryptoRate::class));

        // Should flush once at the end for small batch
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $inserted = $this->service->saveRatesBatch($rates);

        $this->assertEquals(2, $inserted);
    }

    public function testSaveRatesBatchWithLargeBatch(): void
    {
        // Create 250 rates to test batching (default batch size is 100)
        $rates = [];
        for ($i = 0; $i < 250; $i++) {
            $rates[] = [
                'pair' => 'EUR/BTC',
                'rate' => '98606.' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'timestamp' => new DateTimeImmutable('2023-12-25 10:' . str_pad((string) ($i % 60), 2, '0', STR_PAD_LEFT) . ':00')
            ];
        }

        $this->entityManager
            ->expects($this->exactly(250))
            ->method('persist')
            ->with($this->isInstanceOf(CryptoRate::class));

        // Should flush 3 times: at 100, 200, and final flush
        $this->entityManager
            ->expects($this->exactly(3))
            ->method('flush');

        $inserted = $this->service->saveRatesBatch($rates);

        $this->assertEquals(250, $inserted);
    }

    public function testSaveRatesBatchWithCustomBatchSize(): void
    {
        $rates = [];
        for ($i = 0; $i < 25; $i++) {
            $rates[] = [
                'pair' => 'EUR/BTC',
                'rate' => '98606.63000000',
                'timestamp' => new DateTimeImmutable('2023-12-25 10:' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ':00')
            ];
        }

        $this->entityManager
            ->expects($this->exactly(25))
            ->method('persist')
            ->with($this->isInstanceOf(CryptoRate::class));

        // With batch size of 10, should flush at 10, 20, and final
        $this->entityManager
            ->expects($this->exactly(3))
            ->method('flush');

        $inserted = $this->service->saveRatesBatch($rates, 10);

        $this->assertEquals(25, $inserted);
    }

    public function testSaveRatesBatchWithEmptyArray(): void
    {
        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        // Should still call flush once for empty array
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $inserted = $this->service->saveRatesBatch([]);

        $this->assertEquals(0, $inserted);
    }

    public function testSaveRateCreatesCorrectCryptoRateEntity(): void
    {
        $pair = 'EUR/LTC';
        $rate = '97.74000000';
        $timestamp = new DateTimeImmutable('2023-12-25 15:30:00');

        $capturedEntity = null;
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (CryptoRate $entity) use (&$capturedEntity) {
                $capturedEntity = $entity;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->saveRate($pair, $rate, $timestamp);

        $this->assertInstanceOf(CryptoRate::class, $capturedEntity);
        $this->assertEquals($pair, $capturedEntity->getPair());
        $this->assertEquals($rate, $capturedEntity->getRate());
        $this->assertEquals($timestamp, $capturedEntity->getTimestamp());
    }

    public function testSaveRatesBatchCreatesCorrectEntities(): void
    {
        $rates = [
            [
                'pair' => 'EUR/BTC',
                'rate' => '98606.63000000',
                'timestamp' => new DateTimeImmutable('2023-12-25 10:00:00')
            ],
            [
                'pair' => 'EUR/ETH',
                'rate' => '3804.28000000',
                'timestamp' => new DateTimeImmutable('2023-12-25 10:05:00')
            ]
        ];

        $capturedEntities = [];
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function (CryptoRate $entity) use (&$capturedEntities) {
                $capturedEntities[] = $entity;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->saveRatesBatch($rates);

        $this->assertCount(2, $capturedEntities);

        $this->assertEquals('EUR/BTC', $capturedEntities[0]->getPair());
        $this->assertEquals('98606.63000000', $capturedEntities[0]->getRate());

        $this->assertEquals('EUR/ETH', $capturedEntities[1]->getPair());
        $this->assertEquals('3804.28000000', $capturedEntities[1]->getRate());
    }
}