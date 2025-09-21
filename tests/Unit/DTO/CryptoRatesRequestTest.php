<?php

namespace App\Tests\Unit\DTO;

use App\DTO\CryptoRatesRequest;
use App\DTO\Last24HoursRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class CryptoRatesRequestTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidCryptoRatesRequest(): void
    {
        $request = new CryptoRatesRequest('EUR/BTC', '2025-09-21');

        $violations = $this->validator->validate($request);

        $this->assertCount(0, $violations);
        $this->assertEquals('EUR/BTC', $request->getPair());
        $this->assertEquals('2025-09-21', $request->getDate());
    }

    public function testInvalidPair(): void
    {
        $request = new CryptoRatesRequest('INVALID/PAIR', '2025-09-21');

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('Unsupported pair', $violations[0]->getMessage());
    }

    public function testEmptyPair(): void
    {
        $request = new CryptoRatesRequest('', '2025-09-21');

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('required', $violations[0]->getMessage());
    }

    public function testInvalidDate(): void
    {
        $request = new CryptoRatesRequest('EUR/BTC', 'invalid-date');

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('Invalid date format', $violations[0]->getMessage());
    }

    public function testEmptyDate(): void
    {
        $request = new CryptoRatesRequest('EUR/BTC', '');

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('required', $violations[0]->getMessage());
    }

    public function testNullDate(): void
    {
        $request = new CryptoRatesRequest('EUR/BTC', null);

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testAllSupportedPairs(): void
    {
        $supportedPairs = ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'];

        foreach ($supportedPairs as $pair) {
            $request = new CryptoRatesRequest($pair, '2025-09-21');
            $violations = $this->validator->validate($request);

            $this->assertCount(0, $violations, "Pair {$pair} should be valid");
        }
    }

    public function testValidLast24HoursRequest(): void
    {
        $request = new Last24HoursRequest('EUR/BTC');

        $violations = $this->validator->validate($request);

        $this->assertCount(0, $violations);
        $this->assertEquals('EUR/BTC', $request->getPair());
    }

    public function testInvalidLast24HoursRequest(): void
    {
        $request = new Last24HoursRequest('INVALID');

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('Unsupported pair', $violations[0]->getMessage());
    }

    public function testEmptyLast24HoursRequest(): void
    {
        $request = new Last24HoursRequest('');

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('required', $violations[0]->getMessage());
    }
}