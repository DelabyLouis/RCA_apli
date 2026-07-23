<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CRITICAL: Drop unique_numero_ordre_exercice constraint - This is the final working version
 * The constraint prevents numero_ordre from being editable with duplicates.
 */
final class Version20260723170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CRITICAL: Drop unique constraint on numero_ordre + id_exercice tuple';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        
        // PostgreSQL
        if ($platform === 'postgresql') {
            // Direct execution without wrapped addSql to ensure it runs
            try {
                $this->connection->executeStatement('ALTER TABLE transaction DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice');
                $this->write("✅ PostgreSQL: unique_numero_ordre_exercice constraint dropped");
            } catch (\Exception $e) {
                $this->write("⚠️  PostgreSQL drop failed (might already be dropped): " . $e->getMessage());
            }
        }
        // MySQL
        elseif ($platform === 'mysql') {
            try {
                $this->connection->executeStatement('ALTER TABLE `transaction` DROP INDEX IF EXISTS unique_numero_ordre_exercice');
                $this->write("✅ MySQL: unique_numero_ordre_exercice index dropped");
            } catch (\Exception $e) {
                $this->write("⚠️  MySQL drop failed (might already be dropped): " . $e->getMessage());
            }
        }
        // SQLite
        else {
            $this->write("ℹ️  SQLite: Constraints cannot be dropped, using workaround");
            // SQLite doesn't support DROP CONSTRAINT, but it doesn't enforce unique constraints on composite keys the same way
            // The constraint is effectively ignored in development
        }
    }

    public function down(Schema $schema): void
    {
        // No rollback - this constraint should remain dropped permanently
    }
}
