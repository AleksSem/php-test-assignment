<?php

namespace App\Tests\Fixtures;

use App\Entity\CryptoRate;

class CryptoRateFixtures
{
    public static function createValidCryptoRate(
        string $pair = 'EUR/BTC',
        string $rate = '98606.63000000',
        ?\DateTimeImmutable $timestamp = null
    ): CryptoRate {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair($pair);
        $cryptoRate->setRate($rate);
        $cryptoRate->setTimestamp($timestamp ?? new \DateTimeImmutable());

        return $cryptoRate;
    }

    public static function createBtcRate(
        string $rate = '98606.63000000',
        ?\DateTimeImmutable $timestamp = null
    ): CryptoRate {
        return self::createValidCryptoRate('EUR/BTC', $rate, $timestamp);
    }

    public static function createEthRate(
        string $rate = '3804.28000000',
        ?\DateTimeImmutable $timestamp = null
    ): CryptoRate {
        return self::createValidCryptoRate('EUR/ETH', $rate, $timestamp);
    }

    public static function createLtcRate(
        string $rate = '97.74000000',
        ?\DateTimeImmutable $timestamp = null
    ): CryptoRate {
        return self::createValidCryptoRate('EUR/LTC', $rate, $timestamp);
    }

    public static function createHighPrecisionRate(
        string $pair = 'EUR/BTC',
        string $rate = '98606.12345678'
    ): CryptoRate {
        return self::createValidCryptoRate($pair, $rate);
    }

    public static function createRateWithZeroDecimals(
        string $pair = 'EUR/BTC',
        string $rate = '100000.00000000'
    ): CryptoRate {
        return self::createValidCryptoRate($pair, $rate);
    }

    public static function createLast24HoursRates(string $pair = 'EUR/BTC'): array
    {
        $rates = [];
        $baseTimestamp = new \DateTimeImmutable('-23 hours');

        // Create rates every hour for last 24 hours
        for ($i = 0; $i < 24; $i++) {
            $timestamp = $baseTimestamp->modify("+{$i} hours");
            $rateValue = match ($pair) {
                'EUR/BTC' => sprintf('%.8f', 98000 + ($i * 50) + rand(1, 100)),
                'EUR/ETH' => sprintf('%.8f', 3800 + ($i * 2) + rand(1, 10)),
                'EUR/LTC' => sprintf('%.8f', 97 + ($i * 0.1) + rand(1, 5)),
                default => '1.00000000'
            };

            $rates[] = self::createValidCryptoRate($pair, $rateValue, $timestamp);
        }

        return $rates;
    }

    public static function createDayRates(
        string $pair = 'EUR/BTC',
        \DateTimeImmutable $date = null
    ): array {
        $date = $date ?? new \DateTimeImmutable('today');
        $rates = [];

        // Create rates every 5 minutes for a day (288 rates)
        for ($i = 0; $i < 288; $i++) {
            $timestamp = $date->modify("+{$i} minutes");
            $rateValue = match ($pair) {
                'EUR/BTC' => sprintf('%.8f', 98000 + ($i * 0.5) + rand(1, 10)),
                'EUR/ETH' => sprintf('%.8f', 3800 + ($i * 0.02) + rand(1, 2)),
                'EUR/LTC' => sprintf('%.8f', 97 + ($i * 0.001) + rand(1, 1)),
                default => '1.00000000'
            };

            $rates[] = self::createValidCryptoRate($pair, $rateValue, $timestamp);
        }

        return $rates;
    }

    public static function createMultiPairRates(\DateTimeImmutable $timestamp = null): array
    {
        $timestamp = $timestamp ?? new \DateTimeImmutable();

        return [
            self::createBtcRate('98606.63000000', $timestamp),
            self::createEthRate('3804.28000000', $timestamp),
            self::createLtcRate('97.74000000', $timestamp),
        ];
    }

    public static function createTimestampSequence(
        string $pair = 'EUR/BTC',
        int $count = 5,
        string $interval = '+5 minutes'
    ): array {
        $rates = [];
        $timestamp = new \DateTimeImmutable('-1 hour');

        for ($i = 0; $i < $count; $i++) {
            $timestamp = $timestamp->modify($interval);
            $rateValue = sprintf('%.8f', 98000 + ($i * 10));
            $rates[] = self::createValidCryptoRate($pair, $rateValue, $timestamp);
        }

        return $rates;
    }

    public static function createChartDataSample(): array
    {
        return [
            self::createBtcRate('98639.69000000', new \DateTimeImmutable('2025-09-21 12:22:50')),
            self::createBtcRate('98654.08000000', new \DateTimeImmutable('2025-09-21 12:37:51')),
            self::createBtcRate('98654.10000000', new \DateTimeImmutable('2025-09-21 12:38:54')),
            self::createBtcRate('98645.36000000', new \DateTimeImmutable('2025-09-21 12:42:50')),
            self::createBtcRate('98621.69000000', new \DateTimeImmutable('2025-09-21 12:47:50')),
        ];
    }

    public static function getValidationTestCases(): array
    {
        return [
            'valid_btc' => [
                'pair' => 'EUR/BTC',
                'rate' => '98606.63000000',
                'expectValid' => true
            ],
            'valid_eth' => [
                'pair' => 'EUR/ETH',
                'rate' => '3804.28000000',
                'expectValid' => true
            ],
            'valid_ltc' => [
                'pair' => 'EUR/LTC',
                'rate' => '97.74000000',
                'expectValid' => true
            ],
            'empty_pair' => [
                'pair' => '',
                'rate' => '98606.63000000',
                'expectValid' => false
            ],
            'empty_rate' => [
                'pair' => 'EUR/BTC',
                'rate' => '',
                'expectValid' => false
            ],
            'negative_rate' => [
                'pair' => 'EUR/BTC',
                'rate' => '-100.00000000',
                'expectValid' => false
            ],
            'long_pair' => [
                'pair' => 'VERYLONGPAIRNAME',
                'rate' => '98606.63000000',
                'expectValid' => false
            ],
            'high_precision' => [
                'pair' => 'EUR/BTC',
                'rate' => '98606.12345678',
                'expectValid' => true
            ]
        ];
    }

    public static function getSupportedPairs(): array
    {
        return ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'];
    }

    public static function getUnsupportedPairs(): array
    {
        return ['USD/BTC', 'INVALID', 'EUR/DOGE', ''];
    }
}