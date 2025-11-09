<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\PersonneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, PersonneRepository $personneRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
            'personnes' => $personneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET'])]
    public function new(): Response
    {
        $this->addFlash('info', 'Les utilisateurs doivent s\'inscrire via le formulaire d\'inscription. Un administrateur pourra ensuite leur attribuer des rôles.');
        return $this->redirectToRoute('app_register');
    }

    #[Route('/{id_user}', name: 'app_user_show', methods: ['GET'])]
    public function show(int $id_user, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['id_user' => $id_user]);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id_user}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_user, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->findOneBy(['id_user' => $id_user]);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $form = $this->createForm(\App\Form\UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id_user}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_user, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->findOneBy(['id_user' => $id_user]);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getIdUser(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_user}/update-field', name: 'app_user_update_field', methods: ['POST'])]
    public function updateField(Request $request, int $id_user, UserRepository $userRepository, PersonneRepository $personneRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->findOneBy(['id_user' => $id_user]);
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        $field = $request->request->get('field');
        $value = $request->request->get('value');

        try {
            switch ($field) {
                case 'username':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le nom d\'utilisateur ne peut pas être vide'], 400);
                    }
                    
                    // Vérifier l'unicité du username
                    $existingUser = $userRepository->findOneBy(['username' => trim($value)]);
                    if ($existingUser && $existingUser->getIdUser() !== $user->getIdUser()) {
                        return new JsonResponse(['success' => false, 'message' => 'Ce nom d\'utilisateur existe déjà'], 400);
                    }
                    
                    $user->setUsername(trim($value));
                    break;
                case 'personne':
                    if (empty($value)) {
                        $user->setPersonne(null);
                    } else {
                        $personne = $personneRepository->findOneBy(['id_personne' => intval($value)]);
                        if (!$personne) {
                            return new JsonResponse(['success' => false, 'message' => 'Personne non trouvée'], 400);
                        }
                        $user->setPersonne($personne);
                    }
                    break;
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Champ non autorisé'], 400);
            }

            $entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id_user}/toggle-enabled', name: 'app_user_toggle_enabled', methods: ['POST'])]
    public function toggleEnabled(int $id_user, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->findOneBy(['id_user' => $id_user]);
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        try {
            $user->setEnabled(!$user->isEnabled());
            $entityManager->flush();
            
            $status = $user->isEnabled() ? 'activé' : 'désactivé';
            return new JsonResponse([
                'success' => true, 
                'message' => "Utilisateur $status avec succès",
                'enabled' => $user->isEnabled()
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la modification: ' . $e->getMessage()], 500);
        }
    }
}
