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
    header('Location: profil.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: profil.php?error=Token de sécurité invalide');
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérification que l'utilisateur n'est pas un administrateur (protection supplémentaire)
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header('Location: profil.php?error=Les comptes administrateurs ne peuvent pas être supprimés depuis cette interface');
    exit;
}

// Récupération des informations de l'utilisateur
$query = "SELECT * FROM users WHERE id = ?";
global $conn;
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    // Session invalide, déconnexion forcée
    session_destroy();
    header('Location: connexion.php?login_error=Session invalide');
    exit;
}

$user = mysqli_fetch_assoc($result);

// Vérification des réservations actives en tant que locataire
$query_reservations = "SELECT r.*, l.titre as logement_titre 
                      FROM reservations r 
                      JOIN logements l ON r.logement_id = l.id 
                      WHERE r.user_id = ? AND r.statut = 'acceptée' 
                      AND r.date_depart > CURDATE()";
$stmt_reservations = mysqli_prepare($conn, $query_reservations);
mysqli_stmt_bind_param($stmt_reservations, "i", $user_id);
mysqli_stmt_execute($stmt_reservations);
$result_reservations = mysqli_stmt_get_result($stmt_reservations);

if (mysqli_num_rows($result_reservations) > 0) {
    header('Location: profil.php?error=Vous ne pouvez pas supprimer votre compte car vous avez des réservations en cours. Veuillez les annuler avant de supprimer votre compte.');
    exit;
}

// Vérification des logements avec des réservations actives
$query_logements = "SELECT l.*, r.id as reservation_id 
                   FROM logements l 
                   JOIN reservations r ON l.id = r.logement_id 
                   WHERE l.user_id = ? AND r.statut = 'acceptée' 
                   AND r.date_depart > CURDATE()";
$stmt_logements = mysqli_prepare($conn, $query_logements);
mysqli_stmt_bind_param($stmt_logements, "i", $user_id);
mysqli_stmt_execute($stmt_logements);
$result_logements = mysqli_stmt_get_result($stmt_logements);

if (mysqli_num_rows($result_logements) > 0) {
    header('Location: profil.php?error=Vous ne pouvez pas supprimer votre compte car vous avez des logements avec des réservations en cours. Veuillez les annuler avant de supprimer votre compte.');
    exit;
}

// Récupération des photos des logements pour les supprimer
$query_photos = "SELECT p.photo_url 
                FROM photos p 
                JOIN logements l ON p.logement_id = l.id 
                WHERE l.user_id = ?";
$stmt_photos = mysqli_prepare($conn, $query_photos);
mysqli_stmt_bind_param($stmt_photos, "i", $user_id);
mysqli_stmt_execute($stmt_photos);
$result_photos = mysqli_stmt_get_result($stmt_photos);

$photos = [];
while ($photo = mysqli_fetch_assoc($result_photos)) {
    $photos[] = $photo['photo_url'];
}

// Récupération de la photo de profil
if (!empty($user['photo'])) {
    $photos[] = $user['photo'];
}

// Commencer une transaction
mysqli_begin_transaction($conn);

try {
    // Supprimer toutes les réservations liées à l'utilisateur (en tant que locataire)
    $query_delete_reservations_locataire = "DELETE FROM reservations WHERE user_id = ?";
    $stmt_delete_reservations_locataire = mysqli_prepare($conn, $query_delete_reservations_locataire);
    mysqli_stmt_bind_param($stmt_delete_reservations_locataire, "i", $user_id);
    mysqli_stmt_execute($stmt_delete_reservations_locataire);

    // Récupérer les IDs des logements de l'utilisateur
    $query_logements_ids = "SELECT id FROM logements WHERE user_id = ?";
    $stmt_logements_ids = mysqli_prepare($conn, $query_logements_ids);
    mysqli_stmt_bind_param($stmt_logements_ids, "i", $user_id);
    mysqli_stmt_execute($stmt_logements_ids);
    $result_logements_ids = mysqli_stmt_get_result($stmt_logements_ids);

    $logement_ids = [];
    while ($row = mysqli_fetch_assoc($result_logements_ids)) {
        $logement_ids[] = $row['id'];
    }

    if (!empty($logement_ids)) {
        // Supprimer toutes les réservations liées aux logements de l'utilisateur
        $placeholders = str_repeat('?,', count($logement_ids) - 1) . '?';
        $query_delete_reservations_proprio = "DELETE FROM reservations WHERE logement_id IN ($placeholders)";
        $stmt_delete_reservations_proprio = mysqli_prepare($conn, $query_delete_reservations_proprio);

        $types = str_repeat('i', count($logement_ids));
        mysqli_stmt_bind_param($stmt_delete_reservations_proprio, $types, ...$logement_ids);
        mysqli_stmt_execute($stmt_delete_reservations_proprio);

        // Supprimer toutes les photos liées aux logements de l'utilisateur
        $query_delete_photos = "DELETE FROM photos WHERE logement_id IN ($placeholders)";
        $stmt_delete_photos = mysqli_prepare($conn, $query_delete_photos);
        mysqli_stmt_bind_param($stmt_delete_photos, $types, ...$logement_ids);
        mysqli_stmt_execute($stmt_delete_photos);
    }

    // Supprimer tous les logements de l'utilisateur
    $query_delete_logements = "DELETE FROM logements WHERE user_id = ?";
    $stmt_delete_logements = mysqli_prepare($conn, $query_delete_logements);
    mysqli_stmt_bind_param($stmt_delete_logements, "i", $user_id);
    mysqli_stmt_execute($stmt_delete_logements);

    // Supprimer les cookies d'authentification
    $query_delete_cookies = "DELETE FROM cookies_auth WHERE id_utilisateur = ?";
    $stmt_delete_cookies = mysqli_prepare($conn, $query_delete_cookies);
    mysqli_stmt_bind_param($stmt_delete_cookies, "i", $user_id);
    mysqli_stmt_execute($stmt_delete_cookies);

    // Supprimer l'utilisateur
    $query_delete_user = "DELETE FROM users WHERE id = ?";
    $stmt_delete_user = mysqli_prepare($conn, $query_delete_user);
    mysqli_stmt_bind_param($stmt_delete_user, "i", $user_id);
    mysqli_stmt_execute($stmt_delete_user);

    // Valider la transaction
    mysqli_commit($conn);

    // Supprimer les fichiers physiques des photos
    foreach ($photos as $photo_url) {
        if (file_exists($photo_url)) {
            unlink($photo_url);
        }
    }

    // Envoyer un email de confirmation
    $email_subject = "Confirmation de suppression de compte - OmnesBnB";
    $email_body = "Bonjour " . $user['prenom'] . " " . $user['nom'] . ",\n\n";
    $email_body .= "Nous vous confirmons que votre compte OmnesBnB a été supprimé avec succès.\n\n";
    $email_body .= "Toutes vos données personnelles, vos annonces et vos réservations ont été supprimées de notre base de données.\n\n";
    $email_body .= "Nous espérons vous revoir bientôt sur OmnesBnB.\n\n";
    $email_body .= "Cordialement,\n";
    $email_body .= "L'équipe OmnesBnB";

    send_email($user['email'], $email_subject, $email_body);

    // Détruire la session
    session_destroy();

    // Supprimer le cookie de connexion automatique
    if (isset($_COOKIE['auth_token'])) {
        setcookie('auth_token', '', time() - 3600, '/', '', true, true);
    }

    // Redirection vers la page d'accueil avec message de succès
    header('Location: index.php?success=Votre compte a été supprimé avec succès');
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    mysqli_rollback($conn);

    // Redirection avec message d'erreur
    header('Location: profil.php?error=Une erreur est survenue lors de la suppression de votre compte: ' . $e->getMessage());
}

exit;
?>