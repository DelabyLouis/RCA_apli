<?php

namespace App\Controller;

use App\Entity\Personne;
use App\Form\PersonneType;
use App\Repository\PersonneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}