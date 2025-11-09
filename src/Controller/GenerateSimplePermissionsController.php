<?php

namespace App\Controller;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GenerateSimplePermissionsController extends AbstractController
{
    #[Route('/generate-simple-permissions', name: 'generate_simple_permissions')]
    public function generateSimplePermissions(EntityManagerInterface $entityManager): Response
    {
        try {
            $html = '<h1>Génération des Permissions Simplifiées</h1>';
            $html .= '<style>table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>';
            
            // Nettoyer d'abord les permissions existantes
            $html .= '<h2>1. Nettoyage des permissions existantes</h2>';
            try {
                $connection = $entityManager->getConnection();
                
                // Supprimer les associations role-permission
                $connection->executeStatement('DELETE FROM role_permission');
                $html .= '<p>✅ Associations rôles-permissions supprimées</p>';
                
                // Supprimer toutes les permissions
                $connection->executeStatement('DELETE FROM permission');
                $html .= '<p>✅ Permissions existantes supprimées</p>';
                
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur nettoyage: ' . $e->getMessage() . '</p>';
            }
            
            // Définir les entités avec seulement 2 permissions : view et modify
            $entitiesPermissions = [
                'user' => [
                    'view' => 'Voir la liste des utilisateurs',
                    'modify' => 'Créer, modifier et supprimer des utilisateurs'
                ],
                'role' => [
                    'view' => 'Voir la liste des rôles',
                    'modify' => 'Créer, modifier et supprimer des rôles'
                ],
                'exercice' => [
                    'view' => 'Voir la liste des exercices',
                    'modify' => 'Créer, modifier, supprimer et clôturer des exercices'
                ],
                'transaction' => [
                    'view' => 'Voir la liste des transactions',
                    'modify' => 'Créer, modifier et supprimer des transactions'
                ],
                'type_transaction' => [
                    'view' => 'Voir les types de transactions',
                    'modify' => 'Créer, modifier et supprimer des types'
                ],
                'mode_paiement' => [
                    'view' => 'Voir les modes de paiement',
                    'modify' => 'Créer, modifier et supprimer des modes'
                ],
                'personne' => [
                    'view' => 'Voir la liste des personnes',
                    'modify' => 'Créer, modifier et supprimer des personnes'
                ],
                'entreprise' => [
                    'view' => 'Voir la liste des entreprises',
                    'modify' => 'Créer, modifier et supprimer des entreprises'
                ],
                'livret' => [
                    'view' => 'Accéder au livret en lecture',
                    'modify' => 'Modifier les entrées du livret'
                ],
                'attestation' => [
                    'view' => 'Voir les attestations fiscales',
                    'modify' => 'Générer et gérer les attestations'
                ],
                'import_export' => [
                    'view' => 'Voir l\'historique des imports/exports',
                    'modify' => 'Importer, exporter et nettoyer les données'
                ],
                'reports' => [
                    'view' => 'Voir les rapports financiers',
                    'modify' => 'Générer et personnaliser les rapports'
                ],
                'admin' => [
                    'view' => 'Accéder aux outils d\'administration',
                    'modify' => 'Configuration système complète'
                ]
            ];
            
            $createdCount = 0;
            
            $html .= '<h2>2. Création des nouvelles permissions:</h2>';
            $html .= '<table>';
            $html .= '<tr><th>Permission</th><th>Description</th><th>Route</th><th>Statut</th></tr>';
            
            foreach ($entitiesPermissions as $entity => $actions) {
                foreach ($actions as $action => $description) {
                    $permissionName = $entity . '_' . $action;
                    $route = 'app_' . $entity . '_' . ($action === 'view' ? 'index' : $action);
                    
                    try {
                        // Créer la nouvelle permission
                        $permission = new Permission();
                        $permission->setName($permissionName);
                        $permission->setDescription($description);
                        $permission->setRoute($route);
                        $permission->setPublicAccess(false);
                        
                        $entityManager->persist($permission);
                        
                        $html .= '<tr>';
                        $html .= '<td><strong>' . $permissionName . '</strong></td>';
                        $html .= '<td>' . $description . '</td>';
                        $html .= '<td>' . $route . '</td>';
                        $html .= '<td><span style="color: green;">✅ Créée</span></td>';
                        $html .= '</tr>';
                        $createdCount++;
                        
                    } catch (\Exception $e) {
                        $html .= '<tr>';
                        $html .= '<td>' . $permissionName . '</td>';
                        $html .= '<td colspan="3"><span style="color: red;">❌ Erreur: ' . $e->getMessage() . '</span></td>';
                        $html .= '</tr>';
                    }
                }
            }
            
            $html .= '</table>';
            
            // Sauvegarder les nouvelles permissions
            try {
                $entityManager->flush();
                $html .= '<div style="padding: 15px; margin: 20px 0; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;">';
                $html .= '<h3 style="color: #155724;">✅ Permissions créées avec succès!</h3>';
                $html .= '<p><strong>' . $createdCount . '</strong> nouvelles permissions créées</p>';
                $html .= '<p>Système simplifié : <strong>2 permissions par entité</strong> (Voir + Modifier)</p>';
                $html .= '</div>';
            } catch (\Exception $e) {
                $html .= '<div style="padding: 15px; margin: 20px 0; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">';
                $html .= '<h3 style="color: #721c24;">❌ Erreur de sauvegarde</h3>';
                $html .= '<p>' . $e->getMessage() . '</p>';
                $html .= '</div>';
            }
            
            $html .= '<h2>3. Suggestions de rôles types:</h2>';
            $html .= '<div style="background-color: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;">';
            $html .= '<h4>🔹 Rôle "Lecteur"</h4>';
            $html .= '<p>Toutes les permissions "_view" pour consultation uniquement</p>';
            $html .= '<h4>🔹 Rôle "Comptable"</h4>';
            $html .= '<p>transaction_*, exercice_*, attestation_*, reports_* pour la gestion financière</p>';
            $html .= '<h4>🔹 Rôle "Gestionnaire"</h4>';
            $html .= '<p>Toutes les permissions sauf admin_* et user_*</p>';
            $html .= '<h4>🔹 Rôle "Administrateur"</h4>';
            $html .= '<p>Toutes les permissions pour un contrôle total</p>';
            $html .= '</div>';
            
            $html .= '<h2>Actions suivantes:</h2>';
            $html .= '<ul>';
            $html .= '<li><a href="/role/new" style="font-weight: bold;">Créer les rôles types</a> avec les permissions appropriées</li>';
            $html .= '<li><a href="/user/2/edit">Assigner le rôle "Membre"</a> à l\'utilisateur AZERTY</li>';
            $html .= '<li><a href="/user">Gérer tous les utilisateurs</a></li>';
            $html .= '</ul>';
            
            return new Response($html, 200, ['Content-Type' => 'text/html']);
            
        } catch (\Exception $e) {
            return new Response('Erreur générale: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>', 500, ['Content-Type' => 'text/html']);
        }
    }
}