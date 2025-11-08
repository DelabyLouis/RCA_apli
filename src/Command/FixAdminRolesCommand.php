<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-admin-roles',
    description: 'Fix admin user roles by linking to Administrateur role'
)]
class FixAdminRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Trouver l'utilisateur admin
            $userRepo = $this->entityManager->getRepository(User::class);
            $adminUser = $userRepo->findOneBy(['username' => 'admin']);
            
            if (!$adminUser) {
                $io->error('Utilisateur admin non trouvé !');
                return Command::FAILURE;
            }
            
            $io->info(sprintf('Utilisateur admin trouvé - ID: %d', $adminUser->getIdUser()));
            $io->info(sprintf('Rôles actuels: %d', $adminUser->getUserRoles()->count()));
            
            // Trouver le rôle Administrateur
            $roleRepo = $this->entityManager->getRepository(Role::class);
            $adminRole = $roleRepo->findOneBy(['libelle' => 'Administrateur']);
            
            if (!$adminRole) {
                $io->error('Rôle Administrateur non trouvé !');
                return Command::FAILURE;
            }
            
            $io->info(sprintf('Rôle Administrateur trouvé - ID: %d, Niveau: %d', 
                $adminRole->getIdRole(), 
                $adminRole->getHierarchyLevel())
            );
            
            // Vérifier si le rôle est déjà lié
            if ($adminUser->getUserRoles()->contains($adminRole)) {
                $io->success('L\'utilisateur admin a déjà le rôle Administrateur !');
                return Command::SUCCESS;
            }
            
            // Ajouter le rôle
            $adminUser->getUserRoles()->add($adminRole);
            
            // Sauvegarder
            $this->entityManager->flush();
            
            $io->success('Rôle Administrateur ajouté à l\'utilisateur admin !');
            $io->info(sprintf('Rôles maintenant: %d', $adminUser->getUserRoles()->count()));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}