<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * HTTP Client decorator that adds OpenTelemetry tracing to HTTP requests.
 */
class TracingHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OpenTelemetryService $openTelemetryService
    ) {}

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $span = $this->openTelemetryService->startSpan("http.client.$method", [
            'http.method' => $method,
            'http.url' => $url,
            'http.client' => 'symfony',
        ]);

        $startTime = microtime(true);

        try {
            $response = $this->httpClient->request($method, $url, $options);

            // We need to wrap the response to capture the final status when it's accessed
            return new TracingResponse($response, $span, $startTime);

        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            $span->setAttributes([
                'http.duration' => $duration,
                'error' => true,
                'error.type' => get_class($e),
                'error.message' => $e->getMessage(),
            ]);

            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $e->getMessage());
            $span->end();

            throw $e;
        }
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->httpClient->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        return new self(
            $this->httpClient->withOptions($options),
            $this->openTelemetryService
        );
    }
}