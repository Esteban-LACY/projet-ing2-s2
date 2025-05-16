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
    
    // Vérifier que l'utilisateur est autorisé à payer cette réservation
    if (!estUtilisateurAutoriseAPayer($idReservation)) {
        repondreJSON(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à payer cette réservation']);
        return;
    }
    
    // Récupérer les données de la réservation
    $reservationPaiement = preparerDonneesReservationPourPaiement($idReservation);
    
    if (!$reservationPaiement) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la préparation du paiement']);
        return;
    }
    
    // Créer une session de paiement Stripe
    $sessionId = creerSessionPaiement($reservationPaiement);
    
    if (!$sessionId) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la création de la session de paiement']);
        return;
    }
    
    // Enregistrer l'ID de session Stripe dans la base de données
    $resultat = enregistrerSessionPaiement($idReservation, $sessionId, calculerMontantTotal($reservationPaiement['prix_total']));
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de la session de paiement']);
        return;
    }
    
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
    
    // Traiter la confirmation du paiement
    $resultat = traiterConfirmationPaiement($sessionId);
    
    if (!$resultat['success']) {
        // Rediriger vers la page d'erreur
        rediriger(URL_SITE . '/reservation/confirmation.php?statut=erreur&message=' . urlencode($resultat['message']));
        return;
    }
    
    // Rediriger vers la page de confirmation avec succès
    rediriger(URL_SITE . '/reservation/confirmation.php?statut=succes&id_reservation=' . $resultat['id_reservation']);
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
    
    // Mettre à jour le statut du paiement
    marquerPaiementCommeEchoue($sessionId);
    
    // Rediriger vers la page de confirmation avec annulation
    rediriger(URL_SITE . '/reservation/confirmation.php?statut=annule');
}

/**
 * Gère le traitement des webhooks Stripe
 */
function actionTraiterWebhook() {
    // Traiter le webhook Stripe
    $resultat = traiterWebhookStripe();
    
    // Répondre au webhook
    header('Content-Type: application/json');
    echo json_encode($resultat);
    exit;
}

/**
 * Renvoie une réponse JSON et termine le script
 * 
 * @param array $donnees Données à renvoyer
 */
function repondreJSON($donnees) {
    header('Content-Type: application/json');
    echo json_encode($donnees);
    exit;
}
?>
