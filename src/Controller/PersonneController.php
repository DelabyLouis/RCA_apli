<?php

namespace App\Controller;

use App\Entity\Personne;
use App\Form\PersonneType;
use App\Repository\PersonneRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/personne')]
final class PersonneController extends AbstractController
{
    #[Route(name: 'app_personne_index', methods: ['GET'])]
    public function index(PersonneRepository $personneRepository): Response
    {
        // Récupération des personnes avec leurs relations pour éviter les requêtes N+1
        $personnes = $personneRepository->findAllWithRelations();
        
        return $this->render('personne/index.html.twig', [
            'personnes' => $personnes,
        ]);
    }

    #[Route('/new', name: 'app_personne_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $personne = new Personne();
        $form = $this->createForm(PersonneType::class, $personne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($personne);
            $entityManager->flush();

            return $this->redirectToRoute('app_personne_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('personne/new.html.twig', [
            'personne' => $personne,
            'form' => $form,
        ]);
    }

    #[Route('/{id_personne}', name: 'app_personne_show', methods: ['GET'])]
    public function show(int $id_personne, PersonneRepository $personneRepository): Response
    {
        $personne = $personneRepository->findOneBy(['id_personne' => $id_personne]);
        
        if (!$personne) {
            throw $this->createNotFoundException('Personne non trouvée');
        }

        return $this->render('personne/show.html.twig', [
            'personne' => $personne,
        ]);
    }

    #[Route('/{id_personne}/edit', name: 'app_personne_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_personne, PersonneRepository $personneRepository, EntityManagerInterface $entityManager): Response
    {
        $personne = $personneRepository->findOneBy(['id_personne' => $id_personne]);
        
        if (!$personne) {
            throw $this->createNotFoundException('Personne non trouvée');
        }
        $form = $this->createForm(PersonneType::class, $personne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_personne_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('personne/edit.html.twig', [
            'personne' => $personne,
            'form' => $form,
        ]);
    }

    #[Route('/{id_personne}', name: 'app_personne_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_personne, PersonneRepository $personneRepository, EntityManagerInterface $entityManager): Response
    {
        $personne = $personneRepository->findOneBy(['id_personne' => $id_personne]);
        
        if (!$personne) {
            throw $this->createNotFoundException('Personne non trouvée');
        }
        if ($this->isCsrfTokenValid('delete'.$personne->getIdPersonne(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($personne);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_personne_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_personne}/update-field', name: 'app_personne_update_field', methods: ['POST'])]
    public function updateField(Request $request, int $id_personne, PersonneRepository $personneRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $personne = $personneRepository->findOneBy(['id_personne' => $id_personne]);
        
        if (!$personne) {
            return new JsonResponse(['success' => false, 'message' => 'Personne non trouvée'], 404);
        }

        $field = $request->request->get('field');
        $value = $request->request->get('value');

        try {
            switch ($field) {
                case 'nom':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le nom ne peut pas être vide'], 400);
                    }
                    $personne->setNom(trim($value));
                    break;
                case 'prenom':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le prénom ne peut pas être vide'], 400);
                    }
                    $personne->setPrenom(trim($value));
                    break;
                case 'civilite':
                    $personne->setCivilite($value ? trim($value) : null);
                    break;
                case 'numero_voie':
                    $personne->setNumeroVoie($value ? trim($value) : null);
                    break;
                case 'rue':
                    $personne->setRue($value ? trim($value) : null);
                    break;
                case 'complement_adresse':
                    $personne->setComplementAdresse($value ? trim($value) : null);
                    break;
                case 'ville':
                    $personne->setVille($value ? trim($value) : null);
                    break;
                case 'code_postal':
                    $personne->setCodePostal($value ? trim($value) : null);
                    break;
                case 'pays':
                    $personne->setPays($value ? trim($value) : 'France');
                    break;
                case 'telephone':
                    $personne->setTelephone($value ? trim($value) : null);
                    break;
                case 'email':
                    $personne->setEmail($value ? trim($value) : null);
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

    #[Route('/{id_personne}/delete-ajax', name: 'app_personne_delete_ajax', methods: ['DELETE'])]
    public function deleteAjax(int $id_personne, PersonneRepository $personneRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $personne = $personneRepository->findOneBy(['id_personne' => $id_personne]);
        
        if (!$personne) {
            return new JsonResponse(['success' => false, 'message' => 'Personne non trouvée'], 404);
        }

        try {
            $entityManager->remove($personne);
            $entityManager->flush();
            
            return new JsonResponse(['success' => true, 'message' => 'Personne supprimée avec succès']);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }

    // ===== ROUTE TEMPORAIRE: Fixer les codes postaux avec zéros de tête =====
    #[Route('/admin/fix-postal-codes', name: 'app_personne_fix_postal_codes', methods: ['POST'])]
    public function fixPostalCodes(PersonneRepository $personneRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Sécurité: vérifier que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        try {
            $personnes = $personneRepository->findAll();
            $fixed = 0;
            
            foreach ($personnes as $personne) {
                $cp = $personne->getCodePostal();
                // Si c'est un entier (ancien format), le convertir en string avec zéro padding
                if ($cp !== null && !empty($cp)) {
                    $newCp = str_pad((string)(int)$cp, 5, '0', STR_PAD_LEFT);
                    if ($newCp !== $cp) {
                        $personne->setCodePostal($newCp);
                        $fixed++;
                    }
                }
            }
            
            if ($fixed > 0) {
                $entityManager->flush();
            }
            
            return new JsonResponse(['success' => true, 'message' => "$fixed codes postaux corrigés"]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    // Fixer aussi les entreprises
    #[Route('/admin/fix-entreprise-postal-codes', name: 'app_entreprise_fix_postal_codes', methods: ['POST'])]
    public function fixEntreprisePostalCodes(\App\Repository\EntrepriseRepository $entrepriseRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        try {
            $entreprises = $entrepriseRepository->findAll();
            $fixed = 0;
            
            foreach ($entreprises as $entreprise) {
                $cp = $entreprise->getCodePostal();
                if ($cp !== null && !empty($cp)) {
                    $newCp = str_pad((string)(int)$cp, 5, '0', STR_PAD_LEFT);
                    if ($newCp !== $cp) {
                        $entreprise->setCodePostal($newCp);
                        $fixed++;
                    }
                }
            }
            
            if ($fixed > 0) {
                $entityManager->flush();
            }
            
            return new JsonResponse(['success' => true, 'message' => "$fixed codes postaux entreprises corrigés"]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}