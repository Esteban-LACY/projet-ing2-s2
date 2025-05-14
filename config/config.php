<?php
// Fichier de configuration principale
define('APP_NAME', 'OmnesBnB');
define('APP_URL', 'http://localhost/omnesbnb'); // À modifier lors de la mise en production
define('APP_EMAIL', 'contact@omnesbnb.fr');
define('APP_VERSION', '1.0.0');

// Configuration des chemins
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONTROLLERS_PATH', ROOT_PATH . 'controllers/');
define('MODELS_PATH', ROOT_PATH . 'models/');
define('VIEWS_PATH', ROOT_PATH . 'views/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');

// Configuration des emails
define('EMAIL_VERIFICATION', true);
define('EMAIL_FROM', 'noreply@omnesbnb.fr');
define('EMAIL_REPLY_TO', 'contact@omnesbnb.fr');

// Configuration des domaines email autorisés
define('EMAILS_AUTORISES', [
    'omnesintervenant.com',
    'ece.fr',
    'edu.ece.fr',
]);

// Configuration de l'upload de fichiers
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 Mo
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Configuration de session
session_start();
?>
