<?php
/**
 * API de détails de lieu pour Google Maps
 * 
 * Ce fichier permet d'obtenir les détails d'un lieu à partir de son place_id
 * 
 * @author OmnesBnB
 */

// Inclure la configuration
require_once __DIR__ . '/../../config/config.php';
require_once CHEMIN_INCLUDES . '/auth.php';

// Vérifier que l'utilisateur est connecté
if (!estConnecte()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentification requise']);
    exit;
}

// Vérifier que la requête est de type GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer l'ID du lieu
$placeId = isset($_GET['place_id']) ? $_GET['place_id'] : '';

if (empty($placeId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de lieu requis']);
    exit;
}

// Obtenir les détails du lieu
$details = obtenirDetailsLieu($placeId);

if (!$details) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Impossible d\'obtenir les détails du lieu']);
    exit;
}

// Répondre avec les détails
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'details' => $details
]);

/**
 * Obtient les détails d'un lieu à partir de son place_id
 * 
 * @param string $placeId ID du lieu
 * @return array|false Détails du lieu ou false en cas d'erreur
 */
function obtenirDetailsLieu($placeId) {
    // URL-encoder l'ID du lieu
    $placeId = urlencode($placeId);
    
    // Construire l'URL de l'API Google Places Details
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=address_component,geometry,formatted_address&key=" . GOOGLE_MAPS_API_KEY;
    
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
        error_log('Erreur Places API: ' . $data['status']);
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
?>
