<?php
/**
 * Fonctions d'envoi d'emails
 * 
 * Ce fichier contient des fonctions pour l'envoi d'emails
 * 
 * @author OmnesBnB
 */

/**
 * Envoie un email
 * 
 * @param string $destinataire Adresse email du destinataire
 * @param string $sujet Sujet de l'email
 * @param string $message Corps de l'email
 * @param string $expediteur Adresse email de l'expéditeur (optionnel)
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmail($destinataire, $sujet, $message, $expediteur = null) {
    // Si aucun expéditeur n'est spécifié, utiliser l'adresse par défaut
    if ($expediteur === null) {
        $expediteur = EMAIL_NOREPLY;
    }
    
    // En-têtes de l'email
    $entetes = [
        'From' => $expediteur,
        'Reply-To' => $expediteur,
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8'
    ];
    
    // Conversion des en-têtes au format approprié
    $entetesString = '';
    
    foreach ($entetes as $nom => $valeur) {
        $entetesString .= "$nom: $valeur\r\n";
    }
    
    // En mode développement, on peut se contenter de journaliser l'email
    if (MODE_DEVELOPPEMENT) {
        journaliser("Email envoyé à $destinataire - Sujet: $sujet - Message: $message", 'INFO');
        return true;
    }
    
    // Envoi de l'email
    return mail($destinataire, $sujet, $message, $entetesString);
}

/**
 * Envoie un email de confirmation d'inscription
 * 
 * @param string $destinataire Adresse email du destinataire
 * @param string $prenom Prénom de l'utilisateur
 * @param string $nom Nom de l'utilisateur
 * @param string $token Token de vérification
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailConfirmation($destinataire, $prenom, $nom, $token) {
    $sujet = 'Confirmation de votre compte OmnesBnB';
    $lienVerification = URL_SITE . '/verifier-email.php?token=' . $token;
    
    $message = "Bonjour $prenom $nom,\n\n";
    $message .= "Merci de vous être inscrit sur OmnesBnB. Pour confirmer votre adresse email, veuillez cliquer sur le lien suivant :\n\n";
    $message .= "$lienVerification\n\n";
    $message .= "Si vous n'avez pas créé de compte sur OmnesBnB, veuillez ignorer cet email.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    return envoyerEmail($destinataire, $sujet, $message);
}

/**
 * Envoie un email de réinitialisation de mot de passe
 * 
 * @param string $destinataire Adresse email du destinataire
 * @param string $prenom Prénom de l'utilisateur
 * @param string $nom Nom de l'utilisateur
 * @param string $token Token de réinitialisation
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailReinitialisationMotDePasse($destinataire, $prenom, $nom, $token) {
    $sujet = 'Réinitialisation de votre mot de passe OmnesBnB';
    $lienReinitialisation = URL_SITE . '/reinitialiser-mot-de-passe.php?token=' . $token;
    
    $message = "Bonjour $prenom $nom,\n\n";
    $message .= "Vous avez demandé la réinitialisation de votre mot de passe sur OmnesBnB. Pour créer un nouveau mot de passe, veuillez cliquer sur le lien suivant :\n\n";
    $message .= "$lienReinitialisation\n\n";
    $message .= "Ce lien expirera dans 24 heures. Si vous n'avez pas demandé la réinitialisation de votre mot de passe, veuillez ignorer cet email.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    return envoyerEmail($destinataire, $sujet, $message);
}

/**
 * Envoie un email de confirmation de réservation au locataire
 * 
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @param array $locataire Données du locataire
 * @param array $proprietaire Données du propriétaire
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailConfirmationReservationLocataire($reservation, $logement, $locataire, $proprietaire) {
    $sujet = 'Confirmation de réservation - ' . $logement['titre'];
    
    $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
    $message .= "Votre réservation pour le logement \"{$logement['titre']}\" a été confirmée.\n\n";
    $message .= "Détails de la réservation :\n";
    $message .= "- Adresse : {$logement['adresse']}, {$logement['code_postal']} {$logement['ville']}\n";
    $message .= "- Période : du " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n";
    $message .= "- Montant total : " . formaterPrix($reservation['prix_total']) . "\n\n";
    $message .= "Informations du propriétaire :\n";
    $message .= "- Nom : {$proprietaire['prenom']} {$proprietaire['nom']}\n";
    $message .= "- Téléphone : {$proprietaire['telephone']}\n";
    $message .= "- Email : {$proprietaire['email']}\n\n";
    $message .= "Nous vous souhaitons un agréable séjour !\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    return envoyerEmail($locataire['email'], $sujet, $message);
}

/**
 * Envoie un email de confirmation de réservation au propriétaire
 * 
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @param array $locataire Données du locataire
 * @param array $proprietaire Données du propriétaire
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailConfirmationReservationProprietaire($reservation, $logement, $locataire, $proprietaire) {
    $sujet = 'Nouvelle réservation confirmée - ' . $logement['titre'];
    
    $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
    $message .= "Une réservation pour votre logement \"{$logement['titre']}\" a été confirmée.\n\n";
    $message .= "Détails de la réservation :\n";
    $message .= "- Période : du " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n";
    $message .= "- Montant total : " . formaterPrix($reservation['prix_total']) . "\n\n";
    $message .= "Informations du locataire :\n";
    $message .= "- Nom : {$locataire['prenom']} {$locataire['nom']}\n";
    $message .= "- Téléphone : {$locataire['telephone']}\n";
    $message .= "- Email : {$locataire['email']}\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    return envoyerEmail($proprietaire['email'], $sujet, $message);
}

/**
 * Envoie un email de notification de nouvelle réservation au propriétaire
 * 
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @param array $locataire Données du locataire
 * @param array $proprietaire Données du propriétaire
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailNouvelleReservation($reservation, $logement, $locataire, $proprietaire) {
    $sujet = 'Nouvelle réservation en attente - ' . $logement['titre'];
    
    $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
    $message .= "Vous avez reçu une nouvelle demande de réservation pour votre logement \"{$logement['titre']}\".\n\n";
    $message .= "Détails de la réservation :\n";
    $message .= "- Période : du " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n";
    $message .= "- Montant total : " . formaterPrix($reservation['prix_total']) . "\n\n";
    $message .= "Informations du locataire :\n";
    $message .= "- Nom : {$locataire['prenom']} {$locataire['nom']}\n";
    $message .= "- Email : {$locataire['email']}\n\n";
    $message .= "Veuillez vous connecter à votre compte pour accepter ou refuser cette réservation.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    return envoyerEmail($proprietaire['email'], $sujet, $message);
}

/**
 * Envoie un email de notification de paiement réussi au locataire
 * 
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @param array $locataire Données du locataire
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailPaiementReussi($reservation, $logement, $locataire) {
    $sujet = 'Paiement confirmé - ' . $logement['titre'];
    
    $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
    $message .= "Nous vous confirmons que votre paiement pour la réservation du logement \"{$logement['titre']}\" a bien été effectué.\n\n";
    $message .= "Détails de la réservation :\n";
    $message .= "- Période : du " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n";
    $message .= "- Montant payé : " . formaterPrix($reservation['prix_total']) . "\n\n";
    $message .= "Votre réservation est en attente de confirmation par le propriétaire. Vous recevrez une notification dès que celle-ci sera acceptée.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    return envoyerEmail($locataire['email'], $sujet, $message);
}

/**
 * Envoie un email de notification d'annulation de réservation
 * 
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @param array $destinataire Données du destinataire
 * @param string $role Rôle du destinataire ('locataire' ou 'proprietaire')
 * @param string $raisonAnnulation Raison de l'annulation
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailAnnulationReservation($reservation, $logement, $destinataire, $role, $raisonAnnulation = '') {
    $sujet = 'Annulation de réservation - ' . $logement['titre'];
    
    $message = "Bonjour {$destinataire['prenom']} {$destinataire['nom']},\n\n";
    
    if ($role === 'locataire') {
        $message .= "Nous vous informons que votre réservation pour le logement \"{$logement['titre']}\" a été annulée";
    } else {
        $message .= "Nous vous informons qu'une réservation pour votre logement \"{$logement['titre']}\" a été annulée";
    }
    
    if (!empty($raisonAnnulation)) {
        $message .= " pour la raison suivante : $raisonAnnulation";
    }
    
    $message .= ".\n\n";
    $message .= "Détails de la réservation :\n";
    $message .= "- Période : du " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n";
    
    if ($role === 'locataire') {
        $message .= "- Montant remboursé : " . formaterPrix($reservation['prix_total']) . "\n\n";
        $message .= "Le remboursement sera effectué sur votre compte bancaire dans les prochains jours.\n\n";
    }
    
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    return envoyerEmail($destinataire['email'], $sujet, $message);
}

/**
 * Envoie un email de bienvenue après confirmation de l'email
 * 
 * @param string $destinataire Adresse email du destinataire
 * @param string $prenom Prénom de l'utilisateur
 * @param string $nom Nom de l'utilisateur
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailBienvenue($destinataire, $prenom, $nom) {
    $sujet = 'Bienvenue sur OmnesBnB !';
    
    $message = "Bonjour $prenom $nom,\n\n";
    $message .= "Votre compte OmnesBnB a été confirmé avec succès. Nous sommes ravis de vous compter parmi nos utilisateurs !\n\n";
    $message .= "Vous pouvez dès maintenant rechercher des logements ou publier le vôtre en vous connectant sur notre site :\n";
    $message .= URL_SITE . "\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    return envoyerEmail($destinataire, $sujet, $message);
}

/**
 * Envoie un email de contact
 * 
 * @param string $nom Nom de l'expéditeur
 * @param string $email Email de l'expéditeur
 * @param string $sujet Sujet du message
 * @param string $message Corps du message
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailContact($nom, $email, $sujet, $message) {
    $sujetComplet = "Message de contact - $sujet";
    
    $messageComplet = "Message de contact envoyé par $nom ($email) :\n\n";
    $messageComplet .= $message;
    
    return envoyerEmail(EMAIL_CONTACT, $sujetComplet, $messageComplet, $email);
}

/**
 * Envoie un email de notification à l'administrateur
 * 
 * @param string $sujet Sujet du message
 * @param string $message Corps du message
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerEmailAdmin($sujet, $message) {
    return envoyerEmail(EMAIL_ADMINISTRATEUR, $sujet, $message);
}
?>
