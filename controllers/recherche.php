<?php
/**
 * Contrôleur pour la gestion des recherches
 * 
 * Ce fichier gère les actions liées à la recherche de logements
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_MODELES . '/logement.php';
require_once CHEMIN_MODELES . '/disponibilite.php';

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : 'rechercher';

switch ($action) {
    case 'rechercher':
        actionRechercherLogements();
        break;
    case 'suggestions':
        actionSuggestionVilles();
        break;
    default:
        repondreJSON(['success' => false, 'message' => 'Action non reconnue']);
}

/**
 * Recherche des logements selon les critères fournis
 */
function actionRechercherLogements() {
    // Paramètres de recherche
    $filtres = [];
    
    // Filtrer par lieu (ville ou code postal)
    if (isset($_GET['lieu']) && !empty($_GET['lieu'])) {
        $lieu = nettoyer($_GET['lieu']);
        
        // Déterminer si c'est un code postal ou une ville
        if (preg_match('/^[0-9]{5}$/', $lieu)) {
            $filtres['code_postal'] = $lieu;
        } else {
            $filtres['ville'] = $lieu;
        }
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
    
    // Options de tri
    $tri = isset($_GET['tri']) ? nettoyer($_GET['tri']) : 'prix_asc';
    
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
    $logements = rechercherLogements($filtres, $tri, $limite, $offset);
    
    // Ajouter la photo principale pour chaque logement
    foreach ($logements as &$logement) {
        $photos = recupererPhotosLogement($logement['id']);
        $logement['photo_principale'] = !empty($photos) ? $photos[0]['url'] : null;
    }
    
    // Récupérer le nombre total de logements correspondant aux critères
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
 * Récupère des suggestions de villes pour l'autocomplétion
 */
function actionSuggestionVilles() {
    // Récupérer le terme de recherche
    $terme = isset($_GET['terme']) ? nettoyer($_GET['terme']) : '';
    
    if (empty($terme) || strlen($terme) < 2) {
        repondreJSON(['success' => true, 'suggestions' => []]);
        return;
    }
    
    // Récupérer les suggestions de villes
    $suggestions = recupererSuggestionsVilles($terme);
    
    // Répondre avec succès
    repondreJSON(['success' => true, 'suggestions' => $suggestions]);
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
