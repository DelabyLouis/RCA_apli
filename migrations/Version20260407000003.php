<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'SQLite: Fix code_postal and telephone columns to TEXT type';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        
        if ($platform === 'sqlite') {
            // SQLite: Disable foreign keys temporarily
            $this->connection->exec('PRAGMA foreign_keys = OFF');
            
            try {
                // PERSONNE table
                $this->addSql('ALTER TABLE personne RENAME TO personne_backup');
                
                $this->addSql('CREATE TABLE personne (
                    id_personne INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    nom VARCHAR(100) NOT NULL,
                    prenom VARCHAR(100) NOT NULL,
                    civilite VARCHAR(10),
                    numero_voie VARCHAR(10),
                    rue VARCHAR(200),
                    complement_adresse VARCHAR(100),
                    ville VARCHAR(100),
                    code_postal TEXT,
                    pays VARCHAR(50) DEFAULT "France",
                    telephone TEXT,
                    email VARCHAR(255),
                    deleted_at DATETIME
                )');
                
                $this->addSql('INSERT INTO personne 
                    SELECT id_personne, nom, prenom, civilite, numero_voie, rue, 
                           complement_adresse, ville, CAST(code_postal AS TEXT), pays, 
                           CAST(telephone AS TEXT), email, deleted_at 
                    FROM personne_backup');
                
                $this->addSql('DROP TABLE personne_backup');
                
                // ENTREPRISE table
                $this->addSql('ALTER TABLE entreprise RENAME TO entreprise_backup');
                
                $this->addSql('CREATE TABLE entreprise (
                    id_entreprise INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    nom_entreprise VARCHAR(255) NOT NULL,
                    siret VARCHAR(14),
                    rue VARCHAR(200),
                    complement_adresse VARCHAR(100),
                    ville VARCHAR(100),
                    code_postal TEXT,
                    pays VARCHAR(50) DEFAULT "France",
                    telephone TEXT,
                    email VARCHAR(255)
                )');
                
                $this->addSql('INSERT INTO entreprise 
                    SELECT id_entreprise, nom_entreprise, siret, rue, complement_adresse, 
                           ville, CAST(code_postal AS TEXT), pays, CAST(telephone AS TEXT), email 
                    FROM entreprise_backup');
                
                $this->addSql('DROP TABLE entreprise_backup');
                
            } finally {
                $this->connection->exec('PRAGMA foreign_keys = ON');
            }
        } elseif ($platform === 'postgresql') {
            // PostgreSQL: ALTER COLUMN type
            $this->addSql('ALTER TABLE personne ALTER COLUMN code_postal TYPE VARCHAR(10) USING code_postal::VARCHAR(10)');
            $this->addSql('ALTER TABLE personne ALTER COLUMN telephone TYPE VARCHAR(20)');
            $this->addSql('ALTER TABLE entreprise ALTER COLUMN code_postal TYPE VARCHAR(10) USING code_postal::VARCHAR(10)');
            $this->addSql('ALTER TABLE entreprise ALTER COLUMN telephone TYPE VARCHAR(20)');
        } elseif ($platform === 'mysql') {
            // MySQL: ALTER COLUMN type
            $this->addSql('ALTER TABLE personne MODIFY code_postal VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $this->addSql('ALTER TABLE personne MODIFY telephone VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $this->addSql('ALTER TABLE entreprise MODIFY code_postal VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $this->addSql('ALTER TABLE entreprise MODIFY telephone VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    public function down(Schema $schema): void
    {
        // Rollback not easily reversible for SQLite schema changes
    }
}