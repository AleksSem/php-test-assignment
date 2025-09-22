<?php

namespace App\Tests\Unit\Command;

use App\Command\BackfillCryptoRatesCommand;
use App\Service\BinanceApiService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BackfillCryptoRatesCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject $binanceApiService;

    protected function setUp(): void
    {
        $this->binanceApiService = $this->createMock(BinanceApiService::class);

        $command = new BackfillCryptoRatesCommand($this->binanceApiService);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccessWithDefaultParameters(): void
    {
        $this->binanceApiService
            ->expects($this->once())
            ->method('backfillHistoricalRates')
            ->with(7, null)
            ->willReturn([
                'total_inserted' => 2000,
                'pairs_processed' => ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'],
                'start_date' => '2025-09-15',
                'end_date' => '2025-09-22'
            ]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Backfilling cryptocurrency rates', $output);
    }

    public function testExecuteSuccessWithCustomDays(): void
    {
        $this->binanceApiService
            ->expects($this->once())
            ->method('backfillHistoricalRates')
            ->with(3, null)
            ->willReturn([
                'total_inserted' => 900,
                'pairs_processed' => ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'],
                'start_date' => '2025-09-19',
                'end_date' => '2025-09-22'
            ]);

        $exitCode = $this->commandTester->execute(['days' => '3']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Backfilling', $output);
    }

    public function testExecuteSuccessWithSpecificPair(): void
    {
        $this->binanceApiService
            ->expects($this->once())
            ->method('backfillHistoricalRates')
            ->with(7, 'EUR/BTC')
            ->willReturn([
                'total_inserted' => 700,
                'pairs_processed' => ['EUR/BTC'],
                'start_date' => '2025-09-15',
                'end_date' => '2025-09-22'
            ]);

        $exitCode = $this->commandTester->execute(['--pair' => 'EUR/BTC']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Backfilling', $output);
    }

    public function testExecuteFailureWithInvalidDays(): void
    {
        $exitCode = $this->commandTester->execute(['days' => 'invalid']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('ERROR', $output);
    }

    public function testExecuteFailureWithServiceException(): void
    {
        $this->binanceApiService
            ->expects($this->once())
            ->method('backfillHistoricalRates')
            ->willThrowException(new \RuntimeException('API Error'));

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('ERROR', $output);
    }
}