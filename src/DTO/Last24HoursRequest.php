<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class Last24HoursRequest
{
    #[Assert\NotBlank(message: 'Pair parameter is required')]
    #[Assert\Choice(
        choices: CryptoRatesRequest::SUPPORTED_PAIRS,
        message: 'Unsupported pair. Supported pairs: EUR/BTC, EUR/ETH, EUR/LTC'
    )]
    public string $pair;

    public function __construct(string $pair)
    {
        $this->pair = $pair;
    }

    final public function getPair(): string
    {
        return $this->pair;
    }
}
