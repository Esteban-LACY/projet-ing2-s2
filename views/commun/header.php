<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre) ? $titre . ' - ' . APP_NAME : APP_NAME ?></title>
    <meta name="description" content="Plateforme de logements pour les étudiants et le personnel Omnes.">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/custom.css">
    <?php if (isset($styles)) echo $styles; ?>
</head>
<body class="flex flex-col min-h-screen bg-gray-100">
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <h1 class="text-3xl font-bold"><?= APP_NAME ?></h1>
            <p class="text-lg">Plateforme de logements pour les étudiants et le personnel Omnes.</p>
        </div>
    </header>
    
    <main class="flex-grow container mx-auto px-4 py-6">
        <?php
        // Affichage des alertes
        $alerte = recupererAlerte();
        if ($alerte): 
            $couleurs = [
                'success' => 'bg-green-100 border-green-500 text-green-700',
                'danger' => 'bg-red-100 border-red-500 text-red-700',
                'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
                'info' => 'bg-blue-100 border-blue-500 text-blue-700'
            ];
            $couleur = $couleurs[$alerte['type']] ?? $couleurs['info'];
        ?>
        <div class="<?= $couleur ?> px-4 py-3 mb-4 rounded border" role="alert">
            <?= $alerte['message'] ?>
        </div>
        <?php endif; ?>
