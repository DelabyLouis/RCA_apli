<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FixEnabledFieldController extends AbstractController
{
    #[Route('/fix-enabled-field', name: 'fix_enabled_field')]
    public function fixEnabledField(EntityManagerInterface $entityManager): Response
    {
        try {
            $connection = $entityManager->getConnection();
            
            $html = '<h1>Fix Champ Enabled</h1>';
            
            // Vérifier si le champ existe déjà
            try {
                $stmt = $connection->executeQuery("SELECT enabled FROM \"user\" LIMIT 1");
                $html .= '<p>✅ Le champ enabled existe déjà</p>';
            } catch (\Exception $e) {
                $html .= '<p>❌ Le champ enabled n\'existe pas, ajout en cours...</p>';
                
                // Ajouter le champ enabled
                try {
                    $connection->executeStatement('ALTER TABLE "user" ADD COLUMN enabled BOOLEAN DEFAULT TRUE NOT NULL');
                    $html .= '<p>✅ Champ enabled ajouté avec succès</p>';
                } catch (\Exception $e2) {
                    $html .= '<p>❌ Erreur lors de l\'ajout: ' . $e2->getMessage() . '</p>';
                }
            }
            
            // Vérifier la structure finale
            try {
                $stmt = $connection->executeQuery("SELECT column_name, data_type, column_default FROM information_schema.columns WHERE table_name = 'user' AND column_name = 'enabled'");
                $column = $stmt->fetchAssociative();
                
                if ($column) {
                    $html .= '<h2>Structure du champ enabled:</h2>';
                    $html .= '<p>Type: ' . $column['data_type'] . '</p>';
                    $html .= '<p>Défaut: ' . ($column['column_default'] ?? 'NULL') . '</p>';
                } else {
                    $html .= '<p>❌ Le champ enabled n\'existe toujours pas</p>';
                }
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur vérification: ' . $e->getMessage() . '</p>';
            }
            
            // Tester les utilisateurs
            try {
                $users = $connection->executeQuery('SELECT id_user, username, enabled FROM "user"')->fetchAllAssociative();
                $html .= '<h2>Utilisateurs avec champ enabled:</h2>';
                $html .= '<ul>';
                foreach ($users as $user) {
                    $html .= '<li>ID: ' . $user['id_user'] . ', Username: ' . $user['username'] . ', Enabled: ' . ($user['enabled'] ? 'Oui' : 'Non') . '</li>';
                }
                $html .= '</ul>';
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur lecture utilisateurs: ' . $e->getMessage() . '</p>';
            }
            
            return new Response($html, 200, ['Content-Type' => 'text/html']);
            
        } catch (\Exception $e) {
            return new Response('Erreur générale: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>', 500, ['Content-Type' => 'text/html']);
        }
    }
}