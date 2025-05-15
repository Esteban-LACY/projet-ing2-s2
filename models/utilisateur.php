<?php
/**
 * Modèle pour la gestion des utilisateurs
 * 
 * Ce fichier contient les fonctions d'accès aux données des utilisateurs
 * 
 * @author OmnesBnB
 */

// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';

/**
 * Crée un nouvel utilisateur
 * 
 * @param array $donnees Données de l'utilisateur
 * @return int|false ID de l'utilisateur créé ou false en cas d'erreur
 */
function creerUtilisateur($donnees) {
    $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, token_verification, est_verifie, est_admin, date_creation)
            VALUES (:nom, :prenom, :email, :mot_de_passe, :telephone, :token_verification, :est_verifie, :est_admin, NOW())";
    
    $params = [
        ':nom' => $donnees['nom'],
        ':prenom' => $donnees['prenom'],
        ':email' => $donnees['email'],
        ':mot_de_passe' => $donnees['mot_de_passe'],
        ':telephone' => isset($donnees['telephone']) ? $donnees['telephone'] : null,
        ':token_verification' => isset($donnees['token_verification']) ? $donnees['token_verification'] : null,
        ':est_verifie' => isset($donnees['est_verifie']) ? $donnees['est_verifie'] : 0,
        ':est_admin' => isset($donnees['est_admin']) ? $donnees['est_admin'] : 0
    ];
    
    return executerRequete($sql, $params);
}

/**
 * Récupère un utilisateur par son ID
 * 
 * @param int $id ID de l'utilisateur
 * @return array|false Données de l'utilisateur ou false si non trouvé
 */
function recupererUtilisateurParId($id) {
    $sql = "SELECT * FROM utilisateurs WHERE id = :id";
    $params = [':id' => $id];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Récupère un utilisateur par son email
 * 
 * @param string $email Email de l'utilisateur
 * @return array|false Données de l'utilisateur ou false si non trouvé
 */
function recupererUtilisateurParEmail($email) {
    $sql = "SELECT * FROM utilisateurs WHERE email = :email";
    $params = [':email' => $email];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Récupère un utilisateur par son token de vérification
 * 
 * @param string $token Token de vérification
 * @return array|false Données de l'utilisateur ou false si non trouvé
 */
function recupererUtilisateurParToken($token) {
    $sql = "SELECT * FROM utilisateurs WHERE token_verification = :token";
    $params = [':token' => $token];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && !empty($resultat) ? $resultat[0] : false;
}

/**
 * Vérifie si un utilisateur existe par son email
 * 
 * @param string $email Email à vérifier
 * @return bool True si l'utilisateur existe, false sinon
 */
function utilisateurExisteParEmail($email) {
    $sql = "SELECT COUNT(*) as compte FROM utilisateurs WHERE email = :email";
    $params = [':email' => $email];
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && isset($resultat[0]['compte']) && $resultat[0]['compte'] > 0;
}

/**
 * Vérifie l'email d'un utilisateur
 * 
 * @param int $idUtilisateur ID de l'utilisateur
 * @return bool True si la mise à jour a réussi, false sinon
 */
function verifierEmail($idUtilisateur) {
    $sql = "UPDATE utilisateurs SET est_verifie = 1, token_verification = NULL WHERE id = :id";
    $params = [':id' => $idUtilisateur];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Modifie le profil d'un utilisateur
 * 
 * @param int $idUtilisateur ID de l'utilisateur
 * @param array $donnees Données à modifier
 * @return bool True si la mise à jour a réussi, false sinon
 */
function modifierProfil($idUtilisateur, $donnees) {
    $champsAutorises = ['nom', 'prenom', 'email', 'telephone', 'est_verifie', 'token_verification', 'est_admin'];
    $setClause = [];
    $params = [':id' => $idUtilisateur];
    
    foreach ($donnees as $champ => $valeur) {
        if (in_array($champ, $champsAutorises)) {
            $setClause[] = "$champ = :$champ";
            $params[":$champ"] = $valeur;
        }
    }
    
    if (empty($setClause)) {
        return false;
    }
    
    $sql = "UPDATE utilisateurs SET " . implode(', ', $setClause) . " WHERE id = :id";
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Modifie le mot de passe d'un utilisateur
 * 
 * @param int $idUtilisateur ID de l'utilisateur
 * @param string $motDePasse Nouveau mot de passe (haché)
 * @return bool True si la mise à jour a réussi, false sinon
 */
function modifierMotDePasse($idUtilisateur, $motDePasse) {
    $sql = "UPDATE utilisateurs SET mot_de_passe = :mot_de_passe WHERE id = :id";
    $params = [
        ':id' => $idUtilisateur,
        ':mot_de_passe' => $motDePasse
    ];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Modifie la photo de profil d'un utilisateur
 * 
 * @param int $idUtilisateur ID de l'utilisateur
 * @param string $photoUrl URL de la photo
 * @return bool True si la mise à jour a réussi, false sinon
 */
function modifierPhotoProfil($idUtilisateur, $photoUrl) {
    $sql = "UPDATE utilisateurs SET photo_profil = :photo_profil WHERE id = :id";
    $params = [
        ':id' => $idUtilisateur,
        ':photo_profil' => $photoUrl
    ];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Met à jour la date de dernière connexion
 * 
 * @param int $idUtilisateur ID de l'utilisateur
 * @return bool True si la mise à jour a réussi, false sinon
 */
function majDerniereConnexion($idUtilisateur) {
    $sql = "UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = :id";
    $params = [':id' => $idUtilisateur];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Supprime un utilisateur
 * 
 * @param int $idUtilisateur ID de l'utilisateur
 * @return bool True si la suppression a réussi, false sinon
 */
function supprimerUtilisateur($idUtilisateur) {
    $sql = "DELETE FROM utilisateurs WHERE id = :id";
    $params = [':id' => $idUtilisateur];
    
    return executerRequete($sql, $params) !== false;
}

/**
 * Vérifie si un utilisateur a des réservations en cours
 * 
 * @param int $idUtilisateur ID de l'utilisateur
 * @return bool True si l'utilisateur a des réservations en cours, false sinon
 */
function utilisateurAReservationsEnCours($idUtilisateur) {
    $sql = "SELECT COUNT(*) as compte FROM reservations 
            WHERE id_locataire = :id_utilisateur AND date_fin >= CURDATE() 
            AND statut IN ('en_attente', 'acceptee')";
    $params = [':id_utilisateur' => $idUtilisateur];
    
    $resultat = executerRequete($sql, $params);
    
    if (is_array($resultat) && isset($resultat[0]['compte'])) {
        if ($resultat[0]['compte'] > 0) {
            return true;
        }
    }
    
    // Vérifier également les logements de l'utilisateur
    $sql = "SELECT COUNT(*) as compte FROM reservations r
            JOIN logements l ON r.id_logement = l.id
            WHERE l.id_proprietaire = :id_utilisateur AND r.date_fin >= CURDATE()
            AND r.statut IN ('en_attente', 'acceptee')";
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && isset($resultat[0]['compte']) && $resultat[0]['compte'] > 0;
}

/**
 * Récupère les utilisateurs avec filtrage
 * 
 * @param array $filtres Filtres de recherche
 * @param int $limite Nombre d'utilisateurs à récupérer
 * @param int $offset Offset pour la pagination
 * @return array Liste des utilisateurs
 */
function recupererUtilisateurs($filtres = [], $limite = 10, $offset = 0) {
    $whereClause = [];
    $params = [];
    
    if (isset($filtres['recherche']) && !empty($filtres['recherche'])) {
        $whereClause[] = "(nom LIKE :recherche OR prenom LIKE :recherche OR email LIKE :recherche)";
        $params[':recherche'] = '%' . $filtres['recherche'] . '%';
    }
    
    if (isset($filtres['est_admin'])) {
        $whereClause[] = "est_admin = :est_admin";
        $params[':est_admin'] = $filtres['est_admin'] ? 1 : 0;
    }
    
    if (isset($filtres['est_verifie'])) {
        $whereClause[] = "est_verifie = :est_verifie";
        $params[':est_verifie'] = $filtres['est_verifie'] ? 1 : 0;
    }
    
    $sql = "SELECT id, nom, prenom, email, telephone, photo_profil, est_verifie, est_admin, date_creation, derniere_connexion 
            FROM utilisateurs";
    
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
 * Compte le nombre d'utilisateurs avec filtrage
 * 
 * @param array $filtres Filtres de recherche
 * @return int Nombre d'utilisateurs
 */
function compterUtilisateurs($filtres = []) {
    $whereClause = [];
    $params = [];
    
    if (isset($filtres['recherche']) && !empty($filtres['recherche'])) {
        $whereClause[] = "(nom LIKE :recherche OR prenom LIKE :recherche OR email LIKE :recherche)";
        $params[':recherche'] = '%' . $filtres['recherche'] . '%';
    }
    
    if (isset($filtres['est_admin'])) {
        $whereClause[] = "est_admin = :est_admin";
        $params[':est_admin'] = $filtres['est_admin'] ? 1 : 0;
    }
    
    if (isset($filtres['est_verifie'])) {
        $whereClause[] = "est_verifie = :est_verifie";
        $params[':est_verifie'] = $filtres['est_verifie'] ? 1 : 0;
    }
    
    $sql = "SELECT COUNT(*) as compte FROM utilisateurs";
    
    if (!empty($whereClause)) {
        $sql .= " WHERE " . implode(' AND ', $whereClause);
    }
    
    $resultat = executerRequete($sql, $params);
    
    return is_array($resultat) && isset($resultat[0]['compte']) ? $resultat[0]['compte'] : 0;
}
?>
