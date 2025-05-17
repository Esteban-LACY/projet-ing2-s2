/**
 * Gestion de la recherche et du filtrage des logements
 * Version mobile-first optimisée pour OmnesBnB
 */

class RechercheManager {
    constructor() {
        // Éléments du DOM
        this.searchForm = null;
        this.searchResults = null;
        this.filtersToggle = null;
        this.filtersPanel = null;
        this.sortToggle = null;
        this.sortOptions = null;
        this.mapToggle = null;
        this.mapContainer = null;
        this.loadingIndicator = null;
        this.noResultsMessage = null;
        
        // État de la recherche
        this.currentSearchParams = new URLSearchParams(window.location.search);
        this.isLoading = false;
        this.isMapView = false;
        this.lastScrollPosition = 0;
        this.resultsCount = 0;
        
        // Paramètres de pagination
        this.page = 1;
        this.limit = 10;
        this.hasMoreResults = true;
        
        // État des filtres
        this.filtersVisible = false;
        this.activeFilters = {
            lieu: '',
            date_debut: '',
            date_fin: '',
            prix_min: '',
            prix_max: '',
            type_logement: '',
            nb_places: ''
        };
    }
    
    /**
     * Initialise le gestionnaire de recherche
     */
    init() {
        // Initialiser les références aux éléments DOM
        this.searchForm = document.getElementById('search-form');
        this.searchResults = document.getElementById('search-results');
        this.filtersToggle = document.getElementById('filters-toggle');
        this.filtersPanel = document.getElementById('filters-panel');
        this.sortToggle = document.getElementById('sort-toggle');
        this.sortOptions = document.getElementById('sort-options');
        this.mapToggle = document.getElementById('map-toggle');
        this.mapContainer = document.getElementById('search-map');
        this.loadingIndicator = document.getElementById('loading-indicator');
        this.noResultsMessage = document.getElementById('no-results-message');
        
        // Vérifier les éléments requis
        if (!this.searchForm || !this.searchResults) return;
        
        // Initialiser les composants
        this.initSearchForm();
        this.initFilters();
        this.initSorting();
        this.initMapToggle();
        this.setupInfiniteScroll();
        
        // Charger les valeurs des paramètres actuels
        this.loadCurrentFilters();
        
        // Mettre à jour les compteurs de filtres actifs
        this.updateActiveFiltersCount();
    }
    
    /**
     * Initialise le formulaire de recherche principal
     */
    initSearchForm() {
        if (!this.searchForm) return;
        
        // Préremplir les champs avec les valeurs de l'URL
        const lieuInput = this.searchForm.querySelector('[name="lieu"]');
        const dateDebutInput = this.searchForm.querySelector('[name="date_debut"]');
        const dateFinInput = this.searchForm.querySelector('[name="date_fin"]');
        
        if (lieuInput && this.currentSearchParams.has('lieu')) {
            lieuInput.value = this.currentSearchParams.get('lieu');
            this.activeFilters.lieu = this.currentSearchParams.get('lieu');
        }
        
        if (dateDebutInput && this.currentSearchParams.has('date_debut')) {
            dateDebutInput.value = this.currentSearchParams.get('date_debut');
            this.activeFilters.date_debut = this.currentSearchParams.get('date_debut');
        }
        
        if (dateFinInput && this.currentSearchParams.has('date_fin')) {
            dateFinInput.value = this.currentSearchParams.get('date_fin');
            this.activeFilters.date_fin = this.currentSearchParams.get('date_fin');
        }
        
        // Gérer la soumission du formulaire
        this.searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Réinitialiser la pagination
            this.page = 1;
            this.hasMoreResults = true;
            
            // Mettre à jour les paramètres de recherche
            const formData = new FormData(this.searchForm);
            this.currentSearchParams = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                if (value) {
                    this.currentSearchParams.append(key, value);
                    this.activeFilters[key] = value;
                } else {
                    this.activeFilters[key] = '';
                }
            }
            
            // Effectuer la recherche
            this.performSearch(true);
        });
        
        // Animation du champ de recherche sur focus
        const searchBar = this.searchForm.querySelector('.search-bar');
        if (searchBar) {
            const searchInput = searchBar.querySelector('input');
            if (searchInput) {
                searchInput.addEventListener('focus', () => {
                    searchBar.classList.add('focused');
                });
                
                searchInput.addEventListener('blur', () => {
                    searchBar.classList.remove('focused');
                });
            }
        }
    }
    
    /**
     * Initialise les fonctionnalités de filtrage
     */
    initFilters() {
        if (!this.filtersToggle || !this.filtersPanel) return;
        
        // Gestionnaire pour afficher/masquer les filtres
        this.filtersToggle.addEventListener('click', () => {
            this.toggleFilters();
        });
        
        // Gestionnaire de fermeture du panneau de filtres
        const closeButton = this.filtersPanel.querySelector('.close-filters');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                this.toggleFilters(false);
            });
        }
        
        // Gestionnaire pour appliquer les filtres
        const applyButton = this.filtersPanel.querySelector('.apply-filters');
        if (applyButton) {
            applyButton.addEventListener('click', () => {
                this.applyFilters();
            });
        }
        
        // Gestionnaire pour réinitialiser les filtres
        const resetButton = this.filtersPanel.querySelector('.reset-filters');
        if (resetButton) {
            resetButton.addEventListener('click', () => {
                this.resetFilters();
            });
        }
        
        // Gestionnaires pour les contrôles de filtre interactifs
        this.initRangeSliders();
        this.initDatePickers();
        this.initTypeLogementSelector();
        this.initNumberSteppers();
    }
    
    /**
     * Charge les valeurs des filtres actuels depuis l'URL
     */
    loadCurrentFilters() {
        // Mettre à jour les filtres avec les paramètres de l'URL
        for (const [key, value] of this.currentSearchParams.entries()) {
            this.activeFilters[key] = value;
            
            // Mettre à jour les contrôles de filtre
            const input = this.filtersPanel?.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = value === 'on';
                } else {
                    input.value = value;
                }
                
                // Mise à jour visuelle des sliders
                if (input.classList.contains('range-slider')) {
                    this.updateRangeSliderDisplay(input);
                }
            }
        }
    }
    
    /**
     * Initialise les sliders pour les filtres de prix
     */
    initRangeSliders() {
        const priceSliders = this.filtersPanel?.querySelectorAll('.range-slider');
        if (!priceSliders) return;
        
        priceSliders.forEach(slider => {
            const valueDisplay = document.getElementById(`${slider.id}-value`);
            const min = parseInt(slider.getAttribute('min'));
            const max = parseInt(slider.getAttribute('max'));
            
            // Initialiser la valeur affichée
            this.updateRangeSliderDisplay(slider);
            
            slider.addEventListener('input', () => {
                this.updateRangeSliderDisplay(slider);
            });
            
            // Double tap pour réinitialiser
            let lastTap = 0;
            slider.addEventListener('click', (e) => {
                const currentTime = new Date().getTime();
                const tapLength = currentTime - lastTap;
                
                if (tapLength < 300 && tapLength > 0) {
                    // Double tap détecté
                    if (slider.id === 'prix_min') {
                        slider.value = min;
                    } else if (slider.id === 'prix_max') {
                        slider.value = max;
                    }
                    this.updateRangeSliderDisplay(slider);
                    e.preventDefault();
                }
                
                lastTap = currentTime;
            });
        });
    }
    
    /**
     * Met à jour l'affichage d'un slider
     * @param {HTMLElement} slider - Élément slider
     */
    updateRangeSliderDisplay(slider) {
        const valueDisplay = document.getElementById(`${slider.id}-value`);
        if (!valueDisplay) return;
        
        const value = slider.value;
        const min = parseInt(slider.getAttribute('min'));
        const max = parseInt(slider.getAttribute('max'));
        
        // Calculer la position de l'indicateur
        const percentage = ((value - min) / (max - min)) * 100;
        valueDisplay.style.left = `calc(${percentage}% - 20px)`;
        
        // Mettre à jour la valeur affichée
        if (slider.id === 'prix_min' || slider.id === 'prix_max') {
            valueDisplay.textContent = `${value}€`;
        } else {
            valueDisplay.textContent = value;
        }
        
        // Mettre à jour la barre de progression
        const progressBar = slider.parentNode.querySelector('.slider-progress');
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }
    }
    
    /**
     * Initialise les sélecteurs de date
     */
    initDatePickers() {
        const datePickers = this.filtersPanel?.querySelectorAll('input[type="date"]');
        if (!datePickers) return;
        
        datePickers.forEach(picker => {
            // Définir la date minimale à aujourd'hui
            if (picker.id === 'date_debut' || picker.id === 'date_fin') {
                const today = new Date().toISOString().split('T')[0];
                picker.setAttribute('min', today);
            }
            
            // Gérer les dépendances entre les dates
            picker.addEventListener('change', () => {
                if (picker.id === 'date_debut') {
                    const dateFinPicker = document.getElementById('date_fin');
                    if (dateFinPicker && picker.value) {
                        dateFinPicker.setAttribute('min', picker.value);
                        
                        // Si la date de fin est antérieure à la date de début, la réinitialiser
                        if (dateFinPicker.value && dateFinPicker.value < picker.value) {
                            dateFinPicker.value = picker.value;
                        }
                    }
                }
            });
        });
    }
    
    /**
     * Initialise le sélecteur de type de logement
     */
    initTypeLogementSelector() {
        const typeButtons = this.filtersPanel?.querySelectorAll('.type-selector button');
        if (!typeButtons) return;
        
        const typeInput = document.getElementById('type_logement');
        if (!typeInput) return;
        
        // Initialiser l'état actif
        const activeType = typeInput.value;
        if (activeType) {
            const activeButton = Array.from(typeButtons).find(btn => btn.dataset.type === activeType);
            if (activeButton) {
                activeButton.classList.add('active');
            }
        }
        
        typeButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Réinitialiser tous les boutons
                typeButtons.forEach(btn => btn.classList.remove('active'));
                
                // Activer le bouton cliqué
                button.classList.add('active');
                
                // Mettre à jour la valeur de l'input
                typeInput.value = button.dataset.type;
            });
        });
    }
    
    /**
     * Initialise les contrôles pour les nombres
     */
    initNumberSteppers() {
        const steppers = this.filtersPanel?.querySelectorAll('.number-stepper');
        if (!steppers) return;
        
        steppers.forEach(stepper => {
            const input = stepper.querySelector('input[type="number"]');
            const decrement = stepper.querySelector('.decrement');
            const increment = stepper.querySelector('.increment');
            
            if (!input || !decrement || !increment) return;
            
            decrement.addEventListener('click', () => {
                const min = parseInt(input.getAttribute('min') || 0);
                const value = parseInt(input.value || 0);
                
                if (value > min) {
                    input.value = value - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
            
            increment.addEventListener('click', () => {
                const max = parseInt(input.getAttribute('max') || 99);
                const value = parseInt(input.value || 0);
                
                if (value < max) {
                    input.value = value + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });
    }
    
    /**
     * Initialise le tri des résultats
     */
    initSorting() {
        if (!this.sortToggle || !this.sortOptions) return;
        
        // Gestionnaire pour afficher/masquer les options de tri
        this.sortToggle.addEventListener('click', () => {
            this.sortOptions.classList.toggle('visible');
            
            // Fermer le panneau de filtre si ouvert
            if (this.filtersVisible) {
                this.toggleFilters(false);
            }
        });
        
        // Gestionnaire pour les options de tri
        const sortItems = this.sortOptions.querySelectorAll('li');
        sortItems.forEach(item => {
            item.addEventListener('click', () => {
                // Mettre à jour le texte et l'icône du bouton de tri
                const value = item.dataset.value;
                const text = item.textContent;
                
                this.sortToggle.querySelector('.sort-text').textContent = text;
                
                // Mettre à jour le paramètre de tri
                this.currentSearchParams.set('tri', value);
                
                // Réinitialiser la pagination
                this.page = 1;
                this.hasMoreResults = true;
                
                // Masquer les options
                this.sortOptions.classList.remove('visible');
                
                // Effectuer la recherche
                this.performSearch(true);
            });
        });
        
        // Fermer les options de tri si on clique ailleurs
        document.addEventListener('click', (e) => {
            if (!this.sortToggle.contains(e.target) && !this.sortOptions.contains(e.target)) {
                this.sortOptions.classList.remove('visible');
            }
        });
    }
    
    /**
     * Initialise la bascule entre vue liste et vue carte
     */
    initMapToggle() {
        if (!this.mapToggle || !this.mapContainer || !this.searchResults) return;
        
        this.mapToggle.addEventListener('click', () => {
            this.isMapView = !this.isMapView;
            
            if (this.isMapView) {
                // Passer à la vue carte
                this.searchResults.classList.add('hidden');
                this.mapContainer.classList.remove('hidden');
                this.mapToggle.querySelector('span').textContent = 'view_list';
                this.mapToggle.querySelector('.toggle-text').textContent = 'Liste';
                
                // Redimensionner la carte
                if (window.googleMaps && window.googleMaps.resizeMap) {
                    window.googleMaps.resizeMap();
                }
                
                // Désactiver le scroll infini
                window.removeEventListener('scroll', this.scrollHandler);
            } else {
                // Passer à la vue liste
                this.searchResults.classList.remove('hidden');
                this.mapContainer.classList.add('hidden');
                this.mapToggle.querySelector('span').textContent = 'map';
                this.mapToggle.querySelector('.toggle-text').textContent = 'Carte';
                
                // Réactiver le scroll infini
                this.setupInfiniteScroll();
            }
        });
    }
    
    /**
     * Configure le défilement infini pour charger plus de résultats
     */
    setupInfiniteScroll() {
        // Créer un gestionnaire de défilement lié à cette instance
        this.scrollHandler = () => {
            if (this.isLoading || !this.hasMoreResults || this.isMapView) return;
            
            const scrollY = window.scrollY || window.pageYOffset;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            
            // Vérifier si on a atteint le bas de la page (avec une marge)
            if (scrollY + windowHeight >= documentHeight - 200) {
                this.loadMoreResults();
            }
        };
        
        // Ajouter l'écouteur d'événements
        window.addEventListener('scroll', this.scrollHandler);
    }
    
    /**
     * Affiche ou masque le panneau de filtres
     * @param {boolean} [show] - Si défini, force l'état du panneau
     */
    toggleFilters(show) {
        if (show === undefined) {
            this.filtersVisible = !this.filtersVisible;
        } else {
            this.filtersVisible = show;
        }
        
        if (this.filtersVisible) {
            // Afficher le panneau de filtres
            this.filtersPanel.classList.add('visible');
            document.body.classList.add('filters-open');
            
            // Enregistrer la position de défilement
            this.lastScrollPosition = window.scrollY;
            
            // Désactiver le défilement de la page
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.width = '100%';
            document.body.style.top = `-${this.lastScrollPosition}px`;
        } else {
            // Masquer le panneau de filtres
            this.filtersPanel.classList.remove('visible');
            document.body.classList.remove('filters-open');
            
            // Réactiver le défilement de la page
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.width = '';
            document.body.style.top = '';
            
            // Restaurer la position de défilement
            window.scrollTo(0, this.lastScrollPosition);
        }
    }
    
    /**
     * Applique les filtres et effectue une nouvelle recherche
     */
    applyFilters() {
        // Récupérer tous les filtres
        const filters = this.filtersPanel.querySelectorAll('input, select');
        
        // Réinitialiser les paramètres de recherche
        this.currentSearchParams = new URLSearchParams();
        
        // Ajouter les filtres actifs
        filters.forEach(filter => {
            if (filter.type === 'checkbox') {
                if (filter.checked) {
                    this.currentSearchParams.set(filter.name, 'on');
                    this.activeFilters[filter.name] = 'on';
                } else {
                    this.activeFilters[filter.name] = '';
                }
            } else if (filter.value) {
                this.currentSearchParams.set(filter.name, filter.value);
                this.activeFilters[filter.name] = filter.value;
            } else {
                this.activeFilters[filter.name] = '';
            }
        });
        
        // Réinitialiser la pagination
        this.page = 1;
        this.hasMoreResults = true;
        
        // Fermer le panneau de filtres
        this.toggleFilters(false);
        
        // Mettre à jour les compteurs de filtres actifs
        this.updateActiveFiltersCount();
        
        // Effectuer la recherche
        this.performSearch(true);
    }
    
    /**
     * Réinitialise tous les filtres
     */
    resetFilters() {
        const filters = this.filtersPanel.querySelectorAll('input, select');
        
        filters.forEach(filter => {
            if (filter.type === 'checkbox') {
                filter.checked = false;
            } else if (filter.type === 'number') {
                filter.value = filter.getAttribute('min') || '0';
            } else {
                filter.value = '';
            }
            
            // Mise à jour visuelle des sliders
            if (filter.classList.contains('range-slider')) {
                this.updateRangeSliderDisplay(filter);
            }
            
            this.activeFilters[filter.name] = '';
        });
        
        // Réinitialiser les boutons de type de logement
        const typeButtons = this.filtersPanel.querySelectorAll('.type-selector button');
        if (typeButtons) {
            typeButtons.forEach(btn => btn.classList.remove('active'));
        }
        
        // Mettre à jour les compteurs de filtres actifs
        this.updateActiveFiltersCount();
    }
    
    /**
     * Met à jour le compteur de filtres actifs
     */
    updateActiveFiltersCount() {
        const filterCount = document.querySelector('.filter-count');
        if (!filterCount) return;
        
        // Compter les filtres actifs
        let count = 0;
        for (const key in this.activeFilters) {
            if (this.activeFilters[key] && key !== 'lieu') {
                count++;
            }
        }
        
        // Mettre à jour le compteur
        filterCount.textContent = count;
        
        // Afficher ou masquer le compteur
        if (count > 0) {
            filterCount.classList.remove('hidden');
        } else {
            filterCount.classList.add('hidden');
        }
    }
    
    /**
     * Effectue une recherche avec les paramètres actuels
     * @param {boolean} resetResults - Si true, remplace les résultats actuels
     */
    performSearch(resetResults = false) {
        if (this.isLoading) return;
        
        // Indiquer que la recherche est en cours
        this.isLoading = true;
        
        if (this.loadingIndicator) {
            this.loadingIndicator.classList.remove('hidden');
        }
        
        if (resetResults && this.searchResults) {
            this.searchResults.innerHTML = '';
        }
        
        // Ajouter le paramètre de page
        this.currentSearchParams.set('page', this.page);
        
        // Effectuer la requête AJAX
        fetch(`controllers/recherche.php?${this.currentSearchParams.toString()}`)
            .then(response => response.json())
            .then(data => {
                // Masquer l'indicateur de chargement
                if (this.loadingIndicator) {
                    this.loadingIndicator.classList.add('hidden');
                }
                
                // Traiter les résultats
                this.processSearchResults(data, resetResults);
                
                // Mettre à jour l'URL sans recharger la page
                const newUrl = `recherche.php?${this.currentSearchParams.toString()}`;
                window.history.pushState({}, '', newUrl);
                
                this.isLoading = false;
            })
            .catch(error => {
                console.error('Erreur lors de la recherche:', error);
                
                // Masquer l'indicateur de chargement
                if (this.loadingIndicator) {
                    this.loadingIndicator.classList.add('hidden');
                }
                
                this.isLoading = false;
            });
    }
    
    /**
     * Charge plus de résultats (pagination)
     */
    loadMoreResults() {
        if (!this.hasMoreResults || this.isLoading) return;
        
        // Passer à la page suivante
        this.page++;
        
        // Effectuer la recherche
        this.performSearch(false);
    }
    
    /**
     * Traite les résultats de recherche
     * @param {Object} data - Données de résultat
     * @param {boolean} resetResults - Si true, remplace les résultats actuels
     */
    processSearchResults(data) {
        if (!data.success || !this.searchResults) return;
        
        const logements = data.logements || [];
        
        // Mettre à jour le nombre de résultats
        this.resultsCount = data.total || 0;
        this.updateResultsCount();
        
        // Déterminer s'il y a plus de résultats
        this.hasMoreResults = this.page < data.total_pages;
        
        // Afficher les résultats
        if (logements.length === 0 && this.page === 1) {
            // Aucun résultat
            if (this.noResultsMessage) {
                this.noResultsMessage.classList.remove('hidden');
            }
            return;
        } else if (this.noResultsMessage) {
            this.noResultsMessage.classList.add('hidden');
        }
        
        // Créer et ajouter les cartes de logement
        logements.forEach(logement => {
            const card = this.createPropertyCard(logement);
            this.searchResults.appendChild(card);
        });
        
        // Ajouter l'indicateur "Plus de résultats"
        if (this.hasMoreResults) {
            const loadMoreIndicator = document.createElement('div');
            loadMoreIndicator.className = 'load-more';
            loadMoreIndicator.innerHTML = '<span class="material-icons">more_horiz</span>';
            this.searchResults.appendChild(loadMoreIndicator);
        }
        
        // Mettre à jour la carte si elle est visible
        if (this.isMapView && window.googleMaps) {
            this.updateMap(logements);
        }
    }
    
    /**
     * Met à jour le compteur de résultats
     */
    updateResultsCount() {
        const countElement = document.querySelector('.results-count');
        if (!countElement) return;
        
        if (this.resultsCount === 0) {
            countElement.textContent = 'Aucun logement trouvé';
        } else if (this.resultsCount === 1) {
            countElement.textContent = '1 logement trouvé';
        } else {
            countElement.textContent = `${this.resultsCount} logements trouvés`;
        }
    }
    
    /**
     * Crée un élément de carte pour un logement
     * @param {Object} logement - Données du logement
     * @returns {HTMLElement} - Élément DOM de la carte
     */
    createPropertyCard(logement) {
        const card = document.createElement('div');
        card.className = 'property-card';
        card.setAttribute('data-id', logement.id);
        
        // Déterminer l'URL de l'image
        const imageUrl = logement.photo_principale 
            ? `/uploads/logements/${logement.photo_principale}` 
            : '/assets/img/placeholders/logement.jpg';
        
        // Créer le HTML de la carte
        card.innerHTML = `
            <div class="card-image">
                <img src="${imageUrl}" alt="${logement.titre}" loading="lazy">
                <div class="price-tag">${logement.prix}€ <span>/ nuit</span></div>
                ${this.getAvailabilityBadge(logement)}
            </div>
            <div class="card-content">
                <h3 class="card-title">${logement.titre}</h3>
                <p class="card-location">
                    <span class="material-icons">location_on</span>
                    ${logement.ville}
                </p>
                <div class="card-details">
                    <span class="property-type">${this.formatPropertyType(logement.type_logement)}</span>
                    <span class="property-capacity">
                        <span class="material-icons">person</span>
                        ${logement.nb_places}
                    </span>
                </div>
                <a href="/logement/details.php?id=${logement.id}" class="view-details">Voir détails</a>
            </div>
        `;
        
        // Ajouter les écouteurs d'événements
        card.addEventListener('click', (e) => {
            // Éviter de déclencher lorsqu'on clique sur le lien "Voir détails"
            if (e.target.tagName !== 'A') {
                // Naviguer vers la page de détails
                window.location.href = `/logement/details.php?id=${logement.id}`;
            }
        });
        
        // Effet de surbrillance sur hover pour mobile
        card.addEventListener('touchstart', () => {
            card.classList.add('hover');
        });
        
        card.addEventListener('touchend', () => {
            setTimeout(() => {
                card.classList.remove('hover');
            }, 300);
        });
        
        return card;
    }
    
    /**
     * Retourne le badge de disponibilité pour un logement
     * @param {Object} logement - Données du logement
     * @returns {string} - HTML du badge
     */
    getAvailabilityBadge(logement) {
        if (logement.disponible === false) {
            return '<div class="badge badge-warning">Non disponible</div>';
        }
        
        if (logement.reservation_en_cours) {
            return '<div class="badge badge-danger">Réservé</div>';
        }
        
        return '';
    }
    
    /**
     * Formate le type de logement pour l'affichage
     * @param {string} type - Type de logement
     * @returns {string} - Type formaté
     */
    formatPropertyType(type) {
        switch (type) {
            case 'entier':
                return 'Logement entier';
            case 'collocation':
                return 'Collocation';
            case 'libere':
                return 'Libéré';
            default:
                return type;
        }
    }
    
    /**
     * Met à jour la carte avec les logements
     * @param {Array} logements - Données des logements
     */
    updateMap(logements) {
        if (!window.googleMaps || !window.googleMaps.map) return;
        
        // Supprimer les marqueurs existants
        if (window.googleMaps.markers) {
            window.googleMaps.markers.forEach(marker => {
                if (marker.id !== 'user') { // Garder le marqueur de l'utilisateur
                    marker.setMap(null);
                }
            });
            
            // Filtrer pour ne garder que le marqueur utilisateur
            window.googleMaps.markers = window.googleMaps.markers.filter(marker => marker.id === 'user');
        }
        
        // Ajouter les nouveaux marqueurs
        logements.forEach(logement => {
            window.googleMaps.addPropertyMarker(logement);
        });
        
        // Ajuster la vue de la carte
        if (logements.length > 0) {
            window.googleMaps.fitMapToMarkers();
        }
        
        // Mettre à jour les attributs data de la carte
        this.mapContainer.setAttribute('data-logements', JSON.stringify(logements));
    }
    
    /**
     * Affiche une notification de toast
     * @param {string} message - Message à afficher
     * @param {string} type - Type de notification (success, error, info)
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span class="material-icons">${this.getToastIcon(type)}</span>
                <span class="toast-message">${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Afficher avec animation
        setTimeout(() => {
            toast.classList.add('show');
            
            // Masquer après un délai
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }, 10);
    }
    
    /**
     * Retourne l'icône pour un type de toast
     * @param {string} type - Type de notification
     * @returns {string} - Nom de l'icône
     */
    getToastIcon(type) {
        switch (type) {
            case 'success':
                return 'check_circle';
            case 'error':
                return 'error';
            case 'warning':
                return 'warning';
            default:
                return 'info';
        }
    }
}

// Créer et initialiser le gestionnaire de recherche
document.addEventListener('DOMContentLoaded', function() {
    const rechercheManager = new RechercheManager();
    rechercheManager.init();
    
    // Exposer l'instance au niveau global pour les intégrations
    window.rechercheManager = rechercheManager;
    
    // Gestion des suggestions de lieux
    initLocationSuggestions();
});

/**
 * Initialise les suggestions de lieux pour le champ de recherche
 */
function initLocationSuggestions() {
    const lieuInput = document.querySelector('input[name="lieu"]');
    if (!lieuInput) return;
    
    // Créer le conteneur de suggestions
    const suggestions = document.createElement('div');
    suggestions.className = 'suggestions-container';
    lieuInput.parentNode.appendChild(suggestions);
    
    let timeoutId = null;
    let currentQuery = '';
    
    // Écouteur d'événements pour la saisie
    lieuInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Ne rien faire si la requête est trop courte ou identique
        if (query.length < 2 || query === currentQuery) {
            return;
        }
        
        currentQuery = query;
        
        // Effacer le timeout précédent
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        
        // Définir un délai avant d'envoyer la requête
        timeoutId = setTimeout(() => {
            fetchSuggestions(query, suggestions, lieuInput);
        }, 300);
    });
    
    // Masquer les suggestions lorsque l'input perd le focus
    lieuInput.addEventListener('blur', function() {
        setTimeout(() => {
            suggestions.innerHTML = '';
            suggestions.classList.remove('visible');
        }, 200);
    });
    
    // Afficher les suggestions lorsque l'input reçoit le focus
    lieuInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            fetchSuggestions(this.value.trim(), suggestions, lieuInput);
        }
    });
}

/**
 * Récupère les suggestions pour un terme de recherche
 * @param {string} query - Terme de recherche
 * @param {HTMLElement} container - Conteneur des suggestions
 * @param {HTMLElement} input - Champ de saisie
 */
function fetchSuggestions(query, container, input) {
    // Effectuer la requête AJAX
    fetch(`controllers/recherche.php?action=suggestions&terme=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.suggestions || data.suggestions.length === 0) {
                container.innerHTML = '';
                container.classList.remove('visible');
                return;
            }
            
            // Créer les éléments de suggestion
            container.innerHTML = '';
            
            data.suggestions.forEach(suggestion => {
                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.innerHTML = `
                    <span class="material-icons">location_on</span>
                    <span class="suggestion-text">${suggestion}</span>
                `;
                
                // Gérer la sélection d'une suggestion
                item.addEventListener('click', () => {
                    input.value = suggestion;
                    container.innerHTML = '';
                    container.classList.remove('visible');
                    
                    // Déclencher un événement input pour mettre à jour les filtres
                    input.dispatchEvent(new Event('input'));
                });
                
                container.appendChild(item);
            });
            
            // Afficher le conteneur de suggestions
            container.classList.add('visible');
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des suggestions:', error);
            container.innerHTML = '';
            container.classList.remove('visible');
        });
}
