<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <?php if ($statut === 'succes'): ?>
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-2">Réservation confirmée !</h1>
            <p class="text-gray-600">Votre paiement a été traité avec succès.</p>
        </div>
        
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
                    <span>Montant total</span>
                    <span class="font-medium"><?php echo formaterPrix($paiement['montant']); ?></span>
                </div>
                
                <div class="flex justify-between mb-2">
                    <span>Statut</span>
                    <span class="font-medium text-green-500">Payé</span>
                </div>
            </div>
            
            <?php if ($logement['type_logement'] === 'collocation'): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Votre réservation est en attente de confirmation par le propriétaire. Vous serez notifié par email dès qu'elle sera acceptée.
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mt-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                Votre réservation est confirmée ! Vous pouvez maintenant contacter le propriétaire au <?php echo $proprietaire['telephone']; ?>.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center">
            <a href="<?php echo URL_SITE; ?>/locations/mes_locations.php" class="btn-primary">
                Voir mes locations
            </a>
        </div>
        
    <?php elseif ($statut === 'annule'): ?>
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-2">Paiement annulé</h1>
            <p class="text-gray-600">Votre paiement a été annulé. Aucun montant n'a été débité.</p>
        </div>
        
        <div class="text-center">
            <a href="<?php echo URL_SITE; ?>/logement/recherche.php" class="btn-primary">
                Reprendre la recherche
            </a>
        </div>
        
    <?php else: ?>
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-2">Erreur de paiement</h1>
            <p class="text-gray-600">Une erreur s'est produite lors du traitement de votre paiement.</p>
            <?php if (!empty($message)): ?>
                <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="text-center">
            <a href="<?php echo URL_SITE; ?>/reservation/paiement.php?id=<?php echo $reservation['id']; ?>" class="btn-primary">
                Réessayer le paiement
            </a>
        </div>
    <?php endif; ?>
</div>
