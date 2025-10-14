-- =============================================================================
-- Script de création de la base de données ApliRCA
-- Généré à partir du MCD mcd-transactions.puml
-- Date: 2025-10-14
-- =============================================================================

-- Suppression des tables existantes (dans l'ordre des dépendances)
DROP TABLE IF EXISTS Transaction;
DROP TABLE IF EXISTS PersonneEntreprise;
DROP TABLE IF EXISTS User;
DROP TABLE IF EXISTS Personne;
DROP TABLE IF EXISTS Entreprise;
DROP TABLE IF EXISTS Exercice;
DROP TABLE IF EXISTS TypeTransaction;
DROP TABLE IF EXISTS Role;

-- =============================================================================
-- TABLES DE RÉFÉRENCE
-- =============================================================================

-- Table Role
CREATE TABLE Role (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    
    -- Index
    INDEX idx_role_libelle (libelle)
);

-- Table TypeTransaction
CREATE TABLE TypeTransaction (
    id_type INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    
    -- Index
    INDEX idx_type_libelle (libelle)
);

-- Table Exercice
CREATE TABLE Exercice (
    id_exercice INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    
    -- Contraintes
    CONSTRAINT chk_exercice_dates CHECK (date_fin IS NULL OR date_fin >= date_debut),
    
    -- Index
    INDEX idx_exercice_dates (date_debut, date_fin)
);

-- =============================================================================
-- TABLES PRINCIPALES
-- =============================================================================

-- Table Personne
CREATE TABLE Personne (
    id_personne INT AUTO_INCREMENT PRIMARY KEY,
    civilite VARCHAR(10) NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    numero_voie VARCHAR(10) NULL,
    rue VARCHAR(200) NULL,
    complement_adresse VARCHAR(100) NULL,
    ville VARCHAR(100) NULL,
    code_postal INT NULL,
    pays VARCHAR(50) NULL DEFAULT 'France',
    telephone INT NULL,
    email VARCHAR(255) NULL,
    
    -- Contraintes
    CONSTRAINT chk_personne_civilite CHECK (civilite IN ('Mr', 'Me', 'Dr', 'Mme', 'Mlle') OR civilite IS NULL),
    CONSTRAINT chk_personne_email CHECK (email IS NULL OR email LIKE '%@%'),
    
    -- Index
    INDEX idx_personne_nom_prenom (nom, prenom),
    INDEX idx_personne_email (email)
);

-- Table Entreprise
CREATE TABLE Entreprise (
    id_entreprise INT AUTO_INCREMENT PRIMARY KEY,
    nom_entreprise VARCHAR(255) NOT NULL,
    siret VARCHAR(14) NULL UNIQUE,
    siren VARCHAR(9) NULL,
    numero_voie VARCHAR(10) NULL,
    rue VARCHAR(200) NULL,
    complement_adresse VARCHAR(100) NULL,
    ville VARCHAR(100) NULL,
    code_postal INT NULL,
    pays VARCHAR(50) NULL DEFAULT 'France',
    telephone INT NULL,
    email VARCHAR(255) NULL,
    
    -- Contraintes
    CONSTRAINT chk_entreprise_siret CHECK (siret IS NULL OR LENGTH(siret) = 14),
    CONSTRAINT chk_entreprise_siren CHECK (siren IS NULL OR LENGTH(siren) = 9),
    CONSTRAINT chk_entreprise_email CHECK (email IS NULL OR email LIKE '%@%'),
    
    -- Index
    INDEX idx_entreprise_nom (nom_entreprise),
    INDEX idx_entreprise_siret (siret),
    INDEX idx_entreprise_siren (siren)
);

-- Table User
CREATE TABLE User (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    id_personne INT NOT NULL,
    id_role INT NOT NULL,
    
    -- Clés étrangères
    CONSTRAINT fk_user_personne FOREIGN KEY (id_personne) REFERENCES Personne(id_personne) ON DELETE CASCADE,
    CONSTRAINT fk_user_role FOREIGN KEY (id_role) REFERENCES Role(id_role) ON DELETE RESTRICT,
    
    -- Index
    INDEX idx_user_username (username),
    INDEX idx_user_personne (id_personne),
    INDEX idx_user_role (id_role)
);

-- =============================================================================
-- TABLE D'ASSOCIATION
-- =============================================================================

-- Table PersonneEntreprise (relation n:n)
CREATE TABLE PersonneEntreprise (
    id_personne INT NOT NULL,
    id_entreprise INT NOT NULL,
    
    -- Clé primaire composite
    PRIMARY KEY (id_personne, id_entreprise),
    
    -- Clés étrangères
    CONSTRAINT fk_pe_personne FOREIGN KEY (id_personne) REFERENCES Personne(id_personne) ON DELETE CASCADE,
    CONSTRAINT fk_pe_entreprise FOREIGN KEY (id_entreprise) REFERENCES Entreprise(id_entreprise) ON DELETE CASCADE,
    
    -- Index
    INDEX idx_pe_entreprise (id_entreprise)
);

-- =============================================================================
-- TABLE TRANSACTION
-- =============================================================================

-- Table Transaction
CREATE TABLE Transaction (
    id_transaction INT AUTO_INCREMENT PRIMARY KEY,
    numero_ordre INT NOT NULL,
    date_transaction DATE NOT NULL,
    id_exercice INT NOT NULL,
    id_type INT NOT NULL,
    id_personne INT NULL,
    id_entreprise INT NULL,
    montant DECIMAL(15,2) NOT NULL,
    
    -- Contrainte XOR : soit personne, soit entreprise, jamais les deux, jamais aucun
    CONSTRAINT chk_transaction_xor CHECK (
        (id_personne IS NOT NULL AND id_entreprise IS NULL) OR 
        (id_personne IS NULL AND id_entreprise IS NOT NULL)
    ),
    
    -- Clés étrangères
    CONSTRAINT fk_transaction_exercice FOREIGN KEY (id_exercice) REFERENCES Exercice(id_exercice) ON DELETE RESTRICT,
    CONSTRAINT fk_transaction_type FOREIGN KEY (id_type) REFERENCES TypeTransaction(id_type) ON DELETE RESTRICT,
    CONSTRAINT fk_transaction_personne FOREIGN KEY (id_personne) REFERENCES Personne(id_personne) ON DELETE RESTRICT,
    CONSTRAINT fk_transaction_entreprise FOREIGN KEY (id_entreprise) REFERENCES Entreprise(id_entreprise) ON DELETE RESTRICT,
    
    -- Index
    INDEX idx_transaction_numero_ordre (numero_ordre),
    INDEX idx_transaction_date (date_transaction),
    INDEX idx_transaction_exercice (id_exercice),
    INDEX idx_transaction_type (id_type),
    INDEX idx_transaction_personne (id_personne),
    INDEX idx_transaction_entreprise (id_entreprise),
    INDEX idx_transaction_montant (montant)
);

-- =============================================================================
-- DONNÉES D'EXEMPLE
-- =============================================================================

-- Insertion des rôles de base
INSERT INTO Role (libelle, description) VALUES
('admin', 'Administrateur système avec tous les droits'),
('user', 'Utilisateur standard'),
('moderator', 'Modérateur avec droits étendus'),
('guest', 'Invité en lecture seule');

-- Insertion des types de transaction
INSERT INTO TypeTransaction (libelle, description) VALUES
('vente', 'Transaction de vente'),
('achat', 'Transaction d\'achat'),
('remboursement', 'Remboursement client'),
('avoir', 'Avoir client'),
('frais', 'Frais divers'),
('commission', 'Commission');

-- Insertion d'un exercice de base
INSERT INTO Exercice (libelle, date_debut, date_fin) VALUES
('Exercice 2025', '2025-01-01', '2025-12-31');

-- =============================================================================
-- VUES UTILES
-- =============================================================================

-- Vue des utilisateurs avec leurs informations complètes
CREATE VIEW v_users_complet AS
SELECT 
    u.id_user,
    u.username,
    p.civilite,
    p.nom,
    p.prenom,
    p.email,
    r.libelle as role_libelle,
    r.description as role_description
FROM User u
JOIN Personne p ON u.id_personne = p.id_personne
JOIN Role r ON u.id_role = r.id_role;

-- Vue des transactions avec détails
CREATE VIEW v_transactions_detail AS
SELECT 
    t.id_transaction,
    t.numero_ordre,
    t.date_transaction,
    t.montant,
    e.libelle as exercice,
    tt.libelle as type_transaction,
    CASE 
        WHEN t.id_personne IS NOT NULL THEN CONCAT(p.prenom, ' ', p.nom)
        WHEN t.id_entreprise IS NOT NULL THEN ent.nom_entreprise
    END as entite,
    CASE 
        WHEN t.id_personne IS NOT NULL THEN 'Personne'
        WHEN t.id_entreprise IS NOT NULL THEN 'Entreprise'
    END as type_entite
FROM Transaction t
JOIN Exercice e ON t.id_exercice = e.id_exercice
JOIN TypeTransaction tt ON t.id_type = tt.id_type
LEFT JOIN Personne p ON t.id_personne = p.id_personne
LEFT JOIN Entreprise ent ON t.id_entreprise = ent.id_entreprise;

-- =============================================================================
-- PROCÉDURES STOCKÉES UTILES
-- =============================================================================

DELIMITER //

-- Procédure pour créer un utilisateur complet
CREATE PROCEDURE sp_create_user_complet(
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_civilite VARCHAR(10),
    IN p_nom VARCHAR(100),
    IN p_prenom VARCHAR(100),
    IN p_email VARCHAR(255),
    IN p_role_id INT
)
BEGIN
    DECLARE v_personne_id INT;
    
    START TRANSACTION;
    
    -- Créer la personne
    INSERT INTO Personne (civilite, nom, prenom, email)
    VALUES (p_civilite, p_nom, p_prenom, p_email);
    
    SET v_personne_id = LAST_INSERT_ID();
    
    -- Créer l'utilisateur
    INSERT INTO User (username, password, id_personne, id_role)
    VALUES (p_username, p_password, v_personne_id, p_role_id);
    
    COMMIT;
    
    SELECT v_personne_id as personne_id, LAST_INSERT_ID() as user_id;
END //

DELIMITER ;

-- =============================================================================
-- COMMENTAIRES ET DOCUMENTATION
-- =============================================================================

-- Ajout de commentaires sur les tables
ALTER TABLE Transaction COMMENT = 'Table des transactions - Contrainte XOR sur personne/entreprise';
ALTER TABLE User COMMENT = 'Utilisateurs du système - Lié obligatoirement à une personne';
ALTER TABLE PersonneEntreprise COMMENT = 'Association many-to-many entre personnes et entreprises';

SHOW TABLES;