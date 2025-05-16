<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Résultats</h1>
        <a href="<?php echo URL_SITE; ?>/logement/recherche.php?<?php echo http_build_query($filtres); ?>" class="text-black underline">
            Modifier la recherche
        </a>
    </div>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <div class="mb-4 py-2 border-b border-gray-200">
        <p class="text-gray-700">
            <?php echo $total; ?> logement<?php echo $total > 1 ? 's' : ''; ?> trouvé<?php echo $total > 1 ? 's' : ''; ?>
            <?php if (!empty($filtres['lieu'])): ?>
                à <strong><?php echo htmlspecialchars($filtres['lieu']); ?></strong>
            <?php endif; ?>
            <?php if (!empty($filtres['date_debut']) && !empty($filtres['date_fin'])): ?>
                du <strong><?php echo formaterDate($filtres['date_debut']); ?></strong> au <strong><?php echo formaterDate($filtres['date_fin']); ?></strong>
            <?php endif; ?>
        </p>
    </div>
    
    <?php if ($useGoogleMaps): ?>
        <div id="search-map" class="h-60 mb-6 rounded-lg shadow" data-logements='<?php echo json_encode($resultats); ?>'></div>
    <?php endif; ?>
    
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
        
        <?php if ($pagination['total_pages'] > 1): ?>
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
</div>
