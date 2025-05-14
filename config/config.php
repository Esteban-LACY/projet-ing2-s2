<?php
/**
 * Configuration générale de l'application
 */

// Informations de base
define('APP_NAME', 'OmnesBnB');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/omnesbnb'); // À modifier pour la production

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration des chemins
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONTROLLERS_PATH', ROOT_PATH . 'controllers/');
define('MODELS_PATH', ROOT_PATH . 'models/');
define('VIEWS_PATH', ROOT_PATH . 'views/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');

// Configuration de l'application
define('APP_DEBUG', true); // À mettre à false en production
define('APP_LANGUE', 'fr');
define('APP_DEVISE', 'EUR');
define('APP_EMAIL', 'contact@omnesbnb.fr');

// Configuration de l'upload de fichiers
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 Mo
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Configuration des utilisateurs
define('EMAIL_VERIFICATION', true);
define('PASSWORD_MIN_LENGTH', 8);

// Configuration des emails acceptés
define('EMAILS_AUTORISES', [
    'omnesintervenant.com',
    'ece.fr',
    'edu.ece.fr',
]);

// Inclusion d'autres fichiers de configuration
require_once 'database.php';
require_once 'email_config.php';

// Configuration des apis externes
require_once 'maps_config.php';
require_once 'stripe_config.php';

// Démarrage de la session
session_start();

// Fonctions d'erreur et de débogage
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    /**
     * Affiche les informations de débogage
     * @param mixed $var Variable à déboguer
     * @param bool $die Arrêter l'exécution après l'affichage
     */
    function debug($var, $die = false) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    
    function debug($var, $die = false) {
        // Ne rien faire en production
    }
}
?>
