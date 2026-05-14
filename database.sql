DROP DATABASE IF EXISTS gestion_heures_uvci;
CREATE DATABASE gestion_heures_uvci
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE gestion_heures_uvci;

-- ============================================================
-- 1. ROLES ET UTILISATEURS
-- ============================================================

CREATE TABLE role (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    libelle_role VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE utilisateur (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_role INT NOT NULL,

    CONSTRAINT fk_utilisateur_role
        FOREIGN KEY (id_role)
        REFERENCES role(id_role)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 2. STRUCTURE ACADEMIQUE
-- ============================================================

CREATE TABLE departement (
    id_departement INT AUTO_INCREMENT PRIMARY KEY,
    nom_departement VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    actif BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE filiere (
    id_filiere INT AUTO_INCREMENT PRIMARY KEY,
    nom_filiere VARCHAR(150) NOT NULL,
    description TEXT,
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    id_departement INT NOT NULL,

    CONSTRAINT uk_filiere_departement
        UNIQUE (nom_filiere, id_departement),

    CONSTRAINT fk_filiere_departement
        FOREIGN KEY (id_departement)
        REFERENCES departement(id_departement)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE annee_academique (
    id_annee INT AUTO_INCREMENT PRIMARY KEY,
    libelle_annee VARCHAR(20) NOT NULL UNIQUE,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    est_active BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT chk_annee_dates
        CHECK (date_fin > date_debut)
) ENGINE=InnoDB;

-- ============================================================
-- 3. ENSEIGNANTS
-- ============================================================

CREATE TABLE grade (
    id_grade INT AUTO_INCREMENT PRIMARY KEY,
    libelle_grade VARCHAR(100) NOT NULL UNIQUE,
    charge_statutaire DECIMAL(10,2) NOT NULL DEFAULT 0,

    CONSTRAINT chk_grade_charge
        CHECK (charge_statutaire >= 0)
) ENGINE=InnoDB;

CREATE TABLE enseignant (
    id_enseignant INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenoms VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(30),
    statut ENUM('PERMANENT', 'VACATAIRE') NOT NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,

    id_departement INT NOT NULL,
    id_grade INT NULL,
    id_utilisateur INT NULL UNIQUE,

    CONSTRAINT fk_enseignant_departement
        FOREIGN KEY (id_departement)
        REFERENCES departement(id_departement)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_enseignant_grade
        FOREIGN KEY (id_grade)
        REFERENCES grade(id_grade)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_enseignant_utilisateur
        FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur(id_utilisateur)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 4. COURS TRANSVERSAUX
-- ============================================================

CREATE TABLE cours (
    id_cours INT AUTO_INCREMENT PRIMARY KEY,
    code_cours VARCHAR(50) NOT NULL UNIQUE,
    intitule_cours VARCHAR(200) NOT NULL,
    nombre_heures DECIMAL(10,2) NOT NULL,
    nombre_credits INT NOT NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT chk_cours_heures
        CHECK (nombre_heures > 0),

    CONSTRAINT chk_cours_credits
        CHECK (nombre_credits >= 0)
) ENGINE=InnoDB;

CREATE TABLE cours_filiere (
    id_cours INT NOT NULL,
    id_filiere INT NOT NULL,
    niveau ENUM('L1', 'L2', 'L3', 'M1', 'M2') NOT NULL,
    semestre ENUM('S1', 'S2', 'S3', 'S4', 'S5', 'S6') NOT NULL,

    PRIMARY KEY (id_cours, id_filiere, niveau, semestre),

    CONSTRAINT fk_cours_filiere_cours
        FOREIGN KEY (id_cours)
        REFERENCES cours(id_cours)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_cours_filiere_filiere
        FOREIGN KEY (id_filiere)
        REFERENCES filiere(id_filiere)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 5. RESSOURCES PEDAGOGIQUES
-- ============================================================

CREATE TABLE ressource_pedagogique (
    id_ressource INT AUTO_INCREMENT PRIMARY KEY,
    titre_ressource VARCHAR(200) NOT NULL,
    type_ressource ENUM(
        'CONTENU_TEXTUEL',
        'VIDEO',
        'DOCUMENT',
        'QUIZ',
        'ACTIVITE_INTERACTIVE',
        'EVALUATION',
        'AUTRE'
    ) NOT NULL,
    description TEXT,
    chemin_fichier VARCHAR(255),
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    id_cours INT NOT NULL,

    CONSTRAINT fk_ressource_cours
        FOREIGN KEY (id_cours)
        REFERENCES cours(id_cours)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 6. PARAMETRES DE CALCUL
-- ============================================================

CREATE TABLE parametre_calcul (
    id_parametre INT AUTO_INCREMENT PRIMARY KEY,
    type_activite ENUM('CREATION_RESSOURCE', 'MISE_A_JOUR_RESSOURCE') NOT NULL,
    niveau_complexite ENUM('NIVEAU_1', 'NIVEAU_2', 'NIVEAU_3') NOT NULL,
    coefficient DECIMAL(10,3) NOT NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT uk_parametre_calcul
        UNIQUE (type_activite, niveau_complexite),

    CONSTRAINT chk_parametre_coefficient
        CHECK (coefficient > 0)
) ENGINE=InnoDB;

-- ============================================================
-- 7. ACTIVITES PEDAGOGIQUES
-- ============================================================

CREATE TABLE activite_pedagogique (
    id_activite INT AUTO_INCREMENT PRIMARY KEY,

    type_activite ENUM('CREATION_RESSOURCE', 'MISE_A_JOUR_RESSOURCE') NOT NULL,
    niveau_complexite ENUM('NIVEAU_1', 'NIVEAU_2', 'NIVEAU_3') NOT NULL,

    nombre_heures DECIMAL(10,2) NOT NULL,
    nb_sequences INT NOT NULL DEFAULT 0,
    volume_horaire_calcule DECIMAL(10,2) NOT NULL DEFAULT 0,

    statut_validation ENUM('EN_ATTENTE', 'VALIDEE', 'REJETEE') NOT NULL DEFAULT 'EN_ATTENTE',
    date_saisie DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observation TEXT,

    id_enseignant INT NOT NULL,
    id_cours INT NOT NULL,
    id_ressource INT NULL,
    id_annee INT NOT NULL,
    id_parametre INT NOT NULL,
    id_saisi_par INT NOT NULL,

    CONSTRAINT chk_activite_nombre_heures
        CHECK (nombre_heures > 0),

    CONSTRAINT fk_activite_enseignant
        FOREIGN KEY (id_enseignant)
        REFERENCES enseignant(id_enseignant)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_activite_cours
        FOREIGN KEY (id_cours)
        REFERENCES cours(id_cours)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_activite_ressource
        FOREIGN KEY (id_ressource)
        REFERENCES ressource_pedagogique(id_ressource)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_activite_annee
        FOREIGN KEY (id_annee)
        REFERENCES annee_academique(id_annee)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_activite_parametre
        FOREIGN KEY (id_parametre)
        REFERENCES parametre_calcul(id_parametre)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_activite_saisi_par
        FOREIGN KEY (id_saisi_par)
        REFERENCES utilisateur(id_utilisateur)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 8. VALIDATION DES ACTIVITES
-- ============================================================

CREATE TABLE validation_activite (
    id_validation INT AUTO_INCREMENT PRIMARY KEY,
    date_validation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decision ENUM('VALIDEE', 'REJETEE') NOT NULL,
    commentaire TEXT,

    id_activite INT NOT NULL,
    id_validateur INT NOT NULL,

    CONSTRAINT uk_validation_activite
        UNIQUE (id_activite),

    CONSTRAINT fk_validation_activite
        FOREIGN KEY (id_activite)
        REFERENCES activite_pedagogique(id_activite)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_validation_validateur
        FOREIGN KEY (id_validateur)
        REFERENCES utilisateur(id_utilisateur)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 9. TAUX HORAIRES ET PAIEMENTS
-- ============================================================

CREATE TABLE taux_horaire (
    id_taux INT AUTO_INCREMENT PRIMARY KEY,
    categorie VARCHAR(100) NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    date_effet DATE NOT NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT uk_taux_horaire
        UNIQUE (categorie, date_effet),

    CONSTRAINT chk_taux_montant
        CHECK (montant >= 0)
) ENGINE=InnoDB;

CREATE TABLE paiement (
    id_paiement INT AUTO_INCREMENT PRIMARY KEY,

    volume_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    volume_complementaire DECIMAL(10,2) NOT NULL DEFAULT 0,
    volume_a_payer DECIMAL(10,2) NOT NULL DEFAULT 0,
    montant_total DECIMAL(12,2) NOT NULL DEFAULT 0,

    date_generation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut_paiement ENUM('GENERE', 'PAYE', 'ANNULE') NOT NULL DEFAULT 'GENERE',

    id_enseignant INT NOT NULL,
    id_annee INT NOT NULL,
    id_taux INT NOT NULL,
    id_genere_par INT NOT NULL,

    CONSTRAINT uk_paiement_enseignant_annee
        UNIQUE (id_enseignant, id_annee),

    CONSTRAINT fk_paiement_enseignant
        FOREIGN KEY (id_enseignant)
        REFERENCES enseignant(id_enseignant)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_paiement_annee
        FOREIGN KEY (id_annee)
        REFERENCES annee_academique(id_annee)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_paiement_taux
        FOREIGN KEY (id_taux)
        REFERENCES taux_horaire(id_taux)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_paiement_genere_par
        FOREIGN KEY (id_genere_par)
        REFERENCES utilisateur(id_utilisateur)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 10. INDEX
-- ============================================================

CREATE INDEX idx_utilisateur_role ON utilisateur(id_role);
CREATE INDEX idx_filiere_departement ON filiere(id_departement);
CREATE INDEX idx_enseignant_departement ON enseignant(id_departement);
CREATE INDEX idx_enseignant_grade ON enseignant(id_grade);
CREATE INDEX idx_enseignant_statut ON enseignant(statut);
CREATE INDEX idx_cours_filiere_filiere ON cours_filiere(id_filiere);
CREATE INDEX idx_ressource_cours ON ressource_pedagogique(id_cours);
CREATE INDEX idx_activite_enseignant ON activite_pedagogique(id_enseignant);
CREATE INDEX idx_activite_cours ON activite_pedagogique(id_cours);
CREATE INDEX idx_activite_annee ON activite_pedagogique(id_annee);
CREATE INDEX idx_activite_statut ON activite_pedagogique(statut_validation);
CREATE INDEX idx_validation_validateur ON validation_activite(id_validateur);
CREATE INDEX idx_paiement_enseignant ON paiement(id_enseignant);
CREATE INDEX idx_paiement_annee ON paiement(id_annee);

-- ============================================================
-- 11. TRIGGERS
-- ============================================================

DELIMITER $$

CREATE TRIGGER trg_enseignant_bi
BEFORE INSERT ON enseignant
FOR EACH ROW
BEGIN
    IF NEW.statut = 'PERMANENT' AND NEW.id_grade IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un enseignant permanent doit avoir un grade.';
    END IF;
END$$

CREATE TRIGGER trg_enseignant_bu
BEFORE UPDATE ON enseignant
FOR EACH ROW
BEGIN
    IF NEW.statut = 'PERMANENT' AND NEW.id_grade IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un enseignant permanent doit avoir un grade.';
    END IF;
END$$

CREATE TRIGGER trg_activite_bi
BEFORE INSERT ON activite_pedagogique
FOR EACH ROW
BEGIN
    DECLARE v_coefficient DECIMAL(10,3);

    SELECT coefficient
    INTO v_coefficient
    FROM parametre_calcul
    WHERE id_parametre = NEW.id_parametre
      AND type_activite = NEW.type_activite
      AND niveau_complexite = NEW.niveau_complexite
      AND actif = TRUE
    LIMIT 1;

    IF v_coefficient IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Paramètre de calcul invalide ou inactif.';
    END IF;

    SET NEW.nb_sequences = NEW.nombre_heures * 4;
    SET NEW.volume_horaire_calcule = NEW.nb_sequences * v_coefficient;
END$$

CREATE TRIGGER trg_activite_bu
BEFORE UPDATE ON activite_pedagogique
FOR EACH ROW
BEGIN
    DECLARE v_coefficient DECIMAL(10,3);

    SELECT coefficient
    INTO v_coefficient
    FROM parametre_calcul
    WHERE id_parametre = NEW.id_parametre
      AND type_activite = NEW.type_activite
      AND niveau_complexite = NEW.niveau_complexite
      AND actif = TRUE
    LIMIT 1;

    IF v_coefficient IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Paramètre de calcul invalide ou inactif.';
    END IF;

    SET NEW.nb_sequences = NEW.nombre_heures * 4;
    SET NEW.volume_horaire_calcule = NEW.nb_sequences * v_coefficient;
END$$

CREATE TRIGGER trg_validation_ai
AFTER INSERT ON validation_activite
FOR EACH ROW
BEGIN
    UPDATE activite_pedagogique
    SET statut_validation = NEW.decision
    WHERE id_activite = NEW.id_activite;
END$$

DELIMITER ;

-- ============================================================
-- 12. PROCEDURE DE GENERATION DES PAIEMENTS
-- ============================================================

DELIMITER $$

CREATE PROCEDURE sp_generer_paiement (
    IN p_id_enseignant INT,
    IN p_id_annee INT,
    IN p_id_taux INT,
    IN p_id_genere_par INT
)
BEGIN
    DECLARE v_volume_total DECIMAL(10,2) DEFAULT 0;
    DECLARE v_charge_statutaire DECIMAL(10,2) DEFAULT 0;
    DECLARE v_statut VARCHAR(20);
    DECLARE v_volume_complementaire DECIMAL(10,2) DEFAULT 0;
    DECLARE v_volume_a_payer DECIMAL(10,2) DEFAULT 0;
    DECLARE v_taux DECIMAL(12,2) DEFAULT 0;
    DECLARE v_montant_total DECIMAL(12,2) DEFAULT 0;

    SELECT e.statut, COALESCE(g.charge_statutaire, 0)
    INTO v_statut, v_charge_statutaire
    FROM enseignant e
    LEFT JOIN grade g ON g.id_grade = e.id_grade
    WHERE e.id_enseignant = p_id_enseignant;

    SELECT COALESCE(SUM(volume_horaire_calcule), 0)
    INTO v_volume_total
    FROM activite_pedagogique
    WHERE id_enseignant = p_id_enseignant
      AND id_annee = p_id_annee
      AND statut_validation = 'VALIDEE';

    SELECT montant
    INTO v_taux
    FROM taux_horaire
    WHERE id_taux = p_id_taux
      AND actif = TRUE;

    IF v_statut = 'VACATAIRE' THEN
        SET v_volume_complementaire = 0;
        SET v_volume_a_payer = v_volume_total;
    ELSE
        SET v_volume_complementaire = GREATEST(0, v_volume_total - v_charge_statutaire);
        SET v_volume_a_payer = v_volume_complementaire;
    END IF;

    SET v_montant_total = v_volume_a_payer * v_taux;

    INSERT INTO paiement (
        volume_total,
        volume_complementaire,
        volume_a_payer,
        montant_total,
        id_enseignant,
        id_annee,
        id_taux,
        id_genere_par
    )
    VALUES (
        v_volume_total,
        v_volume_complementaire,
        v_volume_a_payer,
        v_montant_total,
        p_id_enseignant,
        p_id_annee,
        p_id_taux,
        p_id_genere_par
    )
    ON DUPLICATE KEY UPDATE
        volume_total = VALUES(volume_total),
        volume_complementaire = VALUES(volume_complementaire),
        volume_a_payer = VALUES(volume_a_payer),
        montant_total = VALUES(montant_total),
        id_taux = VALUES(id_taux),
        id_genere_par = VALUES(id_genere_par),
        date_generation = CURRENT_TIMESTAMP,
        statut_paiement = 'GENERE';
END$$

DELIMITER ;

-- ============================================================
-- 13. VUES POUR TABLEAUX DE BORD
-- ============================================================

CREATE VIEW vue_cours_filieres AS
SELECT
    c.id_cours,
    c.code_cours,
    c.intitule_cours,
    c.nombre_heures,
    c.nombre_credits,
    cf.niveau,
    cf.semestre,
    f.nom_filiere,
    d.nom_departement
FROM cours c
JOIN cours_filiere cf ON cf.id_cours = c.id_cours
JOIN filiere f ON f.id_filiere = cf.id_filiere
JOIN departement d ON d.id_departement = f.id_departement;

CREATE VIEW vue_volume_par_enseignant AS
SELECT
    e.id_enseignant,
    e.matricule,
    CONCAT(e.nom, ' ', e.prenoms) AS enseignant,
    e.statut,
    d.nom_departement,
    a.id_annee,
    a.libelle_annee,
    COALESCE(SUM(ap.volume_horaire_calcule), 0) AS volume_total
FROM enseignant e
JOIN departement d ON d.id_departement = e.id_departement
CROSS JOIN annee_academique a
LEFT JOIN activite_pedagogique ap
    ON ap.id_enseignant = e.id_enseignant
    AND ap.id_annee = a.id_annee
    AND ap.statut_validation = 'VALIDEE'
GROUP BY
    e.id_enseignant,
    e.matricule,
    e.nom,
    e.prenoms,
    e.statut,
    d.nom_departement,
    a.id_annee,
    a.libelle_annee;

CREATE VIEW vue_enseignants_depasse_charge AS
SELECT
    e.id_enseignant,
    CONCAT(e.nom, ' ', e.prenoms) AS enseignant,
    d.nom_departement,
    g.libelle_grade,
    g.charge_statutaire,
    a.libelle_annee,
    COALESCE(SUM(ap.volume_horaire_calcule), 0) AS volume_total,
    GREATEST(
        0,
        COALESCE(SUM(ap.volume_horaire_calcule), 0) - g.charge_statutaire
    ) AS heures_complementaires
FROM enseignant e
JOIN grade g ON g.id_grade = e.id_grade
JOIN departement d ON d.id_departement = e.id_departement
JOIN activite_pedagogique ap ON ap.id_enseignant = e.id_enseignant
JOIN annee_academique a ON a.id_annee = ap.id_annee
WHERE e.statut = 'PERMANENT'
  AND ap.statut_validation = 'VALIDEE'
GROUP BY
    e.id_enseignant,
    e.nom,
    e.prenoms,
    d.nom_departement,
    g.libelle_grade,
    g.charge_statutaire,
    a.libelle_annee
HAVING volume_total > g.charge_statutaire;

CREATE VIEW vue_statistiques_globales AS
SELECT
    COUNT(DISTINCT e.id_enseignant) AS total_enseignants,
    COUNT(DISTINCT c.id_cours) AS total_cours,
    COUNT(DISTINCT r.id_ressource) AS total_ressources,
    COUNT(DISTINCT ap.id_activite) AS total_activites,
    COALESCE(SUM(
        CASE 
            WHEN ap.statut_validation = 'VALIDEE'
            THEN ap.volume_horaire_calcule
            ELSE 0
        END
    ), 0) AS volume_horaire_valide
FROM enseignant e
LEFT JOIN cours c ON c.actif = TRUE
LEFT JOIN ressource_pedagogique r ON r.actif = TRUE
LEFT JOIN activite_pedagogique ap ON ap.id_enseignant = e.id_enseignant;

-- ============================================================
-- 14. DONNEES INITIALES
-- ============================================================

INSERT INTO role (libelle_role) VALUES
('ADMINISTRATEUR'),
('SECRETAIRE_PRINCIPAL'),
('ENSEIGNANT');

INSERT INTO utilisateur (login, mot_de_passe_hash, id_role) VALUES
('admin', '$2y$10$REMPLACER_PAR_HASH_ADMIN', 1),
('sec_principal', '$2y$10$REMPLACER_PAR_HASH_SECRETAIRE', 2);

INSERT INTO departement (nom_departement, description) VALUES
('Sciences et Technologies', 'Département actuel de l’UVCI.');

INSERT INTO filiere (nom_filiere, description, id_departement) VALUES
('Informatique et Sciences du Numérique', 'Filière actuelle de l’UVCI.', 1);

INSERT INTO annee_academique (
    libelle_annee,
    date_debut,
    date_fin,
    est_active
) VALUES
('2025-2026', '2025-10-01', '2026-09-30', TRUE);

INSERT INTO grade (libelle_grade, charge_statutaire) VALUES
('Assistant', 240),
('Maître-Assistant', 240),
('Maître de Conférences', 180),
('Professeur Titulaire', 90);

INSERT INTO taux_horaire (categorie, montant, date_effet, actif) VALUES
('VACATAIRE', 5000, '2025-10-01', TRUE),
('ASSISTANT', 5000, '2025-10-01', TRUE),
('MAITRE_ASSISTANT', 6000, '2025-10-01', TRUE),
('MAITRE_CONFERENCES', 7000, '2025-10-01', TRUE),
('PROFESSEUR_TITULAIRE', 8000, '2025-10-01', TRUE);

INSERT INTO parametre_calcul (
    type_activite,
    niveau_complexite,
    coefficient
) VALUES
('CREATION_RESSOURCE', 'NIVEAU_1', 0.400),
('CREATION_RESSOURCE', 'NIVEAU_2', 0.750),
('CREATION_RESSOURCE', 'NIVEAU_3', 1.500),
('MISE_A_JOUR_RESSOURCE', 'NIVEAU_1', 0.200),
('MISE_A_JOUR_RESSOURCE', 'NIVEAU_2', 0.375),
('MISE_A_JOUR_RESSOURCE', 'NIVEAU_3', 0.750);

INSERT INTO cours (
    code_cours,
    intitule_cours,
    nombre_heures,
    nombre_credits
) VALUES
('INF-L1-ALGO', 'Algorithmique de base', 20, 2),
('INF-L1-BD', 'Introduction aux bases de données', 20, 2),
('INF-L2-WEB', 'Développement Web', 20, 2),
('INF-L3-BIGDATA', 'Introduction au Big Data', 20, 2);

INSERT INTO cours_filiere (
    id_cours,
    id_filiere,
    niveau,
    semestre
) VALUES
(1, 1, 'L1', 'S1'),
(2, 1, 'L1', 'S2'),
(3, 1, 'L2', 'S4'),
(4, 1, 'L3', 'S6');

INSERT INTO ressource_pedagogique (
    titre_ressource,
    type_ressource,
    description,
    chemin_fichier,
    id_cours
) VALUES
('Support de cours Algorithmique', 'DOCUMENT', 'Document pédagogique principal du cours.', NULL, 1),
('Quiz introductif Algorithmique', 'QUIZ', 'Quiz de vérification des prérequis.', NULL, 1),
('Support de cours Bases de données', 'DOCUMENT', 'Introduction aux concepts fondamentaux.', NULL, 2);

INSERT INTO utilisateur (login, mot_de_passe_hash, id_role) VALUES
('enseignant_test', '$2y$10$REMPLACER_PAR_HASH_ENSEIGNANT', 3);

INSERT INTO enseignant (
    matricule,
    nom,
    prenoms,
    email,
    telephone,
    statut,
    id_departement,
    id_grade,
    id_utilisateur
) VALUES
(
    'ENS001',
    'KOUASSI',
    'Jean',
    'kouassi.jean@uvci.edu.ci',
    '0700000000',
    'PERMANENT',
    1,
    1,
    3
);

INSERT INTO activite_pedagogique (
    type_activite,
    niveau_complexite,
    nombre_heures,
    id_enseignant,
    id_cours,
    id_ressource,
    id_annee,
    id_parametre,
    id_saisi_par,
    observation
) VALUES (
    'CREATION_RESSOURCE',
    'NIVEAU_1',
    20,
    1,
    1,
    1,
    1,
    1,
    2,
    'Création du support de cours Algorithmique'
);

INSERT INTO validation_activite (
    decision,
    commentaire,
    id_activite,
    id_validateur
) VALUES (
    'VALIDEE',
    'Activité conforme.',
    1,
    2
);

CALL sp_generer_paiement(1, 1, 2, 2);

-- ============================================================
-- 15. TESTS DE VERIFICATION
-- ============================================================

SHOW TABLES;

SELECT * FROM departement;
SELECT * FROM filiere;
SELECT * FROM cours;
SELECT * FROM cours_filiere;
SELECT * FROM vue_cours_filieres;
SELECT * FROM ressource_pedagogique;
SELECT * FROM activite_pedagogique;
SELECT * FROM validation_activite;
SELECT * FROM paiement;
SELECT * FROM vue_volume_par_enseignant;
SELECT * FROM vue_enseignants_depasse_charge;
SELECT * FROM vue_statistiques_globales;