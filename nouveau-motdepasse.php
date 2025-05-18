<?php
// Inclusion des fichiers de configuration et sécurité
include 'config.php';
include 'security.php';

// Vérification du token et de l'email dans l'URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$email = isset($_GET['email']) ? $_GET['email'] : '';

if (empty($token) || empty($email)) {
    header('Location: motdepasse.php?error=Lien de réinitialisation invalide ou expiré');
    exit;
}

// Vérification que le token existe en base de données et n'est pas expiré
$query = "SELECT * FROM users WHERE email = ? AND reset_token = ? AND reset_token_expiry > NOW()";
global $conn;
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $email, $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header('Location: motdepasse.php?error=Lien de réinitialisation invalide ou expiré');
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
    <title>Nouveau mot de passe - OmnesBnB</title>
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
        .password-strength {
            height: 5px;
            transition: all 0.3s;
        }
        .password-feedback ul {
            list-style-type: disc;
            padding-left: 1.5rem;
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

<!-- Bandeau noir avec titre -->
<section class="bg-black py-12 px-4 flex flex-col items-center justify-center">
    <h1 class="text-white text-4xl md:text-5xl font-bold mb-3 text-center leading-tight">
        Créer un nouveau mot de passe
    </h1>
    <p class="text-white text-lg md:text-2xl mb-8 opacity-90 text-center">
        Sécurisez votre compte avec un mot de passe fort.
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

<!-- Formulaire de création de nouveau mot de passe -->
<section class="py-10 px-4 bg-white">
    <div class="container mx-auto">
        <!-- Étape de réinitialisation -->
        <div id="etape-reinitialisation">
            <form id="form-nouveau-mdp" action="process_nouveau_mdp.php" method="POST" class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <div class="text-center mb-6">
                    <i class="fas fa-lock text-5xl text-gray-800 mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">Réinitialisation du mot de passe</h2>
                    <p class="text-gray-600">
                        Pour le compte <span id="email-utilisateur" class="font-semibold"><?php echo htmlspecialchars($email); ?></span>
                    </p>
                </div>

                <div class="mb-2">
                    <label for="nouveau-mdp" class="block text-lg font-semibold text-gray-700 mb-2">Nouveau mot de passe</label>
                    <div class="relative">
                        <input type="password" id="nouveau-mdp" name="nouveau-mdp"
                               placeholder="Votre nouveau mot de passe"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                        <button type="button" id="toggle-password" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <!-- Indicateur de force du mot de passe -->
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-1">
                            <div id="password-strength" class="password-strength bg-red-500 rounded-full" style="width: 0%"></div>
                        </div>
                        <div id="password-feedback" class="password-feedback mt-2 text-sm text-gray-700">
                            <p>Votre mot de passe doit contenir :</p>
                            <ul class="mt-1">
                                <li id="length-check" class="text-red-600">Au moins 8 caractères</li>
                                <li id="uppercase-check" class="text-red-600">Au moins une lettre majuscule</li>
                                <li id="lowercase-check" class="text-red-600">Au moins une lettre minuscule</li>
                                <li id="number-check" class="text-red-600">Au moins un chiffre</li>
                                <li id="special-check" class="text-red-600">Au moins un caractère spécial (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="confirmer-mdp" class="block text-lg font-semibold text-gray-700 mb-2">Confirmer le mot de passe</label>
                    <input type="password" id="confirmer-mdp" name="confirmer-mdp"
                           placeholder="Confirmez votre mot de passe"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" required />
                    <p id="confirmation-feedback" class="mt-1 text-sm text-red-600 hidden">
                        Les mots de passe ne correspondent pas.
                    </p>
                </div>

                <button type="submit" id="btn-reinitialiser" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-xl" disabled>
                    Réinitialiser le mot de passe
                </button>

                <div class="text-center text-sm text-gray-600">
                    <p>
                        Ce lien de réinitialisation a été envoyé à votre adresse e-mail et est valide pendant 30 minutes.
                    </p>
                    <a href="connexion.php" class="text-black font-medium hover:underline mt-2 inline-block">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à la connexion
                    </a>
                </div>
            </form>
        </div>

        <!-- Étape succès (masquée par défaut) -->
        <div id="etape-succes" class="hidden">
            <div class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8">
                <div class="text-center mb-6">
                    <i class="fas fa-check-circle text-5xl text-green-600 mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">Mot de passe mis à jour !</h2>
                    <p class="text-gray-600">
                        Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
                    </p>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-3 text-xl"></i>
                        <p class="text-sm text-gray-700">
                            Par mesure de sécurité, nous vous avons envoyé une notification par e-mail pour confirmer ce changement.
                        </p>
                    </div>
                </div>

                <a href="connexion.php" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-xl block text-center">
                    Se connecter
                </a>
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

    // Afficher l'étape de succès si paramètre success présent
    <?php if (!empty($success)): ?>
    document.getElementById('etape-reinitialisation').classList.add('hidden');
    document.getElementById('etape-succes').classList.remove('hidden');
    <?php endif; ?>

    // Gestion de la visibilité du mot de passe
    document.getElementById('toggle-password').addEventListener('click', function() {
        const passwordInput = document.getElementById('nouveau-mdp');
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Validation du mot de passe et retour visuel
    const nouveauMdp = document.getElementById('nouveau-mdp');
    const confirmerMdp = document.getElementById('confirmer-mdp');
    const strengthBar = document.getElementById('password-strength');
    const btnReinitialiser = document.getElementById('btn-reinitialiser');
    const formNouveauMdp = document.getElementById('form-nouveau-mdp');
    const etapeReinitialisation = document.getElementById('etape-reinitialisation');
    const etapeSucces = document.getElementById('etape-succes');

    // Critères de validation
    const lengthCheck = document.getElementById('length-check');
    const uppercaseCheck = document.getElementById('uppercase-check');
    const lowercaseCheck = document.getElementById('lowercase-check');
    const numberCheck = document.getElementById('number-check');
    const specialCheck = document.getElementById('special-check');
    const confirmationFeedback = document.getElementById('confirmation-feedback');

    // Évaluation de la force du mot de passe
    nouveauMdp.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let color = 'red';

        // Vérification des critères
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        // Mise à jour des indicateurs visuels
        lengthCheck.className = hasLength ? 'text-green-600' : 'text-red-600';
        uppercaseCheck.className = hasUppercase ? 'text-green-600' : 'text-red-600';
        lowercaseCheck.className = hasLowercase ? 'text-green-600' : 'text-red-600';
        numberCheck.className = hasNumber ? 'text-green-600' : 'text-red-600';
        specialCheck.className = hasSpecial ? 'text-green-600' : 'text-red-600';

        // Calcul de la force
        if (hasLength) strength += 20;
        if (hasUppercase) strength += 20;
        if (hasLowercase) strength += 20;
        if (hasNumber) strength += 20;
        if (hasSpecial) strength += 20;

        // Couleur de la barre de force
        if (strength <= 40) {
            color = 'red';
        } else if (strength <= 80) {
            color = 'orange';
        } else {
            color = 'green';
        }

        // Mise à jour de la barre de force
        strengthBar.style.width = strength + '%';
        strengthBar.className = `password-strength bg-${color}-500 rounded-full`;

        // Vérification de la correspondance des mots de passe
        verifierCorrespondance();
    });

    // Vérification de la correspondance des mots de passe
    confirmerMdp.addEventListener('input', verifierCorrespondance);

    function verifierCorrespondance() {
        const password = nouveauMdp.value;
        const confirmation = confirmerMdp.value;

        if (confirmation === '') {
            confirmationFeedback.classList.add('hidden');
            btnReinitialiser.disabled = true;
            return;
        }

        if (password !== confirmation) {
            confirmationFeedback.classList.remove('hidden');
            btnReinitialiser.disabled = true;
            return;
        }

        confirmationFeedback.classList.add('hidden');

        // Vérification des critères de force
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        // Activation du bouton si tous les critères sont remplis
        btnReinitialiser.disabled = !(hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial);
    }
</script>
</body>
</html>