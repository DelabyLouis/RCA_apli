<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251023125852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permettre aux transactions d\'avoir un type_transaction null pour gérer les contraintes de types';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction CHANGE id_type id_type INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction CHANGE id_type id_type INT NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE id_personne id_personne INT DEFAULT NULL');
    }
}
