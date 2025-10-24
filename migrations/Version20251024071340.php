<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024071340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE livret (id_livret INT AUTO_INCREMENT NOT NULL, id_exercice INT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, solde_initial NUMERIC(15, 2) NOT NULL, date_creation DATE NOT NULL, INDEX IDX_C151207B4C32BD8 (id_exercice), PRIMARY KEY(id_livret)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction_livret (id_transaction_livret INT AUTO_INCREMENT NOT NULL, id_livret INT NOT NULL, id_transaction_compte_courant INT NOT NULL, libelle VARCHAR(255) NOT NULL, numero_ordre INT NOT NULL, date_transaction DATE NOT NULL, montant NUMERIC(15, 2) NOT NULL, type VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_7A6860472A22A7EA (id_livret), INDEX IDX_7A68604798119025 (id_transaction_compte_courant), UNIQUE INDEX unique_numero_ordre_livret (numero_ordre, id_livret), PRIMARY KEY(id_transaction_livret)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE livret ADD CONSTRAINT FK_C151207B4C32BD8 FOREIGN KEY (id_exercice) REFERENCES exercice (id_exercice)');
        $this->addSql('ALTER TABLE transaction_livret ADD CONSTRAINT FK_7A6860472A22A7EA FOREIGN KEY (id_livret) REFERENCES livret (id_livret)');
        $this->addSql('ALTER TABLE transaction_livret ADD CONSTRAINT FK_7A68604798119025 FOREIGN KEY (id_transaction_compte_courant) REFERENCES transaction (id_transaction)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE livret DROP FOREIGN KEY FK_C151207B4C32BD8');
        $this->addSql('ALTER TABLE transaction_livret DROP FOREIGN KEY FK_7A6860472A22A7EA');
        $this->addSql('ALTER TABLE transaction_livret DROP FOREIGN KEY FK_7A68604798119025');
        $this->addSql('DROP TABLE livret');
        $this->addSql('DROP TABLE transaction_livret');
    }
}
