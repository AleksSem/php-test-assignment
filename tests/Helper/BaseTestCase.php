<?php

namespace App\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class BaseTestCase extends KernelTestCase
{
    use DatabaseTrait, KernelTestCaseTrait;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel(['environment' => 'test']);

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->setupDatabase($this->entityManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}