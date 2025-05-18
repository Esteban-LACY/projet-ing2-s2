<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';
include 'mail_functions.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?login_error=Vous devez être connecté pour effectuer cette action');
    exit;
}

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mes-locations.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: mes-locations.php?error=Token de sécurité invalide');
    exit;
}

// Vérification de la présence des champs requis
if (!isset($_POST['reservation_id']) || !isset($_POST['action']) || ($_POST['action'] != 'accepter' && $_POST['action'] != 'refuser')) {
    header('Location: mes-locations.php?error=Requête invalide');
    exit;
}

$reservation_id = intval($_POST['reservation_id']);
$action = $_POST['action'];
$user_id = $_SESSION['user_id'];

// Récupération de la réservation et vérification qu'elle existe
$query = "SELECT r.*, l.titre as logement_titre, l.user_id as proprietaire_id, 
          u.prenom as locataire_prenom, u.nom as locataire_nom, u.email as locataire_email 
          FROM reservations r 
          JOIN logements l ON r.logement_id = l.id 
          JOIN users u ON r.user_id = u.id 
          WHERE r.id = ?";
global $conn;
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $reservation_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header('Location: mes-locations.php?error=Cette réservation n\'existe pas');
    exit;
}

$reservation = mysqli_fetch_assoc($result);

// Vérification que l'utilisateur est le propriétaire du logement ou un administrateur
if ($reservation['proprietaire_id'] != $user_id && (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])) {
    header('Location: mes-locations.php?error=Vous n\'êtes pas autorisé à effectuer cette action');
    exit;
}

// Vérification que la réservation est en attente
if ($reservation['statut'] != 'en attente') {
    header('Location: mes-locations.php?error=Cette réservation n\'est plus en attente');
    exit;
}

// Traitement de l'action (accepter ou refuser)
if ($action == 'accepter') {
    $nouveau_statut = 'acceptée';
    $message_succes = 'La réservation a été acceptée avec succès';
    $email_subject = 'Votre réservation a été acceptée';
    $email_message_intro = 'Votre demande de réservation pour le logement "' . $reservation['logement_titre'] . '" a été acceptée par le propriétaire.';
    $email_message_suite = 'Vous pouvez désormais contacter le propriétaire pour organiser votre arrivée. Ses coordonnées sont visibles dans la section "Mes réservations" de votre compte OmnesBnB.';
} else {
    $nouveau_statut = 'refusée';
    $message_succes = 'La réservation a été refusée';
    $email_subject = 'Votre réservation a été refusée';
    $email_message_intro = 'Nous sommes désolés de vous informer que votre demande de réservation pour le logement "' . $reservation['logement_titre'] . '" a été refusée par le propriétaire.';
    $email_message_suite = 'Vous pouvez rechercher d\'autres logements disponibles sur OmnesBnB.';
}

// Mise à jour du statut de la réservation
$query_update = "UPDATE reservations SET statut = ? WHERE id = ?";
$stmt_update = mysqli_prepare($conn, $query_update);
mysqli_stmt_bind_param($stmt_update, "si", $nouveau_statut, $reservation_id);

if (mysqli_stmt_execute($stmt_update)) {
    // Envoi d'un email au locataire pour l'informer de la décision
    $email_body = "Bonjour " . $reservation['locataire_prenom'] . " " . $reservation['locataire_nom'] . ",\n\n";
    $email_body .= $email_message_intro . "\n\n";
    $email_body .= "Détails de votre réservation :\n";
    $email_body .= "- Dates : du " . date('d/m/Y', strtotime($reservation['date_arrivee'])) . " au " . date('d/m/Y', strtotime($reservation['date_depart'])) . "\n";
    $email_body .= "- Nombre de personnes : " . $reservation['nb_personnes'] . "\n";
    $email_body .= "- Montant total : " . $reservation['prix_total'] . " €\n\n";
    $email_body .= $email_message_suite . "\n\n";

    // Si la réservation est acceptée, ajouter les informations de contact du propriétaire
    if ($nouveau_statut == 'acceptée') {
        // Récupérer les informations du propriétaire
        $query_proprio = "SELECT prenom, nom, email, telephone FROM users WHERE id = ?";
        $stmt_proprio = mysqli_prepare($conn, $query_proprio);
        mysqli_stmt_bind_param($stmt_proprio, "i", $user_id);
        mysqli_stmt_execute($stmt_proprio);
        $result_proprio = mysqli_stmt_get_result($stmt_proprio);
        $proprietaire = mysqli_fetch_assoc($result_proprio);

        $email_body .= "Coordonnées du propriétaire :\n";
        $email_body .= "- Nom : " . $proprietaire['prenom'] . " " . $proprietaire['nom'] . "\n";
        $email_body .= "- Email : " . $proprietaire['email'] . "\n";
        $email_body .= "- Téléphone : " . $proprietaire['telephone'] . "\n\n";
    }

    $email_body .= "Cordialement,\n";
    $email_body .= "L'équipe OmnesBnB";

    send_email($reservation['locataire_email'], $email_subject, $email_body);

    // Redirection avec message de succès
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: admin.php?section=reservations&success=' . $message_succes);
    } else {
        header('Location: mes-locations.php?success=' . $message_succes);
    }
} else {
    // Redirection avec message d'erreur en cas d'échec
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: admin.php?section=reservations&error=Une erreur est survenue lors du traitement de la réservation');
    } else {
        header('Location: mes-locations.php?error=Une erreur est survenue lors du traitement de la réservation');
    }
}

exit;
?>