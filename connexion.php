<?php
// Inclusion du fichier de configuration
include 'config.php';
include 'security.php';

// Redirection si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Récupération des messages d'erreur et de succès
$login_error = isset($_GET['login_error']) ? htmlspecialchars($_GET['login_error']) : '';
$register_error = isset($_GET['register_error']) ? htmlspecialchars($_GET['register_error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Génération du token CSRF
$csrf_token = csrf_token();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion / Inscription - OmnesBnB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #FFFFFF;
            overflow-x: hidden;
            max-width: 100%;
        }
        .checkbox-uber {
            appearance: none;
            width: 1.3em;
            height: 1.3em;
            min-width: 1.3em;
            border: 2px solid #000;
            border-radius: 0.25em;
            background: #fff;
            display: inline-block;
            position: relative;
            margin-right: 0.7em;
            cursor: pointer;
            transition: border 0.2s;
            vertical-align: middle;
        }
        .checkbox-uber:checked {
            background: #000;
            border: 2px solid #000;
        }
        .checkbox-uber:checked:after {
            content: '';
            position: absolute;
            left: 0.32em;
            top: 0.09em;
            width: 0.36em;
            height: 0.7em;
            border: solid #fff;
            border-width: 0 0.18em 0.18em 0;
            transform: rotate(45deg);
            display: block;
        }
        .lien-noir {
            color: #111;
            text-decoration: none;
            font-weight: 500;
            transition: text-decoration 0.2s;
        }
        .lien-noir:hover, .lien-noir:focus {
            color: #000;
            text-decoration: underline;
        }
        @media (min-width: 1024px) {
            .form-box {
                max-width: 900px !important;  /* Largeur encore + grande sur PC */
                min-width: 700px;
                margin-left: auto;
                margin-right: auto;
                padding-top: 1.5rem !important;
                padding-bottom: 1.5rem !important;
            }
        }
        @media (max-width: 1023px) {
            .form-box {
                max-width: 100%;
                min-width: 0;
                padding: 2rem 1rem !important;
            }
        }
    </style>
</head>
<body class="bg-white">
<!-- Header identique -->
<header class="bg-white sticky top-0 z-50 border-b-2 border-black shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <a href="index.php" class="text-black font-bold text-xl">OmnesBnB</a>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="index.php" class="text-sm text-black hover:text-black">Chercher</a>
                <a href="publier.php" class="text-sm text-black hover:text-black">Publier</a>
                <a href="mes-locations.php" class="text-sm text-black hover:text-black">Mes locations</a>
                <div class="h-8 w-px mx-3 border-l-2 border-black"></div>
                <a href="connexion.php" class="text-sm bg-black text-white py-2 px-6 rounded-lg hover:bg-gray-800">
                    Connexion / Inscription
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
            <a href="connexion.php" class="block text-sm bg-black text-white py-2 px-6 rounded-lg text-center hover:bg-gray-800">
                Connexion / Inscription
            </a>
        </div>
    </div>
</header>

<!-- Bandeau noir avec onglets -->
<section class="bg-black py-12 px-4 flex flex-col items-center justify-center">
    <h1 class="text-white text-4xl md:text-5xl font-bold mb-3 text-center leading-tight">
        Connexion / Inscription
    </h1>
    <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">
        Accédez à votre espace OmnesBnB en toute simplicité.
    </p>
    <div class="bg-white border-2 border-black rounded-2xl shadow-lg max-w-3xl w-full mx-auto p-0 flex flex-col items-center relative overflow-visible">
        <div class="flex w-full items-stretch relative" style="min-height:72px">
            <button id="onglet-connexion" class="flex-1 text-2xl font-bold py-5 text-black focus:outline-none transition-all duration-150 z-10">
                Connexion
            </button>
            <div class="absolute top-0 bottom-0 left-1/2 transform -translate-x-1/2 w-px border-l-2 border-black z-20"></div>
            <button id="onglet-inscription" class="flex-1 text-2xl font-bold py-5 text-gray-400 hover:text-black focus:outline-none transition-all duration-150 z-10">
                Inscription
            </button>
        </div>
    </div>
</section>

<!-- Formulaires sous le bandeau noir -->
<section id="zone-formulaire" class="py-10 px-4 bg-white">
    <div class="container mx-auto">
        <!-- Message de succès si présent -->
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 max-w-3xl mx-auto">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Connexion -->
        <div id="bloc-connexion" class="w-full">
            <!-- Message d'erreur si présent -->
            <?php if (!empty($login_error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 max-w-3xl mx-auto">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form id="form-connexion" action="process_login.php" method="POST" class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div>
                    <label for="email-connexion" class="block text-lg font-semibold text-gray-700 mb-2">Adresse e-mail</label>
                    <input type="email" id="email-connexion" name="email-connexion"
                           placeholder="exemple@omnes.fr"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                </div>
                <div>
                    <label for="mdp-connexion" class="block text-lg font-semibold text-gray-700 mb-2">Mot de passe</label>
                    <input type="password" id="mdp-connexion" name="mdp-connexion"
                           placeholder="Votre mot de passe"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                </div>
                <div class="flex justify-between items-center">
                    <label class="flex items-center cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="checkbox-uber" />
                        <span class="text-base text-gray-700 font-medium">Se souvenir de moi</span>
                    </label>
                    <a href="motdepasse.php" class="lien-noir">Mot de passe oublié&nbsp;?</a>
                </div>
                <button type="submit" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-2xl">
                    Se connecter
                </button>
            </form>
        </div>

        <!-- Inscription -->
        <div id="bloc-inscription" class="w-full hidden">
            <!-- Message d'erreur si présent -->
            <?php if (!empty($register_error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 max-w-3xl mx-auto">
                    <?php echo $register_error; ?>
                </div>
            <?php endif; ?>

            <form id="form-inscription" action="process_register.php" method="POST" class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="prenom-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Prénom</label>
                        <input type="text" id="prenom-inscription" name="prenom-inscription"
                               placeholder="Votre prénom"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    </div>
                    <div>
                        <label for="nom-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Nom</label>
                        <input type="text" id="nom-inscription" name="nom-inscription"
                               placeholder="Votre nom"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    </div>
                </div>
                <div>
                    <label for="email-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Adresse e-mail</label>
                    <input type="email" id="email-inscription" name="email-inscription"
                           placeholder="exemple@omnes.fr"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    <p class="text-sm text-gray-600 mt-1">Utilisez votre adresse @edu.ece.fr, @ece.fr ou @omnesintervenant.com</p>
                </div>
                <div>
                    <label for="telephone-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Numéro de téléphone</label>
                    <input type="tel" id="telephone-inscription" name="telephone-inscription"
                           placeholder="Votre numéro de téléphone"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                </div>
                <div>
                    <label for="statut-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Statut</label>
                    <select id="statut-inscription" name="statut-inscription" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg focus:outline-none" required>
                        <option value="">Sélectionnez votre statut</option>
                        <option value="Étudiant(e)">Étudiant(e)</option>
                        <option value="Personnel Omnes">Personnel Omnes</option>
                        <option value="Professeur">Professeur</option>
                    </select>
                </div>
                <div>
                    <label for="campus-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Campus</label>
                    <select id="campus-inscription" name="campus-inscription" class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg focus:outline-none" required>
                        <option value="">Sélectionnez votre campus</option>
                        <option value="Paris">Paris</option>
                        <option value="Lyon">Lyon</option>
                        <option value="Bordeaux">Bordeaux</option>
                    </select>
                </div>
                <div>
                    <label for="mdp-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Mot de passe</label>
                    <input type="password" id="mdp-inscription" name="mdp-inscription"
                           placeholder="Créez un mot de passe"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    <p class="text-sm text-gray-600 mt-1">8 caractères minimum avec majuscules, minuscules, chiffres et caractères spéciaux</p>
                </div>
                <div>
                    <label for="mdp2-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Confirmez le mot de passe</label>
                    <input type="password" id="mdp2-inscription" name="mdp2-inscription"
                           placeholder="Confirmez votre mot de passe"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                </div>
                <button type="submit" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-2xl">
                    Créer mon compte
                </button>
            </form>
        </div>
    </div>
</section>

<!-- FOOTER identique à index -->
<section class="bg-black text-white py-12 px-4">
    <div class="container mx-auto max-w-3xl text-center">
        <h2 class="text-2xl font-bold mb-4">Besoin d'aide pour vous connecter ?</h2>
        <p class="text-sm mb-6 opacity-90">
            Accédez à votre compte pour gérer vos réservations ou proposer un logement sur OmnesBnB.
        </p>
        <a href="index.php" class="bg-white text-black font-medium py-2 px-6 rounded-lg border border-white hover:bg-gray-100 inline-flex items-center shadow-md">
            <i class="fas fa-arrow-left mr-2"></i> Retour à l'accueil
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

    // Gestion onglets Connexion/Inscription
    const ongletConnexion = document.getElementById('onglet-connexion');
    const ongletInscription = document.getElementById('onglet-inscription');
    const blocConnexion = document.getElementById('bloc-connexion');
    const blocInscription = document.getElementById('bloc-inscription');

    function afficherConnexion() {
        ongletConnexion.classList.add('text-black');
        ongletConnexion.classList.remove('text-gray-400');
        ongletInscription.classList.add('text-gray-400');
        ongletInscription.classList.remove('text-black');
        blocConnexion.classList.remove('hidden');
        blocInscription.classList.add('hidden');
    }

    function afficherInscription() {
        ongletInscription.classList.add('text-black');
        ongletInscription.classList.remove('text-gray-400');
        ongletConnexion.classList.add('text-gray-400');
        ongletConnexion.classList.remove('text-black');
        blocInscription.classList.remove('hidden');
        blocConnexion.classList.add('hidden');
    }

    ongletConnexion.addEventListener('click', afficherConnexion);
    ongletInscription.addEventListener('click', afficherInscription);

    // Afficher l'onglet inscription si paramètre présent dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('register') === '1') {
        afficherInscription();
    } else {
        afficherConnexion();
    }

    // Validation de mot de passe côté client
    const mdpInscription = document.getElementById('mdp-inscription');
    const mdp2Inscription = document.getElementById('mdp2-inscription');

    mdp2Inscription.addEventListener('input', function() {
        if (mdpInscription.value !== mdp2Inscription.value) {
            mdp2Inscription.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            mdp2Inscription.setCustomValidity('');
        }
    });

    mdpInscription.addEventListener('input', function() {
        if (mdp2Inscription.value && mdpInscription.value !== mdp2Inscription.value) {
            mdp2Inscription.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            mdp2Inscription.setCustomValidity('');
        }
    });
</script>
</body>
</html>