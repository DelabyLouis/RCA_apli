<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    #[Route('/debug/permissions', name: 'app_debug_permissions')]
    public function debugPermissions(): Response
    {
        $user = $this->getUser();
        
        $debug = [
            'user_connected' => $user !== null,
            'user_class' => $user ? get_class($user) : 'null',
            'user_id' => $user instanceof User ? $user->getIdUser() : 'null',
            'user_username' => $user ? $user->getUserIdentifier() : 'null',
        ];
        
        if ($user instanceof User) {
            $debug['user_roles_count'] = $user->getUserRoles()->count();
            $debug['user_roles'] = [];
            
            foreach ($user->getUserRoles() as $role) {
                $debug['user_roles'][] = [
                    'id' => $role->getIdRole(),
                    'libelle' => $role->getLibelle(),
                    'hierarchy_level' => $role->getHierarchyLevel(),
                    'permissions_count' => $role->getPermissions()->count(),
                ];
            }
            
            $debug['user_max_level'] = $this->permissionService->getUserMaxLevel();
            $debug['has_minimum_level_100'] = $this->permissionService->hasMinimumLevel(100);
            
            // Test des accès spécifiques
            $routesToTest = [
                'app_exercice_index',
                'app_transaction_index', 
                'app_livret_index',
                'app_personne_index',
                'app_user_index'
            ];
            
            $debug['route_access'] = [];
            foreach ($routesToTest as $route) {
                $debug['route_access'][$route] = $this->permissionService->hasAccess($route);
            }
        }
        
        return $this->render('debug/permissions.html.twig', [
            'debug' => $debug
        ]);
    }
}