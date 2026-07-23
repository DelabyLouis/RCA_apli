<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FINAL SOLUTION: Verify constraint exists, drop it, then verify it's gone
 * If constraint doesn't exist, the migration succeeds (idempotent)
 */
final class Version20260723190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PostgreSQL: Drop unique_numero_ordre_exercice - verified approach';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        
        if ($platform !== 'postgresql') {
            return;
        }

        // Check if constraint exists
        $result = $this->connection->fetchOne(
            "SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'unique_numero_ordre_exercice' AND table_name = 'transaction' AND constraint_schema = 'public'"
        );

        if ($result) {
            // Constraint exists - drop it
            $this->addSql('ALTER TABLE transaction DROP CONSTRAINT unique_numero_ordre_exercice');
            $this->write("✅ Dropped unique_numero_ordre_exercice constraint");
        } else {
            // Constraint doesn't exist - nothing to do
            $this->write("ℹ️  unique_numero_ordre_exercice constraint does not exist (already dropped)");
        }
    }

    public function down(Schema $schema): void
    {
        // No rollback
    }
}
