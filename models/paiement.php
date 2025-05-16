<?php
/**
 * Modèle pour la gestion des paiements
 * 
 * Ce fichier contient les fonctions d'accès aux données des paiements
 * 
 * @author OmnesBnB
 */

// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';

/**
 * Crée un nouveau paiement
 * 
 * @param array $donnees Données du paiement
 * @return int|false ID du paiement créé ou false en cas d'erreur
 */
function creerPaiement($donnees) {
    $sql = "INSERT INTO paiements (id_reservation, montant, id_transaction, statut, date_paiement)
            VALUES (:id_reservation, :montant, :id_transaction, :statut, NOW())";
    
    $params = [
        ':id_reservation' => $donnees['id_reservation'],
        ':montant' => $donnees['montant'],
        ':id_transaction' => isset($donnees['id_transaction']) ? $donnees['id_transaction'] : null,
        ':statut' => isset($donnees['statut']) ? $donnees['statut'] : 'en_attente'
    ];
    
    return executerRequete($sql, $params);
}

/**
 * Vérifie si l'utilisateur est autorisé à payer une réservation
 * 
 * @param int $idReservation ID de la réservation
 * @return bool True si l'utilisateur est autorisé, false sinon
 */
function estUtilisateurAutoriseAPayer($idReservation) {
    // Récupérer la réservation
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        return false;
    }
    
    // Vérifier que l'utilisateur est le locataire
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    if ($reservation['id_locataire'] != $idUtilisateur) {
        return false;
    }
    
    // Vérifier que la réservation est en attente
    if ($reservation['statut'] != 'en_attente') {
        return false;
    }
    
    return true;
}

/**
 * Prépare les données d'une réservation pour le paiement
 * 
 * @param int $idReservation ID de la réservation
 * @return array|false Données de la réservation ou false en cas d'erreur
 */
function preparerDonneesReservationPourPaiement($idReservation) {
    // Récupérer la réservation
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        return false;
    }
    
    // Récupérer le logement
    $logement = recupererLogementParId($reservation['id_logement']);
    
    if (!$logement) {
        return false;
    }
    
    // Préparer les données pour la session de paiement
    return [
        'id' => $idReservation,
        'id_logement' => $reservation['id_logement'],
        'id_locataire' => $reservation['id_locataire'],
        'date_debut' => $reservation['date_debut'],
        'date_fin' => $reservation['date_fin'],
        'prix_total' => $reservation['prix_total'],
        'titre_logement' => $logement['titre']
    ];
}

/**
 * Enregistre une session de paiement pour une réservation
 * 
 * @param int $idReservation ID de la réservation
 * @param string $sessionId ID de la session Stripe
 * @param float $montant Montant du paiement
 * @return bool True si l'enregistrement a réussi, false sinon
 */
function enregistrerSessionPaiement($idReservation, $sessionId, $montant) {
    // Vérifier si un paiement existe déjà pour cette réservation
    $paiementExistant = recupererPaiementParReservation($idReservation);
    
    if ($paiementExistant) {
        // Mettre à jour le paiement existant
        $sql = "UPDATE paiements SET id_transaction = :id_transaction, montant = :montant, statut = 'en_attente', date_paiement = NOW() WHERE id = :id";
        $params = [
            ':id' => $paiementExistant['id'],
            ':id_transaction' => $sessionId,
            ':montant' => $montant
        ];
        
        return executerRequete($sql, $params) !== false;
    } else {
        // Créer un nouveau paiement
        $donnees = [
            'id_reservation' => $idReservation,
            'montant' => $montant,
            'id_transaction' => $sessionId,
            'statut' => 'en_attente'
        ];
        
        return creerPaiement($donnees) !== false;
    }
}

/**
 * Traite la confirmation d'un paiement
 * 
 * @param string $sessionId ID de la session Stripe
 * @return array Résultat du traitement
 */
function traiterConfirmationPaiement($sessionId) {
    // Récupérer la session Stripe
    $session = recupererSessionPaiement($sessionId);
    
    if (!$session) {
        return ['success' => false, 'message' => 'Session de paiement invalide'];
    }
    
    // Récupérer la réservation associée à la session
    $idReservation = $session->client_reference_id;
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        return ['success' => false, 'message' => 'Réservation non trouvée'];
    }
    
    // Vérifier le statut du paiement
    if ($session->payment_status === 'paid') {
        // Mettre à jour le statut du paiement
        $paiement = recupererPaiementParTransaction($sessionId);
        
        if ($paiement) {
            // Mettre à jour le statut du paiement
            modifierStatutPaiement($paiement['id'], 'complete');
            
            // Si la réservation est toujours en attente, la mettre à jour
            if ($reservation['statut'] === 'en_attente') {
                // Pour les logements de type "entier" ou "libere", la réservation est automatiquement acceptée
                $logement = recupererLogementParId($reservation['id_logement']);
                
                if ($logement['type_logement'] === 'entier' || $logement['type_logement'] === 'libere') {
                    // Accepter automatiquement la réservation
                    confirmerReservation($idReservation);
                    
                    // Envoyer les notifications
                    envoyerNotificationsReservationConfirmee($reservation, $logement);
                } else {
                    // Pour les collocations, le propriétaire doit valider manuellement
                    envoyerNotificationsReservationEnAttente($reservation, $logement);
                }
            }
        }
        
        return ['success' => true, 'id_reservation' => $idReservation];
    } else {
        return ['success' => false, 'message' => 'Le paiement n\'a pas été validé'];
    }
}

/**
 * Marque un paiement comme échoué
 * 
 * @param string $sessionId ID de la session Stripe
 * @return bool True si la mise à jour a réussi, false sinon
 */
function marquerPaiementCommeEchoue($sessionId) {
    // Récupérer le paiement associé à la session
    $paiement = recupererPaiementParTransaction($sessionId);
    
    if ($paiement) {
        // Mettre à jour le statut du paiement
        return modifierStatutPaiement($paiement['id'], 'echoue');
    }
    
    return false;
}

/**
 * Traite les webhooks Stripe
 * 
 * @return array Résultat du traitement
 */
function traiterWebhookStripe() {
    // Récupérer le payload
    $payload = @file_get_contents('php://input');
    $sigHeader = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
    $event = null;
    
    try {
        // Vérifier la signature du webhook pour sécuriser
        require_once CHEMIN_RACINE . '/vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sigHeader, STRIPE_WEBHOOK_SECRET
        );
    } catch (\UnexpectedValueException $e) {
        // Payload invalide
        journaliser('Webhook Stripe - Payload invalide: ' . $e->getMessage(), 'ERROR');
        return ['status' => 'error', 'message' => 'Payload invalide'];
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Signature invalide
        journaliser('Webhook Stripe - Signature invalide: ' . $e->getMessage(), 'ERROR');
        return ['status' => 'error', 'message' => 'Signature invalide'];
    }
    
    // Traiter l'événement
    try {
        switch ($event->type) {
            case 'checkout.session.completed':
                processCheckoutSessionCompleted($event->data->object);
                break;
                
            case 'charge.refunded':
                processChargeRefunded($event->data->object);
                break;
                
            case 'payment_intent.succeeded':
                processPaymentIntentSucceeded($event->data->object);
                break;
                
            case 'payment_intent.payment_failed':
                processPaymentIntentFailed($event->data->object);
                break;
                
            default:
                // Événement non traité
                journaliser('Webhook Stripe - Événement non traité: ' . $event->type, 'INFO');
                break;
        }
        
        return ['status' => 'success'];
    } catch (Exception $e) {
        // Erreur lors du traitement de l'événement
        journaliser('Webhook Stripe - Erreur: ' . $e->getMessage(), 'ERROR');
        return ['status' => 'error', 'message' => 'Erreur lors du traitement de l\'événement'];
    }
}

/**
 * Envoie les notifications quand une réservation est confirmée automatiquement
 * 
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @return void
 */
function envoyerNotificationsReservationConfirmee($reservation, $logement) {
    // Récupérer les utilisateurs
    $locataire = recupererUtilisateurParId($reservation['id_locataire']);
    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
    
    // Notification au locataire
    $sujet = 'Confirmation de réservation - ' . $logement['titre'];
    $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
    $message .= "Votre réservation pour le logement \"{$logement['titre']}\" a été automatiquement confirmée suite à votre paiement.\n";
    $message .= "Dates: " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n\n";
    $message .= "Vous pouvez contacter le propriétaire au {$proprietaire['telephone']}.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    envoyerEmail($locataire['email'], $sujet, $message);
    
    // Notification au propriétaire
    $sujet = 'Nouvelle réservation confirmée - ' . $logement['titre'];
    $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
    $message .= "Une réservation pour votre logement \"{$logement['titre']}\" a été automatiquement confirmée suite au paiement.\n";
    $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
    $message .= "Dates: " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n";
    $message .= "Montant: {$reservation['prix_total']} €\n\n";
    $message .= "Vous pouvez contacter le locataire au {$locataire['telephone']}.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    envoyerEmail($proprietaire['email'], $sujet, $message);
}

/**
 * Envoie les notifications quand une réservation est en attente de confirmation
 * 
 * @param array $reservation Données de la réservation
 * @param array $logement Données du logement
 * @return void
 */
function envoyerNotificationsReservationEnAttente($reservation, $logement) {
    // Récupérer les utilisateurs
    $locataire = recupererUtilisateurParId($reservation['id_locataire']);
    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
    
    // Notification au propriétaire
    $sujet = 'Nouvelle réservation en attente - ' . $logement['titre'];
    $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
    $message .= "Vous avez reçu une nouvelle réservation pour votre logement \"{$logement['titre']}\".\n";
    $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
    $message .= "Dates: " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n";
    $message .= "Montant: {$reservation['prix_total']} €\n\n";
    $message .= "Le paiement a été effectué. Veuillez vous connecter à votre compte pour accepter ou refuser cette réservation.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    envoyerEmail($proprietaire['email'], $sujet, $message);
    
    // Notification au locataire
    $sujet = 'Réservation en attente de confirmation - ' . $logement['titre'];
    $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
    $message .= "Votre paiement pour le logement \"{$logement['titre']}\" a été effectué avec succès.\n";
    $message .= "Dates: " . formaterDate($reservation['date_debut']) . " au " . formaterDate($reservation['date_fin']) . "\n\n";
    $message .= "Votre réservation est maintenant en attente de confirmation par le propriétaire. Vous serez notifié dès que celle-ci sera confirmée.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    envoyerEmail($locataire['email'], $sujet, $message);
}

/**
 * Récupère un paiement par son ID
 * 
 * @param int $id ID du paiement
 * @return array|false Données du paiement ou false si non trouvé
 */
function recupererPaiementParId($id) {
    $sql = "SELECT * FROM paiements WHERE id = :id";
    $params = [':id' => $id];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Récupère un paiement par ID de réservation
 * 
 * @param int $idReservation ID de la réservation
 * @return array|false Données du paiement ou false si non trouvé
 */
function recupererPaiementParReservation($idReservation) {
    $sql = "SELECT * FROM paiements WHERE id_reservation = :id_reservation ORDER BY date_paiement DESC";
    $params = [':id_reservation' => $idReservation];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Récupère un paiement par ID de transaction
 * 
 * @param string $idTransaction ID de transaction
 * @return array|false Données du paiement ou false si non trouvé
 */
function recupererPaiementParTransaction($idTransaction) {
    $sql = "SELECT * FROM paiements WHERE id_transaction = :id_transaction";
    $params = [':id_transaction' => $idTransaction];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Modifie le statut d'un paiement
 * 
 * @param int $idPaiement ID du paiement
 * @param string $statut Nouveau statut
 * @return bool True si la mise à jour a réussi, false sinon
 */
function modifierStatutPaiement($idPaiement, $statut) {
    $sql = "UPDATE paiements SET statut = :statut WHERE id = :id";
    $params = [
        ':id' => $idPaiement,
        ':statut' => $statut
    ];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Rembourse un paiement
 * 
 * @param int $idPaiement ID du paiement
 * @return bool True si le remboursement a réussi, false sinon
 */
function rembourserPaiement($idPaiement) {
    return modifierStatutPaiement($idPaiement, 'rembourse');
}

/**
 * Calcule le montant total des paiements complétés
 * 
 * @return float Montant total des paiements
 */
function calculerMontantTotalPaiements() {
    $sql = "SELECT SUM(montant) as total FROM paiements WHERE statut = 'complete'";
    
    $resultat = executerRequete($sql);
    
    return is_array($resultat) && isset($resultat[0]['total']) ? floatval($resultat[0]['total']) : 0;
}

/**
 * Calcule le montant total des frais de service
 * 
 * @return float Montant total des frais de service
 */
function calculerMontantTotalFraisService() {
    $sql = "SELECT SUM(r.prix_total * " . (FRAIS_SERVICE_POURCENTAGE / 100) . ") as total 
            FROM paiements p 
            JOIN reservations r ON p.id_reservation = r.id 
            WHERE p.statut = 'complete'";
    
    $resultat = executerRequete($sql);
    
    return is_array($resultat) && isset($resultat[0]['total']) ? floatval($resultat[0]['total']) : 0;
}

/**
 * Formate une date pour l'affichage
 * 
 * @param string $date Date au format Y-m-d
 * @return string Date au format d/m/Y
 */
function formaterDate($date) {
    return date("d/m/Y", strtotime($date));
}
?>
