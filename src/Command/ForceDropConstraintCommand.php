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
        
        error_log("═════════════════════════════════════════════════════════");
        error_log("[ForceDropConstraintCommand] 🚀 COMMAND STARTED");
        error_log("═════════════════════════════════════════════════════════");
        
        $platform = $this->connection->getDatabasePlatform()->getName();
        error_log("[ForceDropConstraintCommand] Platform: {$platform}");

        if ($platform !== 'postgresql') {
            $io->warning('This command only works with PostgreSQL');
            error_log("[ForceDropConstraintCommand] Not PostgreSQL, exiting");
            return Command::SUCCESS;
        }

        $io->section('🔍 Checking constraint existence...');
        error_log("[ForceDropConstraintCommand] Getting PDO connection");

        try {
            $pdo = $this->connection->getNativeConnection();
            error_log("[ForceDropConstraintCommand] ✅ PDO connection obtained");
        } catch (\Exception $e) {
            error_log("[ForceDropConstraintCommand] ❌ Failed to get PDO: " . $e->getMessage());
            return Command::FAILURE;
        }

        try {
            $query = "SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = 'transaction' AND constraint_schema = 'public' AND constraint_name LIKE '%numero_ordre%'";
            error_log("[ForceDropConstraintCommand] Query: {$query}");
            $stmt = $pdo->query($query);
            $constraints = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            error_log("[ForceDropConstraintCommand] Found " . count($constraints) . " constraints");
        } catch (\Exception $e) {
            error_log("[ForceDropConstraintCommand] ❌ Query failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        if (empty($constraints)) {
            $io->success('✅ No numero_ordem related constraints found - database is clean!');
            error_log("[ForceDropConstraintCommand] ✅ No constraints found!");
            return Command::SUCCESS;
        }

        $io->warning('Found ' . count($constraints) . ' constraint(s):');
        foreach ($constraints as $c) {
            $io->text("  - {$c['constraint_name']}");
            error_log("[ForceDropConstraintCommand] Constraint: {$c['constraint_name']}");
        }

        $io->section('🔨 Dropping constraints...');

        foreach ($constraints as $constraint) {
            $name = $constraint['constraint_name'];
            try {
                $sql = "ALTER TABLE transaction DROP CONSTRAINT {$name}";
                error_log("[ForceDropConstraintCommand] Executing: {$sql}");
                $pdo->exec($sql);
                $io->success("✅ Dropped: {$name}");
                error_log("[ForceDropConstraintCommand] ✅ Dropped: {$name}");
            } catch (\Exception $e) {
                $io->error("❌ Failed to drop {$name}: " . $e->getMessage());
                error_log("[ForceDropConstraintCommand] ❌ Failed to drop: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->section('📋 Final verification...');
        error_log("[ForceDropConstraintCommand] Running final verification");
        try {
            $query = "SELECT COUNT(*) as cnt FROM information_schema.table_constraints WHERE table_name = 'transaction' AND constraint_schema = 'public' AND constraint_name LIKE '%numero_ordre%'";
            error_log("[ForceDropConstraintCommand] Verification query: {$query}");
            $stmt = $pdo->query($query);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $count = $result['cnt'] ?? 0;
            error_log("[ForceDropConstraintCommand] Final count: {$count}");

            if ($count == 0) {
                $io->success('✅ ALL numero_ordre constraints successfully removed!');
                $io->note('You can now edit numero_ordre fields without unique constraint errors');
                error_log("[ForceDropConstraintCommand] 🎉 SUCCESS - All constraints removed!");
                error_log("═════════════════════════════════════════════════════════");
                return Command::SUCCESS;
            } else {
                $io->error("❌ Some constraints still exist: {$count}");
                error_log("[ForceDropConstraintCommand] ❌ FAILED - {$count} constraints still exist");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            error_log("[ForceDropConstraintCommand] ❌ Verification query failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
