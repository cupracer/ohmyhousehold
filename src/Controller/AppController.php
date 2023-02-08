<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_start')]
    public function start(): Response
    {
        return new Response("welcome", 200);
    }

    #[Route('/ping', name: 'app_ping')]
    public function ping(): Response
    {
        return new Response("PONG", 200);
    }
}
