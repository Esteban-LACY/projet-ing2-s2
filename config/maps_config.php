<?php
/**
 * Configuration de l'API Google Maps
 * 
 * Ce fichier contient les paramètres de configuration pour l'intégration de Google Maps
 * 
 * @author OmnesBnB
 */

// Clé API Google Maps (à remplacer par votre propre clé)
define('GOOGLE_MAPS_API_KEY', 'votre_cle_api_google_maps');

// Options par défaut pour la carte
define('GOOGLE_MAPS_DEFAULT_LAT', 48.856614); // Latitude par défaut (Paris)
define('GOOGLE_MAPS_DEFAULT_LNG', 2.3522219); // Longitude par défaut (Paris)
define('GOOGLE_MAPS_DEFAULT_ZOOM', 13); // Niveau de zoom par défaut

/**
 * Génère le script pour charger l'API Google Maps
 * 
 * @param boolean $includeLibraries Inclure les bibliothèques supplémentaires (places, geometry, etc.)
 * @return string Balise script pour l'API Google Maps
 */
function genererScriptGoogleMaps($includeLibraries = true) {
    $url = 'https://maps.googleapis.com/maps/api/js?key=' . GOOGLE_MAPS_API_KEY;
    
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
function geocoderAdresse($adresse) {
    $adresse = urlencode($adresse);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$adresse}&key=" . GOOGLE_MAPS_API_KEY;
    
    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
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
    if ($data['status'] !== 'OK') {
        error_log('Erreur de géocodage: ' . $data['status']);
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
 * Calcule la distance entre deux points GPS (en kilomètres)
 * 
 * @param float $lat1 Latitude du point 1
 * @param float $lng1 Longitude du point 1
 * @param float $lat2 Latitude du point 2
 * @param float $lng2 Longitude du point 2
 * @return float Distance en kilomètres
 */
function calculerDistance($lat1, $lng1, $lat2, $lng2) {
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
?>
