/**
 * Gestion de la recherche et du filtrage des logements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const searchForm = document.getElementById('search-form');
    const searchResults = document.getElementById('search-results');
    const filtersToggle = document.getElementById('filters-toggle');
    const filtersPanel = document.getElementById('filters-panel');
    
    // Paramètres de recherche
    let searchParams = new URLSearchParams(window.location.search);
    
    // Initialiser la recherche
    initSearchForm();
    initFilters();
    
    /**
     * Initialise le formulaire de recherche
     */
    function initSearchForm() {
        if (!searchForm) return;
        
        // Préremplir les champs de recherche avec les paramètres d'URL
        const lieuInput = searchForm.querySelector('[name="lieu"]');
        const dateDebutInput = searchForm.querySelector('[name="date_debut"]');
        const dateFinInput = searchForm.querySelector('[name="date_fin"]');
        
        if (lieuInput && searchParams.has('lieu')) {
            lieuInput.value = searchParams.get('lieu');
        }
        
        if (dateDebutInput && searchParams.has('date_debut')) {
            dateDebutInput.value = searchParams.get('date_debut');
        }
        
        if (dateFinInput && searchParams.has('date_fin')) {
            dateFinInput.value = searchParams.get('date_fin');
        }
        
        // Soumettre le formulaire pour rechercher
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Construire l'URL de recherche
            const formData = new FormData(searchForm);
            const params = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            // Rediriger vers la page de résultats
            window.location.href = 'recherche.php?' + params.toString();
        });
    }
    
    /**
     * Initialise les filtres de recherche
     */
    function initFilters() {
        if (!filtersToggle || !filtersPanel) return;
        
        // Toggle pour afficher/masquer les filtres
        filtersToggle.addEventListener('click', function() {
            filtersPanel.classList.toggle('hidden');
        });
        
        // Gestion des changements de filtres
        const filterInputs = filtersPanel.querySelectorAll('input, select');
        
        filterInputs.forEach(input => {
            // Préremplir avec les valeurs de l'URL
            if (searchParams.has(input.name)) {
                if (input.type === 'checkbox') {
                    input.checked = searchParams.get(input.name) === 'on';
                } else {
                    input.value = searchParams.get(input.name);
                }
            }
            
            // Mettre à jour les résultats au changement
            input.addEventListener('change', function() {
                applyFilters();
            });
        });
        
        // Appliquer les filtres initiaux
        if (filterInputs.length > 0) {
            applyFilters();
        }
    }
    
    /**
     * Applique les filtres et met à jour les résultats
     */
    function applyFilters() {
        if (!searchResults) return;
        
        // Afficher l'état de chargement
        searchResults.classList.add('loading');
        
        // Collecter tous les filtres
        const formData = new FormData(filtersPanel.closest('form'));
        const params = new URLSearchParams(searchParams);
        
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.set(key, value);
            } else {
                params.delete(key);
            }
        }
        
        // Appel AJAX pour obtenir les résultats filtrés
        fetch('controllers/recherche.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                updateSearchResults(data);
                
                // Mettre à jour l'URL sans recharger la page
                const newUrl = 'recherche.php?' + params.toString();
                window.history.pushState({}, '', newUrl);
                
                // Mettre à jour la référence des paramètres
                searchParams = params;
            })
            .catch(error => {
                console.error('Erreur lors de la recherche:', error);
            })
            .finally(() => {
                searchResults.classList.remove('loading');
            });
    }
    
    /**
     * Met à jour l'affichage des résultats de recherche
     * @param {Array} logements - Liste des logements à afficher
     */
    function updateSearchResults(logements) {
        if (!searchResults) return;
        
        // Vider les résultats actuels
        searchResults.innerHTML = '';
        
        // Vérifier s'il y a des résultats
        if (logements.length === 0) {
            searchResults.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-lg">Aucun logement ne correspond à votre recherche.</p>
                    <p class="text-gray-500 mt-2">Essayez de modifier vos critères.</p>
                </div>
            `;
            return;
        }
        
        // Créer la liste des logements
        logements.forEach(logement => {
            const logementElement = createLogementCard(logement);
            searchResults.appendChild(logementElement);
        });
        
        // Mettre à jour la carte si elle existe
        if (window.googleMaps && window.googleMaps.map) {
            // Supprimer les marqueurs existants
            if (window.googleMaps.markers) {
                window.googleMaps.markers.forEach(marker => marker.setMap(null));
                window.googleMaps.markers = [];
            }
            
            // Ajouter les nouveaux marqueurs
            logements.forEach(logement => {
                window.googleMaps.addPropertyMarker(logement);
            });
            
            // Ajuster la vue
            window.googleMaps.fitMapToMarkers();
        }
    }
    
    /**
     * Crée un élément de carte pour un logement
     * @param {Object} logement - Données du logement
     * @returns {HTMLElement} - Élément DOM représentant le logement
     */
    function createLogementCard(logement) {
        const card = document.createElement('div');
        card.className = 'property-card card mb-4';
        
        // Image principale ou placeholder
        const imageUrl = logement.photo_principale || '/assets/img/placeholders/logement.jpg';
        
        card.innerHTML = `
            <div class="relative">
                <img src="${imageUrl}" alt="${logement.titre}" class="property-image">
                <div class="absolute bottom-2 right-2 bg-white rounded-full px-2 py-1 text-sm font-semibold">
                    ${logement.prix}€ / nuit
                </div>
            </div>
            <div class="property-info">
                <h3 class="font-semibold">${logement.titre}</h3>
                <p class="text-gray-500">${logement.ville}</p>
                <p class="text-sm mt-1">${logement.type_logement === 'entier' ? 'Logement entier' : 'Collocation'}</p>
                <a href="logement/details.php?id=${logement.id}" class="btn-primary mt-3">Voir plus</a>
            </div>
        `;
        
        // Ajouter l'écouteur d'événements pour la navigation
        card.querySelector('a.btn-primary').addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            navigateToPage(url);
        });

        return card;
    }

    /**
     * Navigation vers une page avec transition
     * @param {string} url - URL de destination
     */
    function navigateToPage(url) {
        if (searchResults) {
            searchResults.classList.add('loading');
        }
        setTimeout(() => {
            window.location.href = url;
        }, 300);
    }
});
