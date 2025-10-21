<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251021144708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les numéros d\'ordre aux exercices et modifie la contrainte unique des transactions pour être unique par exercice';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Ajouter le champ numero_ordre à la table exercice
        $this->addSql('ALTER TABLE exercice ADD numero_ordre INT NOT NULL DEFAULT 0');
        
        // Mettre à jour les numéros d'ordre des exercices existants
        $this->addSql('SET @num := 0');
        $this->addSql('UPDATE exercice SET numero_ordre = (@num := @num + 1) ORDER BY id_exercice');
        
        // Supprimer la valeur par défaut après avoir mis à jour les données existantes
        $this->addSql('ALTER TABLE exercice ALTER COLUMN numero_ordre DROP DEFAULT');
        
        // Créer l'index unique pour les transactions
        $this->addSql('CREATE UNIQUE INDEX unique_numero_ordre_exercice ON transaction (numero_ordre, id_exercice)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_numero_ordre_exercice ON transaction');
        $this->addSql('ALTER TABLE exercice DROP numero_ordre');
    }
}
