<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Augmenter les limites d'upload
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '110M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '600');
ini_set('max_input_time', '600');

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?login_error=Vous devez être connecté pour accéder à cette page');
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profil.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: profil.php?error=Token de sécurité invalide');
    exit;
}

global $conn;

// Traitement spécifique pour la mise à jour de la photo de profil
if (isset($_POST['action']) && $_POST['action'] === 'update_photo') {
    // Vérification qu'une photo a été envoyée
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0) {
        header('Location: profil.php?error=Erreur lors de l\'upload de la photo');
        exit;
    }

    // Vérification du type de fichier
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $photo_type = $_FILES['photo']['type'];

    if (!in_array($photo_type, $allowed_types)) {
        header('Location: profil.php?error=Type de fichier non autorisé. Utilisez JPG, PNG ou WEBP.');
        exit;
    }

    // Vérification de la taille du fichier (max 100Mo)
    if ($_FILES['photo']['size'] > 100 * 1024 * 1024) {
        header('Location: profil.php?error=La photo ne doit pas dépasser 2Mo');
        exit;
    }

    // Création du répertoire d'upload si nécessaire
    $upload_dir = 'uploads/profils/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Génération d'un nom de fichier unique
    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $new_filename = 'profil_' . $user_id . '_' . time() . '.' . $extension;
    $target_file = $upload_dir . $new_filename;

    // Upload du fichier
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
        // Récupération de l'ancienne photo pour la supprimer
        $query = "SELECT photo FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        // Mise à jour de la base de données
        $query = "UPDATE users SET photo = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $target_file, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            // Suppression de l'ancienne photo si elle existe
            if (!empty($user['photo']) && file_exists($user['photo']) && is_file($user['photo'])) {
                unlink($user['photo']);
            }

            header('Location: profil.php?success=Votre photo de profil a été mise à jour');
        } else {
            // Suppression du fichier uploadé en cas d'erreur
            if (file_exists($target_file)) {
                unlink($target_file);
            }

            header('Location: profil.php?error=Erreur lors de la mise à jour de la photo');
        }
    } else {
        header('Location: profil.php?error=Erreur lors de l\'upload de la photo');
    }

    exit;
}

// Traitement du formulaire principal de profil
// Vérification de la présence des champs obligatoires
$required_fields = ['prenom', 'nom', 'email', 'telephone', 'statut', 'campus'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        header('Location: profil.php?error=Tous les champs obligatoires doivent être remplis');
        exit;
    }
}

// Récupération et nettoyage des données
$prenom = trim(mysqli_real_escape_string($conn, $_POST['prenom']));
$nom = trim(mysqli_real_escape_string($conn, $_POST['nom']));
$email = trim(mysqli_real_escape_string($conn, $_POST['email']));
$telephone = trim(mysqli_real_escape_string($conn, $_POST['telephone']));
$statut = trim(mysqli_real_escape_string($conn, $_POST['statut']));
$campus = trim(mysqli_real_escape_string($conn, $_POST['campus']));
$description = isset($_POST['description']) ? trim(mysqli_real_escape_string($conn, $_POST['description'])) : '';

// Validation du format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: profil.php?error=Format d\'email invalide');
    exit;
}

// Validation du domaine de l'email (doit être @edu.ece.fr, @ece.fr ou @omnesintervenant.com)
if (!validate_omnes_email($email)) {
    header('Location: profil.php?error=Vous devez utiliser une adresse email Omnes');
    exit;
}

// Validation du numéro de téléphone (format: 10 chiffres, éventuellement séparés par des points)
$telephone_clean = preg_replace('/[^0-9]/', '', $telephone);
if (strlen($telephone_clean) != 10) {
    header('Location: profil.php?error=Le numéro de téléphone doit comporter 10 chiffres');
    exit;
}

// Formater le numéro de téléphone: XX.XX.XX.XX.XX
$telephone_formatted = chunk_split($telephone_clean, 2, '.');
$telephone_formatted = rtrim($telephone_formatted, '.');

// Récupération des données actuelles de l'utilisateur
$query = "SELECT email FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Vérification si l'email a changé et s'il est déjà utilisé par un autre compte
if ($email != $user['email']) {
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        header('Location: profil.php?error=Cette adresse email est déjà utilisée');
        exit;
    }
}

// Mise à jour des informations dans la base de données
$query = "UPDATE users SET prenom = ?, nom = ?, email = ?, telephone = ?, statut = ?, campus = ?, description = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssssssi", $prenom, $nom, $email, $telephone_formatted, $statut, $campus, $description, $user_id);

if (mysqli_stmt_execute($stmt)) {
    // Mise à jour des variables de session
    $_SESSION['user_email'] = $email;
    $_SESSION['user_prenom'] = $prenom;
    $_SESSION['user_nom'] = $nom;

    header('Location: profil.php?success=Votre profil a été mis à jour avec succès');
} else {
    header('Location: profil.php?error=Une erreur est survenue lors de la mise à jour de votre profil');
}

exit;
?>