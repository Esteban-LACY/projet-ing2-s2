<div class="container-mobile mx-auto px-4 py-6 mb-safe">
    <h1 class="text-2xl font-bold mb-4">Publier un logement</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <form method="POST" action="<?php echo URL_SITE; ?>/logement/publier.php" enctype="multipart/form-data" class="mb-4">
        <div class="mb-4">
            <label for="titre" class="block text-gray-700 mb-2">Titre</label>
            <input type="text" id="titre" name="titre" value="<?php echo isset($formData['titre']) ? htmlspecialchars($formData['titre']) : ''; ?>" class="input-field" required>
            <?php if (isset($erreurs['titre'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['titre']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="adresse" class="block text-gray-700 mb-2">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?php echo isset($formData['adresse']) ? htmlspecialchars($formData['adresse']) : ''; ?>" class="input-field address-autocomplete" data-lat-field="latitude" data-lng-field="longitude" data-ville-field="ville" data-code-postal-field="code_postal" required>
            <?php if (isset($erreurs['adresse'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['adresse']; ?></p>
            <?php endif; ?>
            <input type="hidden" id="latitude" name="latitude" value="<?php echo isset($formData['latitude']) ? htmlspecialchars($formData['latitude']) : ''; ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo isset($formData['longitude']) ? htmlspecialchars($formData['longitude']) : ''; ?>">
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w
