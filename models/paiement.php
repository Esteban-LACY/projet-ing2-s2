<?php
/**
* Modèle pour la gestion des paiements
* 
* Ce fichier contient les fonctions d'accès aux données des paiements
* 
* @author OmnesBnB
*/

// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/stripe_service.php';

/**
* Crée un nouveau paiement
* 
* @param array $donnees Données du paiement
* @return int|false ID du paiement créé ou false en cas d'erreur
*/
function creerPaiement($donnees) {
   $sql = "INSERT INTO paiements (id_reservation, montant, id_transaction, statut, date_paiement)
           VALUES (:id_reservation, :montant, :id_transaction, :statut, NOW())";
   
   $params = [
       ':id_reservation' => $donnees['id_reservation'],
       ':montant' => $donnees['montant'],
       ':id_transaction' => isset($donnees['id_transaction']) ? $donnees['id_transaction'] : null,
       ':statut' => isset($donnees['statut']) ? $donnees['statut'] : 'en_attente'
   ];
   
   return executerRequete($sql, $params);
}

/**
* Vérifie si l'utilisateur est autorisé à payer une réservation
* 
* @param int $idReservation ID de la réservation
* @param int $idUtilisateur ID de l'utilisateur (optionnel, utilise l'utilisateur en session par défaut)
* @return bool True si l'utilisateur est autorisé, false sinon
*/
function estUtilisateurAutoriseAPayer($idReservation, $idUtilisateur = null) {
   if ($idUtilisateur === null) {
       if (!estConnecte()) {
           return false;
       }
       $idUtilisateur = $_SESSION['utilisateur_id'];
   }
   
   // Récupérer la réservation
   $reservation = recupererReservationParId($idReservation);
   
   if (!$reservation) {
       return false;
   }
   
   // Vérifier que l'utilisateur est le locataire
   if ($reservation['id_locataire'] != $idUtilisateur) {
       return false;
   }
   
   // Vérifier que la réservation est en attente
   if ($reservation['statut'] != 'en_attente') {
       return false;
   }
   
   return true;
}

/**
* Prépare les données d'une réservation pour le paiement
* 
* @param int $idReservation ID de la réservation
* @return array|false Données de la réservation ou false en cas d'erreur
*/
function preparerDonneesReservationPourPaiement($idReservation) {
   // Récupérer la réservation
   $reservation = recupererReservationParId($idReservation);
   
   if (!$reservation) {
       return false;
   }
   
   // Récupérer le logement
   $logement = recupererLogementParId($reservation['id_logement']);
   
   if (!$logement) {
       return false;
   }
   
   // Préparer les données pour la session de paiement
   return [
       'id' => $idReservation,
       'id_logement' => $reservation['id_logement'],
       'id_locataire' => $reservation['id_locataire'],
       'date_debut' => $reservation['date_debut'],
       'date_fin' => $reservation['date_fin'],
       'prix_total' => $reservation['prix_total'],
       'titre_logement' => $logement['titre']
   ];
}

/**
* Enregistre une session de paiement pour une réservation
* 
* @param int $idReservation ID de la réservation
* @param string $sessionId ID de la session Stripe
* @param float $montant Montant du paiement
* @return bool True si l'enregistrement a réussi, false sinon
*/
function enregistrerSessionPaiement($idReservation, $sessionId, $montant) {
   // Vérifier si un paiement existe déjà pour cette réservation
   $paiementExistant = recupererPaiementParReservation($idReservation);
   
   if ($paiementExistant) {
       // Mettre à jour le paiement existant
       $sql = "UPDATE paiements SET id_transaction = :id_transaction, montant = :montant, statut = 'en_attente', date_paiement = NOW() WHERE id = :id";
       $params = [
           ':id' => $paiementExistant['id'],
           ':id_transaction' => $sessionId,
           ':montant' => $montant
       ];
       
       return executerRequete($sql, $params) !== false;
   } else {
       // Créer un nouveau paiement
       $donnees = [
           'id_reservation' => $idReservation,
           'montant' => $montant,
           'id_transaction' => $sessionId,
           'statut' => 'en_attente'
       ];
       
       return creerPaiement($donnees) !== false;
   }
}

/**
* Traite la confirmation d'un paiement
* 
* @param string $sessionId ID de la session Stripe
* @return array Résultat du traitement
*/
function traiterConfirmationPaiement($sessionId) {
   // Récupérer la session Stripe
   $session = recupererSessionStripe($sessionId);
   
   if (!$session) {
       return ['success' => false, 'message' => 'Session de paiement invalide'];
   }
   
   // Récupérer la réservation associée à la session
   $idReservation = $session->client_reference_id;
   $reservation = recupererReservationParId($idReservation);
   
   if (!$reservation) {
       return ['success' => false, 'message' => 'Réservation non trouvée'];
   }
   
   // Vérifier le statut du paiement
   if ($session->payment_status === 'paid') {
       // Mettre à jour le statut du paiement
       $paiement = recupererPaiementParTransaction($sessionId);
       
       if ($paiement) {
           // Mettre à jour le statut du paiement
           modifierStatutPaiement($paiement['id'], 'complete');
           
           // Si la réservation est toujours en attente, la mettre à jour
           if ($reservation['statut'] === 'en_attente') {
               // Pour les logements de type "entier" ou "libere", la réservation est automatiquement acceptée
               $logement = recupererLogementParId($reservation['id_logement']);
               
               if ($logement['type_logement'] === 'entier' || $logement['type_logement'] === 'libere') {
                   // Accepter automatiquement la réservation
                   confirmerReservation($idReservation);
                   
                   // Envoyer les notifications
                   envoyerNotificationsReservationConfirmee($reservation, $logement);
               } else {
                   // Pour les collocations, le propriétaire doit valider manuellement
                   envoyerNotificationsReservationEnAttente($reservation, $logement);
               }
           }
       }
       
       return ['success' => true, 'id_reservation' => $idReservation];
   } else {
       return ['success' => false, 'message' => 'Le paiement n\'a pas été validé'];
   }
}

/**
* Envoie les notifications quand une réservation est confirmée automatiquement
* 
* @param array $reservation Données de la réservation
* @param array $logement Données du logement
* @return void
*/
function envoyerNotificationsReservationConfirmee($reservation, $logement) {
   // Récupérer les utilisateurs
   $locataire = recupererUtilisateurParId($reservation['id_locataire']);
   $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
   
   // Notification au locataire
   envoyerEmailConfirmationReservationLocataire($reservation, $logement, $locataire, $proprietaire);
   
   // Notification au propriétaire
   envoyerEmailConfirmationReservationProprietaire($reservation, $logement, $locataire, $proprietaire);
}

/**
* Envoie les notifications quand une réservation est en attente de confirmation
* 
* @param array $reservation Données de la réservation
* @param array $logement Données du logement
* @return void
*/
function envoyerNotificationsReservationEnAttente($reservation, $logement) {
   // Récupérer les utilisateurs
   $locataire = recupererUtilisateurParId($reservation['id_locataire']);
   $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
   
   // Notification au propriétaire
   envoyerEmailNouvelleReservation($reservation, $logement, $locataire, $proprietaire);
   
   // Notification au locataire
   envoyerEmailPaiementReussi($reservation, $logement, $locataire);
}

/**
* Récupère un paiement par son ID
* 
* @param int $id ID du paiement
* @return array|false Données du paiement ou false si non trouvé
*/
function recupererPaiementParId($id) {
   $sql = "SELECT * FROM paiements WHERE id = :id";
   $params = [':id' => $id];
   
   $resultat = executerRequete($sql, $params);
   
   return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
* Récupère un paiement par ID de réservation
* 
* @param int $idReservation ID de la réservation
* @return array|false Données du paiement ou false si non trouvé
*/
function recupererPaiementParReservation($idReservation) {
   $sql = "SELECT * FROM paiements WHERE id_reservation = :id_reservation ORDER BY date_paiement DESC LIMIT 1";
   $params = [':id_reservation' => $idReservation];
   
   $resultat = executerRequete($sql, $params);
   
   return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
* Récupère un paiement par ID de transaction
* 
* @param string $idTransaction ID de transaction
* @return array|false Données du paiement ou false si non trouvé
*/
function recupererPaiementParTransaction($idTransaction) {
   $sql = "SELECT * FROM paiements WHERE id_transaction = :id_transaction";
   $params = [':id_transaction' => $idTransaction];
   
   $resultat = executerRequete($sql, $params);
   
   return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
* Modifie le statut d'un paiement
* 
* @param int $idPaiement ID du paiement
* @param string $statut Nouveau statut
* @return bool True si la mise à jour a réussi, false sinon
*/
function modifierStatutPaiement($idPaiement, $statut) {
   $sql = "UPDATE paiements SET statut = :statut WHERE id = :id";
   $params = [
       ':id' => $idPaiement,
       ':statut' => $statut
   ];
   
   return executerRequete($sql, $params) !== false;
}

/**
* Rembourse un paiement
* 
* @param int $idPaiement ID du paiement
* @return bool True si le remboursement a réussi, false sinon
*/
function rembourserPaiement($idPaiement) {
   $paiement = recupererPaiementParId($idPaiement);
   
   if (!$paiement || $paiement['statut'] !== 'complete') {
       return false;
   }
   
   // Essayer de rembourser via Stripe si un ID de transaction est présent
   if (!empty($paiement['id_transaction'])) {
       $resultatStripe = rembourserPaiementStripe($paiement['id_transaction']);
       
       if (!$resultatStripe['success']) {
           journaliser("Échec du remboursement Stripe pour le paiement {$idPaiement}: {$resultatStripe['message']}", 'ERROR');
           return false;
       }
   }
   
   // Mettre à jour le statut dans la base de données
   return modifierStatutPaiement($idPaiement, 'rembourse');
}

/**
* Calcule le montant total des paiements complétés
* 
* @return float Montant total des paiements
*/
function calculerMontantTotalPaiements() {
   $sql = "SELECT SUM(montant) as total FROM paiements WHERE statut = 'complete'";
   
   $resultat = executerRequete($sql);
   
   return is_array($resultat) && isset($resultat[0]['total']) ? floatval($resultat[0]['total']) : 0;
}

/**
* Calcule le montant total des frais de service
* 
* @return float Montant total des frais de service
*/
function calculerMontantTotalFraisService() {
   $fraisPourcentage = defined('FRAIS_SERVICE_POURCENTAGE') ? FRAIS_SERVICE_POURCENTAGE / 100 : 0.1; // Par défaut 10%
   
   $sql = "SELECT SUM(r.prix_total * " . $fraisPourcentage . ") as total 
           FROM paiements p 
           JOIN reservations r ON p.id_reservation = r.id 
           WHERE p.statut = 'complete'";
   
   $resultat = executerRequete($sql);
   
   return is_array($resultat) && isset($resultat[0]['total']) ? floatval($resultat[0]['total']) : 0;
}

/**
* Récupère tous les paiements avec filtrage
* 
* @param array $filtres Filtres de recherche (statut, id_reservation, date_debut, date_fin, etc.)
* @param int $limite Nombre d'éléments à récupérer (pagination)
* @param int $offset Position de départ (pagination) 
* @return array Liste des paiements
*/
function recupererPaiements($filtres = [], $limite = 0, $offset = 0) {
   $whereClause = [];
   $params = [];
   $index = 0;
   
   // Construction des clauses WHERE en fonction des filtres
   if (isset($filtres['statut']) && !empty($filtres['statut'])) {
       $whereClause[] = "p.statut = :statut";
       $params[':statut'] = $filtres['statut'];
   }
   
   if (isset($filtres['id_reservation']) && !empty($filtres['id_reservation'])) {
       $whereClause[] = "p.id_reservation = :id_reservation";
       $params[':id_reservation'] = $filtres['id_reservation'];
   }
   
   if (isset($filtres['date_debut']) && !empty($filtres['date_debut'])) {
       $whereClause[] = "p.date_paiement >= :date_debut";
       $params[':date_debut'] = $filtres['date_debut'];
   }
   
   if (isset($filtres['date_fin']) && !empty($filtres['date_fin'])) {
       $whereClause[] = "p.date_paiement <= :date_fin";
       $params[':date_fin'] = $filtres['date_fin'];
   }
   
   // Construction de la requête SQL
   $sql = "SELECT p.*, r.id_locataire, r.id_logement, r.date_debut as reservation_debut, r.date_fin as reservation_fin 
           FROM paiements p
           LEFT JOIN reservations r ON p.id_reservation = r.id";
   
   if (!empty($whereClause)) {
       $sql .= " WHERE " . implode(' AND ', $whereClause);
   }
   
   $sql .= " ORDER BY p.date_paiement DESC";
   
   if ($limite > 0) {
       $sql .= " LIMIT :limite OFFSET :offset";
       $params[':limite'] = $limite;
       $params[':offset'] = $offset;
   }
   
   return executerRequete($sql, $params) ?: [];
}

/**
* Compte le nombre de paiements avec filtrage
* 
* @param array $filtres Filtres de recherche
* @return int Nombre de paiements
*/
function compterPaiements($filtres = []) {
   $whereClause = [];
   $params = [];
   
   // Construction des clauses WHERE en fonction des filtres
   if (isset($filtres['statut']) && !empty($filtres['statut'])) {
       $whereClause[] = "p.statut = :statut";
       $params[':statut'] = $filtres['statut'];
   }
   
   if (isset($filtres['id_reservation']) && !empty($filtres['id_reservation'])) {
       $whereClause[] = "p.id_reservation = :id_reservation";
       $params[':id_reservation'] = $filtres['id_reservation'];
   }
   
   if (isset($filtres['date_debut']) && !empty($filtres['date_debut'])) {
       $whereClause[] = "p.date_paiement >= :date_debut";
       $params[':date_debut'] = $filtres['date_debut'];
   }
   
   if (isset($filtres['date_fin']) && !empty($filtres['date_fin'])) {
       $whereClause[] = "p.date_paiement <= :date_fin";
       $params[':date_fin'] = $filtres['date_fin'];
   }
   
   // Construction de la requête SQL
   $sql = "SELECT COUNT(*) as compte FROM paiements p";
   
   if (!empty($whereClause)) {
       $sql .= " WHERE " . implode(' AND ', $whereClause);
   }
   
   $resultat = executerRequete($sql, $params);
   
   return is_array($resultat) && isset($resultat[0]['compte']) ? intval($resultat[0]['compte']) : 0;
}
?>
