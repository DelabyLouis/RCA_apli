<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * NUCLEAR OPTION: Drop unique_numero_ordem_exercice constraint with ALL possible SQL variations
 */
final class Version20260723210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[NUCLEAR] Drop constraint: Try ALL SQL syntax variations';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if (!($platform instanceof PostgreSQLPlatform)) {
            error_log("[Migration 20260723210000] SKIP - Not PostgreSQL");
            return;
        }

        error_log("[Migration 20260723210000] 🚀 NUCLEAR OPTION STARTING");

        // Table must exist
        if (!$this->checkTableExists()) {
            error_log("[Migration 20260723210000] ⚠️  Table does not exist");
            return;
        }

        // Get constraint name EXACTLY as PostgreSQL has it
        $realConstraintName = $this->findExactConstraintName();
        
        if ($realConstraintName) {
            error_log("[Migration 20260723210000] Found constraint: $realConstraintName");
            $this->dropConstraint($realConstraintName);
        } else {
            error_log("[Migration 20260723210000] No numero* constraint found - trying generic names anyway");
        }

        // Try ALL possible variations
        $variations = [
            'unique_numero_ordre_exercice',
            'unique_numero_ordem_exercice',
            'UNIQUE_numero_ordre_exercice',
            'UNIQUE_numero_ordem_exercice',
            '"unique_numero_ordre_exercice"',
            '"unique_numero_ordem_exercice"',
            'public.unique_numero_ordem_exercice',
            'public.unique_numero_ordre_exercice',
        ];

        foreach ($variations as $name) {
            $this->tryDropConstraint($name);
        }

        // Final verification
        $remaining = $this->findExactConstraintName();
        if ($remaining) {
            error_log("[Migration 20260723210000] ❌ FAILED - Constraint still exists: $remaining");
        } else {
            error_log("[Migration 20260723210000] ✅ SUCCESS - All constraints removed!");
        }
    }

    public function down(Schema $schema): void {}

    private function checkTableExists(): bool
    {
        try {
            $result = $this->connection->fetchAssociative(
                "SELECT 1 FROM information_schema.tables WHERE table_name = 'transaction' AND table_schema = 'public'"
            );
            return $result !== false;
        } catch (\Exception $e) {
            error_log("[Migration 20260723210000] Error checking table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find the EXACT constraint name as PostgreSQL has it
     */
    private function findExactConstraintName(): ?string
    {
        try {
            $constraints = $this->connection->fetchAllAssociative(
                "SELECT constraint_name FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' 
                 AND table_schema = 'public'
                 AND constraint_type = 'UNIQUE'"
            );

            foreach ($constraints as $row) {
                $name = $row['constraint_name'];
                if (stripos($name, 'numero') !== false) {
                    error_log("[Migration 20260723210000] Found exact constraint: $name");
                    return $name;
                }
            }
            return null;
        } catch (\Exception $e) {
            error_log("[Migration 20260723210000] Error finding constraint: " . $e->getMessage());
            return null;
        }
    }

    private function dropConstraint(string $name): void
    {
        try {
            // Try with proper quoting for PostgreSQL identifiers
            $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "' . str_replace('"', '""', $name) . '"');
            error_log("[Migration 20260723210000] ✅ Dropped: $name");
        } catch (\Exception $e) {
            error_log("[Migration 20260723210000] ⚠️  Drop failed for $name: " . $e->getMessage());
        }
    }

    private function tryDropConstraint(string $name): void
    {
        try {
            // Variations without execution error (using IF EXISTS)
            if (strpos($name, '"') === false) {
                // Unquoted version
                $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS ' . $name);
            } else {
                // Already quoted - use as is
                $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS ' . $name);
            }
            error_log("[Migration 20260723210000] Tried: $name");
        } catch (\Exception $e) {
            // Silent - IF EXISTS means this won't fail
        }
    }
}
