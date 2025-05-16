<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Annuler la réservation</h1>
    
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
                <span>Montant total</span>
                <span class="font-medium"><?php echo formaterPrix($reservation['prix_total']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Attention</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>L'annulation de cette réservation est définitive et ne pourra pas être annulée.</p>
                    
                    <?php if ($reservation['statut'] === 'acceptee' && $paiement && $paiement['statut'] === 'complete'): ?>
                        <p class="mt-2">Un remboursement sera effectué selon la politique d'annulation :</p>
                        <ul class="list-disc pl-5 mt-1">
                            <li>100% du montant si l'annulation est faite plus de 7 jours avant l'arrivée</li>
                            <li>50% du montant si l'annulation est faite entre 3 et 7 jours avant l'arrivée</li>
                            <li>0% du montant si l'annulation est faite moins de 3 jours avant l'arrivée</li>
                        </ul>
                        
                        <?php
                        $joursRestants = ceil((strtotime($reservation['date_debut']) - time()) / (60 * 60 * 24));
                        $pourcentageRemboursement = 0;
                        
                        if ($joursRestants > 7) {
                            $pourcentageRemboursement = 100;
                        } elseif ($joursRestants >= 3) {
                            $pourcentageRemboursement = 50;
                        }
                        
                        $montantRemboursement = ($reservation['prix_total'] * $pourcentageRemboursement) / 100;
                        ?>
                        
                        <p class="mt-2">Dans votre cas, vous recevrez un remboursement de <?php echo formaterPrix($montantRemboursement); ?> (<?php echo $pourcentageRemboursement; ?>%).</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <form action="<?php echo URL_SITE; ?>/reservation/annuler.php" method="POST">
        <div class="mb-4">
            <label for="raison_annulation" class="block text-gray-700 mb-2">Raison de l'annulation</label>
            <select id="raison_annulation" name="raison_annulation" class="input-field" required>
                <option value="">Sélectionner une raison</option>
                <option value="changement_plans">Changement de plans</option>
                <option value="imprevu">Imprévu</option>
                <option value="autre_logement">J'ai trouvé un autre logement</option>
                <option value="autre">Autre raison</option>
            </select>
        </div>
        
        <div class="mb-6">
            <label for="commentaire" class="block text-gray-700 mb-2">Commentaire (optionnel)</label>
            <textarea id="commentaire" name="commentaire" rows="3" class="input-field"></textarea>
        </div>
        
        <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
        
        <button type="submit" class="btn-primary w-full">Confirmer l'annulation</button>
    </form>
    
    <div class="text-center mt-4">
        <a href="<?php echo URL_SITE; ?>/locations/mes_locations.php" class="text-black underline">
            Retour à mes locations
        </a>
    </div>
</div>
