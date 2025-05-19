<?php
// Fichier d'urgence pour activer un compte directement

// Inclusion de la config pour accéder à la BD
include 'config.php';

// Si un email est spécifié dans l'URL
if (isset($_GET['email'])) {
    $email = $_GET['email'];

    global $conn;

    // Activer le compte
    $query = "UPDATE users SET active = 1 WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);

    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color:green'>Le compte associé à l'email $email a été activé avec succès!</p>";
        echo "<p>Vous pouvez maintenant <a href='connexion.php'>vous connecter</a>.</p>";
    } else {
        echo "<p style='color:red'>Erreur lors de l'activation du compte: " . mysqli_error($conn) . "</p>";
    }
} else {
    // Formulaire pour entrer l'email
    echo "
    <h2>Activation de compte d'urgence</h2>
    <form method='GET'>
        <label>Email du compte à activer:</label><br>
        <input type='email' name='email' required><br><br>
        <button type='submit'>Activer ce compte</button>
    </form>
    ";
}
?>