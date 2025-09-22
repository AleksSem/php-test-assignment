# Production Deployment

## Configuration

Edit `.env.prod`:

```bash
APP_ENV=prod
APP_SECRET=your-secure-secret-key

DATABASE_HOST=your-database-host
DATABASE_USER=crypto_user
DATABASE_PASSWORD=secure_password
DATABASE_NAME=crypto_rates

CORS_ALLOW_ORIGIN=^https?://(yourdomain\.com)(:[0-9]+)?$
```

## Deployment

```bash
# Build and start
docker compose -f compose.prod.yaml build
docker compose -f compose.prod.yaml up -d

# Run migrations
docker compose -f compose.prod.yaml exec api php bin/console doctrine:migrations:migrate --no-interaction

# Verify
curl "http://your-domain/api/rates/last-24h?pair=EUR/BTC"
```

## Security

```bash
# Generate secrets
APP_SECRET=$(openssl rand -hex 32)
DATABASE_PASSWORD=$(openssl rand -base64 32)
```

## Monitoring

- Metrics: `/metrics`
- Logs: `docker compose -f compose.prod.yaml logs -f`
- Health: `curl "http://your-domain/api/rates/last-24h?pair=EUR/BTC"`

## Updates

```bash
docker compose -f compose.prod.yaml down
git pull origin main
docker compose -f compose.prod.yaml build
docker compose -f compose.prod.yaml up -d
```
