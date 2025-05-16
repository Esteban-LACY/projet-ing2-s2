<div class="container-mobile mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Inscription</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <form method="POST" action="<?php echo URL_SITE; ?>/utilisateur/inscription.php" class="mb-4" enctype="multipart/form-data">
        <div class="mb-4">
            <label for="prenom" class="block text-gray-700 mb-2">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?php echo isset($formData['prenom']) ? htmlspecialchars($formData['prenom']) : ''; ?>" class="input-field" required>
            <?php if (isset($erreurs['prenom'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['prenom']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="nom" class="block text-gray-700 mb-2">Nom</label>
            <input type="text" id="nom" name="nom" value="<?php echo isset($formData['nom']) ? htmlspecialchars($formData['nom']) : ''; ?>" class="input-field" required>
            <?php if (isset($erreurs['nom'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['nom']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-gray-700 mb-2">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>" class="input-field" required>
            <?php if (isset($erreurs['email'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['email']; ?></p>
            <?php endif; ?>
            <p class="text-gray-500 text-sm mt-1">Utilisez une adresse email institutionnelle (@omnesintervenant.com, @ece.fr, etc.)</p>
        </div>
        
        <div class="mb-4">
            <label for="telephone" class="block text-gray-700 mb-2">Numéro de téléphone</label>
            <input type="tel" id="telephone" name="telephone" value="<?php echo isset($formData['telephone']) ? htmlspecialchars($formData['telephone']) : ''; ?>" class="input-field" required>
            <?php if (isset($erreurs['telephone'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['telephone']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="mot_de_passe" class="block text-gray-700 mb-2">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" class="input-field" required>
            <?php if (isset($erreurs['mot_de_passe'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['mot_de_passe']; ?></p>
            <?php endif; ?>
            <p class="text-gray-500 text-sm mt-1">Au moins 8 caractères</p>
        </div>
        
        <div class="mb-4">
            <label for="confirmer_mot_de_passe" class="block text-gray-700 mb-2">Confirmer le mot de passe</label>
            <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" class="input-field" required>
            <?php if (isset($erreurs['confirmer_mot_de_passe'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['confirmer_mot_de_passe']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-6">
            <label for="photo" class="block text-gray-700 mb-2">Photo de profil (optionnelle)</label>
            <input type="file" id="photo" name="photo" accept="image/*" class="input-field">
            <?php if (isset($erreurs['photo'])): ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['photo']; ?></p>
            <?php endif; ?>
        </div>
        
        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
        
        <button type="submit" class="btn-primary">S'inscrire</button>
    </form>
    
    <div class="text-center mt-4">
        <p>Vous avez déjà un compte ?</p>
        <a href="<?php echo URL_SITE; ?>/utilisateur/connexion.php" class="text-black underline">Se connecter</a>
    </div>
</div>
