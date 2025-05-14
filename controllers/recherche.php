<?php
/**
 * Contrôleur pour la recherche de logements
 */
require_once 'config/config.php';
require_once 'models/logement.php';
require_once 'includes/fonctions.php';

class RechercheController {
    private $logementModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->logementModel = new LogementModel();
    }
    
    /**
     * Recherche des logements selon des critères
     * @param array $criteres Critères de recherche
     * @return array Résultats de la recherche
     */
    public function rechercher($criteres = []) {
        // Nettoyage et validation des critères
        $criteresValides = $this->nettoyerCriteres($criteres);
        
        // Recherche des logements
        $logements = $this->logementModel->rechercher($criteresValides);
        
        // Format de réponse pour AJAX
        if (isset($criteres['ajax']) && $criteres['ajax']) {
            header('Content-Type: application/json');
            echo json_encode([
                'succes' => true,
                'resultats' => $logements,
                'nombre' => count($logements)
            ]);
            exit;
        }
        
        return $logements;
    }
    
    /**
     * Recherche des logements par localisation
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param float $rayon Rayon de recherche en km
     * @param array $criteres Critères additionnels
     * @return array Résultats de la recherche
     */
    public function rechercherParLocalisation($latitude, $longitude, $rayon = 10, $criteres = []) {
        // Validation des coordonnées
        $latitude = floatval($latitude);
        $longitude = floatval($longitude);
        $rayon = floatval($rayon);
        
        if ($latitude == 0 || $longitude == 0 || $rayon <= 0) {
            return [];
        }
        
        // Nettoyage et validation des critères
        $criteresValides = $this->nettoyerCriteres($criteres);
        
        // Ajout des critères de localisation
        $criteresValides['latitude'] = $latitude;
        $criteresValides['longitude'] = $longitude;
        $criteresValides['rayon'] = $rayon;
        
        // Recherche des logements
        $logements = $this->logementModel->rechercherParLocalisation($criteresValides);
        
        // Format de réponse pour AJAX
        if (isset($criteres['ajax']) && $criteres['ajax']) {
            header('Content-Type: application/json');
            echo json_encode([
                'succes' => true,
                'resultats' => $logements,
                'nombre' => count($logements)
            ]);
            exit;
        }
        
        return $logements;
    }
    
    /**
     * Nettoie et valide les critères de recherche
     * @param array $criteres Critères bruts
     * @return array Critères nettoyés et validés
     */
    private function nettoyerCriteres($criteres) {
        $criteresValides = [];
        
        // Lieu (ville, code postal, adresse)
        if (isset($criteres['lieu']) && !empty($criteres['lieu'])) {
            $criteresValides['lieu'] = nettoyer($criteres['lieu']);
        }
        
        // Dates
        if (isset($criteres['date_debut']) && !empty($criteres['date_debut'])) {
            if (estDateValide($criteres['date_debut'])) {
                $criteresValides['date_debut'] = $criteres['date_debut'];
            }
        }
        
        if (isset($criteres['date_fin']) && !empty($criteres['date_fin'])) {
            if (estDateValide($criteres['date_fin'])) {
                $criteresValides['date_fin'] = $criteres['date_fin'];
            }
        }
        
        // Type de logement
        if (isset($criteres['type_logement']) && !empty($criteres['type_logement'])) {
            if (in_array($criteres['type_logement'], ['entier', 'collocation', 'libere'])) {
                $criteresValides['type_logement'] = $criteres['type_logement'];
            }
        }
        
        // Prix
        if (isset($criteres['prix_min']) && !empty($criteres['prix_min'])) {
            $prixMin = floatval($criteres['prix_min']);
            if ($prixMin >= 0) {
                $criteresValides['prix_min'] = $prixMin;
            }
        }
        
        if (isset($criteres['prix_max']) && !empty($criteres['prix_max'])) {
            $prixMax = floatval($criteres['prix_max']);
            if ($prixMax > 0) {
                $criteresValides['prix_max'] = $prixMax;
            }
        }
        
        // Nombre de places
        if (isset($criteres['nb_places_min']) && !empty($criteres['nb_places_min'])) {
            $nbPlacesMin = intval($criteres['nb_places_min']);
            if ($nbPlacesMin > 0) {
                $criteresValides['nb_places_min'] = $nbPlacesMin;
            }
        }
        
        // Tri
        if (isset($criteres['tri']) && !empty($criteres['tri'])) {
            if (in_array($criteres['tri'], ['prix_asc', 'prix_desc', 'date_asc', 'date_desc'])) {
                $criteresValides['tri'] = $criteres['tri'];
            }
        }
        
        return $criteresValides;
    }
    
    /**
     * Récupère les suggestions de villes pour l'autocomplétion
     * @param string $terme Terme de recherche
     * @return array Suggestions
     */
    public function getSuggestionVilles($terme) {
        if (empty($terme) || strlen($terme) < 2) {
            return [];
        }
        
        $terme = nettoyer($terme);
        
        $suggestions = $this->logementModel->rechercherVilles($terme);
        
        // Format de réponse pour AJAX
        header('Content-Type: application/json');
        echo json_encode($suggestions);
        exit;
    }
}
?>
