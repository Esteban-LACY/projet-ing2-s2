<div class="container-mobile mx-auto px-4 py-8 text-center">
    <?php if ($verification_reussie): ?>
        <div class="bg-green-100 rounded-lg p-8 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h1 class="text-3xl font-bold mb-4">Email vérifié avec succès</h1>
            <p class="mb-6">Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant vous connecter à votre compte.</p>
            <a href="<?php echo URL_SITE; ?>/utilisateur/connexion.php" class="btn-primary inline-block">Se connecter</a>
        </div>
    <?php else: ?>
        <div class="bg-red-100 rounded-lg p-8 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h1 class="text-3xl font-bold mb-4">Vérification échouée</h1>
            <p class="mb-6"><?php echo $message_erreur; ?></p>
            <a href="<?php echo URL_SITE; ?>/utilisateur/connexion.php" class="btn-primary inline-block">Retour à la connexion</a>
        </div>
    <?php endif; ?>
</div>
