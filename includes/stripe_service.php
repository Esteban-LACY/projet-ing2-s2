<?php
/**
* Service pour l'intégration Stripe
* 
* Ce fichier encapsule toutes les interactions avec l'API Stripe
* 
* @author OmnesBnB
*/

// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';

/**
* Initialise l'API Stripe avec les clés d'API
* 
* @return void
*/
function initStripe() {
   // Vérifier que les clés Stripe sont configurées
   if (empty(STRIPE_SECRET_KEY)) {
       throw new Exception('Clé secrète Stripe non configurée');
   }
   
   // Charger la bibliothèque Stripe
   require_once CHEMIN_RACINE . '/vendor/autoload.php';
   
   // Définir la clé API
   \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
}

/**
* Crée une session de paiement Stripe
* 
* @param array $reservation Données de la réservation
* @return string|false ID de la session ou false en cas d'erreur
*/
function creerSessionPaiement($reservation) {
   try {
       initStripe();
       
       // Calculer le montant total
       $montantTotal = calculerMontantTotal($reservation['prix_total']);
       $fraisService = calculerFraisService($reservation['prix_total']);
       
       // Créer la session de paiement
       $session = \Stripe\Checkout\Session::create([
           'payment_method_types' => ['card'],
           'line_items' => [
               [
                   'price_data' => [
                       'currency' => 'eur',
                       'unit_amount' => round($reservation['prix_total'] * 100), // En centimes
                       'product_data' => [
                           'name' => 'Réservation - ' . $reservation['titre_logement'],
                           'description' => 'Du ' . formaterDateReservation($reservation['date_debut']) . ' au ' . formaterDateReservation($reservation['date_fin']),
                       ],
                   ],
                   'quantity' => 1,
               ],
               [
                   'price_data' => [
                       'currency' => 'eur',
                       'unit_amount' => round($fraisService * 100), // En centimes
                       'product_data' => [
                           'name' => 'Frais de service',
                           'description' => FRAIS_SERVICE_POURCENTAGE . '% du montant de la réservation',
                       ],
                   ],
                   'quantity' => 1,
               ],
           ],
           'mode' => 'payment',
           'success_url' => STRIPE_SUCCESS_URL,
           'cancel_url' => STRIPE_CANCEL_URL,
           'client_reference_id' => $reservation['id'],
           'metadata' => [
               'id_reservation' => $reservation['id'],
               'id_logement' => $reservation['id_logement'],
               'id_locataire' => $reservation['id_locataire'],
           ],
       ]);
       
       return $session->id;
   } catch (\Exception $e) {
       journaliser("Erreur Stripe: " . $e->getMessage(), 'ERROR');
       return false;
   }
}

/**
* Récupère une session de paiement Stripe
* 
* @param string $sessionId ID de la session Stripe
* @return object|false Objet session ou false en cas d'erreur
*/
function recupererSessionPaiement($sessionId) {
   try {
       initStripe();
       return \Stripe\Checkout\Session::retrieve($sessionId);
   } catch (\Exception $e) {
       journaliser("Erreur Stripe: " . $e->getMessage(), 'ERROR');
       return false;
   }
}

/**
* Vérifie la signature d'un webhook Stripe
* 
* @param string $payload Contenu brut de la requête
* @param string $sigHeader En-tête de signature Stripe
* @return \Stripe\Event|false Événement Stripe ou false en cas d'erreur
*/
function verifierSignatureWebhook($payload, $sigHeader) {
   try {
       initStripe();
       return \Stripe\Webhook::constructEvent(
           $payload, 
           $sigHeader, 
           STRIPE_WEBHOOK_SECRET
       );
   } catch (\UnexpectedValueException $e) {
       journaliser('Webhook Stripe - Payload invalide: ' . $e->getMessage(), 'ERROR');
       return false;
   } catch (\Stripe\Exception\SignatureVerificationException $e) {
       journaliser('Webhook Stripe - Signature invalide: ' . $e->getMessage(), 'ERROR');
       return false;
   }
}

/**
* Traite un événement webhook Stripe
* 
* @param \Stripe\Event $event Événement Stripe
* @return array Résultat du traitement
*/
function traiterEvenementWebhook($event) {
   try {
       switch ($event->type) {
           case 'checkout.session.completed':
               return traiterCheckoutSessionCompleted($event->data->object);
               
           case 'charge.refunded':
               return traiterChargeRefunded($event->data->object);
               
           case 'payment_intent.succeeded':
               return traiterPaymentIntentSucceeded($event->data->object);
               
           case 'payment_intent.payment_failed':
               return traiterPaymentIntentFailed($event->data->object);
               
           default:
               journaliser('Webhook Stripe - Événement non traité: ' . $event->type, 'INFO');
               return ['status' => 'ignored', 'message' => 'Événement non traité'];
       }
   } catch (\Exception $e) {
       journaliser('Webhook Stripe - Erreur: ' . $e->getMessage(), 'ERROR');
       return ['status' => 'error', 'message' => $e->getMessage()];
   }
}

/**
* Traite l'événement checkout.session.completed
* 
* @param \Stripe\Checkout\Session $session Session Stripe
* @return array Résultat du traitement
*/
function traiterCheckoutSessionCompleted($session) {
   // Récupérer l'ID de la réservation
   $idReservation = $session->client_reference_id;
   
   if (!$idReservation) {
       return ['status' => 'error', 'message' => 'ID de réservation manquant'];
   }
   
   // Récupérer la réservation depuis la DB
   $reservation = recupererReservationParId($idReservation);
   
   if (!$reservation) {
       return ['status' => 'error', 'message' => 'Réservation non trouvée'];
   }
   
   // Récupérer le paiement associé
   $paiement = recupererPaiementParReservation($idReservation);
   
   // Si le paiement est déjà enregistré, mettre à jour son statut
   if ($paiement) {
       if ($session->payment_status === 'paid') {
           modifierStatutPaiement($paiement['id'], 'complete');
           
           // Récupérer les informations nécessaires
           $logement = recupererLogementParId($reservation['id_logement']);
           $locataire = recupererUtilisateurParId($reservation['id_locataire']);
           
           // Traiter la réservation selon le type de logement
           if ($reservation['statut'] === 'en_attente') {
               if ($logement['type_logement'] === 'entier' || $logement['type_logement'] === 'libere') {
                   confirmerReservation($idReservation);
                   
                   // Envoyer les notifications
                   $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                   envoyerNotificationsReservationConfirmee($reservation, $logement, $locataire, $proprietaire);
               } else {
                   // Pour les collocations, notification au propriétaire
                   $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                   envoyerNotificationsReservationEnAttente($reservation, $logement, $locataire, $proprietaire);
               }
           }
           
           return ['status' => 'success', 'id_reservation' => $idReservation];
       } else {
           return ['status' => 'pending', 'message' => 'Paiement en attente'];
       }
   } else {
       return ['status' => 'error', 'message' => 'Paiement non trouvé'];
   }
}

/**
* Traite l'événement charge.refunded
* 
* @param \Stripe\Charge $charge Charge Stripe
* @return array Résultat du traitement
*/
function traiterChargeRefunded($charge) {
   // Récupérer le payment intent
   $paymentIntentId = $charge->payment_intent;
   
   if (!$paymentIntentId) {
       return ['status' => 'error', 'message' => 'ID de payment intent manquant'];
   }
   
   // Récupérer le paiement associé
   $paiement = recupererPaiementParTransaction($paymentIntentId);
   
   if (!$paiement) {
       return ['status' => 'error', 'message' => 'Paiement non trouvé'];
   }
   
   // Mettre à jour le statut du paiement
   modifierStatutPaiement($paiement['id'], 'rembourse');
   
   // Récupérer la réservation
   $reservation = recupererReservationParId($paiement['id_reservation']);
   
   if (!$reservation) {
       return ['status' => 'error', 'message' => 'Réservation non trouvée'];
   }
   
   // Annuler la réservation si elle n'est pas déjà annulée
   if ($reservation['statut'] !== 'annulee') {
       annulerReservation($reservation['id']);
       
       // Envoyer les notifications d'annulation
       $logement = recupererLogementParId($reservation['id_logement']);
       $locataire = recupererUtilisateurParId($reservation['id_locataire']);
       $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
       
       envoyerNotificationRemboursement($reservation, $logement, $locataire, $proprietaire);
   }
   
   return ['status' => 'success', 'id_reservation' => $reservation['id']];
}

/**
* Traite l'événement payment_intent.succeeded
* 
* @param \Stripe\PaymentIntent $paymentIntent Payment Intent Stripe
* @return array Résultat du traitement
*/
function traiterPaymentIntentSucceeded($paymentIntent) {
   // Récupérer le paiement associé
   $paiement = recupererPaiementParTransaction($paymentIntent->id);
   
   if (!$paiement) {
       return ['status' => 'warning', 'message' => 'Paiement non trouvé'];
   }
   
   // Mettre à jour le statut du paiement
   modifierStatutPaiement($paiement['id'], 'complete');
   
   return ['status' => 'success', 'id_paiement' => $paiement['id']];
}

/**
* Traite l'événement payment_intent.payment_failed
* 
* @param \Stripe\PaymentIntent $paymentIntent Payment Intent Stripe
* @return array Résultat du traitement
*/
function traiterPaymentIntentFailed($paymentIntent) {
   // Récupérer le paiement associé
   $paiement = recupererPaiementParTransaction($paymentIntent->id);
   
   if (!$paiement) {
       return ['status' => 'warning', 'message' => 'Paiement non trouvé'];
   }
   
   // Mettre à jour le statut du paiement
   modifierStatutPaiement($paiement['id'], 'echoue');
   
   return ['status' => 'success', 'id_paiement' => $paiement['id']];
}

/**
* Rembourse un paiement Stripe
* 
* @param string $paymentIntentId ID du payment intent Stripe
* @return bool True si le remboursement a réussi, false sinon
*/
function rembourserPaiementStripe($paymentIntentId) {
   try {
       initStripe();
       
       $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
       
       // Récupérer les charges associées au payment intent
       $charges = $intent->charges->data;
       
       if (empty($charges)) {
           return false;
       }
       
       // Rembourser la première charge
       $refund = \Stripe\Refund::create([
           'charge' => $charges[0]->id,
       ]);
       
       return $refund->status === 'succeeded';
   } catch (\Exception $e) {
       journaliser("Erreur lors du remboursement Stripe: " . $e->getMessage(), 'ERROR');
       return false;
   }
}

/**
* Calcule le montant des frais de service pour une réservation
* 
* @param float $montant Montant de la réservation
* @return float Montant des frais de service
*/
function calculerFraisService($montant) {
   return round($montant * (FRAIS_SERVICE_POURCENTAGE / 100), 2);
}

/**
* Calcule le montant total à payer pour une réservation (montant + frais)
* 
* @param float $montant Montant de la réservation
* @return float Montant total à payer
*/
function calculerMontantTotal($montant) {
   return $montant + calculerFraisService($montant);
}

/**
* Formate une date pour l'affichage dans les transactions Stripe
* 
* @param string $date Date au format Y-m-d
* @return string Date au format d/m/Y
*/
function formaterDateReservation($date) {
   return date("d/m/Y", strtotime($date));
}

/**
* Envoie les notifications quand une réservation est confirmée
* 
* @param array $reservation Réservation
* @param array $logement Logement
* @param array $locataire Locataire
* @param array $proprietaire Propriétaire
* @return void
*/
function envoyerNotificationsReservationConfirmee($reservation, $logement, $locataire, $proprietaire) {
   // Notification au locataire
   $sujet = 'Confirmation de réservation - ' . $logement['titre'];
   $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
   $message .= "Votre réservation pour le logement \"{$logement['titre']}\" a été confirmée.\n";
   $message .= "Dates: " . formaterDateReservation($reservation['date_debut']) . " au " . formaterDateReservation($reservation['date_fin']) . "\n\n";
   $message .= "Vous pouvez contacter le propriétaire au {$proprietaire['telephone']}.\n\n";
   $message .= "Cordialement,\nL'équipe OmnesBnB";
   
   envoyerEmail($locataire['email'], $sujet, $message);
   
   // Notification au propriétaire
   $sujet = 'Nouvelle réservation confirmée - ' . $logement['titre'];
   $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
   $message .= "Une réservation pour votre logement \"{$logement['titre']}\" a été confirmée.\n";
   $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
   $message .= "Dates: " . formaterDateReservation($reservation['date_debut']) . " au " . formaterDateReservation($reservation['date_fin']) . "\n";
   $message .= "Montant: {$reservation['prix_total']} €\n\n";
   $message .= "Vous pouvez contacter le locataire au {$locataire['telephone']}.\n\n";
   $message .= "Cordialement,\nL'équipe OmnesBnB";
   
   envoyerEmail($proprietaire['email'], $sujet, $message);
}

/**
* Envoie les notifications quand une réservation est en attente
* 
* @param array $reservation Réservation
* @param array $logement Logement
* @param array $locataire Locataire
* @param array $proprietaire Propriétaire
* @return void
*/
function envoyerNotificationsReservationEnAttente($reservation, $logement, $locataire, $proprietaire) {
   // Notification au propriétaire
   $sujet = 'Nouvelle réservation en attente - ' . $logement['titre'];
   $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
   $message .= "Vous avez reçu une nouvelle demande de réservation pour votre logement \"{$logement['titre']}\".\n";
   $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
   $message .= "Dates: " . formaterDateReservation($reservation['date_debut']) . " au " . formaterDateReservation($reservation['date_fin']) . "\n";
   $message .= "Montant: {$reservation['prix_total']} €\n\n";
   $message .= "Le paiement a été effectué. Veuillez vous connecter à votre compte pour accepter ou refuser cette réservation.\n\n";
   $message .= "Cordialement,\nL'équipe OmnesBnB";
   
   envoyerEmail($proprietaire['email'], $sujet, $message);
   
   // Notification au locataire
   $sujet = 'Réservation en attente de confirmation - ' . $logement['titre'];
   $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
   $message .= "Votre paiement pour le logement \"{$logement['titre']}\" a été effectué avec succès.\n";
   $message .= "Dates: " . formaterDateReservation($reservation['date_debut']) . " au " . formaterDateReservation($reservation['date_fin']) . "\n\n";
   $message .= "Votre réservation est maintenant en attente de confirmation par le propriétaire. Vous serez notifié dès que celle-ci sera confirmée.\n\n";
   $message .= "Cordialement,\nL'équipe OmnesBnB";
   
   envoyerEmail($locataire['email'], $sujet, $message);
}

/**
* Envoie les notifications de remboursement
* 
* @param array $reservation Réservation
* @param array $logement Logement
* @param array $locataire Locataire
* @param array $proprietaire Propriétaire
* @return void
*/
function envoyerNotificationRemboursement($reservation, $logement, $locataire, $proprietaire) {
   // Notification au locataire
   $sujet = 'Remboursement de réservation - ' . $logement['titre'];
   $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
   $message .= "Nous vous informons que votre réservation pour le logement \"{$logement['titre']}\" a été annulée et remboursée.\n";
   $message .= "Dates: " . formaterDateReservation($reservation['date_debut']) . " au " . formaterDateReservation($reservation['date_fin']) . "\n\n";
   $message .= "Le montant de {$reservation['prix_total']}€ a été remboursé sur votre carte bancaire.\n\n";
   $message .= "Cordialement,\nL'équipe OmnesBnB";
   
   envoyerEmail($locataire['email'], $sujet, $message);
   
   // Notification au propriétaire
   $sujet = 'Annulation de réservation - ' . $logement['titre'];
   $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
   $message .= "Nous vous informons qu'une réservation pour votre logement \"{$logement['titre']}\" a été annulée.\n";
   $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
   $message .= "Dates: " . formaterDateReservation($reservation['date_debut']) . " au " . formaterDateReservation($reservation['date_fin']) . "\n\n";
   $message .= "Cordialement,\nL'équipe OmnesBnB";
   
   envoyerEmail($proprietaire['email'], $sujet, $message);
}
?>
