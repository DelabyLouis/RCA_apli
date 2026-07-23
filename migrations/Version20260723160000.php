<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop unique constraint on numero_ordre - FINAL attempt with raw SQL
 */
final class Version20260723160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unique_numero_ordre_exercice constraint (final fix)';
    }

    public function up(Schema $schema): void
    {
        // Just execute the DROP - let the database handle the rest
        // PostgreSQL will use this as-is
        // MySQL will ignore it (but needs different syntax)
        
        $platform = $this->connection->getDatabasePlatform()->getName();
        
        if ($platform === 'postgresql') {
            // PostgreSQL: Try multiple variations
            $this->addSql('ALTER TABLE transaction DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice;');
        } elseif ($platform === 'mysql') {
            // MySQL: Drop INDEX instead
            $this->addSql('ALTER TABLE transaction DROP INDEX IF EXISTS unique_numero_ordre_exercice;');
        }
    }

    public function down(Schema $schema): void
    {
        // No rollback
    }
}
