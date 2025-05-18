-- Script d'initialisation de la base de données OmnesBnB
-- Auteur: Administrateur OmnesBnB
-- Date de création: 18/05/2025

-- Suppression des tables existantes (si elles existent)
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS cookies_auth;
DROP TABLE IF EXISTS photos;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS logements;
DROP TABLE IF EXISTS users;

-- Table des utilisateurs
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       prenom VARCHAR(50) NOT NULL,
                       nom VARCHAR(50) NOT NULL,
                       email VARCHAR(100) NOT NULL UNIQUE,
                       password VARCHAR(255) NOT NULL,
                       telephone VARCHAR(20) NOT NULL,
                       photo VARCHAR(255),
                       statut ENUM('Étudiant(e)', 'Personnel Omnes', 'Professeur') NOT NULL,
                       campus ENUM('Paris', 'Lyon', 'Bordeaux') NOT NULL,
                       description TEXT,
                       is_admin BOOLEAN DEFAULT FALSE,
                       active BOOLEAN DEFAULT FALSE,
                       verification_token VARCHAR(64),
                       reset_token VARCHAR(64),
                       reset_token_expiry DATETIME,
                       date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                       derniere_connexion DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des logements
CREATE TABLE logements (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           user_id INT NOT NULL,
                           titre VARCHAR(100) NOT NULL,
                           type ENUM('studio', 'appartement', 'chambre', 'colocation', 'autre') NOT NULL,
                           adresse VARCHAR(255) NOT NULL,
                           prix_nuit DECIMAL(10, 2) NOT NULL,
                           prix_semaine DECIMAL(10, 2) NOT NULL,
                           prix_mois DECIMAL(10, 2) NOT NULL,
                           prix_annee DECIMAL(10, 2) NOT NULL,
                           caution DECIMAL(10, 2) NOT NULL,
                           description TEXT,
                           date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des photos de logements
CREATE TABLE photos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        logement_id INT NOT NULL,
                        photo_url VARCHAR(255) NOT NULL,
                        is_main BOOLEAN NOT NULL DEFAULT FALSE,
                        date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (logement_id) REFERENCES logements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des réservations
CREATE TABLE reservations (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              logement_id INT NOT NULL,
                              user_id INT NOT NULL,
                              date_arrivee DATE NOT NULL,
                              date_depart DATE NOT NULL,
                              nb_personnes INT NOT NULL,
                              prix_total DECIMAL(10, 2) NOT NULL,
                              statut ENUM('en attente', 'acceptée', 'refusée', 'annulée') NOT NULL DEFAULT 'en attente',
                              date_reservation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              FOREIGN KEY (logement_id) REFERENCES logements(id) ON DELETE CASCADE,
                              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour la fonctionnalité "Se souvenir de moi"
CREATE TABLE cookies_auth (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              id_utilisateur INT NOT NULL,
                              token VARCHAR(255) NOT NULL,
                              date_expiration DATETIME NOT NULL,
                              FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour gérer les tentatives de connexion (anti brute-force)
CREATE TABLE login_attempts (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                email VARCHAR(100) NOT NULL,
                                time INT NOT NULL,
                                success BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création d'un compte administrateur (mot de passe: Admin123!)
INSERT INTO users (prenom, nom, email, password, telephone, statut, campus, is_admin, active) VALUES
    ('Admin', 'OmnesBnB', 'admin@omnesbnb.fr', '$2y$10$UYxKXmhMQrT/7P8s6F/XMOeT0g.s3ZlKx3zrPYz7aA/zeHq.L4G7C', '06.12.34.56.78', 'Personnel Omnes', 'Paris', TRUE, TRUE);

-- Création de quelques utilisateurs de test (mot de passe: Password123!)
INSERT INTO users (prenom, nom, email, password, telephone, statut, campus, description, active) VALUES
                                                                                                     ('Marie', 'Dupont', 'marie.dupont@edu.ece.fr', '$2y$10$CdfEyiJjwOJTZwLsp9BLPeM7xJ36JO/VtDLFMHgBq0dLA2zJq1Ufa', '06.23.45.67.89', 'Étudiant(e)', 'Paris', 'Étudiante en 3ème année d''ingénierie à l''ECE Paris. Je recherche un logement calme pour mes études.', TRUE),
                                                                                                     ('Thomas', 'Martin', 'thomas.martin@edu.ece.fr', '$2y$10$CdfEyiJjwOJTZwLsp9BLPeM7xJ36JO/VtDLFMHgBq0dLA2zJq1Ufa', '06.34.56.78.90', 'Étudiant(e)', 'Lyon', 'Étudiant en 2ème année à l''ECE Lyon, passionné de sport et de musique.', TRUE),
                                                                                                     ('Sophie', 'Lefebvre', 'sophie.lefebvre@ece.fr', '$2y$10$CdfEyiJjwOJTZwLsp9BLPeM7xJ36JO/VtDLFMHgBq0dLA2zJq1Ufa', '06.45.67.89.01', 'Professeur', 'Paris', 'Professeure d''informatique à l''ECE Paris depuis 5 ans.', TRUE),
                                                                                                     ('Lucas', 'Dubois', 'lucas.dubois@edu.ece.fr', '$2y$10$CdfEyiJjwOJTZwLsp9BLPeM7xJ36JO/VtDLFMHgBq0dLA2zJq1Ufa', '06.56.78.90.12', 'Étudiant(e)', 'Bordeaux', 'Étudiant en dernière année à l''ECE Bordeaux, spécialisé en IoT.', TRUE);

-- Création de quelques logements de test
INSERT INTO logements (user_id, titre, type, adresse, prix_nuit, prix_semaine, prix_mois, prix_annee, caution, description) VALUES
                                                                                                                                (2, 'Studio Paris 15e', 'studio', '58 Rue de Vaugirard, 75015 Paris', 50, 300, 900, 10500, 900, 'Studio entièrement rénové situé à seulement 10 minutes à pied du campus ECE Paris. Idéal pour les étudiants, cet espace lumineux dispose de tout le confort nécessaire pour vos études : coin bureau spacieux, connexion internet haut débit, cuisine équipée et salle de bain moderne.'),
                                                                                                                                (3, 'Chambre dans colocation Lyon 7e', 'chambre', '23 Avenue Jean Jaurès, 69007 Lyon', 35, 230, 700, 8400, 700, 'Belle chambre dans un appartement partagé avec 2 autres étudiants. L''appartement est situé à 5 minutes du campus ECE Lyon. La chambre est meublée avec un lit simple, un bureau et une armoire.'),
                                                                                                                                (3, 'Appartement T2 Bordeaux', 'appartement', '45 Cours de la Marne, 33800 Bordeaux', 65, 400, 1200, 13000, 1200, 'Magnifique appartement T2 de 45m² situé en plein centre de Bordeaux, à 10 minutes à pied du campus ECE. Entièrement meublé et équipé, il comprend une chambre séparée, un salon lumineux, une cuisine équipée et une salle de bain moderne.'),
                                                                                                                                (4, 'Chambre dans maison partagée', 'chambre', '12 Rue des Fleurs, 75020 Paris', 40, 250, 750, 8000, 750, 'Chambre dans une maison partagée avec jardin, située dans un quartier calme de Paris. La maison est habitée par des étudiants et jeunes professionnels. Accès à toutes les parties communes : cuisine équipée, salon, salle à manger, 2 salles de bain et jardin.');

-- Création des photos pour les logements
INSERT INTO photos (logement_id, photo_url, is_main) VALUES
                                                         (1, 'uploads/logements/studio_paris_1.jpg', TRUE),
                                                         (1, 'uploads/logements/studio_paris_2.jpg', FALSE),
                                                         (1, 'uploads/logements/studio_paris_3.jpg', FALSE),
                                                         (2, 'uploads/logements/chambre_lyon_1.jpg', TRUE),
                                                         (2, 'uploads/logements/chambre_lyon_2.jpg', FALSE),
                                                         (3, 'uploads/logements/appartement_bordeaux_1.jpg', TRUE),
                                                         (3, 'uploads/logements/appartement_bordeaux_2.jpg', FALSE),
                                                         (3, 'uploads/logements/appartement_bordeaux_3.jpg', FALSE),
                                                         (4, 'uploads/logements/chambre_paris_1.jpg', TRUE);

-- Création de quelques réservations de test
INSERT INTO reservations (logement_id, user_id, date_arrivee, date_depart, nb_personnes, prix_total, statut) VALUES
                                                                                                                 (1, 4, '2025-06-01', '2025-07-01', 1, 900, 'acceptée'),
                                                                                                                 (2, 2, '2025-06-15', '2025-06-22', 1, 230, 'en attente'),
                                                                                                                 (3, 2, '2025-07-01', '2025-08-01', 2, 1200, 'refusée');

-- Création d'un index pour améliorer les performances
CREATE INDEX idx_logements_user_id ON logements(user_id);
CREATE INDEX idx_reservations_logement_id ON reservations(logement_id);
CREATE INDEX idx_reservations_user_id ON reservations(user_id);
CREATE INDEX idx_photos_logement_id ON photos(logement_id);
CREATE INDEX idx_login_attempts_email ON login_attempts(email);
CREATE INDEX idx_cookies_auth_token ON cookies_auth(token);