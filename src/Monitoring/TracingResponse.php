<?php

namespace App\Monitoring;

use OpenTelemetry\API\Trace\SpanInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Response decorator that completes tracing information when response is accessed.
 */
class TracingResponse implements ResponseInterface
{
    private bool $tracingCompleted = false;

    public function __construct(
        private readonly ResponseInterface $response,
        private readonly SpanInterface $span,
        private readonly float $startTime
    ) {}

    public function getStatusCode(): int
    {
        $this->completeTracing();
        return $this->response->getStatusCode();
    }

    public function getHeaders(bool $throw = true): array
    {
        $this->completeTracing();
        return $this->response->getHeaders($throw);
    }

    public function getContent(bool $throw = true): string
    {
        $this->completeTracing();
        return $this->response->getContent($throw);
    }

    public function toArray(bool $throw = true): array
    {
        $this->completeTracing();
        return $this->response->toArray($throw);
    }

    public function cancel(): void
    {
        $this->completeTracing(true);
        $this->response->cancel();
    }

    public function getInfo(string $type = null): mixed
    {
        return $this->response->getInfo($type);
    }

    private function completeTracing(bool $cancelled = false): void
    {
        if ($this->tracingCompleted) {
            return;
        }

        $this->tracingCompleted = true;
        $duration = microtime(true) - $this->startTime;

        try {
            if ($cancelled) {
                $this->span->setAttributes([
                    'http.duration' => $duration,
                    'http.cancelled' => true,
                ]);
                $this->span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, 'Request cancelled');
            } else {
                $statusCode = $this->response->getStatusCode();
                $contentLength = $this->response->getHeaders(false)['content-length'][0] ?? null;

                $this->span->setAttributes([
                    'http.status_code' => $statusCode,
                    'http.duration' => $duration,
                ]);

                if ($contentLength !== null) {
                    $this->span->setAttribute('http.response.size', (int) $contentLength);
                }

                if ($statusCode >= 400) {
                    $this->span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, "HTTP Error $statusCode");
                }
            }
        } catch (\Throwable $e) {
            $this->span->setAttributes([
                'http.duration' => $duration,
                'error' => true,
                'error.type' => get_class($e),
                'error.message' => $e->getMessage(),
            ]);
            $this->span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $e->getMessage());
        } finally {
            $this->span->end();
        }
    }
}
