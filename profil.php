<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Récupération des informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
global $conn;
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    // Utilisateur non trouvé, déconnexion et redirection
    session_destroy();
    header('Location: connexion.php?login_error=Session invalide. Veuillez vous reconnecter.');
    exit;
}

$user = mysqli_fetch_assoc($result);

// Récupération des messages de succès/erreur
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Génération d'un token CSRF
$csrf_token = csrf_token();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - OmnesBnB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #FFFFFF; }
        @media (min-width: 768px) { .photo-profil-position { margin-top: 18px; } }
        input[readonly], select[readonly], textarea[readonly] { background: #fafafa !important; color: #888 !important; cursor: not-allowed; }
    </style>
</head>
<body class="bg-white">
<!-- Header -->
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

<!-- Bandeau noir du haut avec carte profil bien responsive -->
<section class="flex flex-col items-center justify-center bg-black py-12 px-4">
    <div class="w-full flex flex-col items-center justify-center">
        <h1 class="text-white text-4xl md:text-5xl font-bold mb-3 text-center leading-tight">
            Mon profil
        </h1>
        <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">
            Gérez vos informations personnelles, votre compte et accédez à tous les services OmnesBnB.
        </p>
    </div>
    <div class="bg-white border-2 border-black rounded-2xl shadow-lg max-w-3xl w-full mx-auto px-6 py-8 md:py-10">
        <div class="flex flex-col md:flex-row md:items-center md:gap-8">
            <!-- Photo de profil -->
            <div class="flex-shrink-0 flex justify-center md:justify-start mb-6 md:mb-0">
                <div class="relative group w-32 h-32 md:w-36 md:h-36">
                    <div id="photo-wrapper" class="w-full h-full rounded-xl overflow-hidden border-2 border-black bg-gray-100 flex items-center justify-center">
                        <?php if (!empty($user['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($user['photo']); ?>" alt="Photo de profil" class="w-full h-full object-cover" />
                        <?php else: ?>
                            <i class="fas fa-user text-5xl text-gray-400"></i>
                        <?php endif; ?>
                    </div>
                    <!-- Icône appareil photo cachée par défaut -->
                    <button id="edit-photo-btn" class="absolute bottom-0 right-0 bg-black text-white rounded-full p-2 shadow-md hover:bg-gray-800 hidden">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
            </div>
            <!-- Infos principales & boutons -->
            <div class="flex flex-1 flex-col justify-between">
                <div class="flex flex-col items-center md:items-start w-full">
                    <h2 id="card-nomprenom" class="text-2xl md:text-3xl font-bold mb-1 md:mb-2 text-center md:text-left">
                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </h2>
                    <!-- Bloc infos desktop/mobile : mail | tel (PC: pas de barre après le tel), puis statut | Paris dessous -->
                    <div class="flex flex-wrap justify-center md:justify-start items-center text-gray-700 text-base md:text-lg gap-x-3 gap-y-2">
                        <span id="card-email" class="text-gray-800"><?php echo htmlspecialchars($user['email']); ?></span>
                        <span class="text-gray-400 hidden md:inline">|</span>
                        <span id="card-tel" class=""><?php echo htmlspecialchars($user['telephone']); ?></span>
                    </div>
                    <div class="flex flex-wrap justify-center md:justify-start items-center text-gray-700 text-base md:text-lg gap-x-3 gap-y-2 mt-1 md:mt-0">
                        <span id="card-statut" class=""><?php echo htmlspecialchars($user['statut']); ?></span>
                        <span class="text-gray-400 hidden md:inline">|</span>
                        <span id="card-campus" class=""><?php echo htmlspecialchars($user['campus']); ?></span>
                    </div>
                </div>
                <!-- Boutons noirs alignés horizontalement sous les infos -->
                <div class="flex flex-col md:flex-row gap-3 md:gap-6 w-full max-w-2xl mx-auto md:mx-0 mt-5">
                    <button id="btn-modifier-profil" class="bg-black text-white py-3 px-4 rounded-lg font-medium hover:bg-gray-800 shadow transition w-full md:w-1/3">
                        Modifier le profil
                    </button>
                    <a href="nouveau-motdepasse.php" id="btn-changer-mdp" class="bg-black text-white py-3 px-4 rounded-lg font-medium hover:bg-gray-800 shadow transition w-full md:w-1/3 text-center">
                        Modifier le mot de passe
                    </a>
                    <a href="deconnexion.php" class="bg-black text-white py-3 px-4 rounded-lg font-medium hover:bg-gray-800 shadow transition w-full md:w-1/3 text-center">
                        Se déconnecter
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Formulaire informations personnelles -->
<section class="flex flex-col items-center justify-center py-8 px-4 bg-white">
    <div id="persoinfos-box" class="bg-white border-2 border-black rounded-2xl shadow-lg max-w-5xl w-full mx-auto p-10 transition-all duration-300">
        <h3 class="text-xl font-bold mb-8 text-center">Informations personnelles</h3>
        <form id="persoinfos-form" action="process_profil.php" method="POST" class="space-y-6" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div>
                    <label for="prenom" class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" readonly />
                </div>
                <div>
                    <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" readonly />
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse e-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" readonly />
                    <p id="email-err" class="hidden text-xs text-red-600 mt-1"></p>
                </div>
                <div>
                    <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1">Numéro de téléphone</label>
                    <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>" maxlength="14"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" readonly />
                    <p id="tel-err" class="hidden text-xs text-red-600 mt-1"></p>
                </div>
                <div>
                    <label for="statut" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select id="statut" name="statut" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" disabled>
                        <option <?php echo $user['statut'] == 'Étudiant(e)' ? 'selected' : ''; ?>>Étudiant(e)</option>
                        <option <?php echo $user['statut'] == 'Personnel Omnes' ? 'selected' : ''; ?>>Personnel Omnes</option>
                        <option <?php echo $user['statut'] == 'Professeur' ? 'selected' : ''; ?>>Professeur</option>
                    </select>
                </div>
                <div>
                    <label for="campus" class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                    <select id="campus" name="campus" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" disabled>
                        <option <?php echo $user['campus'] == 'Paris' ? 'selected' : ''; ?>>Paris</option>
                        <option <?php echo $user['campus'] == 'Lyon' ? 'selected' : ''; ?>>Lyon</option>
                        <option <?php echo $user['campus'] == 'Bordeaux' ? 'selected' : ''; ?>>Bordeaux</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">À propos de moi</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 focus:outline-none" readonly><?php echo htmlspecialchars($user['description']); ?></textarea>
            </div>
            <!-- Boutons édition cachés par défaut -->
            <div id="edit-btns" class="pt-4 flex flex-col gap-3" style="display: none;">
                <div class="flex flex-col md:flex-row md:gap-6 gap-3">
                    <button type="submit" id="btn-enregistrer" class="bg-black text-white font-medium py-3 px-8 rounded-lg hover:bg-gray-800 transition-all shadow-md w-full md:w-1/2">
                        Enregistrer les modifications
                    </button>
                    <button type="button" id="btn-annuler" class="bg-black text-white font-medium py-3 px-8 rounded-lg hover:bg-gray-800 transition-all shadow-md w-full md:w-1/2">
                        Annuler les modifications
                    </button>
                </div>
                <button type="button" id="btn-supprimer" class="bg-red-600 text-white font-medium py-3 px-8 rounded-lg border-2 border-red-600 hover:bg-red-700 transition-all shadow-md w-full">
                    Supprimer mon compte
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Modal de confirmation de suppression (caché par défaut) -->
<div id="modal-suppression" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Supprimer votre compte
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible et toutes vos données seront définitivement supprimées.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form action="process_delete_account.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Supprimer définitivement
                    </button>
                </form>
                <button type="button" id="btn-annuler-suppression" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire hidden pour upload photo de profil -->
<form id="form-photo" action="process_profil.php" method="POST" enctype="multipart/form-data" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="update_photo">
    <input type="file" id="photo-upload" name="photo" accept="image/*">
</form>

<!-- Footer identique -->
<section class="bg-black text-white py-12 px-4">
    <div class="container mx-auto max-w-3xl text-center">
        <h2 class="text-2xl font-bold mb-4">Besoin d'un logement ?</h2>
        <p class="text-sm mb-6 opacity-90">
            Trouvez rapidement votre logement idéal parmi des centaines d'offres sur OmnesBnB. Votre nouvelle vie commence ici !
        </p>
        <a href="recherche.php" class="bg-white text-black font-medium py-2 px-6 rounded-lg border border-white hover:bg-gray-100 inline-flex items-center shadow-md">
            <i class="fas fa-search mr-2"></i> Chercher un logement
        </a>
        <div class="mt-12 pt-4 border-t border-gray-700 mx-auto w-full"></div>
        <div class="text-center text-gray-400 text-xs mt-4">
            &copy; 2025 OmnesBnB. Tous droits réservés.
        </div>
    </div>
</section>

<script>
    // Menu mobile
    document.getElementById('menu-burger').addEventListener('click', function() {
        document.getElementById('menu-mobile').classList.toggle('hidden');
    });

    // Affichage édition : champs éditables, boutons, etc.
    const btnModif = document.getElementById('btn-modifier-profil');
    const persoinfosForm = document.getElementById('persoinfos-form');
    const persoinfosBox = document.getElementById('persoinfos-box');
    const editBtns = document.getElementById('edit-btns');
    const editPhotoBtn = document.getElementById('edit-photo-btn');
    const photoProfil = document.getElementById('photo-wrapper');
    const champIds = ["prenom","nom","email","telephone","description"];
    const selectIds = ["statut","campus"];

    let modeEdition = false;
    let backup = {};

    // Initialement, cache tous les boutons d'édition
    editBtns.style.display = "none";

    btnModif.addEventListener('click', function() {
        modeEdition = !modeEdition;
        if(modeEdition) {
            // Sauvegarde des valeurs pour annulation
            champIds.forEach(id => backup[id] = document.getElementById(id).value);
            selectIds.forEach(id => backup[id] = document.getElementById(id).value);

            // Active édition
            champIds.forEach(id => document.getElementById(id).readOnly = false);
            selectIds.forEach(id => document.getElementById(id).disabled = false);
            editBtns.style.display = "flex";
            editPhotoBtn.classList.remove('hidden');
            persoinfosBox.classList.add('pb-12');
        } else {
            // Désactive édition
            champIds.forEach(id => document.getElementById(id).readOnly = true);
            selectIds.forEach(id => document.getElementById(id).disabled = true);
            editBtns.style.display = "none";
            editPhotoBtn.classList.add('hidden');
            persoinfosBox.classList.remove('pb-12');
        }
    });

    // Annulation des modifications
    document.getElementById('btn-annuler').addEventListener('click', function() {
        champIds.forEach(id => document.getElementById(id).value = backup[id]);
        selectIds.forEach(id => document.getElementById(id).value = backup[id]);
        btnModif.click(); // Quitte le mode édition
    });

    // Validation téléphone
    document.getElementById('telephone').addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '').slice(0,10);
        let formatted = val.match(/.{1,2}/g)?.join('.') || '';
        this.value = formatted;
        // Erreur si pas 10 chiffres
        document.getElementById('tel-err').classList.toggle('hidden', val.length===10);
        document.getElementById('tel-err').textContent = val.length===10 ? "" : "Format attendu : 00.00.00.00.00";
    });

    // Validation e-mail ECE
    document.getElementById('email').addEventListener('input', function() {
        let regex = /^[a-zA-Z]+\.[a-zA-Z]+@(edu\.ece\.fr|ece\.fr)$/;
        let valid = regex.test(this.value.trim());
        document.getElementById('email-err').classList.toggle('hidden', valid);
        document.getElementById('email-err').textContent = valid ? "" : "Format attendu : prenom.nom@ece.fr ou prenom.nom@edu.ece.fr";
    });

    // Gestion de l'upload de photo
    editPhotoBtn.addEventListener('click', function() {
        document.getElementById('photo-upload').click();
    });

    document.getElementById('photo-upload').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            document.getElementById('form-photo').submit();
        }
    });

    // Gestion de la suppression de compte
    document.getElementById('btn-supprimer').addEventListener('click', function() {
        document.getElementById('modal-suppression').classList.remove('hidden');
    });

    document.getElementById('btn-annuler-suppression').addEventListener('click', function() {
        document.getElementById('modal-suppression').classList.add('hidden');
    });
</script>
</body>
</html>