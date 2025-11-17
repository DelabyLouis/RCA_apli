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
        $typeCotisation = $typeTransactionRepository->findOneBy(['libelle' => 'Cotisation']);
        
        if (!$typeCotisation) {
            $this->addFlash('error', 'Le type de transaction "Cotisation" n\'existe pas. Veuillez le créer d\'abord.');
            return $this->redirectToRoute('app_type_transaction_index');
        }
        
        // Récupérer toutes les cotisations des personnes
        $cotisations = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.personne', 'p')
            ->leftJoin('t.exercice', 'ex')
            ->leftJoin('t.modeDePaiement', 'mdp')
            ->addSelect('p')
            ->addSelect('ex')
            ->addSelect('mdp')
            ->where('t.type_transaction = :typeCotisation')
            ->andWhere('t.montant > 0') // Seulement les montants positifs (recettes)
            ->andWhere('t.personne IS NOT NULL') // Seulement les cotisations liées à une personne
            ->setParameter('typeCotisation', $typeCotisation)
            ->orderBy('t.date_transaction', 'DESC')
            ->addOrderBy('p.nom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('attestation_fiscale/index.html.twig', [
            'cotisations' => $cotisations,
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
        $typeCotisation = $typeTransactionRepository->findOneBy(['libelle' => 'Cotisation']);
        
        if (!$typeCotisation) {
            $this->addFlash('error', 'Le type de transaction "Cotisation" n\'existe pas.');
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
    
    #[Route('/generer-selection/{personne_id}', name: 'app_attestation_fiscale_generer_selection', methods: ['GET', 'POST'])]
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
        $cotisationIds = $request->request->all('cotisations') ?: $request->query->all('cotisations');
        
        // Si c'est une requête GET sans cotisations (rafraîchissement), rediriger vers la page de sélection
        if (empty($cotisationIds) && $request->isMethod('GET')) {
            return $this->redirectToRoute('app_attestation_fiscale_personne_details', ['personne_id' => $personne_id]);
        }
        
        if (empty($cotisationIds)) {
            $this->addFlash('error', 'Veuillez sélectionner au moins une cotisation.');
            return $this->redirectToRoute('app_attestation_fiscale_personne_details', ['personne_id' => $personne_id]);
        }
        
        // Récupérer le type de transaction "cotisation"
        $typeCotisation = $typeTransactionRepository->findOneBy(['libelle' => 'Cotisation']);
        
        if (!$typeCotisation) {
            throw $this->createNotFoundException('Le type de transaction "Cotisation" n\'existe pas.');
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
        
        // Déduire l'année d'attestation à partir de la première cotisation sélectionnée
        $anneeAttestation = $cotisations[0]->getDateTransaction()->format('Y');
        
        // Générer plusieurs PDF séparés (un par cotisation)
        return $this->genererMultiplesPDF($personne, $cotisations, $anneeAttestation);
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
        
        // Encoder les images en base64
        $projectDir = $this->getParameter('kernel.project_dir');
        $signatureBase64 = '';
        $logoBase64 = '';
        
        // Utiliser les fichiers source dans assets/ pour éviter les problèmes de hash
        $signaturePath = $projectDir . '/assets/images/Signature.jpg';
        if (file_exists($signaturePath)) {
            $signatureData = file_get_contents($signaturePath);
            $signatureBase64 = 'data:image/jpeg;base64,' . base64_encode($signatureData);
        }
        
        $logoPath = $projectDir . '/assets/images/Logo_Cerfa.svg.png';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
        
        // Générer le HTML de l'attestation
        $html = $this->renderView('attestation_fiscale/attestation_pdf.html.twig', [
            'donateur' => $donateur,
            'donateur_type' => $donateurType,
            'cotisations' => $cotisations,
            'montant_total' => $montantTotal,
            'annee' => $annee,
            'numero_ordre' => $numeroOrdre,
            'date_generation' => new \DateTime(),
            'signature_base64' => $signatureBase64,
            'logo_base64' => $logoBase64,
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
    
    private function genererMultiplesPDF($personne, array $cotisations, string $anneeAttestation): Response
    {
        // Si une seule cotisation, générer un seul PDF
        if (count($cotisations) === 1) {
            $cotisation = $cotisations[0];
            $anneeCotisation = $cotisation->getDateTransaction()->format('Y');
            $numeroOrdre = $this->genererNumeroOrdreSelection($anneeCotisation, $personne->getIdPersonne(), [$cotisation->getIdTransaction()]);
            return $this->genererPDF($personne, 'personne', [$cotisation], (float)$cotisation->getMontant(), $anneeCotisation, $numeroOrdre);
        }
        
        // Créer un ZIP avec tous les PDFs
        $zip = new \ZipArchive();
        $zipFilename = tempnam(sys_get_temp_dir(), 'attestations_') . '.zip';
        
        if ($zip->open($zipFilename, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Impossible de créer le fichier ZIP');
        }
        
        foreach ($cotisations as $index => $cotisation) {
            // Utiliser l'année de la cotisation individuelle
            $anneeCotisation = $cotisation->getDateTransaction()->format('Y');
            
            // Générer un numéro d'ordre unique pour chaque cotisation
            $numeroOrdre = $this->genererNumeroOrdreSelection($anneeCotisation, $personne->getIdPersonne(), [$cotisation->getIdTransaction()]);
            
            // Créer un PDF temporaire pour cette cotisation
            $pdfResponse = $this->genererPDF($personne, 'personne', [$cotisation], (float)$cotisation->getMontant(), $anneeCotisation, $numeroOrdre);
            
            // Nom du fichier PDF
            $pdfFilename = sprintf(
                'Attestation_%s_%s_%s_%d.pdf',
                $anneeCotisation,
                $personne->getPrenom(),
                $personne->getNom(),
                $index + 1
            );
            
            // Ajouter le PDF au ZIP
            $zip->addFromString($pdfFilename, $pdfResponse->getContent());
        }
        
        $zip->close();
        
        // Nom du fichier ZIP
        $zipName = sprintf(
            'Attestations_%s_%s_%s.zip',
            $anneeAttestation,
            $personne->getPrenom(),
            $personne->getNom()
        );
        
        // Retourner le ZIP
        $response = new Response(file_get_contents($zipFilename));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $zipName . '"');
        
        // Supprimer le fichier temporaire
        unlink($zipFilename);
        
        return $response;
    }
}