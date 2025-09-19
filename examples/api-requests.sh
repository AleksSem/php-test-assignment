#!/bin/bash

# Examples of cryptocurrency rates API requests
# Make sure the application is running on localhost:80

BASE_URL="http://localhost"

echo "=== Testing Cryptocurrency Rates API ==="
echo

# 1. Get supported currency pairs
echo "1. Supported currency pairs:"
curl -s "${BASE_URL}/api/rates/supported-pairs" | jq .
echo
echo

# 2. EUR/BTC rates for the last 24 hours
echo "2. EUR/BTC rates for the last 24 hours:"
curl -s "${BASE_URL}/api/rates/last-24h?pair=EUR/BTC" | jq .
echo
echo

# 3. EUR/ETH rates for the last 24 hours
echo "3. EUR/ETH rates for the last 24 hours:"
curl -s "${BASE_URL}/api/rates/last-24h?pair=EUR/ETH" | jq .
echo
echo

# 4. EUR/LTC rates for the last 24 hours
echo "4. EUR/LTC rates for the last 24 hours:"
curl -s "${BASE_URL}/api/rates/last-24h?pair=EUR/LTC" | jq .
echo
echo

# 5. EUR/BTC rates for specific day (today)
TODAY=$(date +%Y-%m-%d)
echo "5. EUR/BTC rates for ${TODAY}:"
curl -s "${BASE_URL}/api/rates/day?pair=EUR/BTC&date=${TODAY}" | jq .
echo
echo

# 6. EUR/ETH rates for specific day (yesterday)
YESTERDAY=$(date -d "yesterday" +%Y-%m-%d)
echo "6. EUR/ETH rates for ${YESTERDAY}:"
curl -s "${BASE_URL}/api/rates/day?pair=EUR/ETH&date=${YESTERDAY}" | jq .
echo
echo

# 7. Validation test - invalid currency pair
echo "7. Validation test - invalid currency pair:"
curl -s "${BASE_URL}/api/rates/last-24h?pair=INVALID" | jq .
echo
echo

# 8. Validation test - missing pair parameter
echo "8. Validation test - missing pair parameter:"
curl -s "${BASE_URL}/api/rates/last-24h" | jq .
echo
echo

# 9. Validation test - invalid date format
echo "9. Validation test - invalid date format:"
curl -s "${BASE_URL}/api/rates/day?pair=EUR/BTC&date=invalid-date" | jq .
echo
echo

# 10. Validation test - missing date parameter
echo "10. Validation test - missing date parameter:"
curl -s "${BASE_URL}/api/rates/day?pair=EUR/BTC" | jq .
echo
echo

echo "=== Testing completed ==="
