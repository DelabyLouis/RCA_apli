<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022111705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modification de la relation User-Personne en OneToOne';
    }

    public function up(Schema $schema): void
    {
        // Modifier la colonne pour être NOT NULL
        $this->addSql('ALTER TABLE user CHANGE id_personne id_personne INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer l'index unique
        $this->addSql('DROP INDEX UNIQ_8D93D6495F15257A ON `user`');
        
        // Remettre la colonne nullable
        $this->addSql('ALTER TABLE `user` CHANGE id_personne id_personne INT DEFAULT NULL');
    }
}
