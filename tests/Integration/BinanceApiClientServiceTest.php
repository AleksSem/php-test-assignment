<?php

namespace App\Tests\Integration;

use App\Service\BinanceApiClientService;
use App\Tests\Helper\FastIntegrationTestCase;
use App\Tests\Helper\TestConstants;
use DateTimeImmutable;

class BinanceApiClientServiceTest extends FastIntegrationTestCase
{
    private BinanceApiClientService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $this->service = $container->get(BinanceApiClientService::class);
    }

    public function testFetchKlinesSuccess(): void
    {
        $startTime = new DateTimeImmutable(TestConstants::TEST_DATES['DEFAULT_DATETIME']);
        $endTime = new DateTimeImmutable('2025-09-21 23:59:59');

        $result = $this->service->fetchKlines('BTCEUR', TestConstants::INTERVALS['DEFAULT'], $startTime, $endTime);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(12, $result[0]);
    }

    public function testFetchKlinesWithInvalidSymbol(): void
    {
        $startTime = new DateTimeImmutable(TestConstants::TEST_DATES['DEFAULT_DATETIME']);
        $endTime = new DateTimeImmutable('2025-09-21 23:59:59');

        $result = $this->service->fetchKlines('INVALIDSYMBOL', TestConstants::INTERVALS['DEFAULT'], $startTime, $endTime);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(12, $result[0]);
    }
}