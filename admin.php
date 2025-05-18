<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification que l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: connexion.php?error=Accès interdit. Vous devez être administrateur pour accéder à cette page.');
    exit;
}

// Génération du token CSRF
$csrf_token = csrf_token();

// Récupération des messages de succès ou d'erreur
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Récupération des données pour l'affichage
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Récupération des statistiques pour le tableau de bord
$stats = [
    'users_count' => 0,
    'logements_count' => 0,
    'reservations_count' => 0,
    'reservations_pending' => 0,
    'new_users_this_month' => 0
];

// Compter le nombre d'utilisateurs
$query = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
global $conn;
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['users_count'] = $row['count'];
}

// Compter le nombre de logements
$query = "SELECT COUNT(*) as count FROM logements";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['logements_count'] = $row['count'];
}

// Compter le nombre de réservations
$query = "SELECT COUNT(*) as count FROM reservations";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['reservations_count'] = $row['count'];
}

// Compter le nombre de réservations en attente
$query = "SELECT COUNT(*) as count FROM reservations WHERE statut = 'en attente'";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['reservations_pending'] = $row['count'];
}

// Compter le nombre de nouveaux utilisateurs ce mois-ci
$query = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0 AND MONTH(date_creation) = MONTH(CURRENT_DATE()) AND YEAR(date_creation) = YEAR(CURRENT_DATE())";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['new_users_this_month'] = $row['count'];
}

// Récupération des listes selon la section
$items = []; // Contiendra les utilisateurs, logements ou réservations selon la section

switch ($section) {
    case 'users':
        $query = "SELECT * FROM users ORDER BY date_creation DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        break;

    case 'logements':
        $query = "SELECT l.*, u.prenom, u.nom FROM logements l JOIN users u ON l.user_id = u.id ORDER BY l.date_creation DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        break;

    case 'reservations':
        $query = "SELECT r.*, l.titre as logement_titre, u.prenom as locataire_prenom, u.nom as locataire_nom, 
                 up.prenom as proprietaire_prenom, up.nom as proprietaire_nom
                 FROM reservations r 
                 JOIN logements l ON r.logement_id = l.id
                 JOIN users u ON r.user_id = u.id
                 JOIN users up ON l.user_id = up.id
                 ORDER BY r.date_reservation DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        break;

    default:
        // Pour le dashboard, on ne fait rien de plus
        break;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - OmnesBnB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex min-h-screen">
    <!-- Sidebar -->
    <div class="w-64 bg-black text-white">
        <div class="p-4">
            <div class="text-2xl font-bold mb-6">Admin OmnesBnB</div>
            <nav class="space-y-2">
                <a href="admin.php" class="block py-2 px-4 rounded hover:bg-gray-800 <?php echo $section === 'dashboard' ? 'bg-gray-800' : ''; ?>">
                    <i class="fas fa-tachometer-alt mr-2"></i> Tableau de bord
                </a>
                <a href="admin.php?section=users" class="block py-2 px-4 rounded hover:bg-gray-800 <?php echo $section === 'users' ? 'bg-gray-800' : ''; ?>">
                    <i class="fas fa-users mr-2"></i> Utilisateurs
                </a>
                <a href="admin.php?section=logements" class="block py-2 px-4 rounded hover:bg-gray-800 <?php echo $section === 'logements' ? 'bg-gray-800' : ''; ?>">
                    <i class="fas fa-home mr-2"></i> Logements
                </a>
                <a href="admin.php?section=reservations" class="block py-2 px-4 rounded hover:bg-gray-800 <?php echo $section === 'reservations' ? 'bg-gray-800' : ''; ?>">
                    <i class="fas fa-calendar-check mr-2"></i> Réservations
                </a>
            </nav>
        </div>
        <div class="mt-auto p-4 border-t border-gray-700">
            <a href="index.php" class="block py-2 px-4 rounded hover:bg-gray-800">
                <i class="fas fa-external-link-alt mr-2"></i> Voir le site
            </a>
            <a href="deconnexion.php" class="block py-2 px-4 rounded hover:bg-gray-800 text-red-400">
                <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex-1 overflow-auto">
        <!-- Top bar -->
        <div class="bg-white shadow p-4 flex justify-between items-center">
            <h1 class="text-xl font-semibold">
                <?php
                switch ($section) {
                    case 'users':
                        echo 'Gestion des utilisateurs';
                        break;
                    case 'logements':
                        echo 'Gestion des logements';
                        break;
                    case 'reservations':
                        echo 'Gestion des réservations';
                        break;
                    default:
                        echo 'Tableau de bord';
                        break;
                }
                ?>
            </h1>
            <div class="text-sm">
                Connecté en tant qu'<span class="font-semibold">administrateur</span>
            </div>
        </div>

        <!-- Messages de succès/erreur -->
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative m-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative m-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Content area -->
        <div class="p-6">
            <?php if ($section === 'dashboard'): ?>
                <!-- Dashboard cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Utilisateurs</div>
                                <div class="text-2xl font-semibold"><?php echo $stats['users_count']; ?></div>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-green-500">
                            <i class="fas fa-arrow-up"></i> <?php echo $stats['new_users_this_month']; ?> ce mois-ci
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                                <i class="fas fa-home"></i>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Logements</div>
                                <div class="text-2xl font-semibold"><?php echo $stats['logements_count']; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-500 mr-4">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Réservations</div>
                                <div class="text-2xl font-semibold"><?php echo $stats['reservations_count']; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">En attente</div>
                                <div class="text-2xl font-semibold"><?php echo $stats['reservations_pending']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Actions rapides</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="admin.php?section=users" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded text-center">
                            Gérer les utilisateurs
                        </a>
                        <a href="admin.php?section=logements" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded text-center">
                            Gérer les logements
                        </a>
                        <a href="admin.php?section=reservations" class="bg-purple-500 hover:bg-purple-600 text-white py-2 px-4 rounded text-center">
                            Gérer les réservations
                        </a>
                    </div>
                </div>

                <!-- Latest activity -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Notes</h2>
                    <p class="text-gray-700">
                        Bienvenue dans le panneau d'administration d'OmnesBnB. Vous pouvez gérer les utilisateurs, logements et réservations à partir de cette interface.
                    </p>
                </div>

            <?php elseif ($section === 'users'): ?>
                <!-- Users section -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 border-b flex justify-between items-center">
                        <h2 class="text-xl font-semibold">Liste des utilisateurs</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $user): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $user['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <?php if (!empty($user['photo'])): ?>
                                                        <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($user['photo']); ?>" alt="">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                            <i class="fas fa-user text-gray-400"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['telephone']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['statut']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['campus']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            $date = new DateTime($user['date_creation']);
                                            echo $date->format('d/m/Y H:i');
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form action="process_admin.php" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                            </form>
                                            <?php if ($user['active'] == 0): ?>
                                                <form action="process_admin.php" method="POST" class="inline ml-3">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="action" value="activate_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="text-green-600 hover:text-green-900">Activer</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Aucun utilisateur trouvé</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($section === 'logements'): ?>
                <!-- Logements section -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 border-b flex justify-between items-center">
                        <h2 class="text-xl font-semibold">Liste des logements</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix (mois)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Propriétaire</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de création</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $logement): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $logement['id']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($logement['titre']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($logement['type']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($logement['adresse']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($logement['prix_mois']); ?>€</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($logement['prenom'] . ' ' . $logement['nom']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            $date = new DateTime($logement['date_creation']);
                                            echo $date->format('d/m/Y H:i');
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="logement.php?id=<?php echo $logement['id']; ?>" class="text-blue-600 hover:text-blue-900" target="_blank">Voir</a>
                                            <form action="process_admin.php" method="POST" class="inline ml-3" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce logement ?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="action" value="delete_logement">
                                                <input type="hidden" name="logement_id" value="<?php echo $logement['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Aucun logement trouvé</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($section === 'reservations'): ?>
            <!-- Reservations section -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Liste des réservations</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Locataire</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Propriétaire</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $reservation): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $reservation['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($reservation['logement_titre']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($reservation['locataire_prenom'] . ' ' . $reservation['locataire_nom']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($reservation['proprietaire_prenom'] . ' ' . $reservation['proprietaire_nom']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php
                                $date_arrivee = new DateTime($reservation['date_arrivee']);
                                $date_depart = new DateTime($reservation['date_depart']);
                                echo $date_arrivee->format('d/m/Y') . ' - ' . $date_depart->format('d/m/Y');
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($reservation['prix_total']); ?>€</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_class = '';
                                switch($reservation['statut']) {
                                    case 'en attente':
                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'acceptée':
                                        $status_class = 'bg-green-100 text-green-800';
                                        break;
                                    case 'refusée':
                                        $status_class = 'bg-red-100 text-red-800';
                                        break;
                                    case 'annulée':
                                        $status_class = 'bg-gray-100 text-gray-800';
                                        break;
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($reservation['statut']); ?>
                                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if ($reservation['statut'] === 'en attente'): ?>
                                <form action="process_admin.php" method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="update_reservation">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <input type="hidden" name="statut" value="acceptée">
                                    <button type="submit" class="text-green-600 hover:text-green-900">Accepter</button>
                                </form>
                                    <form action="process_admin.php" method="POST" class="inline ml-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="update_reservation">
                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                        <input type="hidden" name="statut" value="refusée">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Refuser</button>
                                    </form>
                                <?php endif; ?>
                                <form action="process_admin.php" method="POST" class="inline ml-3" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="delete_reservation">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Aucune réservation trouvée</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>