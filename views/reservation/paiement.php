<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Paiement</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center mb-4">
            <img src="<?php echo urlPhotoLogement($logement['photo_principale']); ?>" alt="<?php echo htmlspecialchars($logement['titre']); ?>" class="w-20 h-20 object-cover rounded-lg mr-4">
            <div>
                <h2 class="font-bold"><?php echo htmlspecialchars($logement['titre']); ?></h2>
                <p class="text-gray-600"><?php echo formaterDate($reservation['date_debut']); ?> - <?php echo formaterDate($reservation['date_fin']); ?></p>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-4 mt-4">
            <div class="flex justify-between font-bold text-lg">
                <span>Montant total</span>
                <span><?php echo calculerMontantTotal($reservation['prix_total']); ?>€</span>
            </div>
        </div>
    </div>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="font-semibold mb-4">Paiement sécurisé</h3>
        
        <form id="payment-form">
            <div id="payment-element" class="mb-6">
                <!-- Stripe va injecter l'interface de paiement ici -->
            </div>
            
            <div id="payment-error" class="bg-red-100 text-red-700 p-4 rounded mb-4 hidden"></div>
            
            <button id="submit-payment" type="submit" class="btn-primary w-full">
                Payer <?php echo calculerMontantTotal($reservation['prix_total']); ?>€
            </button>
        </form>
    </div>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="font-semibold mb-4">Paiement sécurisé</h3>
        <div class="flex items-center space-x-2 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span class="text-gray-700">Paiement sécurisé par Stripe</span>
        </div>
        <div class="flex items-center space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span class="text-gray-700">Vos données sont chiffrées et protégées</span>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <a href="<?php echo URL_SITE; ?>/reservation/confirmer.php?id=<?php echo $reservation['id']; ?>" class="text-black underline">
            Retour à la confirmation
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.stripePayment) {
            window.stripePayment.init('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
            window.stripePayment.setupPaymentForm('<?php echo $sessionId; ?>', 'payment-form');
        }
    });
</script>
