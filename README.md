# Crypto Rates API

Symfony 7.3 REST API for tracking cryptocurrency exchange rates from Binance with monitoring and observability.

## Features

- **Real-time Tracking**: Fetches crypto rates from Binance API every 5 minutes
- **REST API**: Query rates for last 24h or specific dates with chart data
- **Monitoring**: Prometheus metrics, OpenTelemetry tracing, structured logging
- **Docker**: Production-ready deployment with FrankenPHP

## Supported Pairs
- EUR/BTC, EUR/ETH, EUR/LTC (configurable in `config/parameters.yaml`)

## Tech Stack
- PHP 8.2 + Symfony 7.3 + MariaDB + Docker

## Architecture

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

## Quick Start

```bash
# Start development
docker compose up -d

# Check API
curl "http://localhost/api/rates/last-24h?pair=EUR/BTC"

# Run tests
docker compose exec api vendor/bin/phpunit
```

## API

- `GET /api/rates/last-24h?pair=EUR/BTC` - Last 24h rates with chart data
- `GET /api/rates/day?pair=EUR/BTC&date=2024-01-01` - Day rates with chart data
- `GET /metrics` - Prometheus metrics

## Development

```bash
# Run all tests
docker compose exec api vendor/bin/phpunit

# Run specific test suite
docker compose exec api vendor/bin/phpunit --testsuite=unit
docker compose exec api vendor/bin/phpunit --testsuite=functional
docker compose exec api vendor/bin/phpunit --testsuite=integration

# Run single test
docker compose exec api vendor/bin/phpunit tests/Unit/Service/BinanceApiServiceTest.php

# Database
docker compose exec api php bin/console doctrine:migrations:migrate

# Manual rate update
docker compose exec api php bin/console app:update-crypto-rates

# Backfill data
docker compose exec api php bin/console app:backfill-crypto-rates 30

# Logs
docker compose logs -f api
```


## Monitoring

- **Metrics**: http://localhost/metrics (Prometheus)
- **Tracing**: http://localhost:16686 (Jaeger)
- **GELF**: docker compose logs -f logstash (Logstash)
- **Logs**: `docker compose logs -f`

## Configuration

- Environment: `.env.dev`, `.env.prod`, `.env.test`

## Production

```bash
docker compose -f compose.prod.yaml up -d
```
