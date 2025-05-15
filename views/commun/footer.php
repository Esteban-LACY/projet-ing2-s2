</main>
    
    <!-- Navigation principale (en bas pour mobile) -->
    <nav class="bottom-nav">
        <a href="<?php echo SITE_URL; ?>" class="nav-item <?php echo isCurrentPage('index.php') ? 'nav-item-active' : 'nav-item-inactive'; ?>" data-href="index.php">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>OmnesBnB</span>
        </a>
        
        <a href="<?php echo SITE_URL; ?>publier.php" class="nav-item <?php echo isCurrentPage('publier.php') ? 'nav-item-active' : 'nav-item-inactive'; ?>" data-href="publier.php">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Publier</span>
        </a>
        
        <?php if (estConnecte()) : ?>
            <a href="<?php echo SITE_URL; ?>profil.php" class="nav-item <?php echo isCurrentPage('profil.php') ? 'nav-item-active' : 'nav-item-inactive'; ?>" data-href="profil.php">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Profil</span>
            </a>
        <?php else : ?>
            <a href="<?php echo SITE_URL; ?>connexion.php" class="nav-item <?php echo isCurrentPage('connexion.php') ? 'nav-item-active' : 'nav-item-inactive'; ?>" data-href="connexion.php">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Connexion</span>
            </a>
        <?php endif; ?>
    </nav>
    
    <!-- Scripts -->
    <script src="<?php echo SITE_URL; ?>assets/js/app.js"></script>
    
    <!-- Scripts spécifiques aux pages -->
    <?php if (isCurrentPage('index.php') || isCurrentPage('recherche.php')) : ?>
        <script src="<?php echo SITE_URL; ?>assets/js/recherche.js"></script>
    <?php endif; ?>
    
    <?php if (isCurrentPage('profil.php')) : ?>
        <script src="<?php echo SITE_URL; ?>assets/js/profil.js"></script>
    <?php endif; ?>
    
    <?php if (isCurrentPage('logement/details.php') || isCurrentPage('reservation/confirmation.php')) : ?>
        <script src="https://js.stripe.com/v3/"></script>
        <script src="<?php echo SITE_URL; ?>assets/js/stripe.js"></script>
    <?php endif; ?>
    
    <?php if (isCurrentPage('publier.php') || isCurrentPage('recherche.php') || isCurrentPage('logement/details.php')) : ?>
        <script src="<?php echo SITE_URL; ?>assets/js/maps.js"></script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places&callback=initGoogleMaps"></script>
    <?php endif; ?>
</body>
</html>

<?php
/**
 * Vérifie si la page courante correspond au nom de fichier fourni
 * 
 * @param string $page Nom du fichier à vérifier
 * @return bool True si la page courante correspond, false sinon
 */
function isCurrentPage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page;
}
?>
