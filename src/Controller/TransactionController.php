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
        
        // Récupérer les transactions triées par numéro d'ordre avec leurs relations pour éviter les requêtes N+1
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
        
        $transactions = $queryBuilder
            ->orderBy('ex.numero_ordre', 'ASC')
            ->addOrderBy('t.numero_ordre', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Calculer le solde de l'exercice précédent si on filtre par exercice
        $soldePrecedent = 0;
        if ($exerciceFilter) {
            $exercicePrecedent = $exerciceRepository->findPreviousExercice($exerciceFilter);
            if ($exercicePrecedent) {
                $soldePrecedent = $transactionRepository->calculateSoldeByExercice($exercicePrecedent->getIdExercice());
            }
        }
        
        // Calculer le montant cumulé pour chaque transaction
        $montantCumule = $soldePrecedent;
        $transactionsAvecMontant = [];
        
        foreach ($transactions as $transaction) {
            // Pour les transactions livret, inverser le montant pour le point de vue du compte courant
            $montantTransaction = $transaction->getMontant();
            if ($transaction->getTypeCompte() === 'livret') {
                $montantTransaction = -$montantTransaction; // Inverser le signe
            }
            
            $montantCumule += $montantTransaction;
            $transactionsAvecMontant[] = [
                'transaction' => $transaction,
                'montant_cumule' => $montantCumule,
                'montant_compte_courant' => $montantTransaction // Montant du point de vue du compte courant
            ];
        }
        
        // Si on filtre par exercice, utiliser le template simple
        if ($exerciceFilter) {
            return $this->render('transaction/index_exercice_simple.html.twig', [
                'transactions' => $transactions,
                'exercice_filter' => $exerciceFilter,
            ]);
        }
        
        return $this->render('transaction/index.html.twig', [
            'transactions_avec_montant' => $transactionsAvecMontant,
            'solde_precedent' => $soldePrecedent,
            'exercice_precedent_existe' => $exerciceFilter && $soldePrecedent != 0,
            'personnes' => $personneRepository->findAll(),
            'entreprises' => $entrepriseRepository->findAll(),
            'exercices' => $exerciceRepository->findExercicesOuverts(),
            'types_transaction' => $typeTransactionRepository->findAll(),
            'modes_de_paiement' => $modeDePaiementRepository->findAll(),
            'exercice_filter' => $exerciceFilter,
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
            // Générer automatiquement le numéro d'ordre si non défini (nouvelle transaction)
            if ($transaction->getNumeroOrdre() === null && $transaction->getExercice()) {
                $lastNumeroOrdre = $transactionRepository->getLastNumeroOrdreByExercice($transaction->getExercice()->getIdExercice());
                $transaction->setNumeroOrdre($lastNumeroOrdre + 1);
            }
            
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