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
    header('Location: connexion.php?register=1');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: connexion.php?register=1&register_error=Token de sécurité invalide');
    exit;
}

// Vérification de la présence des champs requis
$required_fields = ['prenom-inscription', 'nom-inscription', 'email-inscription',
    'telephone-inscription', 'statut-inscription', 'campus-inscription',
    'mdp-inscription', 'mdp2-inscription'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        header('Location: connexion.php?register=1&register_error=Veuillez remplir tous les champs');
        exit;
    }
}

// Récupération et nettoyage des données
global $conn;
$prenom = trim(mysqli_real_escape_string($conn, $_POST['prenom-inscription']));
$nom = trim(mysqli_real_escape_string($conn, $_POST['nom-inscription']));
$email = trim(mysqli_real_escape_string($conn, $_POST['email-inscription']));
$telephone = trim(mysqli_real_escape_string($conn, $_POST['telephone-inscription']));
$statut = trim(mysqli_real_escape_string($conn, $_POST['statut-inscription']));
$campus = trim(mysqli_real_escape_string($conn, $_POST['campus-inscription']));
$password = $_POST['mdp-inscription'];
$password2 = $_POST['mdp2-inscription'];

// Validation du format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: connexion.php?register=1&register_error=Format d\'email invalide');
    exit;
}

// Validation du domaine de l'email (doit être @edu.ece.fr, @ece.fr ou @omnesintervenant.com)
if (!validate_omnes_email($email)) {
    header('Location: connexion.php?register=1&register_error=Vous devez utiliser une adresse email Omnes');
    exit;
}

// Validation du format du téléphone (format français: 10 chiffres)
if (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $telephone))) {
    header('Location: connexion.php?register=1&register_error=Format de téléphone invalide');
    exit;
}

// Vérification que les mots de passe correspondent
if ($password !== $password2) {
    header('Location: connexion.php?register=1&register_error=Les mots de passe ne correspondent pas');
    exit;
}

// Vérification de la complexité du mot de passe
if (!password_strength($password)) {
    header('Location: connexion.php?register=1&register_error=Le mot de passe n\'est pas assez fort. Il doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.');
    exit;
}

// Vérification que l'email n'est pas déjà utilisé
$check_query = "SELECT id FROM users WHERE email = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "s", $email);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    header('Location: connexion.php?register=1&register_error=Cette adresse email est déjà utilisée');
    exit;
}

// Hashage du mot de passe
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Création du token de vérification
$verification_token = bin2hex(random_bytes(32));

// Insertion de l'utilisateur dans la base de données
$query = "INSERT INTO users (prenom, nom, email, password, telephone, statut, campus, active, verification_token, date_creation) 
          VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ssssssss", $prenom, $nom, $email, $password_hash, $telephone, $statut, $campus, $verification_token);

if (mysqli_stmt_execute($stmt)) {
    // Envoi de l'email de vérification
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/verification.php?email=" . urlencode($email) . "&token=" . $verification_token;

    $email_subject = "Vérification de votre compte OmnesBnB";
    $email_body = "Bonjour $prenom $nom,\n\n";
    $email_body .= "Merci de vous être inscrit sur OmnesBnB. Pour activer votre compte, veuillez cliquer sur le lien suivant :\n\n";
    $email_body .= $verification_link . "\n\n";
    $email_body .= "Ce lien est valable pendant 24 heures.\n\n";
    $email_body .= "Si vous n'avez pas créé de compte sur OmnesBnB, vous pouvez ignorer cet email.\n\n";
    $email_body .= "Cordialement,\n";
    $email_body .= "L'équipe OmnesBnB";

    if (send_email($email, $email_subject, $email_body)) {
        header('Location: connexion.php?success=Votre compte a été créé avec succès. Veuillez vérifier votre email pour activer votre compte.');
    } else {
        // L'email n'a pas pu être envoyé, mais le compte a été créé
        header('Location: connexion.php?success=Votre compte a été créé, mais l\'email de vérification n\'a pas pu être envoyé. Veuillez contacter l\'administrateur.');
    }
} else {
    header('Location: connexion.php?register=1&register_error=Une erreur est survenue lors de la création de votre compte');
}

exit;
?>