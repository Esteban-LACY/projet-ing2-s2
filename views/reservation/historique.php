<div class="container-mobile mx-auto py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Mes locations</h1>
    
    <!-- Onglets -->
    <div class="mb-6">
        <div class="flex border-b">
            <button class="tab-button flex-1 py-2 font-semibold text-center bg-black text-white" data-tab="reservations-locataire">Je loue</button>
            <button class="tab-button flex-1 py-2 font-semibold text-center bg-gray-200 text-gray-700" data-tab="reservations-bailleur">Je propose</button>
        </div>
    </div>
    
    <!-- Contenu des onglets -->
    <div>
        <!-- Réservations en tant que locataire -->
        <div id="reservations-locataire" class="tab-content">
            <?php if (empty($reservationsLocataire)) : ?>
                <div class="text-center py-8">
                    <p class="text-lg">Vous n'avez aucune location en cours.</p>
                    <a href="<?php echo SITE_URL; ?>recherche.php" class="btn-primary mt-4 inline-block">Rechercher un logement</a>
                </div>
            <?php else : ?>
                <div class="space-y-6">
                    <?php foreach ($reservationsLocataire as $reservation) : ?>
                        <div class="card p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-20 h-20 bg-gray-200 rounded-md overflow-hidden">
                                    <img 
                                        src="<?php echo !empty($reservation['photo_principale']) ? SITE_URL . 'uploads/logements/' . $reservation['photo_principale'] : SITE_URL . 'assets/img/placeholders/logement.jpg'; ?>" 
                                        alt="<?php echo htmlspecialchars($reservation['titre']); ?>"
                                        class="w-full h-full object-cover"
                                    >
                                </div>
                                
                                <div class="ml-4 flex-1">
                                    <h3 class="font-semibold"><?php echo htmlspecialchars($reservation['titre']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($reservation['ville']); ?></p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Dates :</span> 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Statut :</span>
                                        <span class="<?php echo getReservationStatusColor($reservation['statut']); ?>">
                                            <?php echo getReservationStatusText($reservation['statut']); ?>
                                        </span>
                                    </p>
                                    <p class="text-sm"><span class="font-semibold">Prix :</span> <?php echo $reservation['prix_total']; ?>€</p>
                                </div>
                                
                                <div class="flex-shrink-0">
                                    <a href="<?php echo SITE_URL; ?>reservation/details.php?id=<?php echo $reservation['id']; ?>" class="text-black underline">Détails</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Réservations en tant que bailleur -->
        <div id="reservations-bailleur" class="tab-content hidden">
            <?php if (empty($reservationsBailleur)) : ?>
                <div class="text-center py-8">
                    <p class="text-lg">Vous n'avez aucune réservation pour vos logements.</p>
                    <a href="<?php echo SITE_URL; ?>publier.php" class="btn-primary mt-4 inline-block">Publier un logement</a>
                </div>
            <?php else : ?>
                <div class="space-y-6">
                    <?php foreach ($reservationsBailleur as $reservation) : ?>
                        <div class="card p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-20 h-20 bg-gray-200 rounded-md overflow-hidden">
                                    <img 
                                        src="<?php echo !empty($reservation['photo_principale']) ? SITE_URL . 'uploads/logements/' . $reservation['photo_principale'] : SITE_URL . 'assets/img/placeholders/logement.jpg'; ?>" 
                                        alt="<?php echo htmlspecialchars($reservation['titre']); ?>"
                                        class="w-full h-full object-cover"
                                    >
                                </div>
                                
                                <div class="ml-4 flex-1">
                                    <h3 class="font-semibold"><?php echo htmlspecialchars($reservation['titre']); ?></h3>
                                    <p class="text-sm">
                                        <span class="font-semibold">Locataire :</span> 
                                        <?php echo htmlspecialchars($reservation['prenom_locataire'] . ' ' . substr($reservation['nom_locataire'], 0, 1) . '.'); ?>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Dates :</span> 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Statut :</span>
                                        <span class="<?php echo getReservationStatusColor($reservation['statut']); ?>">
                                            <?php echo getReservationStatusText($reservation['statut']); ?>
                                        </span>
                                    </p>
                                    <p class="text-sm"><span class="font-semibold">Prix :</span> <?php echo $reservation['prix_total']; ?>€</p>
                                </div>
                                
                                <div class="flex-shrink-0">
                                    <a href="<?php echo SITE_URL; ?>reservation/details.php?id=<?php echo $reservation['id']; ?>" class="text-black underline">Détails</a>
                                    
                                    <?php if ($reservation['statut'] === 'en_attente') : ?>
                                        <form method="POST" action="<?php echo SITE_URL; ?>controllers/reservation.php" class="mt-2">
                                            <input type="hidden" name="action" value="accepter_reservation">
                                            <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                                            <button type="submit" class="text-green-600 underline">Accepter</button>
                                        </form>
                                        
                                        <form method="POST" action="<?php echo SITE_URL; ?>controllers/reservation.php" class="mt-1">
                                            <input type="hidden" name="action" value="refuser_reservation">
                                            <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                                            <button type="submit" class="text-red-600 underline">Refuser</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
