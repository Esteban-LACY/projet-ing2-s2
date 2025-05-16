<div class="container-mobile mx-auto px-4 py-6 mb-safe">
    <h1 class="text-2xl font-bold mb-4">Paiement de la réservation</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($logement['titre']); ?></h2>
        <p class="text-gray-600 mb-4"><?php echo formaterDate($reservation['date_debut']); ?> - <?php echo formaterDate($reservation['date_fin']); ?></p>
        
        <div class="py-4 border-t">
            <div class="flex justify-between font-bold text-lg">
                <span>Montant total</span>
                <span><?php echo formaterPrix($montantTotal); ?></span>
            </div>
        </div>
    </div>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <form id="payment-form">
            <div id="payment-element" class="mb-6">
                <!-- Stripe Elements sera inséré ici -->
            </div>
            
            <div id="payment-error" class="hidden bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"></div>
            
            <button id="submit-payment" class="btn-primary">
                <div class="spinner hidden"></div>
                <span id="button-text">Payer</span>
            </button>
        </form>
    </div>
    
    <div class="text-center">
        <a href="<?php echo URL_SITE; ?>/logement/details.php?id=<?php echo $logement['id']; ?>" class="text-black underline">
            Retour aux détails du logement
        </a>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser Stripe
        if (window.stripePayment) {
            window.stripe
