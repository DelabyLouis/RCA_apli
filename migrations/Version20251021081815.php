<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251021081815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE historique_cloture (id_historique INT AUTO_INCREMENT NOT NULL, id_exercice INT NOT NULL, id_user INT DEFAULT NULL, date_action DATETIME NOT NULL, type_action VARCHAR(50) NOT NULL, commentaire LONGTEXT DEFAULT NULL, INDEX IDX_B7C4EA17B4C32BD8 (id_exercice), INDEX IDX_B7C4EA176B3CA4B (id_user), PRIMARY KEY(id_historique)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE historique_cloture ADD CONSTRAINT FK_B7C4EA17B4C32BD8 FOREIGN KEY (id_exercice) REFERENCES exercice (id_exercice)');
        $this->addSql('ALTER TABLE historique_cloture ADD CONSTRAINT FK_B7C4EA176B3CA4B FOREIGN KEY (id_user) REFERENCES `user` (id_user)');
        $this->addSql('ALTER TABLE exercice ADD clos TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historique_cloture DROP FOREIGN KEY FK_B7C4EA17B4C32BD8');
        $this->addSql('ALTER TABLE historique_cloture DROP FOREIGN KEY FK_B7C4EA176B3CA4B');
        $this->addSql('DROP TABLE historique_cloture');
        $this->addSql('ALTER TABLE exercice DROP clos');
    }
}
