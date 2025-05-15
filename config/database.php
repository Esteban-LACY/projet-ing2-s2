<?php
/**
 * Configuration de la base de données
 * 
 * Ce fichier contient les paramètres de connexion à la base de données MySQL
 * 
 * @author OmnesBnB
 */

// Paramètres de connexion à la base de données en mode production
define('DB_HOST', 'localhost');
define('DB_USER', 'omnesbnb_user');
define('DB_PASSWORD', 'password_securise'); // À changer pour la production
define('DB_NAME', 'omnesbnb_db');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

/**
 * Connexion à la base de données
 * 
 * @return PDO|null Instance PDO de connexion ou null en cas d'erreur
 */
function getConnexionBD() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";port=" . DB_PORT;
    
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        return new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    } catch (PDOException $e) {
        // En production, on ne devrait pas afficher l'erreur exacte
        // mais plutôt la logger et afficher un message générique
        if (MODE_DEVELOPPEMENT) {
            echo "Erreur de connexion à la base de données: " . $e->getMessage();
        } else {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
        }
        return null;
    }
}

/**
 * Exécute une requête SQL et retourne le résultat
 * 
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête (optionnel)
 * @return array|false Résultat de la requête ou false en cas d'erreur
 */
function executerRequete($sql, $params = []) {
    $connexion = getConnexionBD();
    
    if (!$connexion) {
        return false;
    }
    
    try {
        $requete = $connexion->prepare($sql);
        $requete->execute($params);
        
        // Si c'est un SELECT, récupérer les résultats
        if (stripos(trim($sql), 'SELECT') === 0) {
            return $requete->fetchAll();
        }
        
        // Si c'est un INSERT, retourner l'ID inséré
        if (stripos(trim($sql), 'INSERT') === 0) {
            return $connexion->lastInsertId();
        }
        
        // Pour les autres requêtes, retourner le nombre de lignes affectées
        return $requete->rowCount();
    } catch (PDOException $e) {
        if (MODE_DEVELOPPEMENT) {
            echo "Erreur lors de l'exécution de la requête: " . $e->getMessage();
        } else {
            error_log("Erreur lors de l'exécution de la requête: " . $e->getMessage());
        }
        return false;
    }
}
?>
