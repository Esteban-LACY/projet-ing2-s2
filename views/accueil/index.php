<div class="container-mobile mx-auto px-4 mb-safe">
    <div class="search-header py-6">
        <h1 class="text-2xl font-bold mb-2">Trouvez votre logement idéal</h1>
        <p class="text-gray-600 mb-6">Logements entre étudiants et personnel Omnes</p>
        
        <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
        
        <form action="<?php echo URL_SITE; ?>/logement/recherche.php" method="GET" class="search-form-home">
            <div class="mb-4">
                <label for="lieu" class="block text-gray-700 mb-2">Où allez-vous ?</label>
                <input type="text" id="lieu" name="lieu" placeholder="Ville ou code postal" class="input-field address-autocomplete">
            </div>
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="date_debut" class="block text-gray-700 mb-2">Arrivée</label>
                    <input type="date" id="date_debut" name="date_debut" class="input-field">
                </div>
                
                <div class="w-1/2">
                    <label for="date_fin" class="block text-gray-700 mb-2">Départ</label>
                    <input type="date" id="date_fin" name="date_fin" class="input-field">
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Rechercher</button>
        </form>
    </div>
    
    <?php if (!empty($logementsRecents)): ?>
        <div class="mt-8">
            <h2 class="text-xl font-bold mb-4">Logements récents</h2>
            
            <div class="space-y-4">
                <?php foreach ($logementsRecents as $logement): ?>
                    <div class="property-card card">
                        <div class="relative">
                            <img 
                                src="<?php echo !empty($logement['photo_principale']) ? urlPhotoLogement($logement['photo_principale']) : urlAsset('img/placeholders/logement.jpg'); ?>" 
                                alt="<?php echo htmlspecialchars($logement['titre']); ?>" 
                                class="property-image"
                            >
                            <div class="absolute bottom-2 right-2 bg-white rounded-full px-2 py-1 text-sm font-semibold">
                                <?php echo $logement['prix']; ?>€ / nuit
                            </div>
                        </div>
                        <div class="property-info">
                            <h3 class="font-semibold"><?php echo htmlspecialchars($logement['titre']); ?></h3>
                            <p class="text-gray-500"><?php echo htmlspecialchars($logement['ville']); ?></p>
                            <p class="text-sm mt-1">
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
                            <a href="<?php echo URL_SITE; ?>/logement/details.php?id=<?php echo $logement['id']; ?>" class="btn-primary mt-3">Voir plus</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mt-12 mb-8">
        <h2 class="text-xl font-bold mb-4">Comment ça fonctionne</h2>
        
        <div class="space-y-6">
            <div class="flex items-start">
                <div class="bg-black rounded-full p-2 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold">Recherchez</h3>
                    <p class="text-gray-600">Trouvez le logement idéal parmi notre sélection de biens proposés par la communauté Omnes.</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="bg-black rounded-full p-2 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold">Réservez</h3>
                    <p class="text-gray-600">Effectuez votre réservation en ligne avec notre système de paiement sécurisé.</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="bg-black rounded-full p-2 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold">Profitez</h3>
                    <p class="text-gray-600">Installez-vous dans votre nouveau logement en toute sérénité.</p>
                </div>
            </div>
        </div>
    </div>
</div>
