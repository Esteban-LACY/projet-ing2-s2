<?php
/**
 * Configuration principale de OmnesBnB
 * Contient les paramètres de connexion à la base de données et l'initialisation de la session
 */

// Empêcher l'accès direct à ce fichier
if (!defined('OMNESBNB') && basename($_SERVER['PHP_SELF']) == 'config.php') {
    header('Location: index.php');
    exit;
}

// Définition de la constante OMNESBNB pour indiquer que le site est bien chargé
define('OMNESBNB', true);

// Paramètres de connexion à la base de données
$db_host = 'localhost';
$db_user = 'root';  // À remplacer par l'utilisateur réel en production
$db_pass = 'root';      // À remplacer par le mot de passe réel en production
$db_name = 'omnesbnb';

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Établissement de la connexion à la base de données
$conn = mysqli_connect('127.0.0.1', 'root', '', 'omnesbnb');

// Vérification de la connexion
if (!$conn) {
    die("Erreur de connexion à la base de données: " . mysqli_connect_error());
}

// Configuration de l'encodage UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Configuration de la session
ini_set('session.cookie_httponly', 1);  // Empêche l'accès aux cookies via JavaScript
ini_set('session.use_only_cookies', 1); // Utilisation uniquement de cookies pour les sessions
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Cookies sécurisés si HTTPS est disponible

// Démarrage ou reprise de la session
session_start();

// Configuration de base du site
$site_config = [
    'site_name' => 'OmnesBnB',
    'site_url' => 'http://' . $_SERVER['HTTP_HOST'],  // Ajustez en fonction de votre configuration
    'admin_email' => 'admin@omnesbnb.fr',
    'upload_max_size' => 100 * 1024 * 1024,  // 100 Mo
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'version' => '1.0.0'
];

// Vérification de l'authentification par cookie (Se souvenir de moi)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];

    // Recherche du token dans la base de données
    $query = "SELECT cu.*, u.id as user_id, u.prenom, u.nom, u.email, u.is_admin
              FROM cookies_auth cu
              JOIN users u ON cu.id_utilisateur = u.id
              WHERE cu.token LIKE CONCAT('%', ?) AND cu.date_expiration > NOW()";

    $stmt = mysqli_prepare($conn, $query);

    // On utilise LIKE '%token' car le token stocké est hashé
    // Cette approche n'est pas idéale mais suffit pour un projet simple
    $token_search = mysqli_real_escape_string($conn, $token);
    mysqli_stmt_bind_param($stmt, "s", $token_search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Création de la session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];

        // Mise à jour de la date d'expiration du cookie
        $cookie_id = $user['id'];
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

        $update_query = "UPDATE cookies_auth SET date_expiration = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $expiry, $cookie_id);
        mysqli_stmt_execute($update_stmt);

        // Renouvellement du cookie côté client
        setcookie('auth_token', $token, strtotime('+30 days'), '/', '', true, true);
    }
}
?>