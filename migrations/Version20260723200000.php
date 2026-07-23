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
        return '[FINAL] Drop unique_numero_ordre_exercice constraint - COMPOSITE UNIQUE on (numero_ordem, id_exercice)';
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
        error_log("[Migration 20260723200000] Goal: Remove UNIQUE constraint on (numero_ordre, id_exercice)");
        
        // Step 1: Check table existence
        $tableExists = $this->checkTableExists();
        if (!$tableExists) {
            error_log("[Migration 20260723200000] ⚠️  Table 'transaction' does not exist - SKIP");
            return;
        }
        error_log("[Migration 20260723200000] ✅ Table 'transaction' exists");
        
        // Step 2: List ALL constraints BEFORE with TYPE info
        $constraintsBefore = $this->listConstraintsWithType();
        error_log("[Migration 20260723200000] BEFORE constraints: " . count($constraintsBefore) . " total");
        foreach ($constraintsBefore as $constraint) {
            error_log("[Migration 20260723200000]   - {$constraint['name']} ({$constraint['type']})");
        }
        
        // Step 3: Find UNIQUE constraints containing numero
        $numeroUniqueConstraints = array_filter($constraintsBefore, function($c) {
            return $c['type'] === 'UNIQUE' && stripos($c['name'], 'numero') !== false;
        });
        error_log("[Migration 20260723200000] Found UNIQUE numero* constraints: " . count($numeroUniqueConstraints));
        foreach ($numeroUniqueConstraints as $constraint) {
            error_log("[Migration 20260723200000]   UNIQUE CONSTRAINT: " . $constraint['name']);
        }
        
        // Step 4: Drop each UNIQUE constraint found
        foreach ($numeroUniqueConstraints as $constraint) {
            try {
                error_log("[Migration 20260723200000] 🔨 Dropping UNIQUE constraint: {$constraint['name']}");
                $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "' . $constraint['name'] . '"');
                error_log("[Migration 20260723200000] ✅ DROP executed: {$constraint['name']}");
            } catch (\Exception $e) {
                error_log("[Migration 20260723200000] ❌ DROP failed for {$constraint['name']}: " . $e->getMessage());
            }
        }
        
        // Step 5: Also try by name explicitly
        $namesToTry = [
            'unique_numero_ordre_exercice',
            'unique_numero_ordem_exercice',
        ];
        
        foreach ($namesToTry as $name) {
            try {
                error_log("[Migration 20260723200000] 🔨 Attempting explicit DROP IF EXISTS: $name");
                $this->addSql('ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "' . $name . '"');
                error_log("[Migration 20260723200000] ✅ DROP IF EXISTS executed: $name");
            } catch (\Exception $e) {
                error_log("[Migration 20260723200000] ⚠️  DROP IF EXISTS returned: " . $e->getMessage());
            }
        }
        
        // Step 6: List ALL constraints AFTER with TYPE info
        $constraintsAfter = $this->listConstraintsWithType();
        error_log("[Migration 20260723200000] AFTER constraints: " . count($constraintsAfter) . " total");
        foreach ($constraintsAfter as $constraint) {
            error_log("[Migration 20260723200000]   - {$constraint['name']} ({$constraint['type']})");
        }
        
        // Step 7: Verify numero_ordem UNIQUE constraints are gone
        $numeroUniqueAfter = array_filter($constraintsAfter, function($c) {
            return $c['type'] === 'UNIQUE' && stripos($c['name'], 'numero') !== false;
        });
        
        if (count($numeroUniqueAfter) === 0) {
            error_log("[Migration 20260723200000] ✅✅✅ SUCCESS: UNIQUE constraint on numero_ordem successfully removed!");
        } else {
            error_log("[Migration 20260723200000] ❌ FAILED: " . count($numeroUniqueAfter) . " numero* UNIQUE constraints still exist:");
            foreach ($numeroUniqueAfter as $constraint) {
                error_log("[Migration 20260723200000]   STILL EXISTS: {$constraint['name']}");
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
     * List all constraints on transaction table WITH TYPE
     */
    private function listConstraintsWithType(): array
    {
        try {
            $result = $this->connection->fetchAllAssociative(
                "SELECT constraint_name, constraint_type 
                 FROM information_schema.table_constraints 
                 WHERE table_name = 'transaction' AND table_schema = 'public'"
            );
            return array_map(function($row) {
                return [
                    'name' => $row['constraint_name'],
                    'type' => $row['constraint_type']
                ];
            }, $result ?: []);
        } catch (\Exception $e) {
            error_log("[Migration 20260723200000] ❌ Error listing constraints: " . $e->getMessage());
            return [];
        }
    }
}
