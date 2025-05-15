# OmnesBnB

Plateforme de logements pour les étudiants et le personnel d'Omnes.

## Description

OmnesBnB est une application web permettant aux étudiants et au personnel d'Omnes de proposer leur logement à la location ou à la collocation, ou d'annoncer quand ils libèrent un logement. Les utilisateurs peuvent également rechercher et réserver des logements disponibles.

## Fonctionnalités

- Authentification et gestion de profil utilisateur
- Publication de logements (entier, collocation, libération)
- Recherche de logements avec filtres
- Système de réservation et paiement intégré (Stripe)
- Géolocalisation des logements (Google Maps)
- Interface administrateur pour la gestion du site

## Technologies utilisées

- HTML5, CSS3 (Tailwind CSS)
- JavaScript, jQuery
- PHP 8.x
- MySQL
- API Stripe pour les paiements
- API Google Maps pour la géolocalisation

## Installation

1. Cloner le dépôt : `git clone https://github.com/votre-utilisateur/omnesbnb.git`
2. Configurer la base de données dans `config/database.php`
3. Configurer les API keys dans `config/config.php`
4. Importer la structure de la base de données avec le fichier SQL fourni
5. Déployer sur un serveur web avec PHP 8.x et MySQL

## Structure du projet
