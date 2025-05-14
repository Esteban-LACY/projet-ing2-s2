<section class="mb-8">
    <form action="<?= APP_URL ?>/recherche.php" method="get" class="mb-6">
        <div class="mb-4">
            <input type="text" name="lieu" placeholder="Lieu" class="w-full p-3 border rounded" required>
        </div>
        <div class="mb-4">
            <input type="date" name="dates" placeholder="Dates" class="w-full p-3 border rounded">
        </div>
        <button type="submit" class="w-full bg-black text-white py-3 px-4 rounded font-medium">Rechercher</button>
    </form>
</section>

<section>
    <h2 class="text-2xl font-bold mb-4">Chercher un logement</h2>
    
    <?php if (empty($logementsRecents)): ?>
    <p class="text-gray-600">Aucun logement disponible pour le moment.</p>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($logementsRecents as $logement): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="flex">
                <div class="w-1/3">
                    <img src="<?= !empty($logement['photo_url']) ? $logement['photo_url'] : APP_URL . '/assets/img/placeholders/logement.jpg' ?>" 
                         alt="<?= htmlspecialchars($logement['titre']) ?>" 
                         class="h-full w-full object-cover">
                </div>
                <div class="w-2/3 p-4">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($logement['titre']) ?></h3>
                    <p class="text-gray-600"><?= htmlspecialchars($logement['ville']) ?></p>
                    <div class="mt-2 text-right">
                        <a href="<?= APP_URL ?>/logement.php?id=<?= $logement['id'] ?>" class="text-black font-medium">Voir plus &gt;</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
