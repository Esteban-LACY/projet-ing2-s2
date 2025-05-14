<?php
/**
 * Configuration de la base de données
 */

// Informations de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'omnesbnb');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Classe de connexion à la base de données
 */
class Database {
    private static $instance = null;
    private $conn;
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            if (APP_DEBUG) {
                die('Erreur de connexion à la base de données : ' . $e->getMessage());
            } else {
                die('Erreur de connexion à la base de données. Veuillez réessayer plus tard.');
            }
        }
    }
    
    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}
    
    /**
     * Récupère l'instance unique de la classe
     * @return Database Instance unique
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Récupère la connexion à la base de données
     * @return PDO Instance de PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Exécute une requête SQL et retourne les résultats
     * @param string $sql Requête SQL
     * @param array $params Paramètres à binder
     * @return array Résultats de la requête
     */
    public static function query($sql, $params = []) {
        $db = self::getInstance();
        $stmt = $db->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Exécute une requête SQL et retourne le premier résultat
     * @param string $sql Requête SQL
     * @param array $params Paramètres à binder
     * @return array|false Premier résultat ou false
     */
    public static function queryOne($sql, $params = []) {
        $db = self::getInstance();
        $stmt = $db->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
    }
    
    /**
     * Exécute une requête SQL et retourne le nombre de lignes affectées
     * @param string $sql Requête SQL
     * @param array $params Paramètres à binder
     * @return int Nombre de lignes affectées
     */
    public static function execute($sql, $params = []) {
        $db = self::getInstance();
        $stmt = $db->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Exécute une requête SQL d'insertion et retourne l'ID inséré
     * @param string $sql Requête SQL
     * @param array $params Paramètres à binder
     * @return int|false ID inséré ou false
     */
    public static function insert($sql, $params = []) {
        $db = self::getInstance();
        $stmt = $db->conn->prepare($sql);
        $stmt->execute($params);
        
        return $db->conn->lastInsertId();
    }
    
    /**
     * Démarre une transaction
     */
    public static function beginTransaction() {
        $db = self::getInstance();
        $db->conn->beginTransaction();
    }
    
    /**
     * Valide une transaction
     */
    public static function commit() {
        $db = self::getInstance();
        $db->conn->commit();
    }
    
    /**
     * Annule une transaction
     */
    public static function rollback() {
        $db = self::getInstance();
        $db->conn->rollBack();
    }
    
    /**
     * Récupération des erreurs de base de données
     * @return array Informations sur l'erreur
     */
    public static function getError() {
        $db = self::getInstance();
        return $db->conn->errorInfo();
    }
}

/**
 * Création de la table d'utilisateurs (à exécuter une seule fois)
 */
function creerTableUtilisateurs() {
    $sql = "
        CREATE TABLE IF NOT EXISTS utilisateurs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL,
            telephone VARCHAR(20),
            photo_profil VARCHAR(255),
            est_verifie BOOLEAN DEFAULT 0,
            token_verification VARCHAR(255),
            est_admin BOOLEAN DEFAULT 0,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            derniere_connexion DATETIME
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    Database::execute($sql);
}

/**
 * Création de la table de logements (à exécuter une seule fois)
 */
function creerTableLogements() {
    $sql = "
        CREATE TABLE IF NOT EXISTS logements (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_proprietaire INT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description TEXT,
            adresse VARCHAR(255) NOT NULL,
            ville VARCHAR(100) NOT NULL,
            code_postal VARCHAR(10) NOT NULL,
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            prix DECIMAL(10, 2) NOT NULL,
            type_logement ENUM('entier', 'collocation', 'libere') NOT NULL,
            nb_places INT NOT NULL DEFAULT 1,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_proprietaire) REFERENCES utilisateurs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    Database::execute($sql);
}

/**
 * Création de la table de photos de logement (à exécuter une seule fois)
 */
function creerTablePhotosLogement() {
    $sql = "
        CREATE TABLE IF NOT EXISTS photos_logement (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_logement INT NOT NULL,
            url VARCHAR(255) NOT NULL,
            est_principale BOOLEAN DEFAULT 0,
            FOREIGN KEY (id_logement) REFERENCES logements(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    Database::execute($sql);
}

/**
 * Création de la table de disponibilités (à exécuter une seule fois)
 */
function creerTableDisponibilites() {
    $sql = "
        CREATE TABLE IF NOT EXISTS disponibilites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_logement INT NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            FOREIGN KEY (id_logement) REFERENCES logements(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    Database::execute($sql);
}

/**
 * Création de la table de réservations (à exécuter une seule fois)
 */
function creerTableReservations() {
    $sql = "
        CREATE TABLE IF NOT EXISTS reservations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_logement INT NOT NULL,
            id_locataire INT NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            prix_total DECIMAL(10, 2) NOT NULL,
            statut ENUM('en_attente', 'acceptee', 'refusee', 'annulee', 'terminee') DEFAULT 'en_attente',
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_logement) REFERENCES logements(id) ON DELETE CASCADE,
            FOREIGN KEY (id_locataire) REFERENCES utilisateurs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    Database::execute($sql);
}

/**
 * Création de la table de paiements (à exécuter une seule fois)
 */
function creerTablePaiements() {
    $sql = "
        CREATE TABLE IF NOT EXISTS paiements (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_reservation INT NOT NULL,
            montant DECIMAL(10, 2) NOT NULL,
            id_transaction VARCHAR(255),
            statut ENUM('en_attente', 'complete', 'rembourse', 'echoue') DEFAULT 'en_attente',
            date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_reservation) REFERENCES reservations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    Database::execute($sql);
}

/**
 * Création de la table de messages (à exécuter une seule fois)
 */
function creerTableMessages() {
    $sql = "
        CREATE TABLE IF NOT EXISTS messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_expediteur INT NOT NULL,
            id_destinataire INT NOT NULL,
            id_reservation INT,
            contenu TEXT NOT NULL,
            est_lu BOOLEAN DEFAULT 0,
            date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_expediteur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
            FOREIGN KEY (id_destinataire) REFERENCES utilisateurs(id) ON DELETE CASCADE,
            FOREIGN KEY (id_reservation) REFERENCES reservations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    Database::execute($sql);
}

/**
 * Création de la table d'évaluations (à exécuter une seule fois)
 */
function creerTableEvaluations() {
    $sql = "
        CREATE TABLE IF NOT EXISTS evaluations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            id_reservation INT NOT NULL,
            id_evaluateur INT NOT NULL,
            id_evalue INT NOT NULL,
            note INT NOT NULL,
            commentaire TEXT,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_reservation) REFERENCES reservations(id) ON DELETE CASCADE,
            FOREIGN KEY (id_evaluateur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
            FOREIGN KEY (id_evalue) REFERENCES utilisateurs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    Database::execute($sql);
}

// Création des tables lors de l'initialisation (à commenter après la première exécution)
// creerTableUtilisateurs();
// creerTableLogements();
// creerTablePhotosLogement();
// creerTableDisponibilites();
// creerTableReservations();
// creerTablePaiements();
// creerTableMessages();
// creerTableEvaluations();
?>
