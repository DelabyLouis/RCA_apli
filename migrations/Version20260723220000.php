<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * DIRECT PDO: Drop constraint using raw connection.executeStatement()
 */
final class Version20260723220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[DIRECT PDO] Drop unique_numero_ordre_exercice via executeStatement()';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if (!($platform instanceof PostgreSQLPlatform)) {
            return;
        }

        error_log("[MIGRATION 220000] 🚀 START - Direct PDO approach");

        // Get exact constraint name from information_schema
        try {
            $result = $this->connection->executeQuery(
                "SELECT constraint_name FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' 
                 AND constraint_type = 'UNIQUE' 
                 AND constraint_schema = 'public'"
            )->fetchAllAssociative();

            error_log("[MIGRATION 220000] Found constraints: " . json_encode($result));

            foreach ($result as $row) {
                $name = $row['constraint_name'];
                if (stripos($name, 'numero') !== false) {
                    error_log("[MIGRATION 220000] Dropping: $name");
                    try {
                        $this->connection->executeStatement(
                            'ALTER TABLE "transaction" DROP CONSTRAINT "' . str_replace('"', '""', $name) . '"'
                        );
                        error_log("[MIGRATION 220000] ✅ Successfully dropped: $name");
                    } catch (\Exception $e) {
                        error_log("[MIGRATION 220000] ❌ Failed to drop $name: " . $e->getMessage());
                    }
                }
            }

            // Verify
            $remaining = $this->connection->executeQuery(
                "SELECT COUNT(*) as cnt FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' 
                 AND constraint_type = 'UNIQUE'
                 AND constraint_schema = 'public'
                 AND constraint_name LIKE '%numero%'"
            )->fetchAssociative();

            error_log("[MIGRATION 220000] Verification: " . json_encode($remaining));
            
            if ((int)$remaining['cnt'] === 0) {
                error_log("[MIGRATION 220000] ✅ SUCCESS - All numero constraints gone!");
            } else {
                error_log("[MIGRATION 220000] ⚠️  FAILED - Constraints still exist");
            }

        } catch (\Exception $e) {
            error_log("[MIGRATION 220000] ❌ Exception: " . $e->getMessage());
        }

        error_log("[MIGRATION 220000] 🏁 DONE");
    }

    public function down(Schema $schema): void {}
}
