<?php

namespace App\Command;

use App\Entity\Permission;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-permissions',
    description: 'Créer les permissions et rôles par défaut'
)]
class SetupPermissionsCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si des permissions existent déjà
        $existingPermissions = $this->entityManager->getRepository(Permission::class)->count([]);
        
        if ($existingPermissions > 0) {
            $io->info('Des permissions existent déjà. Mise à jour uniquement.');
        }

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
            ]
        ];

        $permissionEntities = [];
        $io->progressStart(count($permissions));

        foreach ($permissions as $permData) {
            // Vérifier si la permission existe déjà
            $existingPermission = $this->entityManager->getRepository(Permission::class)
                ->findOneBy(['route' => $permData['route']]);
            
            if (!$existingPermission) {
                $permission = new Permission();
                $permission->setName($permData['name']);
                $permission->setRoute($permData['route']);
                $permission->setDescription($permData['description']);
                $permission->setPublicAccess($permData['public']);
                
                $this->entityManager->persist($permission);
                $permissionEntities[] = $permission;
            } else {
                $permissionEntities[] = $existingPermission;
            }
            
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success('Permissions créées/mises à jour avec succès !');

        // Mise à jour des rôles existants avec hierarchy_level
        $roles = $this->entityManager->getRepository(Role::class)->findAll();
        
        foreach ($roles as $role) {
            if ($role->getHierarchyLevel() === null || $role->getHierarchyLevel() === 0) {
                // Assigner des niveaux par défaut basés sur le nom
                switch (strtolower($role->getLibelle())) {
                    case 'admin':
                    case 'administrateur':
                        $role->setHierarchyLevel(100);
                        break;
                    case 'user':
                    case 'utilisateur':
                        $role->setHierarchyLevel(50);
                        break;
                    default:
                        $role->setHierarchyLevel(10);
                        break;
                }
            }
        }

        $this->entityManager->flush();

        $io->success('Système de permissions et rôles configuré avec succès !');
        $io->info('Vous pouvez maintenant aller sur /role pour gérer les permissions.');

        return Command::SUCCESS;
    }
}