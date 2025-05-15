<div class="container-mobile mx-auto py-8">
    <h1 class="text-3xl font-bold mb-6">Inscription</h1>
    
    <?php if (isset($erreurs['general'])) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erreurs['general']; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo SITE_URL; ?>inscription.php" class="mb-4">
        <div class="mb-4">
            <label for="nom" class="block text-gray-700 mb-2">Nom</label>
            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom']); ?>" class="input-field <?php echo isset($erreurs['nom']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['nom'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['nom']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="prenom" class="block text-gray-700 mb-2">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($formData['prenom']); ?>" class="input-field <?php echo isset($erreurs['prenom']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['prenom'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['prenom']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-gray-700 mb-2">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" class="input-field <?php echo isset($erreurs['email']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['email'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['email']; ?></p>
            <?php endif; ?>
            <p class="text-gray-500 text-sm mt-1">Utilisez votre adresse email institutionnelle (@omnesintervenant.com, @ece.fr, @edu.ece.fr).</p>
        </div>
        
        <div class="mb-4">
            <label for="telephone" class="block text-gray-700 mb-2">Téléphone</label>
            <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($formData['telephone']); ?>" class="input-field <?php echo isset($erreurs['telephone']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['telephone'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['telephone']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="mot_de_passe" class="block text-gray-700 mb-2">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" class="input-field <?php echo isset($erreurs['mot_de_passe']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['mot_de_passe'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['mot_de_passe']; ?></p>
            <?php endif; ?>
            <p class="text-gray-500 text-sm mt-1">8 caractères minimum.</p>
        </div>
        
        <div class="mb-6">
            <label for="confirmer_mot_de_passe" class="block text-gray-700 mb-2">Confirmer le mot de passe</label>
            <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" class="input-field <?php echo isset($erreurs['confirmer_mot_de_passe']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['confirmer_mot_de_passe'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['confirmer_mot_de_passe']; ?></p>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn-primary">S'inscrire</button>
    </form>
    
    <div class="text-center mt-4">
        <p>Vous avez déjà un compte ? <a href="<?php echo SITE_URL; ?>connexion.php" class="text-black underline">Se connecter</a></p>
    </div>
</div>
