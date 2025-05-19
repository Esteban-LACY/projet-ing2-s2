<?php
// Script d'urgence pour créer un administrateur

// Inclusion de la config pour accéder à la BD
include 'config.php';

global $conn;

// Informations du nouvel admin
$prenom = "Admin";
$nom = "Urgence";
$email = "admin@omnesbnb.fr";
$password = password_hash("Admin123!", PASSWORD_DEFAULT);
$telephone = "06.12.34.56.78";
$statut = "Personnel Omnes";
$campus = "Paris";
$is_admin = 1;
$active = 1;

// Vérifier si l'admin existe déjà
$check_query = "SELECT id FROM users WHERE email = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "s", $email);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    // L'admin existe déjà, mise à jour du mot de passe
    $update_query = "UPDATE users SET password = ?, active = 1, is_admin = 1 WHERE email = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ss", $password, $email);

    if (mysqli_stmt_execute($update_stmt)) {
        echo "<p style='color:green'>L'administrateur existant a été mis à jour avec le mot de passe 'Admin123!'</p>";
    } else {
        echo "<p style='color:red'>Erreur lors de la mise à jour de l'administrateur: " . mysqli_error($conn) . "</p>";
    }
} else {
    // Créer un nouvel admin
    $insert_query = "INSERT INTO users (prenom, nom, email, password, telephone, statut, campus, is_admin, active, date_creation) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "sssssssii", $prenom, $nom, $email, $password, $telephone, $statut, $campus, $is_admin, $active);

    if (mysqli_stmt_execute($insert_stmt)) {
        echo "<p style='color:green'>Nouvel administrateur créé avec succès! Email: admin@omnesbnb.fr, Mot de passe: Admin123!</p>";
    } else {
        echo "<p style='color:red'>Erreur lors de la création de l'administrateur: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p><a href='connexion.php'>Se connecter maintenant</a></p>";
?>