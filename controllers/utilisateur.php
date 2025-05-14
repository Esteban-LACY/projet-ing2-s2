<?php
/**
 * Contrôleur pour la gestion des utilisateurs
 */
require_once 'config/config.php';
require_once 'models/utilisateur.php';
require_once 'includes/fonctions.php';
require_once 'includes/validation.php';

class UtilisateurController {
    private $utilisateurModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->utilisateurModel = new UtilisateurModel();
    }
    
    /**
     * Traite l'inscription d'un utilisateur
     */
    public function inscription() {
        // Vérification si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Récupération et nettoyage des données
        $nom = nettoyer($_POST['nom'] ?? '');
        $prenom = nettoyer($_POST['prenom'] ?? '');
        $email = nettoyer($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';
        $confirmationMotDePasse = $_POST['confirmation_mot_de_passe'] ?? '';
        $telephone = nettoyer($_POST['telephone'] ?? '');
        
        // Validation des données
        $erreurs = [];
        
        if (empty($nom)) {
            $erreurs['nom'] = 'Le nom est obligatoire.';
        }
        
        if (empty($prenom)) {
            $erreurs['prenom'] = 'Le prénom est obligatoire.';
        }
        
        if (empty($email)) {
            $erreurs['email'] = 'L\'email est obligatoire.';
        } elseif (!estEmailValide($email)) {
            $erreurs['email'] = 'Veuillez entrer un email valide.';
        } elseif (!estEmailAutorise($email)) {
            $erreurs['email'] = 'Seules les adresses email institutionnelles sont autorisées.';
        } elseif ($this->utilisateurModel->emailExiste($email)) {
            $erreurs['email'] = 'Cette adresse email est déjà utilisée.';
        }
        
        if (empty($motDePasse)) {
            $erreurs['mot_de_passe'] = 'Le mot de passe est obligatoire.';
        } elseif (strlen($motDePasse) < PASSWORD_MIN_LENGTH) {
            $erreurs['mot_de_passe'] = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères.';
        }
        
        if ($motDePasse !== $confirmationMotDePasse) {
            $erreurs['confirmation_mot_de_passe'] = 'Les mots de passe ne correspondent pas.';
        }
        
        if (!empty($telephone) && !estTelephoneValide($telephone)) {
            $erreurs['telephone'] = 'Le numéro de téléphone n\'est pas valide.';
        }
        
        // S'il y a des erreurs, on les stocke en session
        if (!empty($erreurs)) {
            $_SESSION['erreurs_inscription'] = $erreurs;
            $_SESSION['donnees_inscription'] = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone
            ];
            rediriger('inscription.php');
            return;
        }
        
        // Génération d'un token pour la vérification par email
        $tokenVerification = bin2hex(random_bytes(32));
        
        // Hashage du mot de passe
        $motDePasseHash = password_hash($motDePasse, PASSWORD_DEFAULT);
        
        // Création de l'utilisateur
        $donneesUtilisateur = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'mot_de_passe' => $motDePasseHash,
            'telephone' => $telephone,
            'token_verification' => $tokenVerification
        ];
        
        $idUtilisateur = $this->utilisateurModel->creer($donneesUtilisateur);
        
        if (!$idUtilisateur) {
            afficherMessage('Une erreur est survenue lors de l\'inscription. Veuillez réessayer.', 'erreur');
            rediriger('inscription.php');
            return;
        }
        
        // Envoi de l'email de vérification
        if (EMAIL_VERIFICATION) {
            $lienVerification = APP_URL . '/verification.php?email=' . urlencode($email) . '&token=' . $tokenVerification;
            
            $contenu = getEmailVerificationContent($prenom, $lienVerification);
            $email = getEmailTemplate(EMAIL_VERIFICATION_SUBJECT, $contenu);
            
            // Envoi de l'email (à implémenter avec PHPMailer ou autre)
            // envoyerEmail($email, EMAIL_VERIFICATION_SUBJECT, $email);
            
            afficherMessage('Votre compte a été créé avec succès ! Veuillez vérifier votre email pour activer votre compte.', 'succes');
            rediriger('connexion.php');
        } else {
            // Si la vérification par email est désactivée, on connecte directement l'utilisateur
            $utilisateur = $this->utilisateurModel->recupererParId($idUtilisateur);
            $this->connecterUtilisateur($utilisateur);
            
            afficherMessage('Votre compte a été créé avec succès ! Vous êtes maintenant connecté.', 'succes');
            rediriger('index.php');
        }
    }
    
    /**
     * Traite la connexion d'un utilisateur
     */
    public function connexion() {
        // Vérification si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Récupération des données
        $email = nettoyer($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';
        
        // Validation des données
        $erreurs = [];
        
        if (empty($email)) {
            $erreurs['email'] = 'L\'email est obligatoire.';
        }
        
        if (empty($motDePasse)) {
            $erreurs['mot_de_passe'] = 'Le mot de passe est obligatoire.';
        }
        
        // S'il y a des erreurs, on les stocke en session
        if (!empty($erreurs)) {
            $_SESSION['erreurs_connexion'] = $erreurs;
            $_SESSION['donnees_connexion'] = [
                'email' => $email
            ];
            rediriger('connexion.php');
            return;
        }
        
        // Récupération de l'utilisateur
        $utilisateur = $this->utilisateurModel->recupererParEmail($email);
        
        if (!$utilisateur) {
            afficherMessage('Email ou mot de passe incorrect.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Vérification du mot de passe
        if (!password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
            afficherMessage('Email ou mot de passe incorrect.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Vérification si l'utilisateur a vérifié son email
        if (EMAIL_VERIFICATION && !$utilisateur['est_verifie']) {
            afficherMessage('Veuillez vérifier votre email avant de vous connecter.', 'avertissement');
            rediriger('connexion.php');
            return;
        }
        
        // Connexion de l'utilisateur
        $this->connecterUtilisateur($utilisateur);
        
        // Mise à jour de la dernière connexion
        $this->utilisateurModel->mettreAJourDerniereConnexion($utilisateur['id']);
        
        // Redirection
        $urlRedirection = $_SESSION['url_apres_connexion'] ?? 'index.php';
        unset($_SESSION['url_apres_connexion']);
        
        afficherMessage('Vous êtes maintenant connecté.', 'succes');
        rediriger($urlRedirection);
    }
    
    /**
     * Traite la déconnexion d'un utilisateur
     */
    public function deconnexion() {
        // Destruction de la session
        session_unset();
        session_destroy();
        
        // Redémarrage de la session pour les messages
        session_start();
        
        afficherMessage('Vous êtes maintenant déconnecté.', 'succes');
        rediriger('index.php');
    }
    
    /**
     * Traite la vérification d'email
     */
    public function verifierEmail() {
        // Récupération des données
        $email = nettoyer($_GET['email'] ?? '');
        $token = nettoyer($_GET['token'] ?? '');
        
        if (empty($email) || empty($token)) {
            afficherMessage('Lien de vérification invalide.', 'erreur');
            rediriger('index.php');
            return false;
        }
        
        // Vérification du token
        $utilisateur = $this->utilisateurModel->recupererParEmailEtToken($email, $token);
        
        if (!$utilisateur) {
            afficherMessage('Lien de vérification invalide ou expiré.', 'erreur');
            rediriger('index.php');
            return false;
        }
        
        // Activation du compte
        $this->utilisateurModel->activerCompte($utilisateur['id']);
        
        afficherMessage('Votre compte a été vérifié avec succès ! Vous pouvez maintenant vous connecter.', 'succes');
        rediriger('connexion.php');
        return true;
    }
    
    /**
     * Traite la modification du profil
     */
    public function modifierProfil() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            afficherMessage('Vous devez être connecté pour modifier votre profil.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Vérification si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Récupération et nettoyage des données
        $nom = nettoyer($_POST['nom'] ?? '');
        $prenom = nettoyer($_POST['prenom'] ?? '');
        $telephone = nettoyer($_POST['telephone'] ?? '');
        
        // Validation des données
        $erreurs = [];
        
        if (empty($nom)) {
            $erreurs['nom'] = 'Le nom est obligatoire.';
        }
        
        if (empty($prenom)) {
            $erreurs['prenom'] = 'Le prénom est obligatoire.';
        }
        
        if (!empty($telephone) && !estTelephoneValide($telephone)) {
            $erreurs['telephone'] = 'Le numéro de téléphone n\'est pas valide.';
        }
        
        // Gestion de l'upload de la photo de profil
        $photoProfilChemin = null;
        
        if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
            $resultat = validerFichierImage($_FILES['photo_profil']);
            
            if (!$resultat['valide']) {
                $erreurs['photo_profil'] = $resultat['message'];
            } else {
                $photoProfilChemin = traiterUploadImage($_FILES['photo_profil'], 'profils');
                
                if ($photoProfilChemin === false) {
                    $erreurs['photo_profil'] = 'Une erreur est survenue lors de l\'upload de la photo.';
                }
            }
        }
        
        // S'il y a des erreurs, on les stocke en session
        if (!empty($erreurs)) {
            $_SESSION['erreurs_profil'] = $erreurs;
            $_SESSION['donnees_profil'] = [
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => $telephone
            ];
            rediriger('modifier-profil.php');
            return;
        }
        
        // Récupération de l'utilisateur actuel
        $utilisateur = $this->utilisateurModel->recupererParId($_SESSION['utilisateur_id']);
        
        // Préparation des données à mettre à jour
        $donnees = [
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone
        ];
        
        // Ajout de la photo si elle a été uploadée
        if ($photoProfilChemin !== null) {
            $donnees['photo_profil'] = $photoProfilChemin;
            
            // Suppression de l'ancienne photo si elle existe
            if (!empty($utilisateur['photo_profil'])) {
                $cheminAnciennePhoto = ROOT_PATH . 'uploads/profils/' . $utilisateur['photo_profil'];
                
                if (file_exists($cheminAnciennePhoto)) {
                    unlink($cheminAnciennePhoto);
                }
            }
        }
        
        // Mise à jour du profil
        $resultat = $this->utilisateurModel->mettreAJour($_SESSION['utilisateur_id'], $donnees);
        
        if (!$resultat) {
            afficherMessage('Une erreur est survenue lors de la mise à jour du profil.', 'erreur');
            rediriger('modifier-profil.php');
            return;
        }
        
        // Mise à jour des données de session
        $_SESSION['utilisateur_nom'] = $nom;
        $_SESSION['utilisateur_prenom'] = $prenom;
        
        afficherMessage('Votre profil a été mis à jour avec succès.', 'succes');
        rediriger('profil.php');
    }
    
    /**
     * Traite le changement de mot de passe
     */
    public function changerMotDePasse() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            afficherMessage('Vous devez être connecté pour changer votre mot de passe.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Vérification si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Récupération des données
        $motDePasseActuel = $_POST['mot_de_passe_actuel'] ?? '';
        $nouveauMotDePasse = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirmationMotDePasse = $_POST['confirmation_mot_de_passe'] ?? '';
        
        // Validation des données
        $erreurs = [];
        
        if (empty($motDePasseActuel)) {
            $erreurs['mot_de_passe_actuel'] = 'Le mot de passe actuel est obligatoire.';
        }
        
        if (empty($nouveauMotDePasse)) {
            $erreurs['nouveau_mot_de_passe'] = 'Le nouveau mot de passe est obligatoire.';
        } elseif (strlen($nouveauMotDePasse) < PASSWORD_MIN_LENGTH) {
            $erreurs['nouveau_mot_de_passe'] = 'Le nouveau mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères.';
        }
        
        if ($nouveauMotDePasse !== $confirmationMotDePasse) {
            $erreurs['confirmation_mot_de_passe'] = 'Les mots de passe ne correspondent pas.';
        }
        
        // Récupération de l'utilisateur actuel
        $utilisateur = $this->utilisateurModel->recupererParId($_SESSION['utilisateur_id']);
        
        // Vérification du mot de passe actuel
        if (!password_verify($motDePasseActuel, $utilisateur['mot_de_passe'])) {
            $erreurs['mot_de_passe_actuel'] = 'Le mot de passe actuel est incorrect.';
        }
        
        // S'il y a des erreurs, on les stocke en session
        if (!empty($erreurs)) {
            $_SESSION['erreurs_mot_de_passe'] = $erreurs;
            rediriger('changer-mot-de-passe.php');
            return;
        }
        
        // Hashage du nouveau mot de passe
        $nouveauMotDePasseHash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
        
        // Mise à jour du mot de passe
        $resultat = $this->utilisateurModel->changerMotDePasse($_SESSION['utilisateur_id'], $nouveauMotDePasseHash);
        
        if (!$resultat) {
            afficherMessage('Une erreur est survenue lors du changement de mot de passe.', 'erreur');
            rediriger('changer-mot-de-passe.php');
            return;
        }
        
        afficherMessage('Votre mot de passe a été changé avec succès.', 'succes');
        rediriger('profil.php');
    }
    
    /**
     * Connecte un utilisateur
     * @param array $utilisateur Données de l'utilisateur
     */
    private function connecterUtilisateur($utilisateur) {
        $_SESSION['utilisateur_id'] = $utilisateur['id'];
        $_SESSION['utilisateur_email'] = $utilisateur['email'];
        $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
        $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
        $_SESSION['utilisateur_est_admin'] = (bool)$utilisateur['est_admin'];
        $_SESSION['utilisateur_est_verifie'] = (bool)$utilisateur['est_verifie'];
    }
}
?>
