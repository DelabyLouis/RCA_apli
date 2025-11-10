<?php

namespace App\Command;

use App\Repository\ExerciceRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'test:exercice',
    description: 'Test exercice repository to debug duplication issue',
)]
class TestExerciceCommand extends Command
{
    public function __construct(
        private ExerciceRepository $exerciceRepository,
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Debug Exercice Repository');

        // Test direct SQL query
        $io->section('Direct SQL Query');
        $result = $this->connection->executeQuery('SELECT COUNT(*) as total FROM exercice');
        $count = $result->fetchAssociative();
        $io->text("Total exercises (direct SQL): " . $count['total']);

        $result = $this->connection->executeQuery('SELECT id_exercice, numero_ordre, libelle FROM exercice ORDER BY numero_ordre');
        $exercises = $result->fetchAllAssociative();
        $io->text("Exercises from direct SQL:");
        foreach ($exercises as $exercise) {
            $io->text("  ID: {$exercise['id_exercice']}, Ordre: {$exercise['numero_ordre']}, Libelle: {$exercise['libelle']}");
        }

        // Test repository query
        $io->section('Repository Query');
        $exercicesFromRepo = $this->exerciceRepository->findAllOrderedByNumeroOrdre();
        $io->text("Total exercises (Repository): " . count($exercicesFromRepo));

        foreach ($exercicesFromRepo as $index => $exercice) {
            $io->text("  Exercise #{$index}: ID={$exercice->getIdExercice()}, NumeroOrdre={$exercice->getNumeroOrdre()}, Libelle={$exercice->getLibelle()}");
        }

        // Test findAll method
        $io->section('FindAll Method');
        $allExercices = $this->exerciceRepository->findAll();
        $io->text("Total exercises (findAll): " . count($allExercices));

        foreach ($allExercices as $index => $exercice) {
            $io->text("  Exercise #{$index}: ID={$exercice->getIdExercice()}, NumeroOrdre={$exercice->getNumeroOrdre()}, Libelle={$exercice->getLibelle()}");
        }

        return Command::SUCCESS;
    }
}
