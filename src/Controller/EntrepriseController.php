<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Form\EntrepriseType;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}
