<?php

namespace App\Service;

use App\Entity\Permission;
use App\Entity\User;
use App\Repository\PermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private PermissionRepository $permissionRepository
    ) {}

    /**
     * Vérifie si l'utilisateur actuel a accès à une route
     */
    public function hasAccess(string $route): bool
    {
        // Routes toujours autorisées
        $alwaysAllowed = [
            'app_logout',
            'app_register',
            '_wdt',
            '_profiler',
            '_preview_error'
        ];
        
        if (in_array($route, $alwaysAllowed)) {
            return true;
        }

        // TOUJOURS autoriser l'accueil et la connexion (pas de vérification de permissions)
        if (in_array($route, ['app_home', 'app_login'])) {
            return true;
        }

        // PROTECTION ADMIN : L'admin a TOUJOURS accès à tout pour éviter le softlock
        $user = $this->security->getUser();
        if ($user instanceof User && $this->hasMinimumLevel(100)) {
            return true;
        }

        // Vérifier d'abord si c'est une permission publique OU mapper vers une permission regroupée
        $permission = $this->permissionRepository->findOneBy(['route' => $route]);
        
        // Si pas de permission exacte, essayer le mapping vers permissions regroupées
        if (!$permission) {
            $mappedPermissionName = $this->mapRouteToPermissionGroup($route);
            if ($mappedPermissionName) {
                $permission = $this->permissionRepository->findOneBy(['name' => $mappedPermissionName]);
            }
        }
        
        if (!$permission) {
            // Si aucune permission trouvée, on autorise seulement aux admins
            $user = $this->security->getUser();
            if (!$user instanceof User) {
                return false;
            }
            
            // Autoriser si l'utilisateur est admin (niveau 100+)
            return $this->hasMinimumLevel(100);
        }
        
        // Si c'est public, tout le monde y a accès
        if ($permission->isPublicAccess()) {
            return true;
        }
        
        // Si l'utilisateur n'est pas connecté et que ce n'est pas public
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }
        
        // Vérifier si l'utilisateur a un rôle qui donne accès à cette permission
        foreach ($user->getUserRoles() as $role) {
            if ($role->getPermissions()->contains($permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Récupère toutes les permissions publiques
     */
    public function getPublicPermissions(): array
    {
        return $this->permissionRepository->findBy(['public_access' => true]);
    }

    /**
     * Récupère les permissions d'un utilisateur
     */
    public function getUserPermissions(User $user = null): array
    {
        if (!$user) {
            $user = $this->security->getUser();
        }
        
        if (!$user instanceof User) {
            return $this->getPublicPermissions();
        }
        
        $permissions = [];
        
        // Ajouter les permissions publiques
        foreach ($this->getPublicPermissions() as $permission) {
            $permissions[] = $permission;
        }
        
        // Ajouter les permissions des rôles de l'utilisateur
        foreach ($user->getUserRoles() as $role) {
            foreach ($role->getPermissions() as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
        }
        
        return $permissions;
    }

    /**
     * Vérifie si l'utilisateur a le niveau hiérarchique minimum
     */
    public function hasMinimumLevel(int $minimumLevel): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }
        
        $maxLevel = 0;
        foreach ($user->getUserRoles() as $role) {
            if ($role->getHierarchyLevel() > $maxLevel) {
                $maxLevel = $role->getHierarchyLevel();
            }
        }
        
        return $maxLevel >= $minimumLevel;
    }

    /**
     * Récupère le niveau hiérarchique maximum de l'utilisateur
     */
    public function getUserMaxLevel(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }
        
        $maxLevel = 0;
        foreach ($user->getUserRoles() as $role) {
            if ($role->getHierarchyLevel() > $maxLevel) {
                $maxLevel = $role->getHierarchyLevel();
            }
        }
        
        return $maxLevel;
    }

    /**
     * Mappe une route vers une permission regroupée
     */
    private function mapRouteToPermissionGroup(string $route): ?string
    {
        // Mapping des routes vers les permissions regroupées
        $routeMapping = [
            // 👥 GESTION DES PERSONNES ET ENTREPRISES
            'app_entreprise_index' => 'Consulter Entreprises',
            'app_entreprise_show' => 'Consulter Entreprises',
            'app_entreprise_new' => 'Gérer Entreprises',
            'app_entreprise_create' => 'Gérer Entreprises',
            'app_entreprise_edit' => 'Gérer Entreprises',
            'app_entreprise_update' => 'Gérer Entreprises',
            'app_entreprise_delete' => 'Gérer Entreprises',
            
            'app_personne_index' => 'Consulter Personnes',
            'app_personne_show' => 'Consulter Personnes', 
            'app_personne_new' => 'Gérer Personnes',
            'app_personne_create' => 'Gérer Personnes',
            'app_personne_edit' => 'Gérer Personnes',
            'app_personne_update' => 'Gérer Personnes',
            'app_personne_delete' => 'Gérer Personnes',
            
            // 💰 GESTION FINANCIÈRE
            'app_exercice_index' => 'Consulter Finances',
            'app_exercice_show' => 'Consulter Finances',
            'app_transaction_index' => 'Consulter Finances',
            'app_transaction_show' => 'Consulter Finances',
            'app_type_transaction_index' => 'Consulter Finances',
            'app_mode_de_paiement_index' => 'Consulter Finances',
            
            'app_exercice_new' => 'Gérer Finances',
            'app_exercice_create' => 'Gérer Finances',
            'app_exercice_edit' => 'Gérer Finances',
            'app_exercice_update' => 'Gérer Finances',
            'app_exercice_delete' => 'Gérer Finances',
            'app_transaction_new' => 'Gérer Finances',
            'app_transaction_create' => 'Gérer Finances',
            'app_transaction_edit' => 'Gérer Finances',
            'app_transaction_update' => 'Gérer Finances',
            'app_transaction_delete' => 'Gérer Finances',
            'app_type_transaction_new' => 'Gérer Finances',
            'app_type_transaction_edit' => 'Gérer Finances',
            'app_type_transaction_delete' => 'Gérer Finances',
            'app_mode_de_paiement_new' => 'Gérer Finances',
            'app_mode_de_paiement_edit' => 'Gérer Finances',
            'app_mode_de_paiement_delete' => 'Gérer Finances',
            
            // 👤 ADMINISTRATION SYSTÈME
            'app_user_index' => 'Consulter Utilisateurs',
            'app_user_show' => 'Consulter Utilisateurs',
            'app_role_index' => 'Consulter Permissions',
            'app_role_show' => 'Consulter Permissions',
            'app_permission_index' => 'Consulter Permissions',
            
            'app_user_new' => 'Gérer Utilisateurs',
            'app_user_create' => 'Gérer Utilisateurs', 
            'app_user_edit' => 'Gérer Utilisateurs',
            'app_user_update' => 'Gérer Utilisateurs',
            'app_user_delete' => 'Gérer Utilisateurs',
            'app_role_new' => 'Gérer Permissions',
            'app_role_create' => 'Gérer Permissions',
            'app_role_edit' => 'Gérer Permissions',
            'app_role_update' => 'Gérer Permissions',
            'app_role_delete' => 'Gérer Permissions',
            'app_permission_manage' => 'Gérer Permissions',
            
            // 🔧 ADMINISTRATION TECHNIQUE
            'maintenance_database_admin' => 'Administration Système',
        ];

        return $routeMapping[$route] ?? null;
    }
}