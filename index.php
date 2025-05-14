<?php
// Point d'entrée principal de l'application
require_once 'config/config.php';
require_once 'includes/fonctions.php';

// Modèles nécessaires pour la page d'accueil
require_once 'models/logement.php';

// Initialisation
$logementModel = new LogementModel();

// Récupération des logements récents
$logementsRecents = $logementModel->recupererLogementsRecents(3);

// Inclusion de la vue
require_once 'views/commun/header.php';
require_once 'views/accueil/index.php';
require_once 'views/commun/footer.php';
?>
