<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestDeleteFixController extends AbstractController
{
    #[Route('/test-delete-fix', name: 'test_delete_fix')]
    public function index(TransactionRepository $transactionRepository): Response
    {
        $transactions = $transactionRepository->findAll();
        
        return $this->render('test_delete_fix.html.twig', [
            'transactions' => array_slice($transactions, 0, 5)
        ]);
    }
}