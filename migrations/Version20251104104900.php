<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104104900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_suppression (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, entity_type VARCHAR(100) NOT NULL, entity_id INT NOT NULL, entity_data LONGTEXT NOT NULL, deleted_at DATETIME NOT NULL, deletion_reason VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, deletion_type VARCHAR(20) NOT NULL, scheduled_hard_delete DATETIME DEFAULT NULL, INDEX IDX_86F4A90CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE audit_suppression ADD CONSTRAINT FK_86F4A90CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id_user)');
        $this->addSql('ALTER TABLE personne ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_suppression DROP FOREIGN KEY FK_86F4A90CA76ED395');
        $this->addSql('DROP TABLE audit_suppression');
        $this->addSql('ALTER TABLE personne DROP deleted_at');
    }
}
