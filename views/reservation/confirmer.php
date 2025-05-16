<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Confirmer la réservation</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center mb-4">
            <img src="<?php echo urlPhotoLogement($logement['photo_principale']); ?>" alt="<?php echo htmlspecialchars($logement['titre']); ?>" class="w-20 h-20 object-cover rounded-lg mr-4">
            <div>
                <h2 class="font-bold"><?php echo htmlspecialchars($logement['titre']); ?></h2>
                <p class="text-gray-600"><?php echo htmlspecialchars($logement['adresse']); ?>, <?php echo htmlspecialchars($logement['ville']); ?></p>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-4 mt-4">
            <h3 class="font-semibold mb-2">Détails de la réservation</h3>
            
            <div class="flex justify-between mb-2">
                <span>Arrivée</span>
                <span class="font-medium"><?php echo formaterDate($reservation['date_debut']); ?></span>
            </div>
            
            <div class="flex justify-between mb-2">
                <span>Départ</span>
                <span class="font-medium"><?php echo formaterDate($reservation['date_fin']); ?></span>
            </div>
            
            <div class="flex justify-between mb-2">
                <span>Nombre de nuits</span>
                <span class="font-medium"><?php echo calculerNombreJours($reservation['date_debut'], $reservation['date_fin']); ?></span>
            </div>
            
            <div class="flex justify-between mb-2">
                <span>Type de logement</span>
                <span class="font-medium">
                    <?php 
                    switch ($logement['type_logement']) {
                        case 'entier':
                            echo 'Logement entier';
                            break;
                        case 'collocation':
                            echo 'Collocation';
                            break;
                        case 'libere':
                            echo 'Logement libéré';
                            break;
                    }
                    ?>
                </span>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-4 mt-4">
            <h3 class="font-semibold mb-2">Détails du prix</h3>
            
            <div class="flex justify-between mb-2">
                <span><?php echo $logement['prix']; ?>€ x <?php echo calculerNombreJours($reservation['date_debut'], $reservation['date_fin']); ?> nuits</span>
                <span class="font-medium"><?php echo $reservation['prix_total']; ?>€</span>
            </div>
            
            <div class="flex justify-between mb-2">
                <span>Frais de service (<?php echo FRAIS_SERVICE_POURCENTAGE; ?>%)</span>
                <span class="font-medium"><?php echo calculerFraisService($reservation['prix_total']); ?>€</span>
            </div>
            
            <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-200 mt-2">
                <span>Total</span>
                <span><?php echo calculerMontantTotal($reservation['prix_total']); ?>€</span>
            </div>
        </div>
    </div>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="font-semibold mb-4">Conditions de la réservation</h3>
        
        <?php if ($logement['type_logement'] === 'collocation'): ?>
            <p class="text-gray-700 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Le propriétaire doit approuver votre demande de réservation.
            </p>
        <?php else: ?>
            <p class="text-gray-700 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Confirmation automatique après paiement.
            </p>
        <?php endif; ?>
        
        <p class="text-gray-700 mb-4">
            En confirmant, vous acceptez les conditions d'utilisation d'OmnesBnB et la politique d'annulation du propriétaire.
        </p>
    </div>
    
    <form id="confirmation-form" action="<?php echo URL_SITE; ?>/reservation/paiement.php" method="POST">
        <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
        
        <button type="submit" class="btn-primary w-full">Passer au paiement</button>
    </form>
    
    <div class="text-center mt-4">
        <a href="<?php echo URL_SITE; ?>/logement/details.php?id=<?php echo $logement['id']; ?>" class="text-black underline">
            Retour aux détails du logement
        </a>
    </div>
</div>
