<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024073103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE livret DROP FOREIGN KEY FK_C151207B4C32BD8');
        $this->addSql('DROP INDEX IDX_C151207B4C32BD8 ON livret');
        $this->addSql('ALTER TABLE livret DROP id_exercice');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE livret ADD id_exercice INT NOT NULL');
        $this->addSql('ALTER TABLE livret ADD CONSTRAINT FK_C151207B4C32BD8 FOREIGN KEY (id_exercice) REFERENCES exercice (id_exercice) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C151207B4C32BD8 ON livret (id_exercice)');
    }
}
