<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RefreshUserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage
    ) {}

    #[Route('/public/refresh-user-session', name: 'app_refresh_user_session')]
    public function refreshUserSession(): Response
    {
        $result = [];
        
        try {
            $user = $this->getUser();
            
            if (!$user instanceof User) {
                $result['error'] = 'Utilisateur non connecté';
                return $this->json($result);
            }
            
            $result['before_refresh'] = [
                'user_id' => $user->getIdUser(),
                'username' => $user->getUserIdentifier(),
                'roles_count' => $user->getUserRoles()->count()
            ];
            
            // Rafraîchir l'entité utilisateur depuis la base de données
            $this->entityManager->refresh($user);
            
            // Forcer le rechargement des relations en accédant à la collection
            $user->getUserRoles()->toArray();
            
            $result['after_refresh'] = [
                'user_id' => $user->getIdUser(),
                'username' => $user->getUserIdentifier(),
                'roles_count' => $user->getUserRoles()->count(),
                'roles_details' => []
            ];
            
            foreach ($user->getUserRoles() as $role) {
                $result['after_refresh']['roles_details'][] = [
                    'id' => $role->getIdRole(),
                    'libelle' => $role->getLibelle(),
                    'hierarchy_level' => $role->getHierarchyLevel()
                ];
            }
            
            // Recréer le token de sécurité avec l'utilisateur rafraîchi
            $token = new UsernamePasswordToken(
                $user,
                'main',
                $user->getRoles()
            );
            $this->tokenStorage->setToken($token);
            
            $result['status'] = 'SUCCESS - Session utilisateur rafraîchie';
            
        } catch (\Exception $e) {
            $result['error'] = 'Erreur: ' . $e->getMessage();
        }
        
        return $this->json($result, 200, [], ['json_encode_options' => JSON_PRETTY_PRINT]);
    }
}