# Production Deployment Guide

Guide for deploying the Crypto Rates API to production environments.

## 🔧 Configuration

### Environment Variables Setup

Edit the `.env.prod` file:

```bash
# Application
APP_ENV=prod
APP_SECRET=your-secure-secret-key-change-me-please

# Database
DATABASE_HOST=your-database-host
DATABASE_PORT=3306
DATABASE_NAME=crypto_rates
DATABASE_USER=crypto_user
DATABASE_PASSWORD=secure_password
DATABASE_SERVER_VERSION=mariadb-10.11.2
DATABASE_DRIVER=pdo_mysql

# Security
CORS_ALLOW_ORIGIN=^https?://(yourdomain\.com|api\.yourdomain\.com)(:[0-9]+)?$

# Binance API
BINANCE_API_TIMEOUT=30
BINANCE_KLINES_TIMEOUT=60
BINANCE_KLINES_LIMIT=1000

# Monitoring
OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=http://jaeger:4318/v1/traces
OTEL_EXPORTER_OTLP_METRICS_ENDPOINT=http://jaeger:4318/v1/metrics

# Logging
GRAYLOG_HOSTNAME=logstash
GRAYLOG_PORT=12201

# Chart Settings
CHART_BORDER_COLOR="rgba(75,192,192,1)"
CHART_BACKGROUND_COLOR="rgba(75,192,192,0.2)"
```

### Database Setup

#### Option 1: External Database (Recommended)
Use a managed database service (AWS RDS, Google Cloud SQL, etc.):

```bash
DATABASE_HOST=your-db-cluster.region.rds.amazonaws.com
DATABASE_USER=crypto_admin
DATABASE_PASSWORD=secure_password
DATABASE_NAME=crypto_rates
```

#### Option 2: Self-hosted Database
To deploy MariaDB in a container, edit `compose.prod.yaml`:

```yaml
services:
  mariadb:
    image: mariadb:11.2
    restart: unless-stopped
    environment:
      MARIADB_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MARIADB_DATABASE: crypto_rates
      MARIADB_USER: ${DATABASE_USER}
      MARIADB_PASSWORD: ${DATABASE_PASSWORD}
    volumes:
      - mariadb_data:/var/lib/mysql
    networks:
      - app-network

volumes:
  mariadb_data:
```

## 🚀 Deployment

### Method 1: Docker Compose

```bash
# 1. Build production images
docker compose -f compose.prod.yaml build

# 2. Start services
docker compose -f compose.prod.yaml up -d

# 3. Run migrations
docker compose -f compose.prod.yaml exec api php bin/console doctrine:migrations:migrate --no-interaction

# 4. Verify deployment
docker compose -f compose.prod.yaml ps
curl "http://your-domain/api/rates/last-24h?pair=EUR/BTC"
```

### Method 2: Container Registry

```bash
# 1. Build and push to registry
docker build -f docker/frankenphp/Dockerfile --target prod -t your-registry/crypto-rates:1.0.0 .
docker push your-registry/crypto-rates:1.0.0

# 2. Update compose file
# Replace build with: image: your-registry/crypto-rates:1.0.0

# 3. Deploy
docker compose -f compose.prod.yaml pull
docker compose -f compose.prod.yaml up -d
```

## 🔒 Security

### Basic Security Setup
```bash
# Generate secure app secret
APP_SECRET=$(openssl rand -hex 32)

# Configure CORS for your domain
CORS_ALLOW_ORIGIN=^https?://(yourdomain\.com)(:[0-9]+)?$

# Use strong database passwords
DATABASE_PASSWORD=$(openssl rand -base64 32)
```

## 📊 Monitoring

### Prometheus Metrics
Available at `/metrics` endpoint:
- HTTP requests: response time, status codes
- SQL queries: execution time, query count
- Business metrics: rate updates, API errors

### Logs
```bash
# View logs
docker compose -f compose.prod.yaml logs api
docker compose -f compose.prod.yaml logs scheduler

# Follow logs in real-time
docker compose -f compose.prod.yaml logs -f
```

### Health Checks
```bash
# Check API health
curl -f "http://your-domain/api/rates/last-24h?pair=EUR/BTC"

# Check metrics
curl "http://your-domain/metrics"

# Container status
docker compose -f compose.prod.yaml ps
```

### Updates
```bash
# 1. Stop services
docker compose -f compose.prod.yaml down

# 2. Update code
git pull origin main

# 3. Build new images
docker compose -f compose.prod.yaml build

# 4. Start with migrations
docker compose -f compose.prod.yaml up -d
docker compose -f compose.prod.yaml exec api php bin/console doctrine:migrations:migrate --no-interaction
```

### Log Rotation
```bash
# Configure Docker log rotation
cat > /etc/docker/daemon.json << EOF
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
EOF

sudo systemctl reload docker
```
