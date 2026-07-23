<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove unique constraint on numero_ordre to allow duplicate order numbers (FINAL FIX)
 */
final class Version20260723140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unique constraint on numero_ordre per exercice to allow duplicate order numbers';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            // PostgreSQL: Drop the constraint if it exists
            try {
                $this->addSql('ALTER TABLE transaction DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice');
                $this->write("✅ PostgreSQL: Dropped unique constraint on numero_ordre");
            } catch (\Exception $e) {
                $this->write("ℹ️  PostgreSQL constraint drop skipped: " . $e->getMessage());
            }
        } elseif ($platform instanceof MySQLPlatform) {
            // MySQL: Drop the index if it exists
            try {
                $this->addSql('ALTER TABLE `transaction` DROP INDEX IF EXISTS unique_numero_ordre_exercice');
                $this->write("✅ MySQL: Dropped unique index on numero_ordre");
            } catch (\Exception $e) {
                $this->write("ℹ️  MySQL index drop skipped: " . $e->getMessage());
            }
        }
    }

    public function down(Schema $schema): void
    {
        // No rollback - the constraint was blocking changes
    }
}
