<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?login_error=Vous devez être connecté pour effectuer cette action');
    exit;
}

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: publier.php?manage=1');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: publier.php?manage=1&error=Token de sécurité invalide');
    exit;
}

// Vérification de la présence de l'ID du logement
if (!isset($_POST['logement_id']) || !is_numeric($_POST['logement_id'])) {
    header('Location: publier.php?manage=1&error=ID de logement invalide');
    exit;
}

$logement_id = intval($_POST['logement_id']);
$user_id = $_SESSION['user_id'];

// Vérification que le logement appartient bien à l'utilisateur ou que l'utilisateur est administrateur
$query = "SELECT * FROM logements WHERE id = ?";
global $conn;
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $logement_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header('Location: publier.php?manage=1&error=Ce logement n\'existe pas');
    exit;
}

$logement = mysqli_fetch_assoc($result);

// Vérifier si l'utilisateur est le propriétaire du logement ou s'il est administrateur
if ($logement['user_id'] != $user_id && (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])) {
    header('Location: publier.php?manage=1&error=Vous n\'êtes pas autorisé à supprimer ce logement');
    exit;
}

// Vérifier si le logement a des réservations actives
$query_reservations = "SELECT * FROM reservations WHERE logement_id = ? AND statut = 'acceptée'";
$stmt_reservations = mysqli_prepare($conn, $query_reservations);
mysqli_stmt_bind_param($stmt_reservations, "i", $logement_id);
mysqli_stmt_execute($stmt_reservations);
$result_reservations = mysqli_stmt_get_result($stmt_reservations);

// Seul l'administrateur peut supprimer un logement avec des réservations actives
if (mysqli_num_rows($result_reservations) > 0 && (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])) {
    header('Location: publier.php?manage=1&error=Impossible de supprimer ce logement car il a des réservations actives');
    exit;
}

// Récupérer les photos du logement pour les supprimer
$query_photos = "SELECT photo_url FROM photos WHERE logement_id = ?";
$stmt_photos = mysqli_prepare($conn, $query_photos);
mysqli_stmt_bind_param($stmt_photos, "i", $logement_id);
mysqli_stmt_execute($stmt_photos);
$result_photos = mysqli_stmt_get_result($stmt_photos);

$photos = [];
while ($photo = mysqli_fetch_assoc($result_photos)) {
    $photos[] = $photo['photo_url'];
}

// Commencer une transaction
mysqli_begin_transaction($conn);

try {
    // Supprimer toutes les réservations liées au logement
    $query_delete_reservations = "DELETE FROM reservations WHERE logement_id = ?";
    $stmt_delete_reservations = mysqli_prepare($conn, $query_delete_reservations);
    mysqli_stmt_bind_param($stmt_delete_reservations, "i", $logement_id);
    mysqli_stmt_execute($stmt_delete_reservations);

    // Supprimer toutes les photos liées au logement dans la base de données
    $query_delete_photos = "DELETE FROM photos WHERE logement_id = ?";
    $stmt_delete_photos = mysqli_prepare($conn, $query_delete_photos);
    mysqli_stmt_bind_param($stmt_delete_photos, "i", $logement_id);
    mysqli_stmt_execute($stmt_delete_photos);

    // Supprimer le logement
    $query_delete_logement = "DELETE FROM logements WHERE id = ?";
    $stmt_delete_logement = mysqli_prepare($conn, $query_delete_logement);
    mysqli_stmt_bind_param($stmt_delete_logement, "i", $logement_id);
    mysqli_stmt_execute($stmt_delete_logement);

    // Valider la transaction
    mysqli_commit($conn);

    // Supprimer les fichiers physiques des photos
    foreach ($photos as $photo_url) {
        if (file_exists($photo_url)) {
            unlink($photo_url);
        }
    }

    // Redirection en fonction du rôle de l'utilisateur
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: admin.php?section=logements&success=Le logement a été supprimé avec succès');
    } else {
        header('Location: publier.php?manage=1&success=Votre logement a été supprimé avec succès');
    }
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    mysqli_rollback($conn);

    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: admin.php?section=logements&error=Une erreur est survenue lors de la suppression du logement: ' . $e->getMessage());
    } else {
        header('Location: publier.php?manage=1&error=Une erreur est survenue lors de la suppression de votre logement');
    }
}

exit;
?>
