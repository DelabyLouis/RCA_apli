<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminFixController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/public/fix-admin-roles', name: 'app_public_fix_admin_roles')]
    public function fixAdminRoles(): Response
    {
        $result = [];
        
        try {
            // Trouver l'utilisateur admin
            $userRepo = $this->entityManager->getRepository(User::class);
            $adminUser = $userRepo->findOneBy(['username' => 'admin']);
            
            if (!$adminUser) {
                $result['error'] = 'Utilisateur admin non trouvé !';
                return $this->json($result);
            }
            
            $result['admin_user_id'] = $adminUser->getIdUser();
            $result['current_roles_count'] = $adminUser->getUserRoles()->count();
            $result['current_roles'] = [];
            
            foreach ($adminUser->getUserRoles() as $role) {
                $result['current_roles'][] = [
                    'id' => $role->getIdRole(),
                    'libelle' => $role->getLibelle(),
                    'hierarchy_level' => $role->getHierarchyLevel()
                ];
            }
            
            // Trouver le rôle Administrateur
            $roleRepo = $this->entityManager->getRepository(Role::class);
            $adminRole = $roleRepo->findOneBy(['libelle' => 'Administrateur']);
            
            if (!$adminRole) {
                // Lister tous les rôles disponibles pour debug
                $allRoles = $roleRepo->findAll();
                $result['available_roles'] = [];
                foreach ($allRoles as $role) {
                    $result['available_roles'][] = [
                        'id' => $role->getIdRole(),
                        'libelle' => $role->getLibelle(),
                        'hierarchy_level' => $role->getHierarchyLevel()
                    ];
                }
                
                // Si aucun rôle n'existe, créer les rôles de base
                if (count($allRoles) === 0) {
                    $result['action'] = 'Creating missing roles...';
                    
                    // Créer les rôles de base
                    $rolesData = [
                        ['libelle' => 'Utilisateur', 'description' => 'Accès standard aux fonctionnalités de base', 'hierarchy_level' => 50],
                        ['libelle' => 'Administrateur', 'description' => 'Accès complet à toutes les fonctionnalités', 'hierarchy_level' => 100],
                    ];
                    
                    $createdRoles = [];
                    foreach ($rolesData as $data) {
                        $role = new Role();
                        $role->setLibelle($data['libelle']);
                        $role->setDescription($data['description']);
                        $role->setHierarchyLevel($data['hierarchy_level']);
                        $this->entityManager->persist($role);
                        $createdRoles[] = $data['libelle'];
                    }
                    
                    $this->entityManager->flush();
                    $result['created_roles'] = $createdRoles;
                    
                    // Maintenant récupérer le rôle Administrateur
                    $adminRole = $roleRepo->findOneBy(['libelle' => 'Administrateur']);
                }
                
                if (!$adminRole) {
                    $result['error'] = 'Rôle Administrateur non trouvé même après création !';
                    return $this->json($result);
                }
            }
            
            $result['admin_role_id'] = $adminRole->getIdRole();
            $result['admin_role_level'] = $adminRole->getHierarchyLevel();
            
            // Vérifier si le rôle est déjà lié
            if ($adminUser->getUserRoles()->contains($adminRole)) {
                $result['status'] = 'L\'utilisateur admin a déjà le rôle Administrateur !';
                return $this->json($result);
            }
            
            // Ajouter le rôle
            $adminUser->getUserRoles()->add($adminRole);
            
            // Sauvegarder
            $this->entityManager->flush();
            
            $result['status'] = 'SUCCESS - Rôle Administrateur ajouté à l\'utilisateur admin !';
            $result['new_roles_count'] = $adminUser->getUserRoles()->count();
            
        } catch (\Exception $e) {
            $result['error'] = 'Erreur: ' . $e->getMessage();
            $result['trace'] = $e->getTraceAsString();
        }
        
        return $this->json($result, 200, [], ['json_encode_options' => JSON_PRETTY_PRINT]);
    }
}