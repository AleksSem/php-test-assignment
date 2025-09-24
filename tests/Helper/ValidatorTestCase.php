<?php

namespace App\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validation;

abstract class ValidatorTestCase extends TestCase
{
    protected ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    protected function assertNoViolations($violations, string $message = ''): void
    {
        $this->assertCount(0, $violations, $message);
    }

    protected function assertHasViolations($violations, string $message = ''): void
    {
        $this->assertGreaterThan(0, $violations->count(), $message);
    }

    protected function assertViolationForProperty($violations, string $property, ?string $expectedMessagePart = null): void
    {
        $found = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === $property) {
                $found = true;
                if ($expectedMessagePart) {
                    $this->assertStringContainsString($expectedMessagePart, $violation->getMessage());
                }
                break;
            }
        }
        $this->assertTrue($found, "No violation found for property '{$property}'");
    }
}