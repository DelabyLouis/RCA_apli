<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Changement du type des colonnes code_postal et telephone de INT à VARCHAR
 */
final class Version20260405000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change code_postal and telephone columns from INT to VARCHAR';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MySQLPlatform) {
            // Migration MySQL
            $this->addSql('ALTER TABLE personne MODIFY code_postal VARCHAR(10) NULL');
            $this->addSql('ALTER TABLE personne MODIFY telephone VARCHAR(20) NULL');
            $this->addSql('ALTER TABLE entreprise MODIFY code_postal VARCHAR(10) NULL');
            $this->addSql('ALTER TABLE entreprise MODIFY telephone VARCHAR(20) NULL');
            $this->addSql('ALTER TABLE user MODIFY code_postal VARCHAR(10) NULL');
            $this->addSql('ALTER TABLE user MODIFY telephone VARCHAR(20) NULL');
        }
        // SQLite accepte les types dynamiquement, pas de modification nécessaire
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MySQLPlatform) {
            // Revert migrations MySQL
            $this->addSql('ALTER TABLE personne MODIFY code_postal INT NULL');
            $this->addSql('ALTER TABLE personne MODIFY telephone INT NULL');
            $this->addSql('ALTER TABLE entreprise MODIFY code_postal INT NULL');
            $this->addSql('ALTER TABLE entreprise MODIFY telephone INT NULL');
            $this->addSql('ALTER TABLE user MODIFY code_postal INT NULL');
            $this->addSql('ALTER TABLE user MODIFY telephone INT NULL');
        }
    }
}
