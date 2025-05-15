<?php
/**
 * API de suggestions d'adresses pour Google Maps
 * 
 * Ce fichier permet d'obtenir des suggestions d'adresses depuis l'API Places de Google
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

// Récupérer le terme de recherche
$input = isset($_GET['input']) ? $_GET['input'] : '';

if (empty($input) || strlen($input) < 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Terme de recherche trop court']);
    exit;
}

// Types de résultats (optionnel)
$types = isset($_GET['types']) ? $_GET['types'] : 'address';

// Composants de restriction (optionnel, par défaut France)
$components = isset($_GET['components']) ? $_GET['components'] : 'country:fr';

// Obtenir les suggestions d'adresses
$suggestions = obtenirSuggestionsAdresses($input, $types, $components);

if (!$suggestions) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Impossible d\'obtenir des suggestions']);
    exit;
}

// Répondre avec les suggestions
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'suggestions' => $suggestions
]);

/**
 * Obtient des suggestions d'adresses depuis l'API Places de Google
 * 
 * @param string $input Terme de recherche
 * @param string $types Types de résultats (geocode, address, establishment, etc.)
 * @param string $components Composants de restriction (country:fr, etc.)
 * @return array|false Suggestions d'adresses ou false en cas d'erreur
 */
function obtenirSuggestionsAdresses($input, $types = 'address', $components = 'country:fr') {
    // URL-encoder les paramètres
    $input = urlencode($input);
    $types = urlencode($types);
    $components = urlencode($components);
    
    // Construire l'URL de l'API Google Places Autocomplete
    $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?input={$input}&types={$types}&components={$components}&key=" . GOOGLE_MAPS_API_KEY;
    
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
?>
