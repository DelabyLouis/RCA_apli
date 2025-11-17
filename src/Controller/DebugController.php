<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugController extends AbstractController
{
    #[Route('/debug-test', name: 'app_debug_test')]
    public function debugTest(): Response
    {
        try {
            // Test de base pour voir si le contrôleur fonctionne
            return new Response('Debug test OK - ' . date('Y-m-d H:i:s'));
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}