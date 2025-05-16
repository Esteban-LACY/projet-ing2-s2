<?php
/**
* Gestionnaire de base de données
* 
* Ce fichier centralise toutes les opérations liées à la base de données
* 
* @author OmnesBnB
*/

// Utilisation du design pattern Singleton pour la connexion à la base de données
class DatabaseManager {
   private static $instance = null;
   private $connexion = null;
   private $transactionActive = false;
   
   // Paramètres de connexion à la base de données
   private $host;
   private $dbname;
   private $user;
   private $password;
   private $port;
   private $charset;
   
   /**
    * Constructeur privé pour empêcher l'instanciation directe
    */
   private function __construct() {
       $this->host = DB_HOST;
       $this->dbname = DB_NAME;
       $this->user = DB_USER;
       $this->password = DB_PASSWORD;
       $this->port = DB_PORT;
       $this->charset = DB_CHARSET;
       
       $this->connecter();
   }
   
   /**
    * Clone non autorisé (Singleton)
    */
   private function __clone() {}
   
   /**
    * Récupère l'instance unique de la classe
    * 
    * @return DatabaseManager Instance unique
    */
   public static function getInstance() {
       if (self::$instance === null) {
           self::$instance = new self();
       }
       
       return self::$instance;
   }
   
   /**
    * Établit la connexion à la base de données
    * 
    * @return bool True si la connexion est établie, false sinon
    */
   private function connecter() {
       $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset};port={$this->port}";
       
       try {
           $options = [
               PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES => false,
               PDO::ATTR_PERSISTENT => true
           ];
           
           $this->connexion = new PDO($dsn, $this->user, $this->password, $options);
           return true;
       } catch (PDOException $e) {
           // Journalisation de l'erreur
           $this->journaliserErreur('Erreur de connexion à la base de données: ' . $e->getMessage());
           return false;
       }
   }
   
   /**
    * Vérifie si la connexion est active et tente de se reconnecter si nécessaire
    * 
    * @return bool True si la connexion est active, false sinon
    */
   private function verifierConnexion() {
       if ($this->connexion === null) {
           return $this->connecter();
       }
       
       try {
           $this->connexion->query('SELECT 1');
           return true;
       } catch (PDOException $e) {
           // Tentative de reconnexion
           return $this->connecter();
       }
   }
   
   /**
    * Exécute une requête SQL et retourne le résultat
    * 
    * @param string $sql Requête SQL avec placeholders
    * @param array $params Paramètres de la requête
    * @param bool $retourId Indique si la fonction doit retourner l'ID inséré
    * @return mixed Résultat de la requête, ID inséré, nombre de lignes affectées ou false
    */
   public function executerRequete($sql, $params = [], $retourId = false) {
       if (!$this->verifierConnexion()) {
           return false;
       }
       
       try {
           $requete = $this->connexion->prepare($sql);
           
           // Lier les paramètres de manière sécurisée
           foreach ($params as $param => $valeur) {
               $type = PDO::PARAM_STR;
               
               if (is_int($valeur)) {
                   $type = PDO::PARAM_INT;
               } elseif (is_bool($valeur)) {
                   $type = PDO::PARAM_BOOL;
               } elseif (is_null($valeur)) {
                   $type = PDO::PARAM_NULL;
               }
               
               // Si le paramètre est numérique (pour les requêtes préparées avec ?)
               if (is_int($param)) {
                   $requete->bindValue($param + 1, $valeur, $type);
               } else {
                   // Pour les requêtes préparées avec des noms (:param)
                   $requete->bindValue($param, $valeur, $type);
               }
           }
           
           $requete->execute();
           
           // Déterminer le type de retour en fonction de la requête
           $typeRequete = strtoupper(substr(trim($sql), 0, 6));
           
           switch ($typeRequete) {
               case 'SELECT':
                   return $requete->fetchAll();
                   
               case 'INSERT':
                   return $retourId ? $this->connexion->lastInsertId() : $requete->rowCount();
                   
               default:
                   return $requete->rowCount();
           }
       } catch (PDOException $e) {
           $this->journaliserErreur('Erreur lors de l\'exécution de la requête: ' . $e->getMessage() . ' - SQL: ' . $sql);
           
           // Rollback en cas d'erreur pendant une transaction
           if ($this->transactionActive) {
               $this->annulerTransaction();
           }
           
           return false;
       }
   }
   
   /**
    * Exécute une requête SQL et retourne une seule ligne de résultat
    * 
    * @param string $sql Requête SQL avec placeholders
    * @param array $params Paramètres de la requête
    * @return array|false Une ligne de résultat ou false
    */
   public function executerRequeteUneLigne($sql, $params = []) {
       $resultats = $this->executerRequete($sql, $params);
       
       if ($resultats === false || empty($resultats)) {
           return false;
       }
       
       return $resultats[0];
   }
   
   /**
    * Exécute une requête SQL et retourne une seule valeur
    * 
    * @param string $sql Requête SQL avec placeholders
    * @param array $params Paramètres de la requête
    * @return mixed Une valeur ou false
    */
   public function executerRequeteScalaire($sql, $params = []) {
       $resultat = $this->executerRequeteUneLigne($sql, $params);
       
       if ($resultat === false) {
           return false;
       }
       
       // Retourne la première valeur de la première ligne
       return reset($resultat);
   }
   
   /**
    * Démarre une transaction
    * 
    * @return bool True si la transaction a été démarrée, false sinon
    */
   public function demarrerTransaction() {
       if (!$this->verifierConnexion()) {
           return false;
       }
       
       if ($this->transactionActive) {
           $this->journaliserErreur('Transaction déjà active');
           return false;
       }
       
       try {
           $this->connexion->beginTransaction();
           $this->transactionActive = true;
           return true;
       } catch (PDOException $e) {
           $this->journaliserErreur('Erreur lors du démarrage de la transaction: ' . $e->getMessage());
           return false;
       }
   }
   
   /**
    * Valide une transaction
    * 
    * @return bool True si la transaction a été validée, false sinon
    */
   public function validerTransaction() {
       if (!$this->verifierConnexion() || !$this->transactionActive) {
           return false;
       }
       
       try {
           $this->connexion->commit();
           $this->transactionActive = false;
           return true;
       } catch (PDOException $e) {
           $this->journaliserErreur('Erreur lors de la validation de la transaction: ' . $e->getMessage());
           return false;
       }
   }
   
   /**
    * Annule une transaction
    * 
    * @return bool True si la transaction a été annulée, false sinon
    */
   public function annulerTransaction() {
       if (!$this->verifierConnexion() || !$this->transactionActive) {
           return false;
       }
       
       try {
           $this->connexion->rollBack();
           $this->transactionActive = false;
           return true;
       } catch (PDOException $e) {
           $this->journaliserErreur('Erreur lors de l\'annulation de la transaction: ' . $e->getMessage());
           $this->transactionActive = false;
           return false;
       }
   }
   
   /**
    * Journalise une erreur
    * 
    * @param string $message Message d'erreur
    * @return void
    */
   private function journaliserErreur($message) {
       if (MODE_DEVELOPPEMENT) {
           echo $message;
       }
       error_log($message);
       
       // Si une fonction de journalisation globale existe, l'utiliser
       if (function_exists('journaliser')) {
           journaliser($message, 'ERROR');
       }
   }
   
   /**
    * Échappe une valeur pour l'utilisation dans une requête SQL
    * 
    * @param mixed $valeur Valeur à échapper
    * @return string Valeur échappée
    */
   public function echapper($valeur) {
       if (!$this->verifierConnexion()) {
           return $valeur;
       }
       
       return $this->connexion->quote($valeur);
   }
   
   /**
    * Ferme la connexion à la base de données
    * 
    * @return void
    */
   public function fermerConnexion() {
       $this->connexion = null;
   }
   
   /**
    * Retourne l'instance PDO pour les cas où un accès direct est nécessaire
    * 
    * @return PDO|null Instance PDO ou null
    */
   public function getPDO() {
       if (!$this->verifierConnexion()) {
           return null;
       }
       
       return $this->connexion;
   }
}

/**
* Fonctions globales pour faciliter l'utilisation du gestionnaire de base de données
*/

/**
* Raccourci pour obtenir l'instance du gestionnaire de base de données
* 
* @return DatabaseManager Instance du gestionnaire
*/
function getDB() {
   return DatabaseManager::getInstance();
}

/**
* Raccourci pour exécuter une requête
* 
* @param string $sql Requête SQL
* @param array $params Paramètres
* @param bool $retourId Indique si la fonction doit retourner l'ID inséré
* @return mixed Résultat de la requête
*/
function executerRequete($sql, $params = [], $retourId = false) {
   return getDB()->executerRequete($sql, $params, $retourId);
}

/**
* Raccourci pour exécuter une requête retournant une ligne
* 
* @param string $sql Requête SQL
* @param array $params Paramètres
* @return array|false Une ligne de résultat ou false
*/
function executerRequeteUneLigne($sql, $params = []) {
   return getDB()->executerRequeteUneLigne($sql, $params);
}

/**
* Raccourci pour exécuter une requête retournant une valeur
* 
* @param string $sql Requête SQL
* @param array $params Paramètres
* @return mixed Une valeur ou false
*/
function executerRequeteScalaire($sql, $params = []) {
   return getDB()->executerRequeteScalaire($sql, $params);
}

/**
* Raccourci pour démarrer une transaction
* 
* @return bool True si la transaction a été démarrée, false sinon
*/
function demarrerTransaction() {
   return getDB()->demarrerTransaction();
}

/**
* Raccourci pour valider une transaction
* 
* @return bool True si la transaction a été validée, false sinon
*/
function validerTransaction() {
   return getDB()->validerTransaction();
}

/**
* Raccourci pour annuler une transaction
* 
* @return bool True si la transaction a été annulée, false sinon
*/
function annulerTransaction() {
   return getDB()->annulerTransaction();
}
?>
