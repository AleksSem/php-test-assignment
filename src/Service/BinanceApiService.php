<?php

namespace App\Service;

use App\Entity\CryptoRate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BinanceApiService
{
    private const BINANCE_API_URL = 'https://api.binance.com/api/v3/ticker/price';
    private const SUPPORTED_PAIRS = [
        'EUR/BTC' => 'BTCEUR',
        'EUR/ETH' => 'ETHEUR', 
        'EUR/LTC' => 'LTCEUR'
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Fetches currency rates from Binance API and saves them to database
     */
    public function updateRates(): void
    {
        $timestamp = new \DateTimeImmutable();
        
        foreach (self::SUPPORTED_PAIRS as $pair => $symbol) {
            try {
                $rate = $this->fetchRateFromBinance($symbol);
                $this->saveRate($pair, $rate, $timestamp);
                
                $this->logger->info('Updated rate for {pair}: {rate}', [
                    'pair' => $pair,
                    'rate' => $rate
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to update rate for {pair}: {error}', [
                    'pair' => $pair,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Fetches rate for specific currency pair from Binance API
     */
    private function fetchRateFromBinance(string $symbol): string
    {
        try {
            $response = $this->httpClient->request('GET', self::BINANCE_API_URL, [
                'query' => ['symbol' => $symbol],
                'timeout' => 10
            ]);

            $data = $response->toArray();
            
            if (!isset($data['price'])) {
                throw new \RuntimeException('Price not found in Binance API response');
            }

            return $data['price'];
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            throw new \RuntimeException('Failed to fetch rate from Binance API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Saves rate to database
     */
    private function saveRate(string $pair, string $rate, \DateTimeImmutable $timestamp): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair($pair);
        $cryptoRate->setRate($rate);
        $cryptoRate->setTimestamp($timestamp);

        $this->entityManager->persist($cryptoRate);
        $this->entityManager->flush();
    }

    /**
     * Gets rates for the last 24 hours
     */
    public function getRatesForLast24Hours(string $pair): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $qb->select('c')
           ->from(CryptoRate::class, 'c')
           ->where('c.pair = :pair')
           ->andWhere('c.timestamp >= :startTime')
           ->setParameter('pair', $pair)
           ->setParameter('startTime', new \DateTimeImmutable('-24 hours'))
           ->orderBy('c.timestamp', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets rates for specified day
     */
    public function getRatesForDay(string $pair, \DateTimeImmutable $date): array
    {
        $startOfDay = $date->setTime(0, 0, 0);
        $endOfDay = $date->setTime(23, 59, 59);

        $qb = $this->entityManager->createQueryBuilder();
        
        $qb->select('c')
           ->from(CryptoRate::class, 'c')
           ->where('c.pair = :pair')
           ->andWhere('c.timestamp >= :startTime')
           ->andWhere('c.timestamp <= :endTime')
           ->setParameter('pair', $pair)
           ->setParameter('startTime', $startOfDay)
           ->setParameter('endTime', $endOfDay)
           ->orderBy('c.timestamp', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets list of supported currency pairs
     */
    public function getSupportedPairs(): array
    {
        return array_keys(self::SUPPORTED_PAIRS);
    }
}
