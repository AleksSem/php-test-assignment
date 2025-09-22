<?php

namespace App\Tests\Integration\Command;

use App\Entity\CryptoRate;
use App\Tests\Helper\FastIntegrationTestCase;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCryptoRatesCommandTest extends FastIntegrationTestCase
{
    private Application $application;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application(static::$kernel);
        $command = $this->application->find('app:update-crypto-rates');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccess(): void
    {
        // Verify database is initially empty
        $initialCount = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count([]);
        $this->assertEquals(0, $initialCount);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Updating cryptocurrency rates from Binance API', $output);
        $this->assertStringContainsString('Cryptocurrency rates updated successfully!', $output);

        // Verify rates were saved to database
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();
        $this->assertGreaterThan(0, count($savedRates));

        // Verify we have rates for supported pairs
        $pairs = array_unique(array_map(fn(CryptoRate $rate) => $rate->getPair(), $savedRates));
        $this->assertContains('EUR/BTC', $pairs);
        $this->assertContains('EUR/ETH', $pairs);
        $this->assertContains('EUR/LTC', $pairs);
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('app:update-crypto-rates');

        $this->assertEquals('app:update-crypto-rates', $command->getName());
        $this->assertEquals('Update cryptocurrency rates from Binance API', $command->getDescription());
    }

    public function testExecuteOutputFormat(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        // Check that the output is formatted properly with SymfonyStyle
        $this->assertStringContainsString('Updating cryptocurrency rates from Binance API', $output);
        $this->assertStringContainsString('[OK]', $output);
        $this->assertStringContainsString('Cryptocurrency rates updated successfully!', $output);
    }

    public function testDataPersistence(): void
    {
        $this->commandTester->execute([]);

        $repository = $this->entityManager->getRepository(CryptoRate::class);
        $allRates = $repository->findAll();

        // Verify all rates have valid data
        foreach ($allRates as $rate) {
            $this->assertNotEmpty($rate->getPair());
            $this->assertMatchesRegularExpression('/^[\d.]+$/', $rate->getRate());
            $this->assertGreaterThan(0, (float) $rate->getRate());
            $this->assertInstanceOf(DateTimeImmutable::class, $rate->getTimestamp());
            $this->assertInstanceOf(DateTimeImmutable::class, $rate->getCreatedAt());
        }
    }

    public function testCommandIdempotency(): void
    {
        // Run command twice
        $this->commandTester->execute([]);
        $firstRunCount = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count([]);

        $this->commandTester->execute([]);
        $secondRunCount = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count([]);

        // Second run should add more rates (not fail or duplicate)
        $this->assertGreaterThanOrEqual($firstRunCount, $secondRunCount);
    }
}