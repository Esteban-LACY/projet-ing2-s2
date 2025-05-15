<?php
/**
 * Contrôleur pour la gestion des logements
 * 
 * Ce fichier gère les actions liées aux logements (publication, modification, suppression, etc.)
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_MODELES . '/logement.php';
require_once CHEMIN_MODELES . '/disponibilite.php';
require_once CHEMIN_INCLUDES . '/validation.php';

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'publier':
        actionPublierLogement();
        break;
    case 'modifier':
        actionModifierLogement();
        break;
    case 'supprimer':
        actionSupprimerLogement();
        break;
    case 'recuperer':
        actionRecupererLogement();
        break;
    case 'recuperer_tous':
        actionRecupererTousLogements();
        break;
    case 'upload_photo':
        actionUploadPhoto();
        break;
    case 'supprimer_photo':
        actionSupprimerPhoto();
        break;
    default:
        // Si aucune action n'est spécifiée, rediriger vers la page d'accueil
        rediriger(URL_SITE);
}

/**
 * Gère la publication d'un nouveau logement
 */
function actionPublierLogement() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour publier un logement']);
        return;
    }
    
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer et nettoyer les données du formulaire
    $titre = isset($_POST['titre']) ? nettoyer($_POST['titre']) : '';
    $description = isset($_POST['description']) ? nettoyer($_POST['description']) : '';
    $adresse = isset($_POST['adresse']) ? nettoyer($_POST['adresse']) : '';
    $ville = isset($_POST['ville']) ? nettoyer($_POST['ville']) : '';
    $codePostal = isset($_POST['code_postal']) ? nettoyer($_POST['code_postal']) : '';
    $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
    $typeLogement = isset($_POST['type_logement']) ? nettoyer($_POST['type_logement']) : '';
    $nbPlaces = isset($_POST['nb_places']) ? intval($_POST['nb_places']) : 1;
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $dateDebut = isset($_POST['date_debut']) ? nettoyer($_POST['date_debut']) : '';
    $dateFin = isset($_POST['date_fin']) ? nettoyer($_POST['date_fin']) : '';
    
    // Récupérer l'ID de l'utilisateur connecté
    $idProprietaire = $_SESSION['utilisateur_id'];
    
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
    
    if (empty($dateDebut)) {
        $erreurs[] = 'La date de début est requise';
    } elseif (!validateDate($dateDebut)) {
        $erreurs[] = 'Format de date de début invalide';
    }
    
    if (empty($dateFin)) {
        $erreurs[] = 'La date de fin est requise';
    } elseif (!validateDate($dateFin)) {
        $erreurs[] = 'Format de date de fin invalide';
    }
    
    if (validateDate($dateDebut) && validateDate($dateFin) && strtotime($dateDebut) >= strtotime($dateFin)) {
        $erreurs[] = 'La date de fin doit être postérieure à la date de début';
    }
    
    // Si des erreurs sont présentes, renvoyer une réponse d'erreur
    if (!empty($erreurs)) {
        repondreJSON(['success' => false, 'erreurs' => $erreurs]);
        return;
    }
    
    // Si latitude et longitude ne sont pas fournies, utiliser l'API Google Maps pour les obtenir
    if ($latitude === null || $longitude === null) {
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
    
    // Créer le logement
    $donnees = [
        'id_proprietaire' => $idProprietaire,
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
    
    $idLogement = creerLogement($donnees);
    
    if (!$idLogement) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la création du logement']);
        return;
    }
    
    // Ajouter la disponibilité
    $disponibilite = [
        'id_logement' => $idLogement,
        'date_debut' => $dateDebut,
        'date_fin' => $dateFin
    ];
    
    $idDisponibilite = creerDisponibilite($disponibilite);
    
    if (!$idDisponibilite) {
        // Supprimer le logement en cas d'erreur
        supprimerLogement($idLogement);
        repondreJSON(['success' => false, 'message' => 'Erreur lors de l\'ajout de la disponibilité']);
        return;
    }
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'message' => 'Logement publié avec succès',
        'id_logement' => $idLogement
    ]);
}

/**
 * Gère la modification d'un logement
 */
function actionModifierLogement() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour modifier un logement']);
        return;
    }
    
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
    
    // Vérifier que l'utilisateur est le propriétaire du logement ou un administrateur
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    if ($logement['id_proprietaire'] != $idUtilisateur && !estAdmin()) {
        repondreJSON(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier ce logement']);
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
    repondreJSON([
        'success' => true,
        'message' => 'Logement modifié avec succès'
    ]);
}

/**
 * Gère la suppression d'un logement
 */
function actionSupprimerLogement() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour supprimer un logement']);
        return;
    }
    
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
    
    // Vérifier que l'utilisateur est le propriétaire du logement ou un administrateur
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    if ($logement['id_proprietaire'] != $idUtilisateur && !estAdmin()) {
        repondreJSON(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer ce logement']);
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
    
    // Récupérer les informations du propriétaire
    $proprietaire = recupererUtilisateurParId($logement['id_proprietaire']);
    
    // Préparer les données à renvoyer
    $donnees = [
        'logement' => $logement,
        'disponibilites' => $disponibilites,
        'photos' => $photos,
        'proprietaire' => [
            'id' => $proprietaire['id'],
            'nom' => $proprietaire['nom'],
            'prenom' => $proprietaire['prenom'],
            'photo_profil' => $proprietaire['photo_profil']
        ]
    ];
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'data' => $donnees]);
}

/**
 * Récupère tous les logements
 */
function actionRecupererTousLogements() {
    // Paramètres de filtrage
    $filtres = [];
    
    // Filtrer par ville
    if (isset($_GET['ville']) && !empty($_GET['ville'])) {
        $filtres['ville'] = nettoyer($_GET['ville']);
    }
    
    // Filtrer par type de logement
    if (isset($_GET['type_logement']) && !empty($_GET['type_logement'])) {
        $filtres['type_logement'] = nettoyer($_GET['type_logement']);
    }
    
    // Filtrer par prix minimum
    if (isset($_GET['prix_min']) && is_numeric($_GET['prix_min'])) {
        $filtres['prix_min'] = floatval($_GET['prix_min']);
    }
    
    // Filtrer par prix maximum
    if (isset($_GET['prix_max']) && is_numeric($_GET['prix_max'])) {
        $filtres['prix_max'] = floatval($_GET['prix_max']);
    }
    
    // Filtrer par nombre de places
    if (isset($_GET['nb_places']) && is_numeric($_GET['nb_places'])) {
        $filtres['nb_places'] = intval($_GET['nb_places']);
    }
    
    // Filtrer par disponibilité
    if (isset($_GET['date_debut']) && !empty($_GET['date_debut']) && isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
        $dateDebut = nettoyer($_GET['date_debut']);
        $dateFin = nettoyer($_GET['date_fin']);
        
        if (validateDate($dateDebut) && validateDate($dateFin) && strtotime($dateDebut) < strtotime($dateFin)) {
            $filtres['date_debut'] = $dateDebut;
            $filtres['date_fin'] = $dateFin;
        }
    }
    
    // Récupérer les logements
    $logements = recupererLogements($filtres);
    
    // Ajouter la photo principale pour chaque logement
    foreach ($logements as &$logement) {
        $photos = recupererPhotosLogement($logement['id']);
        $logement['photo_principale'] = !empty($photos) ? $photos[0]['url'] : null;
    }
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'logements' => $logements]);
}

/**
 * Gère l'upload d'une photo pour un logement
 */
function actionUploadPhoto() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour ajouter une photo']);
        return;
    }
    
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
    
    // Vérifier que l'utilisateur est le propriétaire du logement ou un administrateur
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    if ($logement['id_proprietaire'] != $idUtilisateur && !estAdmin()) {
        repondreJSON(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à ajouter une photo à ce logement']);
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
    
    // Créer le répertoire d'upload si nécessaire
    if (!file_exists(CHEMIN_UPLOADS_LOGEMENTS)) {
        mkdir(CHEMIN_UPLOADS_LOGEMENTS, 0755, true);
    }
    
    // Générer un nom unique pour le fichier
    $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
    $nomFichier = 'logement_' . $idLogement . '_' . time() . '.' . $extension;
    $cheminFichier = CHEMIN_UPLOADS_LOGEMENTS . '/' . $nomFichier;
    
    // Déplacer le fichier uploadé
    if (!move_uploaded_file($fichier['tmp_name'], $cheminFichier)) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors du déplacement du fichier']);
        return;
    }
    
    // Déterminer si c'est la photo principale
    $estPrincipale = isset($_POST['est_principale']) && $_POST['est_principale'] === 'true';
    
    // Ajouter la photo dans la base de données
    $cheminRelatif = '/uploads/logements/' . $nomFichier;
    $idPhoto = ajouterPhotoLogement($idLogement, $cheminRelatif, $estPrincipale);
    
    if (!$idPhoto) {
        // Supprimer le fichier en cas d'erreur
        unlink($cheminFichier);
        repondreJSON(['success' => false, 'message' => 'Erreur lors de l\'ajout de la photo']);
        return;
    }
    
    // Répondre avec succès
    repondreJSON([
        'success' => true,
        'message' => 'Photo ajoutée avec succès',
        'photo' => [
            'id' => $idPhoto,
            'url' => $cheminRelatif,
            'est_principale' => $estPrincipale
        ]
    ]);
}

/**
 * Gère la suppression d'une photo de logement
 */
function actionSupprimerPhoto() {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        repondreJSON(['success' => false, 'message' => 'Vous devez être connecté pour supprimer une photo']);
        return;
    }
    
    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        repondreJSON(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    // Récupérer l'ID de la photo
    $idPhoto = isset($_POST['id_photo']) ? intval($_POST['id_photo']) : 0;
    
    if ($idPhoto <= 0) {
        repondreJSON(['success' => false, 'message' => 'ID de photo invalide']);
        return;
    }
    
    // Récupérer la photo
    $photo = recupererPhotoParId($idPhoto);
    
    if (!$photo) {
        repondreJSON(['success' => false, 'message' => 'Photo non trouvée']);
        return;
    }
    
    // Récupérer le logement
    $logement = recupererLogementParId($photo['id_logement']);
    
    // Vérifier que l'utilisateur est le propriétaire du logement ou un administrateur
    $idUtilisateur = $_SESSION['utilisateur_id'];
    
    if ($logement['id_proprietaire'] != $idUtilisateur && !estAdmin()) {
        repondreJSON(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cette photo']);
        return;
    }
    
    // Supprimer la photo
    $resultat = supprimerPhoto($idPhoto);
    
    if (!$resultat) {
        repondreJSON(['success' => false, 'message' => 'Erreur lors de la suppression de la photo']);
        return;
    }
    
    // Supprimer le fichier
    if (file_exists(CHEMIN_RACINE . $photo['url'])) {
        unlink(CHEMIN_RACINE . $photo['url']);
    }
    
    // Si c'était la photo principale, définir une autre photo comme principale
    if ($photo['est_principale']) {
        $autresPhotos = recupererPhotosLogement($photo['id_logement']);
        
        if (!empty($autresPhotos)) {
            definirPhotoPrincipale($autresPhotos[0]['id']);
        }
    }
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'message' => 'Photo supprimée avec succès']);
}

/**
 * Vérifie si une date est valide
 * 
 * @param string $date Date à vérifier (format Y-m-d)
 * @return boolean True si la date est valide, false sinon
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
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
