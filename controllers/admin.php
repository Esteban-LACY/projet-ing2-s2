<?php
/**
 * Contrôleur pour la gestion des fonctionnalités administrateur
 * 
 * Ce fichier gère les actions réservées aux administrateurs
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_MODELES . '/utilisateur.php';
require_once CHEMIN_MODELES . '/logement.php';
require_once CHEMIN_MODELES . '/reservation.php';
require_once CHEMIN_MODELES . '/paiement.php';
require_once CHEMIN_INCLUDES . '/validation.php';
require_once CHEMIN_INCLUDES . '/email.php';

// Vérifier que l'utilisateur est un administrateur
if (!estAdmin()) {
    if (isset($_GET['action']) && $_GET['action'] === 'login') {
        actionLogin();
    } else {
        repondreJSON(['success' => false, 'message' => 'Accès non autorisé. Vous devez être administrateur pour accéder à cette section.']);
        exit;
    }
}

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'login':
        actionLogin();
        break;
    case 'recuperer_utilisateurs':
        actionRecupererUtilisateurs();
        break;
    case 'recuperer_utilisateur':
        actionRecupererUtilisateur();
        break;
    case 'modifier_utilisateur':
        actionModifierUtilisateur();
        break;
    case 'supprimer_utilisateur':
        actionSupprimerUtilisateur();
        break;
    case 'recuperer_logements':
        actionRecupererLogements();
        break;
    case 'recuperer_logement':
        actionRecupererLogement();
        break;
    case 'modifier_logement':
        actionModifierLogement();
        break;
    case 'supprimer_logement':
        actionSupprimerLogement();
        break;
    case 'recuperer_reservations':
        actionRecupererReservations();
        break;
    case 'recuperer_reservation':
        actionRecupererReservation();
        break;
    case 'modifier_reservation':
        actionModifierReservation();
        break;
    case 'supprimer_reservation':
        actionSupprimerReservation();
        break;
    case 'statistiques':
        actionStatistiques();
        break;
    default:
        repondreJSON(['success' => false, 'message' => 'Action non reconnue']);
}

/**
 * Gère la connexion administrateur
 */
function actionLogin() {
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
    
    // Vérifier si l'utilisateur est un administrateur
    if (!$utilisateur['est_admin']) {
        repondreJSON(['success' => false, 'message' => 'Vous n\'avez pas les droits d\'administrateur']);
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
        'redirection' => URL_SITE . '/admin'
    ]);
}

/**
 * Récupère la liste des utilisateurs
 */
function actionRecupererUtilisateurs() {
    // Paramètres de filtrage
    $filtres = [];
    
    // Filtrer par nom/prénom
    if (isset($_GET['recherche']) && !empty($_GET['recherche'])) {
        $filtres['recherche'] = nettoyer($_GET['recherche']);
    }
    
    // Filtrer par statut administrateur
    if (isset($_GET['est_admin'])) {
        $filtres['est_admin'] = $_GET['est_admin'] == '1';
    }
    
    // Filtrer par statut de vérification
    if (isset($_GET['est_verifie'])) {
        $filtres['est_verifie'] = $_GET['est_verifie'] == '1';
    }
    
    // Pagination
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
    
    if ($page < 1) {
        $page = 1;
    }
    
    if ($limite < 1 || $limite > 50) {
        $limite = 10;
    }
    
    $offset = ($page - 1) * $limite;
    
    // Récupérer les utilisateurs
    $utilisateurs = recupererUtilisateurs($filtres, $limite, $offset);
    
    // Récupérer le nombre total d'utilisateurs
    $total = compterUtilisateurs($filtres);
    
    // Calculer le nombre total de pages
    $totalPages = ceil($total / $limite);
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'utilisateurs' => $utilisateurs,
        'total' => $total,
        'page' => $page,
        'limite' => $limite,
        'total_pages' => $totalPages
    ]);
}

/**
 * Récupère les informations d'un utilisateur
 */
function actionRecupererUtilisateur() {
    // Récupérer l'ID de l'utilisateur
    $idUtilisateur = isset($_GET['id_utilisateur']) ? intval($_GET['id_utilisateur']) : 0;
    
    if ($idUtilisateur <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID d\'utilisateur invalide']);
        return;
    }
    
    // Récupérer l'utilisateur
    $utilisateur = recupererUtilisateurParId($idUtilisateur);
    
    if (!$utilisateur) {
        repondreJSON(['success' => false, 'message' => 'Utilisateur non trouvé']);
        return;
    }
    
    // Récupérer les logements de l'utilisateur
    $logements = recupererLogementsPropriete($idUtilisateur);
    
    // Récupérer les réservations de l'utilisateur (en tant que locataire)
    $reservationsLocataire = recupererReservationsParLocataire($idUtilisateur);
    
    // Récupérer les réservations pour les logements de l'utilisateur (en tant que propriétaire)
    $reservationsProprietaire = [];
    $idsLogements = array_column($logements, 'id');
    
    if (!empty($idsLogements)) {
        $reservationsProprietaire = recupererReservationsParLogements($idsLogements);
    }
    
    // Calculer le bilan financier
    $bilanFinancier = [
        'depenses' => 0,
        'revenus' => 0,
        'solde' => 0
    ];
    
    // Dépenses (réservations en tant que locataire avec paiement complété)
    foreach ($reservationsLocataire as $reservation) {
        $paiement = recupererPaiementParReservation($reservation['id']);
        
        if ($paiement && $paiement['statut'] === 'complete') {
            $bilanFinancier['depenses'] += $paiement['montant'];
        }
    }
    
    // Revenus (réservations en tant que propriétaire avec paiement complété)
    foreach ($reservationsProprietaire as $reservation) {
        $paiement = recupererPaiementParReservation($reservation['id']);
        
        if ($paiement && $paiement['statut'] === 'complete') {
            // On retire les frais de service de la plateforme
            $fraisService = calculerFraisService($reservation['prix_total']);
            $bilanFinancier['revenus'] += ($reservation['prix_total'] - $fraisService);
        }
    }
    
    $bilanFinancier['solde'] = $bilanFinancier['revenus'] - $bilanFinancier['depenses'];
    
    // Préparer les données à renvoyer
    $donnees = [
        'utilisateur' => $utilisateur,
        'logements' => $logements,
        'reservations_locataire' => $reservationsLocataire,
        'reservations_proprietaire' => $reservationsProprietaire,
        'bilan_financier' => $bilanFinancier
    ];
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'data' => $donnees]);
}

/**
 * Modifie les informations d'un utilisateur
 */
function actionModifierUtilisateur() {
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID de l'utilisateur
    $idUtilisateur = isset($_POST['id_utilisateur']) ? intval($_POST['id_utilisateur']) : 0;
    
    if ($idUtilisateur <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID d\'utilisateur invalide']);
        return;
    }
    
    // Récupérer l'utilisateur
    $utilisateur = recupererUtilisateurParId($idUtilisateur);
    
    if (!$utilisateur) {
        repondreJSON(['success' => false, 'message' => 'Utilisateur non trouvé']);
        return;
    }
    
    // Récupérer et nettoyer les données du formulaire
    $nom = isset($_POST['nom']) ? nettoyer($_POST['nom']) : $utilisateur['nom'];
    $prenom = isset($_POST['prenom']) ? nettoyer($_POST['prenom']) : $utilisateur['prenom'];
    $email = isset($_POST['email']) ? nettoyer($_POST['email']) : $utilisateur['email'];
    $telephone = isset($_POST['telephone']) ? nettoyer($_POST['telephone']) : $utilisateur['telephone'];
    $estAdmin = isset($_POST['est_admin']) ? ($_POST['est_admin'] == '1') : $utilisateur['est_admin'];
    $estVerifie = isset($_POST['est_verifie']) ? ($_POST['est_verifie'] == '1') : $utilisateur['est_verifie'];
    
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
        // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
        $autreUtilisateur = recupererUtilisateurParEmail($email);
        if ($autreUtilisateur && $autreUtilisateur['id'] != $idUtilisateur) {
            $erreurs[] = 'Cette adresse email est déjà utilisée';
        }
    }
    
    if (!empty($telephone) && !preg_match('/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/', $telephone)) {
        $erreurs[] = 'Format de téléphone invalide';
    }
    
    // Si des erreurs sont présentes, renvoyer une réponse d'erreur
    if (!empty($erreurs)) {
        repondreJSON(['success' => false, 'erreurs' => $erreurs]);
        return;
    }
    
    // Vérifier si l'email a changé
    $emailChange = $email !== $utilisateur['email'];
    
    // Si l'email a changé, générer un nouveau token de vérification
    $tokenVerification = null;
    if ($emailChange && !$estVerifie) {
        $tokenVerification = bin2hex(random_bytes(32));
    }
    
    // Mettre à jour les informations
    $donnees = [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone,
        'est_admin' => $estAdmin,
        'est_verifie' => $estVerifie
    ];
    
    if ($emailChange && !$estVerifie) {
        $donnees['token_verification'] = $tokenVerification;
    }
    
    $resultat = modifierProfil($idUtilisateur, $donnees);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la modification de l\'utilisateur']);
        return;
    }
    
    // Si l'email a changé et n'est pas vérifié, envoyer un email de vérification
    if ($emailChange && !$estVerifie && $tokenVerification) {
        $sujet = 'Vérification de votre nouvelle adresse email OmnesBnB';
        $lienVerification = URL_SITE . '/verifier-email.php?token=' . $tokenVerification;
        
        $message = "Bonjour $prenom $nom,\n\n";
        $message .= "Un administrateur a modifié votre adresse email sur OmnesBnB. Pour confirmer votre nouvelle adresse, veuillez cliquer sur le lien suivant :\n\n";
        $message .= "$lienVerification\n\n";
        $message .= "Si vous n'êtes pas à l'origine de cette modification, veuillez contacter le support immédiatement.\n\n";
        $message .= "Cordialement,\nL'équipe OmnesBnB";
        
        envoyerEmail($email, $sujet, $message);
    }
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Utilisateur modifié avec succès']);
}

/**
 * Supprime un utilisateur
 */
function actionSupprimerUtilisateur() {
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID de l'utilisateur
    $idUtilisateur = isset($_POST['id_utilisateur']) ? intval($_POST['id_utilisateur']) : 0;
    
    if ($idUtilisateur <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID d\'utilisateur invalide']);
        return;
    }
    
    // Récupérer l'utilisateur
    $utilisateur = recupererUtilisateurParId($idUtilisateur);
    
    if (!$utilisateur) {
        repondreJSON(['success' => false, 'message' => 'Utilisateur non trouvé']);
        return;
    }
    
    // Vérifier que l'utilisateur n'est pas l'administrateur actuel
    if ($idUtilisateur == $_SESSION['utilisateur_id']) {
        repondreJSON(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
        return;
    }
    
    // Vérifier si l'utilisateur a des réservations en cours
    if (utilisateurAReservationsEnCours($idUtilisateur)) {
        repondreJSON(['success' => false, 'message' => 'Impossible de supprimer cet utilisateur car il a des réservations en cours']);
        return;
    }
    
    // Récupérer les logements de l'utilisateur
    $logements = recupererLogementsPropriete($idUtilisateur);
    
    // Vérifier si un des logements a des réservations en cours
    foreach ($logements as $logement) {
        if (logementAReservationsEnCours($logement['id'])) {
            repondreJSON(['success' => false, 'message' => 'Impossible de supprimer cet utilisateur car un de ses logements a des réservations en cours']);
            return;
        }
    }
    
    // Supprimer les photos de profil
    if ($utilisateur['photo_profil'] && file_exists(CHEMIN_RACINE . $utilisateur['photo_profil'])) {
        unlink(CHEMIN_RACINE . $utilisateur['photo_profil']);
    }
    
    // Supprimer les photos des logements
    foreach ($logements as $logement) {
        $photos = recupererPhotosLogement($logement['id']);
        
        foreach ($photos as $photo) {
            if (file_exists(CHEMIN_RACINE . $photo['url'])) {
                unlink(CHEMIN_RACINE . $photo['url']);
            }
        }
    }
    
    // Supprimer l'utilisateur
    $resultat = supprimerUtilisateur($idUtilisateur);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la suppression de l\'utilisateur']);
        return;
    }
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
}

/**
 * Récupère la liste des logements
 */
function actionRecupererLogements() {
    // Paramètres de filtrage
    $filtres = [];
    
    // Filtrer par titre/description
    if (isset($_GET['recherche']) && !empty($_GET['recherche'])) {
        $filtres['recherche'] = nettoyer($_GET['recherche']);
    }
    
    // Filtrer par ville
    if (isset($_GET['ville']) && !empty($_GET['ville'])) {
        $filtres['ville'] = nettoyer($_GET['ville']);
    }
    
    // Filtrer par type de logement
    if (isset($_GET['type_logement']) && !empty($_GET['type_logement'])) {
        $filtres['type_logement'] = nettoyer($_GET['type_logement']);
    }
    
    // Filtrer par propriétaire
    if (isset($_GET['id_proprietaire']) && intval($_GET['id_proprietaire']) > 0) {
        $filtres['id_proprietaire'] = intval($_GET['id_proprietaire']);
    }
    
    // Pagination
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
    
    if ($page < 1) {
        $page = 1;
    }
    
    if ($limite < 1 || $limite > 50) {
        $limite = 10;
    }
    
    $offset = ($page - 1) * $limite;
    
    // Récupérer les logements
    $logements = recupererLogements($filtres, 'date_creation_desc', $limite, $offset);
    
    // Ajouter la photo principale pour chaque logement
    foreach ($logements as &$logement) {
        $photos = recupererPhotosLogement($logement['id']);
        $logement['photo_principale'] = !empty($photos) ? $photos[0]['url'] : null;
        
        // Récupérer le nom du propriétaire
        $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
        $logement['proprietaire'] = $proprietaire ? $proprietaire['prenom'] . ' ' . $proprietaire['nom'] : 'Inconnu';
    }
    
    // Récupérer le nombre total de logements
    $total = compterLogements($filtres);
    
    // Calculer le nombre total de pages
    $totalPages = ceil($total / $limite);
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'logements' => $logements,
        'total' => $total,
        'page' => $page,
        'limite' => $limite,
        'total_pages' => $totalPages
    ]);
}

/**
 * Récupère les informations d'un logement
 */
function actionRecupererLogement() {
    // Récupérer l'ID du logement
    $idLogement = isset($_GET['id_logement']) ? intval($_GET['id_logement']) : 0;
    
    if ($idLogement <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID de logement invalide']);
        return;
    }
    
    // Récupérer le logement
    $logement = recupererLogementParId($idLogement);
    
    if (!$logement) {
        repondreJSON(['success' => false, 'message' => 'Logement non trouvé']);
        return;
    }
    
    // Récupérer les disponibilités du logement
    $disponibilites = recupererDisponibilitesLogement($idLogement);
    
    // Récupérer les photos du logement
    $photos = recupererPhotosLogement($idLogement);
    
    // Récupérer les réservations du logement
    $reservations = recupererReservationsParLogement($idLogement);
    
    // Ajouter les informations des locataires pour chaque réservation
    foreach ($reservations as &$reservation) {
        $locataire = recupererUtilisateurParId($reservation['id_locataire']);
        
        $reservation['locataire'] = [
            'id' => $locataire['id'],
            'nom' => $locataire['nom'],
            'prenom' => $locataire['prenom'],
            'email' => $locataire['email'],
            'telephone' => $locataire['telephone'],
            'photo_profil' => $locataire['photo_profil']
        ];
    }
    
    // Récupérer les informations du propriétaire
    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
    
    // Préparer les données à renvoyer
    $donnees = [
        'logement' => $logement,
        'disponibilites' => $disponibilites,
        'photos' => $photos,
        'reservations' => $reservations,
        'proprietaire' => [
            'id' => $proprietaire['id'],
            'nom' => $proprietaire['nom'],
            'prenom' => $proprietaire['prenom'],
            'email' => $proprietaire['email'],
            'telephone' => $proprietaire['telephone'],
            'photo_profil' => $proprietaire['photo_profil']
        ]
    ];
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'data' => $donnees]);
}

/**
 * Modifie les informations d'un logement
 */
function actionModifierLogement() {
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID du logement
    $idLogement = isset($_POST['id_logement']) ? intval($_POST['id_logement']) : 0;
    
    if ($idLogement <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID de logement invalide']);
        return;
    }
    
    // Récupérer le logement
    $logement = recupererLogementParId($idLogement);
    
    if (!$logement) {
        repondreJSON(['success' => false, 'message' => 'Logement non trouvé']);
        return;
    }
    
    // Récupérer et nettoyer les données du formulaire
    $titre = isset($_POST['titre']) ? nettoyer($_POST['titre']) : $logement['titre'];
    $description = isset($_POST['description']) ? nettoyer($_POST['description']) : $logement['description'];
    $adresse = isset($_POST['adresse']) ? nettoyer($_POST['adresse']) : $logement['adresse'];
    $ville = isset($_POST['ville']) ? nettoyer($_POST['ville']) : $logement['ville'];
    $codePostal = isset($_POST['code_postal']) ? nettoyer($_POST['code_postal']) : $logement['code_postal'];
    $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : $logement['prix'];
    $typeLogement = isset($_POST['type_logement']) ? nettoyer($_POST['type_logement']) : $logement['type_logement'];
    $nbPlaces = isset($_POST['nb_places']) ? intval($_POST['nb_places']) : $logement['nb_places'];
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : $logement['latitude'];
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : $logement['longitude'];
    
    // Valider les données
    $erreurs = [];
    
    if (empty($titre)) {
        $erreurs[] = 'Le titre est requis';
    }
    
    if (empty($description)) {
        $erreurs[] = 'La description est requise';
    }
    
    if (empty($adresse)) {
        $erreurs[] = 'L\'adresse est requise';
    }
    
    if (empty($ville)) {
        $erreurs[] = 'La ville est requise';
    }
    
    if (empty($codePostal)) {
        $erreurs[] = 'Le code postal est requis';
    } elseif (!preg_match('/^[0-9]{5}$/', $codePostal)) {
        $erreurs[] = 'Format de code postal invalide';
    }
    
    if ($prix <= 0) {
        $erreurs[] = 'Le prix doit être supérieur à 0';
    }
    
    if (empty($typeLogement)) {
        $erreurs[] = 'Le type de logement est requis';
    } elseif (!in_array($typeLogement, ['entier', 'collocation', 'libere'])) {
        $erreurs[] = 'Type de logement invalide';
    }
    
    if ($nbPlaces <= 0) {
        $erreurs[] = 'Le nombre de places doit être supérieur à 0';
    }
    
    // Si des erreurs sont présentes, renvoyer une réponse d'erreur
    if (!empty($erreurs)) {
        repondreJSON(['success' => false, 'erreurs' => $erreurs]);
        return;
    }
    
    // Vérifier si l'adresse a été modifiée
    $adresseModifiee = $adresse !== $logement['adresse'] || 
                      $ville !== $logement['ville'] || 
                      $codePostal !== $logement['code_postal'];
    
    // Si l'adresse a été modifiée, mettre à jour les coordonnées
    if ($adresseModifiee) {
        $adresseComplete = $adresse . ', ' . $codePostal . ' ' . $ville . ', France';
        $coordonnees = geocoderAdresse($adresseComplete);
        
        if ($coordonnees) {
            $latitude = $coordonnees['lat'];
            $longitude = $coordonnees['lng'];
        } else {
            repondreJSON(['success' => false, 'message' => 'Impossible de géocoder l\'adresse']);
            return;
        }
    }
    
    // Mettre à jour le logement
    $donnees = [
        'titre' => $titre,
        'description' => $description,
        'adresse' => $adresse,
        'ville' => $ville,
        'code_postal' => $codePostal,
        'prix' => $prix,
        'type_logement' => $typeLogement,
        'nb_places' => $nbPlaces,
        'latitude' => $latitude,
        'longitude' => $longitude
    ];
    
    $resultat = modifierLogement($idLogement, $donnees);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la modification du logement']);
        return;
    }
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Logement modifié avec succès']);
}

/**
 * Supprime un logement
 */
function actionSupprimerLogement() {
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID du logement
    $idLogement = isset($_POST['id_logement']) ? intval($_POST['id_logement']) : 0;
    
    if ($idLogement <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID de logement invalide']);
        return;
    }
    
    // Récupérer le logement
    $logement = recupererLogementParId($idLogement);
    
    if (!$logement) {
        repondreJSON(['success' => false, 'message' => 'Logement non trouvé']);
        return;
    }
    
    // Vérifier si le logement a des réservations en cours
    if (logementAReservationsEnCours($idLogement)) {
        repondreJSON(['success' => false, 'message' => 'Impossible de supprimer ce logement car il a des réservations en cours']);
        return;
    }
    
    // Récupérer toutes les photos du logement
    $photos = recupererPhotosLogement($idLogement);
    
    // Supprimer le logement
    $resultat = supprimerLogement($idLogement);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la suppression du logement']);
        return;
    }
    
    // Supprimer les fichiers photos
    foreach ($photos as $photo) {
        if (file_exists(CHEMIN_RACINE . $photo['url'])) {
            unlink(CHEMIN_RACINE . $photo['url']);
        }
    }
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Logement supprimé avec succès']);
}

/**
 * Récupère la liste des réservations
 */
function actionRecupererReservations() {
    // Paramètres de filtrage
    $filtres = [];
    
    // Filtrer par statut
    if (isset($_GET['statut']) && !empty($_GET['statut'])) {
        $filtres['statut'] = nettoyer($_GET['statut']);
    }
    
    // Filtrer par logement
    if (isset($_GET['id_logement']) && intval($_GET['id_logement']) > 0) {
        $filtres['id_logement'] = intval($_GET['id_logement']);
    }
    
    // Filtrer par locataire
    if (isset($_GET['id_locataire']) && intval($_GET['id_locataire']) > 0) {
        $filtres['id_locataire'] = intval($_GET['id_locataire']);
    }
    
    // Pagination
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
    
    if ($page < 1) {
        $page = 1;
    }
    
    if ($limite < 1 || $limite > 50) {
        $limite = 10;
    }
    
    $offset = ($page - 1) * $limite;
    
    // Récupérer les réservations
    $reservations = recupererReservations($filtres, $limite, $offset);
    
    // Enrichir les données des réservations
    foreach ($reservations as &$reservation) {
        // Récupérer le logement
        $logement = recupererLogementParId($reservation['id_logement']);
        $reservation['logement'] = $logement ? [
            'id' => $logement['id'],
            'titre' => $logement['titre'],
            'ville' => $logement['ville'],
            'type_logement' => $logement['type_logement']
        ] : null;
        
        // Récupérer le locataire
        $locataire = recupererUtilisateurParId($reservation['id_locataire']);
        $reservation['locataire'] = $locataire ? [
            'id' => $locataire['id'],
            'nom' => $locataire['nom'],
            'prenom' => $locataire['prenom'],
            'email' => $locataire['email']
        ] : null;
        
        // Récupérer le propriétaire
        if ($logement) {
            $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
            $reservation['proprietaire'] = $proprietaire ? [
                'id' => $proprietaire['id'],
                'nom' => $proprietaire['nom'],
                'prenom' => $proprietaire['prenom'],
                'email' => $proprietaire['email']
            ] : null;
        } else {
            $reservation['proprietaire'] = null;
        }
        
        // Récupérer le paiement
        $paiement = recupererPaiementParReservation($reservation['id']);
        $reservation['paiement'] = $paiement ?: null;
    }
    
    // Récupérer le nombre total de réservations
    $total = compterReservations($filtres);
    
    // Calculer le nombre total de pages
    $totalPages = ceil($total / $limite);
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'reservations' => $reservations,
        'total' => $total,
        'page' => $page,
        'limite' => $limite,
        'total_pages' => $totalPages
    ]);
}

/**
 * Récupère les informations d'une réservation
 */
function actionRecupererReservation() {
    // Récupérer l'ID de la réservation
    $idReservation = isset($_GET['id_reservation']) ? intval($_GET['id_reservation']) : 0;
    
    if ($idReservation <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID de réservation invalide']);
        return;
    }
    
    // Récupérer la réservation
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        repondreJSON(['success' => false, 'message' => 'Réservation non trouvée']);
        return;
    }
    
    // Récupérer le logement
    $logement = recupererLogementParId($reservation['id_logement']);
    
    // Récupérer le locataire
    $locataire = recupererUtilisateurParId($reservation['id_locataire']);
    
    // Récupérer le propriétaire
    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
    
    // Récupérer le paiement
    $paiement = recupererPaiementParReservation($idReservation);
    
    // Préparer les données à renvoyer
    $donnees = [
        'reservation' => $reservation,
        'logement' => $logement,
        'locataire' => $locataire,
        'proprietaire' => $proprietaire,
        'paiement' => $paiement ?: null
    ];
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'data' => $donnees]);
}

/**
 * Modifie les informations d'une réservation
 */
function actionModifierReservation() {
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID de la réservation
    $idReservation = isset($_POST['id_reservation']) ? intval($_POST['id_reservation']) : 0;
    
    if ($idReservation <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID de réservation invalide']);
        return;
    }
    
    // Récupérer la réservation
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        repondreJSON(['success' => false, 'message' => 'Réservation non trouvée']);
        return;
    }
    
    // Récupérer le statut
    $statut = isset($_POST['statut']) ? nettoyer($_POST['statut']) : $reservation['statut'];
    
    // Valider le statut
    if (!in_array($statut, ['en_attente', 'acceptee', 'refusee', 'annulee', 'terminee'])) {
        repondreJSON(['success' => false, 'message' => 'Statut invalide']);
        return;
    }
    
    // Mettre à jour la réservation
    $resultat = modifierStatutReservation($idReservation, $statut);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la modification de la réservation']);
        return;
    }
    
    // Actions supplémentaires selon le nouveau statut
    $logement = recupererLogementParId($reservation['id_logement']);
    $locataire = recupererUtilisateurParId($reservation['id_locataire']);
    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
    
    if ($statut === 'acceptee' && $reservation['statut'] !== 'acceptee') {
        // La réservation vient d'être acceptée
        
        // Notification au locataire
        $sujet = 'Confirmation de réservation - ' . $logement['titre'];
        $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
        $message .= "Votre réservation pour le logement \"{$logement['titre']}\" a été confirmée par un administrateur.\n";
        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
        $message .= "Vous pouvez contacter le propriétaire au {$proprietaire['telephone']}.\n\n";
        $message .= "Cordialement,\nL'équipe OmnesBnB";
        
        envoyerEmail($locataire['email'], $sujet, $message);
        
        // Notification au propriétaire
        $sujet = 'Réservation confirmée par administrateur - ' . $logement['titre'];
        $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
        $message .= "Une réservation pour votre logement \"{$logement['titre']}\" a été confirmée par un administrateur.\n";
        $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n";
        $message .= "Montant: {$reservation['prix_total']} €\n\n";
        $message .= "Vous pouvez contacter le locataire au {$locataire['telephone']}.\n\n";
        $message .= "Cordialement,\nL'équipe OmnesBnB";
        
        envoyerEmail($proprietaire['email'], $sujet, $message);
    } elseif ($statut === 'refusee' && $reservation['statut'] !== 'refusee') {
        // La réservation vient d'être refusée
        
        // Notification au locataire
        $sujet = 'Réservation refusée - ' . $logement['titre'];
        $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
        $message .= "Nous sommes désolés de vous informer que votre réservation pour le logement \"{$logement['titre']}\" a été refusée par un administrateur.\n";
        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
        $message .= "Si un paiement a été effectué, un remboursement complet sera traité.\n\n";
        $message .= "Cordialement,\nL'équipe OmnesBnB";
        
        envoyerEmail($locataire['email'], $sujet, $message);
        
        // Notification au propriétaire
        $sujet = 'Réservation refusée par administrateur - ' . $logement['titre'];
        $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
        $message .= "Une réservation pour votre logement \"{$logement['titre']}\" a été refusée par un administrateur.\n";
        $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
        $message .= "Cordialement,\nL'équipe OmnesBnB";
        
        envoyerEmail($proprietaire['email'], $sujet, $message);
        
        // Rembourser le paiement si nécessaire
        $paiement = recupererPaiementParReservation($idReservation);
        
        if ($paiement && $paiement['statut'] === 'complete') {
            // TODO: Implémenter le remboursement Stripe
            rembourserPaiement($paiement['id']);
        }
    } elseif ($statut === 'annulee' && $reservation['statut'] !== 'annulee') {
        // La réservation vient d'être annulée
        
        // Notification au locataire
        $sujet = 'Réservation annulée - ' . $logement['titre'];
        $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
        $message .= "Nous vous informons que votre réservation pour le logement \"{$logement['titre']}\" a été annulée par un administrateur.\n";
        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
        $message .= "Si un paiement a été effectué, un remboursement complet sera traité.\n\n";
        $message .= "Cordialement,\nL'équipe OmnesBnB";
        
        envoyerEmail($locataire['email'], $sujet, $message);
        
        // Notification au propriétaire
        $sujet = 'Réservation annulée par administrateur - ' . $logement['titre'];
        $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
        $message .= "Une réservation pour votre logement \"{$logement['titre']}\" a été annulée par un administrateur.\n";
        $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
        $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
        $message .= "Cordialement,\nL'équipe OmnesBnB";
        
        envoyerEmail($proprietaire['email'], $sujet, $message);
        
        // Rembourser le paiement si nécessaire
        $paiement = recupererPaiementParReservation($idReservation);
        
        if ($paiement && $paiement['statut'] === 'complete') {
            // TODO: Implémenter le remboursement Stripe
            rembourserPaiement($paiement['id']);
        }
    }
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Réservation modifiée avec succès']);
}

/**
 * Supprime une réservation
 */
function actionSupprimerReservation() {
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID de la réservation
    $idReservation = isset($_POST['id_reservation']) ? intval($_POST['id_reservation']) : 0;
    
    if ($idReservation <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID de réservation invalide']);
        return;
    }
    
    // Récupérer la réservation
    $reservation = recupererReservationParId($idReservation);
    
    if (!$reservation) {
        repondreJSON(['success' => false, 'message' => 'Réservation non trouvée']);
        return;
    }
    
    // Récupérer le logement, le locataire et le propriétaire
    $logement = recupererLogementParId($reservation['id_logement']);
    $locataire = recupererUtilisateurParId($reservation['id_locataire']);
    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
    
    // Vérifier si la réservation est en cours
    $maintenant = time();
    $dateDebut = strtotime($reservation['date_debut']);
    $dateFin = strtotime($reservation['date_fin']);
    
    if ($dateDebut <= $maintenant && $dateFin >= $maintenant) {
        repondreJSON(['success' => false, 'message' => 'Impossible de supprimer une réservation en cours']);
        return;
    }
    
    // Vérifier si un paiement est associé
    $paiement = recupererPaiementParReservation($idReservation);
    
    if ($paiement && $paiement['statut'] === 'complete') {
        // Si le paiement est complété et que la réservation n'est pas terminée, remboursement nécessaire
        if ($reservation['statut'] !== 'terminee') {
            rembourserPaiement($paiement['id']);
            
            // Notifier le locataire du remboursement
            $sujet = 'Remboursement suite à suppression de réservation - ' . $logement['titre'];
            $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
            $message .= "Nous vous informons que votre réservation pour le logement \"{$logement['titre']}\" a été supprimée par un administrateur.\n";
            $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
            $message .= "Un remboursement complet a été initié et sera crédité sur votre compte bancaire dans les prochains jours.\n\n";
            $message .= "Cordialement,\nL'équipe OmnesBnB";
            
            envoyerEmail($locataire['email'], $sujet, $message);
        }
    }
    
    // Supprimer la réservation
    $resultat = supprimerReservation($idReservation);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la suppression de la réservation']);
        return;
    }
    
    // Notifier les parties concernées
    // Notification au locataire
    $sujet = 'Suppression de réservation - ' . $logement['titre'];
    $message = "Bonjour {$locataire['prenom']} {$locataire['nom']},\n\n";
    $message .= "Nous vous informons que votre réservation pour le logement \"{$logement['titre']}\" a été supprimée par un administrateur.\n";
    $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    envoyerEmail($locataire['email'], $sujet, $message);
    
    // Notification au propriétaire
    $sujet = 'Suppression de réservation - ' . $logement['titre'];
    $message = "Bonjour {$proprietaire['prenom']} {$proprietaire['nom']},\n\n";
    $message .= "Nous vous informons qu'une réservation pour votre logement \"{$logement['titre']}\" a été supprimée par un administrateur.\n";
    $message .= "Locataire: {$locataire['prenom']} {$locataire['nom']}\n";
    $message .= "Dates: " . formatDate($reservation['date_debut']) . " au " . formatDate($reservation['date_fin']) . "\n\n";
    $message .= "Cordialement,\nL'équipe OmnesBnB";
    
    envoyerEmail($proprietaire['email'], $sujet, $message);
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Réservation supprimée avec succès']);
}

/**
 * Récupère les statistiques pour le tableau de bord
 */
function actionStatistiques() {
    // Statistiques utilisateurs
    $nbUtilisateurs = compterUtilisateurs();
    $nbAdmins = compterUtilisateurs(['est_admin' => true]);
    $nbUtilisateursVerifies = compterUtilisateurs(['est_verifie' => true]);
    $nbUtilisateursNonVerifies = $nbUtilisateurs - $nbUtilisateursVerifies;
    
    // Statistiques logements
    $nbLogements = compterLogements();
    $nbLogementsEntiers = compterLogements(['type_logement' => 'entier']);
    $nbCollocation = compterLogements(['type_logement' => 'collocation']);
    $nbLogementLibere = compterLogements(['type_logement' => 'libere']);
    
    // Statistiques réservations
    $nbReservations = compterReservations();
    $nbReservationsEnAttente = compterReservations(['statut' => 'en_attente']);
    $nbReservationsAcceptees = compterReservations(['statut' => 'acceptee']);
    $nbReservationsRefusees = compterReservations(['statut' => 'refusee']);
    $nbReservationsAnnulees = compterReservations(['statut' => 'annulee']);
    $nbReservationsTerminees = compterReservations(['statut' => 'terminee']);
    
    // Statistiques paiements
    $montantTotalPaiements = calculerMontantTotalPaiements();
    $montantFraisService = calculerMontantTotalFraisService();
    
    // Villes les plus populaires
    $villesPopulaires = recupererVillesPopulaires(5);
    
    // Utilisateurs les plus actifs
    $bailleursActifs = recupererBailleursActifs(5);
    $locatairesActifs = recupererLocatairesActifs(5);
    
    // Préparer les données à renvoyer
    $donnees = [
        'utilisateurs' => [
            'total' => $nbUtilisateurs,
            'admins' => $nbAdmins,
            'verifies' => $nbUtilisateursVerifies,
            'non_verifies' => $nbUtilisateursNonVerifies
        ],
        'logements' => [
            'total' => $nbLogements,
            'entiers' => $nbLogementsEntiers,
            'collocations' => $nbCollocation,
            'liberes' => $nbLogementLibere
        ],
        'reservations' => [
            'total' => $nbReservations,
            'en_attente' => $nbReservationsEnAttente,
            'acceptees' => $nbReservationsAcceptees,
            'refusees' => $nbReservationsRefusees,
            'annulees' => $nbReservationsAnnulees,
            'terminees' => $nbReservationsTerminees
        ],
        'paiements' => [
            'montant_total' => $montantTotalPaiements,
            'frais_service' => $montantFraisService,
            'revenus_net' => $montantTotalPaiements - $montantFraisService
        ],
        'villes_populaires' => $villesPopulaires,
        'bailleurs_actifs' => $bailleursActifs,
        'locataires_actifs' => $locatairesActifs
    ];
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'statistiques' => $donnees]);
}

/**
 * Formate une date pour l'affichage
 * 
 * @param string $date Date au format Y-m-d
 * @return string Date au format d/m/Y
 */
function formatDate($date) {
    return date("d/m/Y", strtotime($date));
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
?>
