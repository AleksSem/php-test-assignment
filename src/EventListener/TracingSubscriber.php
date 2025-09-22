<?php

namespace App\EventListener;

use App\Monitoring\OpenTelemetryService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TracingSubscriber implements EventSubscriberInterface
{
    private array $activeSpans = [];

    public function __construct(
        private readonly OpenTelemetryService $openTelemetryService,
        private readonly array $monitoringSkipUrls,
        private readonly bool $otelEnabled = true
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
            KernelEvents::EXCEPTION => ['onKernelException', -1024],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->otelEnabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = spl_object_hash($request);

        // Skip tracing for monitoring endpoints to avoid recursive calls
        $route = $this->getRouteName($request);
        if ($this->shouldSkipTracing($route)) {
            return;
        }

        $spanName = sprintf('HTTP %s %s', $request->getMethod(), $this->getRouteName($request));

        $span = $this->openTelemetryService->startSpan($spanName, [
            'http.method' => $request->getMethod(),
            'http.url' => $request->getUri(),
            'http.route' => $route,
            'http.user_agent' => $request->headers->get('User-Agent', ''),
            'http.client_ip' => $request->getClientIp(),
        ]);

        // Activate span in context so child spans are linked
        $scope = $span->activate();

        $this->activeSpans[$requestId] = ['span' => $span, 'scope' => $scope];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestId = spl_object_hash($request);

        if (!isset($this->activeSpans[$requestId])) {
            return;
        }

        $spanData = $this->activeSpans[$requestId];
        unset($this->activeSpans[$requestId]);

        $span = $spanData['span'];
        $scope = $spanData['scope'];

        $span->setAttributes([
            'http.status_code' => $response->getStatusCode(),
            'http.response.size' => strlen($response->getContent()),
        ]);

        if ($response->getStatusCode() >= 400) {
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, 'HTTP Error');
        }

        $scope->detach();
        $span->end();
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = spl_object_hash($request);

        if (!isset($this->activeSpans[$requestId])) {
            return;
        }

        $spanData = $this->activeSpans[$requestId];
        unset($this->activeSpans[$requestId]);

        $span = $spanData['span'];
        $scope = $spanData['scope'];
        $exception = $event->getThrowable();

        $span->setAttributes([
            'error' => true,
            'error.type' => get_class($exception),
            'error.message' => $exception->getMessage(),
        ]);

        $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $exception->getMessage());

        $scope->detach();
        $span->end();
    }

    private function getRouteName(Request $request): string
    {
        // Use URL path instead of route name for clearer tracing
        return $request->getPathInfo();
    }

    private function shouldSkipTracing(string $route): bool
    {
        return in_array($route, $this->monitoringSkipUrls, true);
    }
}
