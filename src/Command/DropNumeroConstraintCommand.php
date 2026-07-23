<?php

namespace App\Command;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Connection;

#[AsCommand(
    name: 'app:drop-numero-constraint',
    description: 'Drop unique_numero_ordre_exercice constraint from transaction table',
    hidden: false,
)]
class DropNumeroConstraintCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $platform = $this->connection->getDatabasePlatform();
        
        if (!($platform instanceof PostgreSQLPlatform)) {
            $output->writeln('<info>Not PostgreSQL - skipping</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<info>🚀 Dropping unique_numero_ordre_exercice constraint...</info>');

        try {
            // Get the exact constraint name
            $result = $this->connection->executeQuery(
                "SELECT constraint_name FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' 
                 AND constraint_type = 'UNIQUE' 
                 AND constraint_schema = 'public'
                 AND constraint_name LIKE '%numero%'"
            )->fetchAllAssociative();

            if (empty($result)) {
                $output->writeln('<comment>⚠️  No numero* UNIQUE constraint found</comment>');
                return Command::SUCCESS;
            }

            foreach ($result as $row) {
                $name = $row['constraint_name'];
                $output->writeln("  Dropping: <fg=yellow>$name</>");
                
                try {
                    $this->connection->executeStatement(
                        'ALTER TABLE "transaction" DROP CONSTRAINT "' . str_replace('"', '""', $name) . '"'
                    );
                    $output->writeln("  <fg=green>✅ Dropped: $name</>");
                } catch (\Exception $e) {
                    $output->writeln("  <fg=red>❌ Failed: " . $e->getMessage() . "</>");
                }
            }

            // Verify
            $verify = $this->connection->executeQuery(
                "SELECT COUNT(*) as cnt FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' 
                 AND constraint_type = 'UNIQUE'
                 AND constraint_schema = 'public'
                 AND constraint_name LIKE '%numero%'"
            )->fetchAssociative();

            if ((int)$verify['cnt'] === 0) {
                $output->writeln('<fg=green>✅ SUCCESS: All numero constraints removed!</>');
                return Command::SUCCESS;
            } else {
                $output->writeln('<fg=red>❌ FAILED: Constraints still exist</>', );
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $output->writeln('<error>❌ Exception: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
