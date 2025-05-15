<?php
/**
 * API de géocodage pour Google Maps
 * 
 * Ce fichier permet de géocoder une adresse en coordonnées GPS
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

// Récupérer l'adresse à géocoder
$adresse = isset($_GET['adresse']) ? $_GET['adresse'] : '';

if (empty($adresse)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Adresse requise']);
    exit;
}

// Géocoder l'adresse
$coordonnees = geocoderAdresse($adresse);

if (!$coordonnees) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Impossible de géocoder l\'adresse']);
    exit;
}

// Répondre avec les coordonnées
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'coordonnees' => $coordonnees
]);

/**
 * Géocode une adresse en utilisant l'API Google Maps
 * 
 * @param string $adresse Adresse à géocoder
 * @return array|false Coordonnées GPS ou false en cas d'erreur
 */
function geocoderAdresse($adresse) {
    // URL-encoder l'adresse
    $adresse = urlencode($adresse);
    
    // Construire l'URL de l'API Google Maps
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
?>
