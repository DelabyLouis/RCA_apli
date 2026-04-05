<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

/**
 * Changement du type des colonnes code_postal de INT à VARCHAR
 * pour préserver les zéros de tête (ex: 06526 au lieu de 6526)
 */
final class Version20260405000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change code_postal columns from INT to VARCHAR to preserve leading zeros';
    }

    public function up(Schema $schema): void
    {
        // Migration pour personne.code_postal
        $this->addSql('ALTER TABLE personne MODIFY code_postal VARCHAR(10) NULL');
        
        // Migration pour entreprise.code_postal
        $this->addSql('ALTER TABLE entreprise MODIFY code_postal VARCHAR(10) NULL');
        
        // Migration pour user.code_postal (si elle existe)
        $this->addSql('ALTER TABLE user MODIFY code_postal VARCHAR(10) NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert migrations
        $this->addSql('ALTER TABLE personne MODIFY code_postal INT NULL');
        $this->addSql('ALTER TABLE entreprise MODIFY code_postal INT NULL');
        $this->addSql('ALTER TABLE user MODIFY code_postal INT NULL');
    }
}
