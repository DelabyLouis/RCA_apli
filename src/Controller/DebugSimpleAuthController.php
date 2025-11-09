<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugSimpleAuthController extends AbstractController
{
    #[Route('/debug-auth-simple', name: 'debug_auth_simple')]
    public function debugAuthSimple(UserRepository $userRepository): Response
    {
        try {
            $users = $userRepository->findAll();
            
            $html = '<!DOCTYPE html><html><head><title>Debug Auth</title></head><body>';
            $html .= '<h1>Debug Authentification Simple</h1>';
            
            $html .= '<h2>Nombre d\'utilisateurs: ' . count($users) . '</h2>';
            
            foreach ($users as $user) {
                $html .= '<div style="border:1px solid #ccc;margin:10px;padding:10px;">';
                $html .= '<h3>Utilisateur ID: ' . $user->getIdUser() . '</h3>';
                $html .= '<p><strong>Username:</strong> ' . htmlspecialchars($user->getUsername()) . '</p>';
                
                // Password hash
                $password = $user->getPassword();
                $html .= '<p><strong>Password Hash:</strong> ' . substr($password, 0, 50) . '... (longueur: ' . strlen($password) . ')</p>';
                
                // Rôles
                try {
                    $roleCount = count($user->getUserRoles());
                    $html .= '<p><strong>Nombre de rôles:</strong> ' . $roleCount . '</p>';
                    
                    if ($roleCount > 0) {
                        $html .= '<ul>';
                        foreach ($user->getUserRoles() as $role) {
                            $html .= '<li>' . htmlspecialchars($role->getLibelle()) . '</li>';
                        }
                        $html .= '</ul>';
                    }
                } catch (\Exception $e) {
                    $html .= '<p style="color:red;">Erreur rôles: ' . $e->getMessage() . '</p>';
                }
                
                // Personne
                try {
                    $personne = $user->getPersonne();
                    if ($personne) {
                        $html .= '<p><strong>Personne:</strong> ' . htmlspecialchars($personne->getPrenom() . ' ' . $personne->getNom()) . '</p>';
                    } else {
                        $html .= '<p><strong>Personne:</strong> Aucune</p>';
                    }
                } catch (\Exception $e) {
                    $html .= '<p style="color:red;">Erreur personne: ' . $e->getMessage() . '</p>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</body></html>';
            
            return new Response($html);
            
        } catch (\Exception $e) {
            return new Response(
                '<h1>Erreur de debug</h1><p>' . $e->getMessage() . '</p><pre>' . $e->getTraceAsString() . '</pre>',
                200,
                ['Content-Type' => 'text/html']
            );
        }
    }
}