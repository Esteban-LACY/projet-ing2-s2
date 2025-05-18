<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?login_error=Vous devez être connecté pour publier un logement');
    exit;
}

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: publier.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: publier.php?error=Token de sécurité invalide');
    exit;
}

// Récupération de l'ID utilisateur
$user_id = $_SESSION['user_id'];

// Vérification de la présence des champs requis
$required_fields = ['titre', 'type-logement', 'adresse', 'prix_nuit', 'prix_semaine', 'prix_mois', 'prix_annee', 'caution'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        header('Location: publier.php?error=Veuillez remplir tous les champs obligatoires');
        exit;
    }
}

// Vérification de la photo principale
if (!isset($_FILES['photo_principale']) || $_FILES['photo_principale']['error'] != 0) {
    header('Location: publier.php?error=Veuillez ajouter une photo principale');
    exit;
}

// Récupération et nettoyage des données
global $conn;
$titre = trim(mysqli_real_escape_string($conn, $_POST['titre']));
$type = trim(mysqli_real_escape_string($conn, $_POST['type-logement']));
$adresse = trim(mysqli_real_escape_string($conn, $_POST['adresse']));
$prix_nuit = floatval($_POST['prix_nuit']);
$prix_semaine = floatval($_POST['prix_semaine']);
$prix_mois = floatval($_POST['prix_mois']);
$prix_annee = floatval($_POST['prix_annee']);
$caution = floatval($_POST['caution']);
$description = isset($_POST['description']) ? trim(mysqli_real_escape_string($conn, $_POST['description'])) : '';

// Validation des prix
if ($prix_nuit <= 0 || $prix_semaine <= 0 || $prix_mois <= 0 || $prix_annee <= 0 || $caution <= 0) {
    header('Location: publier.php?error=Les prix et la caution doivent être supérieurs à zéro');
    exit;
}

// Vérification de la cohérence des prix
if ($prix_nuit * 7 <= $prix_semaine || $prix_semaine * 4 <= $prix_mois || $prix_mois * 12 <= $prix_annee) {
    header('Location: publier.php?error=Les prix ne sont pas cohérents');
    exit;
}

// Traitement de la photo principale
$upload_dir = 'uploads/logements/';

// Créer le répertoire s'il n'existe pas
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Traitement de la photo principale
$photo_principale_name = $_FILES['photo_principale']['name'];
$photo_principale_tmp = $_FILES['photo_principale']['tmp_name'];
$photo_principale_size = $_FILES['photo_principale']['size'];
$photo_principale_error = $_FILES['photo_principale']['error'];

// Validation de la photo principale
$photo_principale_ext = strtolower(pathinfo($photo_principale_name, PATHINFO_EXTENSION));
$allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($photo_principale_ext, $allowed_ext)) {
    header('Location: publier.php?error=La photo principale doit être au format jpg, jpeg, png ou webp');
    exit;
}

// Taille maximale (5 Mo)
if ($photo_principale_size > 5 * 1024 * 1024) {
    header('Location: publier.php?error=La photo principale ne doit pas dépasser 5 Mo');
    exit;
}

// Générer un nom unique pour la photo principale
$photo_principale_new_name = uniqid('logement_') . '_' . time() . '.' . $photo_principale_ext;
$photo_principale_destination = $upload_dir . $photo_principale_new_name;

// Déplacer la photo principale
if (!move_uploaded_file($photo_principale_tmp, $photo_principale_destination)) {
    header('Location: publier.php?error=Une erreur est survenue lors de l\'upload de la photo principale');
    exit;
}

// Insertion du logement dans la base de données
$query = "INSERT INTO logements (user_id, titre, type, adresse, prix_nuit, prix_semaine, prix_mois, prix_annee, caution, description, date_creation) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "isssddddds", $user_id, $titre, $type, $adresse, $prix_nuit, $prix_semaine, $prix_mois, $prix_annee, $caution, $description);

if (mysqli_stmt_execute($stmt)) {
    // Récupération de l'ID du logement inséré
    $logement_id = mysqli_insert_id($conn);

    // Insertion de la photo principale dans la table photos
    $query_photo = "INSERT INTO photos (logement_id, photo_url, is_main) VALUES (?, ?, 1)";
    $stmt_photo = mysqli_prepare($conn, $query_photo);
    mysqli_stmt_bind_param($stmt_photo, "is", $logement_id, $photo_principale_destination);
    mysqli_stmt_execute($stmt_photo);

    // Traitement des photos annexes si présentes
    if (isset($_FILES['photos_annexes']) && is_array($_FILES['photos_annexes']['name'])) {
        $total_annexes = count($_FILES['photos_annexes']['name']);

        for ($i = 0; $i < $total_annexes; $i++) {
            if ($_FILES['photos_annexes']['error'][$i] == 0) {
                $photo_annexe_name = $_FILES['photos_annexes']['name'][$i];
                $photo_annexe_tmp = $_FILES['photos_annexes']['tmp_name'][$i];
                $photo_annexe_size = $_FILES['photos_annexes']['size'][$i];

                // Validation de la photo annexe
                $photo_annexe_ext = strtolower(pathinfo($photo_annexe_name, PATHINFO_EXTENSION));

                if (in_array($photo_annexe_ext, $allowed_ext) && $photo_annexe_size <= 5 * 1024 * 1024) {
                    $photo_annexe_new_name = uniqid('logement_annexe_') . '_' . time() . '_' . $i . '.' . $photo_annexe_ext;
                    $photo_annexe_destination = $upload_dir . $photo_annexe_new_name;

                    if (move_uploaded_file($photo_annexe_tmp, $photo_annexe_destination)) {
                        $query_annexe = "INSERT INTO photos (logement_id, photo_url, is_main) VALUES (?, ?, 0)";
                        $stmt_annexe = mysqli_prepare($conn, $query_annexe);
                        mysqli_stmt_bind_param($stmt_annexe, "is", $logement_id, $photo_annexe_destination);
                        mysqli_stmt_execute($stmt_annexe);
                    }
                }
            }
        }
    }

    header('Location: publier.php?manage=1&success=Votre logement a été publié avec succès');
} else {
    // Suppression de la photo principale si l'insertion échoue
    if (file_exists($photo_principale_destination)) {
        unlink($photo_principale_destination);
    }

    header('Location: publier.php?error=Une erreur est survenue lors de la publication de votre logement');
}

exit;
?>