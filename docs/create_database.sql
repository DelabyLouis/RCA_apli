-- =============================================================================-- =============================================================================

-- Script de création de la base de données ApliRCA-- Script de création de la base de données ApliRCA

-- Généré à partir de l'analyse des entités Symfony/Doctrine-- Généré à partir du MCD mcd-transactions.puml

-- Date: 2025-11-04-- Date: 2025-10-14

-- Version: 2.0-- =============================================================================

-- =============================================================================

-- Suppression des tables existantes (dans l'ordre des dépendances)

-- Suppression des tables existantes (dans l'ordre des dépendances)DROP TABLE IF EXISTS Transaction;

DROP TABLE IF EXISTS audit_trail;DROP TABLE IF EXISTS PersonneEntreprise;

DROP TABLE IF EXISTS audit_suppression;DROP TABLE IF EXISTS User;

DROP TABLE IF EXISTS consentement_rgpd;DROP TABLE IF EXISTS Personne;

DROP TABLE IF EXISTS historique_cloture;DROP TABLE IF EXISTS Entreprise;

DROP TABLE IF EXISTS transaction;DROP TABLE IF EXISTS Exercice;

DROP TABLE IF EXISTS personne_entreprise;DROP TABLE IF EXISTS TypeTransaction;

DROP TABLE IF EXISTS user_role;DROP TABLE IF EXISTS Role;

DROP TABLE IF EXISTS role_permission;

DROP TABLE IF EXISTS `user`;-- =============================================================================

DROP TABLE IF EXISTS personne;-- TABLES DE RÉFÉRENCE

DROP TABLE IF EXISTS entreprise;-- =============================================================================

DROP TABLE IF EXISTS exercice;

DROP TABLE IF EXISTS type_transaction;-- Table Role

DROP TABLE IF EXISTS mode_de_paiement;CREATE TABLE Role (

DROP TABLE IF EXISTS permission;    id_role INT AUTO_INCREMENT PRIMARY KEY,

DROP TABLE IF EXISTS role;    libelle VARCHAR(50) NOT NULL UNIQUE,

    description VARCHAR(255) NULL,

-- =============================================================================    

-- TABLES DE RÉFÉRENCE ET CONFIGURATION    -- Index

-- =============================================================================    INDEX idx_role_libelle (libelle)

);

-- Table Role

CREATE TABLE role (-- Table TypeTransaction

    id_role INT AUTO_INCREMENT PRIMARY KEY,CREATE TABLE TypeTransaction (

    libelle VARCHAR(50) NOT NULL UNIQUE,    id_type INT AUTO_INCREMENT PRIMARY KEY,

    description VARCHAR(255) NULL,    libelle VARCHAR(100) NOT NULL UNIQUE,

        description VARCHAR(255) NULL,

    -- Index    

    INDEX idx_role_libelle (libelle)    -- Index

);    INDEX idx_type_libelle (libelle)

);

-- Table Permission

CREATE TABLE permission (-- Table Exercice

    id INT AUTO_INCREMENT PRIMARY KEY,CREATE TABLE Exercice (

    name VARCHAR(100) NOT NULL UNIQUE,    id_exercice INT AUTO_INCREMENT PRIMARY KEY,

    route VARCHAR(100) NOT NULL UNIQUE,    libelle VARCHAR(100) NOT NULL,

    description VARCHAR(255) NULL,    date_debut DATE NOT NULL,

    public_access BOOLEAN NOT NULL DEFAULT FALSE,    date_fin DATE NULL,

        

    -- Index    -- Contraintes

    INDEX idx_permission_name (name),    CONSTRAINT chk_exercice_dates CHECK (date_fin IS NULL OR date_fin >= date_debut),

    INDEX idx_permission_route (route)    

);    -- Index

    INDEX idx_exercice_dates (date_debut, date_fin)

-- Table de liaison Role-Permission);

CREATE TABLE role_permission (

    role_id INT NOT NULL,-- =============================================================================

    permission_id INT NOT NULL,-- TABLES PRINCIPALES

    -- =============================================================================

    PRIMARY KEY (role_id, permission_id),

    -- Table Personne

    CONSTRAINT fk_role_permission_role CREATE TABLE Personne (

        FOREIGN KEY (role_id) REFERENCES role(id_role) ON DELETE CASCADE,    id_personne INT AUTO_INCREMENT PRIMARY KEY,

    CONSTRAINT fk_role_permission_permission     civilite VARCHAR(10) NULL,

        FOREIGN KEY (permission_id) REFERENCES permission(id) ON DELETE CASCADE    nom VARCHAR(100) NOT NULL,

);    prenom VARCHAR(100) NOT NULL,

    numero_voie VARCHAR(10) NULL,

-- Table TypeTransaction    rue VARCHAR(200) NULL,

CREATE TABLE type_transaction (    complement_adresse VARCHAR(100) NULL,

    id_type INT AUTO_INCREMENT PRIMARY KEY,    ville VARCHAR(100) NULL,

    libelle VARCHAR(100) NOT NULL UNIQUE,    code_postal INT NULL,

    description VARCHAR(255) NULL,    pays VARCHAR(50) NULL DEFAULT 'France',

        telephone INT NULL,

    -- Index    email VARCHAR(255) NULL,

    INDEX idx_type_libelle (libelle)    

);    -- Contraintes

    CONSTRAINT chk_personne_civilite CHECK (civilite IN ('Mr', 'Me', 'Dr', 'Mme', 'Mlle') OR civilite IS NULL),

-- Table ModeDePaiement    CONSTRAINT chk_personne_email CHECK (email IS NULL OR email LIKE '%@%'),

CREATE TABLE mode_de_paiement (    

    id INT AUTO_INCREMENT PRIMARY KEY,    -- Index

    libelle VARCHAR(255) NOT NULL,    INDEX idx_personne_nom_prenom (nom, prenom),

        INDEX idx_personne_email (email)

    -- Index);

    INDEX idx_mode_paiement_libelle (libelle)

);-- Table Entreprise

CREATE TABLE Entreprise (

-- Table Exercice    id_entreprise INT AUTO_INCREMENT PRIMARY KEY,

CREATE TABLE exercice (    nom_entreprise VARCHAR(255) NOT NULL,

    id_exercice INT AUTO_INCREMENT PRIMARY KEY,    siret VARCHAR(14) NULL UNIQUE,

    libelle VARCHAR(100) NOT NULL,    siren VARCHAR(9) NULL,

    numero_ordre INT NOT NULL UNIQUE,    numero_voie VARCHAR(10) NULL,

    date_debut DATE NOT NULL,    rue VARCHAR(200) NULL,

    date_fin DATE NULL,    complement_adresse VARCHAR(100) NULL,

    clos BOOLEAN NOT NULL DEFAULT FALSE,    ville VARCHAR(100) NULL,

        code_postal INT NULL,

    -- Contraintes    pays VARCHAR(50) NULL DEFAULT 'France',

    CONSTRAINT chk_exercice_dates CHECK (date_fin IS NULL OR date_fin >= date_debut),    telephone INT NULL,

        email VARCHAR(255) NULL,

    -- Index    

    INDEX idx_exercice_dates (date_debut, date_fin),    -- Contraintes

    INDEX idx_exercice_numero (numero_ordre)    CONSTRAINT chk_entreprise_siret CHECK (siret IS NULL OR LENGTH(siret) = 14),

);    CONSTRAINT chk_entreprise_siren CHECK (siren IS NULL OR LENGTH(siren) = 9),

    CONSTRAINT chk_entreprise_email CHECK (email IS NULL OR email LIKE '%@%'),

-- =============================================================================    

-- TABLES PRINCIPALES    -- Index

-- =============================================================================    INDEX idx_entreprise_nom (nom_entreprise),

    INDEX idx_entreprise_siret (siret),

-- Table Personne    INDEX idx_entreprise_siren (siren)

CREATE TABLE personne ();

    id_personne INT AUTO_INCREMENT PRIMARY KEY,

    nom VARCHAR(100) NOT NULL,-- Table User

    prenom VARCHAR(100) NOT NULL,CREATE TABLE User (

    civilite VARCHAR(10) NULL,    id_user INT AUTO_INCREMENT PRIMARY KEY,

    numero_voie VARCHAR(10) NULL,    username VARCHAR(50) NOT NULL UNIQUE,

    rue VARCHAR(200) NULL,    password VARCHAR(255) NOT NULL,

    complement_adresse VARCHAR(100) NULL,    id_personne INT NOT NULL,

    ville VARCHAR(100) NULL,    id_role INT NOT NULL,

    code_postal INT NULL,    

    pays VARCHAR(50) NULL DEFAULT 'France',    -- Clés étrangères

    telephone INT NULL,    CONSTRAINT fk_user_personne FOREIGN KEY (id_personne) REFERENCES Personne(id_personne) ON DELETE CASCADE,

    email VARCHAR(255) NULL,    CONSTRAINT fk_user_role FOREIGN KEY (id_role) REFERENCES Role(id_role) ON DELETE RESTRICT,

    deleted_at DATETIME NULL, -- Soft delete    

        -- Index

    -- Contraintes    INDEX idx_user_username (username),

    CONSTRAINT chk_personne_civilite CHECK (civilite IN ('Mr', 'Mme', 'Mlle', 'Dr') OR civilite IS NULL),    INDEX idx_user_personne (id_personne),

    CONSTRAINT chk_personne_email CHECK (email IS NULL OR email LIKE '%@%'),    INDEX idx_user_role (id_role)

    );

    -- Index

    INDEX idx_personne_nom_prenom (nom, prenom),-- =============================================================================

    INDEX idx_personne_email (email),-- TABLE D'ASSOCIATION

    INDEX idx_personne_deleted (deleted_at)-- =============================================================================

);

-- Table PersonneEntreprise (relation n:n)

-- Table EntrepriseCREATE TABLE PersonneEntreprise (

CREATE TABLE entreprise (    id_personne INT NOT NULL,

    id_entreprise INT AUTO_INCREMENT PRIMARY KEY,    id_entreprise INT NOT NULL,

    nom_entreprise VARCHAR(255) NOT NULL,    

    siret VARCHAR(14) NULL UNIQUE,    -- Clé primaire composite

    siren VARCHAR(9) NULL,    PRIMARY KEY (id_personne, id_entreprise),

    numero_voie VARCHAR(10) NULL,    

    rue VARCHAR(200) NULL,    -- Clés étrangères

    complement_adresse VARCHAR(100) NULL,    CONSTRAINT fk_pe_personne FOREIGN KEY (id_personne) REFERENCES Personne(id_personne) ON DELETE CASCADE,

    ville VARCHAR(100) NULL,    CONSTRAINT fk_pe_entreprise FOREIGN KEY (id_entreprise) REFERENCES Entreprise(id_entreprise) ON DELETE CASCADE,

    code_postal INT NULL,    

    pays VARCHAR(50) NULL DEFAULT 'France',    -- Index

    telephone INT NULL,    INDEX idx_pe_entreprise (id_entreprise)

    email VARCHAR(255) NULL,);

    

    -- Contraintes-- =============================================================================

    CONSTRAINT chk_entreprise_siret CHECK (siret IS NULL OR LENGTH(siret) = 14),-- TABLE TRANSACTION

    CONSTRAINT chk_entreprise_siren CHECK (siren IS NULL OR LENGTH(siren) = 9),-- =============================================================================

    CONSTRAINT chk_entreprise_email CHECK (email IS NULL OR email LIKE '%@%'),

    -- Table Transaction

    -- IndexCREATE TABLE Transaction (

    INDEX idx_entreprise_nom (nom_entreprise),    id_transaction INT AUTO_INCREMENT PRIMARY KEY,

    INDEX idx_entreprise_siret (siret),    numero_ordre INT NOT NULL,

    INDEX idx_entreprise_email (email)    date_transaction DATE NOT NULL,

);    id_exercice INT NOT NULL,

    id_type INT NOT NULL,

-- Table de liaison Personne-Entreprise    id_personne INT NULL,

CREATE TABLE personne_entreprise (    id_entreprise INT NULL,

    id_personne INT NOT NULL,    montant DECIMAL(15,2) NOT NULL,

    id_entreprise INT NOT NULL,    

        -- Contrainte XOR : soit personne, soit entreprise, jamais les deux, jamais aucun

    PRIMARY KEY (id_personne, id_entreprise),    CONSTRAINT chk_transaction_xor CHECK (

            (id_personne IS NOT NULL AND id_entreprise IS NULL) OR 

    CONSTRAINT fk_personne_entreprise_personne         (id_personne IS NULL AND id_entreprise IS NOT NULL)

        FOREIGN KEY (id_personne) REFERENCES personne(id_personne) ON DELETE CASCADE,    ),

    CONSTRAINT fk_personne_entreprise_entreprise     

        FOREIGN KEY (id_entreprise) REFERENCES entreprise(id_entreprise) ON DELETE CASCADE    -- Clés étrangères

);    CONSTRAINT fk_transaction_exercice FOREIGN KEY (id_exercice) REFERENCES Exercice(id_exercice) ON DELETE RESTRICT,

    CONSTRAINT fk_transaction_type FOREIGN KEY (id_type) REFERENCES TypeTransaction(id_type) ON DELETE RESTRICT,

-- Table User    CONSTRAINT fk_transaction_personne FOREIGN KEY (id_personne) REFERENCES Personne(id_personne) ON DELETE RESTRICT,

CREATE TABLE `user` (    CONSTRAINT fk_transaction_entreprise FOREIGN KEY (id_entreprise) REFERENCES Entreprise(id_entreprise) ON DELETE RESTRICT,

    id_user INT AUTO_INCREMENT PRIMARY KEY,    

    username VARCHAR(50) NOT NULL UNIQUE,    -- Index

    password VARCHAR(255) NOT NULL,    INDEX idx_transaction_numero_ordre (numero_ordre),

    id_personne INT NOT NULL UNIQUE,    INDEX idx_transaction_date (date_transaction),

        INDEX idx_transaction_exercice (id_exercice),

    CONSTRAINT fk_user_personne     INDEX idx_transaction_type (id_type),

        FOREIGN KEY (id_personne) REFERENCES personne(id_personne) ON DELETE CASCADE,    INDEX idx_transaction_personne (id_personne),

        INDEX idx_transaction_entreprise (id_entreprise),

    -- Index    INDEX idx_transaction_montant (montant)

    INDEX idx_user_username (username));

);

-- =============================================================================

-- Table de liaison User-Role-- DONNÉES D'EXEMPLE

CREATE TABLE user_role (-- =============================================================================

    user_id INT NOT NULL,

    role_id INT NOT NULL,-- Insertion des rôles de base

    INSERT INTO Role (libelle, description) VALUES

    PRIMARY KEY (user_id, role_id),('admin', 'Administrateur système avec tous les droits'),

    ('user', 'Utilisateur standard'),

    CONSTRAINT fk_user_role_user ('moderator', 'Modérateur avec droits étendus'),

        FOREIGN KEY (user_id) REFERENCES `user`(id_user) ON DELETE CASCADE,('guest', 'Invité en lecture seule');

    CONSTRAINT fk_user_role_role 

        FOREIGN KEY (role_id) REFERENCES role(id_role) ON DELETE CASCADE-- Insertion des types de transaction

);INSERT INTO TypeTransaction (libelle, description) VALUES

('vente', 'Transaction de vente'),

-- =============================================================================('achat', 'Transaction d\'achat'),

-- TABLES TRANSACTIONNELLES('remboursement', 'Remboursement client'),

-- =============================================================================('avoir', 'Avoir client'),

('frais', 'Frais divers'),

-- Table Transaction('commission', 'Commission');

CREATE TABLE transaction (

    id_transaction INT AUTO_INCREMENT PRIMARY KEY,-- Insertion d'un exercice de base

    libelle VARCHAR(255) NOT NULL UNIQUE,INSERT INTO Exercice (libelle, date_debut, date_fin) VALUES

    numero_ordre INT NOT NULL,('Exercice 2025', '2025-01-01', '2025-12-31');

    date_transaction DATE NOT NULL,

    montant DECIMAL(15,2) NOT NULL,-- =============================================================================

    type_compte VARCHAR(50) NOT NULL DEFAULT 'compte_courant',-- VUES UTILES

    id_exercice INT NOT NULL,-- =============================================================================

    id_type INT NOT NULL,

    id_personne INT NULL,-- Vue des utilisateurs avec leurs informations complètes

    id_entreprise INT NULL,CREATE VIEW v_users_complet AS

    mode_de_paiement_id INT NULL,SELECT 

        u.id_user,

    -- Contraintes    u.username,

    CONSTRAINT chk_transaction_montant CHECK (montant != 0),    p.civilite,

    CONSTRAINT chk_transaction_xor CHECK (    p.nom,

        (id_personne IS NOT NULL AND id_entreprise IS NULL) OR     p.prenom,

        (id_personne IS NULL AND id_entreprise IS NOT NULL)    p.email,

    ),    r.libelle as role_libelle,

    CONSTRAINT chk_transaction_type_compte CHECK (type_compte IN ('compte_courant', 'livret')),    r.description as role_description

    FROM User u

    -- Contrainte d'unicité sur numéro d'ordre par exerciceJOIN Personne p ON u.id_personne = p.id_personne

    CONSTRAINT unique_numero_ordre_exercice UNIQUE (numero_ordre, id_exercice),JOIN Role r ON u.id_role = r.id_role;

    

    -- Clés étrangères-- Vue des transactions avec détails

    CONSTRAINT fk_transaction_exercice CREATE VIEW v_transactions_detail AS

        FOREIGN KEY (id_exercice) REFERENCES exercice(id_exercice) ON DELETE RESTRICT,SELECT 

    CONSTRAINT fk_transaction_type     t.id_transaction,

        FOREIGN KEY (id_type) REFERENCES type_transaction(id_type) ON DELETE RESTRICT,    t.numero_ordre,

    CONSTRAINT fk_transaction_personne     t.date_transaction,

        FOREIGN KEY (id_personne) REFERENCES personne(id_personne) ON DELETE RESTRICT,    t.montant,

    CONSTRAINT fk_transaction_entreprise     e.libelle as exercice,

        FOREIGN KEY (id_entreprise) REFERENCES entreprise(id_entreprise) ON DELETE RESTRICT,    tt.libelle as type_transaction,

    CONSTRAINT fk_transaction_mode_paiement     CASE 

        FOREIGN KEY (mode_de_paiement_id) REFERENCES mode_de_paiement(id) ON DELETE SET NULL,        WHEN t.id_personne IS NOT NULL THEN CONCAT(p.prenom, ' ', p.nom)

            WHEN t.id_entreprise IS NOT NULL THEN ent.nom_entreprise

    -- Index    END as entite,

    INDEX idx_transaction_date (date_transaction),    CASE 

    INDEX idx_transaction_exercice (id_exercice),        WHEN t.id_personne IS NOT NULL THEN 'Personne'

    INDEX idx_transaction_personne (id_personne),        WHEN t.id_entreprise IS NOT NULL THEN 'Entreprise'

    INDEX idx_transaction_entreprise (id_entreprise),    END as type_entite

    INDEX idx_transaction_type (id_type),FROM Transaction t

    INDEX idx_transaction_numero_ordre (numero_ordre)JOIN Exercice e ON t.id_exercice = e.id_exercice

);JOIN TypeTransaction tt ON t.id_type = tt.id_type

LEFT JOIN Personne p ON t.id_personne = p.id_personne

-- =============================================================================LEFT JOIN Entreprise ent ON t.id_entreprise = ent.id_entreprise;

-- TABLES D'AUDIT ET HISTORIQUE

-- =============================================================================-- =============================================================================

-- PROCÉDURES STOCKÉES UTILES

-- Table HistoriqueCloture-- =============================================================================

CREATE TABLE historique_cloture (

    id_historique INT AUTO_INCREMENT PRIMARY KEY,DELIMITER //

    id_exercice INT NOT NULL,

    date_action DATETIME NOT NULL,-- Procédure pour créer un utilisateur complet

    type_action VARCHAR(50) NOT NULL,CREATE PROCEDURE sp_create_user_complet(

    id_user INT NULL,    IN p_username VARCHAR(50),

    commentaire TEXT NULL,    IN p_password VARCHAR(255),

        IN p_civilite VARCHAR(10),

    -- Contraintes    IN p_nom VARCHAR(100),

    CONSTRAINT chk_historique_type_action CHECK (type_action IN ('CLOTURE', 'DECLOTURE')),    IN p_prenom VARCHAR(100),

        IN p_email VARCHAR(255),

    -- Clés étrangères    IN p_role_id INT

    CONSTRAINT fk_historique_exercice )

        FOREIGN KEY (id_exercice) REFERENCES exercice(id_exercice) ON DELETE CASCADE,BEGIN

    CONSTRAINT fk_historique_user     DECLARE v_personne_id INT;

        FOREIGN KEY (id_user) REFERENCES `user`(id_user) ON DELETE SET NULL,    

        START TRANSACTION;

    -- Index    

    INDEX idx_historique_exercice (id_exercice),    -- Créer la personne

    INDEX idx_historique_date (date_action),    INSERT INTO Personne (civilite, nom, prenom, email)

    INDEX idx_historique_user (id_user)    VALUES (p_civilite, p_nom, p_prenom, p_email);

);    

    SET v_personne_id = LAST_INSERT_ID();

-- Table AuditTrail    

CREATE TABLE audit_trail (    -- Créer l'utilisateur

    id INT AUTO_INCREMENT PRIMARY KEY,    INSERT INTO User (username, password, id_personne, id_role)

    user_id INT NULL,    VALUES (p_username, p_password, v_personne_id, p_role_id);

    action VARCHAR(100) NOT NULL,    

    entity_type VARCHAR(100) NOT NULL,    COMMIT;

    entity_id INT NULL,    

    details LONGTEXT NULL,    SELECT v_personne_id as personne_id, LAST_INSERT_ID() as user_id;

    created_at DATETIME NOT NULL,END //

    ip_address VARCHAR(45) NULL,

    user_agent VARCHAR(255) NULL,DELIMITER ;

    session_id VARCHAR(100) NULL,

    route_name VARCHAR(255) NULL,-- =============================================================================

    severity VARCHAR(20) NOT NULL DEFAULT 'info',-- COMMENTAIRES ET DOCUMENTATION

    -- =============================================================================

    -- Contraintes

    CONSTRAINT chk_audit_severity CHECK (severity IN ('info', 'warning', 'error', 'critical')),-- Ajout de commentaires sur les tables

    ALTER TABLE Transaction COMMENT = 'Table des transactions - Contrainte XOR sur personne/entreprise';

    -- Clés étrangèresALTER TABLE User COMMENT = 'Utilisateurs du système - Lié obligatoirement à une personne';

    CONSTRAINT fk_audit_user ALTER TABLE PersonneEntreprise COMMENT = 'Association many-to-many entre personnes et entreprises';

        FOREIGN KEY (user_id) REFERENCES `user`(id_user) ON DELETE SET NULL,

    SHOW TABLES;
    -- Index
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_date (created_at),
    INDEX idx_audit_action (action)
);

-- Table AuditSuppression
CREATE TABLE audit_suppression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NOT NULL,
    entity_data LONGTEXT NOT NULL,
    user_id INT NULL,
    deleted_at DATETIME NOT NULL,
    deletion_reason VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    deletion_type VARCHAR(20) NOT NULL DEFAULT 'soft',
    scheduled_hard_delete DATETIME NULL,
    
    -- Contraintes
    CONSTRAINT chk_audit_suppression_type CHECK (deletion_type IN ('soft', 'hard', 'gdpr_request')),
    
    -- Clés étrangères
    CONSTRAINT fk_audit_suppression_user 
        FOREIGN KEY (user_id) REFERENCES `user`(id_user) ON DELETE SET NULL,
    
    -- Index
    INDEX idx_audit_suppression_entity (entity_type, entity_id),
    INDEX idx_audit_suppression_date (deleted_at),
    INDEX idx_audit_suppression_user (user_id),
    INDEX idx_audit_suppression_scheduled (scheduled_hard_delete)
);

-- =============================================================================
-- TABLES RGPD ET CONSENTEMENT
-- =============================================================================

-- Table ConsentementRgpd
CREATE TABLE consentement_rgpd (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type_consentement VARCHAR(50) NOT NULL,
    accepte BOOLEAN NOT NULL,
    date_consentement DATETIME NOT NULL,
    version_politique VARCHAR(20) NULL,
    ip_address VARCHAR(45) NULL,
    
    -- Contraintes
    CONSTRAINT chk_consentement_type CHECK (type_consentement IN ('privacy_policy', 'communication', 'analytics', 'marketing')),
    
    -- Clés étrangères
    CONSTRAINT fk_consentement_user 
        FOREIGN KEY (user_id) REFERENCES `user`(id_user) ON DELETE CASCADE,
    
    -- Index
    INDEX idx_consentement_user (user_id),
    INDEX idx_consentement_type (type_consentement),
    INDEX idx_consentement_date (date_consentement)
);

-- =============================================================================
-- DONNÉES INITIALES
-- =============================================================================

-- Insertion des rôles par défaut
INSERT INTO role (libelle, description) VALUES
('ROLE_ADMIN', 'Administrateur système avec tous les droits'),
('ROLE_USER', 'Utilisateur standard avec droits limités'),
('ROLE_MODERATOR', 'Modérateur avec droits intermédiaires');

-- Insertion des permissions par défaut
INSERT INTO permission (name, route, description, public_access) VALUES
('user.list', 'app_user_index', 'Lister les utilisateurs', FALSE),
('user.create', 'app_user_new', 'Créer un utilisateur', FALSE),
('user.edit', 'app_user_edit', 'Modifier un utilisateur', FALSE),
('user.delete', 'app_user_delete', 'Supprimer un utilisateur', FALSE),
('transaction.list', 'app_transaction_index', 'Lister les transactions', FALSE),
('transaction.create', 'app_transaction_new', 'Créer une transaction', FALSE),
('transaction.edit', 'app_transaction_edit', 'Modifier une transaction', FALSE),
('transaction.delete', 'app_transaction_delete', 'Supprimer une transaction', FALSE),
('exercice.manage', 'app_exercice_index', 'Gérer les exercices', FALSE),
('home.access', 'app_home', 'Accès à l\'accueil', TRUE);

-- Assignation des permissions aux rôles
INSERT INTO role_permission (role_id, permission_id) 
SELECT r.id_role, p.id 
FROM role r, permission p 
WHERE r.libelle = 'ROLE_ADMIN';

INSERT INTO role_permission (role_id, permission_id) 
SELECT r.id_role, p.id 
FROM role r, permission p 
WHERE r.libelle = 'ROLE_USER' AND p.public_access = TRUE;

-- Insertion des types de transaction par défaut
INSERT INTO type_transaction (libelle, description) VALUES
('Dépôt', 'Dépôt d\'argent sur le compte'),
('Retrait', 'Retrait d\'argent du compte'),
('Virement', 'Virement entre comptes'),
('Prélèvement', 'Prélèvement automatique'),
('Chèque', 'Paiement par chèque'),
('Carte bancaire', 'Paiement par carte bancaire');

-- Insertion des modes de paiement par défaut
INSERT INTO mode_de_paiement (libelle) VALUES
('Espèces'),
('Chèque'),
('Virement bancaire'),
('Carte bancaire'),
('Prélèvement automatique'),
('PayPal'),
('Autre');

-- =============================================================================
-- VUES UTILES
-- =============================================================================

-- Vue pour les transactions avec détails complets
CREATE VIEW vue_transactions_completes AS
SELECT 
    t.id_transaction,
    t.libelle,
    t.numero_ordre,
    t.date_transaction,
    t.montant,
    t.type_compte,
    e.libelle AS exercice_libelle,
    e.date_debut AS exercice_debut,
    e.date_fin AS exercice_fin,
    tt.libelle AS type_transaction,
    COALESCE(p.nom, ent.nom_entreprise) AS nom_beneficiaire,
    COALESCE(p.prenom, '') AS prenom_beneficiaire,
    mp.libelle AS mode_paiement
FROM transaction t
JOIN exercice e ON t.id_exercice = e.id_exercice
JOIN type_transaction tt ON t.id_type = tt.id_type
LEFT JOIN personne p ON t.id_personne = p.id_personne AND p.deleted_at IS NULL
LEFT JOIN entreprise ent ON t.id_entreprise = ent.id_entreprise
LEFT JOIN mode_de_paiement mp ON t.mode_de_paiement_id = mp.id;

-- Vue pour les soldes par exercice
CREATE VIEW vue_soldes_exercice AS
SELECT 
    e.id_exercice,
    e.libelle AS exercice_libelle,
    e.date_debut,
    e.date_fin,
    e.clos,
    COUNT(t.id_transaction) AS nombre_transactions,
    COALESCE(SUM(CASE WHEN t.type_compte = 'compte_courant' THEN t.montant ELSE 0 END), 0) AS solde_compte_courant,
    COALESCE(SUM(CASE WHEN t.type_compte = 'livret' THEN t.montant ELSE 0 END), 0) AS solde_livret,
    COALESCE(SUM(t.montant), 0) AS solde_total
FROM exercice e
LEFT JOIN transaction t ON e.id_exercice = t.id_exercice
GROUP BY e.id_exercice, e.libelle, e.date_debut, e.date_fin, e.clos;

-- =============================================================================
-- TRIGGERS
-- =============================================================================

-- Trigger pour auditer les modifications de transactions
DELIMITER $$

CREATE TRIGGER audit_transaction_update 
    AFTER UPDATE ON transaction
    FOR EACH ROW
BEGIN
    INSERT INTO audit_trail (
        user_id, action, entity_type, entity_id, 
        details, created_at, severity
    ) VALUES (
        @current_user_id, 'UPDATE', 'Transaction', NEW.id_transaction,
        CONCAT('Montant: ', OLD.montant, ' -> ', NEW.montant, 
               ', Libellé: ', OLD.libelle, ' -> ', NEW.libelle),
        NOW(), 'info'
    );
END$$

CREATE TRIGGER audit_transaction_delete 
    BEFORE DELETE ON transaction
    FOR EACH ROW
BEGIN
    INSERT INTO audit_suppression (
        entity_type, entity_id, entity_data, user_id, 
        deleted_at, deletion_type
    ) VALUES (
        'Transaction', OLD.id_transaction,
        JSON_OBJECT(
            'libelle', OLD.libelle,
            'montant', OLD.montant,
            'date_transaction', OLD.date_transaction,
            'numero_ordre', OLD.numero_ordre
        ),
        @current_user_id, NOW(), 'hard'
    );
END$$

DELIMITER ;

-- =============================================================================
-- COMMENTAIRES ET DOCUMENTATION
-- =============================================================================

-- Ajout de commentaires sur les tables principales
ALTER TABLE personne COMMENT = 'Table des personnes physiques du système';
ALTER TABLE entreprise COMMENT = 'Table des entreprises/personnes morales';
ALTER TABLE transaction COMMENT = 'Table des transactions financières';
ALTER TABLE exercice COMMENT = 'Table des exercices comptables';
ALTER TABLE audit_trail COMMENT = 'Historique des actions utilisateurs';
ALTER TABLE audit_suppression COMMENT = 'Audit des suppressions d\'entités';
ALTER TABLE consentement_rgpd COMMENT = 'Gestion des consentements RGPD';

-- =============================================================================
-- FIN DU SCRIPT
-- =============================================================================

-- Vérification de l'intégrité
SELECT 'Script de création exécuté avec succès' AS status;
SELECT COUNT(*) AS nb_tables FROM information_schema.tables WHERE table_schema = DATABASE();