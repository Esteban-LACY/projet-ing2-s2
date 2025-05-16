<?php
/**
 * Fonctions d'authentification et de gestion des sessions
 * 
 * Ce fichier contient des fonctions pour gérer l'authentification des utilisateurs
 * 
 * @author OmnesBnB
 */

// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_MODELES . '/utilisateur.php';

/**
 * Démarre la session si elle n'est pas déjà démarrée
 * 
 * @return void
 */
function demarrerSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Connecte un utilisateur et crée sa session
 * 
 * @param array $utilisateur Données de l'utilisateur
 * @return void
 */
function connecterUtilisateur($utilisateur) {
    demarrerSession();
    
    $_SESSION['utilisateur_id'] = $utilisateur['id'];
    $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
    $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
    $_SESSION['utilisateur_email'] = $utilisateur['email'];
    $_SESSION['est_admin'] = (bool)$utilisateur['est_admin'];
    
    // Mettre à jour la date de dernière connexion
    majDerniereConnexion($utilisateur['id']);
    
    // Générer un nouveau token CSRF
    genererToken();
}

/**
 * Déconnecte l'utilisateur actuel
 * 
 * @return void
 */
function deconnecterUtilisateur() {
    demarrerSession();
    
    // Détruire toutes les variables de session
    $_SESSION = [];
    
    // Détruire le cookie de session si nécessaire
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Détruire la session
    session_destroy();
}

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return bool True si l'utilisateur est connecté, false sinon
 */
function estConnecte() {
    demarrerSession();
    
    return isset($_SESSION['utilisateur_id']) && !empty($_SESSION['utilisateur_id']);
}

/**
 * Vérifie si l'utilisateur est administrateur
 * 
 * @return bool True si l'utilisateur est administrateur, false sinon
 */
function estAdmin() {
    demarrerSession();
    
    return estConnecte() && isset($_SESSION['est_admin']) && $_SESSION['est_admin'] === true;
}

/**
 * Authentifie un utilisateur avec son email et son mot de passe
 * 
 * @param string $email Email de l'utilisateur
 * @param string $motDePasse Mot de passe de l'utilisateur
 * @return array|false Données de l'utilisateur si authentification réussie, false sinon
 */
function authentifierUtilisateur($email, $motDePasse) {
    // Récupérer l'utilisateur par son email
    $utilisateur = recupererUtilisateurParEmail($email);
    
    if (!$utilisateur) {
        return false;
    }
    
    // Vérifier le mot de passe
    if (!password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
        return false;
    }
    
    // Vérifier si l'email est vérifié
    if (!$utilisateur['est_verifie']) {
        return false;
    }
    
    return $utilisateur;
}

/**
 * Récupère l'utilisateur actuellement connecté
 * 
 * @return array|false Données de l'utilisateur si connecté, false sinon
 */
function recupererUtilisateurConnecte() {
    if (!estConnecte()) {
        return false;
    }
    
    return recupererUtilisateurParId($_SESSION['utilisateur_id']);
}

/**
 * Vérifie si l'utilisateur connecté a accès à une page restreinte
 * 
 * @param string $niveau Niveau d'accès requis ('utilisateur' ou 'admin')
 * @param string $redirection URL de redirection en cas d'accès non autorisé
 * @return bool True si l'utilisateur a accès, redirection sinon
 */
function verifierAcces($niveau = 'utilisateur', $redirection = '/connexion.php') {
    if ($niveau === 'admin' && !estAdmin()) {
        // Rediriger les non-administrateurs
        rediriger(URL_SITE . $redirection);
        return false;
    } elseif ($niveau === 'utilisateur' && !estConnecte()) {
        // Rediriger les utilisateurs non connectés
        rediriger(URL_SITE . $redirection);
        return false;
    }
    
    return true;
}

/**
 * Régénère l'ID de session pour éviter les attaques de fixation de session
 * 
 * @return void
 */
function regenererSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Génère un hash pour un mot de passe
 * 
 * @param string $motDePasse Mot de passe à hacher
 * @return string Hash du mot de passe
 */
function genererHashMotDePasse($motDePasse) {
    return password_hash($motDePasse, PASSWORD_DEFAULT);
}

/**
 * Vérifie si un mot de passe correspond à un hash
 * 
 * @param string $motDePasse Mot de passe à vérifier
 * @param string $hash Hash du mot de passe
 * @return bool True si le mot de passe correspond, false sinon
 */
function verifierMotDePasse($motDePasse, $hash) {
    return password_verify($motDePasse, $hash);
}
?>
