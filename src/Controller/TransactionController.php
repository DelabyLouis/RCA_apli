<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Repository\TransactionRepository;
use App\Repository\PersonneRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ExerciceRepository;
use App\Repository\TypeTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/transaction')]
final class TransactionController extends AbstractController
{
    #[Route(name: 'app_transaction_index', methods: ['GET'])]
    public function index(TransactionRepository $transactionRepository, PersonneRepository $personneRepository, EntrepriseRepository $entrepriseRepository, ExerciceRepository $exerciceRepository, TypeTransactionRepository $typeTransactionRepository): Response
    {
        // Récupérer les transactions triées par numéro d'ordre avec leurs relations pour éviter les requêtes N+1
        $transactions = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.personne', 'p')
            ->leftJoin('t.entreprise', 'e')
            ->addSelect('p')
            ->addSelect('e')
            ->orderBy('t.numero_ordre', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Calculer le solde cumulé pour chaque transaction
        $solde = 0;
        $transactionsAvecSolde = [];
        
        foreach ($transactions as $transaction) {
            $solde += $transaction->getMontant();
            $transactionsAvecSolde[] = [
                'transaction' => $transaction,
                'solde' => $solde
            ];
        }
        
        return $this->render('transaction/index.html.twig', [
            'transactions_avec_solde' => $transactionsAvecSolde,
            'personnes' => $personneRepository->findAll(),
            'entreprises' => $entrepriseRepository->findAll(),
            'exercices' => $exerciceRepository->findAll(),
            'types_transaction' => $typeTransactionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_transaction_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TransactionRepository $transactionRepository): Response
    {
        $transaction = new Transaction();
        
        // Pré-remplir le numéro d'ordre avec le suivant disponible
        $lastNumeroOrdre = $transactionRepository->getLastNumeroOrdre();
        $transaction->setNumeroOrdre($lastNumeroOrdre + 1);
        
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'exercice assigné est clôturé
            if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
                $this->addFlash('error', 'Impossible de créer une transaction pour un exercice clôturé. Vous devez d\'abord déclôturer l\'exercice.');
                return $this->render('transaction/new.html.twig', [
                    'transaction' => $transaction,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($transaction);
            $entityManager->flush();

            return $this->redirectToRoute('app_transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transaction/new.html.twig', [
            'transaction' => $transaction,
            'form' => $form,
        ]);
    }

    #[Route('/{id_transaction}', name: 'app_transaction_show', methods: ['GET'])]
    public function show(int $id_transaction, TransactionRepository $transactionRepository): Response
    {
        $transaction = $transactionRepository->findOneBy(['id_transaction' => $id_transaction]);
        
        if (!$transaction) {
            throw $this->createNotFoundException('Transaction non trouvée');
        }

        return $this->render('transaction/show.html.twig', [
            'transaction' => $transaction,
        ]);
    }

    #[Route('/{id_transaction}/edit', name: 'app_transaction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_transaction, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager): Response
    {
        $transaction = $transactionRepository->findOneBy(['id_transaction' => $id_transaction]);
        
        if (!$transaction) {
            throw $this->createNotFoundException('Transaction non trouvée');
        }

        // Vérifier si l'exercice de la transaction est clôturé
        if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
            $this->addFlash('error', 'Impossible de modifier une transaction d\'un exercice clôturé. Vous devez d\'abord déclôturer l\'exercice.');
            return $this->redirectToRoute('app_transaction_index');
        }

        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transaction/edit.html.twig', [
            'transaction' => $transaction,
            'form' => $form,
        ]);
    }

    #[Route('/{id_transaction}', name: 'app_transaction_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_transaction, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager): Response
    {
        $transaction = $transactionRepository->findOneBy(['id_transaction' => $id_transaction]);
        
        if (!$transaction) {
            throw $this->createNotFoundException('Transaction non trouvée');
        }

        // Vérifier si l'exercice de la transaction est clôturé
        if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
            $this->addFlash('error', 'Impossible de supprimer une transaction d\'un exercice clôturé. Vous devez d\'abord déclôturer l\'exercice.');
            return $this->redirectToRoute('app_transaction_index');
        }

        if ($this->isCsrfTokenValid('delete'.$transaction->getIdTransaction(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($transaction);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_transaction_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_transaction}/update-field', name: 'app_transaction_update_field', methods: ['POST'])]
    public function updateField(Request $request, int $id_transaction, TransactionRepository $transactionRepository, PersonneRepository $personneRepository, EntrepriseRepository $entrepriseRepository, ExerciceRepository $exerciceRepository, TypeTransactionRepository $typeTransactionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $transaction = $transactionRepository->findOneBy(['id_transaction' => $id_transaction]);
        
        if (!$transaction) {
            return new JsonResponse(['success' => false, 'message' => 'Transaction non trouvée'], 404);
        }

        // Vérifier si l'exercice de la transaction est clôturé
        if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de modifier une transaction d\'un exercice clôturé'], 403);
        }

        $field = $request->request->get('field');
        $value = $request->request->get('value');

        try {
            switch ($field) {
                case 'libelle':
                    if (empty(trim($value))) {
                        return new JsonResponse(['success' => false, 'message' => 'Le libellé ne peut pas être vide'], 400);
                    }
                    
                    // Vérifier l'unicité du libellé
                    $existingTransaction = $transactionRepository->findOneBy(['libelle' => trim($value)]);
                    if ($existingTransaction && $existingTransaction->getIdTransaction() !== $transaction->getIdTransaction()) {
                        return new JsonResponse(['success' => false, 'message' => 'Ce libellé existe déjà'], 400);
                    }
                    
                    $transaction->setLibelle(trim($value));
                    break;
                    
                case 'numero_ordre':
                    if (!is_numeric($value) || intval($value) < 1) {
                        return new JsonResponse(['success' => false, 'message' => 'Le numéro d\'ordre doit être un nombre positif'], 400);
                    }
                    $transaction->setNumeroOrdre(intval($value));
                    break;
                    
                case 'date_transaction':
                    try {
                        $date = new \DateTime($value);
                        $transaction->setDateTransaction($date);
                    } catch (\Exception $e) {
                        return new JsonResponse(['success' => false, 'message' => 'Format de date invalide (YYYY-MM-DD attendu)'], 400);
                    }
                    break;
                    
                case 'montant':
                    if (!is_numeric($value) || floatval($value) == 0) {
                        return new JsonResponse(['success' => false, 'message' => 'Le montant doit être un nombre différent de zéro'], 400);
                    }
                    // Formater le montant à 2 décimales pour la base de données
                    $montantFormate = number_format(floatval($value), 2, '.', '');
                    $transaction->setMontant($montantFormate);
                    break;
                    
                case 'personne':
                    if (empty($value)) {
                        $transaction->setPersonne(null);
                        $transaction->setEntreprise(null); // Une transaction ne peut avoir qu'un seul tiers
                    } else {
                        $personne = $personneRepository->findOneBy(['id_personne' => intval($value)]);
                        if (!$personne) {
                            return new JsonResponse(['success' => false, 'message' => 'Personne non trouvée'], 400);
                        }
                        $transaction->setPersonne($personne);
                        $transaction->setEntreprise(null); // Une transaction ne peut avoir qu'un seul tiers
                    }
                    break;
                    
                case 'entreprise':
                    if (empty($value)) {
                        $transaction->setEntreprise(null);
                        $transaction->setPersonne(null); // Une transaction ne peut avoir qu'un seul tiers
                    } else {
                        $entreprise = $entrepriseRepository->findOneBy(['id_entreprise' => intval($value)]);
                        if (!$entreprise) {
                            return new JsonResponse(['success' => false, 'message' => 'Entreprise non trouvée'], 400);
                        }
                        $transaction->setEntreprise($entreprise);
                        $transaction->setPersonne(null); // Une transaction ne peut avoir qu'un seul tiers
                    }
                    break;
                    
                case 'exercice':
                    $exercice = $exerciceRepository->findOneBy(['id_exercice' => intval($value)]);
                    if (!$exercice) {
                        return new JsonResponse(['success' => false, 'message' => 'Exercice non trouvé'], 400);
                    }
                    $transaction->setExercice($exercice);
                    break;
                    
                case 'type_transaction':
                    $typeTransaction = $typeTransactionRepository->findOneBy(['id_type' => intval($value)]);
                    if (!$typeTransaction) {
                        return new JsonResponse(['success' => false, 'message' => 'Type de transaction non trouvé'], 400);
                    }
                    $transaction->setTypeTransaction($typeTransaction);
                    break;
                    
                case 'tiers':
                    // Gestion du champ tiers combiné (personne ou entreprise)
                    if (empty($value)) {
                        // Aucun tiers sélectionné
                        $transaction->setPersonne(null);
                        $transaction->setEntreprise(null);
                    } elseif (strpos($value, 'personne_') === 0) {
                        // Tiers de type personne
                        $personneId = intval(substr($value, 9)); // Enlever "personne_"
                        $personne = $personneRepository->findOneBy(['id_personne' => $personneId]);
                        if (!$personne) {
                            return new JsonResponse(['success' => false, 'message' => 'Personne non trouvée'], 400);
                        }
                        $transaction->setPersonne($personne);
                        $transaction->setEntreprise(null); // Une transaction ne peut avoir qu'un seul tiers
                    } elseif (strpos($value, 'entreprise_') === 0) {
                        // Tiers de type entreprise
                        $entrepriseId = intval(substr($value, 11)); // Enlever "entreprise_"
                        $entreprise = $entrepriseRepository->findOneBy(['id_entreprise' => $entrepriseId]);
                        if (!$entreprise) {
                            return new JsonResponse(['success' => false, 'message' => 'Entreprise non trouvée'], 400);
                        }
                        $transaction->setEntreprise($entreprise);
                        $transaction->setPersonne(null); // Une transaction ne peut avoir qu'un seul tiers
                    } else {
                        return new JsonResponse(['success' => false, 'message' => 'Format de tiers invalide'], 400);
                    }
                    break;
                    
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Champ non autorisé'], 400);
            }

            $entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la modification : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id_transaction}/delete-ajax', name: 'app_transaction_delete_ajax', methods: ['DELETE'])]
    public function deleteAjax(int $id_transaction, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $transaction = $transactionRepository->findOneBy(['id_transaction' => $id_transaction]);
        
        if (!$transaction) {
            return new JsonResponse(['success' => false, 'message' => 'Transaction non trouvée'], 404);
        }

        // Vérifier si l'exercice de la transaction est clôturé
        if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de supprimer une transaction d\'un exercice clôturé'], 403);
        }

        try {
            $entityManager->remove($transaction);
            $entityManager->flush();
            
            return new JsonResponse(['success' => true, 'message' => 'Transaction supprimée avec succès']);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}
