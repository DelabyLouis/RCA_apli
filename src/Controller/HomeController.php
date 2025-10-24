<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(TransactionRepository $transactionRepository): Response
    {
        // Calculer les soldes
        $soldeCompteCourant = $transactionRepository->calculerSoldeCompteCourant();
        $soldeLivret = $transactionRepository->calculerSoldeLivret();
        $soldeTotal = $soldeCompteCourant + $soldeLivret;
        
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'solde_total' => $soldeTotal,
            'solde_compte_courant' => $soldeCompteCourant,
            'solde_livret' => $soldeLivret,
        ]);
    }
}