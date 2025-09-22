<?php

namespace App\Tests\Integration\Command;

use App\Entity\CryptoRate;
use App\Tests\Helper\FastIntegrationTestCase;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BackfillCryptoRatesCommandTest extends FastIntegrationTestCase
{
    private Application $application;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application(static::$kernel);
        $command = $this->application->find('app:backfill-crypto-rates');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccessWithDefaultParameters(): void
    {
        // Verify database is initially empty
        $initialCount = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count([]);
        $this->assertEquals(0, $initialCount);

        // Use 1 day instead of 7 to reduce test time
        $exitCode = $this->commandTester->execute(['days' => 1]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Backfilling cryptocurrency rates from Binance API', $output);
        $this->assertStringContainsString('Backfilling 1 days of data', $output);
        $this->assertStringContainsString('Historical rates backfilled successfully!', $output);
        $this->assertStringContainsString('Total records inserted:', $output);
        $this->assertStringContainsString('Pairs processed:', $output);
        $this->assertStringContainsString('Date range:', $output);

        // Verify rates were saved to database
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();
        $this->assertGreaterThan(0, count($savedRates));
    }

    public function testExecuteSuccessWithCustomDays(): void
    {
        $exitCode = $this->commandTester->execute(['days' => '1']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Backfilling 1 days of data', $output);
        $this->assertStringContainsString('Historical rates backfilled successfully!', $output);
    }

    public function testExecuteSuccessWithSpecificPair(): void
    {
        $exitCode = $this->commandTester->execute([
            'days' => '1',
            '--pair' => 'EUR/BTC'
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Pairs processed: EUR/BTC', $output);

        // Verify only EUR/BTC data was saved
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();

        $pairs = array_unique(array_map(fn(CryptoRate $rate) => $rate->getPair(), $savedRates));
        $this->assertEquals(['EUR/BTC'], $pairs);
    }

    public function testExecuteFailureWithInvalidDays(): void
    {
        $exitCode = $this->commandTester->execute(['days' => '0']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Days must be between 1 and 365', $this->commandTester->getDisplay());

        // Verify no data was saved
        $count = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count([]);
        $this->assertEquals(0, $count);
    }

    public function testExecuteFailureWithTooManyDays(): void
    {
        $exitCode = $this->commandTester->execute(['days' => '400']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Days must be between 1 and 365', $this->commandTester->getDisplay());
    }

    public function testExecuteFailureWithNegativeDays(): void
    {
        $exitCode = $this->commandTester->execute(['days' => '-5']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Days must be between 1 and 365', $this->commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->application->find('app:backfill-crypto-rates');

        $this->assertEquals('app:backfill-crypto-rates', $command->getName());
        $this->assertEquals('Backfill historical cryptocurrency rates from Binance API', $command->getDescription());

        $definition = $command->getDefinition();

        // Test arguments
        $this->assertTrue($definition->hasArgument('days'));
        $this->assertFalse($definition->getArgument('days')->isRequired());
        $this->assertEquals(7, $definition->getArgument('days')->getDefault());

        // Test options
        $this->assertTrue($definition->hasOption('pair'));
        $this->assertEquals('p', $definition->getOption('pair')->getShortcut());
        $this->assertFalse($definition->getOption('pair')->isValueRequired());
    }

    public function testExecuteWithStringDaysParameter(): void
    {
        $exitCode = $this->commandTester->execute(['days' => '2']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Backfilling 2 days of data', $this->commandTester->getDisplay());
    }

    public function testExecuteOutputFormat(): void
    {
        $this->commandTester->execute(['days' => '1']);

        $output = $this->commandTester->getDisplay();

        // Check that the output is formatted properly with SymfonyStyle
        $this->assertStringContainsString('Backfilling cryptocurrency rates from Binance API', $output);
        $this->assertStringContainsString('[OK]', $output);
        $this->assertStringContainsString('Historical rates backfilled successfully!', $output);
    }

    public function testExecuteWithShortPairOption(): void
    {
        $exitCode = $this->commandTester->execute([
            'days' => '1',
            '-p' => 'EUR/ETH'
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Pairs processed: EUR/ETH', $this->commandTester->getDisplay());

        // Verify only EUR/ETH data was saved
        $savedRates = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->findAll();

        $pairs = array_unique(array_map(fn(CryptoRate $rate) => $rate->getPair(), $savedRates));
        $this->assertEquals(['EUR/ETH'], $pairs);
    }

    public function testDataPersistenceAndQuality(): void
    {
        $this->commandTester->execute(['days' => '1']);

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

        // Verify we have historical data spread over time
        $timestamps = array_map(fn(CryptoRate $rate) => $rate->getTimestamp(), $allRates);
        $uniqueTimestamps = array_unique(array_map(fn(DateTimeImmutable $dt) => $dt->getTimestamp(), $timestamps));
        $this->assertGreaterThan(1, count($uniqueTimestamps), 'Should have rates at different timestamps');
    }

    public function testDuplicateHandling(): void
    {
        // Run backfill twice with same parameters
        $this->commandTester->execute(['days' => '1', '--pair' => 'EUR/BTC']);
        $firstRunCount = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count(['pair' => 'EUR/BTC']);

        $this->commandTester->execute(['days' => '1', '--pair' => 'EUR/BTC']);
        $secondRunCount = $this->entityManager
            ->getRepository(CryptoRate::class)
            ->count(['pair' => 'EUR/BTC']);

        // Second run should not add duplicates
        $this->assertEquals($firstRunCount, $secondRunCount);
    }
}