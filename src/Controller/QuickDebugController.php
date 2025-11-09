<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuickDebugController extends AbstractController
{
    #[Route('/quick-debug', name: 'quick_debug')]
    public function quickDebug(EntityManagerInterface $entityManager): Response
    {
        $html = '<h1>Quick Debug</h1>';
        
        try {
            // Test simple de base de données
            $connection = $entityManager->getConnection();
            $result = $connection->executeQuery('SELECT COUNT(*) as count FROM exercice');
            $count = $result->fetchOne();
            
            $html .= '<p>Nombre d\'exercices : ' . $count . '</p>';
            
            if ($count == 0) {
                $html .= '<p><strong>La base est vide, l\'import devrait se lancer.</strong></p>';
            } else {
                $html .= '<p><strong>Il y a déjà des exercices.</strong></p>';
            }
            
        } catch (\Exception $e) {
            $html .= '<p style="color:red">Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        return new Response($html);
    }
    
    #[Route('/force-import', name: 'force_import')]
    public function forceImport(): Response
    {
        $html = '<h1>Force Import</h1>';
        
        try {
            // Exécuter la commande d'import directement
            $output = [];
            $returnCode = 0;
            exec('cd /var/www/html && php bin/console app:import-historical-data 2>&1', $output, $returnCode);
            
            $html .= '<p>Code de retour: ' . $returnCode . '</p>';
            $html .= '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
            
        } catch (\Exception $e) {
            $html .= '<p style="color:red">Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        return new Response($html);
    }
}