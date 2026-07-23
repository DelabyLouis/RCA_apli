<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove unique constraint on numero_ordre to allow duplicate order numbers
 */
final class Version20260723000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unique constraint on numero_ordre per exercice to allow duplicate order numbers';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            // PostgreSQL: Try to drop the constraint using DO block to ignore if it doesn't exist
            $this->addSql("
                DO $$
                BEGIN
                    ALTER TABLE transaction DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice;
                    RAISE NOTICE 'Constraint dropped or did not exist';
                EXCEPTION WHEN OTHERS THEN
                    RAISE NOTICE 'Error dropping constraint: %', SQLERRM;
                END$$;
            ");
        } elseif ($platform instanceof MySQLPlatform) {
            // MySQL: Check if index exists and drop it
            $this->addSql("
                ALTER TABLE `transaction` DROP INDEX IF EXISTS unique_numero_ordre_exercice
            ");
        }
        // SQLite doesn't need special handling for constraint dropping
    }

    public function down(Schema $schema): void
    {
        // No rollback needed - recreating constraints would break existing data
    }
}
