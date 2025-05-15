<?php
/**
 * Modèle pour la gestion des logements
 * 
 * Ce fichier contient les fonctions d'accès aux données des logements
 * 
 * @author OmnesBnB
 */

// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';

/**
 * Crée un nouveau logement
 * 
 * @param array $donnees Données du logement
 * @return int|false ID du logement créé ou false en cas d'erreur
 */
function creerLogement($donnees) {
    $sql = "INSERT INTO logements (id_proprietaire, titre, description, adresse, ville, code_postal, latitude, longitude, prix, type_logement, nb_places, date_creation)
            VALUES (:id_proprietaire, :titre, :description, :adresse, :ville, :code_postal, :latitude, :longitude, :prix, :type_logement, :nb_places, NOW())";
    
    $params = [
        ':id_proprietaire' => $donnees['id_proprietaire'],
        ':titre' => $donnees['titre'],
        ':description' => $donnees['description'],
        ':adresse' => $donnees['adresse'],
        ':ville' => $donnees['ville'],
        ':code_postal' => $donnees['code_postal'],
        ':latitude' => $donnees['latitude'],
        ':longitude' => $donnees['longitude'],
        ':prix' => $donnees['prix'],
        ':type_logement' => $donnees['type_logement'],
        ':nb_places' => $donnees['nb_places']
    ];
    
    return executerRequete($sql, $params);
}

/**
 * Récupère un logement par son ID
 * 
 * @param int $id ID du logement
 * @return array|false Données du logement ou false si non trouvé
 */
function recupererLogementParId($id) {
    $sql = "SELECT * FROM logements WHERE id = :id";
    $params = [':id' => $id];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Modifie un logement
 * 
 * @param int $idLogement ID du logement
 * @param array $donnees Données à modifier
 * @return bool True si la mise à jour a réussi, false sinon
 */
function modifierLogement($idLogement, $donnees) {
    $champsAutorises = ['titre', 'description', 'adresse', 'ville', 'code_postal', 'latitude', 'longitude', 'prix', 'type_logement', 'nb_places'];
    $setClause = [];
    $params = [':id' => $idLogement];
    
    foreach ($donnees as $champ => $valeur) {
        if (in_array($champ, $champsAutorises)) {
            $setClause[] = "$champ = :$champ";
            $params[":$champ"] = $valeur;
        }
    }
    
    if (empty($setClause)) {
        return false;
    }
    
    $sql = "UPDATE logements SET " . implode(', ', $setClause) . " WHERE id = :id";
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Supprime un logement
 * 
 * @param int $idLogement ID du logement
 * @return bool True si la suppression a réussi, false sinon
 */
function supprimerLogement($idLogement) {
    $sql = "DELETE FROM logements WHERE id = :id";
    $params = [':id' => $idLogement];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Récupère les logements d'un propriétaire
 * 
 * @param int $idProprietaire ID du propriétaire
 * @return array Liste des logements
 */
function recupererLogementsPropriete($idProprietaire) {
    $sql = "SELECT * FROM logements WHERE id_proprietaire = :id_proprietaire ORDER BY date_creation DESC";
    $params = [':id_proprietaire' => $idProprietaire];
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Vérifie si un logement a des réservations en cours
 * 
 * @param int $idLogement ID du logement
 * @return bool True si le logement a des réservations en cours, false sinon
 */
function logementAReservationsEnCours($idLogement) {
    $sql = "SELECT COUNT(*) as compte FROM reservations 
            WHERE id_logement = :id_logement AND date_fin >= CURDATE() 
            AND statut IN ('en_attente', 'acceptee')";
    $params = [':id_logement' => $idLogement];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && isset($resultat[0]['compte']) && $resultat[0]['compte'] > 0;
}

/**
 * Vérifie si un logement est disponible pour une période donnée
 * 
 * @param int $idLogement ID du logement
 * @param string $dateDebut Date de début (format Y-m-d)
 * @param string $dateFin Date de fin (format Y-m-d)
 * @return bool True si le logement est disponible, false sinon
 */
function logementEstDisponible($idLogement, $dateDebut, $dateFin) {
    // Vérifier si le logement existe
    $logement = recupererLogementParId($idLogement);
    
    if (!$logement) {
        return false;
    }
    
    // Vérifier s'il y a des disponibilités pour cette période
    $sql = "SELECT COUNT(*) as compte FROM disponibilites 
            WHERE id_logement = :id_logement 
            AND date_debut <= :date_debut AND date_fin >= :date_fin";
    $params = [
        ':id_logement' => $idLogement,
        ':date_debut' => $dateDebut,
        ':date_fin' => $dateFin
    ];
    
    $resultat = executerRequete($sql, $params);
    
    if (!is_array($resultat) || !isset($resultat[0]['compte']) || $resultat[0]['compte'] == 0) {
        return false;
    }
    
    // Vérifier s'il n'y a pas déjà des réservations pour cette période
    $sql = "SELECT COUNT(*) as compte FROM reservations 
            WHERE id_logement = :id_logement 
            AND ((date_debut <= :date_debut AND date_fin > :date_debut) 
            OR (date_debut < :date_fin AND date_fin >= :date_fin) 
            OR (date_debut >= :date_debut AND date_fin <= :date_fin))
            AND statut IN ('en_attente', 'acceptee')";
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && isset($resultat[0]['compte']) && $resultat[0]['compte'] == 0;
}

/**
 * Récupère les logements avec filtrage
 * 
 * @param array $filtres Filtres de recherche
 * @param string $tri Critère de tri (prix_asc, prix_desc, date_creation_desc)
 * @param int $limite Nombre de logements à récupérer
 * @param int $offset Offset pour la pagination
 * @return array Liste des logements
 */
function recupererLogements($filtres = [], $tri = 'prix_asc', $limite = 0, $offset = 0) {
    $whereClause = ["1 = 1"]; // Clause toujours vraie pour faciliter la construction
    $joinClause = [];
    $params = [];
    
    // Filtrer par ville
    if (isset($filtres['ville']) && !empty($filtres['ville'])) {
        $whereClause[] = "ville LIKE :ville";
        $params[':ville'] = '%' . $filtres['ville'] . '%';
    }
    
    // Filtrer par code postal
    if (isset($filtres['code_postal']) && !empty($filtres['code_postal'])) {
        $whereClause[] = "code_postal = :code_postal";
        $params[':code_postal'] = $filtres['code_postal'];
    }
    
    // Filtrer par type de logement
    if (isset($filtres['type_logement']) && !empty($filtres['type_logement'])) {
        $whereClause[] = "type_logement = :type_logement";
        $params[':type_logement'] = $filtres['type_logement'];
    }
    
    // Filtrer par prix minimum
    if (isset($filtres['prix_min']) && is_numeric($filtres['prix_min'])) {
        $whereClause[] = "prix >= :prix_min";
        $params[':prix_min'] = $filtres['prix_min'];
    }
    
    // Filtrer par prix maximum
    if (isset($filtres['prix_max']) && is_numeric($filtres['prix_max'])) {
        $whereClause[] = "prix <= :prix_max";
        $params[':prix_max'] = $filtres['prix_max'];
    }
    
    // Filtrer par nombre de places
    if (isset($filtres['nb_places']) && is_numeric($filtres['nb_places'])) {
        $whereClause[] = "nb_places >= :nb_places";
        $params[':nb_places'] = $filtres['nb_places'];
    }
    
    // Filtrer par propriétaire
    if (isset($filtres['id_proprietaire']) && is_numeric($filtres['id_proprietaire'])) {
        $whereClause[] = "id_proprietaire = :id_proprietaire";
        $params[':id_proprietaire'] = $filtres['id_proprietaire'];
    }
    
    // Filtrer par recherche (titre ou description)
    if (isset($filtres['recherche']) && !empty($filtres['recherche'])) {
        $whereClause[] = "(titre LIKE :recherche OR description LIKE :recherche)";
        $params[':recherche'] = '%' . $filtres['recherche'] . '%';
    }
    
    // Filtrer par disponibilité
    if (isset($filtres['date_debut']) && !empty($filtres['date_debut']) && isset($filtres['date_fin']) && !empty($filtres['date_fin'])) {
        $joinClause[] = "JOIN disponibilites d ON l.id = d.id_logement";
        $whereClause[] = "d.date_debut <= :date_debut AND d.date_fin >= :date_fin";
        $params[':date_debut'] = $filtres['date_debut'];
        $params[':date_fin'] = $filtres['date_fin'];
        
        // Exclure les logements déjà réservés pour cette période
        $joinClause[] = "LEFT JOIN reservations r ON l.id = r.id_logement 
                        AND ((r.date_debut <= :date_debut AND r.date_fin > :date_debut) 
                        OR (r.date_debut < :date_fin AND r.date_fin >= :date_fin) 
                        OR (r.date_debut >= :date_debut AND r.date_fin <= :date_fin))
                        AND r.statut IN ('en_attente', 'acceptee')";
        $whereClause[] = "r.id IS NULL";
    }
    
    // Construction de la requête SQL
    $sql = "SELECT DISTINCT l.* FROM logements l ";
    
    if (!empty($joinClause)) {
        $sql .= implode(' ', $joinClause) . ' ';
    }
    
    if (!empty($whereClause)) {
        $sql .= "WHERE " . implode(' AND ', $whereClause) . ' ';
    }
    
    // Tri
    switch ($tri) {
        case 'prix_desc':
            $sql .= "ORDER BY l.prix DESC ";
            break;
        case 'date_creation_desc':
            $sql .= "ORDER BY l.date_creation DESC ";
            break;
        case 'prix_asc':
        default:
            $sql .= "ORDER BY l.prix ASC ";
            break;
    }
    
    // Pagination
    if ($limite > 0) {
        $sql .= "LIMIT :limite OFFSET :offset";
        $params[':limite'] = $limite;
        $params[':offset'] = $offset;
    }
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Compte le nombre de logements avec filtrage
 * 
 * @param array $filtres Filtres de recherche
 * @return int Nombre de logements
 */
function compterLogements($filtres = []) {
    $whereClause = ["1 = 1"]; // Clause toujours vraie pour faciliter la construction
    $joinClause = [];
    $params = [];
    
    // Filtrer par ville
    if (isset($filtres['ville']) && !empty($filtres['ville'])) {
        $whereClause[] = "ville LIKE :ville";
        $params[':ville'] = '%' . $filtres['ville'] . '%';
    }
    
    // Filtrer par code postal
    if (isset($filtres['code_postal']) && !empty($filtres['code_postal'])) {
        $whereClause[] = "code_postal = :code_postal";
        $params[':code_postal'] = $filtres['code_postal'];
    }
    
    // Filtrer par type de logement
    if (isset($filtres['type_logement']) && !empty($filtres['type_logement'])) {
        $whereClause[] = "type_logement = :type_logement";
        $params[':type_logement'] = $filtres['type_logement'];
    }
    
    // Filtrer par prix minimum
    if (isset($filtres['prix_min']) && is_numeric($filtres['prix_min'])) {
        $whereClause[] = "prix >= :prix_min";
        $params[':prix_min'] = $filtres['prix_min'];
    }
    
    // Filtrer par prix maximum
    if (isset($filtres['prix_max']) && is_numeric($filtres['prix_max'])) {
        $whereClause[] = "prix <= :prix_max";
        $params[':prix_max'] = $filtres['prix_max'];
    }
    
    // Filtrer par nombre de places
    if (isset($filtres['nb_places']) && is_numeric($filtres['nb_places'])) {
        $whereClause[] = "nb_places >= :nb_places";
        $params[':nb_places'] = $filtres['nb_places'];
    }
    
    // Filtrer par propriétaire
    if (isset($filtres['id_proprietaire']) && is_numeric($filtres['id_proprietaire'])) {
        $whereClause[] = "id_proprietaire = :id_proprietaire";
        $params[':id_proprietaire'] = $filtres['id_proprietaire'];
    }
    
    // Filtrer par recherche (titre ou description)
    if (isset($filtres['recherche']) && !empty($filtres['recherche'])) {
        $whereClause[] = "(titre LIKE :recherche OR description LIKE :recherche)";
        $params[':recherche'] = '%' . $filtres['recherche'] . '%';
    }
    
    // Filtrer par disponibilité
    if (isset($filtres['date_debut']) && !empty($filtres['date_debut']) && isset($filtres['date_fin']) && !empty($filtres['date_fin'])) {
        $joinClause[] = "JOIN disponibilites d ON l.id = d.id_logement";
        $whereClause[] = "d.date_debut <= :date_debut AND d.date_fin >= :date_fin";
        $params[':date_debut'] = $filtres['date_debut'];
        $params[':date_fin'] = $filtres['date_fin'];
        
        // Exclure les logements déjà réservés pour cette période
        $joinClause[] = "LEFT JOIN reservations r ON l.id = r.id_logement 
                        AND ((r.date_debut <= :date_debut AND r.date_fin > :date_debut) 
                        OR (r.date_debut < :date_fin AND r.date_fin >= :date_fin) 
                        OR (r.date_debut >= :date_debut AND r.date_fin <= :date_fin))
                        AND r.statut IN ('en_attente', 'acceptee')";
        $whereClause[] = "r.id IS NULL";
    }
    
    // Construction de la requête SQL
    $sql = "SELECT COUNT(DISTINCT l.id) as compte FROM logements l ";
    
    if (!empty($joinClause)) {
        $sql .= implode(' ', $joinClause) . ' ';
    }
    
    if (!empty($whereClause)) {
        $sql .= "WHERE " . implode(' AND ', $whereClause);
    }
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && isset($resultat[0]['compte']) ? $resultat[0]['compte'] : 0;
}

/**
 * Ajoute une photo à un logement
 * 
 * @param int $idLogement ID du logement
 * @param string $url URL de la photo
 * @param bool $estPrincipale Si la photo est principale
 * @return int|false ID de la photo ajoutée ou false en cas d'erreur
 */
function ajouterPhotoLogement($idLogement, $url, $estPrincipale = false) {
    // Si la photo est principale, mettre à jour toutes les autres photos
    if ($estPrincipale) {
        $sql = "UPDATE photos_logement SET est_principale = 0 WHERE id_logement = :id_logement";
        $params = [':id_logement' => $idLogement];
        
        executerRequete($sql, $params);
    }
    
    // Ajouter la nouvelle photo
    $sql = "INSERT INTO photos_logement (id_logement, url, est_principale) VALUES (:id_logement, :url, :est_principale)";
    $params = [
        ':id_logement' => $idLogement,
        ':url' => $url,
        ':est_principale' => $estPrincipale ? 1 : 0
    ];
    
    return executerRequete($sql, $params);
}

/**
 * Récupère les photos d'un logement
 * 
 * @param int $idLogement ID du logement
 * @return array Liste des photos
 */
function recupererPhotosLogement($idLogement) {
    $sql = "SELECT * FROM photos_logement WHERE id_logement = :id_logement ORDER BY est_principale DESC, id ASC";
    $params = [':id_logement' => $idLogement];
    
    return executerRequete($sql, $params) ?: [];
}

/**
 * Récupère une photo par son ID
 * 
 * @param int $idPhoto ID de la photo
 * @return array|false Données de la photo ou false si non trouvée
 */
function recupererPhotoParId($idPhoto) {
    $sql = "SELECT * FROM photos_logement WHERE id = :id";
    $params = [':id' => $idPhoto];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Supprime une photo
 * 
 * @param int $idPhoto ID de la photo
 * @return bool True si la suppression a réussi, false sinon
 */
function supprimerPhoto($idPhoto) {
    $sql = "DELETE FROM photos_logement WHERE id = :id";
    $params = [':id' => $idPhoto];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Définit une photo comme principale
 * 
 * @param int $idPhoto ID de la photo
 * @return bool True si la mise à jour a réussi, false sinon
 */
function definirPhotoPrincipale($idPhoto) {
    $photo = recupererPhotoParId($idPhoto);
    
    if (!$photo) {
        return false;
    }
    
    // Mettre à jour toutes les autres photos
    $sql = "UPDATE photos_logement SET est_principale = 0 WHERE id_logement = :id_logement";
    $params = [':id_logement' => $photo['id_logement']];
    
    executerRequete($sql, $params);
    
    // Définir la photo comme principale
    $sql = "UPDATE photos_logement SET est_principale = 1 WHERE id = :id";
    $params = [':id' => $idPhoto];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Récupère les suggestions de villes pour l'autocomplétion
 * 
 * @param string $terme Terme de recherche
 * @return array Liste des villes correspondantes
 */
function recupererSuggestionsVilles($terme) {
    $sql = "SELECT DISTINCT ville FROM logements WHERE ville LIKE :terme ORDER BY ville LIMIT 10";
    $params = [':terme' => $terme . '%'];
    
    $resultat = executerRequete($sql, $params);
    
    if (!is_array($resultat)) {
        return [];
    }
    
    return array_column($resultat, 'ville');
}

/**
 * Recherche des logements
 * 
 * @param array $filtres Filtres de recherche
 * @param string $tri Critère de tri
 * @param int $limite Nombre de logements à récupérer
 * @param int $offset Offset pour la pagination
 * @return array Liste des logements correspondants
 */
function rechercherLogements($filtres = [], $tri = 'prix_asc', $limite = 0, $offset = 0) {
    return recupererLogements($filtres, $tri, $limite, $offset);
}

/**
 * Récupère les villes les plus populaires (avec le plus de logements)
 * 
 * @param int $limite Nombre de villes à récupérer
 * @return array Liste des villes populaires
 */
function recupererVillesPopulaires($limite = 5) {
    $sql = "SELECT ville, COUNT(*) as nb_logements FROM logements GROUP BY ville ORDER BY nb_logements DESC LIMIT :limite";
    $params = [':limite' => $limite];
    
    return executerRequete($sql, $params) ?: [];
}
?>
