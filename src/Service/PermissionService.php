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

        // PROTECTION ADMIN : L'admin a TOUJOURS accès à tout pour éviter le softlock
        $user = $this->security->getUser();
        if ($user instanceof User && $this->hasMinimumLevel(100)) {
            return true;
        }

        // Vérifier d'abord si c'est une permission publique
        $permission = $this->permissionRepository->findOneBy(['route' => $route]);
        
        if (!$permission) {
            // Si la permission n'existe pas, on autorise seulement aux admins
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
}