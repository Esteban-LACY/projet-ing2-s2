<?php
// Inclusion du fichier de configuration avec la connexion à la base de données
include 'config.php';

// Initialisation des variables de recherche
$lieu = isset($_GET['lieu']) ? htmlspecialchars($_GET['lieu']) : '';
$date_arrivee = isset($_GET['date_arrivee']) ? htmlspecialchars($_GET['date_arrivee']) : '';
$date_depart = isset($_GET['date_depart']) ? htmlspecialchars($_GET['date_depart']) : '';
$type_logement = isset($_GET['type_logement']) ? htmlspecialchars($_GET['type_logement']) : '';
$type_sejour = isset($_GET['type_sejour']) ? htmlspecialchars($_GET['type_sejour']) : 'nuit';
$prix_maximum = isset($_GET['prix_maximum']) ? intval($_GET['prix_maximum']) : 0;

// Page actuelle pour la pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$logements_par_page = 9;
$offset = ($page - 1) * $logements_par_page;

// Construction de la requête SQL pour récupérer les logements
$sql = "SELECT * FROM logements WHERE 1=1";

// Ajout des filtres si présents
global $conn;
if (!empty($lieu)) {
    $lieu = mysqli_real_escape_string($conn, $lieu);
    $sql .= " AND (adresse LIKE '%$lieu%' OR titre LIKE '%$lieu%')";
}

if (!empty($type_logement)) {
    $type_logement = mysqli_real_escape_string($conn, $type_logement);
    $sql .= " AND type = '$type_logement'";
}

// Filtre de prix selon le type de séjour
if ($prix_maximum > 0) {
    switch ($type_sejour) {
        case 'nuit':
            $sql .= " AND prix_nuit <= $prix_maximum";
            break;
        case 'semaine':
            $sql .= " AND prix_semaine <= $prix_maximum";
            break;
        case 'mois':
            $sql .= " AND prix_mois <= $prix_maximum";
            break;
        case 'annee':
            $sql .= " AND prix_annee <= $prix_maximum";
            break;
    }
}

// Vérification des disponibilités si dates spécifiées
if (!empty($date_arrivee) && !empty($date_depart)) {
    $date_arrivee = mysqli_real_escape_string($conn, $date_arrivee);
    $date_depart = mysqli_real_escape_string($conn, $date_depart);

    // Exclure les logements déjà réservés pour cette période
    $sql .= " AND id NOT IN (
        SELECT logement_id FROM reservations 
        WHERE (date_arrivee <= '$date_depart' AND date_depart >= '$date_arrivee')
        AND statut != 'annulée'
    )";
}

// Requête pour compter le nombre total de logements correspondant aux critères
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) AS total", $sql);
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_logements = $count_row['total'];

// Calcul du nombre total de pages
$total_pages = ceil($total_logements / $logements_par_page);

// Ajout de la clause LIMIT pour la pagination
$sql .= " ORDER BY date_creation DESC LIMIT $offset, $logements_par_page";

// Exécution de la requête principale
$result = mysqli_query($conn, $sql);

// Vérification si l'utilisateur est connecté pour adapter l'affichage du header
$user_connected = isset($_SESSION['user_id']);
$user_name = $user_connected ? $_SESSION['user_nom'] . ' ' . $_SESSION['user_prenom'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OmnesBnB - Logements pour étudiants et personnel Omnes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            max-width: 100%;
            background-color: #FFFFFF;
        }
    </style>
</head>
<body class="bg-white">

<!-- En-tête -->
<header class="bg-white sticky top-0 z-50 border-b-2 border-black shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <a href="index.php" class="text-black font-bold text-xl">OmnesBnB</a>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="index.php" class="text-sm text-black hover:text-black">Chercher</a>
                <a href="publier.php" class="text-sm text-black hover:text-black">Publier</a>
                <a href="mes-locations.php" class="text-sm text-black hover:text-black">Mes locations</a>
                <div class="h-8 w-px mx-3 border-l-2 border-black"></div>
                <?php if($user_connected): ?>
                    <a href="profil.php" class="text-sm bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800">Mon Profil</a>
                <?php else: ?>
                    <a href="connexion.php" class="text-sm bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800">Connexion / Inscription</a>
                <?php endif; ?>

                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="admin.php" class="text-sm text-black hover:text-black">Administration</a>
                <?php endif; ?>
            </nav>
            <div class="md:hidden">
                <button id="menu-burger" class="flex items-center p-2 rounded-lg border-2 border-black">
                    <i class="fas fa-bars text-gray-700"></i>
                </button>
            </div>
        </div>
        <div id="menu-mobile" class="md:hidden hidden py-3">
            <a href="index.php" class="block py-2 text-sm text-black font-medium text-center">Chercher</a>
            <a href="publier.php" class="block py-2 text-sm text-black font-medium text-center">Publier</a>
            <a href="mes-locations.php" class="block py-2 text-sm text-black font-medium text-center">Mes locations</a>
            <div class="w-4/5 mx-auto border-t-2 border-black my-3"></div>
            <?php if($user_connected): ?>
                <a href="profil.php" class="block text-sm bg-black text-white py-2 px-6 rounded-lg text-center hover:bg-gray-800">Mon Profil</a>
            <?php else: ?>
                <a href="connexion.php" class="block text-sm bg-black text-white py-2 px-6 rounded-lg text-center hover:bg-gray-800">Connexion / Inscription</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Accroche + Formulaire -->
<section class="flex flex-col items-center justify-center bg-black py-12 px-4">
    <div class="w-full flex flex-col items-center justify-center">
        <h1 class="text-white text-4xl md:text-5xl font-bold mb-3 text-center leading-tight">Trouvez votre logement idéal</h1>
        <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">La plateforme de logements pour les étudiants et le personnel d'Omnes</p>
    </div>
    <div class="bg-white rounded-2xl p-8 shadow-lg border-2 border-black max-w-3xl w-full mx-auto">
        <form action="index.php" method="GET" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label for="lieu" class="block text-sm font-medium text-gray-700 mb-1">Lieu</label>
                    <input type="text" id="lieu" name="lieu" placeholder="Ville, quartier..."
                           value="<?php echo $lieu; ?>"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 placeholder-gray-400 focus:outline-none" />
                </div>
                <div>
                    <label for="date_arrivee" class="block text-sm font-medium text-gray-700 mb-1">Date d'arrivée</label>
                    <input type="date" id="date_arrivee" name="date_arrivee"
                           value="<?php echo $date_arrivee; ?>"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" />
                </div>
                <div>
                    <label for="date_depart" class="block text-sm font-medium text-gray-700 mb-1">Date de départ</label>
                    <input type="date" id="date_depart" name="date_depart"
                           value="<?php echo $date_depart; ?>"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" />
                </div>
                <div>
                    <label for="type_logement" class="block text-sm font-medium text-gray-700 mb-1">Type de logement</label>
                    <select id="type_logement" name="type_logement" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 appearance-none">
                        <option value="" <?php echo $type_logement == '' ? 'selected' : ''; ?>>Tous les types</option>
                        <option value="studio" <?php echo $type_logement == 'studio' ? 'selected' : ''; ?>>Studio</option>
                        <option value="chambre" <?php echo $type_logement == 'chambre' ? 'selected' : ''; ?>>Chambre</option>
                        <option value="appartement" <?php echo $type_logement == 'appartement' ? 'selected' : ''; ?>>Appartement</option>
                        <option value="colocation" <?php echo $type_logement == 'colocation' ? 'selected' : ''; ?>>Collocation</option>
                    </select>
                </div>
                <div>
                    <label for="type_sejour" class="block text-sm font-medium text-gray-700 mb-1">Type de séjour</label>
                    <select id="type_sejour" name="type_sejour" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 appearance-none">
                        <option value="nuit" <?php echo $type_sejour == 'nuit' ? 'selected' : ''; ?>>Nuit(s)</option>
                        <option value="semaine" <?php echo $type_sejour == 'semaine' ? 'selected' : ''; ?>>Semaine(s)</option>
                        <option value="mois" <?php echo $type_sejour == 'mois' ? 'selected' : ''; ?>>Mois</option>
                        <option value="annee" <?php echo $type_sejour == 'annee' ? 'selected' : ''; ?>>Année(s)</option>
                    </select>
                </div>
                <div>
                    <label for="prix_maximum" class="block text-sm font-medium text-gray-700 mb-1">Prix maximum</label>
                    <input type="number" id="prix_maximum" name="prix_maximum"
                           value="<?php echo $prix_maximum > 0 ? $prix_maximum : ''; ?>"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" />
                </div>
            </div>
            <div>
                <button type="submit" class="bg-black text-white font-medium py-3 px-10 rounded-lg w-full hover:bg-gray-900 transition-all shadow-md text-lg">Rechercher</button>
            </div>
        </form>
    </div>
</section>

<!-- Comment ça marche -->
<section class="py-10 px-4 bg-white">
    <div class="container mx-auto max-w-5xl">
        <h2 class="text-2xl md:text-3xl font-bold mb-8 text-center">Comment ça marche</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="flex justify-center">
                    <div class="w-16 h-16 rounded-xl border-2 border-black flex items-center justify-center mb-4">
                        <i class="fas fa-search text-2xl text-black"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold mb-2">Cherchez</h3>
                <p class="text-gray-600 text-sm">Trouvez le logement qui correspond à vos besoins.</p>
            </div>
            <div class="text-center">
                <div class="flex justify-center">
                    <div class="w-16 h-16 rounded-xl border-2 border-black flex items-center justify-center mb-4">
                        <i class="fas fa-credit-card text-2xl text-black"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold mb-2">Réservez</h3>
                <p class="text-gray-600 text-sm">Réservez et payez en ligne en toute sécurité.</p>
            </div>
            <div class="text-center">
                <div class="flex justify-center">
                    <div class="w-16 h-16 rounded-xl border-2 border-black flex items-center justify-center mb-4">
                        <i class="fas fa-home text-2xl text-black"></i>
                    </div>
                </div>
                <h3 class="text-lg font-semibold mb-2">Emménagez</h3>
                <p class="text-gray-600 text-sm">Contactez le propriétaire et installez-vous.</p>
            </div>
        </div>
    </div>
</section>

<!-- Logements disponibles -->
<section class="pb-10 pt-5 px-4 bg-white">
    <div class="container mx-auto max-w-6xl">
        <div class="bg-white border-2 border-black rounded-lg p-8 shadow-sm">
            <h2 class="text-2xl font-bold mb-8">Logements disponibles</h2>
            <div id="logements-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                <?php
                // Affichage des logements
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Récupération de l'image principale du logement
                        $logement_id = $row['id'];
                        $img_query = "SELECT photo_url FROM photos WHERE logement_id = $logement_id AND is_main = 1 LIMIT 1";
                        $img_result = mysqli_query($conn, $img_query);

                        if (mysqli_num_rows($img_result) > 0) {
                            $img_row = mysqli_fetch_assoc($img_result);
                            $image = $img_row['photo_url'];
                        } else {
                            // Image par défaut si aucune image principale n'est trouvée
                            $image = "uploads/logements/default.jpg";
                        }

                        // Affichage du prix selon le type de séjour sélectionné
                        $prix = '';
                        switch ($type_sejour) {
                            case 'nuit':
                                $prix = $row['prix_nuit'] . "€ / nuit";
                                break;
                            case 'semaine':
                                $prix = $row['prix_semaine'] . "€ / semaine";
                                break;
                            case 'mois':
                                $prix = $row['prix_mois'] . "€ / mois";
                                break;
                            case 'annee':
                                $prix = $row['prix_annee'] . "€ / an";
                                break;
                        }

                        // Génération de la carte logement
                        echo '<div>';
                        echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($row['titre']) . '" class="w-full h-48 object-cover rounded-t-lg">';
                        echo '<div class="mt-2 text-right">';
                        echo '<span class="font-medium text-sm">' . htmlspecialchars($row['type']) . '</span>';
                        echo '</div>';
                        echo '<div class="flex justify-between items-center mt-2">';
                        echo '<h3 class="text-lg font-bold">' . htmlspecialchars($row['titre']) . '</h3>';
                        echo '</div>';
                        echo '<p class="text-gray-600 text-sm mb-2">' . htmlspecialchars($row['adresse']) . '</p>';
                        echo '<p class="font-bold text-lg mb-4">' . htmlspecialchars($prix) . '</p>';
                        echo '<a href="logement.php?id=' . $row['id'] . '" class="block text-center bg-black text-white py-3 rounded-lg hover:bg-gray-800">';
                        echo 'Voir détails';
                        echo '</a>';
                        echo '</div>';
                    }
                } else {
                    // Message si aucun logement n'est trouvé
                    echo '<div class="col-span-3 text-center py-8">';
                    echo '<p class="text-lg text-gray-600">Aucun logement ne correspond à vos critères de recherche.</p>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-8 space-x-4">
                    <a href="index.php?page=<?php echo max(1, $page - 1); ?>&lieu=<?php echo urlencode($lieu); ?>&date_arrivee=<?php echo urlencode($date_arrivee); ?>&date_depart=<?php echo urlencode($date_depart); ?>&type_logement=<?php echo urlencode($type_logement); ?>&type_sejour=<?php echo urlencode($type_sejour); ?>&prix_maximum=<?php echo urlencode($prix_maximum); ?>"
                       class="px-6 py-2 rounded-lg font-medium text-white bg-black hover:bg-gray-800 transition <?php echo $page <= 1 ? 'disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed' : ''; ?>"
                        <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                        Précédent
                    </a>
                    <a href="index.php?page=<?php echo min($total_pages, $page + 1); ?>&lieu=<?php echo urlencode($lieu); ?>&date_arrivee=<?php echo urlencode($date_arrivee); ?>&date_depart=<?php echo urlencode($date_depart); ?>&type_logement=<?php echo urlencode($type_logement); ?>&type_sejour=<?php echo urlencode($type_sejour); ?>&prix_maximum=<?php echo urlencode($prix_maximum); ?>"
                       class="px-6 py-2 rounded-lg font-medium text-white bg-black hover:bg-gray-800 transition <?php echo $page >= $total_pages ? 'disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed' : ''; ?>"
                        <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                        Suivant
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Appel à l'action -->
<section class="bg-black text-white py-12 px-4">
    <div class="container mx-auto max-w-3xl text-center">
        <h2 class="text-2xl font-bold mb-4">Vous avez un logement à proposer ?</h2>
        <p class="text-sm mb-6 opacity-90">Partagez votre logement avec la communauté Omnes et gagnez de l'argent</p>
        <a href="publier.php" class="bg-white text-black font-medium py-2 px-6 rounded-lg border border-white hover:bg-gray-100 inline-flex items-center shadow-md">
            <i class="fas fa-plus mr-2"></i> Publier un logement
        </a>
        <div class="mt-12 pt-4 border-t border-gray-700 mx-auto w-full"></div>
        <div class="text-center text-gray-400 text-xs mt-4">
            &copy; 2025 OmnesBnB. Tous droits réservés.
        </div>
    </div>
</section>

<!-- Script JavaScript -->
<script>
    document.getElementById('menu-burger').addEventListener('click', function () {
        document.getElementById('menu-mobile').classList.toggle('hidden');
    });
</script>

</body>
</html>