<?php

namespace App\Controller;

use App\Entity\TypeTransaction;
use App\Form\TypeTransactionType;
use App\Repository\TypeTransactionRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/typetransaction')]
final class TypeTransactionController extends AbstractController
{
    #[Route(name: 'app_type_transaction_index', methods: ['GET'])]
    public function index(TypeTransactionRepository $typeTransactionRepository): Response
    {
        return $this->render('type_transaction/index.html.twig', [
            'type_transactions' => $typeTransactionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_type_transaction_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $typeTransaction = new TypeTransaction();
        $form = $this->createForm(TypeTransactionType::class, $typeTransaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($typeTransaction);
            $entityManager->flush();

            return $this->redirectToRoute('app_type_transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_transaction/new.html.twig', [
            'type_transaction' => $typeTransaction,
            'form' => $form,
        ]);
    }

    #[Route('/{id_type}', name: 'app_type_transaction_show', methods: ['GET'])]
    public function show(int $id_type, TypeTransactionRepository $typeTransactionRepository): Response
    {
        $typeTransaction = $typeTransactionRepository->findOneBy(['id_type' => $id_type]);
        
        if (!$typeTransaction) {
            throw $this->createNotFoundException('Type de transaction non trouvé');
        }

        return $this->render('type_transaction/show.html.twig', [
            'type_transaction' => $typeTransaction,
        ]);
    }

    #[Route('/{id_type}/edit', name: 'app_type_transaction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_type, TypeTransactionRepository $typeTransactionRepository, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager): Response
    {
        $typeTransaction = $typeTransactionRepository->findOneBy(['id_type' => $id_type]);
        
        if (!$typeTransaction) {
            throw $this->createNotFoundException('Type de transaction non trouvé');
        }

        // Sauvegarder la valeur originale pour comparaison
        $originalTypeMontantAutorise = $typeTransaction->getTypeMontantAutorise();

        $form = $this->createForm(TypeTransactionType::class, $typeTransaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newTypeMontantAutorise = $typeTransaction->getTypeMontantAutorise();
            
            // Vérifier si le changement de contrainte affecte des transactions existantes
            $affectedTransactions = [];
            if ($originalTypeMontantAutorise !== $newTypeMontantAutorise) {
                if ($newTypeMontantAutorise === 'debit') {
                    // Changement vers "débit uniquement" - vérifier les transactions en crédit
                    $affectedTransactions = $transactionRepository->findBy([
                        'type_transaction' => $typeTransaction
                    ]);
                    $affectedTransactions = array_filter($affectedTransactions, function($transaction) {
                        return $transaction->getMontant() < 0; // Transactions en crédit
                    });
                } elseif ($newTypeMontantAutorise === 'credit') {
                    // Changement vers "crédit uniquement" - vérifier les transactions en débit
                    $affectedTransactions = $transactionRepository->findBy([
                        'type_transaction' => $typeTransaction
                    ]);
                    $affectedTransactions = array_filter($affectedTransactions, function($transaction) {
                        return $transaction->getMontant() >= 0; // Transactions en débit
                    });
                }
            }

            // Si des transactions sont affectées, les détacher du type
            $successMessage = 'Type de transaction modifié avec succès.';
            if (!empty($affectedTransactions)) {
                foreach ($affectedTransactions as $transaction) {
                    $transaction->setTypeTransaction(null);
                    $entityManager->persist($transaction);
                }
                
                $warningDetails = $newTypeMontantAutorise === 'debit' 
                    ? 'crédit(s) détaché(s) car le type n\'autorise que les débits' 
                    : 'débit(s) détaché(s) car le type n\'autorise que les crédits';
                
                $successMessage .= sprintf(
                    ' <br><strong>Attention :</strong> %d transaction(s) ont été automatiquement détachées (%s) et doivent être réassignées manuellement.',
                    count($affectedTransactions),
                    $warningDetails
                );
            }

            $entityManager->flush();

            $this->addFlash('success', $successMessage);

            return $this->redirectToRoute('app_type_transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_transaction/edit.html.twig', [
            'type_transaction' => $typeTransaction,
            'form' => $form,
        ]);
    }

    #[Route('/{id_type}', name: 'app_type_transaction_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_type, TypeTransactionRepository $typeTransactionRepository, EntityManagerInterface $entityManager): Response
    {
        $typeTransaction = $typeTransactionRepository->findOneBy(['id_type' => $id_type]);
        
        if (!$typeTransaction) {
            throw $this->createNotFoundException('Type de transaction non trouvé');
        }
        if ($this->isCsrfTokenValid('delete'.$typeTransaction->getIdType(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($typeTransaction);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_type_transaction_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_type}/update-field', name: 'app_type_transaction_update_field', methods: ['POST'])]
    public function updateField(Request $request, int $id_type, TypeTransactionRepository $typeTransactionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $typeTransaction = $typeTransactionRepository->findOneBy(['id_type' => $id_type]);
        
        if (!$typeTransaction) {
            return new JsonResponse(['success' => false, 'message' => 'Type de transaction non trouvé'], 404);
        }

        $field = $request->request->get('field');
        $value = $request->request->get('value');

        try {
            switch ($field) {
                case 'libelle':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le libellé ne peut pas être vide'], 400);
                    }
                    $typeTransaction->setLibelle(trim($value));
                    break;
                case 'description':
                    $typeTransaction->setDescription($value ? trim($value) : null);
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

    #[Route('/{id_type}/delete-ajax', name: 'app_type_transaction_delete_ajax', methods: ['DELETE'])]
    public function deleteAjax(int $id_type, TypeTransactionRepository $typeTransactionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $typeTransaction = $typeTransactionRepository->findOneBy(['id_type' => $id_type]);
        
        if (!$typeTransaction) {
            return new JsonResponse(['success' => false, 'message' => 'Type de transaction non trouvé'], 404);
        }

        try {
            $entityManager->remove($typeTransaction);
            $entityManager->flush();
            
            return new JsonResponse(['success' => true, 'message' => 'Type de transaction supprimé avec succès']);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}