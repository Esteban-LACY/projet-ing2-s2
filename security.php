<?php
/**
 * Fonctions de sécurité pour OmnesBnB
 * Contient les fonctions nécessaires à la sécurisation du site
 */

// Empêcher l'accès direct à ce fichier
if (!defined('OMNESBNB') && basename($_SERVER['PHP_SELF']) == 'security.php') {
    header('Location: index.php');
    exit;
}

// Définition de la constante OMNESBNB si elle n'est pas déjà définie
if (!defined('OMNESBNB')) {
    define('OMNESBNB', true);
}

/**
 * Sécurise une entrée utilisateur contre les injections SQL et XSS
 *
 * @param string $data Donnée à sécuriser
 * @return string Donnée sécurisée
 */
function secure_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

/**
 * Génère un token CSRF
 *
 * @return string Token CSRF
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité d'un token CSRF
 *
 * @param string $token Token CSRF à vérifier
 * @return bool True si le token est valide, False sinon
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Vérifie si l'utilisateur est connecté
 *
 * @return bool True si l'utilisateur est connecté, False sinon
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est un administrateur
 *
 * @return bool True si l'utilisateur est un administrateur, False sinon
 */
function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Vérifie la force d'un mot de passe
 *
 * @param string $password Mot de passe à vérifier
 * @return bool True si le mot de passe est suffisamment fort, False sinon
 */
function password_strength($password) {
    // Longueur minimale de 8 caractères
    if (strlen($password) < 8) {
        return false;
    }

    // Au moins une lettre majuscule
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }

    // Au moins une lettre minuscule
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }

    // Au moins un chiffre
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }

    // Au moins un caractère spécial
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return false;
    }

    return true;
}

/**
 * Vérifie si une adresse email appartient au domaine Omnes (@edu.ece.fr, @ece.fr ou @omnesintervenant.com)
 *
 * @param string $email Adresse email à vérifier
 * @return bool True si l'adresse email appartient au domaine Omnes, False sinon
 */
function validate_omnes_email($email) {
    $pattern = '/^[a-zA-Z0-9._%+-]+@(edu\.ece\.fr|ece\.fr|omnesintervenant\.com)$/';
    return preg_match($pattern, $email);
}

/**
 * Vérifie si un utilisateur a le droit de modifier une ressource
 *
 * @param string $table Nom de la table contenant la ressource
 * @param int $resource_id ID de la ressource
 * @param int $user_id ID de l'utilisateur
 * @return bool True si l'utilisateur a le droit de modifier la ressource, False sinon
 */
function check_ownership($table, $resource_id, $user_id) {
    global $conn;

    $table = mysqli_real_escape_string($conn, $table);
    $resource_id = intval($resource_id);
    $user_id = intval($user_id);

    $query = "SELECT 1 FROM $table WHERE id = $resource_id AND user_id = $user_id";
    $result = mysqli_query($conn, $query);

    return mysqli_num_rows($result) > 0;
}

/**
 * Vérifie si les tentatives de connexion n'ont pas dépassé la limite
 *
 * @param string $email Adresse email utilisée pour la tentative de connexion
 * @return bool True si l'utilisateur peut tenter de se connecter, False sinon
 */
function check_login_attempts($conn, $email) {
    $email = mysqli_real_escape_string($conn, $email);
    $time_limit = time() - 15 * 60; // 15 minutes

    // Vérifier si la table login_attempts existe
    $check_table = "SHOW TABLES LIKE 'login_attempts'";
    $result_table = mysqli_query($conn, $check_table);

    if (mysqli_num_rows($result_table) == 0) {
        // Créer la table si elle n'existe pas
        $create_table = "CREATE TABLE login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            time INT NOT NULL,
            success BOOLEAN NOT NULL DEFAULT 0
        )";
        mysqli_query($conn, $create_table);

        // Comme la table vient d'être créée, l'utilisateur n'a pas encore dépassé la limite
        return true;
    }

    // Compter les tentatives échouées dans les 15 dernières minutes
    $query = "SELECT COUNT(*) as count FROM login_attempts 
             WHERE email = '$email' AND success = 0 AND time > $time_limit";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    // Limite de 5 tentatives
    return $row['count'] < 5;
}

/**
 * Enregistre une tentative de connexion
 *
 * @param string $email Adresse email utilisée pour la tentative de connexion
 * @param bool $success Succès ou échec de la tentative
 */
function log_login_attempt($conn, $email, $success) {
    $email = mysqli_real_escape_string($conn, $email);
    $success = $success ? 1 : 0;
    $time = time();

    // Vérifier si la table login_attempts existe
    $check_table = "SHOW TABLES LIKE 'login_attempts'";
    $result_table = mysqli_query($conn, $check_table);

    if (mysqli_num_rows($result_table) == 0) {
        // Créer la table si elle n'existe pas
        $create_table = "CREATE TABLE login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            time INT NOT NULL,
            success BOOLEAN NOT NULL DEFAULT 0
        )";
        mysqli_query($conn, $create_table);
    }

    // Enregistrer la tentative
    $query = "INSERT INTO login_attempts (email, time, success) VALUES ('$email', $time, $success)";
    mysqli_query($conn, $query);
}

/**
 * Nettoie les anciennes tentatives de connexion
 */
function clean_login_attempts() {
    global $conn;

    // Supprimer les tentatives de plus d'une journée
    $time_limit = time() - 24 * 60 * 60; // 24 heures
    $query = "DELETE FROM login_attempts WHERE time < $time_limit";
    mysqli_query($conn, $query);
}

// Nettoyer les anciennes tentatives de connexion (une chance sur 100)
if (mt_rand(1, 100) == 1) {
    clean_login_attempts();
}
?>