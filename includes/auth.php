<?php
require_once '../config/config.php';
require_once 'fonctions.php';

/**
 * Hachage sécurisé d'un mot de passe
 * @param string $motDePasse Mot de passe en clair
 * @return string Mot de passe haché
 */
function hacherMotDePasse($motDePasse) {
    return password_hash($motDePasse, PASSWORD_DEFAULT);
}

/**
 * Vérifie un mot de passe
 * @param string $motDePasse Mot de passe en clair
 * @param string $hash Hash stocké
 * @return bool Résultat de la vérification
 */
function verifierMotDePasse($motDePasse, $hash) {
    return password_verify($motDePasse, $hash);
}

/**
 * Authentifie un utilisateur
 * @param object $utilisateur Données utilisateur
 */
function authentifier($utilisateur) {
    $_SESSION['utilisateur_id'] = $utilisateur['id'];
    $_SESSION['utilisateur_email'] = $utilisateur['email'];
    $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
    $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
    $_SESSION['est_admin'] = $utilisateur['est_admin'];
    $_SESSION['est_verifie'] = $utilisateur['est_verifie'];
    
    // Mettre à jour la dernière connexion
    require_once MODELS_PATH . 'utilisateur.php';
    $utilisateurModel = new UtilisateurModel();
    $utilisateurModel->mettreAJourDerniereConnexion($utilisateur['id']);
}

/**
 * Déconnecte l'utilisateur
 */
function deconnecter() {
    session_unset();
    session_destroy();
    session_start();
}

/**
 * Vérifie si l'utilisateur a accès à une page
 * Redirige si non autorisé
 * @param bool $connexionRequise La connexion est-elle requise
 * @param bool $verificationRequise La vérification est-elle requise
 * @param bool $adminRequis L'accès admin est-il requis
 */
function verifierAcces($connexionRequise = true, $verificationRequise = true, $adminRequis = false) {
    if ($connexionRequise && !estConnecte()) {
        afficherAlerte('Vous devez être connecté pour accéder à cette page.', 'danger');
        rediriger(APP_URL . '/connexion.php');
    }

    if ($verificationRequise && estConnecte() && !$_SESSION['est_verifie']) {
        afficherAlerte('Vous devez vérifier votre email pour accéder à cette page.', 'warning');
        rediriger(APP_URL . '/verification-email.php');
    }

    if ($adminRequis && (!estConnecte() || !$_SESSION['est_admin'])) {
        afficherAlerte('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'danger');
        rediriger(APP_URL);
    }
}
?>
