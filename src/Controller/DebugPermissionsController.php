<?php

namespace App\Controller;

use App\Repository\PermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugPermissionsController extends AbstractController
{
    #[Route('/debug-permissions', name: 'debug_permissions')]
    public function debugPermissions(
        PermissionRepository $permissionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $html = '<h1>Debug Permissions</h1>';
            $html .= '<style>table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>';
            
            // Vérifier la structure de la table permissions
            $connection = $entityManager->getConnection();
            $html .= '<h2>1. Structure table Permission</h2>';
            try {
                $stmt = $connection->executeQuery("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'permission' ORDER BY ordinal_position");
                $columns = $stmt->fetchAllAssociative();
                
                if (empty($columns)) {
                    $html .= '<p>❌ Table permission n\'existe pas</p>';
                } else {
                    $html .= '<table>';
                    $html .= '<tr><th>Colonne</th><th>Type</th></tr>';
                    foreach ($columns as $col) {
                        $html .= '<tr>';
                        $html .= '<td>' . htmlspecialchars($col['column_name']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($col['data_type']) . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                }
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur structure: ' . $e->getMessage() . '</p>';
            }
            
            // Lister les permissions
            $html .= '<h2>2. Permissions en base</h2>';
            try {
                $permissions = $permissionRepository->findAll();
                $html .= '<p>Nombre de permissions: ' . count($permissions) . '</p>';
                
                if (count($permissions) > 0) {
                    $html .= '<table>';
                    $html .= '<tr><th>ID</th><th>Name</th><th>Description</th><th>Route</th></tr>';
                    foreach ($permissions as $permission) {
                        $html .= '<tr>';
                        $html .= '<td>' . $permission->getId() . '</td>';
                        $html .= '<td>' . htmlspecialchars($permission->getName()) . '</td>';
                        $html .= '<td>' . htmlspecialchars($permission->getDescription() ?? 'N/A') . '</td>';
                        $html .= '<td>' . htmlspecialchars($permission->getRoute() ?? 'N/A') . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                } else {
                    $html .= '<p>⚠️ Aucune permission trouvée</p>';
                    $html .= '<h3>Création automatique de permissions de base:</h3>';
                    
                    // Créer quelques permissions de base
                    $basicPermissions = [
                        ['name' => 'view_users', 'description' => 'Voir la liste des utilisateurs', 'route' => 'app_user_index'],
                        ['name' => 'edit_users', 'description' => 'Modifier les utilisateurs', 'route' => 'app_user_edit'],
                        ['name' => 'view_transactions', 'description' => 'Voir les transactions', 'route' => 'app_transaction_index'],
                        ['name' => 'edit_transactions', 'description' => 'Modifier les transactions', 'route' => 'app_transaction_edit'],
                        ['name' => 'view_livret', 'description' => 'Voir le livret', 'route' => 'app_livret_index'],
                        ['name' => 'admin_access', 'description' => 'Accès administration', 'route' => 'app_admin'],
                    ];
                    
                    foreach ($basicPermissions as $permData) {
                        try {
                            $permission = new \App\Entity\Permission();
                            $permission->setName($permData['name']);
                            $permission->setDescription($permData['description']);
                            $permission->setRoute($permData['route']);
                            
                            $entityManager->persist($permission);
                            $html .= '<p>✅ Permission créée: ' . $permData['name'] . '</p>';
                        } catch (\Exception $e) {
                            $html .= '<p>❌ Erreur création ' . $permData['name'] . ': ' . $e->getMessage() . '</p>';
                        }
                    }
                    
                    try {
                        $entityManager->flush();
                        $html .= '<p><strong>✅ Permissions de base créées avec succès!</strong></p>';
                    } catch (\Exception $e) {
                        $html .= '<p>❌ Erreur sauvegarde: ' . $e->getMessage() . '</p>';
                    }
                }
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur permissions: ' . $e->getMessage() . '</p>';
            }
            
            return new Response($html, 200, ['Content-Type' => 'text/html']);
            
        } catch (\Exception $e) {
            return new Response('Erreur générale: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>', 500, ['Content-Type' => 'text/html']);
        }
    }
}