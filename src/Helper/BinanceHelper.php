<?php

namespace App\Helper;

use DateTimeImmutable;

class BinanceHelper
{
    /**
     * Converts Binance timestamp (milliseconds) to DateTimeImmutable
     *
     * @param int|float $binanceTimestamp Timestamp in milliseconds from Binance API
     * @return DateTimeImmutable
     * @throws \Exception If timestamp is invalid
     */
    public static function createDateTimeFromBinanceTimestamp(int|float $binanceTimestamp): DateTimeImmutable
    {
        $timestampSeconds = (int)($binanceTimestamp / 1000);
        return new DateTimeImmutable('@' . $timestampSeconds);
    }
}
