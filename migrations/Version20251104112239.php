<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104112239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_trail (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(100) NOT NULL, entity_type VARCHAR(100) NOT NULL, entity_id INT DEFAULT NULL, details LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, session_id VARCHAR(100) DEFAULT NULL, route_name VARCHAR(255) DEFAULT NULL, severity VARCHAR(20) NOT NULL, INDEX IDX_B523E178A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE audit_trail ADD CONSTRAINT FK_B523E178A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id_user)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_trail DROP FOREIGN KEY FK_B523E178A76ED395');
        $this->addSql('DROP TABLE audit_trail');
    }
}
