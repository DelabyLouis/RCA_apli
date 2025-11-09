<?php

namespace App\Controller;

use App\Form\RoleType;
use App\Entity\Role;
use App\Repository\PermissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugRoleFormController extends AbstractController
{
    #[Route('/debug-role-form', name: 'debug_role_form')]
    public function debugRoleForm(PermissionRepository $permissionRepository): Response
    {
        try {
            $html = '<h1>Debug Formulaire Rôle</h1>';
            $html .= '<style>pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;}</style>';
            
            // Test 1: Créer une entité Role
            $html .= '<h2>1. Test création entité Role</h2>';
            try {
                $role = new Role();
                $html .= '<p>✅ Entité Role créée</p>';
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur entité Role: ' . $e->getMessage() . '</p>';
            }
            
            // Test 2: Lister les permissions
            $html .= '<h2>2. Test permissions</h2>';
            try {
                $permissions = $permissionRepository->findAll();
                $html .= '<p>✅ Permissions trouvées: ' . count($permissions) . '</p>';
                foreach ($permissions as $perm) {
                    $html .= '<p>- ' . $perm->getName() . ' (' . $perm->getDescription() . ')</p>';
                }
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur permissions: ' . $e->getMessage() . '</p>';
            }
            
            // Test 3: Créer le formulaire
            $html .= '<h2>3. Test création formulaire</h2>';
            try {
                $form = $this->createForm(RoleType::class, $role);
                $html .= '<p>✅ Formulaire RoleType créé</p>';
                
                // Test de la vue du formulaire
                try {
                    $formView = $form->createView();
                    $html .= '<p>✅ Vue du formulaire créée</p>';
                    
                    // Test du champ permissions
                    if (isset($formView['permissions'])) {
                        $html .= '<p>✅ Champ permissions existe</p>';
                        $html .= '<p>Type: ' . get_class($formView['permissions']) . '</p>';
                        
                        // Test des choix
                        if (isset($formView['permissions']->children)) {
                            $html .= '<p>✅ Choix permissions: ' . count($formView['permissions']->children) . '</p>';
                        } else {
                            $html .= '<p>⚠️ Pas de children dans permissions</p>';
                        }
                    } else {
                        $html .= '<p>❌ Champ permissions manquant</p>';
                    }
                    
                } catch (\Exception $e) {
                    $html .= '<p>❌ Erreur vue formulaire: ' . $e->getMessage() . '</p>';
                    $html .= '<pre>' . $e->getTraceAsString() . '</pre>';
                }
                
            } catch (\Exception $e) {
                $html .= '<p>❌ Erreur formulaire: ' . $e->getMessage() . '</p>';
                $html .= '<pre>' . $e->getTraceAsString() . '</pre>';
            }
            
            // Test 4: Template simple
            $html .= '<h2>4. Test template simple</h2>';
            try {
                $simpleForm = $this->createForm(RoleType::class, new Role());
                return $this->render('role/debug_simple.html.twig', [
                    'form' => $simpleForm,
                    'debug_info' => $html
                ]);
            } catch (\Exception $e) {
                $html .= '<p>❌ Template non trouvé, retour HTML brut</p>';
            }
            
            return new Response($html, 200, ['Content-Type' => 'text/html']);
            
        } catch (\Exception $e) {
            return new Response('Erreur générale: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>', 500, ['Content-Type' => 'text/html']);
        }
    }
}