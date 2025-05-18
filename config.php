<?php
// Configuration de connexion à la base de données
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root'); // À adapter selon votre configuration
define('DB_PORT', '8889'); // Pour MAMP. Supprimer cette ligne pour XAMPP ou WAMP
define('DB_NAME', 'omnesbnb_db');

try {
    // Création de la connexion PDO
    $conn = new PDO("mysql:host=".DB_SERVER.";port=".DB_PORT.";dbname=".DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Configurer le mode d'erreur PDO pour générer des exceptions
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Définir le jeu de caractères
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("ERREUR : Impossible de se connecter à la base de données. " . $e->getMessage());
}
?>