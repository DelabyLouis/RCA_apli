<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Form\EntrepriseType;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/entreprise')]
final class EntrepriseController extends AbstractController
{
    #[Route(name: 'app_entreprise_index', methods: ['GET'])]
    public function index(EntrepriseRepository $entrepriseRepository): Response
    {
        return $this->render('entreprise/index.html.twig', [
            'entreprises' => $entrepriseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_entreprise_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entreprise = new Entreprise();
        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entreprise);
            $entityManager->flush();

            return $this->redirectToRoute('app_entreprise_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('entreprise/new.html.twig', [
            'entreprise' => $entreprise,
            'form' => $form,
        ]);
    }

    #[Route('/{id_entreprise}', name: 'app_entreprise_show', methods: ['GET'])]
    public function show(int $id_entreprise, EntrepriseRepository $entrepriseRepository): Response
    {
        $entreprise = $entrepriseRepository->findOneBy(['id_entreprise' => $id_entreprise]);
        
        if (!$entreprise) {
            throw $this->createNotFoundException('Entreprise non trouvée');
        }

        return $this->render('entreprise/show.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/{id_entreprise}/edit', name: 'app_entreprise_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_entreprise, EntrepriseRepository $entrepriseRepository, EntityManagerInterface $entityManager): Response
    {
        $entreprise = $entrepriseRepository->findOneBy(['id_entreprise' => $id_entreprise]);
        
        if (!$entreprise) {
            throw $this->createNotFoundException('Entreprise non trouvée');
        }

        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_entreprise_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('entreprise/edit.html.twig', [
            'entreprise' => $entreprise,
            'form' => $form,
        ]);
    }

    #[Route('/{id_entreprise}', name: 'app_entreprise_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_entreprise, EntrepriseRepository $entrepriseRepository, EntityManagerInterface $entityManager): Response
    {
        $entreprise = $entrepriseRepository->findOneBy(['id_entreprise' => $id_entreprise]);
        
        if (!$entreprise) {
            throw $this->createNotFoundException('Entreprise non trouvée');
        }

        if ($this->isCsrfTokenValid('delete'.$entreprise->getIdEntreprise(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($entreprise);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_entreprise_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_entreprise}/update-field', name: 'app_entreprise_update_field', methods: ['POST'])]
    public function updateField(Request $request, int $id_entreprise, EntrepriseRepository $entrepriseRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $entreprise = $entrepriseRepository->findOneBy(['id_entreprise' => $id_entreprise]);
        
        if (!$entreprise) {
            return new JsonResponse(['success' => false, 'message' => 'Entreprise non trouvée'], 404);
        }

        $field = $request->request->get('field');
        $value = $request->request->get('value');

        try {
            switch ($field) {
                case 'nom_entreprise':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le nom de l\'entreprise ne peut pas être vide'], 400);
                    }
                    $entreprise->setNomEntreprise(trim($value));
                    break;
                case 'siret':
                    $entreprise->setSiret($value ? trim($value) : null);
                    break;
                case 'siren':
                    $entreprise->setSiren($value ? trim($value) : null);
                    break;
                case 'numero_voie':
                    $entreprise->setNumeroVoie($value ? trim($value) : null);
                    break;
                case 'rue':
                    $entreprise->setRue($value ? trim($value) : null);
                    break;
                case 'complement_adresse':
                    $entreprise->setComplementAdresse($value ? trim($value) : null);
                    break;
                case 'ville':
                    $entreprise->setVille($value ? trim($value) : null);
                    break;
                case 'code_postal':
                    $entreprise->setCodePostal($value ? (int)$value : null);
                    break;
                case 'pays':
                    $entreprise->setPays($value ? trim($value) : 'France');
                    break;
                case 'telephone':
                    $entreprise->setTelephone($value ? (int)$value : null);
                    break;
                case 'email':
                    $entreprise->setEmail($value ? trim($value) : null);
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

    #[Route('/{id_entreprise}/delete-ajax', name: 'app_entreprise_delete_ajax', methods: ['DELETE'])]
    public function deleteAjax(int $id_entreprise, EntrepriseRepository $entrepriseRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $entreprise = $entrepriseRepository->findOneBy(['id_entreprise' => $id_entreprise]);
        
        if (!$entreprise) {
            return new JsonResponse(['success' => false, 'message' => 'Entreprise non trouvée'], 404);
        }

        try {
            $entityManager->remove($entreprise);
            $entityManager->flush();
            
            return new JsonResponse(['success' => true, 'message' => 'Entreprise supprimée avec succès']);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}