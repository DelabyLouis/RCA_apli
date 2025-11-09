<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugUserController extends AbstractController
{
    #[Route('/debug-user/{id}', name: 'debug_user')]
    public function debugUser(int $id, UserRepository $userRepository): Response
    {
        try {
            $user = $userRepository->findOneBy(['id_user' => $id]);
            
            if (!$user) {
                return new Response('Utilisateur non trouvé', 404);
            }
            
            $html = '<h1>Debug Utilisateur ID: ' . $id . '</h1>';
            $html .= '<style>table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>';
            
            $html .= '<h2>Informations de base</h2>';
            $html .= '<table>';
            $html .= '<tr><th>Propriété</th><th>Valeur</th></tr>';
            $html .= '<tr><td>ID</td><td>' . $user->getIdUser() . '</td></tr>';
            $html .= '<tr><td>Username</td><td>' . htmlspecialchars($user->getUsername()) . '</td></tr>';
            $html .= '<tr><td>Password Hash</td><td>' . substr($user->getPassword(), 0, 30) . '...</td></tr>';
            
            // Test Personne
            try {
                $personne = $user->getPersonne();
                $html .= '<tr><td>Personne</td><td>' . 
                         ($personne ? $personne->getPrenom() . ' ' . $personne->getNom() : 'Aucune') . 
                         '</td></tr>';
            } catch (\Exception $e) {
                $html .= '<tr><td>Personne</td><td>Erreur: ' . $e->getMessage() . '</td></tr>';
            }
            
            $html .= '</table>';
            
            // Test Rôles
            $html .= '<h2>Rôles</h2>';
            try {
                $roles = $user->getUserRoles();
                $html .= '<p>Nombre de rôles: ' . count($roles) . '</p>';
                
                if (count($roles) > 0) {
                    $html .= '<ul>';
                    foreach ($roles as $role) {
                        $html .= '<li>' . htmlspecialchars($role->getLibelle()) . '</li>';
                    }
                    $html .= '</ul>';
                } else {
                    $html .= '<p><strong>Aucun rôle assigné !</strong></p>';
                }
            } catch (\Exception $e) {
                $html .= '<p>Erreur lors de la récupération des rôles: ' . $e->getMessage() . '</p>';
            }
            
            // Test FormType
            $html .= '<h2>Test du formulaire UserEditType</h2>';
            try {
                $form = $this->createForm(\App\Form\UserEditType::class, $user);
                $html .= '<p>✅ Formulaire créé sans erreur</p>';
                
                // Test du rendu du formulaire
                $formView = $form->createView();
                $html .= '<p>✅ Vue du formulaire créée sans erreur</p>';
                
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur lors de la création du formulaire: ' . $e->getMessage() . '</p>';
                $html .= '<p>Stack trace: <pre>' . $e->getTraceAsString() . '</pre></p>';
            }
            
            return new Response($html, 200, ['Content-Type' => 'text/html']);
            
        } catch (\Exception $e) {
            return new Response('Erreur générale: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>', 500, ['Content-Type' => 'text/html']);
        }
    }
}