<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?login_error=Vous devez être connecté pour accéder à cette page');
    exit;
}

// Récupération des informations utilisateur
$user_id = $_SESSION['user_id'];

// Récupération des messages de succès/erreur
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Génération d'un token CSRF
$csrf_token = csrf_token();

// Récupération des logements de l'utilisateur
$query = "SELECT * FROM logements WHERE user_id = ? ORDER BY date_creation DESC";
global $conn;
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$mes_logements = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publier un logement - OmnesBnB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fff; }
        .container-publier { max-width: 1100px; margin: auto; }
        .transition { transition: all .2s; }
        .onglet-header { border: none !important; box-shadow: none !important; }
        @media (max-width: 768px) {
            .container-publier { max-width: 100%; padding: 0 0.5rem; }
            .table-box, .form-box { padding: 1.1rem 0.6rem !important; }
            .header-title { font-size: 2rem !important; }
            .header-sub { font-size: 1.1rem !important; }
        }
        .table-box table { table-layout: fixed; width: 100%; }
        .table-box th, .table-box td { word-break: break-word; white-space: normal; }
    </style>
</head>
<body class="bg-white">

<!-- Header navigation -->
<header class="bg-white sticky top-0 z-50 border-b-2 border-black shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <a href="index.php" class="text-black font-bold text-xl">OmnesBnB</a>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="index.php" class="text-sm text-black hover:text-black">Chercher</a>
                <a href="publier.php" class="text-sm text-black hover:text-black">Publier</a>
                <a href="mes-locations.php" class="text-sm text-black hover:text-black">Mes locations</a>
                <div class="h-8 w-px mx-3 border-l-2 border-black"></div>
                <a href="profil.php" class="text-sm bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800">
                    Mon Profil
                </a>
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
            <a href="profil.php" class="block text-sm bg-black text-white py-2 px-6 rounded-lg text-center hover:bg-gray-800">
                Mon Profil
            </a>
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

<!-- Bandeau noir façon connexion.html -->
<section class="bg-black py-12 px-4 flex flex-col items-center justify-center">
    <h1 class="text-white text-4xl md:text-5xl font-bold mb-3 text-center leading-tight">
        Publier ou gérer vos annonces
    </h1>
    <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">
        Décrivez votre logement ou gérez vos annonces existantes.
    </p>
    <div class="bg-white border-2 border-black rounded-2xl shadow-lg max-w-3xl w-full mx-auto p-0 flex flex-col items-center relative overflow-visible">
        <div class="flex w-full items-stretch relative" style="min-height:72px">
            <button id="onglet-nouveau" class="onglet-header flex-1 text-2xl font-bold py-5 transition text-black focus:outline-none z-10">
                Nouvelle annonce
            </button>
            <div class="absolute top-0 bottom-0 left-1/2 transform -translate-x-1/2 w-px border-l-2 border-black z-20"></div>
            <button id="onglet-modifier" class="onglet-header flex-1 text-2xl font-bold py-5 transition text-gray-400 hover:text-black focus:outline-none z-10">
                Gérer mes annonces
            </button>
        </div>
    </div>
</section>

<section id="zone-publier" class="py-10 px-2 bg-white">
    <div class="container-publier">
        <!-- Bloc Nouvelle annonce -->
        <div id="bloc-nouveau" class="w-full">
            <form class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8" action="process_logement.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div>
                    <label for="titre" class="block text-lg font-semibold text-gray-700 mb-2">Titre de l'annonce</label>
                    <input type="text" id="titre" name="titre"
                           placeholder="Ex: Studio lumineux près du campus"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                </div>
                <div>
                    <label for="type-logement" class="block text-lg font-semibold text-gray-700 mb-2">Type de logement</label>
                    <select id="type-logement" name="type-logement"
                            class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg focus:outline-none" required>
                        <option value="">Sélectionnez</option>
                        <option value="studio">Studio</option>
                        <option value="appartement">Appartement</option>
                        <option value="chambre">Chambre</option>
                        <option value="colocation">Colocation</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label for="adresse" class="block text-lg font-semibold text-gray-700 mb-2">Adresse complète</label>
                    <input type="text" id="adresse" name="adresse"
                           placeholder="Ex: 23 Rue Victor Hugo, 69000 Lyon"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                </div>
                <!-- PRIX & CAUTION (nouvelle version 4 champs + caution) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 mb-2">Prix / nuit (€)</label>
                        <input type="number" id="prix_nuit" name="prix_nuit" placeholder="Ex: 50"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    </div>
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 mb-2">Prix / semaine (€)</label>
                        <input type="number" id="prix_semaine" name="prix_semaine" placeholder="Ex: 300"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    </div>
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 mb-2">Prix / mois (€)</label>
                        <input type="number" id="prix_mois" name="prix_mois" placeholder="Ex: 900"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    </div>
                    <div>
                        <label class="block text-lg font-semibold text-gray-700 mb-2">Prix / année (€)</label>
                        <input type="number" id="prix_annee" name="prix_annee" placeholder="Ex: 10500"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    </div>
                    <div class="md:col-span-2">
                        <label for="caution" class="block text-lg font-semibold text-gray-700 mb-2">Caution (€)</label>
                        <input type="number" id="caution" name="caution" placeholder="Ex: 1000"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    </div>
                </div>
                <!-- PHOTOS LOGEMENT : photo principale + annexes -->
                <div>
                    <label for="photo_principale" class="block text-lg font-semibold text-gray-700 mb-2">Photo principale (obligatoire)</label>
                    <input type="file" id="photo_principale" name="photo_principale" accept="image/png, image/jpeg, image/webp"
                           class="w-full text-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-2 file:border-black file:text-black file:bg-white file:font-semibold" required />
                    <p class="text-xs text-gray-500 mt-1">C'est la photo principale affichée sur l'annonce (formats : jpg, png, webp).</p>
                </div>
                <div>
                    <label for="photos_annexes" class="block text-lg font-semibold text-gray-700 mb-2">Photos annexes (optionnelles)</label>
                    <input type="file" id="photos_annexes" name="photos_annexes[]" multiple accept="image/png, image/jpeg, image/webp"
                           class="w-full text-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-2 file:border-black file:text-black file:bg-white file:font-semibold"/>
                    <p class="text-xs text-gray-500 mt-1">Ajoutez des photos supplémentaires si besoin (formats : jpg, png, webp).</p>
                </div>
                <!-- FIN PHOTOS LOGEMENT -->
                <div>
                    <label for="description" class="block text-lg font-semibold text-gray-700 mb-2">Description</label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Décrivez votre logement, les points forts, etc."
                              class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none"></textarea>
                </div>
                <button type="submit" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-2xl">
                    Publier l'annonce
                </button>
            </form>
        </div>

        <!-- Bloc Gérer mes annonces -->
        <div id="bloc-modifier" class="w-full hidden">
            <div class="table-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto">
                <h2 class="text-2xl font-bold mb-4 text-center">Vos annonces</h2>
                <?php if (count($mes_logements) > 0): ?>
                    <table class="w-full border-collapse">
                        <thead>
                        <tr>
                            <th class="border-b-2 border-black px-2 py-2 text-left text-lg font-semibold">Titre</th>
                            <th class="border-b-2 border-black px-2 py-2 text-left text-lg font-semibold">Statut</th>
                            <th class="border-b-2 border-black px-2 py-2 text-left text-lg font-semibold">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mes_logements as $logement):
                            // Vérifier si le logement est réservé
                            $logement_id = $logement['id'];
                            $reserve_query = "SELECT * FROM reservations WHERE logement_id = ? AND statut = 'acceptée'";
                            $stmt = mysqli_prepare($conn, $reserve_query);
                            mysqli_stmt_bind_param($stmt, "i", $logement_id);
                            mysqli_stmt_execute($stmt);
                            $reserve_result = mysqli_stmt_get_result($stmt);
                            $is_reserved = mysqli_num_rows($reserve_result) > 0;

                            if ($is_reserved) {
                                $reservation = mysqli_fetch_assoc($reserve_result);
                                $locataire_id = $reservation['user_id'];

                                // Récupérer les infos du locataire
                                $locataire_query = "SELECT * FROM users WHERE id = ?";
                                $stmt = mysqli_prepare($conn, $locataire_query);
                                mysqli_stmt_bind_param($stmt, "i", $locataire_id);
                                mysqli_stmt_execute($stmt);
                                $locataire_result = mysqli_stmt_get_result($stmt);
                                $locataire = mysqli_fetch_assoc($locataire_result);
                            }
                            ?>
                            <tr>
                                <td class="border-b border-gray-200 px-2 py-3 whitespace-normal break-words text-xs md:text-base">
                                    <?php echo htmlspecialchars($logement['titre']); ?>
                                </td>
                                <td class="border-b border-gray-200 px-2 py-3 whitespace-normal break-words text-xs md:text-base">
                                    <?php echo $is_reserved ? "Bien loué" : "Annonce publiée"; ?>
                                </td>
                                <td class="border-b border-gray-200 px-2 py-3 flex flex-row flex-nowrap gap-1 items-center justify-start whitespace-normal break-words min-w-0">
                                    <?php if (!$is_reserved): ?>
                                        <button class="bg-black text-white py-2 px-2 md:px-4 rounded-lg hover:bg-gray-800 transition-all text-xs md:text-sm btn-modifier w-full md:w-auto" data-id="<?php echo $logement['id']; ?>">
                                            <span class="hidden md:inline">Modifier</span>
                                            <span class="md:hidden">Modif.</span>
                                        </button>
                                        <form action="process_delete_logement.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="logement_id" value="<?php echo $logement['id']; ?>">
                                            <button type="submit" class="bg-red-600 text-white py-2 px-2 md:px-4 rounded-lg border-2 border-red-600 hover:bg-red-700 transition-all text-xs md:text-sm btn-supprimer w-full md:w-auto">
                                                <span class="hidden md:inline">Supprimer</span>
                                                <span class="md:hidden">Supp.</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="bg-black text-white py-2 px-2 md:px-4 rounded-lg hover:bg-gray-800 transition-all text-xs md:text-sm w-full md:w-auto btn-info-location" data-id="<?php echo $logement['id']; ?>">
                                            <span>Info location</span>
                                        </button>
                                        <button class="bg-black text-white py-2 px-2 md:px-4 rounded-lg hover:bg-gray-800 transition-all text-xs md:text-sm w-full md:w-auto btn-info-locataire" data-id="<?php echo $locataire['id']; ?>">
                                            <span>Info locataire</span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="infos-deroulantes" class="mt-6"></div>
                <?php else: ?>
                    <div class="text-center py-6">
                        <p class="text-gray-600">Vous n'avez pas encore publié d'annonces.</p>
                        <button id="btn-creer-annonce" class="mt-4 bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800 transition-all">
                            Créer une annonce
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<section class="bg-black text-white py-12 px-4">
    <div class="container mx-auto max-w-3xl text-center">
        <h2 class="text-2xl font-bold mb-4">Et maintenant ?</h2>
        <p class="text-sm mb-6 opacity-90">
            N'hésitez pas à découvrir nos logements en ligne sur OmnesBnB, et trouvez votre futur chez-vous en quelques clics.
        </p>
        <a href="index.php" class="bg-white text-black font-medium py-2 px-6 rounded-lg border border-white hover:bg-gray-100 inline-flex items-center shadow-md">
            <i class="fas fa-magnifying-glass mr-2"></i> Chercher un logement
        </a>
        <div class="mt-12 pt-4 border-t border-gray-700 mx-auto w-full"></div>
        <div class="text-center text-gray-400 text-xs mt-4">
            &copy; 2025 OmnesBnB. Tous droits réservés.
        </div>
    </div>
</section>

<script>
    // Responsive menu mobile
    document.getElementById('menu-burger').addEventListener('click', function() {
        document.getElementById('menu-mobile').classList.toggle('hidden');
    });

    // Onglets header
    const ongletNouveau = document.getElementById('onglet-nouveau');
    const ongletModifier = document.getElementById('onglet-modifier');
    const blocNouveau = document.getElementById('bloc-nouveau');
    const blocModifier = document.getElementById('bloc-modifier');

    function afficherNouveau() {
        ongletNouveau.classList.add('text-black');
        ongletNouveau.classList.remove('text-gray-400');
        ongletModifier.classList.add('text-gray-400');
        ongletModifier.classList.remove('text-black');
        blocNouveau.classList.remove('hidden');
        blocModifier.classList.add('hidden');
    }
    function afficherModifier() {
        ongletModifier.classList.add('text-black');
        ongletModifier.classList.remove('text-gray-400');
        ongletNouveau.classList.add('text-gray-400');
        ongletNouveau.classList.remove('text-black');
        blocModifier.classList.remove('hidden');
        blocNouveau.classList.add('hidden');
    }
    ongletNouveau.addEventListener('click', afficherNouveau);
    ongletModifier.addEventListener('click', afficherModifier);

    // Afficher l'onglet "Modifier" si paramètre présent dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('manage') === '1') {
        afficherModifier();
    } else {
        afficherNouveau();
    }

    // Bouton pour créer une annonce depuis l'onglet Gérer
    if (document.getElementById('btn-creer-annonce')) {
        document.getElementById('btn-creer-annonce').addEventListener('click', afficherNouveau);
    }

    // Bloc déroulant "Info location" & "Info locataire" (avec toggle)
    const infosDeroulantes = document.getElementById('infos-deroulantes');
    let infoOuverte = null;

    // Gestion des boutons "Info location"
    document.querySelectorAll('.btn-info-location').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const logementId = this.getAttribute('data-id');

            if(infoOuverte === 'location-' + logementId){
                infosDeroulantes.innerHTML = "";
                infoOuverte = null;
            } else {
                // Charger les informations du logement via AJAX (simulé ici)
                fetch('api/get_logement.php?id=' + logementId)
                    .then(response => response.json())
                    .then(data => {
                        // Générer l'HTML avec les informations du logement
                        const html = `
                    <div class="bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto mt-4 max-w-2xl animate-fade-in">
                        <h3 class="text-2xl font-bold mb-6 text-center">Détail du logement</h3>
                        <ul class="text-lg text-gray-900 space-y-1">
                            <li><span class="font-bold">Titre&nbsp;:</span> ${data.titre}</li>
                            <li><span class="font-bold">Type&nbsp;:</span> ${data.type}</li>
                            <li><span class="font-bold">Adresse&nbsp;:</span> ${data.adresse}</li>
                            <li><span class="font-bold">Prix/nuit&nbsp;:</span> ${data.prix_nuit} €</li>
                            <li><span class="font-bold">Prix/semaine&nbsp;:</span> ${data.prix_semaine} €</li>
                            <li><span class="font-bold">Prix/mois&nbsp;:</span> ${data.prix_mois} €</li>
                            <li><span class="font-bold">Prix/année&nbsp;:</span> ${data.prix_annee} €</li>
                            <li><span class="font-bold">Caution&nbsp;:</span> ${data.caution} €</li>
                            <li><span class="font-bold">Description&nbsp;:</span> ${data.description}</li>
                        </ul>
                    </div>
                    `;
                        infosDeroulantes.innerHTML = html;
                        infoOuverte = 'location-' + logementId;
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        // Afficher un message d'erreur
                        infosDeroulantes.innerHTML = `
                    <div class="bg-red-100 border-2 border-red-400 text-red-700 p-4 rounded-lg">
                        Une erreur est survenue lors du chargement des informations.
                    </div>
                    `;
                    });
            }
        });
    });

    // Gestion des boutons "Info locataire"
    document.querySelectorAll('.btn-info-locataire').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const locataireId = this.getAttribute('data-id');

            if(infoOuverte === 'locataire-' + locataireId){
                infosDeroulantes.innerHTML = "";
                infoOuverte = null;
            } else {
                // Charger les informations du locataire via AJAX (simulé ici)
                fetch('api/get_user.php?id=' + locataireId)
                    .then(response => response.json())
                    .then(data => {
                        // Générer l'HTML avec les informations du locataire
                        let photoHtml = data.photo
                            ? `<img src="${data.photo}" alt="Photo de profil" class="w-full h-full object-cover">`
                            : `<i class="fas fa-user text-5xl text-gray-400"></i>`;

                        const html = `
                    <div class="bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto mt-4 max-w-2xl flex flex-col items-center animate-fade-in">
                        <div class="flex flex-col items-center w-full">
                            <div class="w-28 h-28 rounded-xl overflow-hidden border-2 border-black bg-gray-100 flex items-center justify-center mb-4">
                                ${photoHtml}
                            </div>
                            <h2 class="text-2xl font-bold mb-2 text-center">${data.prenom} ${data.nom}</h2>
                            <div class="flex flex-wrap justify-center items-center text-gray-800 text-lg gap-x-3 gap-y-2 mb-1">
                                <span>${data.email}</span>
                                <span class="text-gray-400">|</span>
                                <span>${data.telephone}</span>
                            </div>
                            <div class="flex flex-wrap justify-center items-center text-gray-700 text-lg gap-x-3 gap-y-2 mb-1">
                                <span>${data.statut}</span>
                                <span class="text-gray-400">|</span>
                                <span>${data.campus}</span>
                            </div>
                            <div class="mt-3 text-center text-gray-600 text-base w-full">
                                ${data.description || 'Aucune description disponible.'}
                            </div>
                        </div>
                    </div>
                    `;
                        infosDeroulantes.innerHTML = html;
                        infoOuverte = 'locataire-' + locataireId;
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        // Afficher un message d'erreur
                        infosDeroulantes.innerHTML = `
                    <div class="bg-red-100 border-2 border-red-400 text-red-700 p-4 rounded-lg">
                        Une erreur est survenue lors du chargement des informations.
                    </div>
                    `;
                    });
            }
        });
    });

    // Gestion des boutons "Modifier"
    document.querySelectorAll('.btn-modifier').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const logementId = this.getAttribute('data-id');

            // Rediriger vers la page de modification avec l'ID du logement
            window.location.href = 'modifier_logement.php?id=' + logementId;
        });
    });

    // Calculateur de prix automatique
    const prixNuit = document.getElementById('prix_nuit');
    const prixSemaine = document.getElementById('prix_semaine');
    const prixMois = document.getElementById('prix_mois');
    const prixAnnee = document.getElementById('prix_annee');

    // Suggérer des prix basés sur le prix par nuit
    prixNuit.addEventListener('input', function() {
        const valeur = parseFloat(this.value);
        if (!isNaN(valeur)) {
            prixSemaine.value = Math.round(valeur * 6.5); // Réduction de 7%
            prixMois.value = Math.round(valeur * 26); // Réduction de 13%
            prixAnnee.value = Math.round(valeur * 300); // Réduction de 18%
        }
    });

    // Prix semaine → nuit, mois, année
    prixSemaine.addEventListener('input', function() {
        const valeur = parseFloat(this.value);
        if (!isNaN(valeur)) {
            prixNuit.value = Math.round(valeur / 6.5);
            prixMois.value = Math.round(valeur * 4);
            prixAnnee.value = Math.round(valeur * 46);
        }
    });

    // Prix mois → nuit, semaine, année
    prixMois.addEventListener('input', function() {
        const valeur = parseFloat(this.value);
        if (!isNaN(valeur)) {
            prixNuit.value = Math.round(valeur / 26);
            prixSemaine.value = Math.round(valeur / 4);
            prixAnnee.value = Math.round(valeur * 11.5);
        }
    });

    // Prix année → nuit, semaine, mois
    prixAnnee.addEventListener('input', function() {
        const valeur = parseFloat(this.value);
        if (!isNaN(valeur)) {
            prixNuit.value = Math.round(valeur / 300);
            prixSemaine.value = Math.round(valeur / 46);
            prixMois.value = Math.round(valeur / 11.5);
        }
    });

    // Validation du formulaire
    const formPublication = document.querySelector('.form-box');
    formPublication.addEventListener('submit', function(e) {
        // Vérifier que les prix sont cohérents
        const nuit = parseFloat(prixNuit.value);
        const semaine = parseFloat(prixSemaine.value);
        const mois = parseFloat(prixMois.value);
        const annee = parseFloat(prixAnnee.value);

        if (nuit * 7 > semaine) {
            alert('Le prix à la semaine devrait être inférieur à 7 fois le prix à la nuit.');
            e.preventDefault();
            return;
        }

        if (semaine * 4 > mois) {
            alert('Le prix au mois devrait être inférieur à 4 fois le prix à la semaine.');
            e.preventDefault();
            return;
        }

        if (mois * 12 > annee) {
            alert('Le prix à l\'année devrait être inférieur à 12 fois le prix au mois.');
            e.preventDefault();
            return;
        }

        // Vérifier la photo principale
        const photoPrincipale = document.getElementById('photo_principale');
        if (photoPrincipale.files.length === 0) {
            alert('Une photo principale est requise.');
            e.preventDefault();
            return;
        }

        // Vérifier l'extension des fichiers
        const extensionsValides = ['jpg', 'jpeg', 'png', 'webp'];

        // Vérifier la photo principale
        const fileNamePrincipal = photoPrincipale.files[0].name;
        const extensionPrincipale = fileNamePrincipal.split('.').pop().toLowerCase();

        if (!extensionsValides.includes(extensionPrincipale)) {
            alert('La photo principale doit être au format jpg, jpeg, png ou webp.');
            e.preventDefault();
            return;
        }

        // Vérifier les photos annexes
        const photosAnnexes = document.getElementById('photos_annexes');
        if (photosAnnexes.files.length > 0) {
            for (let i = 0; i < photosAnnexes.files.length; i++) {
                const fileName = photosAnnexes.files[i].name;
                const extension = fileName.split('.').pop().toLowerCase();

                if (!extensionsValides.includes(extension)) {
                    alert('Toutes les photos doivent être au format jpg, jpeg, png ou webp.');
                    e.preventDefault();
                    return;
                }
            }
        }
    });
</script>
</body>
</html>