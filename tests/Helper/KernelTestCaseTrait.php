<?php

namespace App\Tests\Helper;

trait KernelTestCaseTrait
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        self::ensureKernelShutdown();
        parent::tearDownAfterClass();
    }
}