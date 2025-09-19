# Deployment Instructions

## Development Quick Start

### 1. Clone and Start

```bash
git clone <repository-url>
cd paybis
docker compose up -d
```

That's it! The development environment automatically:
- Creates database if it doesn't exist
- Runs migrations
- Starts the API server with hot reload
- Starts the scheduler service

### 2. Optional: Manual Rate Update

```bash
docker compose exec api php bin/console app:update-crypto-rates
```

## Health Check

### API Endpoint Testing

```bash
# Check rates for 24 hours
curl "http://localhost/api/rates/last-24h?pair=EUR/BTC"

# Check rates for a day
curl "http://localhost/api/rates/day?pair=EUR/BTC&date=$(date +%Y-%m-%d)"

# Expected response formats:
# Last 24h: {"pair":"EUR/BTC","data":[...],"count":288}
# Day rates: {"pair":"EUR/BTC","date":"2024-12-01","data":[...],"count":288}
```

### Log Checking

```bash
# Application logs
docker-compose logs -f api

# Database logs
docker-compose logs -f mariadb
```

## Production Deployment

### 1. Environment Preparation

Create `.env` file with production settings:

```env
APP_ENV=prod
APP_SECRET=your-very-secure-secret-key
DATABASE_URL="mysql://user:password@host:port/database?serverVersion=8.0&charset=utf8mb4"
MARIADB_ROOT_PASSWORD=secure-root-password
MARIADB_DATABASE=crypto
MARIADB_USER=crypto_user
MARIADB_PASSWORD=secure-password
```

### 2. Start Production

```bash
docker-compose -f docker-compose.prod.yaml up -d
```

### 3. Run Migrations

```bash
docker-compose -f docker-compose.prod.yaml exec api php bin/console doctrine:migrations:migrate --no-interaction
```

### 4. Start Scheduler

```bash
docker-compose -f docker-compose.prod.yaml exec scheduler php bin/console scheduler:run
```

## Monitoring

### Container Status Check

```bash
docker-compose ps
```

### Resource Monitoring

```bash
docker stats
```

### Log Checking

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f api
docker-compose logs -f mariadb
docker-compose logs -f scheduler
```

## Backup

### Database Backup Creation

```bash
docker-compose exec mariadb mysqldump -u root -p crypto > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore from Backup

```bash
docker-compose exec -T mariadb mysql -u root -p crypto < backup_file.sql
```

## Application Update

### 1. Stop Services

```bash
docker-compose down
```

### 2. Update Code

```bash
git pull origin main
```

### 3. Rebuild Images

```bash
docker-compose build --no-cache
```

### 4. Start Updated Application

```bash
docker-compose up -d
```

### 5. Run Migrations (if any)

```bash
docker-compose exec api php bin/console doctrine:migrations:migrate
```

## Troubleshooting

### Database Connection Issues

```bash
# Check MariaDB status
docker-compose exec mariadb mysql -u root -p -e "SHOW DATABASES;"

# Check application connection
docker-compose exec api php bin/console doctrine:database:create --if-not-exists
```

### API Issues

```bash
# Check application logs
docker-compose logs api

# Check API availability
curl -I http://localhost/api/rates/last-24h?pair=EUR/BTC
```

### Scheduler Issues

```bash
# Manual rate update
docker-compose exec api php bin/console app:update-crypto-rates

# Check scheduler logs
docker-compose logs scheduler
```

## Security

### Firewall Configuration

```bash
# Allow only necessary ports
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### Secret Updates

```bash
# Generate new APP_SECRET
openssl rand -hex 32
```

### SSL Configuration (recommended for production)

Use reverse proxy (nginx, Apache) with SSL certificates to protect the API.

## Scaling

### Horizontal Scaling

To increase load capacity:

1. Increase the number of API service replicas
2. Use load balancer
3. Configure read replicas for the database

### Vertical Scaling

Increase container resources in docker-compose.yaml:

```yaml
services:
  api:
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '0.5'
```
