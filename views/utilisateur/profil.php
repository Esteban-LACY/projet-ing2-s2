<div class="container-mobile mx-auto py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Mon profil</h1>
    
    <?php if (!empty($message_succes)) : ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $message_succes; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($message_erreur)) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $message_erreur; ?>
        </div>
    <?php endif; ?>
    
    <!-- Informations de profil -->
    <div class="mb-8 text-center">
        <div class="relative inline-block mb-4">
            <img 
                id="profile-image-preview"
                src="<?php echo !empty($utilisateur['photo_profil']) ? SITE_URL . 'uploads/profils/' . $utilisateur['photo_profil'] : SITE_URL . 'assets/img/placeholders/profil.jpg'; ?>" 
                alt="Photo de profil"
                class="profile-avatar"
            >
            <button id="upload-photo-btn" class="absolute bottom-0 right-0 bg-black text-white rounded-full p-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </button>
            <input type="file" id="photo-input" name="photo" accept="image/*" class="hidden">
        </div>
        
        <h2 id="nom-affiche" class="text-2xl font-bold"><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h2>
        <p id="email-affiche" class="text-gray-500"><?php echo htmlspecialchars($utilisateur['email']); ?></p>
        
        <button id="modifier-profil-btn" class="btn-secondary mt-4">Modifier le profil</button>
    </div>
    
    <!-- Onglets -->
    <div class="mb-6">
        <div class="flex border-b">
            <button class="tab-button flex-1 py-2 font-semibold text-center bg-black text-white" data-tab="mes-locations">Mes locations</button>
            <button class="tab-button flex-1 py-2 font-semibold text-center bg-gray-200 text-gray-700" data-tab="mes-logements">Mes logements</button>
        </div>
    </div>
    
    <!-- Contenu des onglets -->
    <div>
        <!-- Mes locations -->
        <div id="mes-locations" class="tab-content">
            <?php if (empty($locations)) : ?>
                <div class="text-center py-8">
                    <p class="text-lg">Vous n'avez aucune location en cours.</p>
                    <a href="<?php echo SITE_URL; ?>recherche.php" class="btn-primary mt-4 inline-block">Rechercher un logement</a>
                </div>
            <?php else : ?>
                <div class="space-y-6">
                    <?php foreach ($locations as $location) : ?>
                        <div class="card p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-20 h-20 bg-gray-200 rounded-md overflow-hidden">
                                    <img 
                                        src="<?php echo !empty($location['photo_principale']) ? SITE_URL . 'uploads/logements/' . $location['photo_principale'] : SITE_URL . 'assets/img/placeholders/logement.jpg'; ?>" 
                                        alt="<?php echo htmlspecialchars($location['titre']); ?>"
                                        class="w-full h-full object-cover"
                                    >
                                </div>
                                
                                <div class="ml-4 flex-1">
                                    <h3 class="font-semibold"><?php echo htmlspecialchars($location['titre']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($location['ville']); ?></p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Dates :</span> 
                                        <?php echo date('d/m/Y', strtotime($location['date_debut'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($location['date_fin'])); ?>
                                    </p>
                                    <p class="text-sm"><span class="font-semibold">Prix :</span> <?php echo $location['prix_total']; ?>€</p>
                                </div>
                                
                                <div class="flex-shrink-0">
                                    <a href="<?php echo SITE_URL; ?>reservation/details.php?id=<?php echo $location['id']; ?>" class="text-black underline">Détails</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Mes logements -->
        <div id="mes-logements" class="tab-content hidden">
            <?php if (empty($logements)) : ?>
                <div class="text-center py-8">
                    <p class="text-lg">Vous n'avez aucun logement publié.</p>
                    <a href="<?php echo SITE_URL; ?>publier.php" class="btn-primary mt-4 inline-block">Publier un logement</a>
                </div>
            <?php else : ?>
                <div class="space-y-6">
                    <?php foreach ($logements as $logement) : ?>
                        <div class="card p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-20 h-20 bg-gray-200 rounded-md overflow-hidden">
                                    <img 
                                        src="<?php echo !empty($logement['photo_principale']) ? SITE_URL . 'uploads/logements/' . $logement['photo_principale'] : SITE_URL . 'assets/img/placeholders/logement.jpg'; ?>" 
                                        alt="<?php echo htmlspecialchars($logement['titre']); ?>"
                                        class="w-full h-full object-cover"
                                    >
                                </div>
                                
                                <div class="ml-4 flex-1">
                                    <h3 class="font-semibold"><?php echo htmlspecialchars($logement['titre']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($logement['ville']); ?></p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Disponibilité :</span> 
                                        <?php echo date('d/m/Y', strtotime($logement['date_debut'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($logement['date_fin'])); ?>
                                    </p>
                                    <p class="text-sm"><span class="font-semibold">Prix :</span> <?php echo $logement['prix']; ?>€ / nuit</p>
                                </div>
                                
                                <div class="flex-shrink-0">
                                    <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $logement['id']; ?>" class="text-black block mb-2 underline">Voir</a>
                                    <a href="<?php echo SITE_URL; ?>logement/modifier.php?id=<?php echo $logement['id']; ?>" class="text-black underline">Modifier</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-6">
                    <a href="<?php echo SITE_URL; ?>publier.php" class="btn-secondary">Publier un nouveau logement</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Formulaire de modification de profil (caché par défaut) -->
    <div id="modifier-profil-form" class="hidden mt-8">
        <h2 class="text-2xl font-bold mb-4">Modifier le profil</h2>
        
        <form id="profile-form" method="POST" action="<?php echo SITE_URL; ?>controllers/utilisateur.php?action=modifier_profil" class="mb-6">
            <div class="mb-4">
                <label for="nom" class="block text-gray-700 mb-2">Nom</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" class="input-field" required>
            </div>
            
            <div class="mb-4">
                <label for="prenom" class="block text-gray-700 mb-2">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" class="input-field" required>
            </div>
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 mb-2">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" class="input-field" required>
                <p class="text-gray-500 text-sm mt-1">Utilisez votre adresse email institutionnelle (@omnesintervenant.com, @ece.fr, @edu.ece.fr).</p>
            </div>
            
            <div class="mb-4">
                <label for="telephone" class="block text-gray-700 mb-2">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($utilisateur['telephone']); ?>" class="input-field" required>
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="btn-primary flex-1">Enregistrer</button>
                <button type="button" id="annuler-modification" class="btn-secondary flex-1">Annuler</button>
            </div>
        </form>
        
        <h2 class="text-2xl font-bold mb-4">Modifier le mot de passe</h2>
        
        <form id="password-form" method="POST" action="<?php echo SITE_URL; ?>controllers/utilisateur.php?action=modifier_mot_de_passe" class="mb-6">
            <div class="mb-4">
                <label for="ancien_mot_de_passe" class="block text-gray-700 mb-2">Mot de passe actuel</label>
                <input type="password" id="ancien_mot_de_passe" name="ancien_mot_de_passe" class="input-field" required>
            </div>
            
            <div class="mb-4">
                <label for="nouveau_mot_de_passe" class="block text-gray-700 mb-2">Nouveau mot de passe</label>
                <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" class="input-field" required>
                <p class="text-gray-500 text-sm mt-1">8 caractères minimum.</p>
            </div>
            
            <div class="mb-4">
                <label for="confirmer_mot_de_passe" class="block text-gray-700 mb-2">Confirmer le nouveau mot de passe</label>
                <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" class="input-field" required>
            </div>
            
            <button type="submit" class="btn-primary w-full">Changer le mot de passe</button>
        </form>
        
        <div class="text-center mt-8">
            <button id="delete-account-btn" class="text-red-500 underline">Supprimer mon compte</button>
        </div>
    </div>
    
    <!-- Bouton de déconnexion -->
    <div class="text-center mt-8">
        <a href="<?php echo SITE_URL; ?>deconnexion.php" class="btn-secondary">Se déconnecter</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion du formulaire de modification
        const modifierProfilBtn = document.getElementById('modifier-profil-btn');
        const modifierProfilForm = document.getElementById('modifier-profil-form');
        const annulerModificationBtn = document.getElementById('annuler-modification');
        
        if (modifierProfilBtn && modifierProfilForm && annulerModificationBtn) {
            modifierProfilBtn.addEventListener('click', function() {
                modifierProfilBtn.parentElement.classList.add('hidden');
                modifierProfilForm.classList.remove('hidden');
            });
            
            annulerModificationBtn.addEventListener('click', function() {
                modifierProfilForm.classList.add('hidden');
                modifierProfilBtn.parentElement.classList.remove('hidden');
            });
        }
    });
</script>
