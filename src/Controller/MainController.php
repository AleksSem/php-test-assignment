<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('', name: 'homepage', methods: ['GET'])]
    final public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to the Crypto Rates API',
        ]);
    }
}