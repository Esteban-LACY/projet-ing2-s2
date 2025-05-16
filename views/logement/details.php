<div class="container-mobile mx-auto px-4 py-8 mb-safe">
    <?php if (isset($_GET['publie']) && $_GET['publie'] == 1): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p>Votre logement a été publié avec succès!</p>
        </div>
    <?php endif; ?>
    
    <?php include CHEMIN_VUES . '/commun/messages.php'; ?>
    
    <div class="relative mb-6">
        <div class="photo-gallery">
            <?php if (!empty($photos)): ?>
                <div class="main-photo">
                    <img src="<?php echo urlPhotoLogement($photos[0]['url']); ?>" alt="<?php echo htmlspecialchars($logement['titre']); ?>" class="w-full h-64 object-cover rounded-lg">
                </div>
                
                <?php if (count($photos) > 1): ?>
                    <div class="thumbnail-container mt-2 flex space-x-2 overflow-x-auto">
                        <?php for ($i = 0; $i < count($photos); $i++): ?>
                            <div class="thumbnail-item flex-shrink-0">
                                <img src="<?php echo urlPhotoLogement($photos[$i]['url']); ?>" alt="Photo <?php echo $i+1; ?>" class="h-16 w-16 object-cover rounded <?php echo $i === 0 ? 'border-2 border-black' : ''; ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="main-photo">
                    <img src="<?php echo urlAsset('img/placeholders/logement.jpg'); ?>" alt="<?php echo htmlspecialchars($logement['titre']); ?>" class="w-full h-64 object-cover rounded-lg">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="absolute top-2 right-2">
            <button class="bg-white rounded-full p-2 shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        </div>
    </div>
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($logement['titre']); ?></h1>
        <p class="text-gray-600"><?php echo htmlspecialchars($logement['adresse']); ?>, <?php echo htmlspecialchars($logement['code_postal']); ?> <?php echo htmlspecialchars($logement['ville']); ?></p>
        
        <div class="mt-2 flex space-x-4">
            <span class="inline-flex items-center bg-gray-100 px-2.5 py-1 rounded-full text-sm font-medium text-gray-800">
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
            </span>
            <span class="inline-flex items-center bg-gray-100 px-2.5 py-1 rounded-full text-sm font-medium text-gray-800">
                <?php echo $logement['nb_places']; ?> place<?php echo $logement['nb_places'] > 1 ? 's' : ''; ?>
            </span>
        </div>
    </div>
    
    <?php if ($logement['type_logement'] === 'libere'): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Ce logement est signalé comme étant libéré. Contactez directement le propriétaire ou l'agence pour plus d'informations.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden mr-4">
                <?php if (!empty($proprietaire['photo_profil'])): ?>
                    <img src="<?php echo urlPhotoProfil($proprietaire['photo_profil']); ?>" alt="Photo de <?php echo htmlspecialchars($proprietaire['prenom']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-full w-full text-gray-500 p-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                <?php endif; ?>
            </div>
            <div>
                <h3 class="font-semibold">Logement proposé par <?php echo htmlspecialchars($proprietaire['prenom']); ?></h3>
                <p class="text-gray-600 text-sm">Membre depuis <?php echo date('M Y', strtotime($proprietaire['date_creation'])); ?></p>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-4">
            <h3 class="font-semibold mb-2">Description</h3>
            <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($logement['description'])); ?></p>
        </div>
    </div>
    
    <?php if ($logement['type_logement'] !== 'libere'): ?>
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="font-semibold mb-4">Disponibilités</h3>
            
            <?php if (empty($disponibilites)): ?>
                <p class="text-gray-600">Aucune disponibilité pour le moment.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($disponibilites as $disponibilite): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <div>
                                <span class="font-medium"><?php echo formaterDate($disponibilite['date_debut']); ?></span>
                                <span class="mx-2">→</span>
                                <span class="font-medium"><?php echo formaterDate($disponibilite['date_fin']); ?></span>
                            </div>
                            
                            <?php
                            $estDisponible = true;
                            foreach ($reservations as $reservation) {
                                if ($reservation['statut'] === 'acceptee' || $reservation['statut'] === 'en_attente') {
                                    // Vérifier si la période est déjà réservée
                                    if (!(strtotime($reservation['date_fin']) <= strtotime($disponibilite['date_debut']) || 
                                          strtotime($reservation['date_debut']) >= strtotime($disponibilite['date_fin']))) {
                                        $estDisponible = false;
                                        break;
                                    }
                                }
                            }
                            ?>
                            
                            <?php if ($estDisponible): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Disponible
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Non disponible
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="font-semibold mb-4">Réserver ce logement</h3>
            
            <?php if (!estConnecte()): ?>
                <p class="text-gray-600 mb-4">Vous devez être connecté pour réserver ce logement.</p>
                <a href="<?php echo URL_SITE; ?>/utilisateur/connexion.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-primary">Se connecter</a>
            <?php elseif ($logement['id_proprietaire'] == $_SESSION['utilisateur_id']): ?>
                <p class="text-gray-600">Vous ne pouvez pas réserver votre propre logement.</p>
            <?php else: ?>
                <form action="<?php echo URL_SITE; ?>/reservation/confirmer.php" method="GET">
                    <div class="mb-4">
                        <label for="date_debut" class="block text-gray-700 mb-2">Date d'arrivée</label>
                        <input type="date" id="date_debut" name="date_debut" class="input-field" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="date_fin" class="block text-gray-700 mb-2">Date de départ</label>
                        <input type="date" id="date_fin" name="date_fin" class="input-field" required>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex justify-between mb-2">
                            <span><?php echo $logement['prix']; ?>€ x <span id="nb-nuits">0</span> nuits</span>
                            <span id="total-price">0€</span>
                        </div>
                        
                        <div class="flex justify-between mb-2">
                            <span>Frais de service (<?php echo FRAIS_SERVICE_POURCENTAGE; ?>%)</span>
                            <span id="service-fee">0€</span>
                        </div>
                        
                        <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-200 mt-2">
                            <span>Total</span>
                            <span id="grand-total">0€</span>
                        </div>
                    </div>
                    
                    <input type="hidden" name="id_logement" value="<?php echo $logement['id']; ?>">
                    
                    <button type="submit" class="btn-primary w-full mt-4">Réserver</button>
                </form>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const dateDebutInput = document.getElementById('date_debut');
                        const dateFinInput = document.getElementById('date_fin');
                        const nbNuitsElement = document.getElementById('nb-nuits');
                        const totalPriceElement = document.getElementById('total-price');
                        const serviceFeeElement = document.getElementById('service-fee');
                        const grandTotalElement = document.getElementById('grand-total');
                        const prixParNuit = <?php echo $logement['prix']; ?>;
                        const fraisServicePourcentage = <?php echo FRAIS_SERVICE_POURCENTAGE; ?>;
                        
                        // Définir une date minimale (aujourd'hui)
                        const today = new Date();
                        const yyyy = today.getFullYear();
                        const mm = String(today.getMonth() + 1).padStart(2, '0');
                        const dd = String(today.getDate()).padStart(2, '0');
                        const todayString = `${yyyy}-${mm}-${dd}`;
                        
                        dateDebutInput.min = todayString;
                        
                        // Mettre à jour la date de fin minimale lorsque la date de début change
                        dateDebutInput.addEventListener('change', function() {
                            if (dateDebutInput.value) {
                                dateFinInput.min = dateDebutInput.value;
                                
                                // Si la date de fin est avant la date de début, réinitialiser
                                if (dateFinInput.value && dateFinInput.value < dateDebutInput.value) {
                                    dateFinInput.value = '';
                                }
                            }
                            
                            updateCalculation();
                        });
                        
                        dateFinInput.addEventListener('change', updateCalculation);
                        
                        function updateCalculation() {
                            if (dateDebutInput.value && dateFinInput.value) {
                                const dateDebut = new Date(dateDebutInput.value);
                                const dateFin = new Date(dateFinInput.value);
                                
                                // Calculer le nombre de nuits
                                const diffTime = dateFin - dateDebut;
                                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                
                                if (diffDays > 0) {
                                    // Mettre à jour l'affichage
                                    nbNuitsElement.textContent = diffDays;
                                    
                                    const totalPrice = diffDays * pr
