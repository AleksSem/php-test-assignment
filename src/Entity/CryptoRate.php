<?php

namespace App\Entity;

use App\Repository\CryptoRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: CryptoRateRepository::class)]
#[ORM\Table(name: 'crypto_rates')]
#[ORM\Index(name: 'idx_pair_timestamp', columns: ['pair', 'timestamp'])]
class CryptoRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    private string $pair;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 8)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private string $rate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank]
    private DateTimeImmutable $timestamp;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getPair(): string
    {
        return $this->pair;
    }

    final public function setPair(string $pair): self
    {
        $this->pair = $pair;
        return $this;
    }

    final public function getRate(): string
    {
        return $this->rate;
    }

    final public function setRate(string $rate): self
    {
        $this->rate = $rate;
        return $this;
    }

    final public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    final public function setTimestamp(DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    final public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    final public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
