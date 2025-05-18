<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Rediriger si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Récupération des messages d'erreur et de succès
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Génération d'un token CSRF
$csrf_token = csrf_token();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - OmnesBnB</title>
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
        @media (min-width: 1024px) {
            .form-box {
                max-width: 650px !important;
                min-width: 500px;
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
                <a href="recherche.php" class="text-sm text-black hover:text-black">Chercher</a>
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
            <a href="recherche.php" class="block py-2 text-sm text-black font-medium text-center">Chercher</a>
            <a href="publier.php" class="block py-2 text-sm text-black font-medium text-center">Publier</a>
            <a href="mes-locations.php" class="block py-2 text-sm text-black font-medium text-center">Mes locations</a>
            <div class="w-4/5 mx-auto border-t-2 border-black my-3"></div>
            <a href="connexion.php" class="block text-sm bg-black text-white py-2 px-6 rounded-lg text-center hover:bg-gray-800">
                Connexion / Inscription
            </a>
        </div>
    </div>
</header>

<!-- Bandeau noir avec titre -->
<section class="bg-black py-12 px-4 flex flex-col items-center justify-center">
    <h1 class="text-white text-4xl md:text-5xl font-bold mb-3 text-center leading-tight">
        Mot de passe oublié
    </h1>
    <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">
        Réinitialisez votre mot de passe en quelques étapes simples.
    </p>
</section>

<!-- Messages d'erreur et de succès -->
<?php if (!empty($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 mt-6 max-w-3xl mx-auto">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 mt-6 max-w-3xl mx-auto">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<!-- Formulaire de récupération de mot de passe -->
<section class="py-10 px-4 bg-white">
    <div class="container mx-auto">
        <div id="etape-email" class="w-full">
            <form id="form-recuperation" action="process_reset.php" method="POST" class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="text-center mb-6">
                    <i class="fas fa-key text-5xl text-gray-800 mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">Récupération de mot de passe</h2>
                    <p class="text-gray-600">
                        Saisissez votre adresse e-mail Omnes ci-dessous. Nous vous enverrons un lien pour réinitialiser votre mot de passe.
                    </p>
                </div>

                <div>
                    <label for="email-recuperation" class="block text-lg font-semibold text-gray-700 mb-2">Adresse e-mail</label>
                    <input type="email" id="email-recuperation" name="email-recuperation"
                           placeholder="exemple@omnes.fr"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    <p class="mt-2 text-sm text-gray-500">
                        Veuillez saisir l'adresse e-mail associée à votre compte OmnesBnB.
                    </p>
                </div>

                <button type="submit" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-xl">
                    Envoyer le lien de réinitialisation
                </button>

                <div class="text-center">
                    <a href="connexion.php" class="text-black font-medium hover:underline">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à la connexion
                    </a>
                </div>
            </form>
        </div>

        <!-- Étape de confirmation (masquée par défaut) -->
        <div id="etape-confirmation" class="w-full hidden">
            <div class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8">
                <div class="text-center mb-6">
                    <i class="fas fa-envelope-open-text text-5xl text-green-600 mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">E-mail envoyé !</h2>
                    <p class="text-gray-600">
                        Nous avons envoyé un lien de réinitialisation à l'adresse e-mail indiquée.
                    </p>
                </div>

                <div class="bg-gray-100 border-l-4 border-blue-500 p-4 mb-4 rounded">
                    <p class="text-sm text-gray-700 mb-2">
                        <strong>Consignes :</strong>
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                        <li>Vérifiez votre boîte de réception et vos spams</li>
                        <li>Cliquez sur le lien dans l'e-mail pour créer un nouveau mot de passe</li>
                        <li>Le lien expire après 30 minutes</li>
                    </ul>
                </div>

                <div class="text-center space-y-4">
                    <button id="btn-renvoyer" class="bg-white text-black font-medium py-2 px-6 rounded-lg border-2 border-black hover:bg-gray-100 transition-all">
                        Je n'ai pas reçu l'e-mail
                    </button>

                    <div>
                        <a href="connexion.php" class="text-black font-medium hover:underline">
                            <i class="fas fa-arrow-left mr-2"></i>Retour à la connexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
</section>

<!-- FOOTER identique à index -->
<section class="bg-black text-white py-12 px-4">
    <div class="container mx-auto max-w-3xl text-center">
        <h2 class="text-2xl font-bold mb-4">Besoin d'aide pour vous connecter ?</h2>
        <p class="text-sm mb-6 opacity-90">
            Contactez notre équipe support si vous rencontrez des difficultés pour accéder à votre compte.
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

    // Afficher l'étape de confirmation si paramètre success présent
    <?php if (!empty($success)): ?>
    document.getElementById('etape-email').classList.add('hidden');
    document.getElementById('etape-confirmation').classList.remove('hidden');
    <?php endif; ?>

    // Bouton "Je n'ai pas reçu l'e-mail"
    document.getElementById('btn-renvoyer')?.addEventListener('click', function() {
        document.getElementById('etape-confirmation').classList.add('hidden');
        document.getElementById('etape-email').classList.remove('hidden');
    });
</script>
</body>
</html>