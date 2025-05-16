<div class="container-mobile mx-auto px-4 py-6 mb-safe">
    <h1 class="text-2xl font-bold mb-4">Rechercher un logement</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <form action="<?php echo URL_SITE; ?>/logement/recherche.php" method="GET" id="search-form" class="mb-6">
        <div class="mb-4">
            <label for="lieu" class="block text-gray-700 mb-2">Lieu</label>
            <input type="text" id="lieu" name="lieu" value="<?php echo isset($filtres['lieu']) ? htmlspecialchars($filtres['lieu']) : ''; ?>" placeholder="Ville ou code postal" class="input-field address-autocomplete">
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="date_debut" class="block text-gray-700 mb-2">Arrivée</label>
                <input type="date" id="date_debut" name="date_debut" value="<?php echo isset($filtres['date_debut']) ? htmlspecialchars($filtres['date_debut']) : ''; ?>" class="input-field">
            </div>
            
            <div class="w-1/2">
                <label for="date_fin" class="block text-gray-700 mb-2">Départ</label>
                <input type="date" id="date_fin" name="date_fin" value="<?php echo isset($filtres['date_fin']) ? htmlspecialchars($filtres['date_fin']) : ''; ?>" class="input-field">
            </div>
        </div>
        
        <button type="submit" class="btn-primary mb-4">Rechercher</button>
        
        <button type="button" id="filters-toggle" class="text-black underline block text-center">
            Filtres avancés
        </button>
        
        <div id="filters-panel" class="mt-4 p-4 bg-gray-100 rounded-md hidden">
            <div class="mb-4">
                <label for="type_logement" class="block text-gray-700 mb-2">Type de logement</label>
                <select id="type_logement" name="type_logement" class="input-field">
                    <option value="">Tous les types</option>
                    <option value="entier" <?php echo (isset($filtres['type_logement']) && $filtres['type_logement'] === 'entier') ? 'selected' : ''; ?>>Logement entier</option>
                    <option value="collocation" <?php echo (isset($filtres['type_logement']) && $filtres['type_logement'] === 'collocation') ? 'selected' : ''; ?>>Collocation</option>
                    <option value="libere" <?php echo (isset($filtres['type_logement']) && $filtres['type_logement'] === 'libere') ? 'selected' : ''; ?>>Logement libéré</option>
                </select>
            </div>
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="prix_min" class="block text-gray-700 mb-2">Prix min (€)</label>
                    <input type="number" id="prix_min" name="prix_min" value="<?php echo isset($filtres['prix_min']) ? htmlspecialchars($filtres['prix_min']) : ''; ?>" min="0" class="input-field">
                </div>
                
                <div class="w-1/2">
                    <label for="prix_max" class="block text-gray-700 mb-2">Prix max (€)</label>
                    <input type="number" id="prix_max" name="prix_max" value="<?php echo isset($filtres['prix_max']) ? htmlspecialchars($filtres['prix_max']) : ''; ?>" min="0" class="input-field">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="nb_places" class="block text-gray-700 mb-2">Nombre de places minimum</label>
                <input type="number" id="nb_places" name="nb_places" value="<?php echo isset($filtres['nb_places']) ? htmlspecialchars($filtres['nb_places']) : ''; ?>" min="1" class="input-field">
            </div>
        </div>
    </form>
    
    <?php if (isset($resultats) && !empty($resultats)): ?>
        <div class="mt-6">
            <h2 class="text-xl font-bold mb-4">Résultats de recherche</h2>
            
            <div id="search-results" class="space-y-4">
                <?php foreach ($resultats as $logement): ?>
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
            
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <div class="flex justify-center mt-8">
                    <nav class="inline-flex rounded">
                        <?php if ($pagination['page'] > 1): ?>
                            <a href="<?php echo URL_SITE; ?>/logement/recherche.php?<?php echo http_build_query(array_merge($filtres, ['page' => $pagination['page'] - 1])); ?>" class="bg-white border border-gray-300 px-3 py-2 rounded-l text-black hover:bg-gray-100">
                                Précédent
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['total_pages'], $pagination['page'] + 2); $i++): ?>
                            <a href="<?php echo URL_SITE; ?>/logement/recherche.php?<?php echo http_build_query(array_merge($filtres, ['page' => $i])); ?>" class="bg-white border border-gray-300 px-3 py-2 text-black hover:bg-gray-100 <?php echo $pagination['page'] == $i ? 'bg-gray-200' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                            <a href="<?php echo URL_SITE; ?>/logement/recherche.php?<?php echo http_build_query(array_merge($filtres, ['page' => $pagination['page'] + 1])); ?>" class="bg-white border border-gray-300 px-3 py-2 rounded-r text-black hover:bg-gray-100">
                                Suivant
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
            
        </div>
    <?php elseif (isset($resultats)): ?>
        <div class="text-center py-8">
            <p class="text-lg">Aucun logement ne correspond à votre recherche.</p>
            <p class="text-gray-500 mt-2">Essayez de modifier vos critères.</p>
        </div>
    <?php endif; ?>
</div>
