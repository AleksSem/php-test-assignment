<?php

namespace App\Command;

use App\Service\BinanceApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backfill-crypto-rates',
    description: 'Backfill historical cryptocurrency rates from Binance API',
)]
class BackfillCryptoRatesCommand extends Command
{
    public function __construct(
        private BinanceApiService $binanceApiService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Number of days to backfill', 7)
            ->addOption('pair', 'p', InputOption::VALUE_OPTIONAL, 'Specific pair to backfill (EUR/BTC, EUR/ETH, EUR/LTC)')
            ->addOption('interval', 'i', InputOption::VALUE_OPTIONAL, 'Data interval (5m, 1h, 1d)', '5m')
            ->setHelp('This command allows you to backfill historical cryptocurrency rates from Binance API')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = (int) $input->getArgument('days');
        $pair = $input->getOption('pair');
        $interval = $input->getOption('interval');

        if ($days <= 0 || $days > 365) {
            $io->error('Days must be between 1 and 365');
            return Command::FAILURE;
        }

        $validIntervals = ['1m', '5m', '15m', '30m', '1h', '2h', '4h', '6h', '8h', '12h', '1d'];
        if (!in_array($interval, $validIntervals)) {
            $io->error('Invalid interval. Supported: ' . implode(', ', $validIntervals));
            return Command::FAILURE;
        }

        $io->title('Backfilling cryptocurrency rates from Binance API');
        $io->info("Backfilling {$days} days of data with {$interval} interval");

        try {
            $result = $this->binanceApiService->backfillHistoricalRates($days, $pair, $interval);

            $io->success([
                'Historical rates backfilled successfully!',
                "Total records inserted: {$result['total_inserted']}",
                "Pairs processed: " . implode(', ', $result['pairs_processed']),
                "Date range: {$result['start_date']} to {$result['end_date']}"
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Failed to backfill historical rates: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}