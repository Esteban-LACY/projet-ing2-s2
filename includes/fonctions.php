<?php
require_once '../config/config.php';

/**
 * Redirection vers une URL
 * @param string $url URL de destination
 */
function rediriger($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Génère un token aléatoire
 * @param int $length Longueur du token
 * @return string Token généré
 */
function genererToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Nettoie les données entrées par l'utilisateur
 * @param string $data Données à nettoyer
 * @return string Données nettoyées
 */
function nettoyer($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool État de connexion
 */
function estConnecte() {
    return isset($_SESSION['utilisateur_id']);
}

/**
 * Vérifie si l'utilisateur est un administrateur
 * @return bool État d'administrateur
 */
function estAdmin() {
    return estConnecte() && isset($_SESSION['est_admin']) && $_SESSION['est_admin'] == true;
}

/**
 * Vérifie si le domaine de l'email est autorisé
 * @param string $email Email à vérifier
 * @return bool État d'autorisation
 */
function estEmailAutorise($email) {
    $domaine = substr(strrchr($email, "@"), 1);
    return in_array($domaine, EMAILS_AUTORISES);
}

/**
 * Génère un nom de fichier unique pour les uploads
 * @param string $extension Extension du fichier
 * @return string Nom de fichier unique
 */
function genererNomFichier($extension) {
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Affiche un message d'alerte
 * @param string $message Message à afficher
 * @param string $type Type d'alerte (success, danger, warning, info)
 */
function afficherAlerte($message, $type = 'info') {
    $_SESSION['alerte'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Récupère et efface l'alerte en session
 * @return array|null Alerte ou null
 */
function recupererAlerte() {
    if (isset($_SESSION['alerte'])) {
        $alerte = $_SESSION['alerte'];
        unset($_SESSION['alerte']);
        return $alerte;
    }
    return null;
}

/**
 * Formate une date
 * @param string $date Date à formater
 * @param string $format Format souhaité
 * @return string Date formatée
 */
function formaterDate($date, $format = 'd/m/Y') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Formate un prix
 * @param float $prix Prix à formater
 * @return string Prix formaté
 */
function formaterPrix($prix) {
    return number_format($prix, 2, ',', ' ') . ' €';
}
?>
