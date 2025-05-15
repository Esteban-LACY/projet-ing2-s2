<?php
/**
 * API de notifications push
 * 
 * Ce fichier permet d'envoyer des notifications push aux utilisateurs
 * 
 * @author OmnesBnB
 */

// Inclure la configuration
require_once __DIR__ . '/../../config/config.php';
require_once CHEMIN_INCLUDES . '/auth.php';

// Vérifier que l'utilisateur est authentifié et est administrateur
if (!estConnecte() || !estAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);

// Si les données ne sont pas au format JSON, utiliser les données POST
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Vérifier les paramètres requis
if (!isset($data['titre']) || empty($data['titre'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Titre de notification requis']);
    exit;
}

if (!isset($data['message']) || empty($data['message'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Message de notification requis']);
    exit;
}

// Destinataire (optionnel, si non spécifié, envoyer à tous les utilisateurs)
$idUtilisateur = isset($data['id_utilisateur']) ? intval($data['id_utilisateur']) : null;

// URL de redirection (optionnel)
$url = isset($data['url']) ? $data['url'] : null;

// Envoyer la notification
$resultat = envoyerNotification($data['titre'], $data['message'], $idUtilisateur, $url);

if (!$resultat) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi de la notification']);
    exit;
}

// Répondre avec succès
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Notification envoyée avec succès'
]);

/**
 * Envoie une notification push à un utilisateur ou à tous les utilisateurs
 * 
 * @param string $titre Titre de la notification
 * @param string $message Message de la notification
 * @param int|null $idUtilisateur ID de l'utilisateur destinataire (null pour tous)
 * @param string|null $url URL de redirection
 * @return bool True si l'envoi a réussi, false sinon
 */
function envoyerNotification($titre, $message, $idUtilisateur = null, $url = null) {
    // En mode développement, simuler l'envoi de notification
    if (MODE_DEVELOPPEMENT) {
        journaliser("Notification simulée - Titre: $titre - Message: $message - Destinataire: " . ($idUtilisateur ? $idUtilisateur : 'tous'), 'INFO');
        return true;
    }
    
    // Récupérer les tokens de notification
    if ($idUtilisateur) {
        // Récupérer le token de l'utilisateur spécifique
        $sql = "SELECT token_notification FROM notifications_tokens WHERE id_utilisateur = :id_utilisateur";
        $params = [':id_utilisateur' => $idUtilisateur];
        
        $resultat = executerRequete($sql, $params);
        
        if (!$resultat || empty($resultat)) {
            return false;
        }
        
        $tokens = array_column($resultat, 'token_notification');
    } else {
        // Récupérer tous les tokens
        $sql = "SELECT token_notification FROM notifications_tokens";
        
        $resultat = executerRequete($sql);
        
        if (!$resultat || empty($resultat)) {
            return false;
        }
        
        $tokens = array_column($resultat, 'token_notification');
    }
    
    // Si aucun token n'est trouvé, retourner false
    if (empty($tokens)) {
        return false;
    }
    
    // Préparer les données de la notification
    $data = [
        'notification' => [
            'title' => $titre,
            'body' => $message,
            'icon' => '/assets/img/logos/logo.png',
            'click_action' => 'OPEN_ACTIVITY'
        ],
        'data' => [
            'url' => $url
        ]
    ];
    
    // Envoyer la notification (cette implémentation est fictive)
    // Dans une application réelle, vous utiliseriez Firebase Cloud Messaging (FCM) ou un service similaire
    
    // Simuler l'envoi de notification
    journaliser("Notification envoyée - Titre: $titre - Message: $message - Destinataires: " . count($tokens), 'INFO');
    
    return true;
}
?>
