<?php

namespace App\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected FixtureLoader $fixtureLoader;
    private EntityManagerInterface $entityManager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Ensure clean state before class
        if (isset(static::$kernel)) {
            static::$kernel->shutdown();
            static::$kernel = null;
        }
    }

    protected function setUp(): void
    {
        $this->client = static::createClient(['environment' => 'test']);
        $this->setupTestEnvironment();
    }

    private function setupTestEnvironment(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        DatabaseTestHelper::setupTestDatabase(self::$kernel);
        DatabaseTestHelper::resetDatabase($this->entityManager);

        $this->fixtureLoader = new FixtureLoader($this->entityManager);
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->clear();
        }

        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        // Ensure clean state after class
        if (isset(static::$kernel)) {
            static::$kernel->shutdown();
            static::$kernel = null;
        }
        parent::tearDownAfterClass();
    }

    protected function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM crypto_rate');
    }

    protected function loadTestFixtures(): void
    {
        $this->fixtureLoader->loadMinimalFixtures();
    }

    protected function loadFullTestFixtures(): void
    {
        $this->fixtureLoader->loadAllFixtures();
    }

    protected function loadMinimalFixtures(): array
    {
        return $this->fixtureLoader->loadMinimalFixtures();
    }

    protected function loadBasicFixtures(): array
    {
        return $this->fixtureLoader->loadBasicCryptoRates();
    }

    protected function loadLast24HoursFixtures(string $pair = 'EUR/BTC'): array
    {
        return $this->fixtureLoader->loadLast24HoursRates($pair);
    }

    protected function loadAllFixtures(): array
    {
        return $this->fixtureLoader->loadAllFixtures();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function assertSuccessfulApiResponse(): array
    {
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    protected function assertValidationError(string $expectedError = 'Validation failed'): void
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals($expectedError, $data['error']);
    }

    protected function assertChartStructure(array $data): void
    {
        $this->assertArrayHasKey('chart', $data);
        $this->assertArrayHasKey('labels', $data['chart']);
        $this->assertArrayHasKey('datasets', $data['chart']);
        $this->assertIsArray($data['chart']['labels']);
        $this->assertIsArray($data['chart']['datasets']);
    }

    protected function assertDatasetStructure(array $dataset): void
    {
        $this->assertArrayHasKey('label', $dataset);
        $this->assertArrayHasKey('data', $dataset);
        $this->assertArrayHasKey('borderColor', $dataset);
        $this->assertEquals('Exchange Rate', $dataset['label']);
    }

    protected function assertRatePrecision(string $rate): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d{8}$/', $rate);
    }

    protected function assert24HourTimeFormat(string $timestamp): void
    {
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]{2}-\d{2} \d{2}:\d{2}$/', $timestamp);
    }

    protected function assertDayTimeFormat(string $timestamp): void
    {
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $timestamp);
    }
}