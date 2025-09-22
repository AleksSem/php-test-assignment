<?php

namespace App\Tests\Helper;

final class TestConstants
{
    public const DEFAULT_SUPPORTED_PAIRS = [
        'EUR/BTC' => 'BTCEUR',
        'EUR/ETH' => 'ETHEUR',
        'EUR/LTC' => 'LTCEUR',
        'USD/BTC' => 'BTCUSD',
        'USD/ETH' => 'ETHUSD',
        'USD/LTC' => 'LTCUSD',
        'GBP/BTC' => 'BTCGBP',
        'GBP/ETH' => 'ETHGBP',
        'GBP/LTC' => 'LTCGBP',
    ];

    public const DEFAULT_RATES = [
        'BTCEUR' => '98606.63000000',
        'ETHEUR' => '3804.28000000',
        'LTCEUR' => '97.74000000',
        'BTCUSD' => '107000.50000000',
        'ETHUSD' => '4150.75000000',
        'LTCUSD' => '105.25000000',
        'BTCGBP' => '85420.30000000',
        'ETHGBP' => '3290.15000000',
        'LTCGBP' => '84.60000000',
    ];

    public const BINANCE_API = [
        'KLINES_URL' => 'https://api.binance.com/api/v3/klines',
        'PRICE_URL' => 'https://api.binance.com/api/v3/ticker/price',
        'TIMEOUT' => 30,
        'LIMIT' => 1000,
    ];

    public const TEST_DATES = [
        'DEFAULT_DATE' => '2025-09-21',
        'DEFAULT_DATETIME' => '2025-09-21 00:00:00',
    ];

    public const INTERVALS = [
        'DEFAULT' => '5m',
        'HOURLY' => '1h',
        'DAILY' => '1d',
    ];
}