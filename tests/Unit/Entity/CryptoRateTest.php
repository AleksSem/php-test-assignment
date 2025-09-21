<?php

namespace App\Tests\Unit\Entity;

use App\Entity\CryptoRate;
use App\Tests\Fixtures\CryptoRateFixtures;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class CryptoRateTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidCryptoRate(): void
    {
        $timestamp = new \DateTimeImmutable('2025-09-21 12:00:00');
        $cryptoRate = CryptoRateFixtures::createValidCryptoRate('EUR/BTC', '98606.63000000', $timestamp);

        $violations = $this->validator->validate($cryptoRate);

        $this->assertCount(0, $violations);
        $this->assertEquals('EUR/BTC', $cryptoRate->getPair());
        $this->assertEquals('98606.63000000', $cryptoRate->getRate());
        $this->assertEquals($timestamp, $cryptoRate->getTimestamp());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cryptoRate->getCreatedAt());
    }

    public function testEmptyPair(): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair('');
        $cryptoRate->setRate('98606.63000000');
        $cryptoRate->setTimestamp(new \DateTimeImmutable());

        $violations = $this->validator->validate($cryptoRate);

        $this->assertGreaterThan(0, $violations->count());
        $foundNotBlankViolation = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'pair') {
                $foundNotBlankViolation = true;
                break;
            }
        }
        $this->assertTrue($foundNotBlankViolation);
    }

    public function testEmptyRate(): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair('EUR/BTC');
        $cryptoRate->setRate('');
        $cryptoRate->setTimestamp(new \DateTimeImmutable());

        $violations = $this->validator->validate($cryptoRate);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testNegativeRate(): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair('EUR/BTC');
        $cryptoRate->setRate('-100.00000000');
        $cryptoRate->setTimestamp(new \DateTimeImmutable());

        $violations = $this->validator->validate($cryptoRate);

        $this->assertGreaterThan(0, $violations->count());
        $foundPositiveViolation = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'rate' &&
                strpos($violation->getMessage(), 'positive') !== false) {
                $foundPositiveViolation = true;
                break;
            }
        }
        $this->assertTrue($foundPositiveViolation);
    }

    public function testPairTooLong(): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair('VERYLONGPAIRNAME');
        $cryptoRate->setRate('98606.63000000');
        $cryptoRate->setTimestamp(new \DateTimeImmutable());

        $violations = $this->validator->validate($cryptoRate);

        $this->assertGreaterThan(0, $violations->count());
        $foundLengthViolation = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'pair') {
                $foundLengthViolation = true;
                break;
            }
        }
        $this->assertTrue($foundLengthViolation);
    }

    public function testCreatedAtIsSetAutomatically(): void
    {
        $beforeCreation = new \DateTimeImmutable();

        $cryptoRate = new CryptoRate();

        $afterCreation = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($beforeCreation, $cryptoRate->getCreatedAt());
        $this->assertLessThanOrEqual($afterCreation, $cryptoRate->getCreatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $cryptoRate = new CryptoRate();
        $timestamp = new \DateTimeImmutable('2025-09-21 12:00:00');
        $createdAt = new \DateTimeImmutable('2025-09-21 12:01:00');

        $cryptoRate->setPair('EUR/ETH');
        $cryptoRate->setRate('3804.28000000');
        $cryptoRate->setTimestamp($timestamp);
        $cryptoRate->setCreatedAt($createdAt);

        $this->assertEquals('EUR/ETH', $cryptoRate->getPair());
        $this->assertEquals('3804.28000000', $cryptoRate->getRate());
        $this->assertEquals($timestamp, $cryptoRate->getTimestamp());
        $this->assertEquals($createdAt, $cryptoRate->getCreatedAt());
        $this->assertNull($cryptoRate->getId()); // ID is null until persisted
    }

    public function testHighPrecisionRate(): void
    {
        $cryptoRate = CryptoRateFixtures::createHighPrecisionRate();

        $violations = $this->validator->validate($cryptoRate);

        $this->assertCount(0, $violations);
        $this->assertEquals('98606.12345678', $cryptoRate->getRate());
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidationWithFixtures(string $pair, string $rate, bool $expectValid): void
    {
        $cryptoRate = CryptoRateFixtures::createValidCryptoRate($pair, $rate);
        $violations = $this->validator->validate($cryptoRate);

        if ($expectValid) {
            $this->assertCount(0, $violations, "Expected {$pair} with rate {$rate} to be valid");
        } else {
            $this->assertGreaterThan(0, $violations->count(), "Expected {$pair} with rate {$rate} to be invalid");
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

    public function testAllSupportedPairs(): void
    {
        foreach (CryptoRateFixtures::getSupportedPairs() as $pair) {
            $cryptoRate = CryptoRateFixtures::createValidCryptoRate($pair, '100.00000000');
            $violations = $this->validator->validate($cryptoRate);

            $this->assertCount(0, $violations, "Pair {$pair} should be valid");
        }
    }
}