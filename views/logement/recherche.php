<div class="container-mobile mx-auto py-4 mb-safe">
    <h1 class="text-3xl font-bold mb-4">Rechercher un logement</h1>
    
    <!-- Formulaire de recherche -->
    <div class="mb-6">
        <form action="<?php echo SITE_URL; ?>recherche.php" method="GET" id="search-form" class="mb-4">
            <div class="mb-4">
                <label for="lieu" class="block text-gray-700 mb-2">Lieu</label>
                <input type="text" id="lieu" name="lieu" value="<?php echo htmlspecialchars($filtres['lieu']); ?>" placeholder="Ville ou code postal" class="input-field address-autocomplete">
            </div>
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="date_debut" class="block text-gray-700 mb-2">Arrivée</label>
                    <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($filtres['date_debut']); ?>" class="input-field">
                </div>
                
                <div class="w-1/2">
                    <label for="date_fin" class="block text-gray-700 mb-2">Départ</label>
                    <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($filtres['date_fin']); ?>" class="input-field">
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Rechercher</button>
        </form>
        
        <!-- Bouton pour afficher/masquer les filtres avancés -->
        <button id="filters-toggle" class="text-black underline">Filtres avancés</button>
        
        <!-- Filtres avancés -->
        <div id="filters-panel" class="mt-4 p-4 bg-gray-100 rounded-md hidden">
            <form action="<?php echo SITE_URL; ?>recherche.php" method="GET" id="advanced-search-form">
                <!-- Copier les champs du formulaire principal pour la soumission -->
                <input type="hidden" name="lieu" value="<?php echo htmlspecialchars($filtres['lieu']); ?>">
                <input type="hidden" name="date_debut" value="<?php echo htmlspecialchars($filtres['date_debut']); ?>">
                <input type="hidden" name="date_fin" value="<?php echo htmlspecialchars($filtres['date_fin']); ?>">
                
                <div class="flex space-x-4 mb-4">
                    <div class="w-1/2">
                        <label for="prix_min" class="block text-gray-700 mb-2">Prix min (€)</label>
                        <input type="number" id="prix_min" name="prix_min" value="<?php echo htmlspecialchars($filtres['prix_min']); ?>" min="0" class="input-field">
                    </div>
                    
                    <div class="w-1/2">
                        <label for="prix_max" class="block text-gray-700 mb-2">Prix max (€)</label>
                        <input type="number" id="prix_max" name="prix_max" value="<?php echo htmlspecialchars($filtres['prix_max']); ?>" min="0" class="input-field">
                    </div>
                </div>
                
                <div class="flex space-x-4 mb-4">
                    <div class="w-1/2">
                        <label for="type_logement" class="block text-gray-700 mb-2">Type de logement</label>
                        <select id="type_logement" name="type_logement" class="input-field">
                            <option value="">Tous les types</option>
                            <option value="entier" <?php echo $filtres['type_logement'] === 'entier' ? 'selected' : ''; ?>>Logement entier</option>
                            <option value="collocation" <?php echo $filtres['type_logement'] === 'collocation' ? 'selected' : ''; ?>>Collocation</option>
                            <option value="libere" <?php echo $filtres['type_logement'] === 'libere' ? 'selected' : ''; ?>>Logement libéré</option>
                        </select>
                    </div>
                    
                    <div class="w-1/2">
                        <label for="nb_places" class="block text-gray-700 mb-2">Nombre de places</label>
                        <input type="number" id="nb_places" name="nb_places" value="<?php echo htmlspecialchars($filtres['nb_places']); ?>" min="1" class="input-field">
                    </div>
                </div>
                
                <button type="submit" class="btn-secondary">Appliquer les filtres</button>
            </form>
        </div>
    </div>
    
    <!-- Affichage des résultats -->
    <div class="mt-6">
        <h2 class="text-2xl font-bold mb-4">Résultats de recherche</h2>
        
        <?php if (empty($resultats)) : ?>
            <p class="text-gray-600">Aucun logement ne correspond à votre recherche.</p>
        <?php else : ?>
            <div id="search-results" class="space-y-6">
                <?php foreach ($resultats as $logement) : ?>
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
                            <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $logement['id']; ?>" class="btn-primary mt-3">Voir plus</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
