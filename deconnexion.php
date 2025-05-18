<?php
// Inclusion du fichier de configuration
include 'config.php';

// Destruction de la session
session_start();
session_unset();
session_destroy();

// Suppression du cookie de connexion automatique si existant
if (isset($_COOKIE['auth_token'])) {
    // Supprimer le cookie côté navigateur
    setcookie('auth_token', '', time() - 3600, '/', '', true, true);

    // Si possible, supprimer aussi le token de la base de données
    if (isset($conn)) {
        $query = "DELETE FROM cookies_auth WHERE token = ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            $token = $_COOKIE['auth_token'];
            mysqli_stmt_bind_param($stmt, "s", $token);
            mysqli_stmt_execute($stmt);
        }
    }
}

// Redirection vers la page d'accueil avec un message de succès
header('Location: index.php?success=Vous avez été déconnecté avec succès.');
exit;
?>