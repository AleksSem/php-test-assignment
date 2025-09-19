<?php

namespace App\Scheduler;

use App\Service\BinanceApiService;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;
use Symfony\Component\Scheduler\Attribute\AsTask;

#[AsTask]
#[AsPeriodicTask(frequency: '5 minutes')]
class UpdateCryptoRatesTask
{
    public function __construct(
        private BinanceApiService $binanceApiService
    ) {
    }

    public function __invoke(): void
    {
        $this->binanceApiService->updateRates();
    }
}
