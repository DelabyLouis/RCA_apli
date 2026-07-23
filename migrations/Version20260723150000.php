<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop unique constraint on numero_ordre - Enhanced version to handle all databases
 */
final class Version20260723150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unique constraint on numero_ordre to allow duplicates (enhanced)';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            // PostgreSQL: First try to drop by exact name
            $this->addSql('ALTER TABLE transaction DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice');
            
            // If the above didn't work, try dropping by column pattern
            $this->addSql(<<<'SQL'
                DO $$
                DECLARE
                    constraint_name TEXT;
                BEGIN
                    -- Look for unique constraint on numero_ordre
                    SELECT constraint_name INTO constraint_name
                    FROM information_schema.table_constraints
                    WHERE table_name = 'transaction'
                      AND constraint_type = 'UNIQUE'
                      AND constraint_schema = 'public'
                      AND constraint_name LIKE '%numero_ordre%'
                    LIMIT 1;
                    
                    IF constraint_name IS NOT NULL THEN
                        EXECUTE 'ALTER TABLE transaction DROP CONSTRAINT ' || constraint_name;
                        RAISE NOTICE 'Dropped numero_ordre constraint: %', constraint_name;
                    END IF;
                END $$;
            SQL
            );
            $this->write("✅ PostgreSQL: Dropped unique constraint on numero_ordre");
            
        } elseif ($platform instanceof MySQLPlatform) {
            // MySQL: Drop index if it exists
            $this->addSql(<<<'SQL'
                ALTER TABLE `transaction` DROP INDEX IF EXISTS unique_numero_ordre_exercice
            SQL
            );
            $this->write("✅ MySQL: Dropped unique index");
        }
    }

    public function down(Schema $schema): void
    {
        // No rollback - constraint should stay dropped
    }
}
