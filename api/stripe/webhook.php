<?php
/**
 * Point d'entrée pour les webhooks Stripe
 * 
 * Ce fichier traite les événements envoyés par Stripe via webhook
 * 
 * @author OmnesBnB
 */

// Inclure la configuration et les modèles nécessaires
require_once __DIR__ . '/../../config/config.php';
require_once CHEMIN_MODELES . '/paiement.php';
require_once CHEMIN_MODELES . '/reservation.php';
require_once CHEMIN_MODELES . '/logement.php';
require_once CHEMIN_INCLUDES . '/email.php';

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

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
    http_response_code(400);
    echo json_encode(['error' => 'Payload invalide']);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Signature invalide
    journaliser('Webhook Stripe - Signature invalide: ' . $e->getMessage(), 'ERROR');
    http_response_code(400);
    echo json_encode(['error' => 'Signature invalide']);
    exit;
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
    
    // Répondre avec succès
    http_response_code(200);
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    // Erreur lors du traitement de l'événement
    journaliser('Webhook Stripe - Erreur: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors du traitement de l\'événement']);
}

/**
 * Traite l'événement checkout.session.completed
 * 
 * @param \Stripe\Checkout\Session $session Session Stripe
 * @return void
 */
function processCheckoutSessionCompleted($session) {
    // Récupérer l'ID de la réservation
    $idReservation = $session->client_reference_id;
    
    if (!$idReservation) {
        journaliser('Webhook Stripe - ID de réservation manquant', 'ERROR');
        return;
    }
    
    // Récupérer la réservation
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        journaliser('Webhook Stripe - Réservation non trouvée: ' . $idReservation, 'ERROR');
        return;
    }
    
    // Récupérer le paiement associé à la réservation
    $paiement = recupererPaiementParReservation($idReservation);
    
    if (!$paiement) {
        journaliser('Webhook Stripe - Paiement non trouvé pour la réservation: ' . $idReservation, 'ERROR');
        return;
    }
    
    // Mettre à jour le statut du paiement
    if ($session->payment_status === 'paid') {
        modifierStatutPaiement($paiement['id'], 'complete');
        
        // Récupérer le logement et les utilisateurs
        $logement = recupererLogementParId($reservation['id_logement']);
        $locataire = recupererUtilisateurParId($reservation['id_locataire']);
        
        // Si la réservation est toujours en attente, déterminer l'action à suivre
        if ($reservation['statut'] === 'en_attente') {
            // Pour les logements de type "entier" ou "libere", la réservation est automatiquement acceptée
            if ($logement['type_logement'] === 'entier' || $logement['type_logement'] === 'libere') {
                confirmerReservation($idReservation);
                
                // Envoyer les notifications
                $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                
                envoyerEmailConfirmationReservationLocataire($reservation, $logement, $locataire, $proprietaire);
                envoyerEmailConfirmationReservationProprietaire($reservation, $logement, $locataire, $proprietaire);
            } else {
                // Pour les collocations, le propriétaire doit valider manuellement
                $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                
                envoyerEmailNouvelleReservation($reservation, $logement, $locataire, $proprietaire);
                envoyerEmailPaiementReussi($reservation, $logement, $locataire);
            }
        }
        
        journaliser('Webhook Stripe - Paiement complété pour la réservation: ' . $idReservation, 'INFO');
    } else {
        journaliser('Webhook Stripe - Paiement non complété pour la réservation: ' . $idReservation . ' - Statut: ' . $session->payment_status, 'WARNING');
    }
}

/**
 * Traite l'événement charge.refunded
 * 
 * @param \Stripe\Charge $charge Charge Stripe
 * @return void
 */
function processChargeRefunded($charge) {
    // Récupérer le payment intent
    $paymentIntentId = $charge->payment_intent;
    
    if (!$paymentIntentId) {
        journaliser('Webhook Stripe - ID de payment intent manquant', 'ERROR');
        return;
    }
    
    // Récupérer le paiement associé à la transaction
    $paiement = recupererPaiementParTransaction($paymentIntentId);
    
    if (!$paiement) {
        journaliser('Webhook Stripe - Paiement non trouvé pour la transaction: ' . $paymentIntentId, 'ERROR');
        return;
    }
    
    // Mettre à jour le statut du paiement
    modifierStatutPaiement($paiement['id'], 'rembourse');
    
    // Récupérer la réservation
    $reservation = recupererReservationParId($paiement['id_reservation']);
    
    if (!$reservation) {
        journaliser('Webhook Stripe - Réservation non trouvée pour le paiement: ' . $paiement['id'], 'ERROR');
        return;
    }
    
    // Annuler la réservation si elle n'est pas déjà annulée
    if ($reservation['statut'] !== 'annulee') {
        annulerReservation($reservation['id']);
        
        // Envoyer les notifications
        $logement = recupererLogementParId($reservation['id_logement']);
        $locataire = recupererUtilisateurParId($reservation['id_locataire']);
        $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
        
        envoyerEmailAnnulationReservation($reservation, $logement, $locataire, 'locataire', 'Remboursement effectué');
        envoyerEmailAnnulationReservation($reservation, $logement, $proprietaire, 'proprietaire', 'Remboursement effectué');
    }
    
    journaliser('Webhook Stripe - Remboursement effectué pour la réservation: ' . $reservation['id'], 'INFO');
}

/**
 * Traite l'événement payment_intent.succeeded
 * 
 * @param \Stripe\PaymentIntent $paymentIntent Payment Intent Stripe
 * @return void
 */
function processPaymentIntentSucceeded($paymentIntent) {
    // Récupérer le paiement associé à la transaction
    $paiement = recupererPaiementParTransaction($paymentIntent->id);
    
    if (!$paiement) {
        journaliser('Webhook Stripe - Paiement non trouvé pour la transaction: ' . $paymentIntent->id, 'WARNING');
        return;
    }
    
    // Mettre à jour le statut du paiement
    modifierStatutPaiement($paiement['id'], 'complete');
    
    journaliser('Webhook Stripe - Payment intent succès pour le paiement: ' . $paiement['id'], 'INFO');
}

/**
 * Traite l'événement payment_intent.payment_failed
 * 
 * @param \Stripe\PaymentIntent $paymentIntent Payment Intent Stripe
 * @return void
 */
function processPaymentIntentFailed($paymentIntent) {
    // Récupérer le paiement associé à la transaction
    $paiement = recupererPaiementParTransaction($paymentIntent->id);
    
    if (!$paiement) {
        journaliser('Webhook Stripe - Paiement non trouvé pour la transaction: ' . $paymentIntent->id, 'WARNING');
        return;
    }
    
    // Mettre à jour le statut du paiement
    modifierStatutPaiement($paiement['id'], 'echoue');
    
    journaliser('Webhook Stripe - Payment intent échec pour le paiement: ' . $paiement['id'], 'WARNING');
}
?>
