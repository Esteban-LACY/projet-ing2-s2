<div class="container-mobile mx-auto px-0 mb-safe">
    <!-- Photos du logement avec slider -->
    <div class="relative">
        <div class="property-slider overflow-x-scroll flex snap-x snap-mandatory">
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $photo): ?>
                    <div class="min-w-full h-64 snap-center">
                        <img src="<?php echo urlPhotoLogement($photo['url']); ?>" alt="Photo du logement" class="h-full w-full object-cover">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="min-w-full h-64 snap-center">
                    <img src="<?php echo urlAsset('img/placeholders/logement.jpg'); ?>" alt="Photo non disponible" class="h-full w-full object-cover">
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bouton retour -->
        <a href="javascript:history.back()" class="absolute top-4 left-4 bg-white rounded-full p-2 shadow">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        
        <!-- Indicateur de position du slider -->
        <div class="absolute bottom-4 left-0 right-0 flex justify-center space-x-2">
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $index => $photo): ?>
                    <div class="w-2 h-2 rounded-full bg-white opacity-50 slider-indicator" data-index="<?php echo $index; ?>"></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="w-2 h-2 rounded-full bg-white opacity-50 slider-indicator active" data-index="0"></div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="px-4 py-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($logement['titre']); ?></h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($logement['ville']); ?></p>
            </div>
            <div class="text-right">
                <p class="text-xl font-bold"><?php echo $logement['prix']; ?>€ <span class="text-gray-600 text-sm font-normal">/ nuit</span></p>
                <p class="text-sm">
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
                </p>
            </div>
        </div>
        
        <div class="py-6 border-b">
            <div class="flex items-center mb-4">
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
                    <p class="font-semibold">Proposé par <?php echo htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']); ?></p>
                    <?php if (isset($reservation) && $reservation['statut'] === 'acceptee'): ?>
                        <p class="text-gray-600 text-sm">Tel: <?php echo htmlspecialchars($proprietaire['telephone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <p><?php echo nl2br(htmlspecialchars($logement['description'])); ?></p>
        </div>
        
        <div class="py-6 border-b">
            <h2 class="text-xl font-bold mb-4">Informations sur le logement</h2>
            
            <div class="flex flex-wrap">
                <div class="w-1/2 mb-4 flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span><?php echo htmlspecialchars($logement['adresse']); ?>, <?php echo htmlspecialchars($logement['code_postal']); ?> <?php echo htmlspecialchars($logement['ville']); ?></span>
                </div>
                
                <div class="w-1/2 mb-4 flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span><?php echo $logement['nb_places']; ?> place<?php echo $logement['nb_places'] > 1 ? 's' : ''; ?></span>
                </div>
            </div>
        </div>
        
        <div class="py-6 border-b">
            <h2 class="text-xl font-bold mb-4">Disponibilités</h2>
            
            <?php if (!empty($disponibilites)): ?>
                <div class="space-y-2">
                    <?php foreach ($disponibilites as $disponibilite): ?>
                        <div class="flex justify-between items-center bg-gray-100 px-4 py-3 rounded">
                            <div>
                                <p class="font-semibold">Du <?php echo formaterDate($disponibilite['date_debut']); ?></p>
                                <p class="font-semibold">Au <?php echo formaterDate($disponibilite['date_fin']); ?></p>
                            </div>
                            <?php if ($disponibilite['est_disponible']): ?>
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Disponible</span>
                            <?php else: ?>
                                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Réservé</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600">Aucune disponibilité renseignée</p>
            <?php endif; ?>
        </div>
        
        <div class="py-6">
            <h2 class="text-xl font-bold mb-4">Emplacement</h2>
            
            <div id="detail-map" class="w-full h-64 bg-gray-200 rounded-lg mb-4"
                 data-lat="<?php echo $logement['latitude']; ?>"
                 data-lng="<?php echo $logement['longitude']; ?>">
                <!-- La carte Google Maps sera chargée ici -->
            </div>
        </div>
        
        <?php if (estConnecte() && !estProprietaire($logement['id_proprietaire'])): ?>
            <div class="fixed bottom-20 left-0 right-0 p-4 bg-white shadow-top">
                <?php if (isset($reservation) && in_array($reservation['statut'], ['en_attente', 'acceptee'])): ?>
                    <div class="mb-4">
                        <p class="text-center mb-2">
                            <?php if ($reservation['statut'] === 'en_attente'): ?>
                                <span class="bg-yellow-100 text-yellow-800 text-sm font-medium px-2.5 py-1 rounded-full">Réservation en attente</span>
                            <?php else: ?>
                                <span class="bg-green-100 text-green-800 text-sm font-medium px-2.5 py-1 rounded-full">Réservation confirmée</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-center text-sm">
                            Du <?php echo formaterDate($reservation['date_debut']); ?> au <?php echo formaterDate($reservation['date_fin']); ?>
                        </p>
                    </div>
                    
                    <a href="<?php echo URL_SITE; ?>/reservation/annuler.php?id=<?php echo $reservation['id']; ?>" class="btn-secondary">
                        Annuler la réservation
                    </a>
                <?php elseif (isset($dates_disponibles) && !empty($dates_disponibles)): ?>
                    <form action="<?php echo URL_SITE; ?>/reservation/confirmer.php" method="POST">
                        <div class="mb-4">
                            <label for="periode" class="block text-gray-700 mb-2">Période de réservation</label>
                            <select id="periode" name="id_disponibilite" class="input-field" required>
                                <option value="">Sélectionnez une période</option>
                                <?php foreach ($dates_disponibles as $dispo): ?>
                                    <option value="<?php echo $dispo['id']; ?>">
                                        Du <?php echo formaterDate($dispo['date_debut']); ?> au <?php echo formaterDate($dispo['date_fin']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <input type="hidden" name="id_logement" value="<?php echo $logement['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
                        
                        <button type="submit" class="btn-primary">Réserver</button>
                    </form>
                <?php else: ?>
                    <p class="text-center text-gray-600 mb-4">Aucune disponibilité pour ce logement actuellement.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
