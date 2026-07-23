<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:force-drop-constraint',
    description: 'Force drop unique_numero_ordre_exercice constraint - works even if migrations failed'
)]
class ForceDropConstraintCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($platform !== 'postgresql') {
            $io->warning('This command only works with PostgreSQL');
            return Command::SUCCESS;
        }

        $io->section('🔍 Checking constraint existence...');

        // Get raw PDO connection
        $pdo = $this->connection->getNativeConnection();

        // Check if constraint exists
        $stmt = $pdo->query(
            "SELECT constraint_name FROM information_schema.table_constraints 
             WHERE table_name = 'transaction' AND constraint_schema = 'public' AND constraint_name LIKE '%numero_ordre%'"
        );
        $constraints = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($constraints)) {
            $io->success('✅ No numero_ordem related constraints found - database is clean!');
            return Command::SUCCESS;
        }

        $io->warning('Found ' . count($constraints) . ' constraint(s):');
        foreach ($constraints as $c) {
            $io->text("  - {$c['constraint_name']}");
        }

        $io->section('🔨 Dropping constraints...');

        foreach ($constraints as $constraint) {
            $name = $constraint['constraint_name'];
            try {
                $sql = "ALTER TABLE transaction DROP CONSTRAINT {$name}";
                $pdo->exec($sql);
                $io->success("✅ Dropped: {$name}");
            } catch (\Exception $e) {
                $io->error("❌ Failed to drop {$name}: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // Final verification
        $io->section('📋 Final verification...');
        $stmt = $pdo->query(
            "SELECT COUNT(*) as cnt FROM information_schema.table_constraints 
             WHERE table_name = 'transaction' AND constraint_schema = 'public' AND constraint_name LIKE '%numero_ordem%'"
        );
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result['cnt'] == 0) {
            $io->success('✅ ALL numero_ordem constraints successfully removed!');
            $io->note('You can now edit numero_ordre fields without unique constraint errors');
            return Command::SUCCESS;
        } else {
            $io->error('❌ Some constraints still exist!');
            return Command::FAILURE;
        }
    }
}
