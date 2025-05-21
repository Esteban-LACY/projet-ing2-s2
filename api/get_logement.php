<?php
// Inclusion des fichiers de configuration et sécurité
include '../config.php';
include '../security.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

// Vérification de la présence de l'ID du logement
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de logement invalide']);
    exit;
}

$logement_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Récupération des informations du logement
global $conn;
$query = "SELECT l.*, 
           COALESCE(p.photo_url, 'uploads/logements/default.jpg') as photo_principale,
           u.prenom as proprietaire_prenom, u.nom as proprietaire_nom, 
           u.email as proprietaire_email, u.telephone as proprietaire_telephone
          FROM logements l
          LEFT JOIN (SELECT * FROM photos WHERE is_main = 1) p ON l.id = p.logement_id
          LEFT JOIN users u ON l.user_id = u.id
          WHERE l.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $logement_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Logement introuvable']);
    exit;
}

$logement = mysqli_fetch_assoc($result);

// Vérification que l'utilisateur a le droit d'accéder à ces informations
// (soit c'est le propriétaire, soit un admin, soit un locataire avec réservation acceptée)
$can_access = false;

// Vérifier si c'est le propriétaire ou un admin
if ($logement['user_id'] == $user_id || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    $can_access = true;
} else {
    // Vérifier si l'utilisateur a une réservation acceptée pour ce logement
    $query_reservation = "SELECT id FROM reservations 
                         WHERE logement_id = ? AND user_id = ? AND statut IN ('acceptée', 'en attente')";
    $stmt_reservation = mysqli_prepare($conn, $query_reservation);
    mysqli_stmt_bind_param($stmt_reservation, "ii", $logement_id, $user_id);
    mysqli_stmt_execute($stmt_reservation);
    $result_reservation = mysqli_stmt_get_result($stmt_reservation);

    if (mysqli_num_rows($result_reservation) > 0) {
        $can_access = true;
    }
}

// Si l'utilisateur n'a pas le droit d'accéder aux informations
if (!$can_access) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

// Récupérer les réservations associées au logement si l'utilisateur est le propriétaire
if ($logement['user_id'] == $user_id || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    $query_reservations = "SELECT r.*, u.prenom, u.nom, u.email, u.telephone
                          FROM reservations r
                          JOIN users u ON r.user_id = u.id
                          WHERE r.logement_id = ?
                          ORDER BY r.date_reservation DESC";
    $stmt_reservations = mysqli_prepare($conn, $query_reservations);
    mysqli_stmt_bind_param($stmt_reservations, "i", $logement_id);
    mysqli_stmt_execute($stmt_reservations);
    $result_reservations = mysqli_stmt_get_result($stmt_reservations);

    $reservations = [];
    while ($reservation = mysqli_fetch_assoc($result_reservations)) {
        $reservations[] = $reservation;
    }

    $logement['reservations'] = $reservations;
}

// Récupérer toutes les photos du logement
$query_photos = "SELECT photo_url, is_main FROM photos WHERE logement_id = ? ORDER BY is_main DESC";
$stmt_photos = mysqli_prepare($conn, $query_photos);
mysqli_stmt_bind_param($stmt_photos, "i", $logement_id);
mysqli_stmt_execute($stmt_photos);
$result_photos = mysqli_stmt_get_result($stmt_photos);

$photos = [];
while ($photo = mysqli_fetch_assoc($result_photos)) {
    $photos[] = $photo;
}

$logement['photos'] = $photos;

// Retourner les informations au format JSON
header('Content-Type: application/json');
echo json_encode($logement);
?>