# Crypto Rates API

A production-ready Symfony-based REST API for tracking cryptocurrency exchange rates from Binance with comprehensive monitoring, observability, and real-time data processing capabilities.

## 🚀 Features

### Core Functionality
- **Real-time Rate Tracking**: Automatically fetches cryptocurrency rates from Binance API every 5 minutes
- **Historical Data Access**: Query rates for specific dates or last 24 hours with chart-ready formatting
- **RESTful API**: Clean, well-documented endpoints with comprehensive validation
- **Batch Processing**: Efficient bulk operations for historical data backfill

### Supported Currency Pairs
- **EUR/BTC** (Bitcoin to Euro)
- **EUR/ETH** (Ethereum to Euro)
- **EUR/LTC** (Litecoin to Euro)

### Monitoring & Observability
- **Prometheus Metrics**: Custom HTTP and SQL performance metrics
- **OpenTelemetry Tracing**: Distributed tracing with Jaeger integration
- **Structured Logging**: GELF-formatted logs with Logstash processing
- **Health Checks**: Comprehensive service monitoring

### Production Features
- **Docker Deployment**: Multi-stage Dockerfiles with FrankenPHP
- **High Performance**: OpCache optimization and efficient database queries
- **Scalable Architecture**: Separate API and scheduler containers
- **Security**: CORS configuration, input validation, and secure defaults

## 🏗️ Architecture

### Technology Stack
- **Runtime**: PHP 8.2+ with FrankenPHP (Caddy-based application server)
- **Framework**: Symfony 7.3 with modern PHP practices
- **Database**: MariaDB with Doctrine ORM
- **Message Queue**: Symfony Messenger for async task processing
- **Monitoring**: Prometheus + Jaeger + Logstash stack
- **Deployment**: Docker with multi-container architecture

### Service Architecture
```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   API Service   │    │ Scheduler Service │    │    MariaDB      │
│   (Port 80)     │────│  (Background)     │────│   (Port 3306)   │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
         ┌─────────────────┐    ┌┴──────────────────┐    ┌─────────────────┐
         │     Jaeger      │    │   Binance API     │    │    Logstash     │
         │   (Port 16686)  │    │ (External Source) │    │   (Port 12201)  │
         └─────────────────┘    └───────────────────┘    └─────────────────┘
```

## 🚀 Quick Start

### Development Setup

1. **Clone and start the environment**
   ```bash
   git https://github.com/AleksSem/php-test-assignment.git
   cd php-test-assignment
   docker compose up -d
   ```

2. **Access the application**
   - **API**: http://localhost
   - **Metrics**: http://localhost/metrics
   - **Jaeger UI**: http://localhost:16686
   - **Logs**: `docker compose logs -f`

3. **Verify installation**
   ```bash
   # Check service health
   curl "http://localhost/api/rates/last-24h?pair=EUR/BTC"

   # View metrics
   curl http://localhost/metrics
   ```

The application will automatically:
- Create and migrate the database
- Start fetching rates from Binance API
- Begin scheduled rate updates every 5 minutes

## 📡 API Reference

### Base URL
```
http://localhost/api/rates
```

### Endpoints

#### Get Last 24 Hours Rates
```http
GET /api/rates/last-24h?pair={pair}
```

**Parameters:**
- `pair` (required): Currency pair (`EUR/BTC`, `EUR/ETH`, `EUR/LTC`)

**Response:**
```json
{
  "pair": "EUR/BTC",
  "chart": {
    "labels": ["Sep-21 12:00", "Sep-21 12:05", "Sep-21 12:10"],
    "datasets": [{
      "label": "Exchange Rate",
      "data": ["54321.12345678", "54456.87654321", "54389.98765432"],
      "borderColor": "rgba(75,192,192,1)",
      "backgroundColor": "rgba(75,192,192,0.2)",
      "fill": true,
      "tension": 0.1
    }]
  },
  "count": 288
}
```

#### Get Full Day Rates
```http
GET /api/rates/day?pair={pair}&date={date}
```

**Parameters:**
- `pair` (required): Currency pair
- `date` (required): Date in YYYY-MM-DD format

**Response:**
```json
{
  "pair": "EUR/BTC",
  "chart": {
    "labels": ["00:00", "00:05", "00:10"],
    "datasets": [{
      "label": "Exchange Rate",
      "data": ["54321.12345678", "54456.87654321", "54389.98765432"],
      "borderColor": "rgba(75,192,192,1)",
      "backgroundColor": "rgba(75,192,192,0.2)",
      "fill": true,
      "tension": 0.1
    }]
  },
  "count": 288
}
```

#### Prometheus Metrics
```http
GET /metrics
```

Returns application performance metrics in Prometheus format.

### Error Responses
```json
{
  "error": {
    "type": "validation_error",
    "message": "Invalid currency pair",
    "details": {
      "pair": ["This value should be one of: EUR/BTC, EUR/ETH, EUR/LTC"]
    }
  }
}
```

## 🛠️ Development

### Running Tests
```bash
# All tests
docker compose exec api vendor/bin/phpunit

# Specific test suites
docker compose exec api vendor/bin/phpunit --testsuite=unit
docker compose exec api vendor/bin/phpunit --testsuite=functional
docker compose exec api vendor/bin/phpunit --testsuite=integration

# Single test file
docker compose exec api vendor/bin/phpunit tests/Functional/CryptoRatesControllerTest.php
```

### Database Operations
```bash
# Run migrations
docker compose exec api php bin/console doctrine:migrations:migrate

# Check migration status
docker compose exec api php bin/console doctrine:migrations:status

# Generate new migration after entity changes
docker compose exec api php bin/console doctrine:migrations:diff
```

### Manual Commands
```bash
# Update rates manually
docker compose exec api php bin/console app:update-crypto-rates

# Backfill historical data (last N days)
docker compose exec api php bin/console app:backfill-crypto-rates 30

# Backfill with specific pair and interval (default interval is 5m)
docker compose exec api php bin/console app:backfill-crypto-rates 7 --pair=EUR/BTC --interval=5m

# Monitor scheduler worker
docker compose exec scheduler php bin/console messenger:consume scheduler_default -vv
```

### Debugging
```bash
# Application logs
docker compose logs -f api

# Scheduler logs
docker compose logs -f scheduler

# Database logs
docker compose logs -f mariadb

# Check container health
docker compose ps
```

## 📊 Monitoring

### Metrics Collection
The application exposes Prometheus metrics at `/metrics` including:

- **HTTP Metrics**: Request duration, status codes, endpoint usage
- **SQL Metrics**: Query duration, connection counts, query types
- **Application Metrics**: Rate update success/failure, API response times
- **System Metrics**: Memory usage, CPU utilization

### Distributed Tracing
Access Jaeger UI at http://localhost:16686 to view:
- Request flow across services
- SQL query performance
- External API call timing
- Error tracking and debugging

### Structured Logging
Logs are processed by Logstash in GELF format with:
- Correlation IDs for request tracking
- Service context information
- Error details with stack traces
- Performance metrics integration

## 🔧 Configuration

### Environment Files
- **`.env.dev`**: Development configuration with debug enabled
- **`.env.prod`**: Production configuration with optimizations
- **`.env.test`**: Testing configuration with test database

### Key Configuration Parameters

#### Binance API Settings
```bash
BINANCE_API_TIMEOUT=30                    # API request timeout (seconds)
BINANCE_KLINES_TIMEOUT=60                # Historical data timeout (seconds)
BINANCE_KLINES_LIMIT=1000                # Maximum records per request
```

#### Chart Customization
```bash
CHART_BORDER_COLOR="rgba(75,192,192,1)"
CHART_BACKGROUND_COLOR="rgba(75,192,192,0.2)"
```

#### Monitoring Configuration
```bash
OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=http://jaeger:4318/v1/traces
OTEL_EXPORTER_OTLP_METRICS_ENDPOINT=http://jaeger:4318/v1/metrics
GRAYLOG_HOSTNAME=logstash
GRAYLOG_PORT=12201
```

### Supported Currency Pairs Configuration
Edit `config/parameters.yaml` to modify supported pairs:
```yaml
app.supported_pairs:
    'EUR/BTC': 'BTCEUR'
    'EUR/ETH': 'ETHEUR'
    'EUR/LTC': 'LTCEUR'
    # Add more pairs as needed
```

## 🚀 Production Deployment

For detailed production deployment instructions, see [DEPLOYMENT.md](DEPLOYMENT.md).

### Quick Production Start
```bash
# Build and deploy production services
docker compose -f compose.prod.yaml build --build-arg APP_VERSION=1.0.0
docker compose -f compose.prod.yaml up -d

# Run migrations
docker compose -f compose.prod.yaml exec api php bin/console doctrine:migrations:migrate --no-interaction
```

## 🗄️ Database Schema

### CryptoRate Entity
```sql
CREATE TABLE crypto_rates (
    id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL,
    pair VARCHAR(10) NOT NULL,
    rate NUMERIC(20, 8) NOT NULL,
    timestamp DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_pair_timestamp (pair, timestamp),
    PRIMARY KEY(id)
);
```

**Key Features:**
- High-precision decimal storage (20,8) for accurate rates
- Optimized indexing for time-series queries
- Duplicate prevention with unique constraints
- Efficient pagination support

## 🔒 Security

### API Security
- Input validation with Symfony Validator
- CORS configuration for cross-origin requests
- Rate limiting and request throttling
- Secure HTTP headers in production

### Container Security
- Multi-stage Docker builds for minimal attack surface
- Non-root user execution in containers
- Read-only filesystem where possible
- Health checks for service monitoring

### Data Security
- Encrypted database connections in production
- Environment variable management for secrets
- No sensitive data in logs or metrics
- Regular security updates for dependencies

## 🤝 Contributing

### Development Workflow
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Make changes and add tests
4. Run the test suite: `docker compose exec api vendor/bin/phpunit`
5. Submit a pull request

### Code Standards
- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for API changes
- Ensure all tests pass before submission

### Testing Requirements
- Unit tests for service layer
- Integration tests for database operations
- Functional tests for API endpoints
- Performance tests for critical paths

### Troubleshooting
- Check Docker container logs: `docker compose logs <service>`
- Verify service health: `docker compose ps`
- Test API connectivity: `curl http://localhost/api/rates/last-24h?pair=EUR/BTC`
- Monitor metrics: `curl http://localhost/metrics`

### Performance Optimization
- Monitor Prometheus metrics for bottlenecks
- Use Jaeger tracing for request flow analysis
- Check database query performance in logs
- Optimize Docker resource allocation as needed

For detailed deployment and operational procedures, refer to [DEPLOYMENT.md](DEPLOYMENT.md).