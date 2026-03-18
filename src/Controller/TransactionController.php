<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Personne;
use App\Entity\Entreprise;
use App\Form\TransactionType;
use App\Form\TransactionNewType;
use App\Repository\TransactionRepository;
use App\Repository\PersonneRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ExerciceRepository;
use App\Repository\TypeTransactionRepository;
use App\Repository\ModeDePaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#[Route('/transaction')]
final class TransactionController extends AbstractController
{
    #[Route(name: 'app_transaction_index', methods: ['GET'])]
    public function index(Request $request, TransactionRepository $transactionRepository, PersonneRepository $personneRepository, EntrepriseRepository $entrepriseRepository, ExerciceRepository $exerciceRepository, TypeTransactionRepository $typeTransactionRepository, ModeDePaiementRepository $modeDePaiementRepository): Response
    {
        $exerciceId = $request->query->get('exercice_id');
        $exerciceFilter = null;
        
        // Si un exercice est spécifié, le récupérer pour le filtre
        if ($exerciceId) {
            $exerciceFilter = $exerciceRepository->findOneBy(['id_exercice' => $exerciceId]);
        }
        
        // Récupérer les paramètres de filtrage
        $libelleFilter = $request->query->get('libelle');
        $tiersFilter = $request->query->all()['tiers'] ?? []; // Array of personne_XX or entreprise_XX
        $typeMontantFilter = $request->query->get('type_montant');
        $montantMinFilter = $request->query->get('montant_min');
        $montantMaxFilter = $request->query->get('montant_max');
        $dateMinFilter = $request->query->get('date_min');
        $dateMaxFilter = $request->query->get('date_max');
        
        // Normaliser tiersFilter en array si c'est une string
        if (is_string($tiersFilter)) {
            $tiersFilter = [$tiersFilter];
        }
        
        // Initialiser le solde précédent
        $soldePrecedent = 0;
        if ($exerciceFilter) {
            $exercicePrecedent = $exerciceRepository->findPreviousExercice($exerciceFilter);
            if ($exercicePrecedent) {
                $soldePrecedent = $transactionRepository->calculateSoldeByExercice($exercicePrecedent->getIdExercice());
            }
        }
        
        // Maintenant, appliquer les filtres pour la requête affichée à l'écran
        $queryBuilder = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.personne', 'p')
            ->leftJoin('t.entreprise', 'e')
            ->leftJoin('t.exercice', 'ex')
            ->addSelect('p')
            ->addSelect('e')
            ->addSelect('ex');
        
        // Appliquer le filtre par exercice si spécifié
        if ($exerciceFilter) {
            $queryBuilder->where('t.exercice = :exercice')
                        ->setParameter('exercice', $exerciceFilter);
        }
        
        // Appliquer le filtre par libellé
        if ($libelleFilter) {
            $queryBuilder->andWhere('t.libelle LIKE :libelle')
                        ->setParameter('libelle', '%' . $libelleFilter . '%');
        }
        
        // Appliquer le filtre par tiers
        if (!empty($tiersFilter)) {
            $personnesIds = [];
            $entreprisesIds = [];
            
            foreach ($tiersFilter as $tier) {
                if (strpos($tier, 'personne_') === 0) {
                    $personnesIds[] = (int) str_replace('personne_', '', $tier);
                } elseif (strpos($tier, 'entreprise_') === 0) {
                    $entreprisesIds[] = (int) str_replace('entreprise_', '', $tier);
                }
            }
            
            // Construire la condition: (personne IN (...) OR entreprise IN (...))
            $tiersConditions = [];
            
            if (!empty($personnesIds)) {
                $tiersConditions[] = $queryBuilder->expr()->in('t.personne', $personnesIds);
            }
            
            if (!empty($entreprisesIds)) {
                $tiersConditions[] = $queryBuilder->expr()->in('t.entreprise', $entreprisesIds);
            }
            
            if (!empty($tiersConditions)) {
                // Combine multiple conditions with OR
                $orExpression = call_user_func_array(
                    [$queryBuilder->expr(), 'orX'],
                    $tiersConditions
                );
                $queryBuilder->andWhere($orExpression);
            }
        }
        
        // Appliquer le filtre par type de montant (crédit/débit).
        // Note : les transactions de type "livret" ont leur signe inversé
        // lors de l'affichage, il faut donc tenir compte de cette inversion
        // dans les conditions de requête.
        if ($typeMontantFilter) {
            if ($typeMontantFilter === 'credit') {
                // montant positif sur compte courant OR montant négatif sur livret
                $queryBuilder->andWhere(
                    '( (t.type_compte != :livret AND t.montant > 0) OR (t.type_compte = :livret AND t.montant < 0) )'
                )
                ->setParameter('livret', 'livret');
            } elseif ($typeMontantFilter === 'debit') {
                // opposé de crédit
                $queryBuilder->andWhere(
                    '( (t.type_compte != :livret AND t.montant < 0) OR (t.type_compte = :livret AND t.montant > 0) )'
                )
                ->setParameter('livret', 'livret');
            }
        }
        
        // Appliquer le filtre par montant min/max
        if ($montantMinFilter !== null && $montantMinFilter !== '') {
            $queryBuilder->andWhere('ABS(t.montant) >= :montant_min')
                        ->setParameter('montant_min', (float)$montantMinFilter);
        }
        
        if ($montantMaxFilter !== null && $montantMaxFilter !== '') {
            $queryBuilder->andWhere('ABS(t.montant) <= :montant_max')
                        ->setParameter('montant_max', (float)$montantMaxFilter);
        }
        
        // Appliquer le filtre par date
        if ($dateMinFilter) {
            $queryBuilder->andWhere('t.date_transaction >= :date_min')
                        ->setParameter('date_min', new \DateTime($dateMinFilter));
        }
        
        if ($dateMaxFilter) {
            $queryBuilder->andWhere('t.date_transaction <= :date_max')
                        ->setParameter('date_max', new \DateTime($dateMaxFilter . ' 23:59:59'));
        }
        
        // exécution encapsulée pour capturer les erreurs DQL/SQL en cas de 500
        try {
            $transactions = $queryBuilder
                ->orderBy('ex.numero_ordre', 'ASC')
                ->addOrderBy('t.numero_ordre', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            error_log('Erreur exécution requête transactions: ' . $e->getMessage());
            error_log('DQL: ' . $queryBuilder->getQuery()->getDQL());
            $params = [];
            foreach ($queryBuilder->getParameters() as $param) {
                $params[$param->getName()] = $param->getValue();
            }
            error_log('Paramètres: ' . json_encode($params));
            throw $e;
        }
        
        // Calculer le montant cumulé pour chaque transaction (basé UNIQUEMENT sur les transactions filtrées)
        $montantCumule = $soldePrecedent;
        $transactionsAvecMontant = [];
        
        foreach ($transactions as $transaction) {
            // Pour l'affichage du montant de la ligne (crédit/débit)
            $montantTransaction = $transaction->getMontant();
            if ($transaction->getTypeCompte() === 'livret') {
                $montantTransaction = -$montantTransaction;
            }
            
            // Ajouter à la somme cumulée des transactions filtrées
            $montantCumule += $montantTransaction;
            
            $transactionsAvecMontant[] = [
                'transaction' => $transaction,
                'montant_cumule' => $montantCumule,
                'montant_compte_courant' => $montantTransaction
            ];
        }
        
        // Calculer les montants finaux par exercice (basé sur les transactions filtrées)
        $montantsParExercice = [];
        foreach ($transactionsAvecMontant as $item) {
            $tx = $item['transaction'];
            $exercice = $tx->getExercice();
            if ($exercice) {
                $exerciceId = $exercice->getIdExercice();
                $montantsParExercice[$exerciceId] = $item['montant_cumule'];
            }
        }
        
        // Si on filtre par exercice, utiliser le nouveau template spécialisé
        if ($exerciceFilter) {
            return $this->render('transaction/index_exercice_filtered.html.twig', [
                'transactions_avec_montant' => $transactionsAvecMontant,
                'montants_par_exercice' => $montantsParExercice,
                'solde_precedent' => $soldePrecedent,
                'exercice_precedent_existe' => $soldePrecedent != 0,
                'exercice_filter' => $exerciceFilter,
                'types_transaction' => $typeTransactionRepository->findAll(),
                'personnes' => $personneRepository->findAll(),
                'entreprises' => $entrepriseRepository->findAll(),
                // Filtres
                'libelle_filter' => $libelleFilter,
                'tiers_filter' => $tiersFilter,
                'type_montant_filter' => $typeMontantFilter,
                'montant_min_filter' => $montantMinFilter,
                'montant_max_filter' => $montantMaxFilter,
                'date_min_filter' => $dateMinFilter,
                'date_max_filter' => $dateMaxFilter,
            ]);
        }
        
        return $this->render('transaction/index.html.twig', [
            'transactions_avec_montant' => $transactionsAvecMontant,
            'montants_par_exercice' => $montantsParExercice,
            'solde_precedent' => $soldePrecedent,
            'exercice_precedent_existe' => $exerciceFilter && $soldePrecedent != 0,
            'personnes' => $personneRepository->findAll(),
            'entreprises' => $entrepriseRepository->findAll(),
            'exercices' => $exerciceRepository->findExercicesOuverts(),
            'types_transaction' => $typeTransactionRepository->findAll(),
            'modes_de_paiement' => $modeDePaiementRepository->findAll(),
            'exercice_filter' => $exerciceFilter,
            // Filtres
            'libelle_filter' => $libelleFilter,
            'tiers_filter' => $tiersFilter,
            'type_montant_filter' => $typeMontantFilter,
            'montant_min_filter' => $montantMinFilter,
            'montant_max_filter' => $montantMaxFilter,
            'date_min_filter' => $dateMinFilter,
            'date_max_filter' => $dateMaxFilter,
        ]);
    }

    #[Route('/new', name: 'app_transaction_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TransactionRepository $transactionRepository): Response
    {
        $transaction = new Transaction();
        
        // Pré-remplir le numéro d'ordre avec le suivant disponible pour l'exercice par défaut
        // Le numéro d'ordre sera automatiquement calculé lors de la soumission
        
        $form = $this->createForm(TransactionNewType::class, $transaction);
        $form->handleRequest($request);

        // Debug: vérifier si le formulaire est soumis
        if ($request->isMethod('POST')) {
            error_log('POST reçu pour création transaction');
            error_log('Données POST: ' . json_encode($request->request->all()));
            error_log('Form submitted: ' . ($form->isSubmitted() ? 'OUI' : 'NON'));
            error_log('Form valid: ' . ($form->isValid() ? 'OUI' : 'NON'));
            
            // Debug du montant spécifiquement
            $montantFromForm = $transaction->getMontant();
            error_log('Montant reçu dans l\'entité: ' . var_export($montantFromForm, true));
            
            if (!$form->isValid()) {
                error_log('Erreurs de validation: ' . json_encode($form->getErrors(true, false)));
                foreach ($form->all() as $child) {
                    if (!$child->isValid()) {
                        error_log('Erreur champ ' . $child->getName() . ': ' . json_encode($child->getErrors(true, false)));
                    }
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'exercice est clôturé
            if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
                $this->addFlash('error', 'Impossible de créer une transaction dans un exercice clôturé.');
                return $this->render('transaction/new.html.twig', [
                    'transaction' => $transaction,
                    'form' => $form,
                ]);
            }
            
            // Validation supplémentaire côté serveur
            if (!$transaction->getPersonne() && !$transaction->getEntreprise()) {
                $this->addFlash('error', 'Vous devez sélectionner une personne ou une entreprise.');
                return $this->render('transaction/new.html.twig', [
                    'transaction' => $transaction,
                    'form' => $form,
                ]);
            }
            
            if (!$transaction->getMontant() || $transaction->getMontant() == 0) {
                $this->addFlash('error', 'Le montant ne peut pas être égal à zéro.');
                return $this->render('transaction/new.html.twig', [
                    'transaction' => $transaction,
                    'form' => $form,
                ]);
            }
            
            // Calculer automatiquement le numéro d'ordre en fonction de l'exercice choisi
            if ($transaction->getExercice()) {
                $lastNumeroOrdre = $transactionRepository->getLastNumeroOrdreByExercice($transaction->getExercice()->getIdExercice());
                $transaction->setNumeroOrdre($lastNumeroOrdre + 1);
            }

            $entityManager->persist($transaction);
            $entityManager->flush();

            $this->addFlash('success', 'Transaction créée avec succès !');
            return $this->redirectToRoute('app_transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('transaction/new.html.twig', [
            'transaction' => $transaction,
            'form' => $form,
        ]);
    }

    #[Route('/export-excel', name: 'app_transaction_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, TransactionRepository $transactionRepository, ExerciceRepository $exerciceRepository): Response
    {
        $exerciceId = $request->query->get('exercice_id');
        $exerciceFilter = null;
        
        // Si un exercice est spécifié, le récupérer pour le filtre
        if ($exerciceId) {
            $exerciceFilter = $exerciceRepository->findOneBy(['id_exercice' => $exerciceId]);
        }
        
        // Récupérer les transactions avec leurs relations
        $queryBuilder = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.personne', 'p')
            ->leftJoin('t.entreprise', 'e')
            ->leftJoin('t.exercice', 'ex')
            ->leftJoin('t.type_transaction', 'tt')
            ->leftJoin('t.modeDePaiement', 'mp')
            ->addSelect('p')
            ->addSelect('e')
            ->addSelect('ex')
            ->addSelect('tt')
            ->addSelect('mp');
        
        // Appliquer le filtre par exercice si spécifié
        if ($exerciceFilter) {
            $queryBuilder->where('t.exercice = :exercice')
                        ->setParameter('exercice', $exerciceFilter);
        }
        
        $transactions = $queryBuilder
            ->orderBy('ex.numero_ordre', 'ASC')
            ->addOrderBy('t.numero_ordre', 'ASC')
            ->getQuery()
            ->getResult();

        // Créer le fichier Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Définir le titre de la feuille
        $titre = $exerciceFilter ? 
            'Transactions - Exercice ' . $exerciceFilter->getLibelle() : 
            'Toutes les Transactions';
        $sheet->setTitle('Transactions');
        
        // En-têtes des colonnes
        $headers = [
            'A1' => 'N° Ordre',
            'B1' => 'Exercice', 
            'C1' => 'Date',
            'D1' => 'Libellé',
            'E1' => 'Montant',
            'F1' => 'Type de Compte',
            'G1' => 'Type Transaction',
            'H1' => 'Mode de Paiement',
            'I1' => 'Personne',
            'J1' => 'Entreprise'
        ];
        
        // Appliquer les en-têtes
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Styliser les en-têtes
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        
        // Ajouter les données
        $row = 2;
        foreach ($transactions as $transaction) {
            $sheet->setCellValue('A' . $row, $transaction->getNumeroOrdre());
            $sheet->setCellValue('B' . $row, $transaction->getExercice() ? $transaction->getExercice()->getLibelle() : '');
            $sheet->setCellValue('C' . $row, $transaction->getDateTransaction() ? $transaction->getDateTransaction()->format('d/m/Y') : '');
            $sheet->setCellValue('D' . $row, $transaction->getLibelle());
            $sheet->setCellValue('E' . $row, floatval($transaction->getMontant()));
            $sheet->setCellValue('F' . $row, $transaction->getTypeCompte());
            $sheet->setCellValue('G' . $row, $transaction->getTypeTransaction() ? $transaction->getTypeTransaction()->getLibelle() : '');
            $sheet->setCellValue('H' . $row, $transaction->getModeDePaiement() ? $transaction->getModeDePaiement()->getLibelle() : '');
            $sheet->setCellValue('I' . $row, $transaction->getPersonne() ? $transaction->getPersonne()->getNom() . ' ' . $transaction->getPersonne()->getPrenom() : '');
            $sheet->setCellValue('J' . $row, $transaction->getEntreprise() ? $transaction->getEntreprise()->getNomEntreprise() : '');
            $row++;
        }
        
        // Ajuster automatiquement la largeur des colonnes
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Formater la colonne montant en euros
        $sheet->getStyle('E2:E' . ($row - 1))->getNumberFormat()
            ->setFormatCode('#,##0.00 "€"');
        
        // Créer le nom de fichier
        $filename = $exerciceFilter ? 
            'transactions_exercice_' . $exerciceFilter->getLibelle() . '.xlsx' : 
            'transactions_toutes.xlsx';
        $filename = str_replace(' ', '_', $filename);
        
        // Créer la réponse
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        
        // Capturer le contenu du fichier Excel
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        
        $response->setContent($content);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        
        return $response;
    }

    #[Route('/bulk-update-order', name: 'app_transaction_bulk_update_order', methods: ['POST'])]
    public function bulkUpdateOrder(Request $request, TransactionRepository $transactionRepository, ExerciceRepository $exerciceRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Log de débogage avec environnement
        error_log("=== BULK UPDATE ORDER CALLED ===");
        error_log("Environment: " . ($_ENV['APP_ENV'] ?? 'undefined'));
        error_log("Database URL: " . (isset($_ENV['DATABASE_URL']) ? 'configured' : 'not configured'));
        error_log("Request method: " . $request->getMethod());
        error_log("Content-Type: " . $request->headers->get('Content-Type'));
        error_log("User authenticated: " . ($this->getUser() ? 'YES' : 'NO'));
        
        try {
            $rawContent = $request->getContent();
            error_log("Raw content: " . $rawContent);
            
            $data = json_decode($rawContent, true);
            error_log("Decoded data: " . print_r($data, true));
            
            if (!$data || !isset($data['transactions'])) {
                error_log("Données invalides: " . print_r($data, true));
                return new JsonResponse(['success' => false, 'error' => 'Données invalides'], 400);
            }

            $updated = 0;
            $errors = [];
            $transactionsToUpdate = [];
            
            // ÉTAPE 1: Préparer et valider toutes les transactions
            foreach ($data['transactions'] as $item) {
                if (!isset($item['id']) || !isset($item['order'])) {
                    $errors[] = "Élément manquant id ou order: " . print_r($item, true);
                    continue;
                }
                
                $transaction = $transactionRepository->findOneBy(['id_transaction' => (int)$item['id']]);
                if (!$transaction) {
                    $errors[] = "Transaction non trouvée: " . $item['id'];
                    continue;
                }
                
                // Vérifier que l'exercice n'est pas clôturé
                if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
                    $errors[] = "Exercice clôturé pour transaction: " . $item['id'];
                    continue;
                }
                
                $transactionsToUpdate[] = [
                    'transaction' => $transaction,
                    'newOrder' => (int)$item['order'],
                    'exerciceId' => isset($item['exercice_id']) ? (int)$item['exercice_id'] : null
                ];
            }
            
            if (empty($transactionsToUpdate)) {
                return new JsonResponse(['success' => false, 'error' => 'Aucune transaction valide à mettre à jour'], 400);
            }
            
            // ÉTAPE 2: Assigner les numéros d'ordre définitifs DIRECTEMENT
            // On utilise une structure temporaire pour éviter les conflits
            error_log("Assignation des numéros d'ordre définitifs...");
            
            // Créer un mapping temporaire ID -> nouvel ordre
            $orderMap = [];
            foreach ($transactionsToUpdate as $index => $item) {
                $transaction = $item['transaction'];
                $orderMap[$transaction->getIdTransaction()] = $item['newOrder'];
            }
            
            // Assigner les ordres depuis le mapping
            foreach ($transactionsToUpdate as $item) {
                $transaction = $item['transaction'];
                $newOrder = $orderMap[$transaction->getIdTransaction()];
                $exerciceId = $item['exerciceId'];
                
                // Vérifier que le nouvel ordre est valide (>= 1)
                if ($newOrder < 1) {
                    $errors[] = "Numéro d'ordre invalide pour transaction " . $transaction->getIdTransaction() . ": " . $newOrder;
                    continue;
                }
                
                $transaction->setNumeroOrdre($newOrder);
                error_log("Transaction " . $transaction->getIdTransaction() . " -> ordre final: " . $newOrder);
                
                // Changement d'exercice si spécifié
                if ($exerciceId) {
                    $newExercice = $exerciceRepository->findOneBy(['id_exercice' => $exerciceId]);
                    if ($newExercice && !$newExercice->isClos()) {
                        $transaction->setExercice($newExercice);
                        error_log("Changement exercice pour transaction " . $transaction->getIdTransaction() . " vers " . $exerciceId);
                    } else {
                        $errors[] = "Exercice non trouvé ou clôturé: " . $exerciceId;
                    }
                }
                
                $updated++;
            }
            
            // ÉTAPE 3: Flush final
            error_log("Flush final...");
            $entityManager->flush();
            error_log("Flush final terminé avec succès");
            
            $response = ['success' => true, 'message' => "{$updated} transactions mises à jour", 'errors' => $errors];
            error_log("Réponse: " . print_r($response, true));
            
            return new JsonResponse($response);
            
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            error_log("ERREUR CONTRAINTE UNIQUE: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'error' => 'Conflit de numéro d\'ordre: ' . $e->getMessage()], 500);
        } catch (\Doctrine\DBAL\Exception $e) {
            error_log("ERREUR BASE DE DONNÉES: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'error' => 'Erreur base de données: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            error_log("ERREUR GÉNÉRALE BULK UPDATE: " . $e->getMessage());
            error_log("Classe d'exception: " . get_class($e));
            error_log("Code d'erreur: " . $e->getCode());
            error_log("Fichier: " . $e->getFile() . " ligne: " . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage(), 'class' => get_class($e)], 500);
        }
    }

    #[Route('/admin/fix-order', name: 'app_transaction_fix_order', methods: ['POST'])]
    public function fixTransactionOrder(ExerciceRepository $exerciceRepository, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $results = [];
            $totalFixed = 0;

            // Récupérer tous les exercices
            $exercices = $exerciceRepository->findAll();
            
            if (empty($exercices)) {
                return new JsonResponse(['success' => false, 'error' => 'Aucun exercice trouvé'], 400);
            }

            foreach ($exercices as $exercice) {
                // Récupérer toutes les transactions de cet exercice triées par numéro d'ordre actuel
                $transactions = $transactionRepository->findBy(
                    ['exercice' => $exercice],
                    ['numero_ordre' => 'ASC']
                );

                if (empty($transactions)) {
                    continue;
                }

                $exerciceResults = [];
                
                // ÉTAPE 1 : Assigner des numéros temporaires très grands pour éviter les conflits
                $tempOrder = 100000;
                foreach ($transactions as $transaction) {
                    $transaction->setNumeroOrdre($tempOrder);
                    $tempOrder++;
                }
                $entityManager->flush();
                
                // ÉTAPE 2 : Maintenant renumméroter correctement de 1 à N
                $newOrder = 1;
                foreach ($transactions as $transaction) {
                    $oldOrder = $transaction->getNumeroOrdre();
                    $transaction->setNumeroOrdre($newOrder);
                    
                    $exerciceResults[] = [
                        'id' => $transaction->getIdTransaction(),
                        'old_order' => $oldOrder,
                        'new_order' => $newOrder
                    ];
                    $totalFixed++;
                    $newOrder++;
                }
                
                // Flush final pour cet exercice
                $entityManager->flush();

                if (!empty($exerciceResults)) {
                    $results[$exercice->getLibelle()] = $exerciceResults;
                }
            }

            return new JsonResponse([
                'success' => true,
                'message' => "{$totalFixed} transactions renummérotées avec succès",
                'total_fixed' => $totalFixed,
                'details' => $results
            ]);

        } catch (\Exception $e) {
            error_log("Erreur lors du fix-order: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sort-by-date', name: 'app_transaction_sort_by_date', methods: ['POST'])]
    public function sortByDate(Request $request, ExerciceRepository $exerciceRepository, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $exerciceId = $data['exercice_id'] ?? null;
            
            error_log("=== SORT BY DATE ===");
            error_log("Exercise ID: " . ($exerciceId ?? "null"));
            
            $results = [];
            $totalSorted = 0;

            if ($exerciceId) {
                // Trier un exercice spécifique
                $exercice = $exerciceRepository->findOneBy(['id_exercice' => $exerciceId]);
                if (!$exercice) {
                    error_log("Exercice not found: " . $exerciceId);
                    return new JsonResponse(['success' => false, 'error' => 'Exercice non trouvé'], 400);
                }
                
                error_log("Sorting exercice: " . $exercice->getLibelle());
                
                // Utiliser DQL au lieu de findBy pour un tri vraiment fiable
                $qb = $transactionRepository->createQueryBuilder('t')
                    ->where('t.exercice = :exercice')
                    ->setParameter('exercice', $exercice)
                    ->orderBy('t.date_transaction', 'ASC')
                    ->addOrderBy('t.id_transaction', 'ASC');
                
                $transactions = $qb->getQuery()->getResult();
                error_log("Found " . count($transactions) . " transactions");
                
                // Log first 3 transactions before sorting
                $idx = 0;
                foreach ($transactions as $tx) {
                    if ($idx >= 3) break;
                    error_log("Pre-sort TX " . $tx->getIdTransaction() . ": " . ($tx->getDateTransaction()?->format('Y-m-d') ?? 'NULL'));
                    $idx++;
                }

                if (!empty($transactions)) {
                    // ÉTAPE 1 : Numéros temporaires
                    $tempOrder = 100000;
                    foreach ($transactions as $transaction) {
                        $transaction->setNumeroOrdre($tempOrder);
                        $tempOrder++;
                    }
                    $entityManager->flush();
                    error_log("Temporary orders assigned");
                    
                    // ÉTAPE 2 : Renumméroter par date de 1 à N
                    $newOrder = 1;
                    $exerciceResults = [];
                    foreach ($transactions as $transaction) {
                        $transaction->setNumeroOrdre($newOrder);
                        $exerciceResults[] = [
                            'id' => $transaction->getIdTransaction(),
                            'date' => $transaction->getDateTransaction()?->format('Y-m-d'),
                            'new_order' => $newOrder
                        ];
                        error_log("TX " . $transaction->getIdTransaction() . " -> order " . $newOrder . " (date: " . ($transaction->getDateTransaction()?->format('Y-m-d') ?? 'NULL') . ")");
                        $totalSorted++;
                        $newOrder++;
                    }
                    $entityManager->flush();
                    error_log("Final flush done");
                    
                    $results[$exercice->getLibelle()] = $exerciceResults;
                }
            } else {
                // Trier TOUS les exercices
                $exercices = $exerciceRepository->findAll();
                error_log("Sorting all " . count($exercices) . " exercices");
                
                foreach ($exercices as $exercice) {
                    error_log("Processing exercice: " . $exercice->getLibelle());
                    
                    $qb = $transactionRepository->createQueryBuilder('t')
                        ->where('t.exercice = :exercice')
                        ->setParameter('exercice', $exercice)
                        ->orderBy('t.date_transaction', 'ASC')
                        ->addOrderBy('t.id_transaction', 'ASC');
                    
                    $transactions = $qb->getQuery()->getResult();
                    error_log("Found " . count($transactions) . " transactions for this exercice");

                    if (empty($transactions)) {
                        continue;
                    }

                    // ÉTAPE 1 : Numéros temporaires
                    $tempOrder = 100000;
                    foreach ($transactions as $transaction) {
                        $transaction->setNumeroOrdre($tempOrder);
                        $tempOrder++;
                    }
                    $entityManager->flush();
                    
                    // ÉTAPE 2 : Renumméroter par date de 1 à N
                    $newOrder = 1;
                    $exerciceResults = [];
                    foreach ($transactions as $transaction) {
                        $transaction->setNumeroOrdre($newOrder);
                        $exerciceResults[] = [
                            'id' => $transaction->getIdTransaction(),
                            'date' => $transaction->getDateTransaction()?->format('Y-m-d'),
                            'new_order' => $newOrder
                        ];
                        $totalSorted++;
                        $newOrder++;
                    }
                    $entityManager->flush();

                    if (!empty($exerciceResults)) {
                        $results[$exercice->getLibelle()] = $exerciceResults;
                    }
                }
            }

            error_log("Total sorted: " . $totalSorted);
            return new JsonResponse([
                'success' => true,
                'message' => "{$totalSorted} transactions triées par date avec succès",
                'total_sorted' => $totalSorted,
                'details' => $results
            ]);

        } catch (\Exception $e) {
            error_log("Error in sort-by-date: " . $e->getMessage());
            error_log("Stack: " . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }


    #[Route('/{id_transaction}', name: 'app_transaction_show', methods: ['GET'])]
    public function show(Request $request, int $id_transaction, TransactionRepository $transactionRepository, ExerciceRepository $exerciceRepository): Response
    {
        $transaction = $transactionRepository->findOneBy(['id_transaction' => $id_transaction]);
        
        if (!$transaction) {
            throw $this->createNotFoundException('Transaction non trouvée');
        }

        // Détecter si on vient d'un contexte d'exercice filtré
        $exerciceId = $request->query->get('exercice_id');
        $exerciceFilter = null;
        
        if ($exerciceId) {
            $exerciceFilter = $exerciceRepository->findOneBy(['id_exercice' => $exerciceId]);
        } elseif ($transaction->getExercice()) {
            // Si pas d'exercice spécifié mais la transaction a un exercice, l'utiliser pour le contexte
            $exerciceFilter = $transaction->getExercice();
        }

        // Choisir le template selon le contexte
        $template = $exerciceFilter ? 'transaction/show_exercice_filtered.html.twig' : 'transaction/show.html.twig';

        return $this->render($template, [
            'transaction' => $transaction,
            'exercice_filter' => $exerciceFilter,
        ]);
    }

    #[Route('/{id_transaction}/edit', name: 'app_transaction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_transaction, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager, ExerciceRepository $exerciceRepository): Response
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

        // Détecter si on vient d'un contexte d'exercice filtré
        $exerciceId = $request->query->get('exercice_id');
        $exerciceFilter = null;
        
        if ($exerciceId) {
            $exerciceFilter = $exerciceRepository->findOneBy(['id_exercice' => $exerciceId]);
        } elseif ($transaction->getExercice()) {
            // Si pas d'exercice spécifié mais la transaction a un exercice, l'utiliser pour le contexte
            $exerciceFilter = $transaction->getExercice();
        }

        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer automatiquement le numéro d'ordre si non défini (nouvelle transaction)
            if ($transaction->getNumeroOrdre() === null && $transaction->getExercice()) {
                $lastNumeroOrdre = $transactionRepository->getLastNumeroOrdreByExercice($transaction->getExercice()->getIdExercice());
                $transaction->setNumeroOrdre($lastNumeroOrdre + 1);
            }
            
            $entityManager->flush();

            // Rediriger vers le bon contexte selon d'où on vient
            if ($exerciceFilter && $request->query->get('exercice_id')) {
                return $this->redirectToRoute('app_transaction_index', ['exercice_id' => $exerciceFilter->getIdExercice()], Response::HTTP_SEE_OTHER);
            }
            
            return $this->redirectToRoute('app_transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        // Choisir le template selon le contexte
        $template = $exerciceFilter ? 'transaction/edit_exercice_filtered.html.twig' : 'transaction/edit.html.twig';

        return $this->render($template, [
            'transaction' => $transaction,
            'form' => $form,
            'exercice_filter' => $exerciceFilter,
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
    public function updateField(Request $request, int $id_transaction, TransactionRepository $transactionRepository, PersonneRepository $personneRepository, EntrepriseRepository $entrepriseRepository, ExerciceRepository $exerciceRepository, TypeTransactionRepository $typeTransactionRepository, ModeDePaiementRepository $modeDePaiementRepository, EntityManagerInterface $entityManager): JsonResponse
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
                    
                case 'mode_de_paiement':
                    if (empty($value)) {
                        $transaction->setModeDePaiement(null);
                    } else {
                        $modeDePaiement = $modeDePaiementRepository->findOneBy(['id' => intval($value)]);
                        if (!$modeDePaiement) {
                            return new JsonResponse(['success' => false, 'message' => 'Mode de paiement non trouvé'], 400);
                        }
                        $transaction->setModeDePaiement($modeDePaiement);
                    }
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
            return new JsonResponse(['success' => false, 'error' => 'Transaction non trouvée'], 404);
        }

        // Vérifier si l'exercice de la transaction est clôturé
        if ($transaction->getExercice() && $transaction->getExercice()->isClos()) {
            return new JsonResponse(['success' => false, 'error' => 'Impossible de supprimer une transaction d\'un exercice clôturé'], 403);
        }

        try {
            // Si c'est une transaction liée à une autre (transfert livret), supprimer aussi la transaction liée
            if ($transaction->getTransactionLieeId()) {
                $transactionLiee = $transactionRepository->find($transaction->getTransactionLieeId());
                if ($transactionLiee) {
                    $entityManager->remove($transactionLiee);
                }
            }
            
            // Chercher les transactions qui pointent vers celle-ci
            $transactionsLiees = $transactionRepository->findBy(['transaction_liee_id' => $transaction->getIdTransaction()]);
            foreach ($transactionsLiees as $transactionLiee) {
                $entityManager->remove($transactionLiee);
            }
            
            $entityManager->remove($transaction);
            $entityManager->flush();
            
            return new JsonResponse(['success' => true, 'message' => 'Transaction supprimée avec succès']);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }

}