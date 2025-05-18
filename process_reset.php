<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';
include 'mail_functions.php';

// Vérification si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: motdepasse.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: motdepasse.php?error=Token de sécurité invalide');
    exit;
}

// Vérification de la présence du champ email
if (!isset($_POST['email-recuperation']) || empty(trim($_POST['email-recuperation']))) {
    header('Location: motdepasse.php?error=Veuillez saisir une adresse email');
    exit;
}

// Récupération et nettoyage de l'email
global $conn;
$email = trim(mysqli_real_escape_string($conn, $_POST['email-recuperation']));

// Validation du format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: motdepasse.php?error=Format d\'email invalide');
    exit;
}

// Vérification que l'email existe dans la base de données
$query = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    // Pour des raisons de sécurité, ne pas indiquer que l'email n'existe pas
    // mais plutôt envoyer un message générique
    header('Location: motdepasse.php?success=Si cette adresse email est associée à un compte, vous recevrez un email avec les instructions pour réinitialiser votre mot de passe.');
    exit;
}

$user = mysqli_fetch_assoc($result);

// Génération d'un token unique pour la réinitialisation
$token = bin2hex(random_bytes(32));

// Calcul de la date d'expiration (30 minutes)
$expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

// Mise à jour de la base de données avec le token et la date d'expiration
$query = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ssi", $token, $expiry, $user['id']);

if (mysqli_stmt_execute($stmt)) {
    // Envoi de l'email de réinitialisation
    $reset_url = "http://" . $_SERVER['HTTP_HOST'] . "/nouveau-motdepasse.php?token=" . $token . "&email=" . urlencode($email);

    $email_subject = "Réinitialisation de votre mot de passe - OmnesBnB";
    $email_body = "Bonjour " . $user['prenom'] . " " . $user['nom'] . ",\n\n";
    $email_body .= "Vous avez demandé la réinitialisation de votre mot de passe sur OmnesBnB. Veuillez cliquer sur le lien ci-dessous pour créer un nouveau mot de passe :\n\n";
    $email_body .= $reset_url . "\n\n";
    $email_body .= "Ce lien est valable pendant 30 minutes.\n\n";
    $email_body .= "Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.\n\n";
    $email_body .= "Cordialement,\n";
    $email_body .= "L'équipe OmnesBnB";

    if (send_email($email, $email_subject, $email_body)) {
        header('Location: motdepasse.php?success=Un email de réinitialisation a été envoyé à votre adresse email.');
    } else {
        header('Location: motdepasse.php?error=Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer plus tard.');
    }
} else {
    header('Location: motdepasse.php?error=Une erreur est survenue. Veuillez réessayer plus tard.');
}

exit;
?>