<div class="container-mobile mx-auto py-4 mb-safe">
    <?php if (isset($_GET['publie']) && $_GET['publie'] == 1) : ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Votre logement a été publié avec succès !
        </div>
    <?php endif; ?>
    
    <?php if (!empty($erreur)) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erreur; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($logement)) : ?>
        <div class="text-center py-8">
            <p class="text-lg">Ce logement n'existe pas ou a été supprimé.</p>
            <a href="<?php echo SITE_URL; ?>" class="btn-primary mt-4 inline-block">Retour à l'accueil</a>
        </div>
    <?php else : ?>
        <!-- Photos du logement -->
        <div class="mb-6">
            <div class="relative overflow-hidden rounded-lg" style="height: 250px;">
                <?php if (!empty($photos) && count($photos) > 0) : ?>
                    <img 
                        src="<?php echo SITE_URL . 'uploads/logements/' . $photos[0]['url']; ?>" 
                        alt="<?php echo htmlspecialchars($logement['titre']); ?>" 
                        class="w-full h-full object-cover"
                    >
                <?php else : ?>
                    <img 
                        src="<?php echo SITE_URL; ?>assets/img/placeholders/logement.jpg" 
                        alt="<?php echo htmlspecialchars($logement['titre']); ?>" 
                        class="w-full h-full object-cover"
                    >
                <?php endif; ?>
                
                <?php if (!empty($photos) && count($photos) > 1) : ?>
                    <button id="voir-photos" class="absolute bottom-4 right-4 bg-white text-black rounded-full px-3 py-2 text-sm font-semibold">
                        Voir toutes les photos
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informations logement -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($logement['titre']); ?></h1>
            <p class="text-gray-500 mb-2"><?php echo htmlspecialchars($logement['adresse'] . ', ' . $logement['code_postal'] . ' ' . $logement['ville']); ?></p>
            
            <div class="flex items-center mb-4">
                <p class="text-xl font-bold"><?php echo $logement['prix']; ?>€</p>
                <span class="text-gray-500 ml-1">/ nuit</span>
            </div>
            
            <p class="mb-2">
                <span class="font-semibold">Type :</span> 
                <?php 
                switch ($logement['type_logement']) {
                    case 'entier':
                        echo 'Logement entier';
                        break;
                    case 'collocation':
                        echo 'Collocation';
                        break;
                    case 'libere':
                        echo 'Logement libéré';
                        break;
                }
                ?>
            </p>
            
            <p class="mb-2">
                <span class="font-semibold">Places :</span> 
                <?php echo $logement['nb_places']; ?> personne<?php echo $logement['nb_places'] > 1 ? 's' : ''; ?>
            </p>
            
            <p class="mb-4">
                <span class="font-semibold">Disponibilité :</span> 
                <?php echo date('d/m/Y', strtotime($disponibilite['date_debut'])); ?> - 
                <?php echo date('d/m/Y', strtotime($disponibilite['date_fin'])); ?>
            </p>
            
            <div class="mt-4">
                <h2 class="text-xl font-bold mb-2">Description</h2>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($logement['description'])); ?></p>
            </div>
        </div>
        
        <!-- Carte -->
        <div class="mb-6">
            <h2 class="text-xl font-bold mb-2">Emplacement</h2>
            <div id="detail-map" class="w-full h-48 bg-gray-200 rounded-lg" data-lat="<?php echo $logement['latitude']; ?>" data-lng="<?php echo $logement['longitude']; ?>"></div>
        </div>
        
        <!-- Propriétaire -->
        <div class="mb-6">
            <h2 class="text-xl font-bold mb-2">Proposé par</h2>
            <div class="flex items-center">
                <img 
                    src="<?php echo !empty($proprietaire['photo_profil']) ? SITE_URL . 'uploads/profils/' . $proprietaire['photo_profil'] : SITE_URL . 'assets/img/placeholders/profil.jpg'; ?>" 
                    alt="Photo de <?php echo htmlspecialchars($proprietaire['prenom']); ?>"
                    class="w-12 h-12 rounded-full object-cover"
                >
                <div class="ml-4">
                    <p class="font-semibold"><?php echo htmlspecialchars($proprietaire['prenom'] . ' ' . substr($proprietaire['nom'], 0, 1) . '.'); ?></p>
                    <p class="text-gray-500 text-sm">Membre depuis <?php echo date('m/Y', strtotime($proprietaire['date_creation'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Réservation -->
        <?php if (estConnecte() && $_SESSION['utilisateur']['id'] !== $logement['id_proprietaire']) : ?>
            <div class="mb-6 p-4 bg-gray-100 rounded-lg">
                <h2 class="text-xl font-bold mb-2">Réserver</h2>
                
                <form id="reservation-form" method="POST" action="<?php echo SITE_URL; ?>reservation/confirmer.php">
                    <input type="hidden" name="id_logement" value="<?php echo $logement['id']; ?>">
                    
                    <div class="flex space-x-4 mb-4">
                        <div class="w-1/2">
                            <label for="date_debut" class="block text-gray-700 mb-2">Date d'arrivée</label>
                            <input type="date" id="date_debut" name="date_debut" min="<?php echo $disponibilite['date_debut']; ?>" max="<?php echo $disponibilite['date_fin']; ?>" class="input-field" required>
                        </div>
                        
                        <div class="w-1/2">
                            <label for="date_fin" class="block text-gray-700 mb-2">Date de départ</label>
                            <input type="date" id="date_fin" name="date_fin" min="<?php echo $disponibilite['date_debut']; ?>" max="<?php echo $disponibilite['date_fin']; ?>" class="input-field" required>
                        </div>
                    </div>
                    
                    <div id="reservation-summary" class="hidden mb-4 p-3 bg-white rounded">
                        <p><span class="font-semibold">Prix par nuit :</span> <?php echo $logement['prix']; ?>€</p>
                        <p><span class="font-semibold">Nombre de nuits :</span> <span id="nb-nuits">0</span></p>
                        <p class="text-lg font-bold mt-2"><span class="font-semibold">Total :</span> <span id="prix-total">0</span>€</p>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full">Réserver</button>
                </form>
            </div>
        <?php elseif (!estConnecte()) : ?>
            <div class="mb-6 p-4 bg-gray-100 rounded-lg text-center">
                <p class="mb-2">Vous devez être connecté pour réserver ce logement.</p>
                <a href="<?php echo SITE_URL; ?>connexion.php" class="btn-primary inline-block">Se connecter</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal photos -->
<div id="photos-modal" class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center hidden">
    <div class="relative w-full max-w-3xl p-4">
        <button id="fermer-modal" class="absolute top-4 right-4 text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php if (!empty($photos)) : ?>
                    <?php foreach ($photos as $photo) : ?>
                        <div class="swiper-slide">
                            <img 
                                src="<?php echo SITE_URL . 'uploads/logements/' . $photo['url']; ?>" 
                                alt="Photo du logement"
                                class="w-full h-auto max-h-screen"
                            >
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion de la carte
        if (window.googleMaps) {
            window.googleMaps.init('<?php echo GOOGLE_MAPS_API_KEY; ?>');
        }
        
        // Gestion de la galerie photos
        const voirPhotosBtn = document.getElementById('voir-photos');
        const photosModal = document.getElementById('photos-modal');
        const fermerModalBtn = document.getElementById('fermer-modal');
        
        if (voirPhotosBtn && photosModal && fermerModalBtn) {
            voirPhotosBtn.addEventListener('click', function() {
                photosModal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                
                // Initialiser Swiper
                if (typeof Swiper !== 'undefined') {
                    new Swiper('.swiper-container', {
                        pagination: {
                            el: '.swiper-pagination',
                        },
                        navigation: {
                            nextEl: '.swiper-button-next',
                            prevEl: '.swiper-button-prev',
                        },
                    });
                }
            });
            
            fermerModalBtn.addEventListener('click', function() {
                photosModal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            });
        }
        
        // Calcul du prix total
        const dateDebutInput = document.getElementById('date_debut');
        const dateFinInput = document.getElementById('date_fin');
        const nbNuitsElement = document.getElementById('nb-nuits');
        const prixTotalElement = document.getElementById('prix-total');
        const reservationSummary = document.getElementById('reservation-summary');
        
        if (dateDebutInput && dateFinInput && nbNuitsElement && prixTotalElement && reservationSummary) {
            function calculerPrix() {
                const dateDebut = new Date(dateDebutInput.value);
                const dateFin = new Date(dateFinInput.value);
                
                if (dateDebut && dateFin && dateDebut < dateFin) {
                    // Calcul du nombre de nuits
                    const diffTime = Math.abs(dateFin - dateDebut);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    // Mise à jour de l'affichage
                    nbNuitsElement.textContent = diffDays;
                    prixTotalElement.textContent = (diffDays * <?php echo $logement['prix']; ?>).toFixed(2);
                    
                    // Afficher le résumé
                    reservationSummary.classList.remove('hidden');
                } else {
                    reservationSummary.classList.add('hidden');
                }
            }
            
            dateDebutInput.addEventListener('change', calculerPrix);
            dateFinInput.addEventListener('change', calculerPrix);
        }
    });
</script>
