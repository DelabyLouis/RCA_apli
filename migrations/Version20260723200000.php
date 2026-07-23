<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[FINAL] Drop unique_numero_ordre_exercice constraint with aggressive verification';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        
        // ONLY run on PostgreSQL (Render uses PostgreSQL)
        if (!($platform instanceof PostgreSQLPlatform)) {
            error_log("[Migration 20260723200000] ⚠️  SKIPPED - Not PostgreSQL platform");
            return;
        }
        
        error_log("[Migration 20260723200000] ✅ STARTING on PostgreSQL");
        
        // Step 1: Check table existence
        $tableExists = $this->checkTableExists();
        if (!$tableExists) {
            error_log("[Migration 20260723200000] ⚠️  Table 'transaction' does not exist - SKIP");
            return;
        }
        error_log("[Migration 20260723200000] ✅ Table 'transaction' exists");
        
        // Step 2: List ALL constraints BEFORE
        $constraintsBefore = $this->listConstraints();
        error_log("[Migration 20260723200000] BEFORE constraints: " . count($constraintsBefore) . " total");
        foreach ($constraintsBefore as $constraint) {
            error_log("[Migration 20260723200000]   - " . $constraint);
        }
        
        // Step 3: Find numero_ordem constraints
        $numeroConstraints = array_filter($constraintsBefore, function($c) {
            return stripos($c, 'numero') !== false;
        });
        error_log("[Migration 20260723200000] Found numero* constraints: " . count($numeroConstraints));
        foreach ($numeroConstraints as $constraint) {
            error_log("[Migration 20260723200000]   FOUND: " . $constraint);
        }
        
        // Step 4: Drop each constraint found
        foreach ($numeroConstraints as $constraint) {
            try {
                error_log("[Migration 20260723200000] Dropping: $constraint");
                $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS ' . $constraint);
                error_log("[Migration 20260723200000] ✅ DROP executed: $constraint");
            } catch (\Exception $e) {
                error_log("[Migration 20260723200000] ❌ DROP failed for $constraint: " . $e->getMessage());
            }
        }
        
        // Step 5: Also try by name explicitly (in case search missed it)
        $namesToTry = [
            'unique_numero_ordre_exercice',
            'unique_numero_ordem_exercice',
            'pk_numero_ordre',
            'uk_numero_ordre_exercice',
        ];
        
        foreach ($namesToTry as $name) {
            try {
                error_log("[Migration 20260723200000] Attempting explicit DROP: $name");
                $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS ' . $name);
                error_log("[Migration 20260723200000] ✅ Executed DROP IF EXISTS for $name");
            } catch (\Exception $e) {
                error_log("[Migration 20260723200000] ⚠️  DROP IF EXISTS for $name returned: " . $e->getMessage());
            }
        }
        
        // Step 6: List ALL constraints AFTER
        $constraintsAfter = $this->listConstraints();
        error_log("[Migration 20260723200000] AFTER constraints: " . count($constraintsAfter) . " total");
        foreach ($constraintsAfter as $constraint) {
            error_log("[Migration 20260723200000]   - " . $constraint);
        }
        
        // Step 7: Verify numero_ordem constraints are gone
        $numeroConstraintsAfter = array_filter($constraintsAfter, function($c) {
            return stripos($c, 'numero') !== false;
        });
        
        if (count($numeroConstraintsAfter) === 0) {
            error_log("[Migration 20260723200000] ✅✅✅ SUCCESS: All numero* constraints removed!");
        } else {
            error_log("[Migration 20260723200000] ❌ FAILED: " . count($numeroConstraintsAfter) . " numero* constraints still exist:");
            foreach ($numeroConstraintsAfter as $constraint) {
                error_log("[Migration 20260723200000]   STILL EXISTS: " . $constraint);
            }
        }
        
        error_log("[Migration 20260723200000] ✅ MIGRATION COMPLETED");
    }

    public function down(Schema $schema): void
    {
        // Not needed - we're removing a constraint
    }
    
    /**
     * Check if transaction table exists
     */
    private function checkTableExists(): bool
    {
        try {
            $result = $this->connection->fetchAssociative(
                "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_name = 'transaction' AND table_schema = 'public'"
            );
            return (int)($result['cnt'] ?? 0) > 0;
        } catch (\Exception $e) {
            error_log("[Migration 20260723200000] ❌ Error checking table existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * List all constraints on transaction table
     */
    private function listConstraints(): array
    {
        try {
            $result = $this->connection->fetchAllAssociative(
                "SELECT constraint_name FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' AND table_schema = 'public'"
            );
            return array_map(function($row) {
                return $row['constraint_name'];
            }, $result ?: []);
        } catch (\Exception $e) {
            error_log("[Migration 20260723200000] ❌ Error listing constraints: " . $e->getMessage());
            return [];
        }
    }
}
