<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Rechercher un logement</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <form id="search-form" action="<?php echo URL_SITE; ?>/logement/resultats.php" method="GET" class="mb-6">
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
        
        <button type="button" id="filters-toggle" class="text-black underline mb-4">Filtres avancés</button>
        
        <div id="filters-panel" class="bg-gray-50 p-4 rounded-lg mb-4 <?php echo isset($filtresAvances) && $filtresAvances ? '' : 'hidden'; ?>">
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
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="type_logement" class="block text-gray-700 mb-2">Type de logement</label>
                    <select id="type_logement" name="type_logement" class="input-field">
                        <option value="">Tous les types</option>
                        <option value="entier" <?php echo isset($filtres['type_logement']) && $filtres['type_logement'] === 'entier' ? 'selected' : ''; ?>>Logement entier</option>
                        <option value="collocation" <?php echo isset($filtres['type_logement']) && $filtres['type_logement'] === 'collocation' ? 'selected' : ''; ?>>Collocation</option>
                        <option value="libere" <?php echo isset($filtres['type_logement']) && $filtres['type_logement'] === 'libere' ? 'selected' : ''; ?>>Logement libéré</option>
                    </select>
                </div>
                
                <div class="w-1/2">
                    <label for="nb_places" class="block text-gray-700 mb-2">Nombre de places</label>
                    <input type="number" id="nb_places" name="nb_places" value="<?php echo isset($filtres['nb_places']) ? htmlspecialchars($filtres['nb_places']) : ''; ?>" min="1" class="input-field">
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn-primary w-full">Rechercher</button>
    </form>
    
    <?php if (isset($recherche_effectuee) && $recherche_effectuee): ?>
        <?php if (empty($resultats)): ?>
            <div class="text-center py-8">
                <p class="text-lg">Aucun logement ne correspond à votre recherche.</p>
                <p class="text-gray-500 mt-2">Essayez de modifier vos critères de recherche.</p>
            </div>
        <?php else: ?>
            <div id="search-results" class="space-y-6">
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
                <div class="flex justify-center mt-6">
                    <div class="inline-flex">
                        <?php if ($pagination['page'] > 1): ?>
                            <a href="<?php echo URL_SITE; ?>/logement/resultats.php?<?php echo http_build_query(array_merge($filtres, ['page' => $pagination['page'] - 1])); ?>" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-l hover:bg-gray-100">
                                Précédent
                            </a>
                        <?php else: ?>
                            <span class="bg-gray-100 border border-gray-300 text-gray-400 px-4 py-2 rounded-l cursor-not-allowed">
                                Précédent
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                            <a href="<?php echo URL_SITE; ?>/logement/resultats.php?<?php echo http_build_query(array_merge($filtres, ['page' => $pagination['page'] + 1])); ?>" class="bg-white border border-gray-300 border-l-0 text-gray-700 px-4 py-2 rounded-r hover:bg-gray-100">
                                Suivant
                            </a>
                        <?php else: ?>
                            <span class="bg-gray-100 border border-gray-300 border-l-0 text-gray-400 px-4 py-2 rounded-r cursor-not-allowed">
                                Suivant
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filtersToggle = document.getElementById('filters-toggle');
        const filtersPanel = document.getElementById('filters-panel');
        
        if (filtersToggle && filtersPanel) {
            filtersToggle.addEventListener('click', function() {
                if (filtersPanel.classList.contains('hidden')) {
                    filtersPanel.classList.remove('hidden');
                    filtersToggle.textContent = 'Masquer les filtres';
                } else {
                    filtersPanel.classList.add('hidden');
                    filtersToggle.textContent = 'Filtres avancés';
                }
            });
        }
    });
</script>
