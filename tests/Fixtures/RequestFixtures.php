<?php

namespace App\Tests\Fixtures;

use App\DTO\CryptoRatesRequest;
use App\DTO\Last24HoursRequest;

class RequestFixtures
{
    public static function createValidCryptoRatesRequest(
        string $pair = 'EUR/BTC',
        string $date = '2025-09-21'
    ): CryptoRatesRequest {
        return new CryptoRatesRequest($pair, $date);
    }

    public static function createValidLast24HoursRequest(
        string $pair = 'EUR/BTC'
    ): Last24HoursRequest {
        return new Last24HoursRequest($pair);
    }

    public static function createInvalidPairRequest(): CryptoRatesRequest
    {
        return new CryptoRatesRequest('INVALID/PAIR', '2025-09-21');
    }

    public static function createEmptyPairRequest(): CryptoRatesRequest
    {
        return new CryptoRatesRequest('', '2025-09-21');
    }

    public static function createInvalidDateRequest(): CryptoRatesRequest
    {
        return new CryptoRatesRequest('EUR/BTC', 'invalid-date');
    }

    public static function createEmptyDateRequest(): CryptoRatesRequest
    {
        return new CryptoRatesRequest('EUR/BTC', '');
    }

    public static function createNullDateRequest(): CryptoRatesRequest
    {
        return new CryptoRatesRequest('EUR/BTC', null);
    }

    public static function getAllSupportedPairRequests(): array
    {
        $requests = [];
        foreach (CryptoRateFixtures::getSupportedPairs() as $pair) {
            $requests[$pair] = self::createValidCryptoRatesRequest($pair, '2025-09-21');
        }
        return $requests;
    }

    public static function getAllSupportedPairLast24HRequests(): array
    {
        $requests = [];
        foreach (CryptoRateFixtures::getSupportedPairs() as $pair) {
            $requests[$pair] = self::createValidLast24HoursRequest($pair);
        }
        return $requests;
    }

    public static function getValidationTestCases(): array
    {
        return [
            'valid_btc_request' => [
                'request' => self::createValidCryptoRatesRequest('EUR/BTC', '2025-09-21'),
                'expectValid' => true
            ],
            'valid_eth_request' => [
                'request' => self::createValidCryptoRatesRequest('EUR/ETH', '2025-09-21'),
                'expectValid' => true
            ],
            'valid_ltc_request' => [
                'request' => self::createValidCryptoRatesRequest('EUR/LTC', '2025-09-21'),
                'expectValid' => true
            ],
            'invalid_pair' => [
                'request' => self::createInvalidPairRequest(),
                'expectValid' => false
            ],
            'empty_pair' => [
                'request' => self::createEmptyPairRequest(),
                'expectValid' => false
            ],
            'invalid_date' => [
                'request' => self::createInvalidDateRequest(),
                'expectValid' => false
            ],
            'empty_date' => [
                'request' => self::createEmptyDateRequest(),
                'expectValid' => false
            ],
            'null_date' => [
                'request' => self::createNullDateRequest(),
                'expectValid' => false
            ]
        ];
    }

    public static function getLast24HoursValidationTestCases(): array
    {
        return [
            'valid_btc' => [
                'request' => self::createValidLast24HoursRequest('EUR/BTC'),
                'expectValid' => true
            ],
            'valid_eth' => [
                'request' => self::createValidLast24HoursRequest('EUR/ETH'),
                'expectValid' => true
            ],
            'valid_ltc' => [
                'request' => self::createValidLast24HoursRequest('EUR/LTC'),
                'expectValid' => true
            ],
            'invalid_pair' => [
                'request' => new Last24HoursRequest('INVALID'),
                'expectValid' => false
            ],
            'empty_pair' => [
                'request' => new Last24HoursRequest(''),
                'expectValid' => false
            ]
        ];
    }

    public static function getApiTestUrls(): array
    {
        return [
            'last_24h_btc' => '/api/rates/last-24h?pair=EUR/BTC',
            'last_24h_eth' => '/api/rates/last-24h?pair=EUR/ETH',
            'last_24h_ltc' => '/api/rates/last-24h?pair=EUR/LTC',
            'day_btc' => '/api/rates/day?pair=EUR/BTC&date=2025-09-21',
            'day_eth' => '/api/rates/day?pair=EUR/ETH&date=2025-09-21',
            'day_ltc' => '/api/rates/day?pair=EUR/LTC&date=2025-09-21',
            'invalid_pair' => '/api/rates/last-24h?pair=INVALID',
            'missing_pair' => '/api/rates/last-24h',
            'invalid_date' => '/api/rates/day?pair=EUR/BTC&date=invalid',
            'missing_date' => '/api/rates/day?pair=EUR/BTC'
        ];
    }
}