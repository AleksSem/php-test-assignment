<?php

namespace App\EventListener;

use App\Service\PrometheusService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

class MetricsSubscriber implements EventSubscriberInterface
{
    private array $requestStartTimes = [];

    public function __construct(
        private readonly PrometheusService $prometheusService,
        private readonly array $monitoringSkipUrls
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = spl_object_hash($request);
        $this->requestStartTimes[$requestId] = microtime(true);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestId = spl_object_hash($request);

        if (!isset($this->requestStartTimes[$requestId])) {
            return;
        }

        $duration = microtime(true) - $this->requestStartTimes[$requestId];
        unset($this->requestStartTimes[$requestId]);

        // Skip monitoring endpoints to avoid recursive metrics
        $route = $this->getRouteName($request);
        if ($this->shouldSkipMonitoring($route)) {
            return;
        }

        $method = $request->getMethod();
        $statusCode = $response->getStatusCode();

        $this->prometheusService->incrementHttpRequests($method, $route, $statusCode);
        $this->prometheusService->observeHttpRequestDuration($method, $route, $duration);
    }

    private function getRouteName(Request $request): string
    {
        // Use URL path instead of route name for clearer metrics
        return $request->getPathInfo();
    }

    private function shouldSkipMonitoring(string $route): bool
    {
        return in_array($route, $this->monitoringSkipUrls, true);
    }
}