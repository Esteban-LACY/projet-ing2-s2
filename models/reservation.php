<?php
/**
 * Modèle pour la gestion des réservations
 * 
 * Ce fichier contient les fonctions d'accès aux données des réservations
 * 
 * @author OmnesBnB
 */

// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';

/**
 * Crée une nouvelle réservation
 * 
 * @param array $donnees Données de la réservation
 * @return int|false ID de la réservation créée ou false en cas d'erreur
 */
function creerReservation($donnees) {
    $sql = "INSERT INTO reservations (id_logement, id_locataire, date_debut, date_fin, prix_total, statut, date_creation)
            VALUES (:id_logement, :id_locataire, :date_debut, :date_fin, :prix_total, :statut, NOW())";
    
    $params = [
        ':id_logement' => $donnees['id_logement'],
        ':id_locataire' => $donnees['id_locataire'],
        ':date_debut' => $donnees['date_debut'],
        ':date_fin' => $donnees['date_fin'],
        ':prix_total' => $donnees['prix_total'],
        ':statut' => isset($donnees['statut']) ? $donnees['statut'] : 'en_attente'
    ];
    
    return executerRequete($sql, $params);
}

/**
 * Récupère une réservation par son ID
 * 
 * @param int $id ID de la réservation
 * @return array|false Données de la réservation ou false si non trouvée
 */
function recupererReservationParId($id) {
    $sql = "SELECT * FROM reservations WHERE id = :id";
    $params = [':id' => $id];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Récupère les réservations d'un logement
 * 
 * @param int $idLogement ID du logement
 * @param string|null $statut Statut des réservations à récupérer (optionnel)
 * @return array Liste des réservations
 */
function recupererReservationsParLogement($idLogement, $statut = null) {
    $sql = "SELECT * FROM reservations WHERE id_logement = :id_logement";
    $params = [':id_logement' => $idLogement];
    
    if ($statut !== null) {
        $sql .= " AND statut = :statut";
        $params[':statut'] = $statut;
    }
    
    $sql .= " ORDER BY date_debut";
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Récupère les réservations d'un locataire
 * 
 * @param int $idLocataire ID du locataire
 * @param string|null $statut Statut des réservations à récupérer (optionnel)
 * @return array Liste des réservations
 */
function recupererReservationsParLocataire($idLocataire, $statut = null) {
    $sql = "SELECT * FROM reservations WHERE id_locataire = :id_locataire";
    $params = [':id_locataire' => $idLocataire];
    
    if ($statut !== null) {
        $sql .= " AND statut = :statut";
        $params[':statut'] = $statut;
    }
    
    $sql .= " ORDER BY date_debut";
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Récupère les réservations pour plusieurs logements
 * 
 * @param array $idsLogements Liste des IDs de logements
 * @param string|null $statut Statut des réservations à récupérer (optionnel)
 * @return array Liste des réservations
 */
function recupererReservationsParLogements($idsLogements, $statut = null) {
    if (empty($idsLogements)) {
        return [];
    }
    
    $placeholders = implode(',', array_fill(0, count($idsLogements), '?'));
    $sql = "SELECT * FROM reservations WHERE id_logement IN ($placeholders)";
    $params = $idsLogements;
    
    if ($statut !== null) {
        $sql .= " AND statut = ?";
        $params[] = $statut;
    }
    
    $sql .= " ORDER BY date_debut";
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Modifie le statut d'une réservation
 * 
 * @param int $idReservation ID de la réservation
 * @param string $statut Nouveau statut
 * @return bool True si la mise à jour a réussi, false sinon
 */
function modifierStatutReservation($idReservation, $statut) {
    $sql = "UPDATE reservations SET statut = :statut WHERE id = :id";
    $params = [
        ':id' => $idReservation,
        ':statut' => $statut
    ];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Annule une réservation
 * 
 * @param int $idReservation ID de la réservation
 * @return bool True si l'annulation a réussi, false sinon
 */
function annulerReservation($idReservation) {
    return modifierStatutReservation($idReservation, 'annulee');
}

/**
 * Confirme une réservation
 * 
 * @param int $idReservation ID de la réservation
 * @return bool True si la confirmation a réussi, false sinon
 */
function confirmerReservation($idReservation) {
    return modifierStatutReservation($idReservation, 'acceptee');
}

/**
 * Refuse une réservation
 * 
 * @param int $idReservation ID de la réservation
 * @return bool True si le refus a réussi, false sinon
 */
function refuserReservation($idReservation) {
    return modifierStatutReservation($idReservation, 'refusee');
}

/**
 * Termine une réservation
 * 
 * @param int $idReservation ID de la réservation
 * @return bool True si la terminaison a réussi, false sinon
 */
function terminerReservation($idReservation) {
    return modifierStatutReservation($idReservation, 'terminee');
}

/**
 * Supprime une réservation
 * 
 * @param int $idReservation ID de la réservation
 * @return bool True si la suppression a réussi, false sinon
 */
function supprimerReservation($idReservation) {
    $sql = "DELETE FROM reservations WHERE id = :id";
    $params = [':id' => $idReservation];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Récupère les réservations avec filtrage
 * 
 * @param array $filtres Filtres de recherche
 * @param int $limite Nombre de réservations à récupérer
 * @param int $offset Offset pour la pagination
 * @return array Liste des réservations
 */
function recupererReservations($filtres = [], $limite = 0, $offset = 0) {
    $whereClause = ["1 = 1"]; // Clause toujours vraie pour faciliter la construction
    $params = [];
    
    // Filtrer par statut
    if (isset($filtres['statut']) && !empty($filtres['statut'])) {
        $whereClause[] = "statut = :statut";
        $params[':statut'] = $filtres['statut'];
    }
    
    // Filtrer par logement
    if (isset($filtres['id_logement']) && is_numeric($filtres['id_logement'])) {
        $whereClause[] = "id_logement = :id_logement";
        $params[':id_logement'] = $filtres['id_logement'];
    }
    
    // Filtrer par locataire
    if (isset($filtres['id_locataire']) && is_numeric($filtres['id_locataire'])) {
        $whereClause[] = "id_locataire = :id_locataire";
        $params[':id_locataire'] = $filtres['id_locataire'];
    }
    
    // Filtrer par date de début
    if (isset($filtres['date_debut_min']) && !empty($filtres['date_debut_min'])) {
        $whereClause[] = "date_debut >= :date_debut_min";
        $params[':date_debut_min'] = $filtres['date_debut_min'];
    }
    
    // Filtrer par date de fin
    if (isset($filtres['date_fin_max']) && !empty($filtres['date_fin_max'])) {
        $whereClause[] = "date_fin <= :date_fin_max";
        $params[':date_fin_max'] = $filtres['date_fin_max'];
    }
    
    // Construction de la requête SQL
    $sql = "SELECT * FROM reservations";
    
    if (!empty($whereClause)) {
        $sql .= " WHERE " . implode(' AND ', $whereClause);
    }
    
    $sql .= " ORDER BY date_creation DESC";
    
    if ($limite > 0) {
        $sql .= " LIMIT :limite OFFSET :offset";
        $params[':limite'] = $limite;
        $params[':offset'] = $offset;
    }
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Compte le nombre de réservations avec filtrage
 * 
 * @param array $filtres Filtres de recherche
 * @return int Nombre de réservations
 */
function compterReservations($filtres = []) {
    $whereClause = ["1 = 1"]; // Clause toujours vraie pour faciliter la construction
    $params = [];
    
    // Filtrer par statut
    if (isset($filtres['statut']) && !empty($filtres['statut'])) {
        $whereClause[] = "statut = :statut";
        $params[':statut'] = $filtres['statut'];
    }
    
    // Filtrer par logement
    if (isset($filtres['id_logement']) && is_numeric($filtres['id_logement'])) {
        $whereClause[] = "id_logement = :id_logement";
        $params[':id_logement'] = $filtres['id_logement'];
    }
    
    // Filtrer par locataire
    if (isset($filtres['id_locataire']) && is_numeric($filtres['id_locataire'])) {
        $whereClause[] = "id_locataire = :id_locataire";
        $params[':id_locataire'] = $filtres['id_locataire'];
    }
    
    // Filtrer par date de début
    if (isset($filtres['date_debut_min']) && !empty($filtres['date_debut_min'])) {
        $whereClause[] = "date_debut >= :date_debut_min";
        $params[':date_debut_min'] = $filtres['date_debut_min'];
    }
    
    // Filtrer par date de fin
    if (isset($filtres['date_fin_max']) && !empty($filtres['date_fin_max'])) {
        $whereClause[] = "date_fin <= :date_fin_max";
        $params[':date_fin_max'] = $filtres['date_fin_max'];
    }
    
    // Construction de la requête SQL
    $sql = "SELECT COUNT(*) as compte FROM reservations";
    
    if (!empty($whereClause)) {
        $sql .= " WHERE " . implode(' AND ', $whereClause);
    }
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && isset($resultat[0]['compte']) ? $resultat[0]['compte'] : 0;
}

/**
 * Récupère les bailleurs les plus actifs (avec le plus de logements)
 * 
 * @param int $limite Nombre de bailleurs à récupérer
 * @return array Liste des bailleurs actifs
 */
function recupererBailleursActifs($limite = 5) {
    $sql = "SELECT u.id, u.nom, u.prenom, COUNT(l.id) as nb_logements 
            FROM utilisateurs u 
            JOIN logements l ON u.id = l.id_proprietaire 
            GROUP BY u.id 
            ORDER BY nb_logements DESC 
            LIMIT :limite";
    
    $params = [':limite' => $limite];
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Récupère les locataires les plus actifs (avec le plus de réservations)
 * 
 * @param int $limite Nombre de locataires à récupérer
 * @return array Liste des locataires actifs
 */
function recupererLocatairesActifs($limite = 5) {
    $sql = "SELECT u.id, u.nom, u.prenom, COUNT(r.id) as nb_reservations 
            FROM utilisateurs u 
            JOIN reservations r ON u.id = r.id_locataire 
            GROUP BY u.id 
            ORDER BY nb_reservations DESC 
            LIMIT :limite";
    
    $params = [':limite' => $limite];
    
    return executerRequete($sql, $params) ?: [];
}
?>
