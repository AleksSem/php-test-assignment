<?php

namespace App\Controller;

use App\DTO\CryptoRatesRequest;
use App\Service\BinanceApiService;
use App\Service\ExceptionHandlerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/rates', name: 'api_rates_')]
class CryptoRatesController extends AbstractController
{
    public function __construct(
        private BinanceApiService $binanceApiService,
        private ValidatorInterface $validator,
        private ExceptionHandlerService $exceptionHandler
    ) {
    }

    #[Route('/last-24h', name: 'last_24h', methods: ['GET'])]
    public function getLast24Hours(Request $request): JsonResponse
    {
        try {
            $pair = $request->query->get('pair');
            $requestDto = new CryptoRatesRequest($pair);
            
            $violations = $this->validator->validate($requestDto);
            if (count($violations) > 0) {
                return $this->exceptionHandler->handleValidationErrors($violations);
            }

            $rates = $this->binanceApiService->getRatesForLast24Hours($pair);
            
            $data = array_map(function($rate) {
                return [
                    'timestamp' => $rate->getTimestamp()->format('Y-m-d H:i:s'),
                    'rate' => $rate->getRate(),
                    'pair' => $rate->getPair()
                ];
            }, $rates);

            return new JsonResponse([
                'pair' => $pair,
                'data' => $data,
                'count' => count($data)
            ]);
        } catch (\Exception $e) {
            return $this->exceptionHandler->handleApiException($e, 'getLast24Hours');
        }
    }

    #[Route('/day', name: 'day', methods: ['GET'])]
    public function getDay(Request $request): JsonResponse
    {
        try {
            $pair = $request->query->get('pair');
            $dateString = $request->query->get('date');
            
            $requestDto = new CryptoRatesRequest($pair, $dateString);
            
            $violations = $this->validator->validate($requestDto);
            if (count($violations) > 0) {
                return $this->exceptionHandler->handleValidationErrors($violations);
            }

            $date = new \DateTimeImmutable($dateString);
            $rates = $this->binanceApiService->getRatesForDay($pair, $date);
            
            $data = array_map(function($rate) {
                return [
                    'timestamp' => $rate->getTimestamp()->format('Y-m-d H:i:s'),
                    'rate' => $rate->getRate(),
                    'pair' => $rate->getPair()
                ];
            }, $rates);

            return new JsonResponse([
                'pair' => $pair,
                'date' => $date->format('Y-m-d'),
                'data' => $data,
                'count' => count($data)
            ]);
        } catch (\Exception $e) {
            return $this->exceptionHandler->handleApiException($e, 'getDay');
        }
    }

    #[Route('/supported-pairs', name: 'supported_pairs', methods: ['GET'])]
    public function getSupportedPairs(): JsonResponse
    {
        return new JsonResponse([
            'supported_pairs' => $this->binanceApiService->getSupportedPairs()
        ]);
    }
}
