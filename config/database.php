<?php
/**
* Configuration de la base de données
* 
* Ce fichier contient les paramètres de connexion à la base de données MySQL
* et les fonctions principales d'accès aux données
* 
* @author OmnesBnB
*/

// Paramètres de connexion à la base de données
// En production, ces paramètres devraient être récupérés depuis des variables d'environnement
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'omnesbnb_user');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'password_securise');
define('DB_NAME', getenv('DB_NAME') ?: 'omnesbnb_db');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// Variable statique pour conserver la connexion (pattern singleton)
$GLOBALS['connexion_pdo'] = null;

/**
* Connexion à la base de données avec pattern singleton
* 
* @return PDO|null Instance PDO de connexion ou null en cas d'erreur
*/
function getConnexionBD() {
   // Si une connexion existe déjà, la réutiliser
   if ($GLOBALS['connexion_pdo'] instanceof PDO) {
       return $GLOBALS['connexion_pdo'];
   }
   
   $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";port=" . DB_PORT;
   
   try {
       $options = [
           PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
           PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
           PDO::ATTR_EMULATE_PREPARES => false,
           // Activer la persistance des connexions
           PDO::ATTR_PERSISTENT => true
       ];
       
       $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
       $GLOBALS['connexion_pdo'] = $pdo;
       return $pdo;
   } catch (PDOException $e) {
       // En production, on ne devrait pas afficher l'erreur exacte
       // mais plutôt la logger
       if (MODE_DEVELOPPEMENT) {
           error_log("Erreur de connexion à la base de données: " . $e->getMessage());
           echo "Erreur de connexion à la base de données: " . $e->getMessage();
       } else {
           error_log("Erreur de connexion à la base de données: " . $e->getMessage());
       }
       return null;
   }
}

/**
* Exécute une requête SQL préparée et retourne le résultat
* 
* @param string $sql Requête SQL
* @param array $params Paramètres de la requête (optionnel)
* @param bool $returnLastId Indique si la fonction doit retourner le dernier ID inséré
* @return mixed Résultat de la requête, ID inséré, nombre de lignes affectées ou false en cas d'erreur
*/
function executerRequete($sql, $params = [], $returnLastId = false) {
   $connexion = getConnexionBD();
   
   if (!$connexion) {
       return false;
   }
   
   try {
       $requete = $connexion->prepare($sql);
       
       // Liaison sécurisée des paramètres
       foreach ($params as $param => $value) {
           // Déterminer le type du paramètre
           $type = PDO::PARAM_STR;
           if (is_int($value)) {
               $type = PDO::PARAM_INT;
           } elseif (is_bool($value)) {
               $type = PDO::PARAM_BOOL;
           } elseif (is_null($value)) {
               $type = PDO::PARAM_NULL;
           }
           
           // Si le paramètre est numérique, le convertir en entier
           if (is_numeric($param)) {
               $requete->bindValue($param + 1, $value, $type);
           } else {
               $requete->bindValue($param, $value, $type);
           }
       }
       
       $requete->execute();
       
       // Si c'est un SELECT, récupérer les résultats
       if (stripos(trim($sql), 'SELECT') === 0) {
           return $requete->fetchAll();
       }
       
       // Si c'est un INSERT et qu'on veut l'ID, retourner l'ID inséré
       if (stripos(trim($sql), 'INSERT') === 0 && $returnLastId) {
           return $connexion->lastInsertId();
       }
       
       // Pour les autres requêtes, retourner le nombre de lignes affectées
       return $requete->rowCount();
   } catch (PDOException $e) {
       if (MODE_DEVELOPPEMENT) {
           error_log("Erreur lors de l'exécution de la requête: " . $e->getMessage() . " - SQL: " . $sql);
           echo "Erreur lors de l'exécution de la requête: " . $e->getMessage();
       } else {
           error_log("Erreur lors de l'exécution de la requête: " . $e->getMessage() . " - SQL: " . $sql);
       }
       return false;
   }
}

/**
* Exécute une requête de sélection paginée
*
* @param string $sql Requête SQL de base (sans LIMIT)
* @param array $params Paramètres de la requête
* @param int $page Numéro de page (commence à 1)
* @param int $limite Nombre d'éléments par page
* @return array Tableau contenant les résultats et les informations de pagination
*/
function executerRequetePaginee($sql, $params = [], $page = 1, $limite = 10) {
   // Validation des paramètres de pagination
   $page = max(1, intval($page));
   $limite = max(1, min(100, intval($limite))); // Limite maximum de 100 pour éviter les surcharges
   
   // Calculer l'offset
   $offset = ($page - 1) * $limite;
   
   // Requête pour compter le nombre total d'éléments
   $sqlCount = preg_replace('/^SELECT\s.*?\sFROM/i', 'SELECT COUNT(*) as total FROM', $sql);
   $sqlCount = preg_replace('/ORDER BY.*$/i', '', $sqlCount); // Supprimer la clause ORDER BY pour le comptage
   
   $resultCount = executerRequete($sqlCount, $params);
   $total = $resultCount && isset($resultCount[0]['total']) ? intval($resultCount[0]['total']) : 0;
   
   // Ajouter la pagination à la requête
   $sqlPagination = $sql . " LIMIT " . $limite . " OFFSET " . $offset;
   
   // Exécuter la requête paginée
   $resultats = executerRequete($sqlPagination, $params);
   
   // Calculer le nombre total de pages
   $totalPages = ceil($total / $limite);
   
   return [
       'resultats' => $resultats ?: [],
       'pagination' => [
           'total' => $total,
           'page' => $page,
           'limite' => $limite,
           'total_pages' => $totalPages,
           'offset' => $offset
       ]
   ];
}

/**
* Démarre une transaction
*
* @return bool True si la transaction est démarrée, false sinon
*/
function demarrerTransaction() {
   $connexion = getConnexionBD();
   
   if (!$connexion) {
       return false;
   }
   
   try {
       return $connexion->beginTransaction();
   } catch (PDOException $e) {
       error_log("Erreur lors du démarrage de la transaction: " . $e->getMessage());
       return false;
   }
}

/**
* Valide une transaction
*
* @return bool True si la transaction est validée, false sinon
*/
function validerTransaction() {
   $connexion = getConnexionBD();
   
   if (!$connexion) {
       return false;
   }
   
   try {
       return $connexion->commit();
   } catch (PDOException $e) {
       error_log("Erreur lors de la validation de la transaction: " . $e->getMessage());
       return false;
   }
}

/**
* Annule une transaction
*
* @return bool True si la transaction est annulée, false sinon
*/
function annulerTransaction() {
   $connexion = getConnexionBD();
   
   if (!$connexion) {
       return false;
   }
   
   try {
       return $connexion->rollBack();
   } catch (PDOException $e) {
       error_log("Erreur lors de l'annulation de la transaction: " . $e->getMessage());
       return false;
   }
}

/**
* Échappe une chaîne pour une utilisation sécurisée dans une requête SQL
* 
* @param string $valeur Valeur à échapper
* @return string Valeur échappée
*/
function echapperSQL($valeur) {
   $connexion = getConnexionBD();
   
   if (!$connexion) {
       return $valeur;
   }
   
   return $connexion->quote($valeur);
}

/**
* Ferme la connexion à la base de données
*
* @return void
*/
function fermerConnexionBD() {
   $GLOBALS['connexion_pdo'] = null;
}

// S'assurer que la connexion est fermée lorsque le script se termine
register_shutdown_function('fermerConnexionBD');
?>
