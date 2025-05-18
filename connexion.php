<?php
// Démarrer la session
global $conn;
session_start();

// Inclure le fichier de configuration
require_once "config.php";

// Définir les variables avec des valeurs vides
$email = $motDePasse = "";
$email_inscription = $nom_inscription = $prenom_inscription = $mdp_inscription = $mdp2_inscription = "";
$email_err = $motDePasse_err = $connexion_err = "";
$inscription_success = $inscription_err = "";

// Traitement du formulaire de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["connexion"])) {
    // Vérifier si l'email est vide
    if (empty(trim($_POST["email-connexion"]))) {
        $email_err = "Veuillez saisir votre adresse e-mail.";
    } else {
        $email = trim($_POST["email-connexion"]);
    }

    // Vérifier si le mot de passe est vide
    if (empty(trim($_POST["mdp-connexion"]))) {
        $motDePasse_err = "Veuillez saisir votre mot de passe.";
    } else {
        $motDePasse = trim($_POST["mdp-connexion"]);
    }

    // Valider les identifiants
    if (empty($email_err) && empty($motDePasse_err)) {
        // Préparer la requête de sélection
        $sql = "SELECT id, email, mot_de_passe, prenom, nom, type_utilisateur FROM utilisateurs WHERE email = :email";

        global $conn;

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);

            // Définir les paramètres
            $param_email = $email;

            // Exécuter la requête préparée
            $stmt->execute();

            // Si une ligne est trouvée (email existe)
            if ($stmt->rowCount() == 1) {
                // Récupérer les données de l'utilisateur
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $id = $row["id"];
                $hashed_password = $row["mot_de_passe"];
                $prenom = $row["prenom"];
                $nom = $row["nom"];
                $type_utilisateur = $row["type_utilisateur"];

                // Vérifier le mot de passe
                // Partie spécifique à la connexion à modifier dans votre code
                if (password_verify($motDePasse, $hashed_password)) {
                    // Mot de passe correct, démarrer une nouvelle session
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["email"] = $email;
                    $_SESSION["prenom"] = $prenom;
                    $_SESSION["nom"] = $nom;
                    $_SESSION["type_utilisateur"] = $type_utilisateur;

                    // Forcer la redirection sans possibilité d'échec
                    echo "<script>window.location.href = 'index.php';</script>";
                    header("location: index.php");
                    exit();
                } else {
                    // Le mot de passe n'est pas valide
                    $connexion_err = "L'adresse e-mail ou le mot de passe que vous avez saisi est incorrect.";
                }
            } else {
                // L'email n'existe pas
                $connexion_err = "L'adresse e-mail ou le mot de passe que vous avez saisi est incorrect.";
            }
        } catch(PDOException $e) {
            $connexion_err = "Oups! Quelque chose s'est mal passé. Veuillez réessayer plus tard. Erreur: " . $e->getMessage();
        }
    }
}

// Traitement du formulaire d'inscription
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["inscription"])) {
    // Validation du prénom
    if (empty(trim($_POST["prenom-inscription"]))) {
        $inscription_err = "Veuillez saisir votre prénom.";
    } else {
        $prenom_inscription = trim($_POST["prenom-inscription"]);
    }

    // Validation du nom
    if (empty(trim($_POST["nom-inscription"]))) {
        $inscription_err = "Veuillez saisir votre nom.";
    } else {
        $nom_inscription = trim($_POST["nom-inscription"]);
    }

    // Validation de l'email
    if (empty(trim($_POST["email-inscription"]))) {
        $inscription_err = "Veuillez saisir une adresse e-mail.";
    } else {
        // Vérifier si l'email est au format Omnes
        $email_inscription = trim($_POST["email-inscription"]);
        if (!preg_match("/@(ece\.fr|edu\.ece\.fr|omnesintervenant\.com)$/i", $email_inscription)) {
            $inscription_err = "Veuillez utiliser une adresse e-mail Omnes valide.";
        } else {
            // Vérifier si l'email existe déjà
            try {
                $sql = "SELECT id FROM utilisateurs WHERE email = :email";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":email", $email_inscription, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $inscription_err = "Cette adresse e-mail est déjà utilisée.";
                }
            } catch(PDOException $e) {
                $inscription_err = "Erreur de vérification: " . $e->getMessage();
            }
        }
    }

    // Validation du mot de passe
    if (empty(trim($_POST["mdp-inscription"]))) {
        $inscription_err = "Veuillez saisir un mot de passe.";
    } elseif (strlen(trim($_POST["mdp-inscription"])) < 6) {
        $inscription_err = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $mdp_inscription = trim($_POST["mdp-inscription"]);
    }

    // Vérification de la confirmation du mot de passe
    if (empty(trim($_POST["mdp2-inscription"]))) {
        $inscription_err = "Veuillez confirmer le mot de passe.";
    } else {
        $mdp2_inscription = trim($_POST["mdp2-inscription"]);
        if ($mdp_inscription != $mdp2_inscription) {
            $inscription_err = "Les mots de passe ne correspondent pas.";
        }
    }

    // Vérifier s'il n'y a pas d'erreurs avant d'insérer dans la base de données
    if (empty($inscription_err)) {
        try {
            // Préparer une requête d'insertion
            $sql = "INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, type_utilisateur) VALUES (:prenom, :nom, :email, :mot_de_passe, 'utilisateur')";
            $stmt = $conn->prepare($sql);

            // Définir les paramètres
            $param_prenom = $prenom_inscription;
            $param_nom = $nom_inscription;
            $param_email = $email_inscription;
            $param_password = password_hash($mdp_inscription, PASSWORD_DEFAULT); // Crée un mot de passe haché

            // Lier les paramètres
            $stmt->bindParam(":prenom", $param_prenom, PDO::PARAM_STR);
            $stmt->bindParam(":nom", $param_nom, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":mot_de_passe", $param_password, PDO::PARAM_STR);

            // Exécuter la requête
            if ($stmt->execute()) {
                // Rediriger vers la page de connexion avec un message de succès
                header("location: connexion.php?success=1");
                exit;
            } else {
                $inscription_err = "Oups! Quelque chose s'est mal passé. Veuillez réessayer plus tard.";
            }
        } catch(PDOException $e) {
            $inscription_err = "Erreur d'inscription: " . $e->getMessage();
        }
    }
}
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
        .alert-danger {
            background-color: #FEE2E2;
            border: 2px solid #EF4444;
            color: #B91C1C;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #D1FAE5;
            border: 2px solid #10B981;
            color: #047857;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
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
        <?php
        // Affichage de messages d'erreur ou de succès
        if(isset($_GET["success"]) && $_GET["success"] == 1) {
            echo '<div class="alert-success max-w-3xl mx-auto mb-4">Inscription réussie ! Vous pouvez maintenant vous connecter.</div>';
        }
        ?>

        <!-- Connexion -->
        <div id="bloc-connexion" class="w-full">
            <?php
            if(!empty($connexion_err)){
                echo '<div class="alert-danger max-w-3xl mx-auto mb-4">' . $connexion_err . '</div>';
            }
            ?>
            <form id="form-connexion" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8">
                <div>
                    <label for="email-connexion" class="block text-lg font-semibold text-gray-700 mb-2">Adresse e-mail</label>
                    <input type="email" id="email-connexion" name="email-connexion"
                           placeholder="exemple@omnes.fr"
                           class="w-full border-2 <?php echo (!empty($email_err)) ? 'border-red-500' : 'border-black'; ?> rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none"
                           value="<?php echo $email; ?>" />
                    <?php if(!empty($email_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $email_err; ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="mdp-connexion" class="block text-lg font-semibold text-gray-700 mb-2">Mot de passe</label>
                    <input type="password" id="mdp-connexion" name="mdp-connexion"
                           placeholder="Votre mot de passe"
                           class="w-full border-2 <?php echo (!empty($motDePasse_err)) ? 'border-red-500' : 'border-black'; ?> rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" />
                    <?php if(!empty($motDePasse_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $motDePasse_err; ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between items-center">
                    <label class="flex items-center cursor-pointer select-none">
                        <input type="checkbox" name="remember-me" class="checkbox-uber" />
                        <span class="text-base text-gray-700 font-medium">Se souvenir de moi</span>
                    </label>
                    <a href="mot-de-passe-oublie.php" class="lien-noir">Mot de passe oublié&nbsp;?</a>
                </div>
                <input type="hidden" name="connexion" value="1">
                <button type="submit" class="bg-black text-white font-bold py-4 px-8 rounded-lg w-full hover:bg-gray-800 transition-all shadow-md text-2xl">
                    Se connecter
                </button>
            </form>
        </div>

        <!-- Inscription -->
        <div id="bloc-inscription" class="w-full hidden">
            <?php
            if(!empty($inscription_err)){
                echo '<div class="alert-danger max-w-3xl mx-auto mb-4">' . $inscription_err . '</div>';
            }
            if(!empty($inscription_success)){
                echo '<div class="alert-success max-w-3xl mx-auto mb-4">' . $inscription_success . '</div>';
            }
            ?>
            <form id="form-inscription" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form-box bg-white border-2 border-black rounded-2xl shadow-lg p-8 mx-auto space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="prenom-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Prénom</label>
                        <input type="text" id="prenom-inscription" name="prenom-inscription"
                               placeholder="Votre prénom"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none"
                               value="<?php echo $prenom_inscription; ?>" />
                    </div>
                    <div>
                        <label for="nom-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Nom</label>
                        <input type="text" id="nom-inscription" name="nom-inscription"
                               placeholder="Votre nom"
                               class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none"
                               value="<?php echo $nom_inscription; ?>" />
                    </div>
                </div>
                <div>
                    <label for="email-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Adresse e-mail</label>
                    <input type="email" id="email-inscription" name="email-inscription"
                           placeholder="exemple@omnes.fr"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none"
                           value="<?php echo $email_inscription; ?>" />
                </div>
                <div>
                    <label for="mdp-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Mot de passe</label>
                    <input type="password" id="mdp-inscription" name="mdp-inscription"
                           placeholder="Créez un mot de passe"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" />
                </div>
                <div>
                    <label for="mdp2-inscription" class="block text-lg font-semibold text-gray-700 mb-2">Confirmez le mot de passe</label>
                    <input type="password" id="mdp2-inscription" name="mdp2-inscription"
                           placeholder="Confirmez votre mot de passe"
                           class="w-full border-2 border-black rounded-lg py-3 px-4 bg-white text-gray-900 text-lg placeholder-gray-400 focus:outline-none" />
                </div>
                <input type="hidden" name="inscription" value="1">
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

    // Si l'URL contient un paramètre pour l'onglet inscription
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('inscription')) {
        afficherInscription();
    } else {
        afficherConnexion();
    }
</script>
</body>
</html>