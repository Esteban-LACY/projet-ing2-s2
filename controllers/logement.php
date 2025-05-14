<?php
/**
 * Contrôleur pour la gestion des logements
 */
require_once 'config/config.php';
require_once 'models/logement.php';
require_once 'includes/fonctions.php';
require_once 'includes/validation.php';

class LogementController {
    private $logementModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->logementModel = new LogementModel();
    }
    
    /**
     * Traite la publication d'un logement
     */
    public function publier() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            $_SESSION['url_apres_connexion'] = 'publier.php';
            afficherMessage('Vous devez être connecté pour publier un logement.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Vérification si l'utilisateur a vérifié son email
        if (EMAIL_VERIFICATION && !$_SESSION['utilisateur_est_verifie']) {
            afficherMessage('Vous devez vérifier votre email avant de publier un logement.', 'avertissement');
            rediriger('index.php');
            return;
        }
        
        // Vérification si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Récupération et nettoyage des données
        $titre = nettoyer($_POST['titre'] ?? '');
        $description = nettoyer($_POST['description'] ?? '');
        $adresse = nettoyer($_POST['adresse'] ?? '');
        $ville = nettoyer($_POST['ville'] ?? '');
        $codePostal = nettoyer($_POST['code_postal'] ?? '');
        $prix = floatval($_POST['prix'] ?? 0);
        $typeLogement = nettoyer($_POST['type_logement'] ?? '');
        $nbPlaces = intval($_POST['nb_places'] ?? 1);
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        
        // Récupération des dates de disponibilité
        $datesDebut = $_POST['dates_debut'] ?? [];
        $datesFin = $_POST['dates_fin'] ?? [];
        
        // Validation des données
        $erreurs = [];
        
        if (empty($titre)) {
            $erreurs['titre'] = 'Le titre est obligatoire.';
        }
        
        if (empty($description)) {
            $erreurs['description'] = 'La description est obligatoire.';
        }
        
        if (empty($adresse)) {
            $erreurs['adresse'] = 'L\'adresse est obligatoire.';
        }
        
        if (empty($ville)) {
            $erreurs['ville'] = 'La ville est obligatoire.';
        }
        
        if (empty($codePostal)) {
            $erreurs['code_postal'] = 'Le code postal est obligatoire.';
        } elseif (!estCodePostalValide($codePostal)) {
            $erreurs['code_postal'] = 'Le code postal n\'est pas valide.';
        }
        
        if (empty($prix) || $prix <= 0) {
            $erreurs['prix'] = 'Le prix doit être supérieur à 0.';
        }
        
        if (empty($typeLogement) || !in_array($typeLogement, ['entier', 'collocation', 'libere'])) {
            $erreurs['type_logement'] = 'Veuillez sélectionner un type de logement valide.';
        }
        
        if ($nbPlaces < 1) {
            $erreurs['nb_places'] = 'Le nombre de places doit être au moins 1.';
        }
        
        // Validation des dates de disponibilité
        $disponibilites = [];
        $erreursDates = false;
        
        for ($i = 0; $i < count($datesDebut); $i++) {
            if (empty($datesDebut[$i]) || empty($datesFin[$i])) {
                $erreursDates = true;
                break;
            }
            
            if (!estDateValide($datesDebut[$i]) || !estDateValide($datesFin[$i])) {
                $erreursDates = true;
                break;
            }
            
            if (!estPeriodeValide($datesDebut[$i], $datesFin[$i])) {
                $erreursDates = true;
                break;
            }
            
            $disponibilites[] = [
                'date_debut' => $datesDebut[$i],
                'date_fin' => $datesFin[$i]
            ];
        }
        
        if (empty($disponibilites) || $erreursDates) {
            $erreurs['dates'] = 'Veuillez spécifier au moins une période de disponibilité valide.';
        }
        
        // Vérification des coordonnées géographiques
        if ($latitude == 0 || $longitude == 0) {
            // Si les coordonnées ne sont pas définies, on essaie de les récupérer via l'API
            $coordonnees = geocodeAdresse($adresse . ', ' . $codePostal . ' ' . $ville);
            
            if ($coordonnees) {
                $latitude = $coordonnees['latitude'];
                $longitude = $coordonnees['longitude'];
            } else {
                $erreurs['adresse'] = 'Impossible de géolocaliser cette adresse.';
            }
        }
        
        // S'il y a des erreurs, on les stocke en session
        if (!empty($erreurs)) {
            $_SESSION['erreurs_publication'] = $erreurs;
            $_SESSION['donnees_publication'] = [
                'titre' => $titre,
                'description' => $description,
                'adresse' => $adresse,
                'ville' => $ville,
                'code_postal' => $codePostal,
                'prix' => $prix,
                'type_logement' => $typeLogement,
                'nb_places' => $nbPlaces,
                'disponibilites' => $disponibilites,
                'latitude' => $latitude,
                'longitude' => $longitude
            ];
            rediriger('publier.php');
            return;
        }
        
        // Création du logement
        $donneesLogement = [
            'id_proprietaire' => $_SESSION['utilisateur_id'],
            'titre' => $titre,
            'description' => $description,
            'adresse' => $adresse,
            'ville' => $ville,
            'code_postal' => $codePostal,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'prix' => $prix,
            'type_logement' => $typeLogement,
            'nb_places' => $nbPlaces,
            'disponibilites' => $disponibilites
        ];
        
        $idLogement = $this->logementModel->creer($donneesLogement);
        
        if (!$idLogement) {
            afficherMessage('Une erreur est survenue lors de la publication du logement.', 'erreur');
            rediriger('publier.php');
            return;
        }
        
        // Gestion des photos
        if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
            $photoPrincipale = isset($_POST['photo_principale']) ? intval($_POST['photo_principale']) : 0;
            
            foreach ($_FILES['photos']['name'] as $i => $nom) {
                if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                
                // Création d'un tableau pour le fichier courant
                $fichier = [
                    'name' => $_FILES['photos']['name'][$i],
                    'type' => $_FILES['photos']['type'][$i],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                    'error' => $_FILES['photos']['error'][$i],
                    'size' => $_FILES['photos']['size'][$i]
                ];
                
                $resultat = validerFichierImage($fichier);
                
                if (!$resultat['valide']) {
                    continue;
                }
                
                $photoNom = traiterUploadImage($fichier, 'logements');
                
                if ($photoNom !== false) {
                    $this->logementModel->ajouterPhoto($idLogement, $photoNom, $i === $photoPrincipale);
                }
            }
        }
        
        afficherMessage('Votre logement a été publié avec succès.', 'succes');
        rediriger('logement.php?id=' . $idLogement);
    }
    
    /**
     * Traite la modification d'un logement
     */
    public function modifier() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            afficherMessage('Vous devez être connecté pour modifier un logement.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Récupération de l'ID du logement
        $idLogement = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($idLogement <= 0) {
            afficherMessage('Logement introuvable.', 'erreur');
            rediriger('index.php');
            return;
        }
        
        // Récupération du logement
        $logement = $this->logementModel->recupererParId($idLogement);
        
        if (!$logement) {
            afficherMessage('Logement introuvable.', 'erreur');
            rediriger('index.php');
            return;
        }
        
        // Vérification des droits
        if ($logement['id_proprietaire'] != $_SESSION['utilisateur_id'] && !$_SESSION['utilisateur_est_admin']) {
            afficherMessage('Vous n\'êtes pas autorisé à modifier ce logement.', 'erreur');
            rediriger('index.php');
            return;
        }
        
        // Vérification si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Récupération et nettoyage des données (similaire à publier())
        $titre = nettoyer($_POST['titre'] ?? '');
        $description = nettoyer($_POST['description'] ?? '');
        $adresse = nettoyer($_POST['adresse'] ?? '');
        $ville = nettoyer($_POST['ville'] ?? '');
        $codePostal = nettoyer($_POST['code_postal'] ?? '');
        $prix = floatval($_POST['prix'] ?? 0);
        $typeLogement = nettoyer($_POST['type_logement'] ?? '');
        $nbPlaces = intval($_POST['nb_places'] ?? 1);
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        
        // Validation des données (similaire à publier())
        // ...
        
        // Mise à jour du logement
        $donneesLogement = [
            'titre' => $titre,
            'description' => $description,
            'adresse' => $adresse,
            'ville' => $ville,
            'code_postal' => $codePostal,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'prix' => $prix,
            'type_logement' => $typeLogement,
            'nb_places' => $nbPlaces
        ];
        
        $resultat = $this->logementModel->mettreAJour($idLogement, $donneesLogement);
        
        if (!$resultat) {
            afficherMessage('Une erreur est survenue lors de la mise à jour du logement.', 'erreur');
            rediriger('modifier-logement.php?id=' . $idLogement);
            return;
        }
        
        // Gestion des disponibilités
        $this->mettreAJourDisponibilites($idLogement);
        
        // Gestion des photos
        $this->mettreAJourPhotos($idLogement);
        
        afficherMessage('Votre logement a été mis à jour avec succès.', 'succes');
        rediriger('logement.php?id=' . $idLogement);
    }
    
    /**
     * Traite la suppression d'un logement
     */
    public function supprimer() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            afficherMessage('Vous devez être connecté pour supprimer un logement.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Récupération de l'ID du logement
        $idLogement = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($idLogement <= 0) {
            afficherMessage('Logement introuvable.', 'erreur');
            rediriger('index.php');
            return;
        }
        
        // Récupération du logement
        $logement = $this->logementModel->recupererParId($idLogement);
        
        if (!$logement) {
            afficherMessage('Logement introuvable.', 'erreur');
            rediriger('index.php');
            return;
        }
        
        // Vérification des droits
        if ($logement['id_proprietaire'] != $_SESSION['utilisateur_id'] && !$_SESSION['utilisateur_est_admin']) {
            afficherMessage('Vous n\'êtes pas autorisé à supprimer ce logement.', 'erreur');
            rediriger('index.php');
            return;
        }
        
        // Vérification s'il n'y a pas de réservations en cours
        if ($this->logementModel->aReservationsEnCours($idLogement)) {
            afficherMessage('Impossible de supprimer ce logement car il a des réservations en cours.', 'erreur');
            rediriger('logement.php?id=' . $idLogement);
            return;
        }
        
        // Suppression du logement
        $resultat = $this->logementModel->supprimer($idLogement);
        
        if (!$resultat) {
            afficherMessage('Une erreur est survenue lors de la suppression du logement.', 'erreur');
            rediriger('logement.php?id=' . $idLogement);
            return;
        }
        
        // Suppression des photos
        $photos = $this->logementModel->recupererPhotos($idLogement);
        
        foreach ($photos as $photo) {
            $cheminPhoto = ROOT_PATH . 'uploads/logements/' . $photo['url'];
            
            if (file_exists($cheminPhoto)) {
                unlink($cheminPhoto);
            }
        }
        
        afficherMessage('Votre logement a été supprimé avec succès.', 'succes');
        rediriger('profil.php');
    }
    
    /**
     * Récupère les détails d'un logement
     * @param int $id ID du logement
     * @return array|boolean Données du logement ou false
     */
    public function detailler($id) {
        $id = intval($id);
        
        if ($id <= 0) {
            return false;
        }
        
        // Récupération du logement
        $logement = $this->logementModel->recupererParId($id);
        
        if (!$logement) {
            return false;
        }
        
        // Récupération des photos
        $logement['photos'] = $this->logementModel->recupererPhotos($id);
        
        // Récupération des disponibilités
        $logement['disponibilites'] = $this->logementModel->recupererDisponibilites($id);
        
        return $logement;
    }
    
    /**
     * Récupère les disponibilités d'un logement formatées pour un calendrier
     * @param int $id ID du logement
     * @return array Disponibilités formatées
     */
    public function recupererDisponibilitesCalendrier($id) {
        $disponibilites = $this->logementModel->recupererDisponibilites($id);
        $reservations = $this->logementModel->recupererReservationsAcceptees($id);
        
        $evenements = [];
        
        // Ajouter les disponibilités
        foreach ($disponibilites as $dispo) {
            $evenements[] = [
                'title' => 'Disponible',
                'start' => $dispo['date_debut'],
                'end' => date('Y-m-d', strtotime($dispo['date_fin'] . ' +1 day')),
                'color' => '#4CAF50',
                'type' => 'disponibilite',
                'id' => $dispo['id']
            ];
        }
        
        // Ajouter les réservations
        foreach ($reservations as $reservation) {
            $evenements[] = [
                'title' => 'Réservé',
                'start' => $reservation['date_debut'],
                'end' => date('Y-m-d', strtotime($reservation['date_fin'] . ' +1 day')),
                'color' => '#F44336',
                'type' => 'reservation',
                'id' => $reservation['id']
            ];
        }
        
        return $evenements;
    }
    
    /**
     * Met à jour les disponibilités d'un logement
     * @param int $idLogement ID du logement
     * @return boolean Résultat de l'opération
     */
    private function mettreAJourDisponibilites($idLogement) {
        // Récupération des données
        $datesDebut = $_POST['dates_debut'] ?? [];
        $datesFin = $_POST['dates_fin'] ?? [];
        $idsDisponibilite = $_POST['ids_disponibilite'] ?? [];
        
        // Supprimer les disponibilités existantes qui ne sont pas dans la liste
        $this->logementModel->supprimerDisponibilitesNonListees($idLogement, $idsDisponibilite);
        
        // Traiter les disponibilités
        for ($i = 0; $i < count($datesDebut); $i++) {
            if (empty($datesDebut[$i]) || empty($datesFin[$i])) {
                continue;
            }
            
            if (!estDateValide($datesDebut[$i]) || !estDateValide($datesFin[$i])) {
                continue;
            }
            
            if (!estPeriodeValide($datesDebut[$i], $datesFin[$i])) {
                continue;
            }
            
            $disponibilite = [
                'date_debut' => $datesDebut[$i],
                'date_fin' => $datesFin[$i]
            ];
            
            // Si on a un ID, c'est une mise à jour
            if (isset($idsDisponibilite[$i]) && !empty($idsDisponibilite[$i])) {
                $this->logementModel->mettreAJourDisponibilite(
                    $idsDisponibilite[$i],
                    $disponibilite['date_debut'],
                    $disponibilite['date_fin']
                );
            } else {
                // Sinon, c'est une création
                $this->logementModel->ajouterDisponibilite(
                    $idLogement,
                    $disponibilite['date_debut'],
                    $disponibilite['date_fin']
                );
            }
        }
        
        return true;
    }
    
    /**
     * Met à jour les photos d'un logement
     * @param int $idLogement ID du logement
     * @return boolean Résultat de l'opération
     */
    private function mettreAJourPhotos($idLogement) {
        // Si on a des photos à supprimer
        if (isset($_POST['photos_supprimer']) && is_array($_POST['photos_supprimer'])) {
            foreach ($_POST['photos_supprimer'] as $idPhoto) {
                $photo = $this->logementModel->recupererPhoto($idPhoto);
                
                if ($photo && $photo['id_logement'] == $idLogement) {
                    $cheminPhoto = ROOT_PATH . 'uploads/logements/' . $photo['url'];
                    
                    if (file_exists($cheminPhoto)) {
                        unlink($cheminPhoto);
                    }
                    
                    $this->logementModel->supprimerPhoto($idPhoto);
                }
            }
        }
        
        // Si on a une nouvelle photo principale
        if (isset($_POST['photo_principale']) && !empty($_POST['photo_principale'])) {
            $idPhotoPrincipale = intval($_POST['photo_principale']);
            $this->logementModel->definirPhotoPrincipale($idLogement, $idPhotoPrincipale);
        }
        
        // Si on a de nouvelles photos
        if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
            $nouvellePrincipale = isset($_POST['nouvelle_photo_principale']) ? intval($_POST['nouvelle_photo_principale']) : -1;
            
            foreach ($_FILES['photos']['name'] as $i => $nom) {
                if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                
                // Création d'un tableau pour le fichier courant
                $fichier = [
                    'name' => $_FILES['photos']['name'][$i],
                    'type' => $_FILES['photos']['type'][$i],
                    'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                    'error' => $_FILES['photos']['error'][$i],
                    'size' => $_FILES['photos']['size'][$i]
                ];
                
                $resultat = validerFichierImage($fichier);
                
                if (!$resultat['valide']) {
                    continue;
                }
                
                $photoNom = traiterUploadImage($fichier, 'logements');
                
                if ($photoNom !== false) {
                    $estPrincipale = ($i === $nouvellePrincipale);
                    $this->logementModel->ajouterPhoto($idLogement, $photoNom, $estPrincipale);
                }
            }
        }
        
        return true;
    }
}
?>
