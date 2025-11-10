<?php

namespace App\Command;

use App\Repository\ExerciceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-duplicate-exercices',
    description: 'Clean duplicate exercices from database',
)]
class CleanDuplicateExercicesCommand extends Command
{
    public function __construct(
        private ExerciceRepository $exerciceRepository,
        private EntityManagerInterface $entityManager,
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deleted without actually deleting')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Actually perform the cleanup (required for real deletion)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');
        
        if (!$isDryRun && !$force) {
            $io->error('You must specify either --dry-run to preview changes or --force to actually perform the cleanup.');
            return Command::FAILURE;
        }

        $io->title('Nettoyage des exercices dupliqués');

        // Compter les exercices avant nettoyage
        $totalBefore = $this->connection->executeQuery('SELECT COUNT(*) as count FROM exercice')->fetchAssociative()['count'];
        $io->text("Nombre d'exercices avant nettoyage: {$totalBefore}");

        // Identifier les exercices uniques (garder le plus ancien ID pour chaque combinaison numero_ordre + libelle)
        $sql = "
            SELECT 
                MIN(id_exercice) as keep_id,
                numero_ordre,
                libelle,
                COUNT(*) as duplicate_count
            FROM exercice 
            GROUP BY numero_ordre, libelle 
            HAVING COUNT(*) > 1
            ORDER BY numero_ordre
        ";

        $duplicates = $this->connection->executeQuery($sql)->fetchAllAssociative();

        if (empty($duplicates)) {
            $io->success('Aucun doublon trouvé !');
            return Command::SUCCESS;
        }

        $io->section('Doublons détectés:');
        $totalToDelete = 0;

        foreach ($duplicates as $duplicate) {
            $keepId = $duplicate['keep_id'];
            $numeroOrdre = $duplicate['numero_ordre'];
            $libelle = $duplicate['libelle'];
            $duplicateCount = $duplicate['duplicate_count'];
            $toDeleteCount = $duplicateCount - 1;
            $totalToDelete += $toDeleteCount;

            $io->text("Exercice: '{$libelle}' (N°{$numeroOrdre}) - {$duplicateCount} copies, garder ID {$keepId}, supprimer {$toDeleteCount}");

            if (!$isDryRun) {
                // Supprimer tous les doublons sauf celui avec le plus petit ID
                $deleteSql = "
                    DELETE FROM exercice 
                    WHERE numero_ordre = :numero_ordre 
                    AND libelle = :libelle 
                    AND id_exercice != :keep_id
                ";
                
                $this->connection->executeStatement($deleteSql, [
                    'numero_ordre' => $numeroOrdre,
                    'libelle' => $libelle,
                    'keep_id' => $keepId
                ]);

                $io->text("  ✅ {$toDeleteCount} doublons supprimés");
            }
        }

        if ($isDryRun) {
            $io->warning("Mode DRY-RUN: {$totalToDelete} exercices seraient supprimés");
            $io->note('Utilisez --force pour effectuer réellement le nettoyage');
        } else {
            // Compter après nettoyage
            $totalAfter = $this->connection->executeQuery('SELECT COUNT(*) as count FROM exercice')->fetchAssociative()['count'];
            
            $io->success("Nettoyage terminé !");
            $io->text("Exercices avant: {$totalBefore}");
            $io->text("Exercices après: {$totalAfter}");
            $io->text("Exercices supprimés: " . ($totalBefore - $totalAfter));

            // Clear caches
            $this->entityManager->clear();
            $io->text("Cache Doctrine nettoyé");
        }

        return Command::SUCCESS;
    }
}
