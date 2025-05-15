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
    $sql = "SELECT * FROM paiements WHERE id_reservation = :id_reservation ORDER BY date_paiement DESC";
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
    $sql = "SELECT SUM(r.prix_total * " . (FRAIS_SERVICE_POURCENTAGE / 100) . ") as total 
            FROM paiements p 
            JOIN reservations r ON p.id_reservation = r.id 
            WHERE p.statut = 'complete'";
    
    $resultat = executerRequete($sql);
    
    return is_array($resultat) && isset($resultat[0]['total']) ? floatval($resultat[0]['total']) : 0;
}
?>
