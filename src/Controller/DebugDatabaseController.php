<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugDatabaseController extends AbstractController
{
    #[Route('/debug-db', name: 'debug_database')]
    public function debugDatabase(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        try {
            $html = '<h1>Debug Base de Données</h1>';
            $html .= '<style>table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>';
            
            // Test de connexion à la base
            $connection = $entityManager->getConnection();
            $html .= '<h2>1. Connexion Base de Données</h2>';
            $html .= '<p>✅ Connexion établie</p>';
            
            // Test de la structure de la table user
            $html .= '<h2>2. Structure table User</h2>';
            try {
                $stmt = $connection->executeQuery("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'user' ORDER BY ordinal_position");
                $columns = $stmt->fetchAllAssociative();
                
                $html .= '<table>';
                $html .= '<tr><th>Colonne</th><th>Type</th><th>Nullable</th></tr>';
                foreach ($columns as $col) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($col['column_name']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($col['data_type']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($col['is_nullable']) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</table>';
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur structure: ' . $e->getMessage() . '</p>';
            }
            
            // Test des utilisateurs
            $html .= '<h2>3. Test Utilisateurs</h2>';
            try {
                $users = $userRepository->findAll();
                $html .= '<p>Nombre d\'utilisateurs: ' . count($users) . '</p>';
                
                if (count($users) > 0) {
                    $html .= '<table>';
                    $html .= '<tr><th>ID</th><th>Username</th><th>Enabled</th><th>Erreur</th></tr>';
                    foreach ($users as $user) {
                        try {
                            $html .= '<tr>';
                            $html .= '<td>' . $user->getIdUser() . '</td>';
                            $html .= '<td>' . htmlspecialchars($user->getUsername()) . '</td>';
                            
                            // Test du champ enabled avec protection
                            try {
                                $enabled = method_exists($user, 'isEnabled') ? $user->isEnabled() : 'N/A';
                                $html .= '<td>' . ($enabled === true ? 'Oui' : ($enabled === false ? 'Non' : $enabled)) . '</td>';
                                $html .= '<td>OK</td>';
                            } catch (\Exception $e) {
                                $html .= '<td>Erreur</td>';
                                $html .= '<td>' . $e->getMessage() . '</td>';
                            }
                            
                            $html .= '</tr>';
                        } catch (\Exception $e) {
                            $html .= '<tr><td colspan="4">Erreur utilisateur: ' . $e->getMessage() . '</td></tr>';
                        }
                    }
                    $html .= '</table>';
                }
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur utilisateurs: ' . $e->getMessage() . '</p>';
            }
            
            // Test des migrations
            $html .= '<h2>4. Migrations appliquées</h2>';
            try {
                $stmt = $connection->executeQuery("SELECT version FROM doctrine_migration_versions ORDER BY version DESC LIMIT 5");
                $migrations = $stmt->fetchAllAssociative();
                
                $html .= '<ul>';
                foreach ($migrations as $migration) {
                    $html .= '<li>' . htmlspecialchars($migration['version']) . '</li>';
                }
                $html .= '</ul>';
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur migrations: ' . $e->getMessage() . '</p>';
            }
            
            return new Response($html, 200, ['Content-Type' => 'text/html']);
            
        } catch (\Exception $e) {
            return new Response('Erreur générale: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>', 500, ['Content-Type' => 'text/html']);
        }
    }
}