<div class="container-mobile mx-auto py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Détails de la réservation</h1>
    
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
    
    <?php if (empty($reservation)) : ?>
        <div class="text-center py-8">
            <p class="text-lg">Cette réservation n'existe pas ou a été supprimée.</p>
            <a href="<?php echo SITE_URL; ?>profil.php" class="btn-primary mt-4 inline-block">Retour au profil</a>
        </div>
    <?php else : ?>
        <!-- Statut de la réservation -->
        <div class="mb-6 p-4 bg-gray-100 rounded-lg">
            <p class="text-lg">
                Statut : 
                <span class="font-bold <?php echo getReservationStatusColor($reservation['statut']); ?>">
                    <?php echo getReservationStatusText($reservation['statut']); ?>
                </span>
            </p>
            
            <?php if ($reservation['statut'] === 'en_attente' && $estProprietaire) : ?>
                <div class="mt-4">
                    <form method="POST" action="<?php echo SITE_URL; ?>controllers/reservation.php" class="inline-block mr-2">
                        <input type="hidden" name="action" value="accepter_reservation">
                        <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                        <button type="submit" class="btn-primary">Accepter</button>
                    </form>
                    
                    <form method="POST" action="<?php echo SITE_URL; ?>controllers/reservation.php" class="inline-block">
                        <input type="hidden" name="action" value="refuser_reservation">
                        <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                        <button type="submit" class="btn-secondary">Refuser</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Informations sur le logement -->
        <div class="card p-4 mb-6">
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
                    <h3 class="font-semibold"><?php echo htmlspecialchars($logement['titre']); ?></h3>
                    <p class="text-gray-500"><?php echo htmlspecialchars($logement['adresse'] . ', ' . $logement['code_postal'] . ' ' . $logement['ville']); ?></p>
                    <p class="mt-2">
                        <a href="<?php echo SITE_URL; ?>logement/details.php?id=<?php echo $logement['id']; ?>" class="text-black underline">Voir le logement</a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Détails de la réservation -->
        <div class="card p-4 mb-6">
            <h2 class="text-xl font-bold mb-4">Détails de la réservation</h2>
            
            <div class="mb-4">
                <p><span class="font-semibold">Dates :</span> 
                    <?php echo date('d/m/Y', strtotime($reservation['date_debut'])); ?> - 
                    <?php echo date('d/m/Y', strtotime($reservation['date_fin'])); ?>
                </p>
                <p><span class="font-semibold">Nombre de nuits :</span> <?php echo $nbNuits; ?></p>
                <p><span class="font-semibold">Prix par nuit :</span> <?php echo $logement['prix']; ?>€</p>
                <p class="text-xl font-bold mt-2"><span class="font-semibold">Prix total :</span> <?php echo $reservation['prix_total']; ?>€</p>
            </div>
            
            <div class="mb-4">
                <p><span class="font-semibold">Date de réservation :</span> <?php echo date('d/m/Y H:i', strtotime($reservation['date_creation'])); ?></p>
            </div>
            
            <?php if ($reservation['statut'] === 'acceptee' && $estLocataire && !$reservation['est_passee']) : ?>
                <form method="POST" action="<?php echo SITE_URL; ?>controllers/reservation.php" class="mt-4">
                    <input type="hidden" name="action" value="annuler_reservation">
                    <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                    <button type="submit" class="btn-secondary">Annuler la réservation</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Informations de contact -->
        <?php if ($reservation['statut'] === 'acceptee' || $estProprietaire) : ?>
            <div class="card p-4 mb-6">
                <?php if ($estProprietaire) : ?>
                    <h2 class="text-xl font-bold mb-4">Informations du locataire</h2>
                    
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gray-200 rounded-full overflow-hidden">
                            <img 
                                src="<?php echo !empty($locataire['photo_profil']) ? SITE_URL . 'uploads/profils/' . $locataire['photo_profil'] : SITE_URL . 'assets/img/placeholders/profil.jpg'; ?>" 
                                alt="Photo de <?php echo htmlspecialchars($locataire['prenom']); ?>"
                                class="w-full h-full object-cover"
                            >
                        </div>
                        
                        <div class="ml-4">
                            <p class="font-semibold"><?php echo htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']); ?></p>
                            <p class="text-gray-500"><?php echo htmlspecialchars($locataire['email']); ?></p>
                            <p class="text-gray-500"><?php echo htmlspecialchars($locataire['telephone']); ?></p>
                        </div>
                    </div>
                <?php else : ?>
                    <h2 class="text-xl font-bold mb-4">Informations du propriétaire</h2>
                    
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gray-200 rounded-full overflow-hidden">
                            <img 
                                src="<?php echo !empty($proprietaire['photo_profil']) ? SITE_URL . 'uploads/profils/' . $proprietaire['photo_profil'] : SITE_URL . 'assets/img/placeholders/profil.jpg'; ?>" 
                                alt="Photo de <?php echo htmlspecialchars($proprietaire['prenom']); ?>"
                                class="w-full h-full object-cover"
                            >
                        </div>
                        
                        <div class="ml-4">
                            <p class="font-semibold"><?php echo htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom']); ?></p>
                            <p class="text-gray-500"><?php echo htmlspecialchars($proprietaire['email']); ?></p>
                            <p class="text-gray-500"><?php echo htmlspecialchars($proprietaire['telephone']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Messagerie -->
        <div class="card p-4 mb-6">
            <h2 class="text-xl font-bold mb-4">Messages</h2>
            
            <div id="messages-container" class="mb-4">
                <?php if (empty($messages)) : ?>
                    <p class="text-gray-500 text-center">Aucun message pour le moment.</p>
                <?php else : ?>
                    <div class="space-y-4">
                        <?php foreach ($messages as $message) : ?>
                            <div class="p-3 rounded-lg <?php echo $message['id_expediteur'] === $_SESSION['utilisateur']['id'] ? 'bg-black text-white ml-12' : 'bg-gray-200 mr-12'; ?>">
                                <p class="text-sm">
                                    <?php if ($message['id_expediteur'] !== $_SESSION['utilisateur']['id']) : ?>
                                        <span class="font-semibold">
                                            <?php echo $message['id_expediteur'] === $logement['id_proprietaire'] ? $proprietaire['prenom'] : $locataire['prenom']; ?>:
                                        </span>
                                    <?php endif; ?>
                                    <?php echo nl2br(htmlspecialchars($message['contenu'])); ?>
                                </p>
                                <p class="text-xs <?php echo $message['id_expediteur'] === $_SESSION['utilisateur']['id'] ? 'text-gray-300' : 'text-gray-500'; ?> text-right">
                                    <?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <form id="message-form" method="POST" action="<?php echo SITE_URL; ?>controllers/message.php">
                <input type="hidden" name="action" value="envoyer_message">
                <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                <input type="hidden" name="id_destinataire" value="<?php echo $estProprietaire ? $locataire['id'] : $proprietaire['id']; ?>">
                
                <div class="mb-2">
                    <textarea name="contenu" rows="2" class="input-field" placeholder="Écrire un message..." required></textarea>
                </div>
                
                <button type="submit" class="btn-primary">Envoyer</button>
            </form>
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
