<?php

namespace App\Controller;

use App\Service\PrometheusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController extends AbstractController
{
    public function __construct(
        private readonly PrometheusService $prometheusService
    ) {}

    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(): Response
    {
        $metrics = $this->prometheusService->render();

        return new Response(
            $metrics,
            200,
            ['Content-Type' => 'text/plain; version=0.0.4; charset=utf-8']
        );
    }
}