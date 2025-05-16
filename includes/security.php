<?php
/**
* Fonctions de sécurité centralisées
* 
* Ce fichier contient toutes les fonctions liées à la sécurité de l'application
* pour éviter les redondances et centraliser la gestion de la sécurité
* 
* @author OmnesBnB
*/

// Inclusion du fichier de configuration si pas déjà inclus
if (!defined('CHEMIN_RACINE')) {
   require_once __DIR__ . '/../config/config.php';
}

/**
* Nettoie une chaîne de caractères pour éviter les injections XSS
* 
* @param string $donnee Donnée à nettoyer
* @return string Donnée nettoyée
*/
function nettoyer($donnee) {
   if (is_array($donnee)) {
       // Si c'est un tableau, nettoyer chaque élément récursivement
       return array_map('nettoyer', $donnee);
   }
   
   // Pour les chaînes
   $donnee = trim($donnee);
   $donnee = stripslashes($donnee);
   return htmlspecialchars($donnee, ENT_QUOTES, 'UTF-8');
}

/**
* Valide un jeton CSRF
* 
* @param string $token Jeton à valider
* @return bool True si le jeton est valide, false sinon
*/
function verifierToken($token) {
   if (!isset($_SESSION['csrf_token'])) {
       return false;
   }
   
   return hash_equals($_SESSION['csrf_token'], $token);
}

/**
* Génère un jeton CSRF et le stocke en session
* 
* @return string Jeton généré
*/
function genererToken() {
   if (!isset($_SESSION['csrf_token'])) {
       $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   }
   
   return $_SESSION['csrf_token'];
}

/**
* Renvoie le jeton CSRF actuel sans en générer un nouveau
* 
* @return string|null Jeton actuel ou null s'il n'existe pas
*/
function getToken() {
   return $_SESSION['csrf_token'] ?? null;
}

/**
* Crée un champ caché pour le jeton CSRF à inclure dans les formulaires
* 
* @return string Champ input HTML avec le jeton CSRF
*/
function genererChampToken() {
   $token = genererToken();
   return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
* Sécurise les entêtes HTTP pour prévenir les attaques
*/
function securiserEntetes() {
   // Protection contre le clickjacking
   header('X-Frame-Options: SAMEORIGIN');
   
   // Protection contre le MIME-sniffing
   header('X-Content-Type-Options: nosniff');
   
   // Protection XSS pour les navigateurs modernes
   header('X-XSS-Protection: 1; mode=block');
   
   // Politique de sécurité du contenu (CSP)
   // Ajustez selon les besoins spécifiques de l'application
   header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://maps.googleapis.com https://js.stripe.com; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https://*.googleapis.com https://*.gstatic.com; connect-src 'self' https://*.stripe.com https://*.googleapis.com; frame-src https://*.stripe.com;");
   
   // Strict Transport Security
   if (!MODE_DEVELOPPEMENT) {
       header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
   }
}

/**
* Valide une adresse email
* 
* @param string $email Email à valider
* @return bool True si l'email est valide, false sinon
*/
function estEmailValide($email) {
   return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
* Vérifie si un email appartient à un domaine autorisé
* 
* @param string $email Email à vérifier
* @param array $domainesAutorises Liste des domaines autorisés
* @return bool True si l'email appartient à un domaine autorisé, false sinon
*/
function estDomainEmailAutorise($email, $domainesAutorises = DOMAINES_EMAIL_AUTORISES) {
   if (!estEmailValide($email)) {
       return false;
   }
   
   $domaine = substr($email, strpos($email, '@') + 1);
   
   return in_array($domaine, $domainesAutorises);
}

/**
* Vérifie si une chaîne est un numéro de téléphone valide (format français)
* 
* @param string $telephone Numéro de téléphone à vérifier
* @return bool True si le numéro est valide, false sinon
*/
function estTelephoneValide($telephone) {
   return preg_match('/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/', $telephone) === 1;
}

/**
* Vérifie si une chaîne est un code postal français valide
* 
* @param string $codePostal Code postal à vérifier
* @return bool True si le code postal est valide, false sinon
*/
function estCodePostalValide($codePostal) {
   return preg_match('/^[0-9]{5}$/', $codePostal) === 1;
}

/**
* Génère un mot de passe aléatoire sécurisé
* 
* @param int $longueur Longueur du mot de passe
* @return string Mot de passe généré
*/
function genererMotDePasse($longueur = 12) {
   $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()-_+=';
   $mdp = '';
   
   for ($i = 0; $i < $longueur; $i++) {
       $mdp .= $caracteres[random_int(0, strlen($caracteres) - 1)];
   }
   
   return $mdp;
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
* Vérifie si une date est valide et au format Y-m-d
* 
* @param string $date Date à vérifier
* @return bool True si la date est valide, false sinon
*/
function estDateValide($date) {
   if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
       return false;
   }
   
   $parts = explode('-', $date);
   
   return checkdate($parts[1], $parts[2], $parts[0]);
}

/**
* Vérifie si une date est future
* 
* @param string $date Date à vérifier (format Y-m-d)
* @return bool True si la date est future, false sinon
*/
function estDateFuture($date) {
   if (!estDateValide($date)) {
       return false;
   }
   
   return strtotime($date) > time();
}

/**
* Journalise un message dans le fichier de log
* 
* @param string $message Message à journaliser
* @param string $niveau Niveau de log (INFO, WARNING, ERROR)
* @return void
*/
function journaliser($message, $niveau = 'INFO') {
   $dateHeure = date('Y-m-d H:i:s');
   $ip = $_SERVER['REMOTE_ADDR'] ?? 'Inconnue';
   $ligne = "[$dateHeure] [$niveau] [$ip] $message" . PHP_EOL;
   
   $cheminLog = CHEMIN_RACINE . '/logs/app.log';
   $dossierLogs = dirname($cheminLog);
   
   if (!file_exists($dossierLogs)) {
       mkdir($dossierLogs, 0755, true);
   }
   
   file_put_contents($cheminLog, $ligne, FILE_APPEND);
}

/**
* Prépare les paramètres d'une requête SQL pour éviter les injections
* 
* @param array $params Paramètres bruts
* @return array Paramètres préparés pour PDO
*/
function preparerParamsSQL($params) {
   $prepared = [];
   
   foreach ($params as $key => $value) {
       // Si la clé ne commence pas par ':', l'ajouter
       if (substr($key, 0, 1) !== ':') {
           $key = ':' . $key;
       }
       
       $prepared[$key] = $value;
   }
   
   return $prepared;
}

/**
* Redirige l'utilisateur vers une autre page
* 
* @param string $url URL de destination
* @param bool $permanent Redirection permanente (301) ou temporaire (302)
* @return void
*/
function rediriger($url, $permanent = false) {
   if ($permanent) {
       header('HTTP/1.1 301 Moved Permanently');
   }
   
   header('Location: ' . $url);
   exit();
}

/**
* Vérifie si une requête est une requête AJAX
* 
* @return bool True si c'est une requête AJAX, false sinon
*/
function estAjax() {
   return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
* Répond avec un JSON et termine le script
* 
* @param array $donnees Données à renvoyer
* @param int $statusCode Code de statut HTTP
* @return void
*/
function repondreJSON($donnees, $statusCode = 200) {
   http_response_code($statusCode);
   header('Content-Type: application/json');
   echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
   exit;
}
?>
