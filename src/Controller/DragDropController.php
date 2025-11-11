<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Exercice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class DragDropController extends AbstractController
{
    #[Route('/test', name: 'app_api_test', methods: ['GET', 'POST'])]
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'success' => true, 
            'message' => 'API DragDrop fonctionne parfaitement !',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/transaction/reorder', name: 'app_api_transaction_reorder', methods: ['POST'])]
    public function reorderTransactions(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['transactions'])) {
                return new JsonResponse(['success' => false, 'error' => 'Données invalides'], 400);
            }

            $transactionRepository = $entityManager->getRepository(Transaction::class);
            $exerciceRepository = $entityManager->getRepository(Exercice::class);
            
            $updatedCount = 0;
            
            foreach ($data['transactions'] as $transactionData) {
                $transactionId = (int) $transactionData['id'];
                $newOrder = (int) $transactionData['order'];
                
                $transaction = $transactionRepository->findOneBy(['id_transaction' => $transactionId]);
                if (!$transaction) {
                    continue;
                }
                
                // Vérifier que l'exercice actuel n'est pas clôturé
                if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
                    return new JsonResponse(['success' => false, 'error' => 'Impossible de réorganiser des transactions d\'exercices clôturés'], 403);
                }
                
                // Gérer le changement d'exercice si spécifié
                if (isset($transactionData['exercice_id'])) {
                    $newExerciceId = (int) $transactionData['exercice_id'];
                    $currentExerciceId = $transaction->getExercice() ? $transaction->getExercice()->getIdExercice() : null;
                    
                    if ($newExerciceId !== $currentExerciceId) {
                        $newExercice = $exerciceRepository->findOneBy(['id_exercice' => $newExerciceId]);
                        if ($newExercice) {
                            // Vérifier que le nouvel exercice n'est pas clôturé
                            if ($newExercice->isClos()) {
                                return new JsonResponse(['success' => false, 'error' => 'Impossible de déplacer vers un exercice clôturé'], 403);
                            }
                            $transaction->setExercice($newExercice);
                        } else {
                            return new JsonResponse(['success' => false, 'error' => "Exercice avec l'ID {$newExerciceId} non trouvé"], 400);
                        }
                    }
                }
                
                $transaction->setNumeroOrdre($newOrder);
                $updatedCount++;
            }
            
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true, 
                'message' => "Ordre de {$updatedCount} transaction(s) mis à jour avec succès !",
                'updated_count' => $updatedCount
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'error' => 'Erreur lors de la réorganisation: ' . $e->getMessage()
            ], 500);
        }
    }
}