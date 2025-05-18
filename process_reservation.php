<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';
include 'mail_functions.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?login_error=Vous devez être connecté pour effectuer une réservation');
    exit;
}

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: index.php?error=Token de sécurité invalide');
    exit;
}

// Récupération de l'utilisateur
$user_id = $_SESSION['user_id'];

global $conn;

// Traitement de l'annulation
if (isset($_POST['action']) && $_POST['action'] === 'annuler' && isset($_POST['reservation_id'])) {
    $reservation_id = intval($_POST['reservation_id']);

    // Vérifier que la réservation appartient à l'utilisateur
    $query = "SELECT r.*, l.user_id as proprietaire_id, l.titre
              FROM reservations r 
              JOIN logements l ON r.logement_id = l.id 
              WHERE r.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $reservation_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        header('Location: mes-locations.php?error=Cette réservation n\'existe pas');
        exit;
    }

    $reservation = mysqli_fetch_assoc($result);

    // Vérifier que l'utilisateur est le locataire
    if ($reservation['user_id'] != $user_id) {
        header('Location: mes-locations.php?error=Vous n\'avez pas les droits pour annuler cette réservation');
        exit;
    }

    // Vérifier que la réservation est en attente (seules les réservations en attente peuvent être annulées par le locataire)
    if ($reservation['statut'] !== 'en attente') {
        header('Location: mes-locations.php?error=Vous ne pouvez annuler que les réservations en attente');
        exit;
    }

    // Mettre à jour le statut de la réservation
    $update_query = "UPDATE reservations SET statut = 'annulée' WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $reservation_id);

    if (mysqli_stmt_execute($update_stmt)) {
        // Envoyer un email au propriétaire pour l'informer de l'annulation
        $proprietaire_id = $reservation['proprietaire_id'];
        $titre_logement = $reservation['titre'];

        // Récupérer les informations du propriétaire
        $query_proprio = "SELECT prenom, nom, email FROM users WHERE id = ?";
        $stmt_proprio = mysqli_prepare($conn, $query_proprio);
        mysqli_stmt_bind_param($stmt_proprio, "i", $proprietaire_id);
        mysqli_stmt_execute($stmt_proprio);
        $result_proprio = mysqli_stmt_get_result($stmt_proprio);
        $proprietaire = mysqli_fetch_assoc($result_proprio);

        // Récupérer les informations du locataire
        $query_locataire = "SELECT prenom, nom FROM users WHERE id = ?";
        $stmt_locataire = mysqli_prepare($conn, $query_locataire);
        mysqli_stmt_bind_param($stmt_locataire, "i", $user_id);
        mysqli_stmt_execute($stmt_locataire);
        $result_locataire = mysqli_stmt_get_result($stmt_locataire);
        $locataire = mysqli_fetch_assoc($result_locataire);

        $email_subject = "Annulation de réservation - OmnesBnB";
        $email_body = "Bonjour " . $proprietaire['prenom'] . " " . $proprietaire['nom'] . ",\n\n";
        $email_body .= "La demande de réservation de " . $locataire['prenom'] . " " . $locataire['nom'] . " pour votre logement \"" . $titre_logement . "\" a été annulée.\n\n";
        $email_body .= "Dates : du " . date('d/m/Y', strtotime($reservation['date_arrivee'])) . " au " . date('d/m/Y', strtotime($reservation['date_depart'])) . "\n";
        $email_body .= "Montant : " . $reservation['prix_total'] . " €\n\n";
        $email_body .= "Vous pouvez vous connecter à votre compte OmnesBnB pour plus de détails.\n\n";
        $email_body .= "Cordialement,\n";
        $email_body .= "L'équipe OmnesBnB";

        send_email($proprietaire['email'], $email_subject, $email_body);

        header('Location: mes-locations.php?success=Votre réservation a été annulée avec succès');
    } else {
        header('Location: mes-locations.php?error=Une erreur est survenue lors de l\'annulation de votre réservation');
    }

    exit;
}

// Sinon, c'est une nouvelle réservation

// Vérification de la présence des champs requis
$required_fields = ['logement_id', 'date_arrivee', 'date_depart', 'nb_personnes', 'prix_total'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        header('Location: index.php?error=Formulaire incomplet');
        exit;
    }
}

// Récupération et validation des données
$logement_id = intval($_POST['logement_id']);
$date_arrivee = trim(mysqli_real_escape_string($conn, $_POST['date_arrivee']));
$date_depart = trim(mysqli_real_escape_string($conn, $_POST['date_depart']));
$nb_personnes = intval($_POST['nb_personnes']);
$prix_total = floatval($_POST['prix_total']);

// Vérification que les dates sont valides et que la date d'arrivée est avant la date de départ
$date_arrivee_obj = new DateTime($date_arrivee);
$date_depart_obj = new DateTime($date_depart);
$aujourdhui = new DateTime();

if ($date_arrivee_obj < $aujourdhui) {
    header('Location: logement.php?id=' . $logement_id . '&error=La date d\'arrivée doit être ultérieure à aujourd\'hui');
    exit;
}

if ($date_arrivee_obj >= $date_depart_obj) {
    header('Location: logement.php?id=' . $logement_id . '&error=La date de départ doit être ultérieure à la date d\'arrivée');
    exit;
}

// Vérification que le logement existe
$query = "SELECT l.*, u.id as proprietaire_id, u.email as proprietaire_email, u.prenom as proprietaire_prenom, u.nom as proprietaire_nom
          FROM logements l 
          JOIN users u ON l.user_id = u.id 
          WHERE l.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $logement_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Ce logement n\'existe pas');
    exit;
}

$logement = mysqli_fetch_assoc($result);

// Vérification que l'utilisateur n'est pas le propriétaire du logement
if ($logement['proprietaire_id'] == $user_id) {
    header('Location: logement.php?id=' . $logement_id . '&error=Vous ne pouvez pas réserver votre propre logement');
    exit;
}

// Vérification que le logement n'est pas déjà réservé pour ces dates
$query_dispo = "SELECT * FROM reservations 
               WHERE logement_id = ? 
               AND ((date_arrivee <= ? AND date_depart >= ?) 
                   OR (date_arrivee <= ? AND date_depart >= ?) 
                   OR (date_arrivee >= ? AND date_depart <= ?))
               AND statut IN ('en attente', 'acceptée')";
$stmt_dispo = mysqli_prepare($conn, $query_dispo);
mysqli_stmt_bind_param($stmt_dispo, "issssss", $logement_id, $date_depart, $date_arrivee, $date_arrivee, $date_arrivee, $date_arrivee, $date_depart);
mysqli_stmt_execute($stmt_dispo);
$result_dispo = mysqli_stmt_get_result($stmt_dispo);

if (mysqli_num_rows($result_dispo) > 0) {
    header('Location: logement.php?id=' . $logement_id . '&error=Ce logement n\'est pas disponible pour ces dates');
    exit;
}

// Récupérer le nom et prénom de l'utilisateur
$query_user = "SELECT prenom, nom, email FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

// Insertion de la réservation dans la base de données
$query_insert = "INSERT INTO reservations (logement_id, user_id, date_arrivee, date_depart, nb_personnes, prix_total, statut, date_reservation)
                VALUES (?, ?, ?, ?, ?, ?, 'en attente', NOW())";
$stmt_insert = mysqli_prepare($conn, $query_insert);
mysqli_stmt_bind_param($stmt_insert, "iissid", $logement_id, $user_id, $date_arrivee, $date_depart, $nb_personnes, $prix_total);

if (mysqli_stmt_execute($stmt_insert)) {
    $reservation_id = mysqli_insert_id($conn);

    // Envoi d'un email au propriétaire pour l'informer de la demande de réservation
    $email_subject = "Nouvelle demande de réservation - OmnesBnB";
    $email_body = "Bonjour " . $logement['proprietaire_prenom'] . " " . $logement['proprietaire_nom'] . ",\n\n";
    $email_body .= "Vous avez reçu une nouvelle demande de réservation pour votre logement \"" . $logement['titre'] . "\".\n\n";
    $email_body .= "Détails de la réservation :\n";
    $email_body .= "- Locataire : " . $user['prenom'] . " " . $user['nom'] . "\n";
    $email_body .= "- Dates : du " . date('d/m/Y', strtotime($date_arrivee)) . " au " . date('d/m/Y', strtotime($date_depart)) . "\n";
    $email_body .= "- Nombre de personnes : " . $nb_personnes . "\n";
    $email_body .= "- Montant total : " . $prix_total . " €\n\n";
    $email_body .= "Vous pouvez accepter ou refuser cette demande en vous connectant à votre compte OmnesBnB.\n\n";
    $email_body .= "Cordialement,\n";
    $email_body .= "L'équipe OmnesBnB";

    send_email($logement['proprietaire_email'], $email_subject, $email_body);

    // Envoi d'un email de confirmation au locataire
    $email_subject_locataire = "Confirmation de votre demande de réservation - OmnesBnB";
    $email_body_locataire = "Bonjour " . $user['prenom'] . " " . $user['nom'] . ",\n\n";
    $email_body_locataire .= "Nous avons bien reçu votre demande de réservation pour le logement \"" . $logement['titre'] . "\".\n\n";
    $email_body_locataire .= "Détails de votre réservation :\n";
    $email_body_locataire .= "- Dates : du " . date('d/m/Y', strtotime($date_arrivee)) . " au " . date('d/m/Y', strtotime($date_depart)) . "\n";
    $email_body_locataire .= "- Nombre de personnes : " . $nb_personnes . "\n";
    $email_body_locataire .= "- Montant total : " . $prix_total . " €\n\n";
    $email_body_locataire .= "Le propriétaire va étudier votre demande et vous recevrez un email dès qu'il aura pris sa décision.\n\n";
    $email_body_locataire .= "Vous pouvez suivre l'état de votre réservation en vous connectant à votre compte OmnesBnB.\n\n";
    $email_body_locataire .= "Cordialement,\n";
    $email_body_locataire .= "L'équipe OmnesBnB";

    send_email($user['email'], $email_subject_locataire, $email_body_locataire);

    header('Location: mes-locations.php?tab=reservations&success=Votre demande de réservation a été envoyée avec succès');
} else {
    header('Location: logement.php?id=' . $logement_id . '&error=Une erreur est survenue lors de la réservation');
}

exit;
?>
