<?php
/**
 * Page d'accueil OmnesBnB
 * Point d'entrée principal de l'application
 */

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion des fichiers de configuration et fonctions
require_once 'config/config.php';
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// Initialisation des variables
$logementsRecents = [];
$message_erreur = null;

try {
    // Tentative de connexion à la base de données
    $pdo = getConnexionBD();

    // Vérifier si la connexion est établie
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données. Veuillez contacter l'administrateur.");
    }

    // Récupérer les logements récents (limités à 5)
    $requete = $pdo->prepare('
        SELECT l.*, (
            SELECT url FROM photos_logement 
            WHERE id_logement = l.id AND est_principale = 1 
            LIMIT 1
        ) AS photo_principale 
        FROM logements l 
        ORDER BY l.date_creation DESC 
        LIMIT 5
    ');
    $requete->execute();
    $logementsRecents = $requete->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log de l'erreur
    journaliser('Erreur de base de données dans index.php : ' . $e->getMessage(), 'ERROR');
    $message_erreur = MODE_DEVELOPPEMENT ?
        'Erreur de connexion à la base de données: ' . $e->getMessage() :
        'Une erreur de connexion est survenue. Veuillez réessayer plus tard.';
} catch (Exception $e) {
    journaliser('Erreur dans index.php : ' . $e->getMessage(), 'ERROR');
    $message_erreur = MODE_DEVELOPPEMENT ?
        'Erreur: ' . $e->getMessage() :
        'Une erreur est survenue. Veuillez réessayer plus tard.';
}

// Titre de la page
$titre = 'Accueil';

// Inclusion du header
include 'views/commun/header.php';

// Affichage de la vue principale de l'accueil (C'EST CECI QUI MANQUAIT !)
include 'views/accueil/index.php';

// Inclusion du footer
include 'views/commun/footer.php';
