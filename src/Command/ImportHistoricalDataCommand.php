<?php

namespace App\Command;

use App\Entity\Exercice;
use App\Entity\Personne;
use App\Entity\TypeTransaction;
use App\Entity\Transaction;
use App\Entity\ModeDePaiement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-historical-data',
    description: 'Import historical data from RCA amicale Excel file'
)]
class ImportHistoricalDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Import des données historiques de l\'Amicale RCA');

        try {
            // Créer les exercices
            $this->createExercices($io);
            $this->entityManager->flush(); // Flush pour avoir les IDs
            
            // Créer les types de transactions
            $this->createTypeTransactions($io);
            $this->entityManager->flush(); // Flush pour avoir les IDs
            
            // Créer les modes de paiement
            $this->createModesDePayement($io);
            $this->entityManager->flush(); // Flush pour avoir les IDs
            
            // Créer les personnes
            $this->createPersonnes($io);
            $this->entityManager->flush(); // Flush pour avoir les IDs
            
            // Créer les transactions 2022-2023
            $this->createTransactions2022_2023($io);
            $this->entityManager->flush(); // Flush pour avoir les IDs
            
            // Créer les transactions 2023-2024
            $this->createTransactions2023_2024($io);
            $this->entityManager->flush(); // Flush final

            $io->success('Import des données historiques terminé avec succès !');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'import : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createExercices(SymfonyStyle $io): void
    {
        $io->section('Création des exercices');

        $exercices = [
            ['libelle' => 'Exercice 2022-2023', 'date_debut' => '2022-09-01', 'date_fin' => '2023-08-31', 'clos' => true],
            ['libelle' => 'Exercice 2023-2024', 'date_debut' => '2023-09-01', 'date_fin' => '2024-08-31', 'clos' => true],
            ['libelle' => 'Exercice 2024-2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31', 'clos' => false],
        ];

        $numeroOrdre = 1;
        foreach ($exercices as $data) {
            $exercice = new Exercice();
            $exercice->setLibelle($data['libelle']);
            $exercice->setDateDebut(new \DateTime($data['date_debut']));
            $exercice->setDateFin(new \DateTime($data['date_fin']));
            $exercice->setClos($data['clos']);
            $exercice->setNumeroOrdre($numeroOrdre++);

            $this->entityManager->persist($exercice);
            $io->text("Exercice créé : {$data['libelle']}");
        }
    }

    private function createTypeTransactions(SymfonyStyle $io): void
    {
        $io->section('Création des types de transactions');

        $types = [
            ['libelle' => 'Cotisation', 'type_montant' => 'credit'],
            ['libelle' => 'Repas amicale', 'type_montant' => 'credit'],
            ['libelle' => 'Achats', 'type_montant' => 'debit'],
            ['libelle' => 'Frais bancaires', 'type_montant' => 'debit'],
            ['libelle' => 'Ristourne', 'type_montant' => 'credit'],
            ['libelle' => 'Report à nouveau', 'type_montant' => 'credit'],
        ];

        foreach ($types as $data) {
            $type = new TypeTransaction();
            $type->setLibelle($data['libelle']);
            $type->setTypeMontantAutorise($data['type_montant']);

            $this->entityManager->persist($type);
            $io->text("Type créé : {$data['libelle']} ({$data['type_montant']})");
        }
    }

    private function createModesDePayement(SymfonyStyle $io): void
    {
        $io->section('Création des modes de paiement');

        $modes = [
            'Chèque',
            'Virement',
            'Espèces',
            'Prélèvement',
        ];

        foreach ($modes as $libelle) {
            $mode = new ModeDePaiement();
            $mode->setLibelle($libelle);

            $this->entityManager->persist($mode);
            $io->text("Mode créé : {$libelle}");
        }
    }

    private function createPersonnes(SymfonyStyle $io): void
    {
        $io->section('Création des personnes');

        $personnes = [
            ['nom' => 'GAUTHEROT', 'prenom' => 'Didier'],
            ['nom' => 'CLAY', 'prenom' => 'Jacques'],
            ['nom' => 'HERBST', 'prenom' => 'David'],
            ['nom' => 'DELABY', 'prenom' => 'Fabienne'],
            ['nom' => 'BEGUET', 'prenom' => 'Pierre'],
            ['nom' => 'CHOUCHANA', 'prenom' => 'Hervé'],
            ['nom' => 'DESMARETZ', 'prenom' => 'Paul'],
            ['nom' => 'IDZIK', 'prenom' => 'Bernard'],
            ['nom' => 'IDZIK', 'prenom' => 'Bertrand'],
            ['nom' => 'TOUTAIN', 'prenom' => 'Nicolas'],
            ['nom' => 'ISAERT', 'prenom' => 'Pierre'],
            ['nom' => 'FARDOUX', 'prenom' => 'Gilles'],
            ['nom' => 'BRUGES', 'prenom' => 'Walter'],
            ['nom' => 'BRUGES', 'prenom' => 'Jean-Marie'],
            ['nom' => 'MARCELIN', 'prenom' => 'Laurent'],
            ['nom' => 'VERBRUGGHE', 'prenom' => 'Max'],
            ['nom' => 'DEBOFLE', 'prenom' => 'Paul'],
            ['nom' => 'D\'HULSTER', 'prenom' => 'Christopher'],
            ['nom' => 'DELABY', 'prenom' => 'Pierre'],
            ['nom' => 'BAUDELLE', 'prenom' => 'Philippe'],
            ['nom' => 'MANTEL', 'prenom' => 'Cyril'],
            ['nom' => 'CAYRE', 'prenom' => 'Serge'],
            ['nom' => 'HOLLANDER', 'prenom' => 'Patrick'],
            ['nom' => 'DUCASSE', 'prenom' => 'Michel'],
            ['nom' => 'FASQUELLE', 'prenom' => 'Yves'],
            ['nom' => 'CHEVALIER', 'prenom' => 'José'],
            ['nom' => 'GAMOT', 'prenom' => 'Yves'],
            ['nom' => 'BLONDET', 'prenom' => 'Guy'],
            ['nom' => 'RIFFLART', 'prenom' => 'Jocelyn'],
            ['nom' => 'GARIN', 'prenom' => 'Louis'],
            ['nom' => 'OBERT', 'prenom' => 'Armand'],
            ['nom' => 'CANLER', 'prenom' => 'Marc Antoine'],
            ['nom' => 'VERDRU', 'prenom' => 'JC'],
            ['nom' => 'WALLOIS', 'prenom' => 'Jérôme'],
        ];

        foreach ($personnes as $data) {
            $personne = new Personne();
            $personne->setCivilite('Mr');
            $personne->setNom($data['nom']);
            $personne->setPrenom($data['prenom']);
            $personne->setEmail('');
            
            $this->entityManager->persist($personne);
            $io->text("Personne créée : {$data['prenom']} {$data['nom']}");
        }
    }

    private function createTransactions2022_2023(SymfonyStyle $io): void
    {
        $io->section('Import transactions 2022-2023');
        
        $exercice = $this->entityManager->getRepository(Exercice::class)->findOneBy(['libelle' => 'Exercice 2022-2023']);
        $typeCotisation = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Cotisation']);
        $typeRepas = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Repas amicale']);
        $typeAchats = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Achats']);
        $typeReport = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Report à nouveau']);
        
        $modeChèque = $this->entityManager->getRepository(ModeDePaiement::class)->findOneBy(['libelle' => 'Chèque']);
        $modeLiquide = $this->entityManager->getRepository(ModeDePaiement::class)->findOneBy(['libelle' => 'Espèces']);

        // Quelques transactions principales de 2022-2023
        $transactions = [
            ['date' => '2022-08-31', 'libelle' => 'Report à nouveau exercice 2022-2023', 'montant' => '1931.40', 'type' => $typeReport, 'mode' => null, 'personne_nom' => null],
            ['date' => '2022-12-01', 'libelle' => 'Cotisation 22/23 - Mr GAUTHEROT Didier', 'montant' => '30', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'GAUTHEROT'],
            ['date' => '2022-12-21', 'libelle' => 'Cotisation 22/23 - Mr TOUTAIN Nicolas', 'montant' => '100', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'TOUTAIN'],
            ['date' => '2023-01-05', 'libelle' => 'Cotisation 22/23 - Mr ISAERT Pierre', 'montant' => '30', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'ISAERT'],
            ['date' => '2023-01-07', 'libelle' => 'Cotisation 22/23 - Mr BEGUET Pierre (1)', 'montant' => '60', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'BEGUET'],
            ['date' => '2023-01-07', 'libelle' => 'Cotisation 22/23 - Mr BEGUET Pierre (2)', 'montant' => '150', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'BEGUET'],
            ['date' => '2023-01-18', 'libelle' => 'Cotisation 22/23 - Mr FARDOUX Gilles', 'montant' => '30', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'FARDOUX'],
            ['date' => '2023-03-05', 'libelle' => 'Repas amicale 2023 - Mr DELABY Pierre', 'montant' => '56', 'type' => $typeRepas, 'mode' => $modeChèque, 'personne_nom' => 'DELABY'],
            ['date' => '2023-03-05', 'libelle' => 'Repas amicale 2023 - Mr ISAERT Pierre', 'montant' => '56', 'type' => $typeRepas, 'mode' => $modeChèque, 'personne_nom' => 'ISAERT'],
            ['date' => '2023-03-05', 'libelle' => 'Achat repas - Chez Marco', 'montant' => '-646', 'type' => $typeAchats, 'mode' => $modeChèque, 'personne_nom' => null],
        ];

        $numeroOrdre = 1;
        foreach ($transactions as $data) {
            $transaction = new Transaction();
            $transaction->setExercice($exercice);
            $transaction->setDateTransaction(new \DateTime($data['date']));
            $transaction->setLibelle($data['libelle']);
            $transaction->setMontant($data['montant']);
            $transaction->setTypeTransaction($data['type']);
            $transaction->setNumeroOrdre($numeroOrdre++);
            
            if ($data['mode']) {
                $transaction->setModeDePaiement($data['mode']);
            }
            
            if ($data['personne_nom']) {
                $personne = $this->entityManager->getRepository(Personne::class)->findOneBy(['nom' => $data['personne_nom']]);
                if ($personne) {
                    $transaction->setPersonne($personne);
                }
            }

            $this->entityManager->persist($transaction);
            $io->text("Transaction 2022-2023 : {$data['libelle']} - {$data['montant']}€");
        }
    }

    private function createTransactions2023_2024(SymfonyStyle $io): void
    {
        $io->section('Import transactions 2023-2024');
        
        $exercice = $this->entityManager->getRepository(Exercice::class)->findOneBy(['libelle' => 'Exercice 2023-2024']);
        $typeCotisation = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Cotisation']);
        $typeAchats = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Achats']);
        $typeFrais = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Frais bancaires']);
        $typeRistourne = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Ristourne']);
        $typeReport = $this->entityManager->getRepository(TypeTransaction::class)->findOneBy(['libelle' => 'Report à nouveau']);
        
        $modeChèque = $this->entityManager->getRepository(ModeDePaiement::class)->findOneBy(['libelle' => 'Chèque']);
        $modeLiquide = $this->entityManager->getRepository(ModeDePaiement::class)->findOneBy(['libelle' => 'Espèces']);
        $modeVirement = $this->entityManager->getRepository(ModeDePaiement::class)->findOneBy(['libelle' => 'Virement']);

        // Transactions principales de 2023-2024
        $transactions = [
            ['date' => '2023-10-01', 'libelle' => 'Report à nouveau exercice 2023-2024', 'montant' => '3225.89', 'type' => $typeReport, 'mode' => null, 'personne_nom' => null],
            ['date' => '2023-10-01', 'libelle' => 'Achats assemblée générale - Relai du Marais', 'montant' => '-37', 'type' => $typeAchats, 'mode' => null, 'personne_nom' => null],
            ['date' => '2023-12-06', 'libelle' => 'Cotisation 23-24 - Mr GAUTHEROT Didier', 'montant' => '30', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'GAUTHEROT'],
            ['date' => '2023-12-20', 'libelle' => 'Cotisation 23-24 - Mr CLAY Jacques', 'montant' => '30', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'CLAY'],
            ['date' => '2023-12-28', 'libelle' => 'Cotisation 23-24 - Mr HERBST David', 'montant' => '100', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'HERBST'],
            ['date' => '2023-12-21', 'libelle' => 'Cotisation 23-24 - Mr DESMARETZ Paul', 'montant' => '30', 'type' => $typeCotisation, 'mode' => $modeLiquide, 'personne_nom' => 'DESMARETZ'],
            ['date' => '2024-01-03', 'libelle' => 'Cotisation 23-24 - Mr BEGUET Pierre', 'montant' => '150', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'BEGUET'],
            ['date' => '2024-01-03', 'libelle' => 'Cotisation 23-24 - Mr CHOUCHANA Hervé', 'montant' => '40', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'CHOUCHANA'],
            ['date' => '2024-01-08', 'libelle' => 'Cotisation 23-24 - Mr GARIN Louis', 'montant' => '100', 'type' => $typeCotisation, 'mode' => $modeVirement, 'personne_nom' => 'GARIN'],
            ['date' => '2024-01-08', 'libelle' => 'Cotisation 23-24 - Mr CANLER Marc Antoine', 'montant' => '100', 'type' => $typeCotisation, 'mode' => $modeVirement, 'personne_nom' => 'CANLER'],
            ['date' => '2024-01-31', 'libelle' => 'Cotisation 23-24 - Mr VERBRUGGHE Max', 'montant' => '80', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'VERBRUGGHE'],
            ['date' => '2024-02-15', 'libelle' => 'Cotisation 23-24 - Mr OBERT Armand', 'montant' => '80', 'type' => $typeCotisation, 'mode' => $modeChèque, 'personne_nom' => 'OBERT'],
            ['date' => '2024-05-02', 'libelle' => 'Cotisation 23-24 - Mr BRUGES Jean-Marie', 'montant' => '100', 'type' => $typeCotisation, 'mode' => $modeVirement, 'personne_nom' => 'BRUGES'],
            // Frais bancaires
            ['date' => '2023-11-10', 'libelle' => 'Tenue de compte CA', 'montant' => '-2', 'type' => $typeFrais, 'mode' => null, 'personne_nom' => null],
            ['date' => '2023-11-24', 'libelle' => 'Ristourne tenue compte SG', 'montant' => '75', 'type' => $typeRistourne, 'mode' => null, 'personne_nom' => null],
        ];

        $numeroOrdre = 1;
        foreach ($transactions as $data) {
            $transaction = new Transaction();
            $transaction->setExercice($exercice);
            $transaction->setDateTransaction(new \DateTime($data['date']));
            $transaction->setLibelle($data['libelle']);
            $transaction->setMontant($data['montant']);
            $transaction->setTypeTransaction($data['type']);
            $transaction->setNumeroOrdre($numeroOrdre++);
            
            if ($data['mode']) {
                $transaction->setModeDePaiement($data['mode']);
            }
            
            if ($data['personne_nom']) {
                $personne = $this->entityManager->getRepository(Personne::class)->findOneBy(['nom' => $data['personne_nom']]);
                if ($personne) {
                    $transaction->setPersonne($personne);
                }
            }

            $this->entityManager->persist($transaction);
            $io->text("Transaction 2023-2024 : {$data['libelle']} - {$data['montant']}€");
        }
    }
}