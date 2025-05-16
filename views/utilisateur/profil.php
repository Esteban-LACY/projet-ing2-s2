<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Mon profil</h1>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <div class="text-center mb-8">
        <div class="profile-avatar mb-2">
            <?php if (!empty($utilisateur['photo_profil'])): ?>
                <img src="<?php echo urlPhotoProfil($utilisateur['photo_profil']); ?>" alt="Photo de profil" class="rounded-full">
            <?php else: ?>
                <div class="w-full h-full bg-gray-200 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            <?php endif; ?>
        </div>
        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h2>
        <p class="text-gray-600"><?php echo htmlspecialchars($utilisateur['email']); ?></p>
        
        <div class="mt-4">
            <button id="upload-photo-btn" class="text-black underline text-sm">
                Modifier ma photo de profil
            </button>
            <input type="file" id="photo-input" name="photo" accept="image/*" class="hidden">
        </div>
    </div>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-xl font-bold mb-4">Informations personnelles</h3>
        
        <form id="profile-form" method="POST" action="<?php echo URL_SITE; ?>/utilisateur/modifier_profil.php">
            <div class="mb-4">
                <label for="prenom" class="block text-gray-700 mb-2">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" class="input-field" required>
            </div>
            
            <div class="mb-4">
                <label for="nom" class="block text-gray-700 mb-2">Nom</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" class="input-field" required>
            </div>
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 mb-2">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" class="input-field" required>
                <p class="text-gray-500 text-sm mt-1">Si vous changez d'email, vous devrez confirmer votre nouvelle adresse.</p>
            </div>
            
            <div class="mb-4">
                <label for="telephone" class="block text-gray-700 mb-2">Numéro de téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($utilisateur['telephone']); ?>" class="input-field" required>
            </div>
            
            <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
            
            <button type="submit" class="btn-primary">Sauvegarder les modifications</button>
        </form>
    </div>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-xl font-bold mb-4">Modifier le mot de passe</h3>
        
        <form id="password-form" method="POST" action="<?php echo URL_SITE; ?>/utilisateur/modifier_mot_de_passe.php">
            <div class="mb-4">
                <label for="ancien_mot_de_passe" class="block text-gray-700 mb-2">Mot de passe actuel</label>
                <input type="password" id="ancien_mot_de_passe" name="ancien_mot_de_passe" class="input-field" required>
            </div>
            
            <div class="mb-4">
                <label for="nouveau_mot_de_passe" class="block text-gray-700 mb-2">Nouveau mot de passe</label>
                <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" class="input-field" required>
                <p class="text-gray-500 text-sm mt-1">Au moins 8 caractères</p>
            </div>
            
            <div class="mb-4">
                <label for="confirmer_mot_de_passe" class="block text-gray-700 mb-2">Confirmer le nouveau mot de passe</label>
                <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" class="input-field" required>
            </div>
            
            <input type="hidden" name="csrf_token" value="<?php echo genererToken(); ?>">
            
            <button type="submit" class="btn-primary">Changer le mot de passe</button>
        </form>
    </div>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-xl font-bold mb-4">Bilan financier</h3>
        
        <div class="flex justify-between mb-4">
            <div>
                <p class="font-semibold">Revenus</p>
                <p class="text-green-600 font-bold"><?php echo formaterPrix($bilanFinancier['revenus']); ?></p>
            </div>
            <div>
                <p class="font-semibold">Dépenses</p>
                <p class="text-red-600 font-bold"><?php echo formaterPrix($bilanFinancier['depenses']); ?></p>
            </div>
            <div>
                <p class="font-semibold">Solde</p>
                <p class="<?php echo $bilanFinancier['solde'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                    <?php echo formaterPrix($bilanFinancier['solde']); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="text-center mb-6">
        <button id="delete-account-btn" class="text-red-600 underline text-sm">
            Supprimer mon compte
        </button>
    </div>
    
    <div class="mb-4">
        <a href="<?php echo URL_SITE; ?>/utilisateur/deconnexion.php" class="btn-secondary block text-center">
            Se déconnecter
        </a>
    </div>
</div>
