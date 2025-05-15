<?php
/**
 * Contrôleur pour la gestion des utilisateurs
 * 
 * Ce fichier gère les actions liées aux utilisateurs (inscription, connexion, profil, etc.)
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_MODELES . '/utilisateur.php';
require_once CHEMIN_INCLUDES . '/validation.php';
require_once CHEMIN_INCLUDES . '/email.php';

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'inscription':
        actionInscription();
        break;
    case 'connexion':
        actionConnexion();
        break;
    case 'deconnexion':
        actionDeconnexion();
        break;
    case 'verifier_email':
        actionVerifierEmail();
        break;
    case 'modifier_profil':
        actionModifierProfil();
        break;
    case 'modifier_mot_de_passe':
        actionModifierMotDePasse();
        break;
    case 'upload_photo':
        actionUploadPhoto();
        break;
    case 'supprimer_compte':
        actionSupprimerCompte();
        break;
    default:
        // Si aucune action n'est spécifiée, rediriger vers la page d'accueil
        rediriger(URL_SITE);
}

/**
 * Gère l'inscription d'un nouvel utilisateur
 */
function actionInscription() {
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer et nettoyer les données du formulaire
    $nom = isset($_POST['nom']) ? nettoyer($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? nettoyer($_POST['prenom']) : '';
    $email = isset($_POST['email']) ? nettoyer($_POST['email']) : '';
    $motDePasse = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    $confirmerMotDePasse = isset($_POST['confirmer_mot_de_passe']) ? $_POST['confirmer_mot_de_passe'] : '';
    
    // Valider les données
    $erreurs = [];
    
    if (empty($nom)) {
        $erreurs[] = 'Le nom est requis';
    }
    
    if (empty($prenom)) {
        $erreurs[] = 'Le prénom est requis';
    }
    
    if (empty($email)) {
        $erreurs[] = 'L\'email est requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Format d\'email invalide';
    } else {
        // Vérifier que l'email est institutionnel
        $domaineValide = false;
        foreach (DOMAINES_EMAIL_AUTORISES as $domaine) {
            if (endsWith($email, '@' . $domaine)) {
                $domaineValide = true;
                break;
            }
        }
        
        if (!$domaineValide) {
            $erreurs[] = 'Vous devez utiliser une adresse email institutionnelle';
        }
        
        // Vérifier que l'email n'est pas déjà utilisé
        if (utilisateurExisteParEmail($email)) {
            $erreurs[] = 'Cette adresse email est déjà utilisée';
        }
    }
    
    if (empty($motDePasse)) {
        $erreurs[] = 'Le mot de passe est requis';
    } elseif (strlen($motDePasse) < 8) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères';
    }
    
    if ($motDePasse !== $confirmerMotDePasse) {
        $erreurs[] = 'Les mots de passe ne correspondent pas';
    }
    
    // Si des erreurs sont présentes, renvoyer une réponse d'erreur
    if (!empty($erreurs)) {
        repondreJSON(['success' => false, 'erreurs' => $erreurs]);
        return;
    }
    
    // Générer un token de vérification
    $tokenVerification = bin2hex(random_bytes(32));
    
    // Hacher le mot de passe
    $motDePasseHash = password_hash($motDePasse, PASSWORD_DEFAULT);
    
    // Créer l'utilisateur
    $idUtilisateur = creerUtilisateur([
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'mot_de_passe' => $motDePasseHash,
        'token_verification' => $tokenVerification
    ]);
    
    if (!$idUtilisateur) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la création du compte']);
        return;
    }
    
    // Envoyer l'email de vérification
    $sujet = 'Vérification de votre compte OmnesBnB';
    $lienVerification = URL_SITE . '/verifier-email.php?token=' . $tokenVerification;
    
    $message = "Bonjour $prenom $nom,\n\n";
    $message .= "Merci de vous être inscrit sur OmnesBnB. Pour confirmer votre adresse email, veuillez cliquer sur le lien suivant :\n\n";
    $message .= "$lienVerification\n\n";
    $message .= "Si vous n'avez pas créé de compte sur OmnesBnB, veuillez ignorer cet email.\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    $envoiReussi = envoyerEmail($email, $sujet, $message);
    
    if (!$envoiReussi) {
        repondreJSON([
            'success' => true,
            'message' => 'Compte créé mais erreur lors de l\'envoi de l\'email de vérification. Veuillez contacter le support.'
        ]);
        return;
    }
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'message' => 'Compte créé avec succès. Un email de vérification a été envoyé à votre adresse.'
    ]);
}

/**
 * Gère la connexion d'un utilisateur
 */
function actionConnexion() {
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer et nettoyer les données du formulaire
    $email = isset($_POST['email']) ? nettoyer($_POST['email']) : '';
    $motDePasse = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    
    // Valider les données
    if (empty($email) || empty($motDePasse)) {
        repondreJSON(['success' => false, 'message' => 'Email et mot de passe requis']);
        return;
    }
    
    // Récupérer l'utilisateur
    $utilisateur = recupererUtilisateurParEmail($email);
    
    if (!$utilisateur) {
        repondreJSON(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
        return;
    }
    
    // Vérifier le mot de passe
    if (!password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
        repondreJSON(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
        return;
    }
    
    // Vérifier si l'email est vérifié
    if (!$utilisateur['est_verifie']) {
        repondreJSON(['success' => false, 'message' => 'Veuillez vérifier votre adresse email avant de vous connecter']);
        return;
    }
    
    // Mettre à jour la date de dernière connexion
    majDerniereConnexion($utilisateur['id']);
    
    // Stocker les informations de l'utilisateur en session
    $_SESSION['utilisateur_id'] = $utilisateur['id'];
    $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
    $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
    $_SESSION['utilisateur_email'] = $utilisateur['email'];
    $_SESSION['est_admin'] = $utilisateur['est_admin'] == 1;
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'message' => 'Connexion réussie',
        'redirection' => $utilisateur['est_admin'] == 1 ? URL_SITE . '/admin' : URL_SITE
    ]);
}

/**
 * Gère la déconnexion d'un utilisateur
 */
function actionDeconnexion() {
    // Détruire la session
    session_destroy();
    
    // Rediriger vers la page d'accueil
    rediriger(URL_SITE);
}

/**
 * Gère la vérification de l'email d'un utilisateur
 */
function actionVerifierEmail() {
    // Récupérer le token de vérification
    $token = isset($_GET['token']) ? nettoyer($_GET['token']) : '';
    
    if (empty($token)) {
        repondreJSON(['success' => false, 'message' => 'Token de vérification manquant']);
        return;
    }
    
    // Vérifier le token
    $utilisateur = recupererUtilisateurParToken($token);
    
    if (!$utilisateur) {
        repondreJSON(['success' => false, 'message' => 'Token de vérification invalide ou expiré']);
        return;
    }
    
    // Marquer l'email comme vérifié
    $resultat = verifierEmail($utilisateur['id']);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la vérification de l\'email']);
        return;
    }
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'message' => 'Email vérifié avec succès. Vous pouvez maintenant vous connecter.'
    ]);
}

/**
 * Gère la modification du profil d'un utilisateur
 */
function actionModifierProfil() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour modifier votre profil']);
        return;
    }
    
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer et nettoyer les données du formulaire
    $nom = isset($_POST['nom']) ? nettoyer($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? nettoyer($_POST['prenom']) : '';
    $email = isset($_POST['email']) ? nettoyer($_POST['email']) : '';
    $telephone = isset($_POST['telephone']) ? nettoyer($_POST['telephone']) : '';
    
    // Récupérer l'ID de l'utilisateur connecté
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    // Valider les données
    $erreurs = [];
    
    if (empty($nom)) {
        $erreurs[] = 'Le nom est requis';
    }
    
    if (empty($prenom)) {
        $erreurs[] = 'Le prénom est requis';
    }
    
    if (empty($email)) {
        $erreurs[] = 'L\'email est requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Format d\'email invalide';
    } else {
        // Vérifier que l'email est institutionnel
        $domaineValide = false;
        foreach (DOMAINES_EMAIL_AUTORISES as $domaine) {
            if (endsWith($email, '@' . $domaine)) {
                $domaineValide = true;
                break;
            }
        }
        
        if (!$domaineValide) {
            $erreurs[] = 'Vous devez utiliser une adresse email institutionnelle';
        }
        
        // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
        $utilisateur = recupererUtilisateurParEmail($email);
        if ($utilisateur && $utilisateur['id'] != $idUtilisateur) {
            $erreurs[] = 'Cette adresse email est déjà utilisée';
        }
    }
    
    if (!empty($telephone)) {
        // Valider le format du téléphone (format français)
        if (!preg_match('/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/', $telephone)) {
            $erreurs[] = 'Format de téléphone invalide';
        }
    }
    
    // Si des erreurs sont présentes, renvoyer une réponse d'erreur
    if (!empty($erreurs)) {
        repondreJSON(['success' => false, 'erreurs' => $erreurs]);
        return;
    }
    
    // Récupérer l'utilisateur actuel
    $utilisateurActuel = recupererUtilisateurParId($idUtilisateur);
    
    // Vérifier si l'email a changé
    $emailChange = $email !== $utilisateurActuel['email'];
    
    // Si l'email a changé, générer un nouveau token de vérification
    $tokenVerification = null;
    if ($emailChange) {
        $tokenVerification = bin2hex(random_bytes(32));
    }
    
    // Mettre à jour le profil
    $donnees = [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone
    ];
    
    if ($emailChange) {
        $donnees['est_verifie'] = false;
        $donnees['token_verification'] = $tokenVerification;
    }
    
    $resultat = modifierProfil($idUtilisateur, $donnees);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la modification du profil']);
        return;
    }
    
    // Si l'email a changé, envoyer un nouvel email de vérification
    if ($emailChange && $tokenVerification) {
        $sujet = 'Vérification de votre nouvelle adresse email OmnesBnB';
        $lienVerification = URL_SITE . '/verifier-email.php?token=' . $tokenVerification;
        
        $message = "Bonjour $prenom $nom,\n\n";
        $message .= "Vous avez récemment modifié votre adresse email sur OmnesBnB. Pour confirmer votre nouvelle adresse, veuillez cliquer sur le lien suivant :\n\n";
        $message .= "$lienVerification\n\n";
        $message .= "Si vous n'avez pas effectué cette modification, veuillez nous contacter immédiatement.\n\n";
        $message .= "Cordialement,\nL'équipe OmnesBnB";
        
        $envoiReussi = envoyerEmail($email, $sujet, $message);
        
        if (!$envoiReussi) {
            repondreJSON([
                'success' => true,
                'message' => 'Profil modifié mais erreur lors de l\'envoi de l\'email de vérification. Veuillez contacter le support.'
            ]);
            return;
        }
        
        // Mettre à jour les informations de la session
        $_SESSION['utilisateur_nom'] = $nom;
        $_SESSION['utilisateur_prenom'] = $prenom;
        $_SESSION['utilisateur_email'] = $email;
        
        repondreJSON([
            'success' => true,
            'message' => 'Profil modifié avec succès. Un email de vérification a été envoyé à votre nouvelle adresse.'
        ]);
    } else {
        // Mettre à jour les informations de la session
        $_SESSION['utilisateur_nom'] = $nom;
        $_SESSION['utilisateur_prenom'] = $prenom;
        $_SESSION['utilisateur_email'] = $email;
        
        repondreJSON(['success' => true, 'message' => 'Profil modifié avec succès']);
    }
}

/**
 * Gère la modification du mot de passe d'un utilisateur
 */
function actionModifierMotDePasse() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour modifier votre mot de passe']);
        return;
    }
    
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer les données du formulaire
    $ancienMotDePasse = isset($_POST['ancien_mot_de_passe']) ? $_POST['ancien_mot_de_passe'] : '';
    $nouveauMotDePasse = isset($_POST['nouveau_mot_de_passe']) ? $_POST['nouveau_mot_de_passe'] : '';
    $confirmerMotDePasse = isset($_POST['confirmer_mot_de_passe']) ? $_POST['confirmer_mot_de_passe'] : '';
    
    // Récupérer l'ID de l'utilisateur connecté
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    // Valider les données
    if (empty($ancienMotDePasse) || empty($nouveauMotDePasse) || empty($confirmerMotDePasse)) {
        repondreJSON(['success' => false, 'message' => 'Tous les champs sont requis']);
        return;
    }
    
    if ($nouveauMotDePasse !== $confirmerMotDePasse) {
        repondreJSON(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas']);
        return;
    }
    
    if (strlen($nouveauMotDePasse) < 8) {
        repondreJSON(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères']);
        return;
    }
    
    // Récupérer l'utilisateur
    $utilisateur = recupererUtilisateurParId($idUtilisateur);
    
    // Vérifier l'ancien mot de passe
    if (!password_verify($ancienMotDePasse, $utilisateur['mot_de_passe'])) {
        repondreJSON(['success' => false, 'message' => 'Ancien mot de passe incorrect']);
        return;
    }
    
    // Hacher le nouveau mot de passe
    $nouveauMotDePasseHash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
    
    // Mettre à jour le mot de passe
    $resultat = modifierMotDePasse($idUtilisateur, $nouveauMotDePasseHash);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la modification du mot de passe']);
        return;
    }
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
}

/**
 * Gère l'upload de la photo de profil
 */
function actionUploadPhoto() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour modifier votre photo de profil']);
        return;
    }
    
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
        return;
    }
    
    // Vérifier le type de fichier
    $fichier = $_FILES['photo'];
    $typesFichierAutorises = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($fichier['type'], $typesFichierAutorises)) {
        repondreJSON(['success' => false, 'message' => 'Type de fichier non autorisé. Seuls les formats JPEG, PNG et GIF sont acceptés']);
        return;
    }
    
    // Vérifier la taille du fichier (max 2MB)
    if ($fichier['size'] > 2 * 1024 * 1024) {
        repondreJSON(['success' => false, 'message' => 'Le fichier est trop volumineux. Taille maximale: 2MB']);
        return;
    }
    
    // Récupérer l'ID de l'utilisateur connecté
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    // Créer le répertoire d'upload si nécessaire
    if (!file_exists(CHEMIN_UPLOADS_PROFILS)) {
        mkdir(CHEMIN_UPLOADS_PROFILS, 0755, true);
    }
    
    // Générer un nom unique pour le fichier
    $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
    $nomFichier = 'profil_' . $idUtilisateur . '_' . time() . '.' . $extension;
    $cheminFichier = CHEMIN_UPLOADS_PROFILS . '/' . $nomFichier;
    
    // Déplacer le fichier uploadé
    if (!move_uploaded_file($fichier['tmp_name'], $cheminFichier)) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors du déplacement du fichier']);
        return;
    }
    
    // Récupérer l'ancienne photo de profil
    $utilisateur = recupererUtilisateurParId($idUtilisateur);
    $anciennePhoto = $utilisateur['photo_profil'];
    
    // Mettre à jour la photo de profil dans la base de données
    $cheminRelatif = '/uploads/profils/' . $nomFichier;
    $resultat = modifierPhotoProfil($idUtilisateur, $cheminRelatif);
    
    if (!$resultat) {
        // Supprimer le fichier en cas d'erreur
        unlink($cheminFichier);
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la mise à jour de la photo de profil']);
        return;
    }
    
    // Supprimer l'ancienne photo si elle existe
    if ($anciennePhoto && file_exists(CHEMIN_RACINE . $anciennePhoto)) {
        unlink(CHEMIN_RACINE . $anciennePhoto);
    }
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'message' => 'Photo de profil mise à jour avec succès',
        'photo_url' => $cheminRelatif
    ]);
}

/**
 * Gère la suppression du compte d'un utilisateur
 */
function actionSupprimerCompte() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour supprimer votre compte']);
        return;
    }
    
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID de l'utilisateur connecté
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    // Vérifier si l'utilisateur a des réservations en cours
    if (utilisateurAReservationsEnCours($idUtilisateur)) {
        repondreJSON(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre compte car vous avez des réservations en cours']);
        return;
    }
    
    // Récupérer les informations de l'utilisateur
    $utilisateur = recupererUtilisateurParId($idUtilisateur);
    
    // Supprimer la photo de profil si elle existe
    if ($utilisateur['photo_profil'] && file_exists(CHEMIN_RACINE . $utilisateur['photo_profil'])) {
        unlink(CHEMIN_RACINE . $utilisateur['photo_profil']);
    }
    
    // Supprimer le compte
    $resultat = supprimerUtilisateur($idUtilisateur);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la suppression du compte']);
        return;
    }
    
    // Détruire la session
    session_destroy();
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Compte supprimé avec succès']);
}

/**
 * Renvoie une réponse JSON et termine le script
 * 
 * @param array $donnees Données à renvoyer
 */
function repondreJSON($donnees) {
    header('Content-Type: application/json');
    echo json_encode($donnees);
    exit;
}

/**
 * Vérifie si une chaîne se termine par un suffixe donné
 * 
 * @param string $chaine Chaîne à vérifier
 * @param string $suffixe Suffixe à rechercher
 * @return boolean True si la chaîne se termine par le suffixe, false sinon
 */
function endsWith($chaine, $suffixe) {
    return substr_compare($chaine, $suffixe, -strlen($suffixe)) === 0;
}
?>
