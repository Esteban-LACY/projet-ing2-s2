<?php
/**
 * Configuration de l'API Stripe
 * 
 * Ce fichier contient les paramètres de configuration pour l'intégration de Stripe
 * 
 * @author OmnesBnB
 */

// Clés API Stripe (à remplacer par vos propres clés)
define('STRIPE_SECRET_KEY', 'sk_test_51XxXxXXxXxXxXxXxXxXxXxXx');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51XxXxXXxXxXxXxXxXxXxXxXx');

// Webhook secret pour vérifier les signatures (à définir dans le dashboard Stripe)
define('STRIPE_WEBHOOK_SECRET', 'whsec_XxXxXxXxXxXxXxXxXxXxXxXx');

// URL de redirection après paiement
define('STRIPE_SUCCESS_URL', URL_SITE . '/reservation/confirmation.php?status=success&session_id={CHECKOUT_SESSION_ID}');
define('STRIPE_CANCEL_URL', URL_SITE . '/reservation/confirmation.php?status=cancel&session_id={CHECKOUT_SESSION_ID}');

// Configuration des frais de service
define('FRAIS_SERVICE_POURCENTAGE', 10); // 10% de frais de service

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
 * Crée une session de paiement Stripe
 * 
 * @param array $reservation Données de la réservation
 * @return string|false ID de la session ou false en cas d'erreur
 */
function creerSessionPaiement($reservation) {
    // Vérifier que Stripe est configuré
    if (empty(STRIPE_SECRET_KEY)) {
        return false;
    }
    
    // Initialiser l'API Stripe
    require_once CHEMIN_RACINE . '/vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    try {
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
                            'description' => 'Du ' . formatDate($reservation['date_debut']) . ' au ' . formatDate($reservation['date_fin']),
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
        if (MODE_DEVELOPPEMENT) {
            echo "Erreur Stripe: " . $e->getMessage();
        } else {
            error_log("Erreur Stripe: " . $e->getMessage());
        }
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
    // Vérifier que Stripe est configuré
    if (empty(STRIPE_SECRET_KEY)) {
        return false;
    }
    
    // Initialiser l'API Stripe
    require_once CHEMIN_RACINE . '/vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    try {
        return \Stripe\Checkout\Session::retrieve($sessionId);
    } catch (\Exception $e) {
        if (MODE_DEVELOPPEMENT) {
            echo "Erreur Stripe: " . $e->getMessage();
        } else {
            error_log("Erreur Stripe: " . $e->getMessage());
        }
        return false;
    }
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
