# Crypto Rates API

Web application built with Symfony and PHP 8.x, providing API for cryptocurrency exchange rates EUR to BTC, ETH, LTC. Data source: Binance API.

## Features

- **Periodic updates**: Saving exchange rates (EUR/BTC, EUR/ETH, EUR/LTC) to MySQL every 5 minutes
- **API endpoints**: JSON responses for charts
- **Automation**: Symfony Scheduler for rate updates
- **Validation**: Complete input data validation
- **Error handling**: Centralized exception handling
- **Logging**: Detailed logging of all operations

## Technologies

- **PHP 8.2+**
- **Symfony 7.3**
- **MySQL/MariaDB**
- **Docker & Docker Compose**
- **FrankenPHP** (for production)
- **Binance API**

## Installation

### Requirements

- Docker and Docker Compose
- PHP 8.2+ (for local development)
- Composer (for local development)

### Quick Start with Docker

1. **Clone the repository:**
```bash
git clone <repository-url>
cd paybis
```

2. **Start the application:**
```bash
docker compose --env-file .env.dev up -d
```

3. **Run migrations:**
```bash
docker-compose exec api php bin/console doctrine:migrations:migrate
```

4. **Start rate updates (optional):**
```bash
docker-compose exec api php bin/console app:update-crypto-rates
```

### Local Development

1. **Install dependencies:**
```bash
composer install
```

2. **Configure database:**
```bash
# Create .env file with database settings
DATABASE_URL="mysql://root:password@127.0.0.1:3306/paybis?serverVersion=8.0&charset=utf8mb4"
```

3. **Run migrations:**
```bash
php bin/console doctrine:migrations:migrate
```

4. **Start server:**
```bash
symfony server:start
```

## API Endpoints

### 1. Rates for the last 24 hours

**GET** `/api/rates/last-24h?pair=EUR/BTC`

**Parameters:**
- `pair` (required) - currency pair (EUR/BTC, EUR/ETH, EUR/LTC)

**Example request:**
```bash
curl "http://localhost/api/rates/last-24h?pair=EUR/BTC"
```

**Example response:**
```json
{
  "pair": "EUR/BTC",
  "chart": {
    "labels": ["Sep-21 12:22", "Sep-21 12:37", "Sep-21 12:38"],
    "datasets": [
      {
        "label": "Exchange Rate",
        "data": [98639.69, 98654.08, 98654.10],
        "borderColor": "#007bff",
        "backgroundColor": "rgba(0, 123, 255, 0.1)",
        "fill": true,
        "tension": 0.1
      }
    ]
  },
  "count": 288
}
```

### 2. Rates for a specific day

**GET** `/api/rates/day?pair=EUR/BTC&date=2024-12-01`

**Parameters:**
- `pair` (required) - currency pair (EUR/BTC, EUR/ETH, EUR/LTC)
- `date` (required) - date in YYYY-MM-DD format

**Example request:**
```bash
curl "http://localhost/api/rates/day?pair=EUR/BTC&date=2024-12-01"
```

**Example response:**
```json
{
  "pair": "EUR/BTC",
  "date": "2024-12-01",
  "chart": {
    "labels": ["12:22", "12:37", "12:38"],
    "datasets": [
      {
        "label": "Exchange Rate",
        "data": [98639.69, 98654.08, 98654.10],
        "borderColor": "#007bff",
        "backgroundColor": "rgba(0, 123, 255, 0.1)",
        "fill": true,
        "tension": 0.1
      }
    ]
  },
  "count": 288
}
```

## Automatic Rate Updates

### Symfony Scheduler (recommended)

The application uses Symfony Scheduler for automatic rate updates every 5 minutes.

**Start the scheduler:**
```bash
# In Docker
docker-compose exec api php bin/console scheduler:run

# Locally
php bin/console scheduler:run
```

### Cron (alternative)

If you prefer to use cron:

```bash
# Add to crontab
*/5 * * * * cd /path/to/project && php bin/console app:update-crypto-rates
```

## Project Structure

```
src/
├── Command/
│   └── UpdateCryptoRatesCommand.php    # Command for updating rates
├── Controller/
│   └── CryptoRatesController.php       # API controllers
├── DTO/
│   └── CryptoRatesRequest.php          # DTO for validation
├── Entity/
│   └── CryptoRate.php                  # Entity for currency rates
├── Scheduler/
│   └── UpdateCryptoRatesTask.php       # Scheduler task
└── Service/
    ├── BinanceApiService.php           # Service for Binance API
    └── ExceptionHandlerService.php     # Exception handling
```

## Configuration

### Environment Files

**Development (`.env.dev`):**
```env
# Database Configuration
DATABASE_SERVER_VERSION=mariadb-10.11.2
DATABASE_DRIVER=pdo_mysql
DATABASE_HOST=mariadb
DATABASE_PORT=3306
DATABASE_NAME=crypto
DATABASE_USER=db_user_123
DATABASE_PASSWORD=db_pass_123

# Application
APP_SECRET=dev-secret-key-change-in-production

# OpenTelemetry
OTEL_SERVICE_VERSION=1.0.0
OTEL_EXPORTER_OTLP_ENDPOINT=http://jaeger:4318
OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=http://jaeger:4318/v1/traces
OTEL_EXPORTER_OTLP_METRICS_ENDPOINT=http://jaeger:4318/v1/metrics
```

**Production (`.env.prod`):**
```env
# OpenTelemetry (production defaults)
OTEL_SERVICE_VERSION=1.0.0
OTEL_EXPORTER_OTLP_ENDPOINT=http://jaeger:4318
OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=http://jaeger:4318/v1/traces
OTEL_EXPORTER_OTLP_METRICS_ENDPOINT=http://jaeger:4318/v1/metrics
```

### Scheduler Configuration

File `config/packages/scheduler.yaml`:
```yaml
framework:
    scheduler:
        enabled: true
        tasks:
            update_crypto_rates:
                type: 'periodic'
                expression: '*/5 * * * *'
                command: 'app:update-crypto-rates'
```

## Production Deployment

### Docker Compose

1. **Create .env file for production:**
```env
APP_SECRET=your-production-secret
DATABASE_URL="mysql://user:password@host:port/database?serverVersion=8.0&charset=utf8mb4"
MARIADB_ROOT_PASSWORD=secure-root-password
MARIADB_DATABASE=crypto
MARIADB_USER=crypto_user
MARIADB_PASSWORD=secure-password
```

2. **Start production:**
```bash
docker compose -f compose.prod.yaml --env-file .env.prod up -d
```

3. **Run migrations:**
```bash
docker compose -f compose.prod.yaml exec api php bin/console doctrine:migrations:migrate --no-interaction
```

### Monitoring

- **Application logs:** `docker-compose logs -f api`
- **Database logs:** `docker-compose logs -f mariadb`
- **Scheduler logs:** `docker-compose logs -f scheduler`

## Error Handling

The application includes centralized error handling:

- **Input data validation** with detailed error messages
- **API exception handling** with logging
- **Graceful degradation** when Binance API is unavailable
- **HTTP status codes** according to REST standards

## Logging

All operations are logged using PSR-3 Logger:

- Currency rate updates
- API errors
- Data validation
- Exceptions

## Testing

### Manual API Testing

```bash
# Test getting rates for 24 hours
curl "http://localhost/api/rates/last-24h?pair=EUR/BTC"

# Test getting rates for a day
curl "http://localhost/api/rates/day?pair=EUR/BTC&date=2024-12-01"

# Test validation (should return error)
curl "http://localhost/api/rates/last-24h?pair=INVALID"
```

### Rate Update Testing

```bash
# Manual rate update
docker-compose exec api php bin/console app:update-crypto-rates
```

## Support

For questions and suggestions, create Issues in the repository.

## License

Project developed for demonstration purposes.