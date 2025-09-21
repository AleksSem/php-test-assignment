<?php

namespace App\Service;

interface BinanceApiClientInterface
{
    public function fetchCurrentPrice(string $symbol): string;

    public function fetchKlines(
        string $symbol,
        string $interval,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime
    ): array;
}