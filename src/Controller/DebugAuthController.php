<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;

class DebugAuthController extends AbstractController
{
    #[Route('/debug-auth', name: 'debug_auth')]
    public function debugAuth(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        try {
            $users = $userRepository->findAll();
            
            $html = '<h1>Debug Authentification</h1>';
            $html .= '<style>table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>';
            
            $html .= '<h2>Liste des utilisateurs (' . count($users) . ')</h2>';
            $html .= '<table>';
            $html .= '<tr><th>ID</th><th>Username</th><th>Rôles</th><th>Password Hash</th><th>Personne</th></tr>';
            
            foreach ($users as $user) {
                try {
                    $html .= '<tr>';
                    $html .= '<td>' . $user->getIdUser() . '</td>';
                    $html .= '<td><strong>' . htmlspecialchars($user->getUsername()) . '</strong></td>';
                    
                    // Rôles avec protection d'erreur
                    try {
                        $roles = [];
                        foreach ($user->getUserRoles() as $role) {
                            $roles[] = $role->getLibelle();
                        }
                        $html .= '<td>' . implode(', ', $roles) . '</td>';
                    } catch (\Exception $e) {
                        $html .= '<td>Erreur rôles: ' . $e->getMessage() . '</td>';
                    }
            
            // Rôles
            $roles = [];
            foreach ($user->getUserRoles() as $role) {
                $roles[] = $role->getLibelle();
            }
            $html .= '<td>' . implode(', ', $roles) . '</td>';
            
            // Hash du mot de passe
            $password = $user->getPassword();
            $html .= '<td style="font-family:monospace;font-size:10px;">' . 
                     (strlen($password) > 50 ? substr($password, 0, 30) . '...' : $password) . 
                     '<br><small>Longueur: ' . strlen($password) . '</small></td>';
            
            // Personne associée
            $personne = $user->getPersonne();
            $html .= '<td>' . ($personne ? $personne->getPrenom() . ' ' . $personne->getNom() : 'Aucune') . '</td>';
            
            // Statut actif (si la méthode existe)
            $html .= '<td>' . (method_exists($user, 'isEnabled') ? ($user->isEnabled() ? 'Oui' : 'Non') : 'N/A') . '</td>';
            
            $html .= '</tr>';
        }
        $html .= '</table>';
        
        $html .= '<h2>Test de vérification de mot de passe</h2>';
        $html .= '<form method="post" style="margin: 20px 0;">';
        $html .= '<input type="text" name="username" placeholder="Nom d\'utilisateur" style="padding:5px;margin:5px;">';
        $html .= '<input type="password" name="password" placeholder="Mot de passe" style="padding:5px;margin:5px;">';
        $html .= '<button type="submit" style="padding:5px 10px;margin:5px;">Tester</button>';
        $html .= '</form>';
        
        // Test de mot de passe si soumis
        if ($_POST['username'] ?? false) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            $user = $userRepository->findOneBy(['username' => $username]);
            
            if ($user) {
                $isValid = $passwordHasher->isPasswordValid($user, $password);
                $html .= '<div style="padding:10px;margin:10px 0;border:2px solid ' . ($isValid ? 'green' : 'red') . ';">';
                $html .= '<h3>' . ($isValid ? '✅ Mot de passe VALIDE' : '❌ Mot de passe INVALIDE') . '</h3>';
                $html .= '<p><strong>Utilisateur:</strong> ' . htmlspecialchars($user->getUsername()) . '</p>';
                $html .= '<p><strong>Hash stocké:</strong> ' . htmlspecialchars(substr($user->getPassword(), 0, 50)) . '...</p>';
                $html .= '<p><strong>Mot de passe testé:</strong> ' . htmlspecialchars($password) . '</p>';
                $html .= '</div>';
            } else {
                $html .= '<div style="padding:10px;margin:10px 0;border:2px solid orange;">';
                $html .= '<h3>⚠️ Utilisateur non trouvé</h3>';
                $html .= '<p>Aucun utilisateur avec le nom: ' . htmlspecialchars($username) . '</p>';
                $html .= '</div>';
            }
        }
        
        $html .= '<h2>Utilisateurs récents (derniers créés)</h2>';
        $recentUsers = $userRepository->findBy([], ['id_user' => 'DESC'], 3);
        foreach ($recentUsers as $user) {
            $html .= '<div style="border:1px solid #ccc;margin:10px 0;padding:10px;">';
            $html .= '<strong>' . htmlspecialchars($user->getUsername()) . '</strong><br>';
            $html .= 'Email: ' . htmlspecialchars($user->getEmail() ?? 'N/A') . '<br>';
            $html .= 'Hash: <code style="font-size:10px;">' . substr($user->getPassword(), 0, 60) . '...</code><br>';
            $html .= 'Rôles: ' . count($user->getUserRoles()) . '<br>';
            $html .= '</div>';
        }
        
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}