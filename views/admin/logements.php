<div class="container-fluid py-4">
    <?php if (isset($logement)) : ?>
        <!-- Détail d'un logement -->
        <div class="flex items-center mb-6">
            <a href="<?php echo SITE_URL; ?>admin/logements.php" class="mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold">Détails du logement</h1>
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
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Informations de base -->
            <div class="md:col-span-2 bg-white p-6 shadow rounded-lg">
                <div class="mb-6">
                    <div class="flex justify-between items-start">
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($logement['titre']); ?></h2>
                        <p>ID: <?php echo $logement['id']; ?></p>
                    </div>
                    <p class="text-gray-500"><?php echo htmlspecialchars($logement['adresse'] . ', ' . $logement['code_postal'] . ' ' . $logement['ville']); ?></p>
                </div>
                
                <!-- Photos -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-2">Photos</h3>
                    
                    <?php if (empty($photos)) : ?>
                        <p class="text-gray-500">Aucune photo disponible.</p>
                    <?php else : ?>
                        <div class="grid grid-cols-3 gap-4">
                            <?php foreach ($photos as $photo) : ?>
                                <div class="relative">
                                    <img 
                                        src="<?php echo SITE_URL . 'uploads/logements/' . $photo['url']; ?>" 
                                        alt="Photo du logement"
                                        class="w-full h-32 object-cover rounded"
                                    >
                                    <?php if ($photo['est_principale']) : ?>
                                        <span class="absolute top-2 right-2 px-2 py-1 bg-black text-white text-xs rounded">Principale</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Description -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-2">Description</h3>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($logement['description'])); ?></p>
                </div>
                
                <!-- Caractéristiques -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-2">Caractéristiques</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p><span class="font-semibold">Type:</span> 
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
                            <p><span class="font-semibold">Prix:</span> <?php echo $logement['prix']; ?>€ / nuit</p>
                            <p><span class="font-semibold">Nombre de places:</span> <?php echo $logement['nb_places']; ?></p>
                        </div>
                        <div>
                            <p><span class="font-semibold">Date de création:</span> <?php echo date('d/m/Y H:i', strtotime($logement['date_creation'])); ?></p>
                            <p><span class="font-semibold">Disponibilité:</span> 
                                <?php echo date('d/m/Y', strtotime($disponibilite['date_debut'])); ?> - 
                                <?php echo date('d/m/Y', strtotime($disponibilite['date_fin'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Carte -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-2">Emplacement</h3>
                    <div id="detail-map" class="w-full h-64 bg-gray-200 rounded-lg" data-lat="<?php echo $logement['latitude']; ?>" data-lng="<?php echo $logement['longitude']; ?>"></div>
                </div>
                
                <!-- Actions -->
                <div class="flex space-x-4">
                    <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $logement['id']; ?>" class="btn-secondary" target="_blank">
                        Voir sur le site
                    </a>
                    
                    <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/logement.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce logement ? Cette action est irréversible.');">
                        <input type="hidden" name="action" value="supprimer_logement">
                        <input type="hidden" name="id_logement" value="<?php echo $logement['id']; ?>">
                        <button type="submit" class="py-2 px-4 border border-red-500 text-red-500 rounded-md hover:bg-red-500 hover:text-white">
                            Supprimer le logement
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Propriétaire et réservations -->
            <div class="space-y-6">
                <!-- Propriétaire -->
                <div class="bg-white p-6 shadow rounded-lg">
                    <h3 class="text-lg font-bold mb-4">Propriétaire</h3>
                    
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
                
                <!-- Réservations -->
                <div class="bg-white p-6 shadow rounded-lg">
                    <h3 class="text-lg font-bold mb-4">Réservations</h3>
                    
                    <?php if (empty($reservations)) : ?>
                        <p class="text-gray-500">Aucune réservation pour ce logement.</p>
                    <?php else : ?>
                        <div class="space-y-4">
                            <?php foreach ($reservations as $reservation) : ?>
                                <div class="border-b pb-4 last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p>
                                                <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $reservation['id_locataire']; ?>" class="font-semibold text-black hover:underline">
                                                    <?php echo htmlspecialchars($reservation['prenom_locataire'] . ' ' . $reservation['nom_locataire']); ?>
                                                </a>
                                            </p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($reservation['email_locataire']); ?></p>
                                        </div>
                                        <span class="<?php echo getReservationStatusBadgeColor($reservation['statut']); ?>">
                                            <?php echo getReservationStatusText($reservation['statut']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm">
                                        <span class="font-semibold">Dates:</span> 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                    </p>
                                    <p class="text-sm"><span class="font-semibold">Prix:</span> <?php echo $reservation['prix_total']; ?>€</p>
                                    <p class="text-sm"><span class="font-semibold">Réservé le:</span> <?php echo date('d/m/Y H:i', strtotime($reservation['date_creation'])); ?></p>
                                    <p class="mt-2">
                                        <a href="<?php echo SITE_URL; ?>admin/reservations.php?id=<?php echo $reservation['id']; ?>" class="text-black underline">
                                            Gérer
                                        </a>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    <?php else : ?>
        <!-- Liste des logements -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Gestion des logements</h1>
            
            <form action="<?php echo SITE_URL; ?>admin/logements.php" method="GET" class="flex">
                <input type="text" name="q" value="<?php echo htmlspecialchars($recherche ?? ''); ?>" placeholder="Rechercher un logement..." class="border rounded-l px-4 py-2 focus:outline-none">
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
            <form action="<?php echo SITE_URL; ?>admin/logements.php" method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type de logement</label>
                    <select id="type" name="type" class="border rounded px-3 py-2">
                        <option value="">Tous</option>
                        <option value="entier" <?php echo isset($filtres['type']) && $filtres['type'] === 'entier' ? 'selected' : ''; ?>>Logement entier</option>
                        <option value="collocation" <?php echo isset($filtres['type']) && $filtres['type'] === 'collocation' ? 'selected' : ''; ?>>Collocation</option>
                        <option value="libere" <?php echo isset($filtres['type']) && $filtres['type'] === 'libere' ? 'selected' : ''; ?>>Logement libéré</option>
                    </select>
                </div>
                
                <div>
                    <label for="ville" class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                    <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($filtres['ville'] ?? ''); ?>" class="border rounded px-3 py-2">
                </div>
                
                <div>
                    <label for="prix_min" class="block text-sm font-medium text-gray-700 mb-1">Prix min (€)</label>
                    <input type="number" id="prix_min" name="prix_min" value="<?php echo htmlspecialchars($filtres['prix_min'] ?? ''); ?>" min="0" class="border rounded px-3 py-2 w-24">
                </div>
                
                <div>
                    <label for="prix_max" class="block text-sm font-medium text-gray-700 mb-1">Prix max (€)</label>
                    <input type="number" id="prix_max" name="prix_max" value="<?php echo htmlspecialchars($filtres['prix_max'] ?? ''); ?>" min="0" class="border rounded px-3 py-2 w-24">
                </div>
                
                <div>
                    <label for="tri" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                    <select id="tri" name="tri" class="border rounded px-3 py-2">
                        <option value="recent" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'recent' ? 'selected' : ''; ?>>Plus récents</option>
                        <option value="ancien" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'ancien' ? 'selected' : ''; ?>>Plus anciens</option>
                        <option value="prix_asc" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'prix_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                        <option value="prix_desc" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'prix_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded">Filtrer</button>
                </div>
            </form>
        </div>
        
        <!-- Tableau des logements -->
        <div class="bg-white p-6 shadow rounded-lg">
            <?php if (empty($logements)) : ?>
                <p class="text-gray-500">Aucun logement trouvé.</p>
            <?php else : ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logement</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Propriétaire</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ville</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de création</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($logements as $logement) : ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $logement['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img 
                                                    src="<?php echo !empty($logement['photo_principale']) ? SITE_URL . 'uploads/logements/' . $logement['photo_principale'] : SITE_URL . 'assets/img/placeholders/logement.jpg'; ?>" 
                                                    alt="Photo du logement"
                                                    class="h-10 w-10 rounded object-cover"
                                                >
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($logement['titre']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $logement['id_proprietaire']; ?>" class="text-black hover:underline">
                                            <?php echo htmlspecialchars($logement['nom_proprietaire'] . ' ' . $logement['prenom_proprietaire']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($logement['ville']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $logement['prix']; ?>€ / nuit</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($logement['date_creation'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="<?php echo SITE_URL; ?>admin/logements.php?id=<?php echo $logement['id']; ?>" class="text-black hover:underline">Voir</a>
                                        
                                        <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/logement.php" class="inline ml-2" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce logement ?');">
                                            <input type="hidden" name="action" value="supprimer_logement">
                                            <input type="hidden" name="id_logement" value="<?php echo $logement['id']; ?>">
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
                                <a href="<?php echo SITE_URL; ?>admin/logements.php?page=<?php echo $page - 1; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded">Précédent</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                <a href="<?php echo SITE_URL; ?>admin/logements.php?page=<?php echo $i; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded <?php echo $i === $page ? 'bg-black text-white' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages) : ?>
                                <a href="<?php echo SITE_URL; ?>admin/logements.php?page=<?php echo $page + 1; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded">Suivant</a>
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
?>
