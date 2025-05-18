<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?login_error=Vous devez être connecté pour accéder à cette page');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les messages de succès/erreur
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Récupérer les logements dont l'utilisateur est propriétaire
$query_proprietaire = "SELECT l.*, p.photo_url as photo_principale,
                      r.id as reservation_id, r.date_arrivee, r.date_depart, r.statut as statut_reservation,
                      u.prenom as locataire_prenom, u.nom as locataire_nom, u.email as locataire_email, u.telephone as locataire_telephone
                      FROM logements l
                      LEFT JOIN (SELECT * FROM photos WHERE is_main = 1) p ON l.id = p.logement_id
                      LEFT JOIN reservations r ON l.id = r.logement_id AND r.statut != 'annulée'
                      LEFT JOIN users u ON r.user_id = u.id
                      WHERE l.user_id = ?
                      ORDER BY l.date_creation DESC";
global $conn;
$stmt_proprietaire = mysqli_prepare($conn, $query_proprietaire);
mysqli_stmt_bind_param($stmt_proprietaire, "i", $user_id);
mysqli_stmt_execute($stmt_proprietaire);
$result_proprietaire = mysqli_stmt_get_result($stmt_proprietaire);
$mes_logements = mysqli_fetch_all($result_proprietaire, MYSQLI_ASSOC);

// Regrouper les logements par ID (pour éviter les doublons dus aux réservations multiples)
$logements_proprietaire = [];
foreach ($mes_logements as $logement) {
    $id = $logement['id'];
    if (!isset($logements_proprietaire[$id])) {
        $logements_proprietaire[$id] = [
            'id' => $logement['id'],
            'titre' => $logement['titre'],
            'adresse' => $logement['adresse'],
            'type' => $logement['type'],
            'prix_nuit' => $logement['prix_nuit'],
            'prix_semaine' => $logement['prix_semaine'],
            'prix_mois' => $logement['prix_mois'],
            'prix_annee' => $logement['prix_annee'],
            'photo_principale' => $logement['photo_principale'],
            'reservations' => []
        ];
    }

    // Ajouter la réservation si elle existe
    if ($logement['reservation_id']) {
        $logements_proprietaire[$id]['reservations'][] = [
            'id' => $logement['reservation_id'],
            'date_arrivee' => $logement['date_arrivee'],
            'date_depart' => $logement['date_depart'],
            'statut' => $logement['statut_reservation'],
            'locataire_prenom' => $logement['locataire_prenom'],
            'locataire_nom' => $logement['locataire_nom'],
            'locataire_email' => $logement['locataire_email'],
            'locataire_telephone' => $logement['locataire_telephone']
        ];
    }
}

// Récupérer les logements que l'utilisateur a réservés
$query_locataire = "SELECT r.*, r.id as reservation_id, r.statut as statut_reservation,
                    l.id as logement_id, l.titre, l.adresse, l.type, l.prix_nuit, l.prix_semaine, l.prix_mois, l.prix_annee,
                    p.photo_url as photo_principale,
                    u.prenom as proprietaire_prenom, u.nom as proprietaire_nom, u.email as proprietaire_email, u.telephone as proprietaire_telephone
                    FROM reservations r
                    JOIN logements l ON r.logement_id = l.id
                    LEFT JOIN (SELECT * FROM photos WHERE is_main = 1) p ON l.id = p.logement_id
                    JOIN users u ON l.user_id = u.id
                    WHERE r.user_id = ? AND r.statut != 'annulée'
                    ORDER BY r.date_reservation DESC";
$stmt_locataire = mysqli_prepare($conn, $query_locataire);
mysqli_stmt_bind_param($stmt_locataire, "i", $user_id);
mysqli_stmt_execute($stmt_locataire);
$result_locataire = mysqli_stmt_get_result($stmt_locataire);
$reservations = mysqli_fetch_all($result_locataire, MYSQLI_ASSOC);

// Génération d'un token CSRF
$csrf_token = csrf_token();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mes locations - OmnesBnB</title>
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
                <a href="recherche.php" class="text-sm text-black hover:text-black">Chercher</a>
                <a href="publier.php" class="text-sm text-black hover:text-black">Publier</a>
                <a href="mes-locations.php" class="text-sm text-black hover:text-black">Mes locations</a>
                <div class="h-8 w-px mx-3 border-l-2 border-black"></div>
                <a href="profil.php" class="text-sm bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800">Mon Profil</a>
            </nav>
            <div class="md:hidden">
                <button id="menu-burger" class="flex items-center p-2 rounded-lg border-2 border-black">
                    <i class="fas fa-bars text-gray-700"></i>
                </button>
            </div>
        </div>
        <div id="menu-mobile" class="md:hidden hidden py-3">
            <a href="recherche.php" class="block py-2 text-sm text-black font-medium text-center">Chercher</a>
            <a href="publier.php" class="block py-2 text-sm text-black font-medium text-center">Publier</a>
            <a href="mes-locations.php" class="block py-2 text-sm text-black font-medium text-center">Mes locations</a>
            <div class="w-4/5 mx-auto border-t-2 border-black my-3"></div>
            <a href="profil.php" class="block text-sm bg-black text-white py-2 px-6 rounded-lg text-center hover:bg-gray-800">Mon Profil</a>
        </div>
    </div>
</header>

<!-- Messages de succès/erreur -->
<?php if (!empty($success)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4 mb-0 max-w-3xl mx-auto">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4 mb-0 max-w-3xl mx-auto">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Accroche + Formulaire -->
<section class="flex flex-col items-center justify-center bg-black py-12 px-4">
    <div class="w-full flex flex-col items-center justify-center">
        <h1 class="text-white text-4xl md:text-5xl font-bold mb-3 text-center leading-tight">Mes locations</h1>
        <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">Gérez vos réservations et vos logements.</p>
    </div>
</section>

<!-- Système d'onglets -->
<div class="flex border-b border-gray-200 mt-8 px-4 max-w-6xl mx-auto">
    <button class="onglet-btn px-4 py-2 font-medium border-b-2 border-black text-black" data-target="logements-loues">
        Mes logements loués
    </button>
    <button class="onglet-btn px-4 py-2 font-medium border-b-2 border-transparent text-gray-500 hover:text-black" data-target="reservations">
        Mes réservations
    </button>
</div>

<!-- Contenu des onglets -->
<section class="pb-10 pt-5 px-4 bg-white">
    <div class="container mx-auto max-w-6xl">
        <!-- Onglet Mes logements loués -->
        <div id="logements-loues" class="onglet-contenu bg-white border-2 border-black rounded-lg p-8 shadow-sm">
            <h2 class="text-2xl font-bold mb-8">Mes logements</h2>

            <?php if (count($logements_proprietaire) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                    <?php foreach ($logements_proprietaire as $logement): ?>
                        <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                            <!-- Image du logement -->
                            <?php if (!empty($logement['photo_principale'])): ?>
                                <img src="<?php echo htmlspecialchars($logement['photo_principale']); ?>" alt="<?php echo htmlspecialchars($logement['titre']); ?>" class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-500">Pas de photo</span>
                                </div>
                            <?php endif; ?>

                            <!-- Informations du logement -->
                            <div class="p-4">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($logement['titre']); ?></h3>
                                    <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($logement['type']); ?></span>
                                </div>
                                <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($logement['adresse']); ?></p>

                                <!-- Prix selon différentes durées -->
                                <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
                                    <div class="bg-gray-50 p-2 rounded">
                                        <p class="text-gray-500">Nuit</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($logement['prix_nuit']); ?>€</p>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <p class="text-gray-500">Semaine</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($logement['prix_semaine']); ?>€</p>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <p class="text-gray-500">Mois</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($logement['prix_mois']); ?>€</p>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <p class="text-gray-500">Année</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($logement['prix_annee']); ?>€</p>
                                    </div>
                                </div>

                                <!-- Réservations -->
                                <?php if (count($logement['reservations']) > 0): ?>
                                    <div class="mb-4">
                                        <h4 class="font-medium text-sm mb-2">Réservations :</h4>
                                        <div class="space-y-2">
                                            <?php foreach ($logement['reservations'] as $reservation): ?>
                                                <div class="bg-blue-50 p-3 rounded text-sm">
                                                    <div class="flex justify-between items-center mb-1">
                                                        <span class="font-medium"><?php echo htmlspecialchars($reservation['locataire_prenom'] . ' ' . $reservation['locataire_nom']); ?></span>
                                                        <?php
                                                        $statut_class = 'bg-gray-100 text-gray-800';
                                                        if ($reservation['statut'] == 'acceptée') {
                                                            $statut_class = 'bg-green-100 text-green-800';
                                                        } elseif ($reservation['statut'] == 'en attente') {
                                                            $statut_class = 'bg-yellow-100 text-yellow-800';
                                                        } elseif ($reservation['statut'] == 'refusée') {
                                                            $statut_class = 'bg-red-100 text-red-800';
                                                        }
                                                        ?>
                                                        <span class="<?php echo $statut_class; ?> text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($reservation['statut']); ?></span>
                                                    </div>
                                                    <div class="text-gray-600 text-xs">
                                                        <?php
                                                        $date_debut = new DateTime($reservation['date_arrivee']);
                                                        $date_fin = new DateTime($reservation['date_depart']);
                                                        echo $date_debut->format('d/m/Y') . ' au ' . $date_fin->format('d/m/Y');
                                                        ?>
                                                    </div>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        <?php if ($reservation['statut'] == 'en attente'): ?>
                                                            <form action="process_accept_refus.php" method="POST" class="inline">
                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                                <input type="hidden" name="action" value="accepter">
                                                                <button type="submit" class="bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-green-700">
                                                                    Accepter
                                                                </button>
                                                            </form>
                                                            <form action="process_accept_refus.php" method="POST" class="inline">
                                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                                <input type="hidden" name="action" value="refuser">
                                                                <button type="submit" class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-red-700">
                                                                    Refuser
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($reservation['statut'] == 'acceptée'): ?>
                                                            <div class="flex flex-col w-full">
                                                                <p class="text-xs mb-1">Coordonnées du locataire :</p>
                                                                <p class="text-xs"><?php echo htmlspecialchars($reservation['locataire_email']); ?></p>
                                                                <p class="text-xs"><?php echo htmlspecialchars($reservation['locataire_telephone']); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-gray-50 p-3 rounded text-sm mb-4">
                                        <p class="text-gray-500">Aucune réservation pour ce logement.</p>
                                    </div>
                                <?php endif; ?>

                                <!-- Boutons d'action -->
                                <div class="flex space-x-2">
                                    <a href="logement.php?id=<?php echo $logement['id']; ?>" class="text-center bg-black text-white py-2 rounded-lg hover:bg-gray-800 flex-1">
                                        Voir détails
                                    </a>
                                    <a href="publier.php?manage=1" class="text-center bg-black text-white py-2 rounded-lg hover:bg-gray-800 flex-1">
                                        Gérer
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-6 bg-gray-50 rounded-lg">
                    <p class="text-gray-600 mb-4">Vous n'avez pas encore publié de logement.</p>
                    <a href="publier.php" class="bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800 inline-block">
                        Publier un logement
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Mes réservations -->
        <div id="reservations" class="onglet-contenu hidden bg-white border-2 border-black rounded-lg p-8 shadow-sm">
            <h2 class="text-2xl font-bold mb-8">Mes réservations</h2>

            <?php if (count($reservations) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                    <?php foreach ($reservations as $reservation): ?>
                        <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                            <!-- Image du logement -->
                            <?php if (!empty($reservation['photo_principale'])): ?>
                                <img src="<?php echo htmlspecialchars($reservation['photo_principale']); ?>" alt="<?php echo htmlspecialchars($reservation['titre']); ?>" class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-500">Pas de photo</span>
                                </div>
                            <?php endif; ?>

                            <!-- Informations de la réservation -->
                            <div class="p-4">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($reservation['titre']); ?></h3>
                                    <?php
                                    $statut_class = 'bg-gray-100 text-gray-800';
                                    if ($reservation['statut_reservation'] == 'acceptée') {
                                        $statut_class = 'bg-green-100 text-green-800';
                                    } elseif ($reservation['statut_reservation'] == 'en attente') {
                                        $statut_class = 'bg-yellow-100 text-yellow-800';
                                    } elseif ($reservation['statut_reservation'] == 'refusée') {
                                        $statut_class = 'bg-red-100 text-red-800';
                                    }
                                    ?>
                                    <span class="<?php echo $statut_class; ?> text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($reservation['statut_reservation']); ?></span>
                                </div>
                                <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($reservation['adresse']); ?></p>

                                <!-- Dates et prix -->
                                <div class="bg-gray-50 p-3 rounded mb-4 text-sm">
                                    <?php
                                    $date_debut = new DateTime($reservation['date_arrivee']);
                                    $date_fin = new DateTime($reservation['date_depart']);

                                    // Calcul de la durée
                                    $interval = $date_debut->diff($date_fin);
                                    $jours = $interval->days;

                                    // Affichage de la durée
                                    if ($jours >= 365) {
                                        $annees = floor($jours / 365);
                                        $texte_duree = $annees . " an" . ($annees > 1 ? "s" : "");
                                    } elseif ($jours >= 30) {
                                        $mois = floor($jours / 30);
                                        $texte_duree = $mois . " mois";
                                    } elseif ($jours >= 7) {
                                        $semaines = floor($jours / 7);
                                        $texte_duree = $semaines . " semaine" . ($semaines > 1 ? "s" : "");
                                    } else {
                                        $texte_duree = $jours . " jour" . ($jours > 1 ? "s" : "");
                                    }
                                    ?>
                                    <div class="flex justify-between mb-1">
                                        <span>Dates</span>
                                        <span><?php echo $date_debut->format('d/m/Y') . ' au ' . $date_fin->format('d/m/Y'); ?></span>
                                    </div>
                                    <div class="flex justify-between mb-1">
                                        <span>Durée</span>
                                        <span><?php echo $texte_duree; ?></span>
                                    </div>
                                    <div class="flex justify-between font-medium">
                                        <span>Prix total</span>
                                        <span><?php echo htmlspecialchars($reservation['prix_total']); ?>€</span>
                                    </div>
                                </div>

                                <!-- Informations du propriétaire -->
                                <?php if ($reservation['statut_reservation'] == 'acceptée'): ?>
                                    <div class="bg-blue-50 p-3 rounded mb-4 text-sm">
                                        <p class="font-medium mb-1">Propriétaire</p>
                                        <p><?php echo htmlspecialchars($reservation['proprietaire_prenom'] . ' ' . $reservation['proprietaire_nom']); ?></p>
                                        <p><?php echo htmlspecialchars($reservation['proprietaire_email']); ?></p>
                                        <p><?php echo htmlspecialchars($reservation['proprietaire_telephone']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Boutons d'action -->
                                <div class="flex space-x-2">
                                    <a href="logement.php?id=<?php echo $reservation['logement_id']; ?>" class="text-center bg-black text-white py-2 rounded-lg hover:bg-gray-800 flex-1">
                                        Voir logement
                                    </a>
                                    <?php if ($reservation['statut_reservation'] == 'en attente'): ?>
                                        <form action="process_reservation.php" method="POST" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                            <input type="hidden" name="action" value="annuler">
                                            <button type="submit" class="w-full text-center bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">
                                                Annuler
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-6 bg-gray-50 rounded-lg">
                    <p class="text-gray-600 mb-4">Vous n'avez pas encore effectué de réservation.</p>
                    <a href="index.php" class="bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800 inline-block">
                        Chercher un logement
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

    // Gestion des onglets
    document.querySelectorAll('.onglet-btn').forEach(function(tab) {
        tab.addEventListener('click', function() {
            // Masquer tous les contenus d'onglets
            document.querySelectorAll('.onglet-contenu').forEach(function(content) {
                content.classList.add('hidden');
            });

            // Réinitialiser le style de tous les boutons d'onglets
            document.querySelectorAll('.onglet-btn').forEach(function(btn) {
                btn.classList.remove('border-black', 'text-black');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            // Afficher le contenu de l'onglet sélectionné
            const target = this.getAttribute('data-target');
            document.getElementById(target).classList.remove('hidden');

            // Mettre à jour le style du bouton sélectionné
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-black', 'text-black');
        });
    });

    // Vérifier si l'URL contient un paramètre pour préselectionner un onglet
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab === 'reservations') {
        document.querySelector('[data-target="reservations"]').click();
    }
</script>

</body>
</html>