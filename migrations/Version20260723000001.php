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

        if ($platform instanceof MySQLPlatform) {
            // MySQL: Drop the unique constraint
            $this->addSql('ALTER TABLE `transaction` DROP INDEX unique_numero_ordre_exercice');
        } elseif ($platform instanceof PostgreSQLPlatform) {
            // PostgreSQL: Drop the unique constraint
            $this->addSql('ALTER TABLE transaction DROP CONSTRAINT unique_numero_ordre_exercice');
        } elseif ($platform instanceof SqlitePlatform) {
            // SQLite: Recreate the table without the unique constraint
            $this->addSql('PRAGMA foreign_keys = OFF');
            
            try {
                $this->addSql('ALTER TABLE transaction RENAME TO transaction_backup');
                
                $this->addSql('CREATE TABLE transaction (
                    id_transaction INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    id_exercice INTEGER NOT NULL,
                    id_type INTEGER,
                    id_personne INTEGER,
                    id_entreprise INTEGER,
                    id INTEGER,
                    libelle VARCHAR(255) NOT NULL UNIQUE,
                    numero_ordre INTEGER NOT NULL,
                    date_transaction DATE NOT NULL,
                    montant NUMERIC(15, 2) NOT NULL,
                    type_compte VARCHAR(50) DEFAULT "compte_courant" NOT NULL,
                    transaction_liee_id INTEGER
                )');
                
                $this->addSql('INSERT INTO transaction 
                    SELECT * FROM transaction_backup');
                
                $this->addSql('DROP TABLE transaction_backup');
            } finally {
                $this->addSql('PRAGMA foreign_keys = ON');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MySQLPlatform) {
            // MySQL: Recreate the unique constraint
            $this->addSql('ALTER TABLE `transaction` ADD UNIQUE KEY unique_numero_ordre_exercice (numero_ordre, id_exercice)');
        } elseif ($platform instanceof PostgreSQLPlatform) {
            // PostgreSQL: Recreate the unique constraint
            $this->addSql('ALTER TABLE transaction ADD CONSTRAINT unique_numero_ordre_exercice UNIQUE (numero_ordre, id_exercice)');
        }
        // SQLite rollback would require recreating the table with the constraint
    }
}
