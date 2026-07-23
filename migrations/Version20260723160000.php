<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop unique constraint on numero_ordre - FINAL attempt with direct ALTER
 */
final class Version20260723160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unique_numero_ordre_exercice constraint (final fix)';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            // PostgreSQL: Drop constraint by exact name (the most reliable method)
            // This constraint exists as: unique_numero_ordre_exercice on (numero_ordre, id_exercice)
            $this->addSql(<<<'SQL'
                ALTER TABLE "transaction" DROP CONSTRAINT IF EXISTS "unique_numero_ordre_exercice";
            SQL
            );
            $this->write("✅ PostgreSQL: Dropped unique_numero_ordre_exercice constraint");
            
        } elseif ($platform instanceof MySQLPlatform) {
            // MySQL: Drop index if it exists
            $this->addSql('ALTER TABLE `transaction` DROP INDEX IF EXISTS unique_numero_ordre_exercice');
            $this->write("✅ MySQL: Dropped unique index");
        }
    }

    public function down(Schema $schema): void
    {
        // No rollback - constraint should stay dropped
    }
}
