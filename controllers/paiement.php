<?php
/**
 * Contrôleur pour la gestion des paiements
 * 
 * Ce fichier gère les actions liées aux paiements (création, confirmation, etc.)
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_MODELES . '/paiement.php';
require_once CHEMIN_MODELES . '/reservation.php';
require_once CHEMIN_MODELES . '/logement.php';
require_once CHEMIN_INCLUDES . '/email.php';

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'creer_session':
        actionCreerSessionPaiement();
        break;
    case 'confirmer':
        actionConfirmerPaiement();
        break;
    case 'annuler':
        actionAnnulerPaiement();
        break;
    case 'webhook':
        actionTraiterWebhook();
        break;
    default:
        // Si aucune action n'est spécifiée, rediriger vers la page d'accueil
        rediriger(URL_SITE);
}

/**
 * Gère la création d'une session de paiement Stripe
 */
function actionCreerSessionPaiement() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour effectuer un paiement']);
        return;
    }
    
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID de la réservation
    $idReservation = isset($_POST['id_reservation']) ? intval($_POST['id_reservation']) : 0;
    
    if ($idReservation <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID de réservation invalide']);
        return;
    }
    
    // Récupérer la réservation
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        repondreJSON(['success' => false, 'message' => 'Réservation non trouvée']);
        return;
    }
    
    // Vérifier que l'utilisateur est le locataire
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    if ($reservation['id_locataire'] != $idUtilisateur) {
        repondreJSON(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à payer cette réservation']);
        return;
    }
    
    // Vérifier que la réservation est en attente
    if ($reservation['statut'] != 'en_attente') {
        repondreJSON(['success' => false, 'message' => 'Cette réservation ne peut pas être payée']);
        return;
    }
    
    // Récupérer le logement
    $logement = recupererLogementParId($reservation['id_logement']);
    
    // Préparer les données pour la session de paiement
    $reservationPaiement = [
        'id' => $idReservation,
        'id_logement' => $reservation['id_logement'],
        'id_locataire' => $reservation['id_locataire'],
        'date_debut' => $reservation['date_debut'],
        'date_fin' => $reservation['date_fin'],
        'prix_total' => $reservation['prix_total'],
        'titre_logement' => $logement['titre']
    ];
    
    // Créer une session de paiement Stripe
    $sessionId = creerSessionPaiement($reservationPaiement);
    
    if (!$sessionId) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la création de la session de paiement']);
        return;
    }
    
    // Enregistrer l'ID de session Stripe dans la base de données
    creerPaiement([
        'id_reservation' => $idReservation,
        'montant' => calculerMontantTotal($reservation['prix_total']),
        'id_transaction' => $sessionId,
        'statut' => 'en_attente'
    ]);
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'message' => 'Session de paiement créée avec succès',
        'session_id' => $sessionId
    ]);
}

/**
 * Gère la confirmation d'un paiement
 */
function actionConfirmerPaiement() {
    // Récupérer l'ID de session Stripe
    $sessionId = isset($_GET['session_id']) ? nettoyer($_GET['session_id']) : '';
    
    if (empty($sessionId)) {
        // Rediriger vers la page d'accueil si aucun ID de session n'est fourni
        rediriger(URL_SITE);
        return;
    }
    
    // Récupérer la session Stripe
    $session = recupererSessionPaiement($sessionId);
    
    if (!$session) {
        // Rediriger vers la page d'erreur si la session n'est pas valide
        rediriger(URL_SITE . '/reservation/confirmation.php?statut=erreur');
        return;
    }
    
    // Récupérer la réservation associée à la session
    $idReservation = $session->client_reference_id;
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        // Rediriger vers la page d'erreur si la réservation n'est pas trouvée
        rediriger(URL_SITE . '/reservation/confirmation.php?statut=erreur');
        return;
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
                    
                    // Notifier le locataire et le propriétaire
                    $locataire = recupererUtilisateurParId($reservation['id_locataire']);
                    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                    
                    // Notification au locataire
                    $sujet = 'Confirmation de réservation - ' . $logement['titre'];
                    $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
                    $message .= "Votre réservation pour le logement \"{$logement['titre']}\" a été automatiquement confirmée suite à votre paiement.\n";
                    $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
                    $message .= "Vous pouvez contacter le propriétaire au {$proprietaire['telephone']}.\n\n";
                    $message .= "Cordialement,\nL'équipe OmnesBnB";
                    
                    envoyerEmail($locataire['email'], $sujet, $message);
                    
                    // Notification au propriétaire
                    $sujet = 'Nouvelle réservation confirmée - ' . $logement['titre'];
                    $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
                    $message .= "Une réservation pour votre logement \"{$logement['titre']}\" a été automatiquement confirmée suite au paiement.\n";
                    $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
                    $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n";
                    $message .= "Montant: {$reservation['prix_total']} €\n\n";
                    $message .= "Vous pouvez contacter le locataire au {$locataire['telephone']}.\n\n";
                    $message .= "Cordialement,\nL'équipe OmnesBnB";
                    
                    envoyerEmail($proprietaire['email'], $sujet, $message);
                } else {
                    // Pour les collocations, le propriétaire doit valider manuellement
                    // Notifier le propriétaire qu'une réservation est en attente de confirmation
                    $locataire = recupererUtilisateurParId($reservation['id_locataire']);
                    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                    
                    $sujet = 'Nouvelle réservation en attente - ' . $logement['titre'];
                    $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
                    $message .= "Vous avez reçu une nouvelle réservation pour votre logement \"{$logement['titre']}\".\n";
                    $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
                    $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n";
                    $message .= "Montant: {$reservation['prix_total']} €\n\n";
                    $message .= "Le paiement a été effectué. Veuillez vous connecter à votre compte pour accepter ou refuser cette réservation.\n\n";
                    $message .= "Cordialement,\nL'équipe OmnesBnB";
                    
                    envoyerEmail($proprietaire['email'], $sujet, $message);
                    
                    // Notifier le locataire
                    $sujet = 'Réservation en attente de confirmation - ' . $logement['titre'];
                    $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
                    $message .= "Votre paiement pour le logement \"{$logement['titre']}\" a été effectué avec succès.\n";
                    $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
                    $message .= "Votre réservation est maintenant en attente de confirmation par le propriétaire. Vous serez notifié dès que celle-ci sera confirmée.\n\n";
                    $message .= "Cordialement,\nL'équipe OmnesBnB";
                    
                    envoyerEmail($locataire['email'], $sujet, $message);
                }
            }
        }
        
        // Rediriger vers la page de confirmation avec succès
        rediriger(URL_SITE . '/reservation/confirmation.php?statut=succes&id_reservation=' . $idReservation);
    } else {
        // Rediriger vers la page de confirmation avec échec
        rediriger(URL_SITE . '/reservation/confirmation.php?statut=echec&id_reservation=' . $idReservation);
    }
}

/**
 * Gère l'annulation d'un paiement
 */
function actionAnnulerPaiement() {
    // Récupérer l'ID de session Stripe
    $sessionId = isset($_GET['session_id']) ? nettoyer($_GET['session_id']) : '';
    
    if (empty($sessionId)) {
        // Rediriger vers la page d'accueil si aucun ID de session n'est fourni
        rediriger(URL_SITE);
        return;
    }
    
    // Récupérer le paiement associé à la session
    $paiement = recupererPaiementParTransaction($sessionId);
    
    if ($paiement) {
        // Mettre à jour le statut du paiement
        modifierStatutPaiement($paiement['id'], 'echoue');
    }
    
    // Rediriger vers la page de confirmation avec annulation
    rediriger(URL_SITE . '/reservation/confirmation.php?statut=annule');
}

/**
 * Gère le traitement des webhooks Stripe
 */
function actionTraiterWebhook() {
    // Récupérer le payload
    $payload = @file_get_contents('php://input');
    $sigHeader = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
    $event = null;
    
    // Vérifier la signature
    try {
        require_once CHEMIN_RACINE . '/vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sigHeader, STRIPE_WEBHOOK_SECRET
        );
    } catch (\UnexpectedValueException $e) {
        // Payload invalide
        http_response_code(400);
        echo json_encode(['error' => 'Payload invalide']);
        exit;
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Signature invalide
        http_response_code(400);
        echo json_encode(['error' => 'Signature invalide']);
        exit;
    }
    
    // Traiter l'événement
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            
            // Récupérer la réservation
            $idReservation = $session->client_reference_id;
            $reservation = recupererReservationParId($idReservation);
            
            if (!$reservation) {
                http_response_code(400);
                echo json_encode(['error' => 'Réservation non trouvée']);
                exit;
            }
            
            // Mettre à jour le statut du paiement
            $paiement = recupererPaiementParTransaction($session->id);
            
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
                        
                        // Notifier le locataire et le propriétaire
                        $locataire = recupererUtilisateurParId($reservation['id_locataire']);
                        $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                        
                        // Notification au locataire
                        $sujet = 'Confirmation de réservation - ' . $logement['titre'];
                        $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
                        $message .= "Votre réservation pour le logement \"{$logement['titre']}\" a été automatiquement confirmée suite à votre paiement.\n";
                        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
                        $message .= "Vous pouvez contacter le propriétaire au {$proprietaire['telephone']}.\n\n";
                        $message .= "Cordialement,\nL'équipe OmnesBnB";
                        
                        envoyerEmail($locataire['email'], $sujet, $message);
                        
                        // Notification au propriétaire
                        $sujet = 'Nouvelle réservation confirmée - ' . $logement['titre'];
                        $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
                        $message .= "Une réservation pour votre logement \"{$logement['titre']}\" a été automatiquement confirmée suite au paiement.\n";
                        $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
                        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n";
                        $message .= "Montant: {$reservation['prix_total']} €\n\n";
                        $message .= "Vous pouvez contacter le locataire au {$locataire['telephone']}.\n\n";
                        $message .= "Cordialement,\nL'équipe OmnesBnB";
                        
                        envoyerEmail($proprietaire['email'], $sujet, $message);
                    } else {
                        // Pour les collocations, le propriétaire doit valider manuellement
                        // Notifier le propriétaire qu'une réservation est en attente de confirmation
                        $locataire = recupererUtilisateurParId($reservation['id_locataire']);
                        $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                        
                        $sujet = 'Nouvelle réservation en attente - ' . $logement['titre'];
                        $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
                        $message .= "Vous avez reçu une nouvelle réservation pour votre logement \"{$logement['titre']}\".\n";
                        $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
                        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n";
                        $message .= "Montant: {$reservation['prix_total']} €\n\n";
                        $message .= "Le paiement a été effectué. Veuillez vous connecter à votre compte pour accepter ou refuser cette réservation.\n\n";
                        $message .= "Cordialement,\nL'équipe OmnesBnB";
                        
                        envoyerEmail($proprietaire['email'], $sujet, $message);
                        
                        // Notifier le locataire
                        $sujet = 'Réservation en attente de confirmation - ' . $logement['titre'];
                        $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
                        $message .= "Votre paiement pour le logement \"{$logement['titre']}\" a été effectué avec succès.\n";
                        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
                        $message .= "Votre réservation est maintenant en attente de confirmation par le propriétaire. Vous serez notifié dès que celle-ci sera confirmée.\n\n";
                        $message .= "Cordialement,\nL'équipe OmnesBnB";
                        
                        envoyerEmail($locataire['email'], $sujet, $message);
                    }
                }
            }
            break;
            
        case 'charge.refunded':
            // Traiter les remboursements
            $charge = $event->data->object;
            
            // Récupérer le paiement associé à la charge
            $idTransaction = $charge->payment_intent;
            $paiement = recupererPaiementParTransaction($idTransaction);
            
            if ($paiement) {
                // Mettre à jour le statut du paiement
                modifierStatutPaiement($paiement['id'], 'rembourse');
                
                // Récupérer la réservation
                $reservation = recupererReservationParId($paiement['id_reservation']);
                
                if ($reservation) {
                    // Mettre à jour le statut de la réservation
                    annulerReservation($reservation['id']);
                    
                    // Notifier le locataire et le propriétaire
                    $locataire = recupererUtilisateurParId($reservation['id_locataire']);
                    $logement = recupererLogementParId($reservation['id_logement']);
                    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
                    
                    // Notification au locataire
                    $sujet = 'Remboursement effectué - ' . $logement['titre'];
                    $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
                    $message .= "Nous vous informons que le remboursement de votre réservation pour le logement \"{$logement['titre']}\" a été effectué.\n";
                    $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n";
                    $message .= "Montant remboursé: {$paiement['montant']} €\n\n";
                    $message .= "Le montant sera crédité sur votre compte bancaire dans les prochains jours.\n\n";
                    $message .= "Cordialement,\nL'équipe OmnesBnB";
                    
                    envoyerEmail($locataire['email'], $sujet, $message);
                    
                    // Notification au propriétaire
                    $sujet = 'Réservation remboursée - ' . $logement['titre'];
                    $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
                    $message .= "Nous vous informons que la réservation pour votre logement \"{$logement['titre']}\" a été remboursée.\n";
                    $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
                    $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
                    $message .= "Cordialement,\nL'équipe OmnesBnB";
                    
                    envoyerEmail($proprietaire['email'], $sujet, $message);
                }
            }
            break;
    }
    
    // Répondre avec succès
    http_response_code(200);
    echo json_encode(['status' => 'success']);
}

/**
 * Formate une date pour l'affichage
 * 
 * @param string $date Date au format Y-m-d
 * @return string Date au format d/m/Y
 */
function formatDate($date) {
    return date("d/m/Y", strtotime($date));
}
?>
