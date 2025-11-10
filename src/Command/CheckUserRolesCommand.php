<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-user-roles',
    description: 'Check user roles',
)]
class CheckUserRolesCommand extends Command
{
    public function __construct(private UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $users = $this->userRepository->findAll();
        
        $io->title('Utilisateurs et leurs rôles');
        
        foreach ($users as $user) {
            $io->section("Utilisateur: {$user->getUsername()}");
            $io->text("ID: {$user->getIdUser()}");
            $io->text("Enabled: " . ($user->isEnabled() ? 'Oui' : 'Non'));
            $io->text("Rôles Symfony: " . implode(', ', $user->getRoles()));
            
            // Rôles via l'entité
            $userRoles = $user->getUserRoles();
            $roleNames = [];
            foreach ($userRoles as $role) {
                $roleNames[] = $role->getLibelle();
            }
            $io->text("Rôles entité: " . (empty($roleNames) ? 'Aucun' : implode(', ', $roleNames)));
        }

        return Command::SUCCESS;
    }
}