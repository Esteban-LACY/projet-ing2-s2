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

// Vérification de la présence de l'ID utilisateur
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID utilisateur invalide']);
    exit;
}

$user_id = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'];

// Vérification que l'utilisateur a le droit d'accéder à ces informations
// (soit c'est lui-même, soit un admin, soit lié par une réservation)
global $conn;
$can_access = false;

// Vérifier si c'est l'utilisateur lui-même ou un admin
if ($user_id == $current_user_id || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    $can_access = true;
} else {
    // Vérifier si l'utilisateur courant a une réservation acceptée avec cet utilisateur
    // En tant que bailleur
    $query_bailleur = "SELECT r.id FROM reservations r 
                     JOIN logements l ON r.logement_id = l.id
                     WHERE l.user_id = ? AND r.user_id = ? AND r.statut = 'acceptée'";
    $stmt_bailleur = mysqli_prepare($conn, $query_bailleur);
    mysqli_stmt_bind_param($stmt_bailleur, "ii", $current_user_id, $user_id);
    mysqli_stmt_execute($stmt_bailleur);
    $result_bailleur = mysqli_stmt_get_result($stmt_bailleur);

    // En tant que locataire
    $query_locataire = "SELECT r.id FROM reservations r 
                      JOIN logements l ON r.logement_id = l.id
                      WHERE l.user_id = ? AND r.user_id = ? AND r.statut = 'acceptée'";
    $stmt_locataire = mysqli_prepare($conn, $query_locataire);
    mysqli_stmt_bind_param($stmt_locataire, "ii", $user_id, $current_user_id);
    mysqli_stmt_execute($stmt_locataire);
    $result_locataire = mysqli_stmt_get_result($stmt_locataire);

    if (mysqli_num_rows($result_bailleur) > 0 || mysqli_num_rows($result_locataire) > 0) {
        $can_access = true;
    }
}

// Si l'utilisateur n'a pas le droit d'accéder aux informations
if (!$can_access) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

// Récupération des informations de l'utilisateur
$query = "SELECT id, prenom, nom, email, telephone, photo, statut, campus, description 
          FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Utilisateur introuvable']);
    exit;
}

$user = mysqli_fetch_assoc($result);

// Retourner les informations au format JSON
header('Content-Type: application/json');
echo json_encode($user);
?>