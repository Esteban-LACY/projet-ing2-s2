</main>
    
    <nav class="bg-white shadow-md py-3 fixed bottom-0 inset-x-0">
        <div class="container mx-auto flex justify-between items-center px-4">
            <a href="<?= APP_URL ?>" class="text-center">
                <div class="w-6 h-6 mx-auto mb-1">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                </div>
                <span class="text-xs">OmnesBnB</span>
            </a>
            
            <a href="<?= APP_URL ?>/publier.php" class="text-center">
                <div class="w-6 h-6 mx-auto mb-1">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <span class="text-xs">Publier</span>
            </a>
            
            <a href="<?= APP_URL ?>/profil.php" class="text-center">
                <div class="w-6 h-6 mx-auto mb-1">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <span class="text-xs">Profil</span>
            </a>
        </div>
    </nav>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    <?php if (isset($scripts)) echo $scripts; ?>
</body>
</html>
