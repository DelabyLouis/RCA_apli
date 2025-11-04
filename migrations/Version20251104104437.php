<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104104437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consentement_rgpd (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type_consentement VARCHAR(50) NOT NULL, accepte TINYINT(1) NOT NULL, date_consentement DATETIME NOT NULL, contexte LONGTEXT DEFAULT NULL, adresse_ip VARCHAR(45) DEFAULT NULL, date_retrait DATETIME DEFAULT NULL, INDEX IDX_E1E265C0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE consentement_rgpd ADD CONSTRAINT FK_E1E265C0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id_user)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consentement_rgpd DROP FOREIGN KEY FK_E1E265C0A76ED395');
        $this->addSql('DROP TABLE consentement_rgpd');
    }
}
