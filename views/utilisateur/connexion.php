<div class="container-mobile mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Connexion</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <form method="POST" action="<?php echo URL_SITE; ?>/utilisateur/connexion.php" class="mb-6">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 mb-2">Email</label>
            <input type="email" id="email" name="email" class="input-field" required>
        </div>
        
        <div class="mb-4">
            <label for="mot_de_passe" class="block text-gray-700 mb-2">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" class="input-field" required>
        </div>
        
        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
        
        <button type="submit" class="btn-primary mb-4">Se connecter</button>
        
        <div class="text-center">
            <a href="<?php echo URL_SITE; ?>/utilisateur/mot_de_passe_oublie.php" class="text-black text-sm underline">
                Mot de passe oublié ?
            </a>
        </div>
    </form>
    
    <div class="text-center">
        <p class="mb-4">Vous n'avez pas de compte ?</p>
        <a href="<?php echo URL_SITE; ?>/utilisateur/inscription.php" class="btn-secondary">
            Créer un compte
        </a>
    </div>
</div>
