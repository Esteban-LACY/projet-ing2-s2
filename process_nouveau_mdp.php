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

// Vérification de la présence des champs requis
if (!isset($_POST['token']) || !isset($_POST['email']) ||
    !isset($_POST['nouveau-mdp']) || !isset($_POST['confirmer-mdp'])) {
    header('Location: motdepasse.php?error=Paramètres manquants');
    exit;
}

// Récupération et nettoyage des données
global $conn;
$token = trim(mysqli_real_escape_string($conn, $_POST['token']));
$email = trim(mysqli_real_escape_string($conn, $_POST['email']));
$password = $_POST['nouveau-mdp'];
$confirm_password = $_POST['confirmer-mdp'];

// Vérification que les mots de passe correspondent
if ($password !== $confirm_password) {
    header('Location: nouveau-motdepasse.php?token=' . urlencode($token) . '&email=' . urlencode($email) . '&error=Les mots de passe ne correspondent pas');
    exit;
}

// Vérification de la force du mot de passe
if (!password_strength($password)) {
    header('Location: nouveau-motdepasse.php?token=' . urlencode($token) . '&email=' . urlencode($email) . '&error=Le mot de passe n\'est pas assez fort. Il doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.');
    exit;
}

// Vérification que le token existe et n'est pas expiré
$query = "SELECT * FROM users WHERE email = ? AND reset_token = ? AND reset_token_expiry > NOW()";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $email, $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header('Location: motdepasse.php?error=Lien de réinitialisation invalide ou expiré');
    exit;
}

$user = mysqli_fetch_assoc($result);

// Hashage du nouveau mot de passe
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Mise à jour du mot de passe et suppression du token de réinitialisation
$query = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "si", $password_hash, $user['id']);

if (mysqli_stmt_execute($stmt)) {
    // Envoi d'un email de confirmation
    $email_subject = "Confirmation de changement de mot de passe - OmnesBnB";
    $email_body = "Bonjour " . $user['prenom'] . " " . $user['nom'] . ",\n\n";
    $email_body .= "Votre mot de passe sur OmnesBnB a été modifié avec succès.\n\n";
    $email_body .= "Si vous n'avez pas effectué cette modification, veuillez nous contacter immédiatement.\n\n";
    $email_body .= "Cordialement,\n";
    $email_body .= "L'équipe OmnesBnB";

    send_email($email, $email_subject, $email_body);

    // Redirection vers la page de connexion avec un message de succès
    header('Location: nouveau-motdepasse.php?token=' . urlencode($token) . '&email=' . urlencode($email) . '&success=Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.');
} else {
    header('Location: nouveau-motdepasse.php?token=' . urlencode($token) . '&email=' . urlencode($email) . '&error=Une erreur est survenue lors de la réinitialisation de votre mot de passe');
}

exit;
?>