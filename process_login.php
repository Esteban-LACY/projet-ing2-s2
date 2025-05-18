<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: connexion.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: connexion.php?login_error=Token de sécurité invalide');
    exit;
}

// Vérification de la présence des champs requis
if (!isset($_POST['email-connexion']) || !isset($_POST['mdp-connexion'])) {
    header('Location: connexion.php?login_error=Veuillez remplir tous les champs');
    exit;
}

// Récupération et nettoyage des données
global $conn;
$email = trim(mysqli_real_escape_string($conn, $_POST['email-connexion']));
$password = $_POST['mdp-connexion'];

// Vérification du format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: connexion.php?login_error=Format d\'email invalide');
    exit;
}

// Protection contre les attaques par force brute (limite le nombre de tentatives)
if (!check_login_attempts($conn, $email)) {
    header('Location: connexion.php?login_error=Trop de tentatives de connexion. Veuillez réessayer plus tard.');
    exit;
}

// Récupération de l'utilisateur depuis la base de données
$query = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);

    // Vérification du mot de passe
    if (password_verify($password, $user['password'])) {
        // Vérifier si le compte est activé
        if ($user['active'] == 0) {
            // Journalisation de la tentative de connexion échouée
            log_login_attempt($conn, $email, false);

            header('Location: connexion.php?login_error=Votre compte n\'est pas encore activé. Veuillez vérifier votre email pour activer votre compte.');
            exit;
        }

        // Connexion réussie, initialisation des variables de session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];

        // Mise à jour de la date de dernière connexion
        $update_query = "UPDATE users SET derniere_connexion = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
        mysqli_stmt_execute($update_stmt);

        // Journalisation de la tentative de connexion réussie
        log_login_attempt($conn, $email, true);

        // Gestion du "Se souvenir de moi"
        if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
            // Génération d'un token unique
            $token = bin2hex(random_bytes(32));
            $token_hash = password_hash($token, PASSWORD_DEFAULT);

            // Expiration dans 30 jours
            $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Enregistrement du token dans la base de données
            $cookie_query = "INSERT INTO cookies_auth (id_utilisateur, token, date_expiration) VALUES (?, ?, ?)";
            $cookie_stmt = mysqli_prepare($conn, $cookie_query);
            mysqli_stmt_bind_param($cookie_stmt, "iss", $user['id'], $token_hash, $expiry);
            mysqli_stmt_execute($cookie_stmt);

            // Création du cookie
            setcookie('auth_token', $token, strtotime('+30 days'), '/', '', true, true);
        }

        // Redirection vers la page d'accueil ou la page demandée
        $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
        unset($_SESSION['redirect_after_login']);

        header("Location: $redirect");
        exit;
    } else {
        // Journalisation de la tentative de connexion échouée
        log_login_attempt($conn, $email, false);

        header('Location: connexion.php?login_error=Identifiants incorrects');
        exit;
    }
} else {
    // Journalisation de la tentative de connexion échouée
    log_login_attempt($conn, $email, false);

    header('Location: connexion.php?login_error=Identifiants incorrects');
    exit;
}
?>