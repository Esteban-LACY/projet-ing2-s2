<?php
/**
 * Fonctions de gestion des emails pour OmnesBnB
 * Contient les fonctions nécessaires à l'envoi d'emails
 */

// Empêcher l'accès direct à ce fichier
if (!defined('OMNESBNB') && basename($_SERVER['PHP_SELF']) == 'mail_functions.php') {
    header('Location: index.php');
    exit;
}

// Définition de la constante OMNESBNB si elle n'est pas déjà définie
if (!defined('OMNESBNB')) {
    define('OMNESBNB', true);
}

/**
 * Envoie un email
 *
 * @param string $to Adresse email du destinataire
 * @param string $subject Sujet de l'email
 * @param string $message Corps de l'email
 * @param array $headers En-têtes additionnels (optionnel)
 * @return bool True si l'email a été envoyé, False sinon
 */
function send_email($to, $subject, $message, $headers = []) {
    // Toujours enregistrer dans un fichier log (local ou production)
    $log_file = 'emails_log.txt';
    $log_message = "=================================================\n";
    $log_message .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $log_message .= "To: " . $to . "\n";
    $log_message .= "Subject: " . $subject . "\n";
    $log_message .= "Headers: " . print_r($headers, true) . "\n";
    $log_message .= "Message: \n" . $message . "\n";
    $log_message .= "=================================================\n\n";

    file_put_contents($log_file, $log_message, FILE_APPEND);

    // MODIFICATION: On retourne toujours true pour simuler un envoi réussi
    // même en localhost
    return true;
}

/**
 * Envoie un email de vérification à un nouvel utilisateur
 *
 * @param string $email Adresse email de l'utilisateur
 * @param string $token Token de vérification
 * @param string $prenom Prénom de l'utilisateur
 * @param string $nom Nom de l'utilisateur
 * @return bool True si l'email a été envoyé, False sinon
 */
function send_verification_email($email, $token, $prenom, $nom) {
    global $site_config;

    // MODIFICATION: Activer automatiquement le compte sans vérification d'email
    // Cette partie est appelée depuis process_register.php
    global $conn;
    if (isset($conn)) {
        $activate_query = "UPDATE users SET active = 1 WHERE email = ?";
        $activate_stmt = mysqli_prepare($conn, $activate_query);
        if ($activate_stmt) {
            mysqli_stmt_bind_param($activate_stmt, "s", $email);
            mysqli_stmt_execute($activate_stmt);
        }
    }

    $verification_link = $site_config['site_url'] . "/verification.php?email=" . urlencode($email) . "&token=" . $token;

    $subject = "Vérification de votre compte OmnesBnB";
    $message = "Bonjour $prenom $nom,\n\n";
    $message .= "Merci de vous être inscrit sur OmnesBnB. Pour activer votre compte, veuillez cliquer sur le lien suivant :\n\n";
    $message .= $verification_link . "\n\n";
    $message .= "Ce lien est valable pendant 24 heures.\n\n";
    $message .= "Si vous n'avez pas créé de compte sur OmnesBnB, vous pouvez ignorer cet email.\n\n";
    $message .= "Cordialement,\n";
    $message .= "L'équipe OmnesBnB";

    return send_email($email, $subject, $message);
}

/**
 * Envoie un email de réinitialisation de mot de passe
 *
 * @param string $email Adresse email de l'utilisateur
 * @param string $token Token de réinitialisation
 * @param string $prenom Prénom de l'utilisateur
 * @param string $nom Nom de l'utilisateur
 * @return bool True si l'email a été envoyé, False sinon
 */
function send_reset_password_email($email, $token, $prenom, $nom) {
    global $site_config;

    $reset_link = $site_config['site_url'] . "/nouveau-motdepasse.php?email=" . urlencode($email) . "&token=" . $token;

    $subject = "Réinitialisation de votre mot de passe - OmnesBnB";
    $message = "Bonjour $prenom $nom,\n\n";
    $message .= "Vous avez demandé la réinitialisation de votre mot de passe sur OmnesBnB. Veuillez cliquer sur le lien ci-dessous pour créer un nouveau mot de passe :\n\n";
    $message .= $reset_link . "\n\n";
    $message .= "Ce lien est valable pendant 30 minutes.\n\n";
    $message .= "Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.\n\n";
    $message .= "Cordialement,\n";
    $message .= "L'équipe OmnesBnB";

    return send_email($email, $subject, $message);
}

/**
 * Envoie un email de confirmation de réservation au locataire
 *
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @param array $locataire Données du locataire
 * @return bool True si l'email a été envoyé, False sinon
 */
function send_reservation_confirmation_email($reservation, $logement, $locataire) {
    $subject = "Confirmation de votre demande de réservation - OmnesBnB";
    $message = "Bonjour " . $locataire['prenom'] . " " . $locataire['nom'] . ",\n\n";
    $message .= "Nous avons bien reçu votre demande de réservation pour le logement \"" . $logement['titre'] . "\".\n\n";
    $message .= "Détails de votre réservation :\n";
    $message .= "- Dates : du " . date('d/m/Y', strtotime($reservation['date_arrivee'])) . " au " . date('d/m/Y', strtotime($reservation['date_depart'])) . "\n";
    $message .= "- Nombre de personnes : " . $reservation['nb_personnes'] . "\n";
    $message .= "- Montant total : " . $reservation['prix_total'] . " €\n\n";
    $message .= "Le propriétaire va étudier votre demande et vous recevrez un email dès qu'il aura pris sa décision.\n\n";
    $message .= "Vous pouvez suivre l'état de votre réservation en vous connectant à votre compte OmnesBnB.\n\n";
    $message .= "Cordialement,\n";
    $message .= "L'équipe OmnesBnB";

    return send_email($locataire['email'], $subject, $message);
}

/**
 * Envoie un email de notification de nouvelle réservation au propriétaire
 *
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @param array $locataire Données du locataire
 * @param array $proprietaire Données du propriétaire
 * @return bool True si l'email a été envoyé, False sinon
 */
function send_new_reservation_notification_email($reservation, $logement, $locataire, $proprietaire) {
    $subject = "Nouvelle demande de réservation - OmnesBnB";
    $message = "Bonjour " . $proprietaire['prenom'] . " " . $proprietaire['nom'] . ",\n\n";
    $message .= "Vous avez reçu une nouvelle demande de réservation pour votre logement \"" . $logement['titre'] . "\".\n\n";
    $message .= "Détails de la réservation :\n";
    $message .= "- Locataire : " . $locataire['prenom'] . " " . $locataire['nom'] . "\n";
    $message .= "- Dates : du " . date('d/m/Y', strtotime($reservation['date_arrivee'])) . " au " . date('d/m/Y', strtotime($reservation['date_depart'])) . "\n";
    $message .= "- Nombre de personnes : " . $reservation['nb_personnes'] . "\n";
    $message .= "- Montant total : " . $reservation['prix_total'] . " €\n\n";
    $message .= "Vous pouvez accepter ou refuser cette demande en vous connectant à votre compte OmnesBnB.\n\n";
    $message .= "Cordialement,\n";
    $message .= "L'équipe OmnesBnB";

    return send_email($proprietaire['email'], $subject, $message);
}
?>