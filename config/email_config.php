<?php
/**
 * Configuration pour l'envoi d'emails
 */

// Configuration du serveur SMTP
define('SMTP_HOST', 'smtp.example.com'); // À remplacer par votre serveur SMTP
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls ou ssl
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'user@example.com'); // À remplacer par votre email
define('SMTP_PASSWORD', 'password'); // À remplacer par votre mot de passe

// Configuration de l'expéditeur
define('EMAIL_FROM', 'noreply@omnesbnb.fr');
define('EMAIL_FROM_NAME', APP_NAME);
define('EMAIL_REPLY_TO', 'contact@omnesbnb.fr');

// Types d'emails
define('EMAIL_VERIFICATION_SUBJECT', 'Vérification de votre adresse email');
define('EMAIL_RESERVATION_SUBJECT', 'Confirmation de votre réservation');
define('EMAIL_CONFIRMATION_BAILLEUR_SUBJECT', 'Nouvelle réservation pour votre logement');
define('EMAIL_ANNULATION_SUBJECT', 'Annulation de réservation');
define('EMAIL_PAIEMENT_SUBJECT', 'Confirmation de paiement');

/**
 * Renvoie le template HTML pour les emails
 * @param string $titre Titre de l'email
 * @param string $contenu Contenu de l'email
 * @return string Template HTML complet
 */
function getEmailTemplate($titre, $contenu) {
    return '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $titre . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #ffffff;
            }
            .header {
                text-align: center;
                padding: 20px 0;
                border-bottom: 1px solid #eeeeee;
            }
            .content {
                padding: 20px 0;
            }
            .footer {
                text-align: center;
                padding: 20px 0;
                font-size: 12px;
                color: #888888;
                border-top: 1px solid #eeeeee;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background-color: #000000;
                color: #ffffff;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . APP_NAME . '</h1>
            </div>
            <div class="content">
                <h2>' . $titre . '</h2>
                ' . $contenu . '
            </div>
            <div class="footer">
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                <p>&copy; ' . date('Y') . ' ' . APP_NAME . '. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * Génère le contenu de l'email de vérification
 * @param string $prenom Prénom de l'utilisateur
 * @param string $lienVerification Lien de vérification
 * @return string Contenu de l'email
 */
function getEmailVerificationContent($prenom, $lienVerification) {
    return '
    <p>Bonjour ' . $prenom . ',</p>
    <p>Merci de vous être inscrit sur ' . APP_NAME . '. Pour finaliser votre inscription, veuillez cliquer sur le lien ci-dessous pour vérifier votre adresse email :</p>
    <p style="text-align: center;">
        <a href="' . $lienVerification . '" class="btn">Vérifier mon email</a>
    </p>
    <p>Ou copiez et collez le lien suivant dans votre navigateur :</p>
    <p>' . $lienVerification . '</p>
    <p>Si vous n\'avez pas demandé cette inscription, vous pouvez ignorer cet email.</p>
    <p>Cordialement,<br>L\'équipe ' . APP_NAME . '</p>
    ';
}

/**
 * Génère le contenu de l'email de confirmation de réservation
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @param array $proprietaire Données du propriétaire
 * @return string Contenu de l'email
 */
function getEmailReservationContent($reservation, $logement, $proprietaire) {
    return '
    <p>Bonjour ' . $reservation['prenom'] . ',</p>
    <p>Votre réservation pour le logement <strong>"' . $logement['titre'] . '"</strong> a été confirmée.</p>
    <p><strong>Détails de la réservation :</strong></p>
    <ul>
        <li>Dates : du ' . date('d/m/Y', strtotime($reservation['date_debut'])) . ' au ' . date('d/m/Y', strtotime($reservation['date_fin'])) . '</li>
        <li>Adresse : ' . $logement['adresse'] . ', ' . $logement['code_postal'] . ' ' . $logement['ville'] . '</li>
        <li>Prix total : ' . number_format($reservation['prix_total'], 2, ',', ' ') . ' €</li>
    </ul>
    <p><strong>Coordonnées du propriétaire :</strong></p>
    <ul>
        <li>' . $proprietaire['prenom'] . ' ' . $proprietaire['nom'] . '</li>
        <li>Téléphone : ' . $proprietaire['telephone'] . '</li>
        <li>Email : ' . $proprietaire['email'] . '</li>
    </ul>
    <p>Vous pouvez consulter les détails de votre réservation à tout moment sur votre <a href="' . APP_URL . '/profil.php">page de profil</a>.</p>
    <p>Nous vous souhaitons un agréable séjour !</p>
    <p>Cordialement,<br>L\'équipe ' . APP_NAME . '</p>
    ';
}
?>
