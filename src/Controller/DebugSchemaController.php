<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Exercice;
use App\Entity\TypeTransaction;
use App\Entity\ModeDePaiement;
use App\Entity\Personne;
use App\Entity\Transaction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugSchemaController extends AbstractController
{
    #[Route('/debug-schema', name: 'debug_schema')]
    public function debugSchema(EntityManagerInterface $entityManager): Response
    {
        $html = '<h1>Debug du schéma de base de données</h1>';
        
        try {
            // Vérifier les tables existantes
            $connection = $entityManager->getConnection();
            $schemaManager = $connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();
            
            $html .= '<h2>Tables présentes :</h2><ul>';
            foreach ($tables as $table) {
                $html .= '<li>' . htmlspecialchars($table) . '</li>';
            }
            $html .= '</ul>';
            
            // Compter les enregistrements dans chaque entité
            $entities = [
                'Exercice' => Exercice::class,
                'TypeTransaction' => TypeTransaction::class,
                'ModeDePaiement' => ModeDePaiement::class,
                'Personne' => Personne::class,
                'Transaction' => Transaction::class,
            ];
            
            $html .= '<h2>Nombre d\'enregistrements par entité :</h2><ul>';
            foreach ($entities as $name => $class) {
                try {
                    $count = $entityManager->getRepository($class)->count([]);
                    $html .= '<li>' . $name . ': ' . $count . ' enregistrements</li>';
                } catch (\Exception $e) {
                    $html .= '<li>' . $name . ': ERREUR - ' . htmlspecialchars($e->getMessage()) . '</li>';
                }
            }
            $html .= '</ul>';
            
            // Tester la création d'un exercice simple
            $html .= '<h2>Test de création d\'un exercice :</h2>';
            try {
                $exercice = new Exercice();
                $exercice->setLibelle('Test Import');
                $exercice->setDateDebut(new \DateTime('2024-01-01'));
                $exercice->setDateFin(new \DateTime('2024-12-31'));
                $exercice->setClos(false);
                $exercice->setNumeroOrdre(999);
                
                $entityManager->persist($exercice);
                $entityManager->flush();
                
                $html .= '<p style="color: green;">✅ Exercice test créé avec succès (ID: ' . $exercice->getIdExercice() . ')</p>';
                
                // Le supprimer immédiatement
                $entityManager->remove($exercice);
                $entityManager->flush();
                $html .= '<p style="color: blue;">🗑️ Exercice test supprimé</p>';
                
            } catch (\Exception $e) {
                $html .= '<p style="color: red;">❌ Erreur lors de la création : ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            
        } catch (\Exception $e) {
            $html .= '<p style="color: red;">Erreur générale: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}