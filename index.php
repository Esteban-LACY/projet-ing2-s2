<?php
/**
 * Page d'accueil OmnesBnB
 *
 * Point d'entrée principal de l'application
 *
 * @author OmnesBnB
 */

// Démarrage de la session
session_start();

// Inclusion des fichiers de configuration et fonctions
require_once 'config/config.php';
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// Initialisation des variables
$logementsRecents = [];
$message_erreur = null;

try {
    // Tentative de connexion à la base de données
    $pdo = getConnexionBD();

    // Vérifier si la connexion est établie
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données. Veuillez contacter l'administrateur.");
    }

    // Récupérer les logements récents (limités à 5)
    $requete = $pdo->prepare('
        SELECT l.*, (
            SELECT url FROM photos_logement 
            WHERE id_logement = l.id AND est_principale = 1 
            LIMIT 1
        ) AS photo_principale 
        FROM logements l 
        ORDER BY l.date_creation DESC 
        LIMIT 5
    ');

    $requete->execute();
    $logementsRecents = $requete->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log de l'erreur
    journaliser('Erreur de base de données dans index.php : ' . $e->getMessage(), 'ERROR');
    $message_erreur = MODE_DEVELOPPEMENT ?
        'Erreur de connexion à la base de données: ' . $e->getMessage() :
        'Une erreur de connexion est survenue. Veuillez réessayer plus tard.';
} catch (Exception $e) {
    journaliser('Erreur dans index.php : ' . $e->getMessage(), 'ERROR');
    $message_erreur = MODE_DEVELOPPEMENT ?
        'Erreur: ' . $e->getMessage() :
        'Une erreur est survenue. Veuillez réessayer plus tard.';
}

// Titre de la page
$titre = 'Accueil';

// Inclusion du header
include 'views/commun/header.php';
?>

    <div class="container-mobile mx-auto py-4 mb-safe">
        <?php if ($message_erreur): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <p><?php echo htmlspecialchars($message_erreur); ?></p>
                <?php if (MODE_DEVELOPPEMENT): ?>
                    <p class="mt-2 text-sm">Vérifiez vos paramètres de connexion à la base de données.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- En-tête -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold mb-2">OmnesBnB</h1>
                <p class="text-lg text-gray-600">Plateforme de logements pour les étudiants et le personnel Omnes.</p>
            </div>

            <!-- Formulaire de recherche simplifié -->
            <div class="search-form-home mb-8">
                <form action="recherche.php" method="GET" id="search-form">
                    <div class="mb-4">
                        <label for="lieu" class="sr-only">Lieu</label>
                        <input type="text" id="lieu" name="lieu" placeholder="Lieu" class="input-field address-autocomplete">
                    </div>

                    <div class="flex space-x-4 mb-4">
                        <div class="w-1/2">
                            <label for="date_debut" class="sr-only">Date début</label>
                            <input type="date" id="date_debut" name="date_debut" class="input-field">
                        </div>

                        <div class="w-1/2">
                            <label for="date_fin" class="sr-only">Date fin</label>
                            <input type="date" id="date_fin" name="date_fin" class="input-field">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Rechercher</button>
                </form>
            </div>

            <!-- Logements récents -->
            <div>
                <h2 class="text-2xl font-bold mb-4">Chercher un logement</h2>

                <?php if (empty($logementsRecents)) : ?>
                    <p class="text-gray-600">Aucun logement disponible pour le moment.</p>
                <?php else : ?>
                    <div class="space-y-6">
                        <?php foreach ($logementsRecents as $logement) : ?>
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
                                    <a href="logement/details.php?id=<?php echo $logement['id']; ?>" class="text-black underline mt-2 inline-block">Voir plus ></a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center mt-4">
                            <a href="recherche.php" class="btn-secondary">Voir tous les logements</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Initialisation de Google Maps -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.googleMaps) {
                window.googleMaps.init('<?php echo defined("GOOGLE_MAPS_API_KEY") ? GOOGLE_MAPS_API_KEY : ""; ?>');
            }
        });
    </script>

<?php
// Inclusion du footer
include 'views/commun/footer.php';
?>