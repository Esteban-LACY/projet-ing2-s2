<?php
/**
 * Fonctions utilitaires communes pour l'application OmnesBnB
 * 
 * Ce fichier contient diverses fonctions génériques utilisées à travers l'application
 * 
 * @author OmnesBnB
 */

/**
 * Formate un prix pour l'affichage
 * 
 * @param float $prix Prix à formater
 * @param bool $avecDevise Inclure le symbole de devise
 * @return string Prix formaté
 */
function formaterPrix($prix, $avecDevise = true) {
    return number_format($prix, 2, ',', ' ') . ($avecDevise ? ' €' : '');
}

/**
 * Formate une date pour l'affichage
 * 
 * @param string $date Date au format SQL (Y-m-d)
 * @param string $format Format de date souhaité
 * @return string Date formatée
 */
function formaterDate($date, $format = 'd/m/Y') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    
    if ($timestamp === false) {
        return '';
    }
    
    return date($format, $timestamp);
}

/**
 * Calcule le nombre de jours entre deux dates
 * 
 * @param string $dateDebut Date de début (format Y-m-d)
 * @param string $dateFin Date de fin (format Y-m-d)
 * @return int Nombre de jours
 */
function calculerNombreJours($dateDebut, $dateFin) {
    $debut = new DateTime($dateDebut);
    $fin = new DateTime($dateFin);
    $interval = $debut->diff($fin);
    
    return $interval->days;
}

/**
 * Tronque un texte à une longueur donnée
 * 
 * @param string $texte Texte à tronquer
 * @param int $longueur Longueur maximale
 * @param string $suite Caractères à ajouter en fin de texte tronqué
 * @return string Texte tronqué
 */
function tronquerTexte($texte, $longueur = 100, $suite = '...') {
    if (mb_strlen($texte) <= $longueur) {
        return $texte;
    }
    
    $texte = mb_substr($texte, 0, $longueur);
    $dernierEspace = mb_strrpos($texte, ' ');
    
    if ($dernierEspace !== false) {
        $texte = mb_substr($texte, 0, $dernierEspace);
    }
    
    return $texte . $suite;
}

/**
 * Génère un slug à partir d'une chaîne de caractères
 * 
 * @param string $texte Texte à convertir en slug
 * @return string Slug généré
 */
function genererSlug($texte) {
    $texte = mb_strtolower($texte, 'UTF-8');
    $texte = preg_replace('/[^a-z0-9\s]/', '', $texte);
    $texte = preg_replace('/\s+/', '-', $texte);
    
    return trim($texte, '-');
}

/**
 * Génère un nom de fichier unique
 * 
 * @param string $prefixe Préfixe du nom de fichier
 * @param string $extension Extension du fichier
 * @return string Nom de fichier unique
 */
function genererNomFichierUnique($prefixe, $extension) {
    return $prefixe . '_' . uniqid() . '.' . $extension;
}

/**
 * Vérifie si une chaîne est un email valide
 * 
 * @param string $email Email à vérifier
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
 * Retourne l'URL complète d'une ressource
 * 
 * @param string $chemin Chemin relatif de la ressource
 * @return string URL complète
 */
function url($chemin = '') {
    return rtrim(URL_SITE, '/') . '/' . ltrim($chemin, '/');
}

/**
 * Retourne l'URL d'un fichier statique
 * 
 * @param string $chemin Chemin relatif du fichier
 * @return string URL complète
 */
function urlAsset($chemin = '') {
    return url('assets/' . ltrim($chemin, '/'));
}

/**
 * Retourne l'URL d'une photo de logement
 * 
 * @param string $url URL stockée en base de données
 * @return string URL complète
 */
function urlPhotoLogement($url) {
    return empty($url) ? urlAsset('img/placeholders/logement.jpg') : url($url);
}

/**
 * Retourne l'URL d'une photo de profil
 * 
 * @param string $url URL stockée en base de données
 * @return string URL complète
 */
function urlPhotoProfil($url) {
    return empty($url) ? urlAsset('img/placeholders/profil.jpg') : url($url);
}

/**
 * Redirige vers une page et arrête l'exécution du script
 * 
 * @param string $url URL de destination
 * @return void
 */
function rediriger($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo '<script>window.location.href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'";</script>';
        exit();
    }
}

/**
 * Retourne si l'utilisateur actuel est connecté
 * 
 * @return bool True si l'utilisateur est connecté, false sinon
 */
function estConnecte() {
    return isset($_SESSION['utilisateur_id']) && !empty($_SESSION['utilisateur_id']);
}

/**
 * Retourne si l'utilisateur actuel est administrateur
 * 
 * @return bool True si l'utilisateur est administrateur, false sinon
 */
function estAdmin() {
    return estConnecte() && isset($_SESSION['est_admin']) && $_SESSION['est_admin'] === true;
}

/**
 * Nettoie une chaîne de caractères pour éviter les injections XSS
 * 
 * @param string $donnee Donnée à nettoyer
 * @return string Donnée nettoyée
 */
function nettoyer($donnee) {
    $donnee = trim($donnee);
    $donnee = stripslashes($donnee);
    return htmlspecialchars($donnee, ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un token CSRF
 * 
 * @return string Token généré
 */
function genererToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * 
 * @param string $token Token à vérifier
 * @return bool True si le token est valide, false sinon
 */
function verifierToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche les erreurs de formulaire
 * 
 * @param array $erreurs Tableau d'erreurs
 * @return void
 */
function afficherErreurs($erreurs) {
    if (empty($erreurs)) {
        return;
    }
    
    echo '<div class="alert alert-danger"><ul>';
    
    foreach ($erreurs as $erreur) {
        echo '<li>' . $erreur . '</li>';
    }
    
    echo '</ul></div>';
}

/**
 * Affiche un message de succès
 * 
 * @param string $message Message à afficher
 * @return void
 */
function afficherSucces($message) {
    if (empty($message)) {
        return;
    }
    
    echo '<div class="alert alert-success">' . $message . '</div>';
}

/**
 * Affiche un message d'information
 * 
 * @param string $message Message à afficher
 * @return void
 */
function afficherInfo($message) {
    if (empty($message)) {
        return;
    }
    
    echo '<div class="alert alert-info">' . $message . '</div>';
}

/**
 * Affiche un message d'avertissement
 * 
 * @param string $message Message à afficher
 * @return void
 */
function afficherAvertissement($message) {
    if (empty($message)) {
        return;
    }
    
    echo '<div class="alert alert-warning">' . $message . '</div>';
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
    $ip = $_SERVER['REMOTE_ADDR'];
    $ligne = "[$dateHeure] [$niveau] [$ip] $message" . PHP_EOL;
    
    $cheminLog = CHEMIN_RACINE . '/logs/app.log';
    $dossierLogs = dirname($cheminLog);
    
    if (!file_exists($dossierLogs)) {
        mkdir($dossierLogs, 0755, true);
    }
    
    file_put_contents($cheminLog, $ligne, FILE_APPEND);
}
?>
