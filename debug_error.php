<?php

// Script de diagnostic pour identifier l'erreur 500
// Usage: php debug_error.php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use App\Kernel;

// Créer une requête pour tester la route
$request = Request::create('/attestation-fiscale/formulaire-date/653?cotisations[]=376', 'GET');

try {
    $kernel = new Kernel('dev', true);
    $response = $kernel->handle($request);
    echo "Response status: " . $response->getStatusCode() . PHP_EOL;
    echo "Response content: " . $response->getContent() . PHP_EOL;
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Trace:" . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}

$kernel->terminate($request, $response ?? new \Symfony\Component\HttpFoundation\Response('', 500));