<?php

namespace App\Tests\Unit\DTO;

use App\DTO\CryptoRatesRequest;
use App\DTO\Last24HoursRequest;
use App\Tests\Helper\TestConstants;
use App\Tests\Helper\ValidatorTestCase;

class CryptoRatesRequestTest extends ValidatorTestCase
{

    public function testValidCryptoRatesRequest(): void
    {
        $request = new CryptoRatesRequest('EUR/BTC', TestConstants::TEST_DATES['DEFAULT_DATE']);

        $violations = $this->validator->validate($request);

        $this->assertNoViolations($violations);
        $this->assertEquals('EUR/BTC', $request->getPair());
        $this->assertEquals(TestConstants::TEST_DATES['DEFAULT_DATE'], $request->getDate());
    }

    public function testInvalidPair(): void
    {
        $request = new CryptoRatesRequest('INVALID/PAIR', TestConstants::TEST_DATES['DEFAULT_DATE']);

        $violations = $this->validator->validate($request);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'pair', 'Unsupported pair');
    }

    public function testEmptyPair(): void
    {
        $request = new CryptoRatesRequest('', TestConstants::TEST_DATES['DEFAULT_DATE']);

        $violations = $this->validator->validate($request);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'pair', 'required');
    }

    public function testInvalidDate(): void
    {
        $request = new CryptoRatesRequest('EUR/BTC', 'invalid-date');

        $violations = $this->validator->validate($request);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'date', 'Invalid date format');
    }

    public function testEmptyDate(): void
    {
        $request = new CryptoRatesRequest('EUR/BTC', '');

        $violations = $this->validator->validate($request);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'date', 'required');
    }

    public function testNullDate(): void
    {
        $request = new CryptoRatesRequest('EUR/BTC', null);

        $violations = $this->validator->validate($request);

        $this->assertHasViolations($violations);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportedPairsProvider')]
    public function testSupportedPairs(string $pair): void
    {
        $request = new CryptoRatesRequest($pair, TestConstants::TEST_DATES['DEFAULT_DATE']);
        $violations = $this->validator->validate($request);

        $this->assertNoViolations($violations, "Pair {$pair} should be valid");
    }

    public static function supportedPairsProvider(): array
    {
        $supportedPairs = array_keys(array_slice(TestConstants::DEFAULT_SUPPORTED_PAIRS, 0, 3, true));
        return array_map(fn($pair) => [$pair], $supportedPairs);
    }

    public function testValidLast24HoursRequest(): void
    {
        $request = new Last24HoursRequest('EUR/BTC');

        $violations = $this->validator->validate($request);

        $this->assertNoViolations($violations);
        $this->assertEquals('EUR/BTC', $request->getPair());
    }

    public function testInvalidLast24HoursRequest(): void
    {
        $request = new Last24HoursRequest('INVALID');

        $violations = $this->validator->validate($request);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'pair', 'Unsupported pair');
    }

    public function testEmptyLast24HoursRequest(): void
    {
        $request = new Last24HoursRequest('');

        $violations = $this->validator->validate($request);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'pair', 'required');
    }
}