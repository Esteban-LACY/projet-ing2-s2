<?php
/**
* Fonctions d'authentification et de gestion des sessions
* 
* Ce fichier contient des fonctions pour gérer l'authentification des utilisateurs
* 
* @author OmnesBnB
*/

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_MODELES . '/utilisateur.php';

/**
* Connecte un utilisateur et crée sa session
* 
* @param array $utilisateur Données de l'utilisateur
* @return void
*/
function connecterUtilisateur($utilisateur) {
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   
   $_SESSION['utilisateur_id'] = $utilisateur['id'];
   $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
   $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
   $_SESSION['utilisateur_email'] = $utilisateur['email'];
   $_SESSION['est_admin'] = (bool)$utilisateur['est_admin'];
   
   // Régénérer l'ID de session pour éviter les attaques de fixation de session
   session_regenerate_id(true);
   
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
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   
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
       rediriger(URL_SITE . $redirection . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
       return false;
   }
   
   return true;
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

/**
* Génère un token CSRF et le stocke en session
* 
* @return string Token généré
*/
function genererToken() {
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   $_SESSION['csrf_token_time'] = time();
   
   return $_SESSION['csrf_token'];
}

/**
* Vérifie la validité d'un token CSRF
* 
* @param string $token Token à vérifier
* @param int $expiration Durée de validité du token en secondes (par défaut 1 heure)
* @return bool True si le token est valide, false sinon
*/
function verifierToken($token, $expiration = 3600) {
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   
   if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
       return false;
   }
   
   // Vérifier si le token a expiré
   if (time() - $_SESSION['csrf_token_time'] > $expiration) {
       // Si le token a expiré, en générer un nouveau
       genererToken();
       return false;
   }
   
   // Vérifier si le token correspond
   return hash_equals($_SESSION['csrf_token'], $token);
}

/**
* Vérifie si l'utilisateur est connecté
* 
* @return bool True si l'utilisateur est connecté, false sinon
*/
function estConnecte() {
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   
   return isset($_SESSION['utilisateur_id']) && !empty($_SESSION['utilisateur_id']);
}

/**
* Vérifie si l'utilisateur est administrateur
* 
* @return bool True si l'utilisateur est administrateur, false sinon
*/
function estAdmin() {
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   
   return estConnecte() && isset($_SESSION['est_admin']) && $_SESSION['est_admin'] === true;
}
?>
