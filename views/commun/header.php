<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titre) ? $titre . ' - OmnesBnB' : 'OmnesBnB'; ?></title>
    <link rel="stylesheet" href="<?php echo URL_SITE; ?>/assets/css/tailwind.css">
    <link rel="stylesheet" href="<?php echo URL_SITE; ?>/assets/css/custom.css">
    <link rel="icon" href="<?php echo URL_SITE; ?>/assets/img/logos/favicon.ico">
    <meta name="description" content="Plateforme de location de logements pour les étudiants et le personnel Omnes">
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <header class="header bg-white shadow-sm fixed top-0 left-0 right-0 z-50">
        <div class="container-mobile mx-auto py-4 px-4 flex justify-between items-center">
            <a href="<?php echo URL_SITE; ?>" class="flex items-center">
                <img src="<?php echo URL_SITE; ?>/assets/img/logos/logo.png" alt="OmnesBnB" class="h-8">
                <span class="font-bold text-black text-xl ml-2">OmnesBnB</span>
            </a>
            <?php if (estConnecte()): ?>
                <div class="flex items-center">
                    <a href="<?php echo URL_SITE; ?>/utilisateur/profil.php" class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden mr-2">
                            <?php if (!empty($_SESSION['utilisateur_photo'])): ?>
                                <img src="<?php echo urlPhotoProfil($_SESSION['utilisateur_photo']); ?>" alt="Profil" class="h-full w-full object-cover">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php else: ?>
                <a href="<?php echo URL_SITE; ?>/utilisateur/connexion.php" class="inline-block px-4 py-2 border border-black rounded-full text-sm font-medium text-black hover:bg-black hover:text-white transition-colors">
                    Connexion
                </a>
            <?php endif; ?>
        </div>
    </header>

    <main class="flex-grow pt-16">
