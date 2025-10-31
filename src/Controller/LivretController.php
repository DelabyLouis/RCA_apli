<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\TypeTransaction;
use App\Entity\Entreprise;
use App\Repository\TransactionRepository;
use App\Repository\ExerciceRepository;
use App\Repository\TypeTransactionRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/livret')]
class LivretController extends AbstractController
{
    #[Route('/', name: 'app_livret_index', methods: ['GET'])]
    public function index(
        Request $request,
        TransactionRepository $transactionRepository,
        ExerciceRepository $exerciceRepository
    ): Response {
        // Filtrage par exercice optionnel
        $exerciceId = $request->query->get('exercice_id');
        $exerciceFilter = null;
        
        if ($exerciceId) {
            $exerciceFilter = $exerciceRepository->find($exerciceId);
            if (!$exerciceFilter) {
                throw $this->createNotFoundException('Exercice non trouvé');
            }
        }
        
        // Récupérer toutes les transactions du livret
        $queryBuilder = $transactionRepository->createQueryBuilder('t')
            ->where('t.type_compte = :type')
            ->setParameter('type', 'livret')
            ->orderBy('t.date_transaction', 'ASC')
            ->addOrderBy('t.numero_ordre', 'ASC');
            
        // Si filtrage par exercice
        if ($exerciceFilter) {
            $queryBuilder->andWhere('t.exercice = :exercice')
                        ->setParameter('exercice', $exerciceFilter);
        }
        
        $transactions = $queryBuilder->getQuery()->getResult();
        
        // Calculer les soldes cumulés
        $transactions_avec_solde = [];
        $solde_cumule = $this->getSoldeInitialLivret();
        
        foreach ($transactions as $transaction) {
            $montant = (float) $transaction->getMontant();
            $solde_cumule += $montant;
            
            $transactions_avec_solde[] = [
                'transaction' => $transaction,
                'solde_cumule' => $solde_cumule
            ];
        }
        
        // Informations du livret simulé
        $livret_info = [
            'solde_initial' => $this->getSoldeInitialLivret(),
            'solde_actuel' => $solde_cumule,
            'date_creation' => new \DateTime('2025-01-01') // Date fictive
        ];
        
        return $this->render('livret/index.html.twig', [
            'livret' => (object) $livret_info,
            'transactions_avec_solde' => $transactions_avec_solde,
            'exercice_filter' => $exerciceFilter,
            'exercices' => $exerciceRepository->findBy([], ['libelle' => 'DESC'])
        ]);
    }

    #[Route('/transfert', name: 'app_livret_transfert', methods: ['GET', 'POST'])]
    public function transfert(
        Request $request,
        EntityManagerInterface $entityManager,
        TransactionRepository $transactionRepository,
        ExerciceRepository $exerciceRepository
    ): Response {
        if ($request->isMethod('POST')) {
            return $this->handleTransfert($request, $entityManager, $transactionRepository);
        }

        // Informations du livret pour l'affichage
        $solde_actuel = $this->calculerSoldeLivret($transactionRepository);
        $livret_info = [
            'solde_initial' => $this->getSoldeInitialLivret(),
            'solde_actuel' => $solde_actuel,
            'date_creation' => new \DateTime('2025-01-01')
        ];

        return $this->render('livret/transfert.html.twig', [
            'livret' => (object) $livret_info,
            'exercices' => $exerciceRepository->findExercicesOuverts()
        ]);
    }

    private function handleTransfert(
        Request $request,
        EntityManagerInterface $entityManager,
        TransactionRepository $transactionRepository
    ): Response {
        $type = $request->request->get('type'); // 'depot' ou 'retrait'
        $montant = (float) $request->request->get('montant');
        $libelle = $request->request->get('libelle');
        $exerciceId = $request->request->get('exercice_id');

        // Générer automatiquement le libellé s'il est vide
        if (empty($libelle) || trim($libelle) === '') {
            $now = new \DateTime();
            $day = $now->format('d');
            $month = $now->format('m');
            $year = $now->format('y');
            $hours = $now->format('H');
            $minutes = $now->format('i');
            
            $typeText = $type === 'depot' ? 'DepotLivret' : 'RetraitLivret';
            $libelle = "{$typeText} {$day}-{$month}-{$year}-{$hours}h{$minutes}";
        }

        if (!$type || !$montant || !$exerciceId) {
            $this->addFlash('error', 'Tous les champs obligatoires doivent être remplis.');
            return $this->redirectToRoute('app_livret_transfert');
        }

        // Récupérer l'exercice sélectionné
        $exercice = $entityManager->getRepository(\App\Entity\Exercice::class)->find($exerciceId);
        if (!$exercice) {
            $this->addFlash('error', 'Exercice non trouvé.');
            return $this->redirectToRoute('app_livret_transfert');
        }

        // Vérifier que l'exercice n'est pas clôturé
        if ($exercice->isClos()) {
            $this->addFlash('error', 'Impossible d\'ajouter une transaction à un exercice clôturé.');
            return $this->redirectToRoute('app_livret_transfert');
        }

        // Créer les deux transactions liées
        try {
            // Créer ou récupérer le type de transaction "livret" et l'entreprise "livret"
            $typeLivret = $this->getOrCreateLivretTypeTransaction($entityManager);
            $entrepriseLivret = $this->getOrCreateLivretEntreprise($entityManager);
            
            // Obtenir le numéro d'ordre
            $numeroOrdre = $this->getNextNumeroOrdre($transactionRepository, $exercice, 'livret');

            // Créer une seule transaction pour le transfert livret
            $transactionLivret = new Transaction();
            $transactionLivret->setLibelle($libelle) // Libellé simple sans suffixe
                ->setNumeroOrdre($numeroOrdre)
                ->setDateTransaction(new \DateTime())
                ->setMontant($type === 'depot' ? (string) $montant : '-' . $montant)
                ->setTypeCompte('livret')
                ->setExercice($exercice)
                ->setTypeTransaction($typeLivret)
                ->setEntreprise($entrepriseLivret);

            $entityManager->persist($transactionLivret);
            $entityManager->flush();

            $this->addFlash('success', 'Transfert effectué avec succès.');
            return $this->redirectToRoute('app_livret_index');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du transfert : ' . $e->getMessage());
            return $this->redirectToRoute('app_livret_transfert');
        }
    }

    #[Route('/{id}/show', name: 'app_livret_show', methods: ['GET'])]
    public function show(int $id, TransactionRepository $transactionRepository): Response
    {
        $transaction = $transactionRepository->find($id);
        
        if (!$transaction || $transaction->getTypeCompte() !== 'livret') {
            throw $this->createNotFoundException('Transaction du livret non trouvée');
        }
        
        // Récupérer la transaction liée si elle existe
        $transactionLiee = null;
        if ($transaction->getTransactionLieeId()) {
            $transactionLiee = $transactionRepository->find($transaction->getTransactionLieeId());
        }
        
        return $this->render('livret/show.html.twig', [
            'transaction' => $transaction,
            'transaction_liee' => $transactionLiee
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_livret_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id, 
        Request $request, 
        TransactionRepository $transactionRepository, 
        EntityManagerInterface $entityManager,
        ExerciceRepository $exerciceRepository
    ): Response {
        $transaction = $transactionRepository->find($id);
        
        if (!$transaction || $transaction->getTypeCompte() !== 'livret') {
            throw $this->createNotFoundException('Transaction du livret non trouvée');
        }
        
        // Vérifier que l'exercice n'est pas clôturé
        if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
            $this->addFlash('error', 'Impossible de modifier une transaction d\'un exercice clôturé.');
            return $this->redirectToRoute('app_livret_index');
        }
        
        if ($request->isMethod('POST')) {
            // Traitement de la modification
            $libelle = $request->request->get('libelle');
            $montant = $request->request->get('montant');
            $dateTransaction = $request->request->get('date_transaction');
            $exerciceId = $request->request->get('exercice');
            
            if ($libelle) {
                $transaction->setLibelle($libelle);
            }
            
            if ($montant !== null && $montant !== '') {
                $transaction->setMontant($montant);
            }
            
            if ($dateTransaction) {
                $transaction->setDateTransaction(new \DateTime($dateTransaction));
            }
            
            if ($exerciceId) {
                $exercice = $exerciceRepository->find($exerciceId);
                if ($exercice) {
                    $transaction->setExercice($exercice);
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Transaction du livret modifiée avec succès');
            return $this->redirectToRoute('app_livret_index');
        }
        
        // Récupérer tous les exercices pour le formulaire
        $exercices = $exerciceRepository->findBy([], ['libelle' => 'ASC']);
        
        return $this->render('livret/edit.html.twig', [
            'transaction' => $transaction,
            'exercices' => $exercices
        ]);
    }

    #[Route('/{id}/update-field', name: 'app_livret_update_field', methods: ['POST'])]
    public function updateField(
        Request $request, 
        int $id_transaction,
        TransactionRepository $transactionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $transaction = $transactionRepository->find($id_transaction);

            if (!$transaction || !$transaction->isLivret()) {
                return new JsonResponse(['success' => false, 'error' => 'Transaction livret non trouvée'], 404);
            }

            // Vérifier que l'exercice n'est pas clôturé
            if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
                return new JsonResponse(['success' => false, 'error' => 'Impossible de modifier une transaction d\'un exercice clôturé'], 403);
            }

            $field = $request->request->get('field');
            $value = $request->request->get('value');

            switch ($field) {
                case 'libelle':
                    $transaction->setLibelle($value);
                    break;
                case 'date_transaction':
                    if ($value) {
                        $date = new \DateTime($value);
                        $transaction->setDateTransaction($date);
                        // Mettre à jour aussi la transaction liée si elle existe
                        if ($transaction->isTransfert()) {
                            $transactionLiee = $transactionRepository->find($transaction->getTransactionLieeId());
                            if ($transactionLiee) {
                                $transactionLiee->setDateTransaction($date);
                            }
                        }
                    }
                    break;
                default:
                    return new JsonResponse(['success' => false, 'error' => 'Champ non autorisé'], 400);
            }

            $entityManager->flush();

            $displayValue = null;
            if ($field === 'date_transaction' && $value) {
                $displayValue = $value;
            }

            return new JsonResponse([
                'success' => true, 
                'display_value' => $displayValue,
                'message' => 'Transaction mise à jour'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id_transaction}/delete-ajax', name: 'app_livret_delete_ajax', methods: ['DELETE'])]
    public function deleteAjax(
        int $id_transaction,
        TransactionRepository $transactionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $transaction = $transactionRepository->find($id_transaction);

            if (!$transaction || !$transaction->isLivret()) {
                return new JsonResponse(['success' => false, 'error' => 'Transaction livret non trouvée'], 404);
            }

            // Supprimer aussi la transaction liée si elle existe
            if ($transaction->isTransfert()) {
                $transactionLiee = $transactionRepository->find($transaction->getTransactionLieeId());
                if ($transactionLiee) {
                    $entityManager->remove($transactionLiee);
                }
            }

            $entityManager->remove($transaction);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Transaction supprimée']);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function getOrCreateLivretTypeTransaction(EntityManagerInterface $entityManager): TypeTransaction
    {
        $typeTransactionRepo = $entityManager->getRepository(TypeTransaction::class);
        $typeTransaction = $typeTransactionRepo->findOneBy(['libelle' => 'Livret']);
        
        if (!$typeTransaction) {
            $typeTransaction = new TypeTransaction();
            $typeTransaction->setLibelle('Livret');
            $entityManager->persist($typeTransaction);
            $entityManager->flush();
        }
        
        return $typeTransaction;
    }
    
    private function getOrCreateLivretEntreprise(EntityManagerInterface $entityManager): Entreprise
    {
        $entrepriseRepo = $entityManager->getRepository(Entreprise::class);
        $entreprise = $entrepriseRepo->findOneBy(['nom_entreprise' => 'Livret']);
        
        if (!$entreprise) {
            $entreprise = new Entreprise();
            $entreprise->setNomEntreprise('Livret');
            $entreprise->setRue('Compte épargne interne');
            $entreprise->setVille('Interne');
            $entreprise->setCodePostal(00000);
            $entreprise->setTelephone(null);
            $entreprise->setEmail('');
            $entityManager->persist($entreprise);
            $entityManager->flush();
        }
        
        return $entreprise;
    }

    private function getSoldeInitialLivret(): float
    {
        // Solde initial fixe du livret
        return 0.0;
    }

    private function calculerSoldeLivret(TransactionRepository $transactionRepository): float
    {
        $transactions = $transactionRepository->createQueryBuilder('t')
            ->where('t.type_compte = :type')
            ->setParameter('type', 'livret')
            ->getQuery()
            ->getResult();

        $solde = $this->getSoldeInitialLivret();
        foreach ($transactions as $transaction) {
            $solde += (float) $transaction->getMontant();
        }

        return $solde;
    }

    private function getNextNumeroOrdre(
        TransactionRepository $transactionRepository, 
        \App\Entity\Exercice $exercice, 
        string $typeCompte
    ): int {
        // Obtenir le numéro d'ordre maximum pour cet exercice (tous types confondus)
        $result = $transactionRepository->createQueryBuilder('t')
            ->select('MAX(t.numero_ordre)')
            ->where('t.exercice = :exercice')
            ->setParameter('exercice', $exercice)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }
}