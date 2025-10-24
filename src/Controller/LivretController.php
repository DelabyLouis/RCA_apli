<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Repository\ExerciceRepository;
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
            'exercices' => $exerciceRepository->findBy([], ['libelle' => 'DESC'])
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
        $description = $request->request->get('description');
        $exerciceId = $request->request->get('exercice_id');

        if (!$type || !$montant || !$libelle || !$exerciceId) {
            $this->addFlash('error', 'Tous les champs obligatoires doivent être remplis.');
            return $this->redirectToRoute('app_livret_transfert');
        }

        // Récupérer l'exercice sélectionné
        $exercice = $entityManager->getRepository(\App\Entity\Exercice::class)->find($exerciceId);
        if (!$exercice) {
            $this->addFlash('error', 'Exercice non trouvé.');
            return $this->redirectToRoute('app_livret_transfert');
        }

        // Créer les deux transactions liées
        try {
            // Obtenir les numéros d'ordre en une fois pour éviter les conflits
            $numeroOrdreCompteCourant = $this->getNextNumeroOrdre($transactionRepository, $exercice, 'compte_courant');
            $numeroOrdrelivret = $numeroOrdreCompteCourant + 1;

            // Transaction compte courant
            $transactionCompteCourant = new Transaction();
            $transactionCompteCourant->setLibelle($libelle . ' (Compte courant)')
                ->setNumeroOrdre($numeroOrdreCompteCourant)
                ->setDateTransaction(new \DateTime())
                ->setMontant($type === 'depot' ? '-' . $montant : (string) $montant)
                ->setTypeCompte('compte_courant')
                ->setDescription($description)
                ->setExercice($exercice);

            $entityManager->persist($transactionCompteCourant);
            $entityManager->flush(); // Pour obtenir l'ID

            // Transaction livret
            $transactionLivret = new Transaction();
            $transactionLivret->setLibelle($libelle . ' (Livret)')
                ->setNumeroOrdre($numeroOrdrelivret)
                ->setDateTransaction(new \DateTime())
                ->setMontant($type === 'depot' ? (string) $montant : '-' . $montant)
                ->setTypeCompte('livret')
                ->setDescription($description)
                ->setTransactionLieeId($transactionCompteCourant->getIdTransaction())
                ->setExercice($exercice);

            $entityManager->persist($transactionLivret);
            $entityManager->flush();

            // Mettre à jour la transaction compte courant avec l'ID de la transaction livret
            $transactionCompteCourant->setTransactionLieeId($transactionLivret->getIdTransaction());
            $entityManager->flush();

            $this->addFlash('success', 'Transfert effectué avec succès.');
            return $this->redirectToRoute('app_livret_index');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du transfert : ' . $e->getMessage());
            return $this->redirectToRoute('app_livret_transfert');
        }
    }

    #[Route('/{id_transaction}/update-field', name: 'app_livret_update_field', methods: ['POST'])]
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

            $field = $request->request->get('field');
            $value = $request->request->get('value');

            switch ($field) {
                case 'libelle':
                    $transaction->setLibelle($value);
                    break;
                case 'description':
                    $transaction->setDescription($value ?: null);
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