<?php

namespace App\Controller;

use App\DTO\CryptoRatesRequest;
use App\DTO\Last24HoursRequest;
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
    final public function getLast24Hours(Request $request): JsonResponse
    {
        try {
            $requestDto = new Last24HoursRequest($request->query->get('pair') ?? '');

            $violations = $this->validator->validate($requestDto);
            if (count($violations) > 0) {
                return $this->exceptionHandler->handleValidationErrors($violations);
            }

            $rates = $this->binanceApiService->getRatesForLast24Hours($requestDto->getPair());
            $data = $this->transformRatesToArray($rates);

            return new JsonResponse([
                'pair' => $requestDto->getPair(),
                'chart' => $this->transformRatesToChartFormat($rates),
                'count' => count($data)
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->handleApiException($e, 'getLast24Hours');
        }
    }

    #[Route('/day', name: 'day', methods: ['GET'])]
    final public function getDay(Request $request): JsonResponse
    {
        try {
            $requestDto = new CryptoRatesRequest(
                $request->query->get('pair') ?? '',
                $request->query->get('date')
            );

            $violations = $this->validator->validate($requestDto);
            if (count($violations) > 0) {
                return $this->exceptionHandler->handleValidationErrors($violations);
            }

            $date = new \DateTimeImmutable($requestDto->getDate());
            $rates = $this->binanceApiService->getRatesForDay($requestDto->getPair(), $date);
            $data = $this->transformRatesToArray($rates);

            return new JsonResponse([
                'pair' => $requestDto->getPair(),
                'date' => $date->format('Y-m-d'),
                'chart' => $this->transformRatesToChartFormat($rates, 'day'),
                'count' => count($data)
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->handleApiException($e, 'getDay');
        }
    }

    private function transformRatesToArray(array $rates): array
    {
        return array_map(function($rate) {
            return [
                'timestamp' => $rate->getTimestamp()->format('Y-m-d H:i:s'),
                'rate' => $rate->getRate(),
                'pair' => $rate->getPair()
            ];
        }, $rates);
    }

    private function transformRatesToChartFormat(array $rates, string $type = '24h'): array
    {
        $labels = [];
        $values = [];

        foreach ($rates as $rate) {
            if ($type === 'day') {
                $labels[] = $rate->getTimestamp()->format('H:i');
            } else {
                $labels[] = $rate->getTimestamp()->format('M-d H:i');
            }
            $values[] = $rate->getRate();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Exchange Rate',
                    'data' => $values,
                    'borderColor' => '#007bff',
                    'backgroundColor' => 'rgba(0, 123, 255, 0.1)',
                    'fill' => true,
                    'tension' => 0.1
                ]
            ]
        ];
    }
}
