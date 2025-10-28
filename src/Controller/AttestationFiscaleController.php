<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\Personne;
use App\Entity\Entreprise;
use App\Repository\TransactionRepository;
use App\Repository\ExerciceRepository;
use App\Repository\TypeTransactionRepository;
use App\Repository\PersonneRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/attestation-fiscale')]
final class AttestationFiscaleController extends AbstractController
{
    #[Route(name: 'app_attestation_fiscale_index', methods: ['GET'])]
    public function index(
        TransactionRepository $transactionRepository,
        TypeTransactionRepository $typeTransactionRepository
    ): Response {
        // Récupérer le type de transaction "cotisation"
        $typeCotisation = $typeTransactionRepository->findOneBy(['libelle' => 'cotisation']);
        
        if (!$typeCotisation) {
            $this->addFlash('error', 'Le type de transaction "cotisation" n\'existe pas. Veuillez le créer d\'abord.');
            return $this->redirectToRoute('app_type_transaction_index');
        }
        
        // Récupérer toutes les cotisations des personnes
        $cotisations = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.personne', 'p')
            ->leftJoin('t.exercice', 'ex')
            ->addSelect('p')
            ->addSelect('ex')
            ->where('t.type_transaction = :typeCotisation')
            ->andWhere('t.montant > 0') // Seulement les montants positifs (recettes)
            ->andWhere('t.personne IS NOT NULL') // Seulement les cotisations liées à une personne
            ->setParameter('typeCotisation', $typeCotisation)
            ->orderBy('p.nom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->addOrderBy('t.date_transaction', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Grouper les cotisations par personne
        $cotisationsGroupees = $this->grouperCotisationsParPersonne($cotisations);
        
        return $this->render('attestation_fiscale/index.html.twig', [
            'cotisations_groupees' => $cotisationsGroupees,
            'type_cotisation' => $typeCotisation,
        ]);
    }
    
    #[Route('/recherche-personne', name: 'app_attestation_fiscale_recherche_personne', methods: ['GET'])]
    public function recherchePersonne(
        Request $request,
        PersonneRepository $personneRepository
    ): JsonResponse {
        $query = $request->query->get('q', '');
        
        $results = [];
        
        if (strlen($query) >= 2) {
            // Recherche uniquement dans les personnes
            $personnes = $personneRepository->createQueryBuilder('p')
                ->where('LOWER(p.nom) LIKE :query OR LOWER(p.prenom) LIKE :query OR CONCAT(LOWER(p.prenom), \' \', LOWER(p.nom)) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%')
                ->setMaxResults(15)
                ->orderBy('p.nom', 'ASC')
                ->addOrderBy('p.prenom', 'ASC')
                ->getQuery()
                ->getResult();
                
            foreach ($personnes as $personne) {
                $results[] = [
                    'id' => $personne->getIdPersonne(),
                    'label' => $personne->getPrenom() . ' ' . $personne->getNom(),
                    'email' => $personne->getEmail(),
                    'ville' => $personne->getVille(),
                ];
            }
        }
        
        return new JsonResponse($results);
    }
    
    #[Route('/personne/{personne_id}', name: 'app_attestation_fiscale_personne_details', methods: ['GET'])]
    public function detailsPersonne(
        int $personne_id,
        TransactionRepository $transactionRepository,
        TypeTransactionRepository $typeTransactionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer la personne
        $personne = $entityManager->getRepository(Personne::class)->find($personne_id);
        
        if (!$personne) {
            throw $this->createNotFoundException('Personne non trouvée.');
        }
        
        // Récupérer le type de transaction "cotisation"
        $typeCotisation = $typeTransactionRepository->findOneBy(['libelle' => 'cotisation']);
        
        if (!$typeCotisation) {
            $this->addFlash('error', 'Le type de transaction "cotisation" n\'existe pas.');
            return $this->redirectToRoute('app_attestation_fiscale_index');
        }
        
        // Récupérer toutes les cotisations de la personne
        $cotisations = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.modeDePaiement', 'mdp')
            ->leftJoin('t.exercice', 'ex')
            ->addSelect('mdp')
            ->addSelect('ex')
            ->where('t.type_transaction = :typeCotisation')
            ->andWhere('t.montant > 0')
            ->andWhere('t.personne = :personne')
            ->setParameter('typeCotisation', $typeCotisation)
            ->setParameter('personne', $personne)
            ->orderBy('t.date_transaction', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->render('attestation_fiscale/personne_details.html.twig', [
            'personne' => $personne,
            'cotisations' => $cotisations,
            'total_cotisations' => count($cotisations),
            'montant_total_global' => array_sum(array_map(fn($c) => (float)$c->getMontant(), $cotisations)),
        ]);
    }
    
    #[Route('/generer-selection/{personne_id}', name: 'app_attestation_fiscale_generer_selection', methods: ['POST'])]
    public function genererAttestationSelection(
        int $personne_id,
        Request $request,
        TransactionRepository $transactionRepository,
        TypeTransactionRepository $typeTransactionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $personne = $entityManager->getRepository(Personne::class)->find($personne_id);
        
        if (!$personne) {
            throw $this->createNotFoundException('Personne non trouvée.');
        }
        
        // Récupérer les IDs des cotisations sélectionnées
        $cotisationIds = $request->request->all('cotisations');
        $anneeAttestation = $request->request->get('annee_attestation', date('Y'));
        
        if (empty($cotisationIds)) {
            $this->addFlash('error', 'Veuillez sélectionner au moins une cotisation.');
            return $this->redirectToRoute('app_attestation_fiscale_personne_details', ['personne_id' => $personne_id]);
        }
        
        // Récupérer le type de transaction "cotisation"
        $typeCotisation = $typeTransactionRepository->findOneBy(['libelle' => 'cotisation']);
        
        if (!$typeCotisation) {
            throw $this->createNotFoundException('Le type de transaction "cotisation" n\'existe pas.');
        }
        
        // Récupérer les cotisations sélectionnées
        $cotisations = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.modeDePaiement', 'mdp')
            ->addSelect('mdp')
            ->where('t.id_transaction IN (:ids)')
            ->andWhere('t.type_transaction = :typeCotisation')
            ->andWhere('t.personne = :personne')
            ->andWhere('t.montant > 0')
            ->setParameter('ids', $cotisationIds)
            ->setParameter('typeCotisation', $typeCotisation)
            ->setParameter('personne', $personne)
            ->orderBy('t.date_transaction', 'ASC')
            ->getQuery()
            ->getResult();
        
        if (empty($cotisations)) {
            $this->addFlash('error', 'Aucune cotisation valide trouvée avec la sélection.');
            return $this->redirectToRoute('app_attestation_fiscale_personne_details', ['personne_id' => $personne_id]);
        }
        
        // Calculer le montant total des cotisations sélectionnées
        $montantTotal = array_sum(array_map(fn($c) => (float)$c->getMontant(), $cotisations));
        
        // Générer un numéro d'ordre unique pour l'attestation
        $numeroOrdre = $this->genererNumeroOrdreSelection($anneeAttestation, $personne_id, $cotisationIds);
        
        // Générer le PDF
        return $this->genererPDF($personne, 'personne', $cotisations, $montantTotal, $anneeAttestation, $numeroOrdre);
    }
    
    #[Route('/generer/{donateur_type}/{donateur_id}', name: 'app_attestation_fiscale_generer', methods: ['GET'])]
    public function genererAttestation(
        string $donateur_type,
        int $donateur_id,
        Request $request,
        TransactionRepository $transactionRepository,
        TypeTransactionRepository $typeTransactionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $annee = $request->query->get('annee', date('Y'));
        
        // Récupérer le donateur
        $donateur = null;
        if ($donateur_type === 'personne') {
            $donateur = $entityManager->getRepository(Personne::class)->find($donateur_id);
        } elseif ($donateur_type === 'entreprise') {
            $donateur = $entityManager->getRepository(Entreprise::class)->find($donateur_id);
        }
        
        if (!$donateur) {
            throw $this->createNotFoundException('Donateur non trouvé.');
        }
        
        // Récupérer le type de transaction "cotisation"
        $typeCotisation = $typeTransactionRepository->findOneBy(['libelle' => 'cotisation']);
        
        if (!$typeCotisation) {
            throw $this->createNotFoundException('Le type de transaction "cotisation" n\'existe pas.');
        }
        
        // Récupérer toutes les cotisations du donateur pour l'année
        $queryBuilder = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.modeDePaiement', 'mdp')
            ->addSelect('mdp')
            ->where('t.type_transaction = :typeCotisation')
            ->andWhere('t.montant > 0')
            ->andWhere('YEAR(t.date_transaction) = :annee')
            ->setParameter('typeCotisation', $typeCotisation)
            ->setParameter('annee', $annee)
            ->orderBy('t.date_transaction', 'ASC');
        
        if ($donateur_type === 'personne') {
            $queryBuilder->andWhere('t.personne = :donateur');
        } else {
            $queryBuilder->andWhere('t.entreprise = :donateur');
        }
        
        $queryBuilder->setParameter('donateur', $donateur);
        
        $cotisations = $queryBuilder->getQuery()->getResult();
        
        if (empty($cotisations)) {
            $this->addFlash('error', 'Aucune cotisation trouvée pour ce donateur en ' . $annee);
            return $this->redirectToRoute('app_attestation_fiscale_index');
        }
        
        // Calculer le montant total des dons
        $montantTotal = array_sum(array_map(fn($c) => (float)$c->getMontant(), $cotisations));
        
        // Générer un numéro d'ordre unique pour l'attestation
        $numeroOrdre = $this->genererNumeroOrdre($annee, $donateur_type, $donateur_id);
        
        // Générer le PDF
        return $this->genererPDF($donateur, $donateur_type, $cotisations, $montantTotal, $annee, $numeroOrdre);
    }
    
    private function grouperCotisationsParPersonne(array $cotisations): array
    {
        $groupes = [];
        
        foreach ($cotisations as $cotisation) {
            // Ne traiter que les cotisations liées à une personne
            if ($cotisation->getPersonne()) {
                $personne = $cotisation->getPersonne();
                $cle = 'personne_' . $personne->getIdPersonne();
                
                if (!isset($groupes[$cle])) {
                    $groupes[$cle] = [
                        'personne' => $personne,
                        'cotisations' => [],
                        'montant_total' => 0,
                        'annees' => []
                    ];
                }
                
                $groupes[$cle]['cotisations'][] = $cotisation;
                $groupes[$cle]['montant_total'] += (float)$cotisation->getMontant();
                
                $annee = $cotisation->getDateTransaction()->format('Y');
                if (!in_array($annee, $groupes[$cle]['annees'])) {
                    $groupes[$cle]['annees'][] = $annee;
                }
            }
        }
        
        // Trier les années pour chaque personne
        foreach ($groupes as &$groupe) {
            sort($groupe['annees']);
        }
        
        return $groupes;
    }
    
    private function genererNumeroOrdre(string $annee, string $donateurType, int $donateurId): string
    {
        // Format: ANNEE-TYPE-ID-TIMESTAMP
        $timestamp = time();
        $typeCode = $donateurType === 'personne' ? 'P' : 'E';
        return $annee . '-' . $typeCode . $donateurId . '-' . $timestamp;
    }
    
    private function genererNumeroOrdreSelection(string $annee, int $personneId, array $cotisationIds): string
    {
        // Format: ANNEE-P-ID-SELECTION-HASH
        $hash = substr(md5(implode('-', $cotisationIds)), 0, 8);
        return $annee . '-P' . $personneId . '-SEL-' . $hash;
    }
    
    private function genererPDF($donateur, string $donateurType, array $cotisations, float $montantTotal, string $annee, string $numeroOrdre): Response
    {
        // Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML de l'attestation
        $html = $this->renderView('attestation_fiscale/attestation_pdf.html.twig', [
            'donateur' => $donateur,
            'donateur_type' => $donateurType,
            'cotisations' => $cotisations,
            'montant_total' => $montantTotal,
            'annee' => $annee,
            'numero_ordre' => $numeroOrdre,
            'date_generation' => new \DateTime(),
        ]);
        
        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        
        // Définir la taille du papier et l'orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Générer le PDF
        $dompdf->render();
        
        // Nom du fichier
        $nomDonateur = $donateurType === 'personne' 
            ? $donateur->getPrenom() . '_' . $donateur->getNom()
            : $donateur->getNomEntreprise();
        $nomFichier = 'Attestation_Fiscale_' . $annee . '_' . $nomDonateur . '.pdf';
        
        // Retourner la réponse PDF
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $nomFichier . '"'
            ]
        );
    }
}