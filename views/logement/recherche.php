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
