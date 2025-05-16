<?php
/**
* Configuration générale du site
* 
* Ce fichier contient les paramètres centralisés de l'application OmnesBnB
* Toutes les constantes et paramètres globaux doivent être définis ici.
* 
* @author OmnesBnB
*/

// Démarrage de la session avec des paramètres sécurisés
session_start([
   'cookie_httponly' => 1,
   'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
   'use_only_cookies' => 1,
   'cookie_samesite' => 'Lax'
]);

// Configuration de l'environnement
if (!defined('MODE_DEVELOPPEMENT')) {
    define('MODE_DEVELOPPEMENT', true);
}

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
$cheminServices = CHEMIN_RACINE . '/services';
$cheminHelpers = CHEMIN_RACINE . '/helpers';

// Définir les chemins en vérifiant l'existence des dossiers
define('CHEMIN_VUES', is_dir($cheminVues) ? $cheminVues : CHEMIN_RACINE . '/views');
define('CHEMIN_MODELES', is_dir($cheminModeles) ? $cheminModeles : CHEMIN_RACINE . '/models');
define('CHEMIN_CONTROLEURS', is_dir($cheminControleurs) ? $cheminControleurs : CHEMIN_RACINE . '/controllers');
define('CHEMIN_INCLUDES', is_dir($cheminIncludes) ? $cheminIncludes : CHEMIN_RACINE . '/includes');
define('CHEMIN_UPLOADS', is_dir($cheminUploads) ? $cheminUploads : CHEMIN_RACINE . '/uploads');
define('CHEMIN_SERVICES', is_dir($cheminServices) ? $cheminServices : CHEMIN_RACINE . '/services');
define('CHEMIN_HELPERS', is_dir($cheminHelpers) ? $cheminHelpers : CHEMIN_RACINE . '/helpers');

// Configuration des dossiers d'uploads
define('CHEMIN_UPLOADS_PROFILS', CHEMIN_UPLOADS . '/profils');
define('CHEMIN_UPLOADS_LOGEMENTS', CHEMIN_UPLOADS . '/logements');

// Créer les dossiers d'upload s'ils n'existent pas
$dossiers = [CHEMIN_UPLOADS, CHEMIN_UPLOADS_PROFILS, CHEMIN_UPLOADS_LOGEMENTS, 
            CHEMIN_SERVICES, CHEMIN_HELPERS];

foreach ($dossiers as $dossier) {
   if (!is_dir($dossier)) {
       mkdir($dossier, 0755, true);
   }
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

// Frais de service
define('FRAIS_SERVICE_POURCENTAGE', 10);

// Configuration de la base de données - Utiliser des variables d'environnement en production
define('DB_HOST', 'localhost');
define('DB_USER', 'omnesbnb_user');
define('DB_PASSWORD', 'password_securise'); // À changer pour la production
define('DB_NAME', 'omnesbnb_db');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

// Clés API (à remplacer par des variables d'environnement en production)
define('GOOGLE_MAPS_API_KEY', 'votre_cle_api_google_maps');
define('STRIPE_SECRET_KEY', 'sk_test_51XxXxXXxXxXxXxXxXxXxXxXx');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51XxXxXXxXxXxXxXxXxXxXxXx');
define('STRIPE_WEBHOOK_SECRET', 'whsec_XxXxXxXxXxXxXxXxXxXxXxXx');

// URL de redirection après paiement
define('STRIPE_SUCCESS_URL', URL_SITE . '/reservation/confirmation.php?status=success&session_id={CHECKOUT_SESSION_ID}');
define('STRIPE_CANCEL_URL', URL_SITE . '/reservation/confirmation.php?status=cancel&session_id={CHECKOUT_SESSION_ID}');

// Options par défaut pour la carte Google Maps
define('GOOGLE_MAPS_DEFAULT_LAT', 48.856614); // Latitude par défaut (Paris)
define('GOOGLE_MAPS_DEFAULT_LNG', 2.3522219); // Longitude par défaut (Paris)
define('GOOGLE_MAPS_DEFAULT_ZOOM', 13); // Niveau de zoom par défaut

// Charger les fichiers d'inclusion nécessaires
require_once CHEMIN_INCLUDES . '/security.php';
require_once CHEMIN_INCLUDES . '/database_manager.php';
require_once CHEMIN_INCLUDES . '/session_manager.php';
require_once CHEMIN_HELPERS . '/view_helpers.php';
?>
