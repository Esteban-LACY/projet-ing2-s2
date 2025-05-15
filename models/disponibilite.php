<?php
/**
 * Modèle pour la gestion des disponibilités des logements
 * 
 * Ce fichier contient les fonctions d'accès aux données des disponibilités
 * 
 * @author OmnesBnB
 */

// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';

/**
 * Crée une nouvelle disponibilité
 * 
 * @param array $donnees Données de la disponibilité
 * @return int|false ID de la disponibilité créée ou false en cas d'erreur
 */
function creerDisponibilite($donnees) {
    $sql = "INSERT INTO disponibilites (id_logement, date_debut, date_fin)
            VALUES (:id_logement, :date_debut, :date_fin)";
    
    $params = [
        ':id_logement' => $donnees['id_logement'],
        ':date_debut' => $donnees['date_debut'],
        ':date_fin' => $donnees['date_fin']
    ];
    
    return executerRequete($sql, $params);
}

/**
 * Récupère une disponibilité par son ID
 * 
 * @param int $id ID de la disponibilité
 * @return array|false Données de la disponibilité ou false si non trouvée
 */
function recupererDisponibiliteParId($id) {
    $sql = "SELECT * FROM disponibilites WHERE id = :id";
    $params = [':id' => $id];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Récupère les disponibilités d'un logement
 * 
 * @param int $idLogement ID du logement
 * @return array Liste des disponibilités
 */
function recupererDisponibilitesLogement($idLogement) {
    $sql = "SELECT * FROM disponibilites WHERE id_logement = :id_logement ORDER BY date_debut";
    $params = [':id_logement' => $idLogement];
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Modifie une disponibilité
 * 
 * @param int $idDisponibilite ID de la disponibilité
 * @param array $donnees Données à modifier
 * @return bool True si la mise à jour a réussi, false sinon
 */
function modifierDisponibilite($idDisponibilite, $donnees) {
    $champsAutorises = ['date_debut', 'date_fin'];
    $setClause = [];
    $params = [':id' => $idDisponibilite];
    
    foreach ($donnees as $champ => $valeur) {
        if (in_array($champ, $champsAutorises)) {
            $setClause[] = "$champ = :$champ";
            $params[":$champ"] = $valeur;
        }
    }
    
    if (empty($setClause)) {
        return false;
    }
    
    $sql = "UPDATE disponibilites SET " . implode(', ', $setClause) . " WHERE id = :id";
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Supprime une disponibilité
 * 
 * @param int $idDisponibilite ID de la disponibilité
 * @return bool True si la suppression a réussi, false sinon
 */
function supprimerDisponibilite($idDisponibilite) {
    $sql = "DELETE FROM disponibilites WHERE id = :id";
    $params = [':id' => $idDisponibilite];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Vérifie si un logement est disponible pour une période donnée
 * 
 * @param int $idLogement ID du logement
 * @param string $dateDebut Date de début (format Y-m-d)
 * @param string $dateFin Date de fin (format Y-m-d)
 * @return bool True si le logement est disponible, false sinon
 */
function verifierDisponibilite($idLogement, $dateDebut, $dateFin) {
    $sql = "SELECT COUNT(*) as compte FROM disponibilites 
            WHERE id_logement = :id_logement 
            AND date_debut <= :date_debut AND date_fin >= :date_fin";
    $params = [
        ':id_logement' => $idLogement,
        ':date_debut' => $dateDebut,
        ':date_fin' => $dateFin
    ];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && isset($resultat[0]['compte']) && $resultat[0]['compte'] > 0;
}
?>
