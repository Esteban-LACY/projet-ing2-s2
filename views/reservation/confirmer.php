<div class="container-mobile mx-auto px-4 py-6 mb-safe">
    <h1 class="text-2xl font-bold mb-4">Confirmer votre réservation</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="mb-4">
            <img src="<?php echo !empty($logement['photo_principale']) ? urlPhotoLogement($logement['photo_principale']) : urlAsset('img/placeholders/logement.jpg'); ?>" alt="<?php echo htmlspecialchars($logement['titre']); ?>" class="w-full h-48 object-cover rounded">
        </div>
        
        <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($logement['titre']); ?></h2>
        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($logement['adresse']); ?>, <?php echo htmlspecialchars($logement['code_postal']); ?> <?php echo htmlspecialchars($logement['ville']); ?></p>
        
        <div class="border-t border-b py-4 my-4">
            <div class="flex justify-between mb-2">
                <span>Type de logement</span>
                <span class="font-semibold">
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
            <div class="flex justify-between mb-2">
                <span>Période</span>
                <span class="font-semibold">Du <?php echo formaterDate($periode['date_debut']); ?> au <?php echo formaterDate($periode['date_fin']); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span>Nombre de nuits</span>
                <span class="font-semibold"><?php echo $nombreNuits; ?></span>
            </div>
        </div>
        
        <div class="py-4">
            <div class="flex justify-between mb-2">
                <span>Prix par nuit</span>
                <span><?php echo formaterPrix($logement['prix']); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span>Total pour <?php echo $nombreNuits; ?> nuit<?php echo $nombreNuits > 1 ? 's' : ''; ?></span>
                <span><?php echo formaterPrix($reservation['prix_total']); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span>Frais de service (<?php echo FRAIS_SERVICE_POURCENTAGE; ?>%)</span>
                <span><?php echo formaterPrix($fraisService); ?></span>
            </div>
            <div class="flex justify-between font-bold text-lg mt-4 pt-4 border-t">
                <span>Total</span>
                <span><?php echo formaterPrix($montantTotal); ?></span>
            </div>
        </div>
    </div>
    
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <h2 class="text-xl font-bold mb-4">Propriétaire</h2>
        
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full overflow-hidden mr-3">
                <?php if (!empty($proprietaire['photo_profil'])): ?>
                    <img src="<?php echo urlPhotoProfil($proprietaire['photo_profil']); ?>" alt="Photo de profil" class="h-full w-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <p class="font-semibold"><?php echo htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']); ?></p>
                <p class="text-gray-600 text-sm">Le numéro de téléphone sera visible après confirmation de la réservation</p>
            </div>
        </div>
    </div>
    
    <?php if ($logement['type_logement'] === 'collocation'): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>Ce logement est proposé en collocation. Le propriétaire devra valider votre réservation après le paiement.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
            <div class="flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>Ce logement sera automatiquement confirmé après le paiement.</p>
            </div>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo URL_SITE; ?>/reservation/paiement.php" method="POST">
        <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
        
        <button type="submit" class="btn-primary">Procéder au paiement</button>
    </form>
</div>
