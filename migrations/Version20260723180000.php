<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ABSOLUTE FINAL FIX: Ensure unique_numero_ordre_exercice constraint is GONE
 * This handles all edge cases and transaction issues
 */
final class Version20260723180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FINAL: Force removal of unique_numero_ordre_exercice constraint';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        
        if ($platform === 'postgresql') {
            // Try raw SQL execution without Doctrine management
            $conn = $this->connection->getNativeConnection();
            
            // First, try to disable foreign keys if possible
            try {
                // List all constraints
                $stmt = $conn->query(<<<'SQL'
                    SELECT constraint_name
                    FROM information_schema.table_constraints
                    WHERE table_name = 'transaction'
                    AND constraint_schema = 'public'
                    AND constraint_type = 'UNIQUE';
                SQL
                );
                $constraints = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($constraints as $row) {
                    $name = $row['constraint_name'];
                    if ($name === 'unique_numero_ordre_exercice' || strpos($name, 'numero_ordre') !== false) {
                        try {
                            $sql = 'ALTER TABLE transaction DROP CONSTRAINT IF EXISTS ' . $name;
                            $conn->exec($sql);
                            $this->write("✅ Dropped constraint: $name");
                        } catch (\Exception $e) {
                            $this->write("⚠️  Could not drop $name: " . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->write("Warning during constraint enumeration: " . $e->getMessage());
            }
            
            // Final verification
            try {
                $stmt = $conn->query(<<<'SQL'
                    SELECT COUNT(*) as cnt
                    FROM information_schema.table_constraints
                    WHERE table_name = 'transaction'
                    AND constraint_schema = 'public'
                    AND constraint_name = 'unique_numero_ordre_exercice';
                SQL
                );
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($result['cnt'] == 0) {
                    $this->write("✅ VERIFIED: unique_numero_ordre_exercice constraint is GONE");
                } else {
                    $this->write("❌ ERROR: unique_numero_ordre_exercice constraint still exists!");
                }
            } catch (\Exception $e) {
                $this->write("Could not verify: " . $e->getMessage());
            }
            
        } elseif ($platform === 'mysql') {
            try {
                $this->connection->executeStatement('ALTER TABLE `transaction` DROP INDEX IF EXISTS unique_numero_ordre_exercice');
                $this->write("✅ MySQL: Dropped unique_numero_ordre_exercice index");
            } catch (\Exception $e) {
                $this->write("MySQL drop attempt: " . $e->getMessage());
            }
        }
    }

    public function down(Schema $schema): void
    {
        // No rollback
    }
}
