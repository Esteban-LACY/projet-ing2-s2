<?php
/**
* Service pour l'intégration de Google Maps
* 
* Ce fichier encapsule toutes les interactions avec l'API Google Maps
* 
* @author OmnesBnB
*/

// Inclusion du fichier de configuration pour les clés API
require_once __DIR__ . '/../config/config.php';

/**
* Classe qui fournit les services Google Maps
*/
class GoogleMapsService {
   /**
    * Clé API Google Maps
    * @var string
    */
   private $apiKey;
   
   /**
    * Constructeur
    */
   public function __construct() {
       $this->apiKey = GOOGLE_MAPS_API_KEY;
   }
   
   /**
    * Génère le script pour charger l'API Google Maps
    * 
    * @param boolean $includeLibraries Inclure les bibliothèques supplémentaires (places, geometry, etc.)
    * @return string Balise script pour l'API Google Maps
    */
   public function genererScriptGoogleMaps($includeLibraries = true) {
       $url = 'https://maps.googleapis.com/maps/api/js?key=' . $this->apiKey;
       
       if ($includeLibraries) {
           $url .= '&libraries=places,geometry';
       }
       
       $url .= '&callback=initGoogleMaps';
       
       return '<script src="' . $url . '" async defer></script>';
   }
   
   /**
    * Géocode une adresse pour obtenir les coordonnées GPS
    * 
    * @param string $adresse Adresse à géocoder
    * @return array|false Tableau avec lat et lng ou false en cas d'erreur
    */
   public function geocoderAdresse($adresse) {
       // URL-encoder l'adresse
       $adresse = urlencode($adresse);
       
       // Construire l'URL de l'API Google Maps Geocoding
       $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$adresse}&key=" . $this->apiKey;
       
       // Initialiser cURL
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 secondes
       
       // Exécuter la requête
       $response = curl_exec($ch);
       
       // Vérifier s'il y a des erreurs
       if (curl_errno($ch)) {
           error_log('Erreur cURL: ' . curl_error($ch));
           curl_close($ch);
           return false;
       }
       
       curl_close($ch);
       
       // Décoder la réponse
       $data = json_decode($response, true);
       
       // Vérifier le statut de la réponse
       if (!isset($data['status']) || $data['status'] !== 'OK') {
           error_log('Erreur de géocodage: ' . (isset($data['status']) ? $data['status'] : 'Réponse invalide'));
           return false;
       }
       
       // Vérifier si on a des résultats
       if (empty($data['results'])) {
           error_log('Aucun résultat trouvé pour l\'adresse: ' . urldecode($adresse));
           return false;
       }
       
       // Récupérer les coordonnées
       $location = $data['results'][0]['geometry']['location'];
       
       return [
           'lat' => $location['lat'],
           'lng' => $location['lng'],
           'adresse_formatee' => $data['results'][0]['formatted_address']
       ];
   }
   
   /**
    * Obtient les détails d'un lieu à partir de son place_id
    * 
    * @param string $placeId ID du lieu
    * @return array|false Détails du lieu ou false en cas d'erreur
    */
   public function obtenirDetailsLieu($placeId) {
       // URL-encoder l'ID du lieu
       $placeId = urlencode($placeId);
       
       // Construire l'URL de l'API Google Places Details
       $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=address_component,geometry,formatted_address&key=" . $this->apiKey;
       
       // Initialiser cURL
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 secondes
       
       // Exécuter la requête
       $response = curl_exec($ch);
       
       // Vérifier s'il y a des erreurs
       if (curl_errno($ch)) {
           error_log('Erreur cURL: ' . curl_error($ch));
           curl_close($ch);
           return false;
       }
       
       curl_close($ch);
       
       // Décoder la réponse
       $data = json_decode($response, true);
       
       // Vérifier le statut de la réponse
       if (!isset($data['status']) || $data['status'] !== 'OK') {
           error_log('Erreur Places API: ' . (isset($data['status']) ? $data['status'] : 'Réponse invalide'));
           return false;
       }
       
       // Extraire les informations nécessaires
       $result = $data['result'];
       
       // Extraire les composants de l'adresse
       $adresseComponents = [];
       $ville = '';
       $codePostal = '';
       $rue = '';
       $numero = '';
       
       foreach ($result['address_components'] as $component) {
           $adresseComponents[$component['types'][0]] = $component['long_name'];
           
           if (in_array('locality', $component['types'])) {
               $ville = $component['long_name'];
           } elseif (in_array('postal_code', $component['types'])) {
               $codePostal = $component['long_name'];
           } elseif (in_array('route', $component['types'])) {
               $rue = $component['long_name'];
           } elseif (in_array('street_number', $component['types'])) {
               $numero = $component['long_name'];
           }
       }
       
       // Construire l'adresse formatée
       $adresse = '';
       
       if (!empty($numero)) {
           $adresse .= $numero . ' ';
       }
       
       if (!empty($rue)) {
           $adresse .= $rue;
       }
       
       // Récupérer les coordonnées
       $location = $result['geometry']['location'];
       
       return [
           'adresse' => $adresse,
           'ville' => $ville,
           'code_postal' => $codePostal,
           'adresse_formatee' => $result['formatted_address'],
           'latitude' => $location['lat'],
           'longitude' => $location['lng'],
           'components' => $adresseComponents
       ];
   }
   
   /**
    * Obtient des suggestions d'adresses depuis l'API Places de Google
    * 
    * @param string $input Terme de recherche
    * @param string $types Types de résultats (geocode, address, establishment, etc.)
    * @param string $components Composants de restriction (country:fr, etc.)
    * @return array|false Suggestions d'adresses ou false en cas d'erreur
    */
   public function obtenirSuggestionsAdresses($input, $types = 'address', $components = 'country:fr') {
       if (empty($input) || strlen($input) < 2) {
           return [];
       }
       
       // URL-encoder les paramètres
       $input = urlencode($input);
       $types = urlencode($types);
       $components = urlencode($components);
       
       // Construire l'URL de l'API Google Places Autocomplete
       $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?input={$input}&types={$types}&components={$components}&key=" . $this->apiKey;
       
       // Initialiser cURL
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 secondes
       
       // Exécuter la requête
       $response = curl_exec($ch);
       
       // Vérifier s'il y a des erreurs
       if (curl_errno($ch)) {
           error_log('Erreur cURL: ' . curl_error($ch));
           curl_close($ch);
           return false;
       }
       
       curl_close($ch);
       
       // Décoder la réponse
       $data = json_decode($response, true);
       
       // Vérifier le statut de la réponse
       if (!isset($data['status']) || $data['status'] !== 'OK') {
           // Si le statut est ZERO_RESULTS, simplement retourner un tableau vide
           if (isset($data['status']) && $data['status'] === 'ZERO_RESULTS') {
               return [];
           }
           
           error_log('Erreur Places API: ' . (isset($data['status']) ? $data['status'] : 'Réponse invalide'));
           return false;
       }
       
       // Formater les suggestions
       $suggestions = [];
       
       foreach ($data['predictions'] as $prediction) {
           $suggestions[] = [
               'place_id' => $prediction['place_id'],
               'description' => $prediction['description'],
               'structured_formatting' => isset($prediction['structured_formatting']) ? [
                   'main_text' => $prediction['structured_formatting']['main_text'],
                   'secondary_text' => $prediction['structured_formatting']['secondary_text']
               ] : null
           ];
       }
       
       return $suggestions;
   }
   
   /**
    * Calcule la distance entre deux points GPS (en kilomètres)
    * 
    * @param float $lat1 Latitude du point 1
    * @param float $lng1 Longitude du point 1
    * @param float $lat2 Latitude du point 2
    * @param float $lng2 Longitude du point 2
    * @return float Distance en kilomètres
    */
   public function calculerDistance($lat1, $lng1, $lat2, $lng2) {
       // Rayon de la Terre en kilomètres
       $rayonTerre = 6371;
       
       // Convertir les degrés en radians
       $lat1 = deg2rad($lat1);
       $lng1 = deg2rad($lng1);
       $lat2 = deg2rad($lat2);
       $lng2 = deg2rad($lng2);
       
       // Formule de Haversine
       $dLat = $lat2 - $lat1;
       $dLng = $lng2 - $lng1;
       
       $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLng/2) * sin($dLng/2);
       $c = 2 * atan2(sqrt($a), sqrt(1-$a));
       $distance = $rayonTerre * $c;
       
       return $distance;
   }
   
   /**
    * Génère une URL de carte statique Google Maps
    * 
    * @param float $lat Latitude
    * @param float $lng Longitude
    * @param int $zoom Niveau de zoom
    * @param int $width Largeur de l'image
    * @param int $height Hauteur de l'image
    * @param string $markerColor Couleur du marqueur
    * @return string URL de l'image de carte statique
    */
   public function genererUrlCarteStatique($lat, $lng, $zoom = 14, $width = 400, $height = 300, $markerColor = 'red') {
       return "https://maps.googleapis.com/maps/api/staticmap?"
           . "center={$lat},{$lng}"
           . "&zoom={$zoom}"
           . "&size={$width}x{$height}"
           . "&markers=color:{$markerColor}|{$lat},{$lng}"
           . "&key={$this->apiKey}";
   }
}

// Instance singleton du service Google Maps
class GoogleMapsServiceSingleton {
   private static $instance = null;
   
   public static function getInstance() {
       if (self::$instance === null) {
           self::$instance = new GoogleMapsService();
       }
       return self::$instance;
   }
}
?>
