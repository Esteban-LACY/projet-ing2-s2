<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titre) ? htmlspecialchars($titre) . ' - OmnesBnB' : 'OmnesBnB'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/custom.css">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>assets/img/logos/favicon.ico" type="image/x-icon">
    
    <!-- Police SF Pro Display (style Uber) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Configuration Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        black: '#000000',
                        white: '#FFFFFF',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white">
    <!-- En-tête (peut être vide, car la navigation principale est en bas) -->
    <header class="px-4 py-4 border-b border-gray-100">
        <?php if (!isCurrentPage('index.php')) : ?>
            <a href="<?php echo SITE_URL; ?>" class="inline-block">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        <?php endif; ?>
    </header>
    
    <!-- Contenu principal -->
    <main class="page-content">
