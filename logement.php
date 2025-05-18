<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification du paramètre id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=ID de logement invalide');
    exit;
}

$logement_id = intval($_GET['id']);

// Récupération des informations du logement
$query = "SELECT l.*, u.id as proprietaire_id, u.prenom, u.nom, u.email, u.telephone, u.photo, u.statut, u.campus, u.description as user_description 
          FROM logements l 
          JOIN users u ON l.user_id = u.id 
          WHERE l.id = ?";
global $conn;
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $logement_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header('Location: index.php?error=Logement non trouvé');
    exit;
}

$logement = mysqli_fetch_assoc($result);

// Récupérer les photos du logement
$query_photos = "SELECT * FROM photos WHERE logement_id = ? ORDER BY is_main DESC";
$stmt_photos = mysqli_prepare($conn, $query_photos);
mysqli_stmt_bind_param($stmt_photos, "i", $logement_id);
mysqli_stmt_execute($stmt_photos);
$result_photos = mysqli_stmt_get_result($stmt_photos);
$photos = mysqli_fetch_all($result_photos, MYSQLI_ASSOC);

// Vérifier si le logement est déjà réservé
$query_reservation = "SELECT * FROM reservations WHERE logement_id = ? AND statut = 'acceptée'";
$stmt_reservation = mysqli_prepare($conn, $query_reservation);
mysqli_stmt_bind_param($stmt_reservation, "i", $logement_id);
mysqli_stmt_execute($stmt_reservation);
$result_reservation = mysqli_stmt_get_result($stmt_reservation);
$is_reserved = mysqli_num_rows($result_reservation) > 0;

// Vérifier si l'utilisateur est connecté
$user_connected = isset($_SESSION['user_id']);
$is_owner = $user_connected && $_SESSION['user_id'] == $logement['proprietaire_id'];

// Récupérer les messages d'erreur ou de succès
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Générer un token CSRF
$csrf_token = csrf_token();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du logement - OmnesBnB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            max-width: 100%;
            background-color: #FFFFFF;
        }
        .photo-square {
            aspect-ratio: 1 / 1;
            object-fit: cover;
        }
        .photo-rectangle {
            aspect-ratio: 16 / 9;
            object-fit: cover;
        }
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
    </style>
</head>
<body class="bg-white">
<!-- En-tête principal avec navigation responsive -->
<header class="bg-white sticky top-0 z-50 border-b-2 border-black shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="index.php" class="text-black font-bold text-xl">OmnesBnB</a>
            <!-- Navigation principale (PC) -->
            <nav class="hidden md:flex items-center space-x-6">
                <a href="index.php" class="text-sm text-black hover:text-black">Chercher</a>
                <a href="publier.php" class="text-sm text-black hover:text-black">Publier</a>
                <a href="mes-locations.php" class="text-sm text-black hover:text-black">Mes locations</a>
                <!-- Barre verticale de séparation -->
                <div class="h-8 w-px mx-3 border-l-2 border-black"></div>
                <!-- Bouton Connexion / Inscription ou Mon Profil -->
                <?php if($user_connected): ?>
                    <a href="profil.php" class="text-sm bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800">
                        Mon Profil
                    </a>
                <?php else: ?>
                    <a href="connexion.php" class="text-sm bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800">
                        Connexion / Inscription
                    </a>
                <?php endif; ?>
            </nav>
            <!-- Menu burger mobile -->
            <div class="md:hidden">
                <button id="menu-burger" class="flex items-center p-2 rounded-lg border-2 border-black">
                    <i class="fas fa-bars text-gray-700"></i>
                </button>
            </div>
        </div>
        <!-- Menu mobile (affichage dynamique) -->
        <div id="menu-mobile" class="md:hidden hidden py-3">
            <a href="index.php" class="block py-2 text-sm text-black font-medium text-center">Chercher</a>
            <a href="publier.php" class="block py-2 text-sm text-black font-medium text-center">Publier</a>
            <a href="mes-locations.php" class="block py-2 text-sm text-black font-medium text-center">Mes locations</a>
            <!-- Barre horizontale de séparation mobile -->
            <div class="w-4/5 mx-auto border-t-2 border-black my-3"></div>
            <!-- Bouton Connexion / Inscription ou Mon Profil (mobile) -->
            <?php if($user_connected): ?>
                <a href="profil.php" class="block text-sm bg-black text-white py-2 px-6 rounded-lg text-center hover:bg-gray-800">
                    Mon Profil
                </a>
            <?php else: ?>
                <a href="connexion.php" class="block text-sm bg-black text-white py-2 px-6 rounded-lg text-center hover:bg-gray-800">
                    Connexion / Inscription
                </a>
            <?php endif; ?>
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

<!-- Section d'en-tête noir avec le titre -->
<section class="flex flex-col items-center justify-center bg-black py-12 px-4">
    <div class="w-full flex flex-col items-center justify-center">
        <h1 class="text-white text-4xl md:text-5xl font-bold mb-3 text-center leading-tight">
            Détails du logement
        </h1>
        <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">
            Découvrez les caractéristiques et disponibilités de ce logement.
        </p>
    </div>
</section>

<!-- Section principale avec les détails du logement -->
<section class="py-8 px-4 bg-white">
    <div class="container mx-auto max-w-5xl">
        <!-- Photos du logement -->
        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <?php if (count($photos) > 0): ?>
                        <img src="<?php echo htmlspecialchars($photos[0]['photo_url']); ?>" alt="Photo principale du logement" class="w-full rounded-lg h-full max-h-96 object-cover border-2 border-black" />
                    <?php else: ?>
                        <div class="w-full rounded-lg h-full max-h-96 bg-gray-200 border-2 border-black flex items-center justify-center">
                            <span class="text-gray-500">Pas de photo disponible</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-2 md:gap-4">
                    <?php
                    // Afficher les 4 premières photos supplémentaires
                    $additional_photos = array_slice($photos, 1, 4);
                    $remaining_photos = count($photos) - 5;

                    // Remplir avec des placeholders si moins de 4 photos disponibles
                    while (count($additional_photos) < 4) {
                        $additional_photos[] = ['photo_url' => '', 'is_empty' => true];
                    }

                    foreach ($additional_photos as $index => $photo):
                        if (isset($photo['is_empty']) && $photo['is_empty']):
                            ?>
                            <div class="w-full h-40 md:h-44 rounded-lg bg-gray-200 border-2 border-black flex items-center justify-center">
                                <span class="text-gray-500">Pas de photo</span>
                            </div>
                        <?php else: ?>
                            <?php if ($index == 3 && $remaining_photos > 0): ?>
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($photo['photo_url']); ?>" alt="Photo supplémentaire" class="w-full h-40 md:h-44 rounded-lg object-cover border-2 border-black" />
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-white font-medium rounded-lg">
                                        <span>+<?php echo $remaining_photos; ?> photos</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($photo['photo_url']); ?>" alt="Photo supplémentaire" class="w-full h-40 md:h-44 rounded-lg object-cover border-2 border-black" />
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Informations principales -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <!-- Colonne de gauche: Infos logement -->
            <div class="md:col-span-2 space-y-6">
                <div class="bg-white border-2 border-black rounded-lg p-6 shadow-sm">
                    <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($logement['titre']); ?></h2>
                    <div class="flex items-center mb-2">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt text-gray-500 mr-1"></i>
                            <span class="text-gray-700"><?php echo htmlspecialchars($logement['adresse']); ?></span>
                        </div>
                    </div>
                    <p class="text-lg font-bold"><?php echo htmlspecialchars($logement['prix_mois']); ?>€ <span class="text-gray-500 font-normal text-sm">/ mois</span></p>

                    <hr class="my-4 border-t-2 border-gray-200" />

                    <div class="flex flex-col md:flex-row md:items-center mb-4">
                        <div class="flex items-center mb-2 md:mb-0 md:mr-4">
                            <div class="bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto mt-4 max-w-2xl flex flex-col items-center animate-fade-in">
                                <div class="flex flex-col items-center w-full">
                                    <div class="w-28 h-28 rounded-xl overflow-hidden border-2 border-black bg-gray-100 flex items-center justify-center mb-4">
                                        <?php if (!empty($logement['photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($logement['photo']); ?>" alt="Photo de profil" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-5xl text-gray-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h2 class="text-2xl font-bold mb-2 text-center"><?php echo htmlspecialchars($logement['prenom'] . ' ' . $logement['nom']); ?></h2>
                                    <?php if ($user_connected && !$is_owner): ?>
                                        <div class="flex flex-wrap justify-center items-center text-gray-800 text-lg gap-x-3 gap-y-2 mb-1">
                                            <span><?php echo htmlspecialchars($logement['email']); ?></span>
                                            <span class="text-gray-400">|</span>
                                            <span><?php echo htmlspecialchars($logement['telephone']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex flex-wrap justify-center items-center text-gray-700 text-lg gap-x-3 gap-y-2 mb-1">
                                        <span><?php echo htmlspecialchars($logement['statut']); ?></span>
                                        <span class="text-gray-400">|</span>
                                        <span><?php echo htmlspecialchars($logement['campus']); ?></span>
                                    </div>
                                    <div class="mt-3 text-center text-gray-600 text-base w-full">
                                        <?php echo htmlspecialchars($logement['user_description']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 border-t-2 border-gray-200" />

                    <!-- Description -->
                    <h3 class="text-xl font-bold mb-3">Description</h3>
                    <p class="text-gray-700 mb-6">
                        <?php echo nl2br(htmlspecialchars($logement['description'])); ?>
                    </p>

                    <!-- Adresse -->
                    <h3 class="text-xl font-bold mb-3">Adresse</h3>
                    <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($logement['adresse']); ?></p>
                    <div class="w-full h-48 bg-gray-200 rounded-lg mb-6">
                        <!-- Placeholder pour Google Maps, à remplacer par iframe Google Maps -->
                        <div class="w-full h-full flex items-center justify-center bg-gray-200 rounded-lg border-2 border-black">
                            <span class="text-gray-500">Carte non disponible</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite: Formulaire de réservation -->
            <div class="space-y-6">
                <div class="bg-white border-2 border-black rounded-lg p-6 shadow-sm sticky top-24">
                    <h3 class="text-xl font-bold mb-4">Réserver ce logement</h3>

                    <div class="mb-4">
                        <p class="text-2xl font-bold"><?php echo htmlspecialchars($logement['prix_mois']); ?>€ <span class="text-gray-500 font-normal text-sm">/ mois</span></p>
                    </div>

                    <?php if($is_reserved): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded-lg mb-4">
                            <p>Ce logement est déjà réservé.</p>
                        </div>
                    <?php elseif($is_owner): ?>
                        <div class="bg-blue-100 border border-blue-400 text-blue-700 p-4 rounded-lg mb-4">
                            <p>C'est votre logement.</p>
                        </div>
                    <?php else: ?>
                        <form class="space-y-4" action="process_reservation.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="logement_id" value="<?php echo $logement_id; ?>">

                            <div>
                                <label for="date_arrivee" class="block text-sm font-medium text-gray-700 mb-1">Date d'arrivée</label>
                                <input type="date" id="date_arrivee" name="date_arrivee"
                                       class="w-full border-2 border-black rounded-lg py-2 px-4 bg-white text-gray-900 placeholder-gray-400 focus:outline-none" required />
                            </div>
                            <div>
                                <label for="date_depart" class="block text-sm font-medium text-gray-700 mb-1">Date de départ</label>
                                <input type="date" id="date_depart" name="date_depart"
                                       class="w-full border-2 border-black rounded-lg py-2 px-4 bg-white text-gray-900 placeholder-gray-400 focus:outline-none" required />
                            </div>
                            <div>
                                <label for="nb_personnes" class="block text-sm font-medium text-gray-700 mb-1">Nombre de personnes</label>
                                <select id="nb_personnes" name="nb_personnes"
                                        class="w-full border-2 border-black rounded-lg py-2 px-4 bg-white text-gray-900 placeholder-gray-400 focus:outline-none" required>
                                    <option value="1">1 personne</option>
                                    <option value="2">2 personnes</option>
                                    <option value="3">3 personnes</option>
                                    <option value="4">4 personnes</option>
                                </select>
                            </div>

                            <!-- Résumé du prix -->
                            <div class="pt-4 space-y-2">
                                <div class="flex justify-between" id="prix-duree">
                                    <span id="duree-texte">Chargement...</span>
                                    <span id="prix-periode">Chargement...</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Frais de service</span>
                                    <span id="frais-service">Chargement...</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Caution</span>
                                    <span><?php echo htmlspecialchars($logement['caution']); ?>€</span>
                                </div>
                                <div class="pt-2 border-t-2 border-gray-200 flex justify-between font-bold">
                                    <span>Total</span>
                                    <span id="prix-total">Chargement...</span>
                                </div>
                                <input type="hidden" name="prix_total" id="input-prix-total" value="0">
                            </div>

                            <?php if($user_connected): ?>
                                <button type="submit" class="bg-black text-white font-medium py-3 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md">
                                    Réserver
                                </button>
                            <?php else: ?>
                                <a href="connexion.php?redirect=logement.php?id=<?php echo $logement_id; ?>" class="bg-black text-white font-medium py-3 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md block text-center">
                                    Connectez-vous pour réserver
                                </a>
                            <?php endif; ?>

                            <p class="text-xs text-gray-500 text-center">
                                Vous ne serez débité que lorsque le propriétaire aura accepté votre demande.
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
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

<script>
    // Gestion du menu mobile (affichage/caché)
    document.getElementById('menu-burger')?.addEventListener('click', function() {
        const menuMobile = document.getElementById('menu-mobile');
        menuMobile.classList.toggle('hidden');
    });

    // Calcul du prix total en fonction des dates
    const dateArrivee = document.getElementById('date_arrivee');
    const dateDepart = document.getElementById('date_depart');
    const nbPersonnes = document.getElementById('nb_personnes');
    const prixPeriode = document.getElementById('prix-periode');
    const dureeTexte = document.getElementById('duree-texte');
    const fraisService = document.getElementById('frais-service');
    const prixTotal = document.getElementById('prix-total');
    const inputPrixTotal = document.getElementById('input-prix-total');

    // Prix du logement
    const prixNuit = <?php echo $logement['prix_nuit']; ?>;
    const prixSemaine = <?php echo $logement['prix_semaine']; ?>;
    const prixMois = <?php echo $logement['prix_mois']; ?>;
    const prixAnnee = <?php echo $logement['prix_annee']; ?>;
    const caution = <?php echo $logement['caution']; ?>;

    function updatePrix() {
        if (!dateArrivee || !dateDepart || !dateArrivee.value || !dateDepart.value) {
            return;
        }

        const debut = new Date(dateArrivee.value);
        const fin = new Date(dateDepart.value);

        // Validation des dates
        if (fin <= debut) {
            alert('La date de départ doit être après la date d\'arrivée');
            dateDepart.value = '';
            return;
        }

        // Calcul de la durée en jours
        const diffTime = Math.abs(fin - debut);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const diffMonths = diffDays / 30;
        const diffWeeks = diffDays / 7;

        let prix = 0;
        let texte = '';

        // Déterminer le type de séjour (nuit, semaine, mois, année)
        if (diffDays < 7) {
            // Prix à la nuit
            prix = prixNuit * diffDays;
            texte = `${prixNuit}€ x ${diffDays} nuit${diffDays > 1 ? 's' : ''}`;
        } else if (diffDays < 30) {
            // Prix à la semaine
            const weeks = Math.floor(diffWeeks);
            const remainingDays = diffDays - (weeks * 7);

            prix = (prixSemaine * weeks) + (prixNuit * remainingDays);
            texte = `${prixSemaine}€ x ${weeks} semaine${weeks > 1 ? 's' : ''}`;

            if (remainingDays > 0) {
                texte += ` + ${prixNuit}€ x ${remainingDays} jour${remainingDays > 1 ? 's' : ''}`;
            }
        } else if (diffDays < 365) {
            // Prix au mois
            const months = Math.floor(diffMonths);
            const remainingDays = diffDays - (months * 30);

            prix = (prixMois * months) + (prixNuit * remainingDays);
            texte = `${prixMois}€ x ${months} mois`;

            if (remainingDays > 0) {
                texte += ` + ${prixNuit}€ x ${remainingDays} jour${remainingDays > 1 ? 's' : ''}`;
            }
        } else {
            // Prix à l'année
            const years = Math.floor(diffDays / 365);
            const remainingDays = diffDays - (years * 365);

            prix = (prixAnnee * years) + (prixNuit * remainingDays);
            texte = `${prixAnnee}€ x ${years} an${years > 1 ? 's' : ''}`;

            if (remainingDays > 0) {
                texte += ` + ${prixNuit}€ x ${remainingDays} jour${remainingDays > 1 ? 's' : ''}`;
            }
        }

        // Arrondir le prix
        prix = Math.round(prix);

        // Frais de service (5% du prix de la location)
        const frais = Math.round(prix * 0.05);

        // Total (prix + frais + caution)
        const total = prix + frais + caution;

        // Mise à jour de l'affichage
        dureeTexte.textContent = `${diffDays} jour${diffDays > 1 ? 's' : ''}`;
        prixPeriode.textContent = `${prix}€`;
        fraisService.textContent = `${frais}€`;
        prixTotal.textContent = `${total}€`;
        inputPrixTotal.value = total;
    }

    // Événements pour mettre à jour le prix
    if (dateArrivee && dateDepart) {
        dateArrivee.addEventListener('change', updatePrix);
        dateDepart.addEventListener('change', updatePrix);
        nbPersonnes?.addEventListener('change', updatePrix);
    }
</script>
</body>
</html>