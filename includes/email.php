<?php
require_once '../config/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envoie un email
 * @param string $destinataire Email du destinataire
 * @param string $sujet Sujet de l'email
 * @param string $message Corps du message
 * @return bool Succès de l'envoi
 */
function envoyerEmail($destinataire, $sujet, $message) {
    // Utilisation de PHPMailer pour l'envoi d'emails
    require ROOT_PATH . 'vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // À configurer avec un serveur SMTP réel
        $mail->SMTPAuth = true;
        $mail->Username = 'user@example.com'; // À configurer
        $mail->Password = 'secret'; // À configurer
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Destinataires
        $mail->setFrom(EMAIL_FROM, APP_NAME);
        $mail->addAddress($destinataire);
        $mail->addReplyTo(EMAIL_REPLY_TO, APP_NAME);
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // En développement, logger l'erreur plutôt que de l'afficher
        error_log('Erreur d\'envoi d\'email: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envoie un email de vérification
 * @param string $email Email du destinataire
 * @param string $token Token de vérification
 * @param string $nom Nom de l'utilisateur
 * @return bool Succès de l'envoi
 */
function envoyerEmailVerification($email, $token, $nom) {
    $lienVerification = APP_URL . '/verification.php?email=' . urlencode($email) . '&token=' . $token;
    
    $sujet = APP_NAME . ' - Vérification de votre adresse email';
    
    $message = '
    <html>
    <head>
        <title>Vérification de votre adresse email</title>
    </head>
    <body>
        <p>Bonjour ' . $nom . ',</p>
        <p>Merci de vous être inscrit sur ' . APP_NAME . '. Pour finaliser votre inscription, veuillez cliquer sur le lien ci-dessous pour vérifier votre adresse email :</p>
        <p><a href="' . $lienVerification . '">' . $lienVerification . '</a></p>
        <p>Si vous n\'avez pas demandé cette inscription, vous pouvez ignorer cet email.</p>
        <p>Cordialement,<br>L\'équipe ' . APP_NAME . '</p>
    </body>
    </html>';
    
    return envoyerEmail($email, $sujet, $message);
}
?>
