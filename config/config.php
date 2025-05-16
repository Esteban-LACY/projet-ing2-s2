<?php

// DÉFINIR LA CONSTANTE AVANT DE L'UTILISER DANS LES ini_set()
if (!defined('MODE_DEVELOPPEMENT')) {
    define('MODE_DEVELOPPEMENT', true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration des erreurs
if (MODE_DEVELOPPEMENT) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

    // Log des erreurs en production
    ini_set('log_errors', 1);
    ini_set('error_log', CHEMIN_RACINE . '/logs/php-errors.log');
}

/**
 * Configuration générale du site
 * 
 * Ce fichier contient les paramètres généraux de l'application OmnesBnB
 * 
 * @author OmnesBnB
 */

// Configuration de l'environnement
if (!defined('MODE_DEVELOPPEMENT')) {
    define('MODE_DEVELOPPEMENT', true);
}

// Chemin racine avec realpath pour normalisation
define('CHEMIN_RACINE', realpath(dirname(__DIR__)));

// Chemins des répertoires principaux
define('CHEMIN_CONFIG', CHEMIN_RACINE . '/config');
define('CHEMIN_VUES', CHEMIN_RACINE . '/views');
define('CHEMIN_MODELES', CHEMIN_RACINE . '/models');
define('CHEMIN_CONTROLEURS', CHEMIN_RACINE . '/controllers');
define('CHEMIN_INCLUDES', CHEMIN_RACINE . '/includes');
define('CHEMIN_SERVICES', CHEMIN_RACINE . '/services');
define('CHEMIN_HELPERS', CHEMIN_RACINE . '/helpers');
define('CHEMIN_ASSETS', CHEMIN_RACINE . '/assets');
define('CHEMIN_UPLOADS', CHEMIN_RACINE . '/uploads');

// Configuration des dossiers d'uploads
define('CHEMIN_UPLOADS_PROFILS', CHEMIN_UPLOADS . '/profils');
define('CHEMIN_UPLOADS_LOGEMENTS', CHEMIN_UPLOADS . '/logements');

// Détection automatique de l'URL de base
if (!defined('URL_SITE')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $scriptPath = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
    $basePath = $scriptPath === '/' ? '' : $scriptPath;
    define('URL_SITE', $protocol . $domainName . $basePath);
}

// Création des dossiers s'ils n'existent pas
$dossiers = [
    CHEMIN_UPLOADS,
    CHEMIN_UPLOADS_PROFILS,
    CHEMIN_UPLOADS_LOGEMENTS,
    CHEMIN_RACINE . '/logs'
];

foreach ($dossiers as $dossier) {
    if (!is_dir($dossier)) {
        mkdir($dossier, 0755, true);
    }
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

// Constantes pour les types de comptes
define('TYPE_COMPTE_ADMIN', 'admin');
define('TYPE_COMPTE_UTILISATEUR', 'utilisateur');

// Constantes pour les types de logements
define('TYPE_LOGEMENT_ENTIER', 'entier');
define('TYPE_LOGEMENT_COLLOCATION', 'collocation');
define('TYPE_LOGEMENT_LIBERE', 'libere');

// Constantes pour les statuts de réservation
define('STATUT_RESERVATION_EN_ATTENTE', 'en_attente');
define('STATUT_RESERVATION_ACCEPTEE', 'acceptee');
define('STATUT_RESERVATION_REFUSEE', 'refusee');
define('STATUT_RESERVATION_ANNULEE', 'annulee');
define('STATUT_RESERVATION_TERMINEE', 'terminee');

// Constantes pour les statuts de paiement
define('STATUT_PAIEMENT_EN_ATTENTE', 'en_attente');
define('STATUT_PAIEMENT_COMPLETE', 'complete');
define('STATUT_PAIEMENT_REMBOURSE', 'rembourse');
define('STATUT_PAIEMENT_ECHOUE', 'echoue');

// Limites de pagination par défaut
define('PAGINATION_LIMITE_DEFAUT', 10);
define('PAGINATION_LIMITE_MAX', 50);

// Charger les configurations supplémentaires
require_once CHEMIN_CONFIG . '/database.php';
require_once CHEMIN_CONFIG . '/stripe_config.php';
require_once CHEMIN_CONFIG . '/maps_config.php';

// Charger les utilitaires essentiels
require_once CHEMIN_INCLUDES . '/fonctions.php';

/**
 * Active le mode de débogage pour afficher des informations détaillées
 * 
 * @param mixed $var Variable à déboguer
 * @param bool $exit Arrête l'exécution après l'affichage
 * @return void
 */
function debug($var, $exit = true) {
    if (MODE_DEVELOPPEMENT) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        
        if ($exit) {
            exit;
        }
    }
}

/**
 * Génère un identifiant unique sécurisé
 * 
 * @param int $longueur Longueur de l'identifiant (défaut: 32)
 * @return string Identifiant unique
 */
function genererIdentifiantUnique($longueur = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($longueur / 2));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($longueur / 2));
    } else {
        // Fallback si les fonctions cryptographiques ne sont pas disponibles
        return md5(uniqid(mt_rand(), true));
    }
}
?>
