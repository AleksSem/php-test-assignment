<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ExceptionHandlerService
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function handleValidationErrors(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage()
            ];
        }

        return new JsonResponse([
            'error' => 'Validation failed',
            'details' => $errors
        ], Response::HTTP_BAD_REQUEST);
    }

    public function handleApiException(\Throwable $e, string $context = ''): JsonResponse
    {
        $this->logger->error('API Exception in {context}: {message}', [
            'context' => $context,
            'message' => $e->getMessage(),
            'exception' => $e
        ]);

        return new JsonResponse([
            'error' => 'Internal server error',
            'message' => 'An error occurred while processing your request'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function handleBinanceApiException(\Throwable $e): JsonResponse
    {
        $this->logger->error('Binance API Exception: {message}', [
            'message' => $e->getMessage(),
            'exception' => $e
        ]);

        return new JsonResponse([
            'error' => 'External API error',
            'message' => 'Failed to fetch data from Binance API'
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
