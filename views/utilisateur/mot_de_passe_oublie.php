<div class="container-mobile mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Mot de passe oublié</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <p class="mb-6">Entrez votre adresse email ci-dessous. Nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
    
    <form method="POST" action="<?php echo URL_SITE; ?>/utilisateur/mot_de_passe_oublie.php" class="mb-6">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 mb-2">Email</label>
            <input type="email" id="email" name="email" class="input-field" required>
        </div>
        
        <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
        
        <button type="submit" class="btn-primary mb-4">Envoyer le lien de réinitialisation</button>
    </form>
    
    <div class="text-center">
        <a href="<?php echo URL_SITE; ?>/utilisateur/connexion.php" class="text-black underline">
            Retour à la connexion
        </a>
    </div>
</div>
