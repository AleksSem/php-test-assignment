<?php

namespace App\Tests\Unit\Command;

use App\Command\UpdateCryptoRatesCommand;
use App\Service\BinanceApiService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCryptoRatesCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject $binanceApiService;

    protected function setUp(): void
    {
        $this->binanceApiService = $this->createMock(BinanceApiService::class);

        $command = new UpdateCryptoRatesCommand($this->binanceApiService);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccess(): void
    {
        $this->binanceApiService
            ->expects($this->once())
            ->method('updateRates');

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Updating cryptocurrency rates', $output);
    }

    public function testExecuteFailureWithServiceException(): void
    {
        $this->binanceApiService
            ->expects($this->once())
            ->method('updateRates')
            ->willThrowException(new \RuntimeException('API Error'));

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('ERROR', $output);
    }
}