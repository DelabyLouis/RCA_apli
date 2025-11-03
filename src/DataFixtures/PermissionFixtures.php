<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PermissionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des permissions principales
        $permissions = [
            [
                'name' => 'Accueil',
                'route' => 'app_home_index',
                'description' => 'Accès à la page d\'accueil',
                'public' => true
            ],
            [
                'name' => 'Connexion',
                'route' => 'app_login',
                'description' => 'Accès à la page de connexion',
                'public' => true
            ],
            [
                'name' => 'Gestion Personnes',
                'route' => 'app_personne_index',
                'description' => 'Accès à la gestion des personnes',
                'public' => false
            ],
            [
                'name' => 'Gestion Entreprises',
                'route' => 'app_entreprise_index',
                'description' => 'Accès à la gestion des entreprises',
                'public' => false
            ],
            [
                'name' => 'Gestion Utilisateurs',
                'route' => 'app_user_index',
                'description' => 'Accès à la gestion des utilisateurs',
                'public' => false
            ],
            [
                'name' => 'Gestion Rôles',
                'route' => 'app_role_index',
                'description' => 'Accès à la gestion des rôles',
                'public' => false
            ],
            [
                'name' => 'Gestion Permissions',
                'route' => 'app_permission_index',
                'description' => 'Accès à la gestion des permissions',
                'public' => false
            ],
            [
                'name' => 'Gestion Transactions',
                'route' => 'app_transaction_index',
                'description' => 'Accès à la gestion des transactions',
                'public' => false
            ],
        ];

        $permissionEntities = [];
        foreach ($permissions as $permData) {
            $permission = new Permission();
            $permission->setName($permData['name']);
            $permission->setRoute($permData['route']);
            $permission->setDescription($permData['description']);
            $permission->setPublicAccess($permData['public']);
            
            $manager->persist($permission);
            $permissionEntities[] = $permission;
        }

        // Création des rôles avec hiérarchie
        $admin = new Role();
        $admin->setLibelle('Administrateur');
        $admin->setDescription('Accès complet à toutes les fonctionnalités');
        $admin->setHierarchyLevel(100);
        
        // L'admin a toutes les permissions
        foreach ($permissionEntities as $permission) {
            $admin->addPermission($permission);
        }
        
        $manager->persist($admin);

        $utilisateur = new Role();
        $utilisateur->setLibelle('Utilisateur');
        $utilisateur->setDescription('Accès standard aux fonctionnalités de base');
        $utilisateur->setHierarchyLevel(50);
        
        // L'utilisateur a accès aux fonctions de base (pas admin)
        foreach ($permissionEntities as $permission) {
            if (!in_array($permission->getRoute(), ['app_user_index', 'app_role_index', 'app_permission_index'])) {
                $utilisateur->addPermission($permission);
            }
        }
        
        $manager->persist($utilisateur);

        $invite = new Role();
        $invite->setLibelle('Invité');
        $invite->setDescription('Accès limité en lecture seule');
        $invite->setHierarchyLevel(10);
        
        // L'invité n'a accès qu'aux pages publiques et à l'accueil
        foreach ($permissionEntities as $permission) {
            if (in_array($permission->getRoute(), ['app_home_index', 'app_login'])) {
                $invite->addPermission($permission);
            }
        }
        
        $manager->persist($invite);

        $manager->flush();
    }
}