<div class="container-fluid py-4">
    <?php if (isset($utilisateur)) : ?>
        <!-- Détail d'un utilisateur -->
        <div class="flex items-center mb-6">
            <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php" class="mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold">Détails de l'utilisateur</h1>
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
            <div class="bg-white p-6 shadow rounded-lg">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0">
                        <img 
                            src="<?php echo !empty($utilisateur['photo_profil']) ? SITE_URL . 'uploads/profils/' . $utilisateur['photo_profil'] : SITE_URL . 'assets/img/placeholders/profil.jpg'; ?>" 
                            alt="Photo de profil"
                            class="w-20 h-20 rounded-full object-cover"
                        >
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h2>
                        <p class="text-gray-500">ID: <?php echo $utilisateur['id']; ?></p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-2">Informations personnelles</h3>
                    <p><span class="font-semibold">Email:</span> <?php echo htmlspecialchars($utilisateur['email']); ?></p>
                    <p><span class="font-semibold">Téléphone:</span> <?php echo htmlspecialchars($utilisateur['telephone']); ?></p>
                    <p>
                        <span class="font-semibold">Compte vérifié:</span> 
                        <?php if ($utilisateur['est_verifie']) : ?>
                            <span class="text-green-600">Oui</span>
                        <?php else : ?>
                            <span class="text-red-600">Non</span>
                        <?php endif; ?>
                    </p>
                    <p>
                        <span class="font-semibold">Rôle:</span> 
                        <?php if ($utilisateur['est_admin']) : ?>
                            <span class="text-indigo-600">Administrateur</span>
                        <?php else : ?>
                            Utilisateur
                        <?php endif; ?>
                    </p>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-2">Dates</h3>
                    <p><span class="font-semibold">Inscription:</span> <?php echo date('d/m/Y H:i', strtotime($utilisateur['date_creation'])); ?></p>
                    <p><span class="font-semibold">Dernière connexion:</span> 
                        <?php echo $utilisateur['derniere_connexion'] ? date('d/m/Y H:i', strtotime($utilisateur['derniere_connexion'])) : 'Jamais'; ?>
                    </p>
                </div>
                
                <div class="mt-6 space-y-2">
                    <?php if (!$utilisateur['est_verifie']) : ?>
                        <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/utilisateur.php">
                            <input type="hidden" name="action" value="verifier_utilisateur">
                            <input type="hidden" name="id_utilisateur" value="<?php echo $utilisateur['id']; ?>">
                            <button type="submit" class="btn-primary w-full">Vérifier le compte</button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/utilisateur.php" onsubmit="return confirm('Êtes-vous sûr de vouloir modifier le rôle de cet utilisateur ?');">
                        <input type="hidden" name="action" value="modifier_role">
                        <input type="hidden" name="id_utilisateur" value="<?php echo $utilisateur['id']; ?>">
                        <input type="hidden" name="est_admin" value="<?php echo $utilisateur['est_admin'] ? '0' : '1'; ?>">
                        <button type="submit" class="btn-secondary w-full">
                            <?php echo $utilisateur['est_admin'] ? 'Retirer les droits d\'administrateur' : 'Promouvoir administrateur'; ?>
                        </button>
                    </form>
                    
                    <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/utilisateur.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                        <input type="hidden" name="action" value="supprimer_utilisateur">
                        <input type="hidden" name="id_utilisateur" value="<?php echo $utilisateur['id']; ?>">
                        <button type="submit" class="w-full py-2 px-4 border border-red-500 text-red-500 rounded-md hover:bg-red-500 hover:text-white">
                            Supprimer l'utilisateur
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Logements de l'utilisateur -->
            <div class="bg-white p-6 shadow rounded-lg">
                <h2 class="text-xl font-bold mb-4">Logements publiés</h2>
                
                <?php if (empty($logements)) : ?>
                    <p class="text-gray-500">Cet utilisateur n'a publié aucun logement.</p>
                <?php else : ?>
                    <div class="space-y-4">
                        <?php foreach ($logements as $logement) : ?>
                            <div class="border-b pb-4 last:border-b-0">
                                <h3 class="font-semibold">
                                    <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $logement['id']; ?>" class="text-black hover:underline">
                                        <?php echo htmlspecialchars($logement['titre']); ?>
                                    </a>
                                </h3>
                                <p class="text-gray-500"><?php echo htmlspecialchars($logement['ville']); ?></p>
                                <p class="text-sm"><?php echo $logement['prix']; ?>€ / nuit</p>
                                <p class="text-sm">
                                    <span class="font-semibold">Type:</span> 
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
                                <p class="text-sm">
                                    <span class="font-semibold">Créé le:</span> 
                                    <?php echo date('d/m/Y', strtotime($logement['date_creation'])); ?>
                                </p>
                                <p class="mt-2">
                                    <a href="<?php echo SITE_URL; ?>admin/logements.php?id=<?php echo $logement['id']; ?>" class="text-black underline">
                                        Gérer
                                    </a>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Réservations de l'utilisateur -->
            <div class="bg-white p-6 shadow rounded-lg">
                <h2 class="text-xl font-bold mb-4">Réservations</h2>
                
                <ul class="mb-4 flex border-b">
                    <li class="mr-1">
                        <a class="inline-block py-2 px-4 <?php echo $onglet_reservation === 'locataire' ? 'bg-black text-white' : 'bg-gray-200 text-gray-700'; ?>" href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $utilisateur['id']; ?>&onglet=locataire">
                            En tant que locataire
                        </a>
                    </li>
                    <li class="mr-1">
                        <a class="inline-block py-2 px-4 <?php echo $onglet_reservation === 'bailleur' ? 'bg-black text-white' : 'bg-gray-200 text-gray-700'; ?>" href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $utilisateur['id']; ?>&onglet=bailleur">
                            En tant que bailleur
                        </a>
                    </li>
                </ul>
                
                <?php if ($onglet_reservation === 'locataire') : ?>
                    <?php if (empty($reservationsLocataire)) : ?>
                        <p class="text-gray-500">Cet utilisateur n'a effectué aucune réservation.</p>
                    <?php else : ?>
                        <div class="space-y-4">
                            <?php foreach ($reservationsLocataire as $reservation) : ?>
                                <div class="border-b pb-4 last:border-b-0">
                                    <h3 class="font-semibold">
                                        <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $reservation['id_logement']; ?>" class="text-black hover:underline">
                                            <?php echo htmlspecialchars($reservation['titre_logement']); ?>
                                        </a>
                                    </h3>
                                    <p class="text-sm">
                                        <span class="font-semibold">Dates:</span> 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                    </p>
                                    <p class="text-sm"><span class="font-semibold">Prix:</span> <?php echo $reservation['prix_total']; ?>€</p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Statut:</span> 
                                        <span class="<?php echo getReservationStatusColor($reservation['statut']); ?>">
                                            <?php echo getReservationStatusText($reservation['statut']); ?>
                                        </span>
                                    </p>
                                    <p class="mt-2">
                                        <a href="<?php echo SITE_URL; ?>admin/reservations.php?id=<?php echo $reservation['id']; ?>" class="text-black underline">
                                            Gérer
                                        </a>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <?php if (empty($reservationsBailleur)) : ?>
                        <p class="text-gray-500">Cet utilisateur n'a reçu aucune réservation.</p>
                    <?php else : ?>
                        <div class="space-y-4">
                            <?php foreach ($reservationsBailleur as $reservation) : ?>
                                <div class="border-b pb-4 last:border-b-0">
                                    <h3 class="font-semibold">
                                        <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $reservation['id_logement']; ?>" class="text-black hover:underline">
                                            <?php echo htmlspecialchars($reservation['titre_logement']); ?>
                                        </a>
                                    </h3>
                                    <p class="text-sm">
                                        <span class="font-semibold">Locataire:</span> 
                                        <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $reservation['id_locataire']; ?>" class="text-black hover:underline">
                                            <?php echo htmlspecialchars($reservation['nom_locataire'] . ' ' . $reservation['prenom_locataire']); ?>
                                        </a>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Dates:</span> 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                    </p>
                                    <p class="text-sm"><span class="font-semibold">Prix:</span> <?php echo $reservation['prix_total']; ?>€</p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Statut:</span> 
                                        <span class="<?php echo getReservationStatusColor($reservation['statut']); ?>">
                                            <?php echo getReservationStatusText($reservation['statut']); ?>
                                        </span>
                                    </p>
                                    <p class="mt-2">
                                        <a href="<?php echo SITE_URL; ?>admin/reservations.php?id=<?php echo $reservation['id']; ?>" class="text-black underline">
                                            Gérer
                                        </a>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else : ?>
        <!-- Liste des utilisateurs -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Gestion des utilisateurs</h1>
            
            <form action="<?php echo SITE_URL; ?>admin/utilisateurs.php" method="GET" class="flex">
                <input type="text" name="q" value="<?php echo htmlspecialchars($recherche ?? ''); ?>" placeholder="Rechercher un utilisateur..." class="border rounded-l px-4 py-2 focus:outline-none">
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
            <form action="<?php echo SITE_URL; ?>admin/utilisateurs.php" method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
                    <select id="role" name="role" class="border rounded px-3 py-2">
                        <option value="">Tous</option>
                        <option value="admin" <?php echo isset($filtres['role']) && $filtres['role'] === 'admin' ? 'selected' : ''; ?>>Administrateurs</option>
                        <option value="user" <?php echo isset($filtres['role']) && $filtres['role'] === 'user' ? 'selected' : ''; ?>>Utilisateurs</option>
                    </select>
                </div>
                
                <div>
                    <label for="verifie" class="block text-sm font-medium text-gray-700 mb-1">Vérification</label>
                    <select id="verifie" name="verifie" class="border rounded px-3 py-2">
                        <option value="">Tous</option>
                        <option value="oui" <?php echo isset($filtres['verifie']) && $filtres['verifie'] === 'oui' ? 'selected' : ''; ?>>Vérifiés</option>
                        <option value="non" <?php echo isset($filtres['verifie']) && $filtres['verifie'] === 'non' ? 'selected' : ''; ?>>Non vérifiés</option>
                    </select>
                </div>
                
                <div>
                    <label for="tri" class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                    <select id="tri" name="tri" class="border rounded px-3 py-2">
                        <option value="recent" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'recent' ? 'selected' : ''; ?>>Plus récents</option>
                        <option value="ancien" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'ancien' ? 'selected' : ''; ?>>Plus anciens</option>
                        <option value="nom" <?php echo isset($filtres['tri']) && $filtres['tri'] === 'nom' ? 'selected' : ''; ?>>Nom</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded">Filtrer</button>
                </div>
            </form>
        </div>
        
        <!-- Tableau des utilisateurs -->
        <div class="bg-white p-6 shadow rounded-lg">
            <?php if (empty($utilisateurs)) : ?>
                <p class="text-gray-500">Aucun utilisateur trouvé.</p>
            <?php else : ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vérifié</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($utilisateurs as $user) : ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img 
                                                    src="<?php echo !empty($user['photo_profil']) ? SITE_URL . 'uploads/profils/' . $user['photo_profil'] : SITE_URL . 'assets/img/placeholders/profil.jpg'; ?>" 
                                                    alt="Photo de profil"
                                                    class="h-10 w-10 rounded-full object-cover"
                                                >
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['telephone']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($user['est_verifie']) : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Oui</span>
                                        <?php else : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($user['est_admin']) : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Oui</span>
                                        <?php else : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?id=<?php echo $user['id']; ?>" class="text-black hover:underline">Voir</a>
                                        
                                        <?php if (!$user['est_verifie']) : ?>
                                            <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/utilisateur.php" class="inline ml-2">
                                                <input type="hidden" name="action" value="verifier_utilisateur">
                                                <input type="hidden" name="id_utilisateur" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:underline">Vérifier</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" action="<?php echo SITE_URL; ?>admin/controllers/utilisateur.php" class="inline ml-2" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                            <input type="hidden" name="action" value="supprimer_utilisateur">
                                            <input type="hidden" name="id_utilisateur" value="<?php echo $user['id']; ?>">
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
                                <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?page=<?php echo $page - 1; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded">Précédent</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?page=<?php echo $i; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded <?php echo $i === $page ? 'bg-black text-white' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages) : ?>
                                <a href="<?php echo SITE_URL; ?>admin/utilisateurs.php?page=<?php echo $page + 1; ?>&<?php echo $query_string; ?>" class="px-4 py-2 mx-1 border rounded">Suivant</a>
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
?>
