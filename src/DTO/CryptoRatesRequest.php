<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CryptoRatesRequest
{
    public const SUPPORTED_PAIRS = ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'];

    #[Assert\NotBlank(message: 'Pair parameter is required')]
    #[Assert\Choice(
        choices: self::SUPPORTED_PAIRS,
        message: 'Unsupported pair. Supported pairs: EUR/BTC, EUR/ETH, EUR/LTC'
    )]
    public string $pair;

    #[Assert\NotBlank(message: 'Date parameter is required')]
    #[Assert\Date(message: 'Invalid date format. Use YYYY-MM-DD format')]
    public ?string $date;

    public function __construct(string $pair, ?string $date = null)
    {
        $this->pair = $pair;
        $this->date = $date;
    }

    public function getPair(): string
    {
        return $this->pair;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }
}
