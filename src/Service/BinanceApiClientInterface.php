<?php

namespace App\Service;

use DateTimeImmutable;

interface BinanceApiClientInterface
{
    public function fetchKlines(
        string $symbol,
        string $interval,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime
    ): array;
}
