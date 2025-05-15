<div class="container-mobile mx-auto py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Publier un logement</h1>
    
    <?php if (isset($erreurs['general'])) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erreurs['general']; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($succes) : ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Votre logement a été publié avec succès !
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo SITE_URL; ?>publier.php" enctype="multipart/form-data" class="mb-4">
        <div class="mb-4">
            <label for="titre" class="block text-gray-700 mb-2">Titre</label>
            <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($formData['titre']); ?>" class="input-field <?php echo isset($erreurs['titre']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['titre'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['titre']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="adresse" class="block text-gray-700 mb-2">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($formData['adresse']); ?>" class="input-field address-autocomplete <?php echo isset($erreurs['adresse']) ? 'border-red-500' : ''; ?>" data-lat-field="latitude" data-lng-field="longitude" data-ville-field="ville" data-code-postal-field="code_postal" required>
            <?php if (isset($erreurs['adresse'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['adresse']; ?></p>
            <?php endif; ?>
            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($formData['latitude'] ?? ''); ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($formData['longitude'] ?? ''); ?>">
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="ville" class="block text-gray-700 mb-2">Ville</label>
                <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($formData['ville']); ?>" class="input-field <?php echo isset($erreurs['ville']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['ville'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['ville']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="w-1/2">
                <label for="code_postal" class="block text-gray-700 mb-2">Code postal</label>
                <input type="text" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($formData['code_postal']); ?>" class="input-field <?php echo isset($erreurs['code_postal']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['code_postal'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['code_postal']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 mb-2">Description</label>
            <textarea id="description" name="description" rows="4" class="input-field <?php echo isset($erreurs['description']) ? 'border-red-500' : ''; ?>"><?php echo htmlspecialchars($formData['description']); ?></textarea>
            <?php if (isset($erreurs['description'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['description']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="type_logement" class="block text-gray-700 mb-2">Type de logement</label>
            <select id="type_logement" name="type_logement" class="input-field">
                <option value="entier" <?php echo $formData['type_logement'] === 'entier' ? 'selected' : ''; ?>>Logement entier</option>
                <option value="collocation" <?php echo $formData['type_logement'] === 'collocation' ? 'selected' : ''; ?>>Collocation</option>
                <option value="libere" <?php echo $formData['type_logement'] === 'libere' ? 'selected' : ''; ?>>Logement libéré</option>
            </select>
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="prix" class="block text-gray-700 mb-2">Prix par nuit (€)</label>
                <input type="number" id="prix" name="prix" value="<?php echo htmlspecialchars($formData['prix']); ?>" min="1" step="0.01" class="input-field <?php echo isset($erreurs['prix']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['prix'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['prix']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="w-1/2">
                <label for="nb_places" class="block text-gray-700 mb-2">Nombre de places</label>
                <input type="number" id="nb_places" name="nb_places" value="<?php echo htmlspecialchars($formData['nb_places']); ?>" min="1" class="input-field" required>
            </div>
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="date_debut" class="block text-gray-700 mb-2">Date de début</label>
                <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($formData['date_debut']); ?>" class="input-field <?php echo isset($erreurs['date_debut']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['date_debut'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['date_debut']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="w-1/2">
                <label for="date_fin" class="block text-gray-700 mb-2">Date de fin</label>
                <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($formData['date_fin']); ?>" class="input-field <?php echo isset($erreurs['date_fin']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['date_fin'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['date_fin']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-6">
            <label for="photos" class="block text-gray-700 mb-2">Photos (facultatif)</label>
            <input type="file" id="photos" name="photos[]" multiple accept="image/*" class="input-field">
            <p class="text-gray-500 text-sm mt-1">Vous pouvez sélectionner plusieurs photos. La première sera utilisée comme image principale.</p>
        </div>
        
        <button type="submit" class="btn-primary">Publier</button>
    </form>
</div>
