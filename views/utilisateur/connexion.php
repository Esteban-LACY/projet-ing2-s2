<div class="container-mobile mx-auto py-8">
    <h1 class="text-3xl font-bold mb-6">Connexion</h1>
    
    <?php if (!empty($erreur)) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erreur; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo SITE_URL; ?>connexion.php" class="mb-4">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 mb-2">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="input-field" required>
        </div>
        
        <div class="mb-6">
            <label for="mot_de_passe" class="block text-gray-700 mb-2">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" class="input-field" required>
        </div>
        
        <button type="submit" class="btn-primary">Se connecter</button>
    </form>
    
    <div class="text-center mt-4">
        <p>Vous n'avez pas de compte ? <a href="<?php echo SITE_URL; ?>inscription.php" class="text-black underline">S'inscrire</a></p>
        <p class="mt-2"><a href="<?php echo SITE_URL; ?>mot-de-passe-oublie.php" class="text-black underline">Mot de passe oublié ?</a></p>
    </div>
</div>
