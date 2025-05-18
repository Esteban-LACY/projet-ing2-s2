<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification du token et de l'email dans l'URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$email = isset($_GET['email']) ? $_GET['email'] : '';

// Initialisation des variables
$error = '';
$success = '';
$verified = false;

// Si l'utilisateur est déjà connecté, le rediriger vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Vérification du token et de l'email
if (empty($token) || empty($email)) {
    $error = 'Lien de vérification invalide ou expiré.';
} else {
    // Vérifier que l'email existe dans la base et que le token correspond
    $query = "SELECT * FROM users WHERE email = ? AND verification_token = ?";
    global $conn;
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Vérifier si le compte n'est pas déjà activé
        if ($user['active'] == 1) {
            $verified = true;
            $success = 'Votre compte est déjà validé. Vous pouvez vous connecter.';
        } else {
            // Activer le compte
            $update_query = "UPDATE users SET active = 1, verification_token = NULL WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "i", $user['id']);

            if (mysqli_stmt_execute($update_stmt)) {
                $verified = true;
                $success = 'Votre compte a été validé avec succès. Vous pouvez maintenant vous connecter.';
            } else {
                $error = 'Une erreur est survenue lors de la validation de votre compte. Veuillez réessayer.';
            }
        }
    } else {
        $error = 'Lien de vérification invalide ou expiré.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de votre compte - OmnesBnB</title>
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
        Vérification de votre compte
    </h1>
    <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">
        Confirmation de votre adresse e-mail
    </p>
</section>

<!-- Section principale -->
<section class="py-10 px-4 bg-white">
    <div class="container mx-auto">
        <div class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto">
            <div class="text-center mb-6">
                <?php if ($verified): ?>
                    <i class="fas fa-check-circle text-5xl text-green-600 mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">Compte vérifié</h2>
                <?php else: ?>
                    <i class="fas fa-times-circle text-5xl text-red-600 mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">Échec de la vérification</h2>
                <?php endif; ?>
                <p class="text-gray-600">
                    <?php
                    if (!empty($success)) {
                        echo $success;
                    } elseif (!empty($error)) {
                        echo $error;
                    }
                    ?>
                </p>
            </div>

            <?php if ($verified): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                Votre adresse e-mail a été confirmée avec succès. Vous pouvez maintenant vous connecter à votre compte.
                            </p>
                        </div>
                    </div>
                </div>

                <a href="connexion.php" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-xl block text-center">
                    Se connecter
                </a>
            <?php else: ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                La vérification de votre compte a échoué. Le lien que vous avez utilisé est peut-être expiré ou invalide.
                            </p>
                        </div>
                    </div>
                </div>

                <a href="connexion.php?register=1" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-xl block text-center">
                    Créer un compte
                </a>
            <?php endif; ?>

            <div class="text-center mt-6">
                <a href="index.php" class="text-black font-medium hover:underline">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER identique -->
<section class="bg-black text-white py-12 px-4">
    <div class="container mx-auto max-w-3xl text-center">
        <h2 class="text-2xl font-bold mb-4">Besoin d'aide ?</h2>
        <p class="text-sm mb-6 opacity-90">
            Si vous rencontrez des difficultés pour accéder à votre compte, contactez notre équipe support.
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
</script>
</body>
</html>