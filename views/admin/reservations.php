<div class="container-fluid py-4">
    <?php if (isset($reservation)) : ?>
        <!-- Détail d'une réservation -->
        <div class="flex items-center mb-6">
            <a href="<?php echo SITE_URL; ?>admin/reservations.php" class="mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold">Détails de la réservation</h1>
        </div>
        
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
        
        <!-- Statut de la réservation -->
        <div class="bg-white p-6 shadow rounded-lg mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500">Réservation #<?php echo $reservation['id']; ?></p>
                    <p class="text-lg">
                        Statut : 
                        <span class="font-bold <?php echo getReservationStatusColor($reservation['statut']); ?>">
                            <?php echo getReservationStatusText($reservation['statut']); ?>
                        </span>
                    </p>
                </div>
                
                <div class="space-x-2">
                    <?php if ($reservation['statut'] === 'en_attente') : ?>
                        <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/reservation.php" class="inline-block">
                            <input type="hidden" name="action" value="accepter_reservation">
                            <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Accepter</button>
                        </form>
                        
                        <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/reservation.php" class="inline-block">
                            <input type="hidden" name="action" value="refuser_reservation">
                            <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">Refuser</button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($reservation['statut'] !== 'annulee' && $reservation['statut'] !== 'refusee') : ?>
                        <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/reservation.php" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                            <input type="hidden" name="action" value="annuler_reservation">
                            <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded">Annuler</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Informations de la réservation -->
            <div class="md:col-span-2 bg-white p-6 shadow rounded-lg">
                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-4">Détails de la réservation</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p><span class="font-semibold">Dates:</span> 
                                <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                                <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                            </p>
                            <p><span class="font-semibold">Nombre de nuits:</span> <?php echo $nbNuits; ?></p>
                            <p><span class="font-semibold">Prix par nuit:</span> <?php echo $logement['prix']; ?>€</p>
                            <p class="text-xl font-bold mt-2"><span class="font-semibold">Prix total:</span> <?php echo $reservation['prix_total']; ?>€</p>
                        </div>
                        <div>
                            <p><span class="font-semibold">Date de réservation:</span> <?php echo date('d/m/Y H:i', strtotime($reservation['date_creation'])); ?></p>
                            
                            <?php if (!empty($paiement)) : ?>
                                <p><span class="font-semibold">Statut du paiement:</span> 
                                    <span class="<?php echo getPaiementStatusColor($paiement['statut']); ?>">
                                        <?php echo getPaiementStatusText($paiement['statut']); ?>
                                    </span>
                                </p>
                                <p><span class="font-semibold">ID de transaction:</span> <?php echo $paiement['id_transaction']; ?></p>
                                <p><span class="font-semibold">Date de paiement:</span> <?php echo date('d/m/Y H:i', strtotime($paiement['date_paiement'])); ?></p>
                            <?php else : ?>
                                <p class="text-yellow-600">Aucun paiement enregistré</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Logement -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-4">Logement</h2>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-24 h-24 bg-gray-200 rounded-md overflow-hidden">
                            <img 
                                src="<?php echo !empty($logement['photo_principale']) ? SITE_URL . 'uploads/logements/' . $logement['photo_principale'] : SITE_URL . 'assets/img/placeholders/logement.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($logement['titre']); ?>"
                                class="w-full h-full object-cover"
                            >
                        </div>
                        
                        <div class="ml-4 flex-1">
                            <h3 class="font-semibold">
                                <a href="<?php echo SITE_URL; ?>admin/logements.php?id=<?php echo $logement['id']; ?>" class="text-black hover:underline">
                                    <?php echo htmlspecialchars($logement['titre']); ?>
                                </a>
                            </h3>
                            <p class="text-gray-500"><?php echo htmlspecialchars($logement['adresse'] . ', ' . $logement['code_postal'] . ' ' . $logement['ville']); ?></p>
                            <p class="mt-2">
                                <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $logement['id']; ?>" class="text-black underline" target="_blank">
                                    Voir sur le site
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Messagerie -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Messages</h2>
                    
                    <div id="messages-container" class="mb-4 h-64 overflow-y-auto border rounded p-4">
                        <?php if (empty($messages)) : ?>
                            <p class="text-gray-500 text-center">Aucun message pour le moment.</p>
                        <?php else : ?>
                            <div class="space-y-4">
                                <?php foreach ($messages as $message) : ?>
                                    <div class="p-3 rounded-lg <?php echo $message['id_expediteur'] === $proprietaire['id'] ? 'bg-blue-100 ml-12' : 'bg-gray-200 mr-12'; ?>">
                                        <p class="text-sm font-semibold">
                                            <?php 
                                            if ($message['id_expediteur'] === $proprietaire['id']) {
                                                echo 'Propriétaire: ' . htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']);
                                            } else {
                                                echo 'Locataire: ' . htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']);
                                            }
                                            ?>
                                        </p>
                                        <p class="text-sm"><?php echo nl2br(htmlspecialchars($message['contenu'])); ?></p>
                                        <p class="text-xs text-gray-500 text-right">
                                            <?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Informations utilisateurs -->
            <div class="space-y-6">
                <!-- Propriétaire -->
                <div class="bg-white p-6 shadow rounded-lg">
                    <h2 class="text-xl font-bold mb-4">Propriétaire</h2>
                    
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <img 
                                src="<?php echo !empty($proprietaire['photo_profil']) ? SITE_URL . 'uploads/profils/' . $proprietaire['photo_profil'] : SITE_URL . 'assets/img/placeholders/profil.jpg'; ?>" 
                                alt="Photo de profil"
                                class="w-12 h-12 rounded-full object-cover"
                            >
                        </div>
                        <div class="ml-4">
                            <p class="font-semibold">
                                <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $proprietaire['id']; ?>" class="text-black hover:underline">
                                    <?php echo htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']); ?>
                                </a>
                            </p>
                            <p class="text-gray-500"><?php echo htmlspecialchars($proprietaire['email']); ?></p>
                            <p class="text-gray-500"><?php echo htmlspecialchars($proprietaire['telephone']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Locataire -->
                <div class="bg-white p-6 shadow rounded-lg">
                    <h2 class="text-xl font-bold mb-4">Locataire</h2>
                    
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <img 
                                src="<?php echo !empty($locataire['photo_profil']) ? SITE_URL . 'uploads/profils/' . $locataire['photo_profil'] : SITE_URL . 'assets/img/placeholders/profil.jpg'; ?>" 
                                alt="Photo de profil"
                                class="w-12 h-12 rounded-full object-cover"
                            >
                        </div>
                        <div class="ml-4">
                            <p class="font-semibold">
                                <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $locataire['id']; ?>" class="text-black hover:underline">
                                    <?php echo htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']); ?>
                                </a>
                            </p>
                            <p class="text-gray-500"><?php echo htmlspecialchars($locataire['email']); ?></p>
                            <p class="text-gray-500"><?php echo htmlspecialchars($locataire['telephone']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Supprimer la réservation -->
                <div class="bg-white p-6 shadow rounded-lg">
                    <h2 class="text-xl font-bold mb-4">Actions</h2>
                    
                    <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/reservation.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ? Cette action est irréversible.');">
                        <input type="hidden" name="action" value="supprimer_reservation">
                        <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                        <button type="submit" class="w-full py-2 px-4 border border-red-500 text-red-500 rounded-md hover:bg-red-500 hover:text-white">
                            Supprimer la réservation
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
    <?php else : ?>
        <!-- Liste des réservations -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Gestion des réservations</h1>
            
            <form action="<?php echo SITE_URL; ?>admin/reservations.php" method="GET" class="flex">
                <input type="text" name="q" value="<?php echo htmlspecialchars($recherche ?? ''); ?>" placeholder="Rechercher une réservation..." class="border rounded-l px-4 py-2 focus:outline-none">
                <button type="submit" class="bg-black text-white px-4 py-2 rounded-r">Rechercher</button>
            </form>
        </div>
        
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
        
        <!-- Filtres -->
        <div class="bg-white p-4 shadow rounded-lg mb-6">
            <form action="<?php echo SITE_URL; ?>admin/reservations.php" method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label for="statut" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select id="statut" name="statut" class="border rounded px-3 py-2">
                        <option value="">Tous</option>
                        <option value="en_attente" <?php echo isset($filtres['statut']) && $filtres['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="acceptee" <?php echo isset($filtres['statut']) && $filtres['statut'] === 'acceptee' ? 'selected' : ''; ?>>Acceptée</option>
                        <option value="refusee" <?php echo isset($filtres['statut']) && $filtres['statut'] === 'refusee' ? 'selected' : ''; ?>>Refusée</option>
                        <option value="annulee" <?php echo isset($filtres['statut']) && $filtres['statut'] === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                        <option value="terminee" <?php echo isset($filtres['statut']) && $filtres['statut'] === 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                    </select>
                </div>
                
                <div>
                    <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($filtres['date_debut'] ?? ''); ?>" class="border rounded px-3 py-2">
                </div>
                
                <div>
                    <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($filtres['date_fin'] ?? ''); ?>" class="border rounded px-3 py-2">
                </div>
                
                <div>
                    <label for="tri" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                    <select id="tri" name="tri" class="border rounded px-3 py-2">
                        <option value="recent" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'recent' ? 'selected' : ''; ?>>Plus récentes</option>
                        <option value="ancien" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'ancien' ? 'selected' : ''; ?>>Plus anciennes</option>
                        <option value="date_debut" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'date_debut' ? 'selected' : ''; ?>>Date de début</option>
                        <option value="prix_asc" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'prix_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                        <option value="prix_desc" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'prix_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded">Filtrer</button>
                </div>
            </form>
        </div>
        
        <!-- Tableau des réservations -->
        <div class="bg-white p-6 shadow rounded-lg">
            <?php if (empty($reservations)) : ?>
                <p class="text-gray-500">Aucune réservation trouvée.</p>
            <?php else : ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logement</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Propriétaire</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Locataire</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de création</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reservations as $reservation) : ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $reservation['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?php echo SITE_URL; ?>admin/logements.php?id=<?php echo $reservation['id_logement']; ?>" class="text-black hover:underline">
                                            <?php echo htmlspecialchars($reservation['titre_logement']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $reservation['id_proprietaire']; ?>" class="text-black hover:underline">
                                            <?php echo htmlspecialchars($reservation['nom_proprietaire'] . ' ' . $reservation['prenom_proprietaire']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $reservation['id_locataire']; ?>" class="text-black hover:underline">
                                            <?php echo htmlspecialchars($reservation['nom_locataire'] . ' ' . $reservation['prenom_locataire']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $reservation['prix_total']; ?>€</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="<?php echo getReservationStatusBadgeColor($reservation['statut']); ?>">
                                            <?php echo getReservationStatusText($reservation['statut']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($reservation['date_creation'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="<?php echo SITE_URL; ?>admin/reservations.php?id=<?php echo $reservation['id']; ?>" class="text-black hover:underline">Voir</a>
                                        
                                        <?php if ($reservation['statut'] === 'en_attente') : ?>
                                            <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/reservation.php" class="inline ml-2">
                                                <input type="hidden" name="action" value="accepter_reservation">
                                                <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:underline">Accepter</button>
                                            </form>
                                            
                                            <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/reservation.php" class="inline ml-2">
                                                <input type="hidden" name="action" value="refuser_reservation">
                                                <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:underline">Refuser</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/reservation.php" class="inline ml-2" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">
                                            <input type="hidden" name="action" value="supprimer_reservation">
                                            <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1) : ?>
                    <div class="flex justify-center mt-6">
                        <div class="flex">
                            <?php if ($page > 1) : ?>
                                <a href="<?php echo SITE_URL; ?>admin/reservations.php?page=<?php echo $page - 1; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded">Précédent</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                <a href="<?php echo SITE_URL; ?>admin/reservations.php?page=<?php echo $i; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded <?php echo $i === $page ? 'bg-black text-white' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages) : ?>
                                <a href="<?php echo SITE_URL; ?>admin/reservations.php?page=<?php echo $page + 1; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded">Suivant</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
/**
 * Retourne la classe CSS pour le badge de statut de réservation
 * 
 * @param string $statut Statut de la réservation
 * @return string Classe CSS
 */
function getReservationStatusBadgeColor($statut) {
    switch ($statut) {
        case 'en_attente':
            return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800';
        case 'acceptee':
            return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
        case 'refusee':
        case 'annulee':
            return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800';
        case 'terminee':
            return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
        default:
            return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
    }
}

/**
 * Retourne la classe CSS correspondant au statut de la réservation
 * 
 * @param string $statut Statut de la réservation
 * @return string Classe CSS
 */
function getReservationStatusColor($statut) {
    switch ($statut) {
        case 'en_attente':
            return 'text-yellow-600';
        case 'acceptee':
            return 'text-green-600';
        case 'refusee':
        case 'annulee':
            return 'text-red-600';
        case 'terminee':
            return 'text-gray-600';
        default:
            return '';
    }
}

/**
 * Retourne le texte correspondant au statut de la réservation
 * 
 * @param string $statut Statut de la réservation
 * @return string Texte du statut
 */
function getReservationStatusText($statut) {
    switch ($statut) {
        case 'en_attente':
            return 'En attente';
        case 'acceptee':
            return 'Acceptée';
        case 'refusee':
            return 'Refusée';
        case 'annulee':
            return 'Annulée';
        case 'terminee':
            return 'Terminée';
        default:
            return 'Inconnu';
    }
}

/**
 * Retourne la classe CSS correspondant au statut du paiement
 * 
 * @param string $statut Statut du paiement
 * @return string Classe CSS
 */
function getPaiementStatusColor($statut) {
    switch ($statut) {
        case 'en_attente':
            return 'text-yellow-600';
        case 'complete':
            return 'text-green-600';
        case 'rembourse':
            return 'text-blue-600';
        case 'echoue':
            return 'text-red-600';
        default:
            return '';
    }
}

/**
 * Retourne le texte correspondant au statut du paiement
 * 
 * @param string $statut Statut du paiement
 * @return string Texte du statut
 */
function getPaiementStatusText($statut) {
    switch ($statut) {
        case 'en_attente':
            return 'En attente';
        case 'complete':
            return 'Complété';
        case 'rembourse':
            return 'Remboursé';
        case 'echoue':
            return 'Échoué';
        default:
            return 'Inconnu';
    }
}
?>
