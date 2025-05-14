<?php
/**
 * Configuration pour l'API Google Maps
 */

// Clé API Google Maps
define('GOOGLE_MAPS_API_KEY', 'VOTRE_CLE_API_GOOGLE_MAPS');

// Options par défaut pour la carte
define('MAPS_DEFAULT_LAT', 46.2276); // Latitude par défaut (centre de la France)
define('MAPS_DEFAULT_LNG', 2.2137); // Longitude par défaut (centre de la France)
define('MAPS_DEFAULT_ZOOM', 5); // Niveau de zoom par défaut

// URL de l'API Google Maps
define('MAPS_API_URL', 'https://maps.googleapis.com/maps/api/js');

// Options pour Geocoding
define('MAPS_GEOCODING_URL', 'https://maps.googleapis.com/maps/api/geocode/json');

// Options pour Places API
define('MAPS_PLACES_URL', 'https://maps.googleapis.com/maps/api/place');

/**
 * Génère l'URL pour l'API Google Maps
 * @param array $libraries Librairies à inclure
 * @param string $callback Fonction de callback
 * @return string URL complète
 */
function getMapsApiUrl($libraries = [], $callback = 'initMap') {
    $url = MAPS_API_URL . '?key=' . GOOGLE_MAPS_API_KEY;
    
    if (!empty($libraries)) {
        $url .= '&libraries=' . implode(',', $libraries);
    }
    
    if ($callback) {
        $url .= '&callback=' . $callback;
    }
    
    return $url;
}

/**
 * Effectue une requête de géocodage
 * @param string $adresse Adresse à géocoder
 * @return array|false Résultat du géocodage ou false
 */
function geocodeAdresse($adresse) {
    $url = MAPS_GEOCODING_URL . '?address=' . urlencode($adresse) . '&key=' . GOOGLE_MAPS_API_KEY;
    
    $response = file_get_contents($url);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if ($data['status'] !== 'OK') {
        return false;
    }
    
    $result = $data['results'][0];
    
    return [
        'latitude' => $result['geometry']['location']['lat'],
        'longitude' => $result['geometry']['location']['lng'],
        'adresse_formatee' => $result['formatted_address'],
        'code_postal' => getComponentFromAddress($result, 'postal_code'),
        'ville' => getComponentFromAddress($result, 'locality')
    ];
}

/**
 * Récupère un composant spécifique d'une adresse
 * @param array $addressData Données d'adresse
 * @param string $type Type de composant
 * @return string Valeur du composant
 */
function getComponentFromAddress($addressData, $type) {
    foreach ($addressData['address_components'] as $component) {
        if (in_array($type, $component['types'])) {
            return $component['long_name'];
        }
    }
    
    return '';
}
?>
