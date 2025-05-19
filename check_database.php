<?php
require_once "config.php";

echo "<h2>Diagnostic de la base de données OmnesBnB</h2>";

global $conn;

try {
    // Vérifier si la table utilisateurs existe
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>Tables trouvées dans la base de données :</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    // Vérifier si la table utilisateurs existe
    if (in_array('utilisateurs', $tables)) {
        echo "<p style='color:green'>✓ La table 'utilisateurs' existe.</p>";

        // Vérifier la structure de la table
        $columns = $conn->query("SHOW COLUMNS FROM utilisateurs")->fetchAll(PDO::FETCH_COLUMN);

        echo "<p>Colonnes de la table utilisateurs :</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>$column</li>";
        }
        echo "</ul>";

        // Compter le nombre d'utilisateurs
        $count = $conn->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
        echo "<p>Nombre d'utilisateurs en base : <strong>$count</strong></p>";

        // Afficher les utilisateurs (limité aux informations non sensibles)
        $users = $conn->query("SELECT id, prenom, nom, email, type_utilisateur FROM utilisateurs")->fetchAll(PDO::FETCH_ASSOC);

        if (count($users) > 0) {
            echo "<h3>Liste des utilisateurs :</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Prénom</th><th>Nom</th><th>Email</th><th>Type</th></tr>";

            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['prenom']) . "</td>";
                echo "<td>" . htmlspecialchars($user['nom']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['type_utilisateur']) . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p style='color:orange'>⚠ Aucun utilisateur n'est enregistré dans la base de données.</p>";
        }
    } else {
        echo "<p style='color:red'>✗ La table 'utilisateurs' n'existe pas !</p>";
    }

} catch(PDOException $e) {
    echo "<p style='color:red'>Erreur lors de l'accès à la base de données : " . $e->getMessage() . "</p>";
}
?>

