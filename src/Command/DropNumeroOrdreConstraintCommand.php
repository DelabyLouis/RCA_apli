<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:drop-numero-ordre-constraint',
    description: 'Drop the unique constraint on numero_ordre to allow duplicates'
)]
class DropNumeroOrdreConstraintCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $platform = $this->connection->getDatabasePlatform()->getName();
            
            if ($platform === 'postgresql') {
                $io->writeln("🔍 PostgreSQL detected...");
                
                // First, find what constraints exist
                $query = <<<'SQL'
                    SELECT constraint_name, constraint_type
                    FROM information_schema.table_constraints
                    WHERE table_name = 'transaction'
                    AND constraint_schema = 'public'
                    ORDER BY constraint_name;
                SQL;
                
                $constraints = $this->connection->fetchAllAssociative($query);
                $io->writeln("Found " . count($constraints) . " constraints:");
                foreach ($constraints as $constraint) {
                    $io->writeln("  - {$constraint['constraint_name']} ({$constraint['constraint_type']})");
                }
                
                // Try to drop the specific constraint
                $io->writeln("\n🔨 Attempting to drop 'unique_numero_ordre_exercice'...");
                try {
                    $this->connection->exec('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "unique_numero_ordre_exercice"');
                    $io->success("✅ Constraint dropped successfully!");
                } catch (\Exception $e) {
                    $io->error("Failed: " . $e->getMessage());
                    
                    // Try without quotes
                    $io->writeln("⚠️  Trying without quotes...");
                    try {
                        $this->connection->exec('ALTER TABLE transaction DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice');
                        $io->success("✅ Constraint dropped successfully (without quotes)!");
                    } catch (\Exception $e2) {
                        $io->error("Also failed: " . $e2->getMessage());
                        return Command::FAILURE;
                    }
                }
                
            } elseif ($platform === 'mysql') {
                $io->writeln("🔍 MySQL detected...");
                
                // Show existing indexes
                $query = "SHOW INDEXES FROM `transaction` WHERE Key_name LIKE '%numero_ordre%'";
                $indexes = $this->connection->fetchAllAssociative($query);
                
                if (empty($indexes)) {
                    $io->info("No indexes found matching 'numero_ordem'");
                } else {
                    $io->writeln("Found indexes:");
                    foreach ($indexes as $idx) {
                        $io->writeln("  - {$idx['Key_name']}");
                    }
                    
                    try {
                        $this->connection->exec('ALTER TABLE `transaction` DROP INDEX IF EXISTS unique_numero_ordre_exercice');
                        $io->success("✅ Index dropped successfully!");
                    } catch (\Exception $e) {
                        $io->error("Failed: " . $e->getMessage());
                        return Command::FAILURE;
                    }
                }
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error("Command failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
