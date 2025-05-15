<div class="container-mobile mx-auto py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Confirmation de réservation</h1>
    
    <?php if (!empty($erreur)) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erreur; ?>
        </div>
    <?php elseif ($paiementReussi) : ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            Votre réservation a été confirmée avec succès !
        </div>
        
        <div class="card p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Détails de la réservation</h2>
            
            <div class="mb-4">
                <p class="font-semibold"><?php echo htmlspecialchars($logement['titre']); ?></p>
                <p class="text-gray-500"><?php echo htmlspecialchars($logement['adresse'] . ', ' . $logement['code_postal'] . ' ' . $logement['ville']); ?></p>
            </div>
            
            <div class="mb-4">
                <p><span class="font-semibold">Dates :</span>
                    <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                    <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                </p>
                <p><span class="font-semibold">Prix total :</span> <?php echo $reservation['prix_total']; ?>€</p>
            </div>
            
            <div class="mb-4">
                <p class="font-semibold">Coordonnées du propriétaire :</p>
                <p><?php echo htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']); ?></p>
                <p><?php echo htmlspecialchars($proprietaire['email']); ?></p>
                <p><?php echo htmlspecialchars($proprietaire['telephone']); ?></p>
            </div>
            
            <a href="<?php echo SITE_URL; ?>reservation/details.php?id=<?php echo $reservation['id']; ?>" class="btn-primary">Voir les détails</a>
        </div>
        
        <div class="text-center">
            <a href="<?php echo SITE_URL; ?>" class="btn-secondary">Retour à l'accueil</a>
        </div>
    <?php else : ?>
        <div id="payment-status"></div>
        
        <!-- Formulaire de paiement Stripe -->
        <div class="mb-6">
            <div class="bg-gray-100 p-6 rounded-lg mb-4">
                <h2 class="text-xl font-bold mb-4">Résumé de la réservation</h2>
                
                <div class="mb-4">
                    <p class="font-semibold"><?php echo htmlspecialchars($logement['titre']); ?></p>
                    <p class="text-gray-500"><?php echo htmlspecialchars($logement['adresse'] . ', ' . $logement['code_postal'] . ' ' . $logement['ville']); ?></p>
                </div>
                
                <div class="mb-4">
                    <p><span class="font-semibold">Dates :</span>
                        <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                        <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                    </p>
                    <p><span class="font-semibold">Nombre de nuits :</span> <?php echo $nbNuits; ?></p>
                    <p><span class="font-semibold">Prix par nuit :</span> <?php echo $logement['prix']; ?>€</p>
                    <p class="text-xl font-bold mt-2"><span class="font-semibold">Prix total :</span> <?php echo $reservation['prix_total']; ?>€</p>
                </div>
            </div>
            
            <form id="payment-form" class="mb-6">
                <h2 class="text-xl font-bold mb-4">Informations de paiement</h2>
                
                <div id="payment-element" class="mb-6">
                    <!-- Elements de paiement Stripe seront injectés ici -->
                </div>
                
                <div id="payment-error" class="text-red-500 mb-4 hidden"></div>
                
                <button id="submit-payment" class="btn-primary w-full">
                    Payer <?php echo $reservation['prix_total']; ?>€
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!$paiementReussi && !empty($clientSecret)) : ?>
            // Initialiser Stripe
            if (window.stripePayment) {
                window.stripePayment.init('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
                window.stripePayment.setupPaymentForm('<?php echo $clientSecret; ?>', 'payment-form');
            }
        <?php elseif (!empty($clientSecret)) : ?>
            // Vérifier le statut du paiement
            if (window.stripePayment) {
                window.stripePayment.init('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
                window.stripePayment.checkPaymentStatus('<?php echo $clientSecret; ?>');
            }
        <?php endif; ?>
    });
</script>
