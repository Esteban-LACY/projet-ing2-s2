<?php
session_start();
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// Initialisation des variables
$resultats = [];
$filtres = [
    'lieu' => '',
    'date_debut' => '',
    'date_fin' => '',
    'prix_min' => '',
    'prix_max' => '',
    'type_logement' => '',
    'nb_places' => ''
];

// Récupération des paramètres de recherche
foreach ($filtres as $key => $value) {
    if (isset($_GET[$key]) && !empty($_GET[$key])) {
        $filtres[$key] = $_GET[$key];
    }
}

try {
    // Connexion à la base de données
    $pdo = connecterBDD();
    
    // Construction de la requête SQL en fonction des filtres
    $sql = '
        SELECT l.*, (
            SELECT url FROM photos_logement 
            WHERE id_logement = l.id AND est_principale = 1 
            LIMIT 1
        ) AS photo_principale 
        FROM logements l 
        LEFT JOIN disponibilites d ON l.id = d.id_logement
        WHERE 1 = 1
    ';
    
    $params = [];
    
    // Filtre par lieu (ville ou code postal)
    if (!empty($filtres['lieu'])) {
        $sql .= ' AND (l.ville LIKE :lieu OR l.code_postal LIKE :lieu)';
        $params[':lieu'] = '%' . $filtres['lieu'] . '%';
    }
    
    // Filtre par dates de disponibilité
    if (!empty($filtres['date_debut']) && !empty($filtres['date_fin'])) {
        $sql .= ' AND d.date_debut <= :date_fin AND d.date_fin >= :date_debut';
        $params[':date_debut'] = $filtres['date_debut'];
        $params[':date_fin'] = $filtres['date_fin'];
    }
    
    // Filtre par prix minimum
    if (!empty($filtres['prix_min'])) {
        $sql .= ' AND l.prix >= :prix_min';
        $params[':prix_min'] = $filtres['prix_min'];
    }
    
    // Filtre par prix maximum
    if (!empty($filtres['prix_max'])) {
        $sql .= ' AND l.prix <= :prix_max';
        $params[':prix_max'] = $filtres['prix_max'];
    }
    
    // Filtre par type de logement
    if (!empty($filtres['type_logement'])) {
        $sql .= ' AND l.type_logement = :type_logement';
        $params[':type_logement'] = $filtres['type_logement'];
    }
    
    // Filtre par nombre de places
    if (!empty($filtres['nb_places'])) {
        $sql .= ' AND l.nb_places >= :nb_places';
        $params[':nb_places'] = $filtres['nb_places'];
    }
    
    // Grouper par logement pour éviter les doublons
    $sql .= ' GROUP BY l.id';
    
    // Tri par date de création (plus récent d'abord)
    $sql .= ' ORDER BY l.date_creation DESC';
    
    // Préparation et exécution de la requête
    $requete = $pdo->prepare($sql);
    foreach ($params as $param => $value) {
        $requete->bindValue($param, $value);
    }
    $requete->execute();
    
    $resultats = $requete->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Log de l'erreur
    error_log('Erreur dans recherche.php : ' . $e->getMessage());
}

// Si la requête est AJAX, retourner les résultats en JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($resultats);
    exit();
}

// Titre de la page
$titre = 'Rechercher un logement';

// Inclusion du header
include 'views/commun/header.php';
?>

<div class="container-mobile mx-auto py-4 mb-safe">
    <h1 class="text-3xl font-bold mb-4">Rechercher un logement</h1>
    
    <!-- Formulaire de recherche -->
    <div class="mb-6">
        <form action="recherche.php" method="GET" id="search-form" class="mb-4">
            <div class="mb-4">
                <label for="lieu" class="block text-gray-700 mb-2">Lieu</label>
                <input type="text" id="lieu" name="lieu" value="<?php echo htmlspecialchars($filtres['lieu']); ?>" placeholder="Ville ou code postal" class="input-field address-autocomplete">
            </div>
            
            <div class="flex space-x-4 mb-4">
                <div class="w-1/2">
                    <label for="date_debut" class="block text-gray-700 mb-2">Arrivée</label>
                    <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($filtres['date_debut']); ?>" class="input-field">
                </div>
                
                <div class="w-1/2">
                    <label for="date_fin" class="block text-gray-700 mb-2">Départ</label>
                    <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($filtres['date_fin']); ?>" class="input-field">
                </div>
            </div>
            
            <button type="submit" class="btn-primary">Rechercher</button>
        </form>
        
        <!-- Bouton pour afficher/masquer les filtres avancés -->
        <button id="filters-toggle" class="text-black underline">Filtres avancés</button>
        
        <!-- Filtres avancés -->
        <div id="filters-panel" class="mt-4 p-4 bg-gray-100 rounded-md hidden">
            <form action="recherche.php" method="GET" id="advanced-search-form">
                <!-- Copier les champs du formulaire principal pour la soumission -->
                <input type="hidden" name="lieu" value="<?php echo htmlspecialchars($filtres['lieu']); ?>">
                <input type="hidden" name="date_debut" value="<?php echo htmlspecialchars($filtres['date_debut']); ?>">
                <input type="hidden" name="date_fin" value="<?php echo htmlspecialchars($filtres['date_fin']); ?>">
                
                <div class="flex space-x-4 mb-4">
                    <div class="w-1/2">
                        <label for="prix_min" class="block text-gray-700 mb-2">Prix min (€)</label>
                        <input type="number" id="prix_min" name="prix_min" value="<?php echo htmlspecialchars($filtres['prix_min']); ?>" min="0" class="input-field">
                    </div>
                    
                    <div class="w-1/2">
                        <label for="prix_max" class="block text-gray-700 mb-2">Prix max (€)</label>
                        <input type="number" id="prix_max" name="prix_max" value="<?php echo htmlspecialchars($filtres['prix_max']); ?>" min="0" class="input-field">
                    </div>
                </div>
                
                <div class="flex space-x-4 mb-4">
                    <div class="w-1/2">
                        <label for="type_logement" class="block text-gray-700 mb-2">Type de logement</label>
                        <select id="type_logement" name="type_logement" class="input-field">
                            <option value="">Tous les types</option>
                            <option value="entier" <?php echo $filtres['type_logement'] === 'entier' ? 'selected' : ''; ?>>Logement entier</option>
                            <option value="collocation" <?php echo $filtres['type_logement'] === 'collocation' ? 'selected' : ''; ?>>Collocation</option>
                            <option value="libere" <?php echo $filtres['type_logement'] === 'libere' ? 'selected' : ''; ?>>Logement libéré</option>
                        </select>
                    </div>
                    
                    <div class="w-1/2">
                        <label for="nb_places" class="block text-gray-700 mb-2">Nombre de places</label>
                        <input type="number" id="nb_places" name="nb_places" value="<?php echo htmlspecialchars($filtres['nb_places']); ?>" min="1" class="input-field">
                    </div>
                </div>
                
                <button type="submit" class="btn-secondary">Appliquer les filtres</button>
            </form>
        </div>
    </div>
    
    <!-- Affichage des résultats -->
    <div class="mt-6">
        <h2 class="text-2xl font-bold mb-4">Résultats de recherche</h2>
        
        <?php if (empty($resultats)) : ?>
            <p class="text-gray-600">Aucun logement ne correspond à votre recherche.</p>
        <?php else : ?>
            <div id="search-results" class="space-y-6">
                <?php foreach ($resultats as $logement) : ?>
                    <div class="property-card card">
                        <div class="relative">
                            <img 
                                src="<?php echo !empty($logement['photo_principale']) ? 'uploads/logements/' . $logement['photo_principale'] : 'assets/img/placeholders/logement.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($logement['titre']); ?>" 
                                class="property-image"
                            >
                            <div class="absolute bottom-2 right-2 bg-white rounded-full px-2 py-1 text-sm font-semibold">
                                <?php echo $logement['prix']; ?>€ / nuit
                            </div>
                        </div>
                        <div class="property-info">
                            <h3 class="font-semibold"><?php echo htmlspecialchars($logement['titre']); ?></h3>
                            <p class="text-gray-500"><?php echo htmlspecialchars($logement['ville']); ?></p>
                            <p class="text-sm mt-1">
                                <?php 
                                switch ($logement['type_logement']) {
                                    case 'entier':
                                        echo 'Logement entier';
                                        break;
                                    case 'collocation':
                                        echo 'Collocation';
                                        break;
                                    case 'libere':
                                        echo 'Logement libéré';
                                        break;
                                }
                                ?>
                            </p>
                            <a href="logement/details.php?id=<?php echo $logement['id']; ?>" class="btn-primary mt-3">Voir plus</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Initialisation de Google Maps et des fonctionnalités de recherche -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser Google Maps
        if (window.googleMaps) {
            window.googleMaps.init('<?php echo GOOGLE_MAPS_API_KEY; ?>');
        }
        
        // Afficher/masquer les filtres avancés
        const filtersToggle = document.getElementById('filters-toggle');
        const filtersPanel = document.getElementById('filters-panel');
        
        if (filtersToggle && filtersPanel) {
            filtersToggle.addEventListener('click', function() {
                if (filtersPanel.classList.contains('hidden')) {
                    filtersPanel.classList.remove('hidden');
                    filtersToggle.textContent = 'Masquer les filtres';
                } else {
                    filtersPanel.classList.add('hidden');
                    filtersToggle.textContent = 'Filtres avancés';
                }
            });
        }
    });
</script>

<?php
// Inclusion du footer
include 'views/commun/footer.php';
?>
