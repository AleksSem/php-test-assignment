# Test Fixtures Guide

This directory contains comprehensive test fixtures for the Crypto Rates API.

## Available Fixtures

### 🏗️ CryptoRateFixtures

Creates `CryptoRate` entities with realistic data:

```php
use App\Tests\Fixtures\CryptoRateFixtures;

// Basic usage
$btc = CryptoRateFixtures::createBtcRate('98606.63000000');
$eth = CryptoRateFixtures::createEthRate('3804.28000000');
$ltc = CryptoRateFixtures::createLtcRate('97.74000000');

// Custom rate with timestamp
$rate = CryptoRateFixtures::createValidCryptoRate(
    'EUR/BTC',
    '98606.63000000',
    new \DateTimeImmutable('2025-09-21 12:00:00')
);

// High precision rates
$precision = CryptoRateFixtures::createHighPrecisionRate();

// Time series data
$last24h = CryptoRateFixtures::createLast24HoursRates('EUR/BTC');
$dayRates = CryptoRateFixtures::createDayRates('EUR/BTC', new \DateTimeImmutable('2025-09-21'));
$sequence = CryptoRateFixtures::createTimestampSequence('EUR/BTC', 5, '+5 minutes');

// Chart-ready data
$chartData = CryptoRateFixtures::createChartDataSample();

// Multiple pairs at same timestamp
$multiPair = CryptoRateFixtures::createMultiPairRates();
```

### 📝 RequestFixtures

Creates DTO request objects for validation testing:

```php
use App\Tests\Fixtures\RequestFixtures;

// Valid requests
$cryptoRequest = RequestFixtures::createValidCryptoRatesRequest('EUR/BTC', '2025-09-21');
$last24h = RequestFixtures::createValidLast24HoursRequest('EUR/BTC');

// Invalid requests for testing validation
$invalidPair = RequestFixtures::createInvalidPairRequest();
$emptyPair = RequestFixtures::createEmptyPairRequest();
$invalidDate = RequestFixtures::createInvalidDateRequest();

// Bulk requests
$allPairs = RequestFixtures::getAllSupportedPairRequests();
$all24h = RequestFixtures::getAllSupportedPairLast24HRequests();
```

## Test Data Patterns

### 💰 Currency Rates

- **BTC**: ~98,000 EUR (high value)
- **ETH**: ~3,800 EUR (medium value)
- **LTC**: ~97 EUR (lower value)
- **Precision**: All rates use 8 decimal places (DECIMAL 20,8)

### ⏰ Timestamps

- **Last 24h**: Hourly data points (24 rates)
- **Day data**: 5-minute intervals (288 rates)
- **Sequences**: Configurable intervals
- **Chart data**: Real-world sample timestamps

### ✅ Validation Cases

- **Valid pairs**: EUR/BTC, EUR/ETH, EUR/LTC
- **Invalid pairs**: USD/BTC, INVALID, EUR/DOGE
- **Date formats**: YYYY-MM-DD (valid), invalid-date (invalid)
- **Rate ranges**: Positive decimals (valid), negative/empty (invalid)

## Usage Examples

### Entity Tests
```php
public function testCryptoRateValidation(): void
{
    $rate = CryptoRateFixtures::createBtcRate('98606.63000000');
    $violations = $this->validator->validate($rate);

    $this->assertCount(0, $violations);
}
```

### Service Tests
```php
public function testGetRatesForLast24Hours(): void
{
    $expectedRates = CryptoRateFixtures::createLast24HoursRates('EUR/BTC');

    $this->repository
        ->expects($this->once())
        ->method('findRatesForLast24Hours')
        ->willReturn($expectedRates);

    $result = $this->service->getRatesForLast24Hours('EUR/BTC');
    $this->assertEquals($expectedRates, $result);
}
```

### Controller Tests
```php
public function testApiEndpoint(): void
{
    // Setup test data
    $rates = CryptoRateFixtures::createChartDataSample();

    // Mock repository response
    $this->setupRepositoryMock($rates);

    // Test API call
    $this->client->request('GET', '/api/rates/last-24h?pair=EUR/BTC');
    $this->assertResponseIsSuccessful();
}
```

## Running Fixture Tests

```bash
# Test fixtures functionality
docker compose exec api php bin/phpunit tests/Example/FixturesUsageExampleTest.php --testdox

# All entity tests with fixtures
docker compose exec api php bin/phpunit tests/Unit/Entity/ --testdox

# All DTO tests with fixtures
docker compose exec api php bin/phpunit tests/Unit/DTO/ --testdox
```

## Benefits

✅ **Consistent Test Data**: Same realistic rates across all tests
✅ **Easy Maintenance**: Centralized test data creation
✅ **Realistic Scenarios**: Based on actual cryptocurrency rates
✅ **Time Series Support**: Generate sequences for chart testing
✅ **Validation Testing**: Built-in valid/invalid test cases
✅ **Performance**: Pre-defined data reduces test setup time

## File Structure

```
tests/
├── Fixtures/
│   ├── CryptoRateFixtures.php    # Entity fixtures
│   └── RequestFixtures.php       # DTO fixtures
├── Example/
│   └── FixturesUsageExampleTest.php  # Usage examples
├── Unit/
│   ├── Entity/                   # Entity tests using fixtures
│   └── DTO/                      # DTO tests using fixtures
└── README.md                     # This guide
```