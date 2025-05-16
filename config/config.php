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

// Chemin racine avec realpath pour normalisation
define('CHEMIN_RACINE', realpath(dirname(__DIR__)));

// Détection automatique de l'URL de base
if (!defined('URL_SITE')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = $scriptPath === '/' ? '' : $scriptPath;
    define('URL_SITE', $protocol . $domainName . $basePath);
}

// Configuration des chemins avec vérification
$cheminVues = CHEMIN_RACINE . '/views';
$cheminModeles = CHEMIN_RACINE . '/models';
$cheminControleurs = CHEMIN_RACINE . '/controllers';
$cheminIncludes = CHEMIN_RACINE . '/includes';
$cheminUploads = CHEMIN_RACINE . '/uploads';

// Définir les chemins en vérifiant l'existence des dossiers
define('CHEMIN_VUES', is_dir($cheminVues) ? $cheminVues : CHEMIN_RACINE);
define('CHEMIN_MODELES', is_dir($cheminModeles) ? $cheminModeles : CHEMIN_RACINE . '/models');
define('CHEMIN_CONTROLEURS', is_dir($cheminControleurs) ? $cheminControleurs : CHEMIN_RACINE . '/controllers');
define('CHEMIN_INCLUDES', is_dir($cheminIncludes) ? $cheminIncludes : CHEMIN_RACINE . '/includes');
define('CHEMIN_UPLOADS', is_dir($cheminUploads) ? $cheminUploads : CHEMIN_RACINE . '/uploads');

// Configuration des dossiers d'uploads
define('CHEMIN_UPLOADS_PROFILS', CHEMIN_UPLOADS . '/profils');
define('CHEMIN_UPLOADS_LOGEMENTS', CHEMIN_UPLOADS . '/logements');

// Créer les dossiers d'upload s'ils n'existent pas
if (!is_dir(CHEMIN_UPLOADS)) {
    mkdir(CHEMIN_UPLOADS, 0755, true);
}
if (!is_dir(CHEMIN_UPLOADS_PROFILS)) {
    mkdir(CHEMIN_UPLOADS_PROFILS, 0755, true);
}
if (!is_dir(CHEMIN_UPLOADS_LOGEMENTS)) {
    mkdir(CHEMIN_UPLOADS_LOGEMENTS, 0755, true);
}

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
