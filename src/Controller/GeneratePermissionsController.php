<?php

namespace App\Controller;

use App\Entity\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GeneratePermissionsController extends AbstractController
{
    #[Route('/generate-all-permissions', name: 'generate_all_permissions')]
    public function generateAllPermissions(EntityManagerInterface $entityManager): Response
    {
        try {
            $html = '<h1>Génération des Permissions Complètes</h1>';
            $html .= '<style>table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>';
            
            // Définir toutes les entités et leurs permissions
            $entitiesPermissions = [
                // Gestion des utilisateurs
                'user' => [
                    'view' => 'Voir la liste des utilisateurs',
                    'create' => 'Créer de nouveaux utilisateurs',
                    'edit' => 'Modifier les utilisateurs existants',
                    'delete' => 'Supprimer des utilisateurs',
                    'activate' => 'Activer/désactiver des utilisateurs'
                ],
                
                // Gestion des rôles et permissions
                'role' => [
                    'view' => 'Voir la liste des rôles',
                    'create' => 'Créer de nouveaux rôles',
                    'edit' => 'Modifier les rôles existants',
                    'delete' => 'Supprimer des rôles'
                ],
                
                'permission' => [
                    'view' => 'Voir la liste des permissions',
                    'manage' => 'Gérer les permissions système'
                ],
                
                // Gestion financière
                'exercice' => [
                    'view' => 'Voir la liste des exercices',
                    'create' => 'Créer de nouveaux exercices',
                    'edit' => 'Modifier les exercices',
                    'delete' => 'Supprimer des exercices',
                    'close' => 'Clôturer des exercices'
                ],
                
                'transaction' => [
                    'view' => 'Voir la liste des transactions',
                    'create' => 'Créer de nouvelles transactions',
                    'edit' => 'Modifier les transactions',
                    'delete' => 'Supprimer des transactions',
                    'validate' => 'Valider des transactions'
                ],
                
                'type_transaction' => [
                    'view' => 'Voir les types de transactions',
                    'create' => 'Créer de nouveaux types',
                    'edit' => 'Modifier les types existants',
                    'delete' => 'Supprimer des types'
                ],
                
                'mode_paiement' => [
                    'view' => 'Voir les modes de paiement',
                    'create' => 'Créer de nouveaux modes',
                    'edit' => 'Modifier les modes existants',
                    'delete' => 'Supprimer des modes'
                ],
                
                // Gestion des contacts
                'personne' => [
                    'view' => 'Voir la liste des personnes',
                    'create' => 'Créer de nouvelles personnes',
                    'edit' => 'Modifier les informations personnelles',
                    'delete' => 'Supprimer des personnes'
                ],
                
                'entreprise' => [
                    'view' => 'Voir la liste des entreprises',
                    'create' => 'Créer de nouvelles entreprises',
                    'edit' => 'Modifier les entreprises',
                    'delete' => 'Supprimer des entreprises'
                ],
                
                // Fonctionnalités spéciales
                'livret' => [
                    'view' => 'Accéder au livret',
                    'edit' => 'Modifier les entrées du livret'
                ],
                
                'attestation' => [
                    'view' => 'Voir les attestations fiscales',
                    'generate' => 'Générer des attestations'
                ],
                
                'import_export' => [
                    'import' => 'Importer des données',
                    'export' => 'Exporter des données',
                    'clean' => 'Nettoyer et réimporter'
                ],
                
                'reports' => [
                    'view' => 'Voir les rapports financiers',
                    'generate' => 'Générer des rapports personnalisés'
                ],
                
                // Administration
                'admin' => [
                    'access' => 'Accès à l\'administration générale',
                    'system' => 'Gestion système et configuration',
                    'logs' => 'Voir les logs système',
                    'backup' => 'Sauvegardes et restauration'
                ],
                
                'rgpd' => [
                    'view' => 'Voir les données RGPD',
                    'manage' => 'Gérer les consentements RGPD'
                ]
            ];
            
            $createdCount = 0;
            $existingCount = 0;
            $errorCount = 0;
            
            $html .= '<h2>Génération des permissions:</h2>';
            $html .= '<table>';
            $html .= '<tr><th>Permission</th><th>Description</th><th>Route</th><th>Statut</th></tr>';
            
            foreach ($entitiesPermissions as $entity => $actions) {
                foreach ($actions as $action => $description) {
                    $permissionName = $entity . '_' . $action;
                    $route = 'app_' . $entity . '_' . $action;
                    
                    try {
                        // Vérifier si la permission existe déjà
                        $existingPermission = $entityManager->getRepository(Permission::class)
                            ->findOneBy(['name' => $permissionName]);
                        
                        if ($existingPermission) {
                            $html .= '<tr>';
                            $html .= '<td>' . $permissionName . '</td>';
                            $html .= '<td>' . $description . '</td>';
                            $html .= '<td>' . $route . '</td>';
                            $html .= '<td><span style="color: orange;">Existe déjà</span></td>';
                            $html .= '</tr>';
                            $existingCount++;
                        } else {
                            // Créer la nouvelle permission
                            $permission = new Permission();
                            $permission->setName($permissionName);
                            $permission->setDescription($description);
                            $permission->setRoute($route);
                            $permission->setPublicAccess(false);
                            
                            $entityManager->persist($permission);
                            
                            $html .= '<tr>';
                            $html .= '<td>' . $permissionName . '</td>';
                            $html .= '<td>' . $description . '</td>';
                            $html .= '<td>' . $route . '</td>';
                            $html .= '<td><span style="color: green;">✅ Créée</span></td>';
                            $html .= '</tr>';
                            $createdCount++;
                        }
                        
                    } catch (\Exception $e) {
                        $html .= '<tr>';
                        $html .= '<td>' . $permissionName . '</td>';
                        $html .= '<td colspan="3"><span style="color: red;">❌ Erreur: ' . $e->getMessage() . '</span></td>';
                        $html .= '</tr>';
                        $errorCount++;
                    }
                }
            }
            
            $html .= '</table>';
            
            // Sauvegarder les nouvelles permissions
            if ($createdCount > 0) {
                try {
                    $entityManager->flush();
                    $html .= '<div style="padding: 15px; margin: 20px 0; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;">';
                    $html .= '<h3 style="color: #155724;">✅ Permissions créées avec succès!</h3>';
                    $html .= '<p><strong>' . $createdCount . '</strong> nouvelles permissions créées</p>';
                    $html .= '<p><strong>' . $existingCount . '</strong> permissions existantes</p>';
                    if ($errorCount > 0) {
                        $html .= '<p style="color: red;"><strong>' . $errorCount . '</strong> erreurs</p>';
                    }
                    $html .= '</div>';
                } catch (\Exception $e) {
                    $html .= '<div style="padding: 15px; margin: 20px 0; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">';
                    $html .= '<h3 style="color: #721c24;">❌ Erreur de sauvegarde</h3>';
                    $html .= '<p>' . $e->getMessage() . '</p>';
                    $html .= '</div>';
                }
            }
            
            $html .= '<h2>Actions suivantes:</h2>';
            $html .= '<ul>';
            $html .= '<li><a href="/role/new">Créer un nouveau rôle</a> avec les permissions appropriées</li>';
            $html .= '<li><a href="/role">Gérer les rôles existants</a></li>';
            $html .= '<li><a href="/user">Assigner les rôles aux utilisateurs</a></li>';
            $html .= '</ul>';
            
            return new Response($html, 200, ['Content-Type' => 'text/html']);
            
        } catch (\Exception $e) {
            return new Response('Erreur générale: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>', 500, ['Content-Type' => 'text/html']);
        }
    }
}