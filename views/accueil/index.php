<div class="container-mobile mx-auto py-4 mb-safe">
    <!-- En-tête -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">OmnesBnB</h1>
        <p class="text-lg text-gray-600">Plateforme de logements pour les étudiants et le personnel Omnes.</p>
    </div>
    
    <!-- Formulaire de recherche simplifié -->
    <div class="search-form-home mb-8">
        <form action="<?php echo SITE_URL; ?>recherche.php" method="GET" id="search-form">
            <div class="mb-4">
                <label for="lieu" class="sr-only">Lieu</label>
                <input type="text" id="lieu" name="lieu" placeholder="Lieu" class="input-field address-autocomplete">
            </div>
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="date_debut" class="sr-only">Date début</label>
                    <input type="date" id="date_debut" name="date_debut" class="input-field">
                </div>
                
                <div class="w-1/2">
                    <label for="date_fin" class="sr-only">Date fin</label>
                    <input type="date" id="date_fin" name="date_fin" class="input-field">
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Rechercher</button>
        </form>
    </div>
    
    <!-- Logements récents -->
    <div>
        <h2 class="text-2xl font-bold mb-4">Chercher un logement</h2>
        
        <?php if (empty($logementsRecents)) : ?>
            <p class="text-gray-600">Aucun logement disponible pour le moment.</p>
        <?php else : ?>
            <div class="space-y-6">
                <?php foreach ($logementsRecents as $logement) : ?>
                    <div class="property-card card">
                        <div class="relative">
                            <img 
                                src="<?php echo !empty($logement['photo_principale']) ? SITE_URL . 'uploads/logements/' . $logement['photo_principale'] : SITE_URL . 'assets/img/placeholders/logement.jpg'; ?>" 
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
                            <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $logement['id']; ?>" class="text-black underline mt-2 inline-block">Voir plus ></a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-4">
                    <a href="<?php echo SITE_URL; ?>recherche.php" class="btn-secondary">Voir tous les logements</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
