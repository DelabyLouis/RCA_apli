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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $exercice = new Exercice();
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
}