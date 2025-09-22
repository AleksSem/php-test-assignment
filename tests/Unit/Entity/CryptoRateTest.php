<?php

namespace App\Tests\Unit\Entity;

use App\Entity\CryptoRate;
use App\Tests\Fixtures\CryptoRateFixtures;
use App\Tests\Helper\TestConstants;
use App\Tests\Helper\ValidatorTestCase;
use DateTimeImmutable;

class CryptoRateTest extends ValidatorTestCase
{

    public function testValidCryptoRate(): void
    {
        $timestamp = new DateTimeImmutable(TestConstants::TEST_DATES['DEFAULT_DATETIME']);
        $cryptoRate = CryptoRateFixtures::createValidCryptoRate('EUR/BTC', TestConstants::DEFAULT_RATES['BTCEUR'], $timestamp);

        $violations = $this->validator->validate($cryptoRate);

        $this->assertNoViolations($violations);
        $this->assertEquals('EUR/BTC', $cryptoRate->getPair());
        $this->assertEquals(TestConstants::DEFAULT_RATES['BTCEUR'], $cryptoRate->getRate());
        $this->assertEquals($timestamp, $cryptoRate->getTimestamp());
        $this->assertInstanceOf(DateTimeImmutable::class, $cryptoRate->getCreatedAt());
    }

    public function testEmptyPair(): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair('');
        $cryptoRate->setRate(TestConstants::DEFAULT_RATES['BTCEUR']);
        $cryptoRate->setTimestamp(new DateTimeImmutable());

        $violations = $this->validator->validate($cryptoRate);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'pair');
    }

    public function testEmptyRate(): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair('EUR/BTC');
        $cryptoRate->setRate('');
        $cryptoRate->setTimestamp(new DateTimeImmutable());

        $violations = $this->validator->validate($cryptoRate);

        $this->assertHasViolations($violations);
    }

    public function testNegativeRate(): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair('EUR/BTC');
        $cryptoRate->setRate('-100.00000000');
        $cryptoRate->setTimestamp(new DateTimeImmutable());

        $violations = $this->validator->validate($cryptoRate);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'rate', 'positive');
    }

    public function testPairTooLong(): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair('VERYLONGPAIRNAME');
        $cryptoRate->setRate(TestConstants::DEFAULT_RATES['BTCEUR']);
        $cryptoRate->setTimestamp(new DateTimeImmutable());

        $violations = $this->validator->validate($cryptoRate);

        $this->assertHasViolations($violations);
        $this->assertViolationForProperty($violations, 'pair');
    }

    public function testCreatedAtIsSetAutomatically(): void
    {
        $beforeCreation = new DateTimeImmutable();
        $cryptoRate = new CryptoRate();
        $afterCreation = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($beforeCreation, $cryptoRate->getCreatedAt());
        $this->assertLessThanOrEqual($afterCreation, $cryptoRate->getCreatedAt());
    }

    public function testHighPrecisionRate(): void
    {
        $cryptoRate = CryptoRateFixtures::createHighPrecisionRate();

        $violations = $this->validator->validate($cryptoRate);

        $this->assertNoViolations($violations);
        $this->assertEquals('98606.12345678', $cryptoRate->getRate());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validationDataProvider')]
    public function testValidationWithFixtures(string $pair, string $rate, bool $expectValid): void
    {
        $cryptoRate = CryptoRateFixtures::createValidCryptoRate($pair, $rate);
        $violations = $this->validator->validate($cryptoRate);

        if ($expectValid) {
            $this->assertNoViolations($violations, "Expected {$pair} with rate {$rate} to be valid");
        } else {
            $this->assertHasViolations($violations, "Expected {$pair} with rate {$rate} to be invalid");
        }
    }

    public static function validationDataProvider(): array
    {
        $testCases = [];
        foreach (CryptoRateFixtures::getValidationTestCases() as $name => $case) {
            $testCases[$name] = [$case['pair'], $case['rate'], $case['expectValid']];
        }
        return $testCases;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportedPairsProvider')]
    public function testSupportedPairValidation(string $pair): void
    {
        $cryptoRate = CryptoRateFixtures::createValidCryptoRate($pair, '100.00000000');
        $violations = $this->validator->validate($cryptoRate);

        $this->assertNoViolations($violations, "Pair {$pair} should be valid");
    }

    public static function supportedPairsProvider(): array
    {
        return array_map(fn($pair) => [$pair], CryptoRateFixtures::getSupportedPairs());
    }
}