<?php

namespace App\Service;

use App\Entity\CryptoRate;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CryptoRatePersistenceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Saves a single rate to database
     */
    public function saveRate(string $pair, string $rate, \DateTimeImmutable $timestamp): void
    {
        $cryptoRate = new CryptoRate();
        $cryptoRate->setPair($pair);
        $cryptoRate->setRate($rate);
        $cryptoRate->setTimestamp($timestamp);

        $this->entityManager->persist($cryptoRate);
        $this->entityManager->flush();
    }

    /**
     * Saves multiple rates in batches for performance
     */
    public function saveRatesBatch(array $rates, int $batchSize = 100): int
    {
        $inserted = 0;

        foreach ($rates as $index => $rateData) {
            $cryptoRate = new CryptoRate();
            $cryptoRate->setPair($rateData['pair']);
            $cryptoRate->setRate($rateData['rate']);
            $cryptoRate->setTimestamp($rateData['timestamp']);

            $this->entityManager->persist($cryptoRate);
            $inserted++;

            if (($index + 1) % $batchSize === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        return $inserted;
    }
}