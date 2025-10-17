<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Form\ExerciceType;
use App\Repository\ExerciceRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exercice')]
final class ExerciceController extends AbstractController
{
    #[Route(name: 'app_exercice_index', methods: ['GET'])]
    public function index(ExerciceRepository $exerciceRepository): Response
    {
        return $this->render('exercice/index.html.twig', [
            'exercices' => $exerciceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_exercice_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ExerciceRepository $exerciceRepository, EntityManagerInterface $entityManager): Response
    {
        $exercice = new Exercice();
        
        // Calculer les dates par défaut basées sur le dernier exercice
        $this->setDefaultDates($exercice, $exerciceRepository);
        
        $form = $this->createForm(ExerciceType::class, $exercice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($exercice);
            $entityManager->flush();

            return $this->redirectToRoute('app_exercice_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('exercice/new.html.twig', [
            'exercice' => $exercice,
            'form' => $form,
        ]);
    }

    #[Route('/transactions', name: 'app_exercice_all_transactions', methods: ['GET'])]
    public function allTransactions(TransactionRepository $transactionRepository): Response
    {
        return $this->render('exercice/all_transactions.html.twig', [
            'transactions' => $transactionRepository->findAll(),
        ]);
    }

    #[Route('/{id_exercice}/transactions', name: 'app_exercice_transactions', methods: ['GET'])]
    public function exerciceTransactions(int $id_exercice, ExerciceRepository $exerciceRepository): Response
    {
        $exercice = $exerciceRepository->findOneBy(['id_exercice' => $id_exercice]);
        
        if (!$exercice) {
            throw $this->createNotFoundException('Exercice non trouvé');
        }

        return $this->render('exercice/transactions.html.twig', [
            'exercice' => $exercice,
            'transactions' => $exercice->getTransactions(),
        ]);
    }

    #[Route('/{id_exercice}', name: 'app_exercice_show', methods: ['GET'])]
    public function show(int $id_exercice, ExerciceRepository $exerciceRepository): Response
    {
        $exercice = $exerciceRepository->findOneBy(['id_exercice' => $id_exercice]);
        
        if (!$exercice) {
            throw $this->createNotFoundException('Exercice non trouvé');
        }

        return $this->render('exercice/show.html.twig', [
            'exercice' => $exercice,
        ]);
    }

    #[Route('/{id_exercice}/edit', name: 'app_exercice_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_exercice, ExerciceRepository $exerciceRepository, EntityManagerInterface $entityManager): Response
    {
        $exercice = $exerciceRepository->findOneBy(['id_exercice' => $id_exercice]);
        
        if (!$exercice) {
            throw $this->createNotFoundException('Exercice non trouvé');
        }
        
        $form = $this->createForm(ExerciceType::class, $exercice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_exercice_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('exercice/edit.html.twig', [
            'exercice' => $exercice,
            'form' => $form,
        ]);
    }

    #[Route('/{id_exercice}', name: 'app_exercice_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_exercice, ExerciceRepository $exerciceRepository, EntityManagerInterface $entityManager): Response
    {
        $exercice = $exerciceRepository->findOneBy(['id_exercice' => $id_exercice]);
        
        if (!$exercice) {
            throw $this->createNotFoundException('Exercice non trouvé');
        }
        if ($this->isCsrfTokenValid('delete'.$exercice->getIdExercice(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($exercice);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_exercice_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Définit les dates par défaut pour un nouvel exercice basé sur le dernier exercice existant
     */
    private function setDefaultDates(Exercice $exercice, ExerciceRepository $exerciceRepository): void
    {
        // Récupérer le dernier exercice par date de fin
        $lastExercice = $exerciceRepository->createQueryBuilder('e')
            ->orderBy('e.date_fin', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastExercice && $lastExercice->getDateFin()) {
            // Date de début = jour après la date de fin du dernier exercice
            $dateDebut = clone $lastExercice->getDateFin();
            $dateDebut->modify('+1 day');
        } else {
            // Si aucun exercice précédent, commencer au 1er janvier de l'année courante
            $dateDebut = new \DateTime('first day of January this year');
        }

        // Date de fin = un an après la date de début (moins un jour pour finir le 31 décembre)
        $dateFin = clone $dateDebut;
        $dateFin->modify('+1 year -1 day');

        $exercice->setDateDebut($dateDebut);
        $exercice->setDateFin($dateFin);
        
        // Générer un libellé par défaut basé sur l'année
        $annee = $dateDebut->format('Y');
        $exercice->setLibelle("Exercice {$annee}");
    }

    #[Route('/{id_exercice}/update-field', name: 'app_exercice_update_field', methods: ['POST'])]
    public function updateField(Request $request, int $id_exercice, ExerciceRepository $exerciceRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $exercice = $exerciceRepository->findOneBy(['id_exercice' => $id_exercice]);
        
        if (!$exercice) {
            return new JsonResponse(['success' => false, 'message' => 'Exercice non trouvé'], 404);
        }

        $field = $request->request->get('field');
        $value = $request->request->get('value');

        try {
            switch ($field) {
                case 'libelle':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le libellé ne peut pas être vide'], 400);
                    }
                    $exercice->setLibelle(trim($value));
                    break;
                case 'date_debut':
                    try {
                        $date = new \DateTime($value);
                        $exercice->setDateDebut($date);
                    } catch (\Exception $e) {
                        return new JsonResponse(['success' => false, 'message' => 'Format de date invalide'], 400);
                    }
                    break;
                case 'date_fin':
                    if (empty($value)) {
                        $exercice->setDateFin(null);
                    } else {
                        try {
                            $date = new \DateTime($value);
                            $exercice->setDateFin($date);
                        } catch (\Exception $e) {
                            return new JsonResponse(['success' => false, 'message' => 'Format de date invalide'], 400);
                        }
                    }
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