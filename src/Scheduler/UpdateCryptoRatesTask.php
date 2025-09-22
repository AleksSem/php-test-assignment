<?php

namespace App\Scheduler;

use App\Service\BinanceApiService;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

/**
 * Periodic task: updates crypto rates from Binance every 5 minutes.
 */
#[AsPeriodicTask(
    frequency: 'PT5M',
    method: 'update'
)]
class UpdateCryptoRatesTask
{
    public function __construct(
        private readonly BinanceApiService $binanceApiService
    ) {
    }

    final public function update(): void
    {
        $this->binanceApiService->updateRates();
    }
}
