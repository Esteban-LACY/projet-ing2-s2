<div class="container-mobile mx-auto px-4 py-6 mb-safe">
    <h1 class="text-2xl font-bold mb-4">Modifier le logement</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <form method="POST" action="<?php echo URL_SITE; ?>/logement/modifier.php" enctype="multipart/form-data" class="mb-4">
        <div class="mb-4">
            <label for="titre" class="block text-gray-700 mb-2">Titre</label>
            <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($logement['titre']); ?>" class="input-field" required>
            <?php if (isset($erreurs['titre'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['titre']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="adresse" class="block text-gray-700 mb-2">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($logement['adresse']); ?>" class="input-field address-autocomplete" data-lat-field="latitude" data-lng-field="longitude" data-ville-field="ville" data-code-postal-field="code_postal" required>
            <?php if (isset($erreurs['adresse'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['adresse']; ?></p>
            <?php endif; ?>
            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($logement['latitude']); ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($logement['longitude']); ?>">
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="ville" class="block text-gray-700 mb-2">Ville</label>
                <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($logement['ville']); ?>" class="input-field" required>
                <?php if (isset($erreurs['ville'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['ville']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="w-1/2">
                <label for="code_postal" class="block text-gray-700 mb-2">Code postal</label>
                <input type="text" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($logement['code_postal']); ?>" class="input-field" required>
                <?php if (isset($erreurs['code_postal'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['code_postal']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 mb-2">Description</label>
            <textarea id="description" name="description" rows="4" class="input-field" required><?php echo htmlspecialchars($logement['description']); ?></textarea>
            <?php if (isset($erreurs['description'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['description']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="type_logement" class="block text-gray-700 mb-2">Type de logement</label>
            <select id="type_logement" name="type_logement" class="input-field" required>
                <option value="entier" <?php echo $logement['type_logement'] === 'entier' ? 'selected' : ''; ?>>Logement entier</option>
                <option value="collocation" <?php echo $logement['type_logement'] === 'collocation' ? 'selected' : ''; ?>>Collocation</option>
                <option value="libere" <?php echo $logement['type_logement'] === 'libere' ? 'selected' : ''; ?>>Logement libéré</option>
            </select>
            <?php if (isset($erreurs['type_logement'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['type_logement']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="prix" class="block text-gray-700 mb-2">Prix par nuit (€)</label>
                <input type="number" id="prix" name="prix" value="<?php echo htmlspecialchars($logement['prix']); ?>" min="1" step="0.01" class="input-field" required>
                <?php if (isset($erreurs['prix'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['prix']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="w-1/2">
                <label for="nb_places" class="block text-gray-700 mb-2">Nombre de places</label>
                <input type="number" id="nb_places" name="nb_places" value="<?php echo htmlspecialchars($logement['nb_places']); ?>" min="1" class="input-field" required>
                <?php if (isset($erreurs['nb_places'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['nb_places']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-6">
            <h3 class="font-bold text-lg mb-3">Photos actuelles</h3>
            
            <?php if (!empty($photos)): ?>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <?php foreach ($photos as $photo): ?>
                        <div class="relative">
                            <img src="<?php echo urlPhotoLogement($photo['url']); ?>" alt="Photo du logement" class="w-full h-40 object-cover rounded">
                            <div class="absolute top-2 right-2 flex space-x-2">
                                <?php if (!$photo['est_principale']): ?>
                                    <a href="<?php echo URL_SITE; ?>/logement/definir_photo_principale.php?id=<?php echo $photo['id']; ?>&id_logement=<?php echo $logement['id']; ?>" class="bg-white rounded-full p-1 shadow">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo URL_SITE; ?>/logement/supprimer_photo.php?id=<?php echo $photo['id']; ?>&id_logement=<?php echo $logement['id']; ?>" class="bg-white rounded-full p-1 shadow" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette photo ?');">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </a>
                            </div>
                            <?php if ($photo['est_principale']): ?>
                                <div class="absolute bottom-2 left-2">
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        Photo principale
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mb-4">Aucune photo pour ce logement</p>
            <?php endif; ?>
            
            <label for="nouvelles_photos" class="block text-gray-700 mb-2">Ajouter des photos</label>
            <input type="file" id="nouvelles_photos" name="nouvelles_photos[]" multiple accept="image/*" class="input-field">
            <p class="text-gray-500 text-sm mt-1">Vous pouvez sélectionner plusieurs photos.</p>
        </div>
        
        <div class="mb-6">
            <h3 class="font-bold text-lg mb-3">Périodes de disponibilité</h3>
            
            <?php if (!empty($disponibilites)): ?>
                <div class="space-y-4 mb-4">
                    <?php foreach ($disponibilites as $dispo): ?>
                        <div class="flex justify-between items-center bg-gray-100 p-4 rounded">
                            <div>
                                <p>Du <?php echo formaterDate($dispo['date_debut']); ?></p>
                                <p>Au <?php echo formaterDate($dispo['date_fin']); ?></p>
                            </div>
                            <a href="<?php echo URL_SITE; ?>/logement/supprimer_disponibilite.php?id=<?php echo $dispo['id']; ?>&id_logement=<?php echo $logement['id']; ?>" class="text-red-500" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette période ?');">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mb-4">Aucune période de disponibilité définie</p>
            <?php endif; ?>
            
            <div class="bg-gray-100 p-4 rounded">
                <h4 class="font-semibold mb-2">Ajouter une nouvelle période</h4>
                <div class="flex space-x-4 mb-2">
                    <div class="w-1/2">
                        <label for="nouvelle_date_debut" class="block text-gray-700 mb-2">Date de début</label>
                        <input type="date" id="nouvelle_date_debut" name="nouvelle_date_debut" class="input-field">
                    </div>
                    
                    <div class="w-1/2">
                        <label for="nouvelle_date_fin" class="block text-gray-700 mb-2">Date de fin</label>
                        <input type="date" id="nouvelle_date_fin" name="nouvelle_date_fin" class="input-field">
                    </div>
                </div>
            </div>
        </div>
        
        <input type="hidden" name="id_logement" value="<?php echo $logement['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
        
        <div class="flex space-x-4">
            <button type="submit" class="btn-primary flex-1">Sauvegarder les modifications</button>
            <a href="<?php echo URL_SITE; ?>/logement/supprimer.php?id=<?php echo $logement['id']; ?>" class="btn-secondary flex-1 bg-red-600 text-white hover:bg-red-700" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce logement ? Cette action est irréversible.');">
                Supprimer le logement
            </a>
        </div>
    </form>
</div>
