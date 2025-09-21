<?php

namespace App\Tests\Unit\Service;

use App\Service\ExceptionHandlerService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ExceptionHandlerServiceTest extends TestCase
{
    private ExceptionHandlerService $service;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ExceptionHandlerService($this->logger);
    }

    public function testHandleValidationErrors(): void
    {
        $violation1 = new ConstraintViolation(
            'Pair parameter is required',
            null,
            [],
            null,
            'pair',
            null
        );

        $violation2 = new ConstraintViolation(
            'Invalid date format',
            null,
            [],
            null,
            'date',
            null
        );

        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $response = $this->service->handleValidationErrors($violations);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals('Validation failed', $data['error']);
        $this->assertCount(2, $data['details']);
        $this->assertEquals('pair', $data['details'][0]['field']);
        $this->assertEquals('Pair parameter is required', $data['details'][0]['message']);
        $this->assertEquals('date', $data['details'][1]['field']);
        $this->assertEquals('Invalid date format', $data['details'][1]['message']);
    }

    public function testHandleApiException(): void
    {
        $exception = new \RuntimeException('Something went wrong');
        $context = 'testContext';

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'API Exception in {context}: {message}',
                [
                    'context' => $context,
                    'message' => 'Something went wrong',
                    'exception' => $exception
                ]
            );

        $response = $this->service->handleApiException($exception, $context);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals('Internal server error', $data['error']);
        $this->assertEquals('An error occurred while processing your request', $data['message']);
    }

    public function testHandleBinanceApiException(): void
    {
        $exception = new \RuntimeException('Binance API timeout');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Binance API Exception: {message}',
                [
                    'message' => 'Binance API timeout',
                    'exception' => $exception
                ]
            );

        $response = $this->service->handleBinanceApiException($exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals('External API error', $data['error']);
        $this->assertEquals('Failed to fetch data from Binance API', $data['message']);
    }
}