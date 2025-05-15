<?php
/**
 * Configuration générale du site
 * 
 * Ce fichier contient les paramètres généraux de l'application OmnesBnB
 * 
 * @author OmnesBnB
 */

// Configuration de l'environnement
define('MODE_DEVELOPPEMENT', true); // Mettre à false en production

// Configuration de l'application
define('NOM_SITE', 'OmnesBnB');
define('URL_SITE', 'http://localhost/omnesbnb'); // À changer en production

// Configuration des chemins
define('CHEMIN_RACINE', dirname(__DIR__));
define('CHEMIN_VUES', CHEMIN_RACINE . '/views');
define('CHEMIN_MODELES', CHEMIN_RACINE . '/models');
define('CHEMIN_CONTROLEURS', CHEMIN_RACINE . '/controllers');
define('CHEMIN_INCLUDES', CHEMIN_RACINE . '/includes');
define('CHEMIN_UPLOADS', CHEMIN_RACINE . '/uploads');
define('CHEMIN_UPLOADS_PROFILS', CHEMIN_UPLOADS . '/profils');
define('CHEMIN_UPLOADS_LOGEMENTS', CHEMIN_UPLOADS . '/logements');

// Configuration des uploads
define('TAILLE_MAX_UPLOAD', 2 * 1024 * 1024); // 2 MB
define('TYPES_IMAGES_AUTORISES', ['image/jpeg', 'image/png', 'image/gif']);

// Configuration des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (MODE_DEVELOPPEMENT === false) {
    ini_set('session.cookie_secure', 1);
}
session_start();

// Configuration des erreurs
if (MODE_DEVELOPPEMENT) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Configuration des emails
define('EMAIL_ADMINISTRATEUR', 'admin@omnesbnb.fr');
define('EMAIL_CONTACT', 'contact@omnesbnb.fr');
define('EMAIL_NOREPLY', 'noreply@omnesbnb.fr');

// Domaines email autorisés pour l'inscription
define('DOMAINES_EMAIL_AUTORISES', [
    'omnesintervenant.com',
    'ece.fr',
    'edu.ece.fr'
]);

// Fonctions utilitaires générales
require_once CHEMIN_INCLUDES . '/fonctions.php';

// Configuration de la base de données
require_once __DIR__ . '/database.php';

// Configuration de Stripe (paiements)
require_once __DIR__ . '/stripe_config.php';

// Configuration de Google Maps
require_once __DIR__ . '/maps_config.php';

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return boolean True si l'utilisateur est connecté, false sinon
 */
function estConnecte() {
    return isset($_SESSION['utilisateur_id']) && !empty($_SESSION['utilisateur_id']);
}

/**
 * Vérifie si l'utilisateur est administrateur
 * 
 * @return boolean True si l'utilisateur est administrateur, false sinon
 */
function estAdmin() {
    return estConnecte() && isset($_SESSION['est_admin']) && $_SESSION['est_admin'] === true;
}

/**
 * Redirige l'utilisateur vers une autre page
 * 
 * @param string $url URL de destination
 * @return void
 */
function rediriger($url) {
    header("Location: $url");
    exit();
}

/**
 * Nettoie une chaîne de caractères pour éviter les injections XSS
 * 
 * @param string $donnee Donnée à nettoyer
 * @return string Donnée nettoyée
 */
function nettoyer($donnee) {
    $donnee = trim($donnee);
    $donnee = stripslashes($donnee);
    $donnee = htmlspecialchars($donnee, ENT_QUOTES, 'UTF-8');
    return $donnee;
}
?>
