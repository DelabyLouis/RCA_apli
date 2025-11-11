<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CustomUserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Essayer d'abord par username (seulement si utilisateur activé)
        $user = $this->userRepository->findOneBy([
            'username' => $identifier, 
            'enabled' => true
        ]);
        
        // Si pas trouvé par username, essayer par email de la personne
        if (!$user) {
            $user = $this->userRepository->createQueryBuilder('u')
                ->join('u.personne', 'p')
                ->where('p.email = :email')
                ->andWhere('u.enabled = :enabled')
                ->setParameter('email', $identifier)
                ->setParameter('enabled', true)
                ->getQuery()
                ->getOneOrNullResult();
        }

        if (!$user) {
            throw new UserNotFoundException(sprintf('User "%s" not found or disabled.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UserNotFoundException('Invalid user class.');
        }

        // Lors du refresh, on cherche par ID pour être sûr
        $refreshedUser = $this->userRepository->find($user->getIdUser());
        
        if (!$refreshedUser || !$refreshedUser->isEnabled()) {
            throw new UserNotFoundException(sprintf('User with ID "%s" not found or disabled.', $user->getIdUser()));
        }

        // Log pour debug
        error_log("CustomUserProvider::refreshUser - User refreshed successfully: " . $refreshedUser->getUsername());

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}