<?php

namespace App\Command;

use App\Service\BinanceApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-crypto-rates',
    description: 'Update cryptocurrency rates from Binance API',
)]
class UpdateCryptoRatesCommand extends Command
{
    public function __construct(
        private BinanceApiService $binanceApiService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Updating cryptocurrency rates from Binance API');

        try {
            $this->binanceApiService->updateRates();
            $io->success('Cryptocurrency rates updated successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to update cryptocurrency rates: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
