</main>

    <nav class="bottom-nav bg-white shadow-lg">
        <a href="<?php echo URL_SITE; ?>" class="nav-item <?php echo $page == 'accueil' ? 'nav-item-active' : 'nav-item-inactive'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Accueil</span>
        </a>
        <a href="<?php echo URL_SITE; ?>/logement/recherche.php" class="nav-item <?php echo $page == 'recherche' ? 'nav-item-active' : 'nav-item-inactive'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span>Rechercher</span>
        </a>
        <a href="<?php echo URL_SITE; ?>/logement/publier.php" class="nav-item <?php echo $page == 'publier' ? 'nav-item-active' : 'nav-item-inactive'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span>Publier</span>
        </a>
        <a href="<?php echo URL_SITE; ?>/locations/mes_locations.php" class="nav-item <?php echo $page == 'locations' ? 'nav-item-active' : 'nav-item-inactive'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Locations</span>
        </a>
        <a href="<?php echo URL_SITE; ?>/utilisateur/profil.php" class="nav-item <?php echo $page == 'profil' ? 'nav-item-active' : 'nav-item-inactive'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>Profil</span>
        </a>
    </nav>

    <!-- Scripts JavaScript -->
    <script src="<?php echo URL_SITE; ?>/assets/js/app.js"></script>
    
    <?php if (isset($scripts) && !empty($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?php echo URL_SITE; ?>/assets/js/<?php echo $script; ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Initialisation de Google Maps si nécessaire -->
    <?php if (isset($useGoogleMaps) && $useGoogleMaps): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.googleMaps) {
                    window.googleMaps.init('<?php echo GOOGLE_MAPS_API_KEY; ?>');
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
