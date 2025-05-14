<?php
/**
 * Configuration pour l'API Stripe
 */

// Clés API Stripe
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key'); // Clé secrète
define('STRIPE_PUBLIC_KEY', 'pk_test_your_stripe_public_key'); // Clé publique
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_stripe_webhook_secret'); // Clé secrète de webhook

// Configuration des devises et montants
define('STRIPE_CURRENCY', 'eur'); // Devise (EUR pour euros)
define('STRIPE_MIN_AMOUNT', 100); // Montant minimum en centimes (1€)
define('STRIPE_MAX_AMOUNT', 1000000); // Montant maximum en centimes (10000€)

// Configuration des URLs de redirection
define('STRIPE_SUCCESS_URL', APP_URL . '/paiement-succes.php'); // URL de succès
define('STRIPE_CANCEL_URL', APP_URL . '/paiement-annule.php'); // URL d'annulation

// Configuration des commissions
define('STRIPE_FEE_PERCENTAGE', 0.05); // 5% de commission
define('STRIPE_FEE_FIXED', 0.50); // 0.50€ de commission fixe

/**
 * Calcule le montant de la commission
 * @param float $montant Montant total
 * @return float Montant de la commission
 */
function calculerCommissionStripe($montant) {
    return ($montant * STRIPE_FEE_PERCENTAGE) + STRIPE_FEE_FIXED;
}

/**
 * Initialise Stripe avec la clé API
 */
function initialiserStripe() {
    require_once ROOT_PATH . 'vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
}

/**
 * Crée une session de paiement Stripe
 * @param array $options Options de la session
 * @return \Stripe\Checkout\Session|false Session ou false
 */
function creerSessionStripe($options) {
    try {
        initialiserStripe();
        
        // Options par défaut
        $defaultOptions = [
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'success_url' => STRIPE_SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => STRIPE_CANCEL_URL . '?session_id={CHECKOUT_SESSION_ID}'
        ];
        
        // Fusionner les options
        $options = array_merge($defaultOptions, $options);
        
        // Créer la session
        $session = \Stripe\Checkout\Session::create($options);
        
        return $session;
    } catch (\Exception $e) {
        if (APP_DEBUG) {
            echo 'Erreur Stripe : ' . $e->getMessage();
        }
        
        return false;
    }
}

/**
 * Vérifie la signature d'un webhook Stripe
 * @param string $payload Contenu de la requête
 * @param string $sigHeader En-tête de signature
 * @return \Stripe\Event|false Événement ou false
 */
function verifierWebhookStripe($payload, $sigHeader) {
    try {
        initialiserStripe();
        
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sigHeader, STRIPE_WEBHOOK_SECRET
        );
        
        return $event;
    } catch (\Exception $e) {
        if (APP_DEBUG) {
            echo 'Erreur de vérification de webhook : ' . $e->getMessage();
        }
        
        return false;
    }
}

/**
 * Rembourse un paiement Stripe
 * @param string $idPaiement ID du paiement
 * @param float|null $montant Montant à rembourser (null pour tout)
 * @return \Stripe\Refund|false Remboursement ou false
 */
function rembourserPaiementStripe($idPaiement, $montant = null) {
    try {
        initialiserStripe();
        
        $options = [
            'payment_intent' => $idPaiement
        ];
        
        if ($montant !== null) {
            $options['amount'] = round($montant * 100); // Conversion en centimes
        }
        
        $refund = \Stripe\Refund::create($options);
        
        return $refund;
    } catch (\Exception $e) {
        if (APP_DEBUG) {
            echo 'Erreur de remboursement : ' . $e->getMessage();
        }
        
        return false;
    }
}
?>
