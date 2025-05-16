<?php
/**
* Gestionnaire de sessions centralisé
* 
* Ce fichier contient toutes les fonctions nécessaires à la gestion des sessions
* de manière sécurisée et cohérente à travers l'application
* 
* @author OmnesBnB
*/

// Configuration des sessions
if (!headers_sent()) {
   // Paramètres de sécurité pour les cookies de session
   ini_set('session.cookie_httponly', 1);
   ini_set('session.use_only_cookies', 1);
   ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
   ini_set('session.cookie_samesite', 'Lax');
   ini_set('session.gc_maxlifetime', 3600); // 1 heure de session
   
   // Protection contre les attaques de fixation de session
   session_regenerate_id();
}

/**
* Démarre la session si elle n'est pas déjà démarrée
* 
* @return bool True si la session a été démarrée, false sinon
*/
function demarrerSession() {
   if (session_status() === PHP_SESSION_NONE) {
       return session_start();
   }
   return true;
}

/**
* Définit une variable de session
* 
* @param string $cle Clé de la variable de session
* @param mixed $valeur Valeur à stocker
* @return void
*/
function definirSessionVar($cle, $valeur) {
   demarrerSession();
   $_SESSION[$cle] = $valeur;
}

/**
* Récupère une variable de session
* 
* @param string $cle Clé de la variable de session
* @param mixed $defaut Valeur par défaut si la clé n'existe pas
* @return mixed Valeur de la variable de session ou valeur par défaut
*/
function obtenirSessionVar($cle, $defaut = null) {
   demarrerSession();
   return isset($_SESSION[$cle]) ? $_SESSION[$cle] : $defaut;
}

/**
* Supprime une variable de session
* 
* @param string $cle Clé de la variable de session à supprimer
* @return void
*/
function supprimerSessionVar($cle) {
   demarrerSession();
   if (isset($_SESSION[$cle])) {
       unset($_SESSION[$cle]);
   }
}

/**
* Vérifie si une variable de session existe
* 
* @param string $cle Clé de la variable de session
* @return bool True si la variable existe, false sinon
*/
function sessionVarExiste($cle) {
   demarrerSession();
   return isset($_SESSION[$cle]);
}

/**
* Stocke les informations de l'utilisateur en session
* 
* @param array $utilisateur Données de l'utilisateur
* @return void
*/
function connecterUtilisateur($utilisateur) {
   demarrerSession();
   
   definirSessionVar('utilisateur_id', $utilisateur['id']);
   definirSessionVar('utilisateur_nom', $utilisateur['nom']);
   definirSessionVar('utilisateur_prenom', $utilisateur['prenom']);
   definirSessionVar('utilisateur_email', $utilisateur['email']);
   definirSessionVar('est_admin', (bool)$utilisateur['est_admin']);
   definirSessionVar('derniere_activite', time());
   
   // Régénérer l'ID de session pour éviter les attaques de fixation de session
   session_regenerate_id(true);
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
   return sessionVarExiste('utilisateur_id') && !empty(obtenirSessionVar('utilisateur_id'));
}

/**
* Vérifie si l'utilisateur est administrateur
* 
* @return bool True si l'utilisateur est administrateur, false sinon
*/
function estAdmin() {
   return estConnecte() && obtenirSessionVar('est_admin') === true;
}

/**
* Régénère l'ID de session pour plus de sécurité
* 
* @return bool True si l'ID a été régénéré, false sinon
*/
function regenererSession() {
   if (session_status() === PHP_SESSION_ACTIVE) {
       return session_regenerate_id(true);
   }
   return false;
}

/**
* Vérifie et met à jour le délai d'expiration de la session
* 
* @param int $delaiInactivite Délai d'inactivité en secondes (par défaut 30 minutes)
* @return bool True si la session est toujours valide, false si expirée
*/
function verifierExpirationSession($delaiInactivite = 1800) {
   if (!estConnecte()) {
       return false;
   }
   
   $derniereActivite = obtenirSessionVar('derniere_activite', 0);
   $tempsEcoule = time() - $derniereActivite;
   
   if ($tempsEcoule > $delaiInactivite) {
       // Session expirée, déconnecter l'utilisateur
       deconnecterUtilisateur();
       return false;
   }
   
   // Mettre à jour le timestamp de dernière activité
   definirSessionVar('derniere_activite', time());
   
   // Régénérer l'ID de session périodiquement pour plus de sécurité
   if ($tempsEcoule > 300) { // Toutes les 5 minutes
       regenererSession();
   }
   
   return true;
}

/**
* Génère un token CSRF
* 
* @return string Token généré
*/
function genererToken() {
   $token = bin2hex(random_bytes(32));
   definirSessionVar('csrf_token', $token);
   return $token;
}

/**
* Vérifie un token CSRF
* 
* @param string $token Token à vérifier
* @return bool True si le token est valide, false sinon
*/
function verifierToken($token) {
   $csrfToken = obtenirSessionVar('csrf_token');
   return isset($csrfToken) && hash_equals($csrfToken, $token);
}

/**
* Récupère l'ID de l'utilisateur connecté
* 
* @return int|null ID de l'utilisateur ou null si non connecté
*/
function obtenirIdUtilisateur() {
   return obtenirSessionVar('utilisateur_id');
}

/**
* Récupère le nom et prénom de l'utilisateur connecté
* 
* @return string|null Nom et prénom de l'utilisateur ou null si non connecté
*/
function obtenirNomUtilisateur() {
   if (!estConnecte()) {
       return null;
   }
   return obtenirSessionVar('utilisateur_prenom') . ' ' . obtenirSessionVar('utilisateur_nom');
}
?>
