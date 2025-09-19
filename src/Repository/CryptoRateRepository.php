<?php

namespace App\Repository;

use App\Entity\CryptoRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CryptoRate>
 */
final class CryptoRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CryptoRate::class);
    }

    /**
     * Gets rates for the last 24 hours
     */
    public function findRatesForLast24Hours(string $pair): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.pair = :pair')
            ->andWhere('c.timestamp >= :startTime')
            ->setParameter('pair', $pair)
            ->setParameter('startTime', new \DateTimeImmutable('-24 hours'))
            ->orderBy('c.timestamp', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets rates for specified day
     */
    public function findRatesForDay(string $pair, \DateTimeImmutable $date): array
    {
        $startOfDay = $date->setTime(0, 0, 0);
        $endOfDay = $date->setTime(23, 59, 59);

        return $this->createQueryBuilder('c')
            ->andWhere('c.pair = :pair')
            ->andWhere('c.timestamp >= :startTime')
            ->andWhere('c.timestamp <= :endTime')
            ->setParameter('pair', $pair)
            ->setParameter('startTime', $startOfDay)
            ->setParameter('endTime', $endOfDay)
            ->orderBy('c.timestamp', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Checks if a rate already exists for given pair and timestamp
     */
    public function rateExists(string $pair, \DateTimeImmutable $timestamp): bool
    {
        return $this->findOneBy([
            'pair' => $pair,
            'timestamp' => $timestamp
        ]) !== null;
    }
}