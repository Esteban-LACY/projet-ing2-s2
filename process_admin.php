<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';
include 'mail_functions.php';

// Vérification que l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: connexion.php?login_error=Accès interdit. Vous devez être administrateur pour effectuer cette action.');
    exit;
}

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: admin.php?error=Token de sécurité invalide');
    exit;
}

// Vérification de la présence du champ action
if (!isset($_POST['action'])) {
    header('Location: admin.php?error=Action non spécifiée');
    exit;
}

$action = $_POST['action'];

global $conn;
// Traitement des différentes actions
switch ($action) {
    case 'delete_user':
        // Vérification de la présence de l'ID utilisateur
        if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
            header('Location: admin.php?section=users&error=ID utilisateur invalide');
            exit;
        }

        $user_id = intval($_POST['user_id']);

        // Vérification que l'utilisateur n'est pas un administrateur (protection supplémentaire)
        $query_check_admin = "SELECT is_admin FROM users WHERE id = ?";
        $stmt_check_admin = mysqli_prepare($conn, $query_check_admin);
        mysqli_stmt_bind_param($stmt_check_admin, "i", $user_id);
        mysqli_stmt_execute($stmt_check_admin);
        $result_check_admin = mysqli_stmt_get_result($stmt_check_admin);

        if (mysqli_num_rows($result_check_admin) == 0) {
            header('Location: admin.php?section=users&error=Utilisateur introuvable');
            exit;
        }

        $user_info = mysqli_fetch_assoc($result_check_admin);

        if ($user_info['is_admin'] == 1) {
            header('Location: admin.php?section=users&error=Vous ne pouvez pas supprimer un administrateur');
            exit;
        }

        // Récupérer les photos des logements pour les supprimer
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

        // Récupérer la photo de profil
        $query_user_photo = "SELECT photo FROM users WHERE id = ?";
        $stmt_user_photo = mysqli_prepare($conn, $query_user_photo);
        mysqli_stmt_bind_param($stmt_user_photo, "i", $user_id);
        mysqli_stmt_execute($stmt_user_photo);
        $result_user_photo = mysqli_stmt_get_result($stmt_user_photo);
        $user_photo = mysqli_fetch_assoc($result_user_photo);

        if (!empty($user_photo['photo'])) {
            $photos[] = $user_photo['photo'];
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

            header('Location: admin.php?section=users&success=Utilisateur supprimé avec succès');
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            mysqli_rollback($conn);
            header('Location: admin.php?section=users&error=Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage());
        }
        break;

    case 'activate_user':
        // Vérification de la présence de l'ID utilisateur
        if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
            header('Location: admin.php?section=users&error=ID utilisateur invalide');
            exit;
        }

        $user_id = intval($_POST['user_id']);

        // Mettre à jour le statut de l'utilisateur
        $query = "UPDATE users SET active = 1, verification_token = NULL WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: admin.php?section=users&success=Utilisateur activé avec succès');
        } else {
            header('Location: admin.php?section=users&error=Erreur lors de l\'activation de l\'utilisateur');
        }
        break;

    case 'delete_logement':
        // Vérification de la présence de l'ID logement
        if (!isset($_POST['logement_id']) || !is_numeric($_POST['logement_id'])) {
            header('Location: admin.php?section=logements&error=ID logement invalide');
            exit;
        }

        $logement_id = intval($_POST['logement_id']);

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

            // Supprimer toutes les photos liées au logement
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

            header('Location: admin.php?section=logements&success=Logement supprimé avec succès');
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            mysqli_rollback($conn);
            header('Location: admin.php?section=logements&error=Erreur lors de la suppression du logement: ' . $e->getMessage());
        }
        break;

    case 'delete_reservation':
        // Vérification de la présence de l'ID réservation
        if (!isset($_POST['reservation_id']) || !is_numeric($_POST['reservation_id'])) {
            header('Location: admin.php?section=reservations&error=ID réservation invalide');
            exit;
        }

        $reservation_id = intval($_POST['reservation_id']);

        // Supprimer la réservation
        $query = "DELETE FROM reservations WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: admin.php?section=reservations&success=Réservation supprimée avec succès');
        } else {
            header('Location: admin.php?section=reservations&error=Erreur lors de la suppression de la réservation');
        }
        break;

    case 'update_reservation':
        // Vérification de la présence des champs requis
        if (!isset($_POST['reservation_id']) || !isset($_POST['statut']) ||
            !is_numeric($_POST['reservation_id']) ||
            !in_array($_POST['statut'], ['acceptée', 'refusée', 'annulée'])) {
            header('Location: admin.php?section=reservations&error=Paramètres invalides');
            exit;
        }

        $reservation_id = intval($_POST['reservation_id']);
        $statut = $_POST['statut'];

        // Récupérer les informations de la réservation
        $query_reservation = "SELECT r.*, l.titre as logement_titre, 
                             u.prenom as locataire_prenom, u.nom as locataire_nom, u.email as locataire_email,
                             up.prenom as proprietaire_prenom, up.nom as proprietaire_nom, up.email as proprietaire_email
                             FROM reservations r 
                             JOIN logements l ON r.logement_id = l.id 
                             JOIN users u ON r.user_id = u.id 
                             JOIN users up ON l.user_id = up.id 
                             WHERE r.id = ?";
        $stmt_reservation = mysqli_prepare($conn, $query_reservation);
        mysqli_stmt_bind_param($stmt_reservation, "i", $reservation_id);
        mysqli_stmt_execute($stmt_reservation);
        $result_reservation = mysqli_stmt_get_result($stmt_reservation);

        if (mysqli_num_rows($result_reservation) == 0) {
            header('Location: admin.php?section=reservations&error=Réservation introuvable');
            exit;
        }

        $reservation = mysqli_fetch_assoc($result_reservation);

        // Mettre à jour le statut de la réservation
        $query = "UPDATE reservations SET statut = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $statut, $reservation_id);

        if (mysqli_stmt_execute($stmt)) {
            // Préparer les messages pour les emails
            if ($statut == 'acceptée') {
                $email_subject_locataire = "Votre réservation a été acceptée - OmnesBnB";
                $email_message_intro = "Votre demande de réservation pour le logement \"" . $reservation['logement_titre'] . "\" a été acceptée.";
                $email_message_suite = "Vous pouvez désormais contacter le propriétaire pour organiser votre arrivée. Ses coordonnées sont visibles dans la section \"Mes réservations\" de votre compte OmnesBnB.";
            } else if ($statut == 'refusée') {
                $email_subject_locataire = "Votre réservation a été refusée - OmnesBnB";
                $email_message_intro = "Nous sommes désolés de vous informer que votre demande de réservation pour le logement \"" . $reservation['logement_titre'] . "\" a été refusée.";
                $email_message_suite = "Vous pouvez rechercher d'autres logements disponibles sur OmnesBnB.";
            } else {
                $email_subject_locataire = "Votre réservation a été annulée - OmnesBnB";
                $email_message_intro = "Nous vous informons que votre réservation pour le logement \"" . $reservation['logement_titre'] . "\" a été annulée par l'administrateur.";
                $email_message_suite = "Pour plus d'informations, veuillez contacter l'équipe OmnesBnB.";
            }

            // Envoi d'un email au locataire
            $email_body_locataire = "Bonjour " . $reservation['locataire_prenom'] . " " . $reservation['locataire_nom'] . ",\n\n";
            $email_body_locataire .= $email_message_intro . "\n\n";
            $email_body_locataire .= "Détails de votre réservation :\n";
            $email_body_locataire .= "- Dates : du " . date('d/m/Y', strtotime($reservation['date_arrivee'])) . " au " . date('d/m/Y', strtotime($reservation['date_depart'])) . "\n";
            $email_body_locataire .= "- Nombre de personnes : " . $reservation['nb_personnes'] . "\n";
            $email_body_locataire .= "- Montant total : " . $reservation['prix_total'] . " €\n\n";
            $email_body_locataire .= $email_message_suite . "\n\n";
            $email_body_locataire .= "Cordialement,\n";
            $email_body_locataire .= "L'équipe OmnesBnB";

            send_email($reservation['locataire_email'], $email_subject_locataire, $email_body_locataire);

            // Envoi d'un email au propriétaire
            $email_subject_proprietaire = "Mise à jour d'une réservation - OmnesBnB";
            $email_body_proprietaire = "Bonjour " . $reservation['proprietaire_prenom'] . " " . $reservation['proprietaire_nom'] . ",\n\n";
            $email_body_proprietaire .= "Un administrateur a modifié le statut d'une réservation pour votre logement \"" . $reservation['logement_titre'] . "\".\n\n";
            $email_body_proprietaire .= "La réservation de " . $reservation['locataire_prenom'] . " " . $reservation['locataire_nom'] . " est maintenant : " . $statut . ".\n\n";
            $email_body_proprietaire .= "Détails de la réservation :\n";
            $email_body_proprietaire .= "- Dates : du " . date('d/m/Y', strtotime($reservation['date_arrivee'])) . " au " . date('d/m/Y', strtotime($reservation['date_depart'])) . "\n";
            $email_body_proprietaire .= "- Nombre de personnes : " . $reservation['nb_personnes'] . "\n";
            $email_body_proprietaire .= "- Montant total : " . $reservation['prix_total'] . " €\n\n";
            $email_body_proprietaire .= "Pour plus d'informations, veuillez contacter l'équipe OmnesBnB.\n\n";
            $email_body_proprietaire .= "Cordialement,\n";
            $email_body_proprietaire .= "L'équipe OmnesBnB";

            send_email($reservation['proprietaire_email'], $email_subject_proprietaire, $email_body_proprietaire);

            header('Location: admin.php?section=reservations&success=Statut de la réservation mis à jour avec succès');
        } else {
            header('Location: admin.php?section=reservations&error=Erreur lors de la mise à jour du statut de la réservation');
        }
        break;

    default:
        header('Location: admin.php?error=Action non reconnue');
        break;
}

exit;
?>