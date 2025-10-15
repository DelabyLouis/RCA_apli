<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015081432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE entreprise (id_entreprise INT AUTO_INCREMENT NOT NULL, nom_entreprise VARCHAR(255) NOT NULL, siret VARCHAR(14) DEFAULT NULL, siren VARCHAR(9) DEFAULT NULL, numero_voie VARCHAR(10) DEFAULT NULL, rue VARCHAR(200) DEFAULT NULL, complement_adresse VARCHAR(100) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, code_postal INT DEFAULT NULL, pays VARCHAR(50) DEFAULT \'France\', telephone INT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_D19FA6026E94372 (siret), PRIMARY KEY(id_entreprise)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE exercice (id_exercice INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, date_debut DATE NOT NULL, date_fin DATE DEFAULT NULL, PRIMARY KEY(id_exercice)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personne (id_personne INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, civilite VARCHAR(10) DEFAULT NULL, numero_voie VARCHAR(10) DEFAULT NULL, rue VARCHAR(200) DEFAULT NULL, complement_adresse VARCHAR(100) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, code_postal INT DEFAULT NULL, pays VARCHAR(50) DEFAULT \'France\', telephone INT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id_personne)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personne_entreprise (id_personne INT NOT NULL, id_entreprise INT NOT NULL, INDEX IDX_F710B2645F15257A (id_personne), INDEX IDX_F710B264A8937AB7 (id_entreprise), PRIMARY KEY(id_personne, id_entreprise)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id_role INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_57698A6AA4D60759 (libelle), PRIMARY KEY(id_role)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (id_transaction INT AUTO_INCREMENT NOT NULL, id_exercice INT NOT NULL, id_type INT NOT NULL, id_personne INT DEFAULT NULL, id_entreprise INT DEFAULT NULL, numero_ordre INT NOT NULL, date_transaction DATE NOT NULL, montant NUMERIC(15, 2) NOT NULL, INDEX IDX_723705D1B4C32BD8 (id_exercice), INDEX IDX_723705D17FE4B2B (id_type), INDEX IDX_723705D15F15257A (id_personne), INDEX IDX_723705D1A8937AB7 (id_entreprise), PRIMARY KEY(id_transaction)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_transaction (id_type INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_392ED240A4D60759 (libelle), PRIMARY KEY(id_type)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id_user INT AUTO_INCREMENT NOT NULL, id_personne INT NOT NULL, id_role INT NOT NULL, username VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, INDEX IDX_8D93D6495F15257A (id_personne), INDEX IDX_8D93D649DC499668 (id_role), UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY(id_user)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE personne_entreprise ADD CONSTRAINT FK_F710B2645F15257A FOREIGN KEY (id_personne) REFERENCES personne (id_personne)');
        $this->addSql('ALTER TABLE personne_entreprise ADD CONSTRAINT FK_F710B264A8937AB7 FOREIGN KEY (id_entreprise) REFERENCES entreprise (id_entreprise)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1B4C32BD8 FOREIGN KEY (id_exercice) REFERENCES exercice (id_exercice)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D17FE4B2B FOREIGN KEY (id_type) REFERENCES type_transaction (id_type)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D15F15257A FOREIGN KEY (id_personne) REFERENCES personne (id_personne)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A8937AB7 FOREIGN KEY (id_entreprise) REFERENCES entreprise (id_entreprise)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6495F15257A FOREIGN KEY (id_personne) REFERENCES personne (id_personne)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649DC499668 FOREIGN KEY (id_role) REFERENCES role (id_role)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personne_entreprise DROP FOREIGN KEY FK_F710B2645F15257A');
        $this->addSql('ALTER TABLE personne_entreprise DROP FOREIGN KEY FK_F710B264A8937AB7');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1B4C32BD8');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D17FE4B2B');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D15F15257A');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A8937AB7');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6495F15257A');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649DC499668');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE exercice');
        $this->addSql('DROP TABLE personne');
        $this->addSql('DROP TABLE personne_entreprise');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE type_transaction');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
