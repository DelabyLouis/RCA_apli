<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-transaction-order',
    description: 'Renumérote toutes les transactions par exercice (1 à N) et corrige les numéros négatifs'
)]
class FixTransactionOrderCommand extends Command
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private ExerciceRepository $exerciceRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔧 Correction des numéros d\'ordre des transactions');

        // Récupérer tous les exercices
        $exercices = $this->exerciceRepository->findAll();
        
        if (empty($exercices)) {
            $io->warning('Aucun exercice trouvé dans la base de données');
            return Command::SUCCESS;
        }

        $totalFixed = 0;

        foreach ($exercices as $exercice) {
            $io->section('Exercice: ' . $exercice->getLibelle());

            // Récupérer toutes les transactions de cet exercice triées par numéro d'ordre actuel
            $transactions = $this->transactionRepository->findBy(
                ['exercice' => $exercice],
                ['numero_ordre' => 'ASC']
            );

            if (empty($transactions)) {
                $io->info('  Aucune transaction pour cet exercice');
                continue;
            }

            $io->info("  Transactions trouvées: " . count($transactions));

            // Renumméroter de 1 à N
            $newOrder = 1;
            foreach ($transactions as $transaction) {
                $oldOrder = $transaction->getNumeroOrdre();
                
                // Vérifier si le numéro a changé
                if ($oldOrder !== $newOrder) {
                    $transaction->setNumeroOrdre($newOrder);
                    
                    // Log si le numéro était négatif ou invalide
                    if ($oldOrder < 1) {
                        $io->writeln("  ⚠️  [ID: {$transaction->getIdTransaction()}] {$oldOrder} → {$newOrder} (NUMÉRO INVALIDE CORRIGÉ)");
                    } else {
                        $io->writeln("  ✓ [ID: {$transaction->getIdTransaction()}] {$oldOrder} → {$newOrder}");
                    }
                    
                    $totalFixed++;
                }
                
                $newOrder++;
            }

            // Flush des changements de cet exercice
            $this->entityManager->flush();
        }

        $io->success("✅ Correction terminée ! {$totalFixed} transactions renummérotées");

        return Command::SUCCESS;
    }
}