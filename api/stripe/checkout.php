<?php
/**
 * API pour la création de sessions de paiement Stripe
 * 
 * Ce fichier permet de créer une session de paiement Stripe pour une réservation
 * 
 * @author OmnesBnB
 */

// Inclure la configuration et les modèles nécessaires
require_once __DIR__ . '/../../config/config.php';
require_once CHEMIN_MODELES . '/reservation.php';
require_once CHEMIN_MODELES . '/logement.php';
require_once CHEMIN_MODELES . '/paiement.php';
require_once CHEMIN_INCLUDES . '/auth.php';

// Vérifier que l'utilisateur est connecté
if (!estConnecte()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentification requise']);
    exit;
}

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);

// Si les données ne sont pas au format JSON, utiliser les données POST
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Vérifier les paramètres requis
if (!isset($data['id_reservation']) || !is_numeric($data['id_reservation'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de réservation invalide']);
    exit;
}

$idReservation = intval($data['id_reservation']);

// Récupérer la réservation
$reservation = recupererReservationParId($idReservation);

if (!$reservation) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Réservation non trouvée']);
    exit;
}

// Vérifier que l'utilisateur est le locataire de la réservation
if ($reservation['id_locataire'] != $_SESSION['utilisateur_id']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à payer cette réservation']);
    exit;
}

// Vérifier que la réservation est en attente
if ($reservation['statut'] !== 'en_attente') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Cette réservation ne peut pas être payée']);
    exit;
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

try {
    // Créer une session de paiement Stripe
    require_once CHEMIN_RACINE . '/vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Calculer les frais de service
    $fraisService = calculerFraisService($reservation['prix_total']);
    $montantTotal = calculerMontantTotal($reservation['prix_total']);
    
    // Créer la session de paiement
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => round($reservation['prix_total'] * 100), // En centimes
                    'product_data' => [
                        'name' => 'Réservation - ' . $logement['titre'],
                        'description' => 'Du ' . formaterDate($reservation['date_debut']) . ' au ' . formaterDate($reservation['date_fin']),
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
        'client_reference_id' => $idReservation,
        'metadata' => [
            'id_reservation' => $idReservation,
            'id_logement' => $reservation['id_logement'],
            'id_locataire' => $reservation['id_locataire'],
        ],
    ]);
    
    // Enregistrer l'ID de session Stripe dans la base de données
    $paiementExistant = recupererPaiementParReservation($idReservation);
    
    if ($paiementExistant) {
        // Mettre à jour le paiement existant
        $sql = "UPDATE paiements SET id_transaction = :id_transaction, montant = :montant, statut = 'en_attente', date_paiement = NOW() WHERE id = :id";
        $params = [
            ':id' => $paiementExistant['id'],
            ':id_transaction' => $session->id,
            ':montant' => $montantTotal
        ];
        
        executerRequete($sql, $params);
    } else {
        // Créer un nouveau paiement
        creerPaiement([
            'id_reservation' => $idReservation,
            'montant' => $montantTotal,
            'id_transaction' => $session->id,
            'statut' => 'en_attente'
        ]);
    }
    
    // Répondre avec l'ID de session Stripe
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'session_id' => $session->id,
        'stripe_publishable_key' => STRIPE_PUBLISHABLE_KEY
    ]);
} catch (Exception $e) {
    // Journaliser l'erreur
    journaliser('Erreur Stripe checkout: ' . $e->getMessage(), 'ERROR');
    
    // Répondre avec une erreur
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la session de paiement']);
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
