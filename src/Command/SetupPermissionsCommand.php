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
            // Pages publiques
            [
                'name' => 'Accueil',
                'route' => 'app_home',
                'description' => 'Accès à la page d\'accueil',
                'public' => true
            ],
            [
                'name' => 'Connexion',
                'route' => 'app_login',
                'description' => 'Accès à la page de connexion',
                'public' => true
            ],
            
            // Fonctionnalités principales
            [
                'name' => 'Gestion Exercices',
                'route' => 'app_exercice_index',
                'description' => 'Accès à la gestion des exercices',
                'public' => false
            ],
            [
                'name' => 'Gestion Transactions',
                'route' => 'app_transaction_index',
                'description' => 'Accès à la gestion des transactions',
                'public' => false
            ],
            [
                'name' => 'Nouvelle Transaction',
                'route' => 'app_transaction_new',
                'description' => 'Créer une nouvelle transaction',
                'public' => false
            ],
            [
                'name' => 'Gestion Livret',
                'route' => 'app_livret_index',
                'description' => 'Accès au livret d\'épargne',
                'public' => false
            ],
            [
                'name' => 'Transfert Livret',
                'route' => 'app_livret_transfert',
                'description' => 'Effectuer des transferts vers/depuis le livret',
                'public' => false
            ],
            [
                'name' => 'Attestations Fiscales',
                'route' => 'app_attestation_fiscale_index',
                'description' => 'Accès aux attestations fiscales',
                'public' => false
            ],
            
            // Gestion des entités
            [
                'name' => 'Gestion Personnes',
                'route' => 'app_personne_index',
                'description' => 'Accès à la gestion des personnes',
                'public' => false
            ],
            [
                'name' => 'Nouvelle Personne',
                'route' => 'app_personne_new',
                'description' => 'Créer une nouvelle personne',
                'public' => false
            ],
            [
                'name' => 'Gestion Entreprises',
                'route' => 'app_entreprise_index',
                'description' => 'Accès à la gestion des entreprises',
                'public' => false
            ],
            [
                'name' => 'Nouvelle Entreprise',
                'route' => 'app_entreprise_new',
                'description' => 'Créer une nouvelle entreprise',
                'public' => false
            ],
            
            // Administration
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
                'name' => 'Gestion Types Transaction',
                'route' => 'app_type_transaction_index',
                'description' => 'Accès à la gestion des types de transaction',
                'public' => false
            ],
            [
                'name' => 'Gestion Modes Paiement',
                'route' => 'app_mode_de_paiement_index',
                'description' => 'Accès à la gestion des modes de paiement',
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

        // Attribuer des permissions par défaut aux rôles existants
        foreach ($roles as $role) {
            // Effacer les permissions existantes pour réassigner
            $role->getPermissions()->clear();
            
            // Permissions communes à tous (pages publiques + accueil)
            foreach ($permissionEntities as $permission) {
                if (in_array($permission->getRoute(), ['app_home', 'app_login'])) {
                    $role->addPermission($permission);
                }
            }
            
            // Permissions selon le niveau hiérarchique
            if ($role->getHierarchyLevel() >= 100) {
                // Admin : toutes les permissions
                foreach ($permissionEntities as $permission) {
                    $role->addPermission($permission);
                }
            } elseif ($role->getHierarchyLevel() >= 50) {
                // Utilisateur standard : fonctionnalités principales sauf administration
                $userRoutes = [
                    'app_home', 'app_login', 'app_exercice_index', 'app_transaction_index', 
                    'app_transaction_new', 'app_livret_index', 'app_livret_transfert',
                    'app_attestation_fiscale_index', 'app_personne_index', 'app_personne_new',
                    'app_entreprise_index', 'app_entreprise_new'
                ];
                
                foreach ($permissionEntities as $permission) {
                    if (in_array($permission->getRoute(), $userRoutes)) {
                        $role->addPermission($permission);
                    }
                }
            } else {
                // Invité : accès très limité
                $guestRoutes = ['app_home', 'app_login', 'app_transaction_index', 'app_livret_index'];
                
                foreach ($permissionEntities as $permission) {
                    if (in_array($permission->getRoute(), $guestRoutes)) {
                        $role->addPermission($permission);
                    }
                }
            }
        }

        $this->entityManager->flush();

        $io->success('Système de permissions et rôles configuré avec succès !');
        $io->info('Permissions attribuées automatiquement selon les niveaux hiérarchiques :');
        $io->info('- Admin (100+) : Toutes les permissions');
        $io->info('- Utilisateur (50+) : Fonctionnalités principales');
        $io->info('- Invité (10+) : Accès limité en lecture');
        $io->info('Vous pouvez maintenant aller sur /role pour ajuster les permissions.');

        return Command::SUCCESS;
    }
}