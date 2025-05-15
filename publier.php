<?php
session_start();
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// Redirection si l'utilisateur n'est pas connecté
if (!estConnecte()) {
    header('Location: connexion.php');
    exit();
}

$erreurs = [];
$succes = false;
$formData = [
    'titre' => '',
    'adresse' => '',
    'ville' => '',
    'code_postal' => '',
    'description' => '',
    'prix' => '',
    'nb_places' => '1',
    'type_logement' => 'entier',
    'date_debut' => '',
    'date_fin' => ''
];

// Traitement du formulaire de publication
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $formData = [
        'titre' => trim($_POST['titre'] ?? ''),
        'adresse' => trim($_POST['adresse'] ?? ''),
        'ville' => trim($_POST['ville'] ?? ''),
        'code_postal' => trim($_POST['code_postal'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'prix' => floatval($_POST['prix'] ?? 0),
        'nb_places' => intval($_POST['nb_places'] ?? 1),
        'type_logement' => $_POST['type_logement'] ?? 'entier',
        'date_debut' => $_POST['date_debut'] ?? '',
        'date_fin' => $_POST['date_fin'] ?? '',
        'latitude' => floatval($_POST['latitude'] ?? 0),
        'longitude' => floatval($_POST['longitude'] ?? 0)
    ];
    
    // Validation des données
    if (empty($formData['titre'])) {
        $erreurs['titre'] = 'Le titre est obligatoire.';
    }
    
    if (empty($formData['adresse'])) {
        $erreurs['adresse'] = 'L\'adresse est obligatoire.';
    }
    
    if (empty($formData['ville'])) {
        $erreurs['ville'] = 'La ville est obligatoire.';
    }
    
    if (empty($formData['code_postal'])) {
        $erreurs['code_postal'] = 'Le code postal est obligatoire.';
    } elseif (!preg_match('/^[0-9]{5}$/', $formData['code_postal'])) {
        $erreurs['code_postal'] = 'Format de code postal invalide.';
    }
    
    if (empty($formData['prix']) || $formData['prix'] <= 0) {
        $erreurs['prix'] = 'Le prix doit être supérieur à 0.';
    }
    
    if (empty($formData['date_debut'])) {
        $erreurs['date_debut'] = 'La date de début est obligatoire.';
    }
    
    if (empty($formData['date_fin'])) {
        $erreurs['date_fin'] = 'La date de fin est obligatoire.';
    } elseif ($formData['date_debut'] >= $formData['date_fin']) {
        $erreurs['date_fin'] = 'La date de fin doit être postérieure à la date de début.';
    }
    
    // Vérification que les coordonnées GPS sont renseignées
    if (empty($formData['latitude']) || empty($formData['longitude'])) {
        // Si pas de coordonnées, utiliser l'API de géocodage pour les obtenir
        if (empty($erreurs['adresse']) && empty($erreurs['ville']) && empty($erreurs['code_postal'])) {
            $adresseComplete = $formData['adresse'] . ', ' . $formData['code_postal'] . ' ' . $formData['ville'] . ', France';
            
            try {
                // Appel à l'API de géocodage (à implémenter)
                $coordonnees = geocoderAdresse($adresseComplete);
                
                if ($coordonnees) {
                    $formData['latitude'] = $coordonnees['lat'];
                    $formData['longitude'] = $coordonnees['lng'];
                } else {
                    $erreurs['adresse'] = 'Impossible de géolocaliser cette adresse.';
                }
            } catch (Exception $e) {
                $erreurs['adresse'] = 'Erreur lors de la géolocalisation : ' . $e->getMessage();
            }
        }
    }
    
    // Si aucune erreur, procéder à la publication
    if (empty($erreurs)) {
        try {
            // Connexion à la base de données
            $pdo = connecterBDD();
            
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Insertion du logement dans la base de données
            $requete = $pdo->prepare('
                INSERT INTO logements (
                    id_proprietaire,
