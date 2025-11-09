<?php

namespace App\Controller;

use App\Command\ImportHistoricalDataCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestImportController extends AbstractController
{
    #[Route('/test-import', name: 'test_import')]
    public function testImport(EntityManagerInterface $entityManager): Response
    {
        try {
            $command = new ImportHistoricalDataCommand($entityManager);
            
            $input = new ArrayInput([]);
            $output = new BufferedOutput();
            
            $result = $command->run($input, $output);
            
            $outputContent = $output->fetch();
            
            return new Response(
                '<pre>' . htmlspecialchars($outputContent) . '</pre>' .
                '<p>Résultat: ' . ($result === 0 ? 'SUCCESS' : 'FAILURE') . '</p>',
                200,
                ['Content-Type' => 'text/html']
            );
            
        } catch (\Exception $e) {
            return new Response(
                '<h1>Erreur lors de l\'import</h1>' .
                '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>' .
                '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>',
                500,
                ['Content-Type' => 'text/html']
            );
        }
    }
}