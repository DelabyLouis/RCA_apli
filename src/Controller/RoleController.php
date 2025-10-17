<?php

namespace App\Controller;

use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/role')]
final class RoleController extends AbstractController
{
    #[Route(name: 'app_role_index', methods: ['GET'])]
    public function index(RoleRepository $roleRepository): Response
    {
        return $this->render('role/index.html.twig', [
            'roles' => $roleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_role_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($role);
            $entityManager->flush();

            return $this->redirectToRoute('app_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('role/new.html.twig', [
            'role' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/{id_role}', name: 'app_role_show', methods: ['GET'])]
    public function show(int $id_role, RoleRepository $roleRepository): Response
    {
        $role = $roleRepository->findOneBy(['id_role' => $id_role]);
        
        if (!$role) {
            throw $this->createNotFoundException('Rôle non trouvé');
        }

        return $this->render('role/show.html.twig', [
            'role' => $role,
        ]);
    }

    #[Route('/{id_role}/edit', name: 'app_role_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_role, RoleRepository $roleRepository, EntityManagerInterface $entityManager): Response
    {
        $role = $roleRepository->findOneBy(['id_role' => $id_role]);
        
        if (!$role) {
            throw $this->createNotFoundException('Rôle non trouvé');
        }

        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('role/edit.html.twig', [
            'role' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/{id_role}', name: 'app_role_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_role, RoleRepository $roleRepository, EntityManagerInterface $entityManager): Response
    {
        $role = $roleRepository->findOneBy(['id_role' => $id_role]);
        
        if (!$role) {
            throw $this->createNotFoundException('Rôle non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$role->getIdRole(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($role);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_role_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_role}/update-field', name: 'app_role_update_field', methods: ['POST'])]
    public function updateField(Request $request, int $id_role, RoleRepository $roleRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $role = $roleRepository->findOneBy(['id_role' => $id_role]);
        
        if (!$role) {
            return new JsonResponse(['success' => false, 'message' => 'Rôle non trouvé'], 404);
        }

        $field = $request->request->get('field');
        $value = $request->request->get('value');

        try {
            switch ($field) {
                case 'libelle':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le libellé ne peut pas être vide'], 400);
                    }
                    $role->setLibelle(trim($value));
                    break;
                case 'description':
                    $role->setDescription($value ? trim($value) : null);
                    break;
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Champ non autorisé'], 400);
            }

            $entityManager->flush();
            
            return new JsonResponse(['success' => true]);        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()], 500);
        }
    }
}
