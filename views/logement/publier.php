<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Publier un logement</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <form method="POST" action="<?php echo URL_SITE; ?>/controllers/logement.php?action=publier" enctype="multipart/form-data" class="mb-4">
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Informations générales</h2>
            
            <div class="mb-4">
                <label for="titre" class="block text-gray-700 mb-2">Titre</label>
                <input type="text" id="titre" name="titre" value="<?php echo isset($formData['titre']) ? htmlspecialchars($formData['titre']) : ''; ?>" class="input-field" required>
                <?php if (isset($erreurs['titre'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['titre']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label for="description" class="block text-gray-700 mb-2">Description</label>
                <textarea id="description" name="description" rows="4" class="input-field" required><?php echo isset($formData['description']) ? htmlspecialchars($formData['description']) : ''; ?></textarea>
                <?php if (isset($erreurs['description'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['description']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <label for="type_logement" class="block text-gray-700 mb-2">Type de logement</label>
                <select id="type_logement" name="type_logement" class="input-field" required>
                    <option value="entier" <?php echo (isset($formData['type_logement']) && $formData['type_logement'] === 'entier') ? 'selected' : ''; ?>>Logement entier</option>
                    <option value="collocation" <?php echo (isset($formData['type_logement']) && $formData['type_logement'] === 'collocation') ? 'selected' : ''; ?>>Collocation</option>
                    <option value="libere" <?php echo (isset($formData['type_logement']) && $formData['type_logement'] === 'libere') ? 'selected' : ''; ?>>Logement libéré</option>
                </select>
                <?php if (isset($erreurs['type_logement'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['type_logement']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="prix" class="block text-gray-700 mb-2">Prix par nuit (€)</label>
                    <input type="number" id="prix" name="prix" value="<?php echo isset($formData['prix']) ? htmlspecialchars($formData['prix']) : ''; ?>" min="1" step="0.01" class="input-field" required>
                    <?php if (isset($erreurs['prix'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['prix']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="w-1/2">
                    <label for="nb_places" class="block text-gray-700 mb-2">Nombre de places</label>
                    <input type="number" id="nb_places" name="nb_places" value="<?php echo isset($formData['nb_places']) ? htmlspecialchars($formData['nb_places']) : '1'; ?>" min="1" class="input-field" required>
                    <?php if (isset($erreurs['nb_places'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['nb_places']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Adresse</h2>
            
            <div class="mb-4">
                <label for="adresse" class="block text-gray-700 mb-2">Adresse</label>
                <input type="text" id="adresse" name="adresse" value="<?php echo isset($formData['adresse']) ? htmlspecialchars($formData['adresse']) : ''; ?>" class="input-field address-autocomplete" data-lat-field="latitude" data-lng-field="longitude" data-ville-field="ville" data-code-postal-field="code_postal" required>
                <?php if (isset($erreurs['adresse'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['adresse']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="ville" class="block text-gray-700 mb-2">Ville</label>
                    <input type="text" id="ville" name="ville" value="<?php echo isset($formData['ville']) ? htmlspecialchars($formData['ville']) : ''; ?>" class="input-field" required>
                    <?php if (isset($erreurs['ville'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['ville']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="w-1/2">
                    <label for="code_postal" class="block text-gray-700 mb-2">Code postal</label>
                    <input type="text" id="code_postal" name="code_postal" value="<?php echo isset($formData['code_postal']) ? htmlspecialchars($formData['code_postal']) : ''; ?>" class="input-field" required>
                    <?php if (isset($erreurs['code_postal'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['code_postal']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <input type="hidden" id="latitude" name="latitude" value="<?php echo isset($formData['latitude']) ? htmlspecialchars($formData['latitude']) : ''; ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo isset($formData['longitude']) ? htmlspecialchars($formData['longitude']) : ''; ?>">
            
            <div id="map-preview" class="w-full h-48 bg-gray-200 rounded-md mb-4 hidden"></div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Disponibilité</h2>
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="date_debut" class="block text-gray-700 mb-2">Disponible à partir du</label>
                    <input type="date" id="date_debut" name="date_debut" value="<?php echo isset($formData['date_debut']) ? htmlspecialchars($formData['date_debut']) : ''; ?>" class="input-field" required>
                    <?php if (isset($erreurs['date_debut'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['date_debut']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="w-1/2">
                    <label for="date_fin" class="block text-gray-700 mb-2">Jusqu'au</label>
                    <input type="date" id="date_fin" name="date_fin" value="<?php echo isset($formData['date_fin']) ? htmlspecialchars($formData['date_fin']) : ''; ?>" class="input-field" required>
                    <?php if (isset($erreurs['date_fin'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['date_fin']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Photos</h2>
            
            <div class="mb-4">
                <label for="photos" class="block text-gray-700 mb-2">Photos du logement</label>
                <input type="file" id="photos" name="photos[]" accept="image/*" multiple class="input-field">
                <p class="text-gray-500 text-sm mt-1">Vous pouvez sélectionner plusieurs photos. La première sera utilisée comme image principale.</p>
                <?php if (isset($erreurs['photos'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['photos']; ?></p>
                <?php endif; ?>
            </div>
            
            <div id="photos-preview" class="grid grid-cols-2 gap-2 mt-4"></div>
        </div>
        
        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
        
        <button type="submit" class="btn-primary w-full">Publier mon logement</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prévisualisation des photos
    const photosInput = document.getElementById('photos');
    const photosPreview = document.getElementById('photos-preview');
    
    if (photosInput && photosPreview) {
        photosInput.addEventListener('change', function() {
            photosPreview.innerHTML = '';
            
            if (this.files) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const preview = document.createElement('div');
                        preview.className = 'relative';
                        preview.innerHTML = `
                            <img src="${e.target.result}" alt="Aperçu" class="w-full h-32 object-cover rounded-md">
                            ${i === 0 ? '<div class="absolute top-2 left-2 bg-black text-white text-xs px-2 py-1 rounded-full">Principale</div>' : ''}
                        `;
                        photosPreview.appendChild(preview);
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
        });
    }
    
    // Affichage de la carte après saisie de l'adresse
    const adresseInput = document.getElementById('adresse');
    const mapPreview = document.getElementById('map-preview');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    
    if (adresseInput && mapPreview && window.googleMaps) {
        adresseInput.addEventListener('change', function() {
            if (this.value.trim() !== '') {
                if (latitudeInput.value && longitudeInput.value) {
                    mapPreview.classList.remove('hidden');
                    // La carte sera initialisée par Google Maps API
                }
            }
        });
    }
});
</script>
