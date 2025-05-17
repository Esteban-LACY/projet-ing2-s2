/**
 * OmnesBnB - Gestion du système d'avis et de notation
 * Ce fichier gère la fonctionnalité bonus de notation et d'avis
 */

// Définir le module comme IIFE pour éviter les conflits de portée
const AvisManager = (function() {
    // Configuration privée
    const config = {
        starFilledClass: 'star-filled',
        starEmptyClass: 'star',
        starActiveColor: '#f59e0b', // Ambre (amber-500)
        starInactiveColor: '#d1d5db', // Gris (gray-300)
        animationDuration: 300 // ms
    };
    
    /**
     * Initialise le gestionnaire d'avis
     */
    function initialize() {
        // Initialiser tous les formulaires d'avis
        const avisForms = document.querySelectorAll('.avis-form');
        
        avisForms.forEach(form => {
            initializeRatingForm(form);
        });
        
        // Initialiser les filtres d'avis
        initializeAvisFilters();
        
        // Initialiser le chargement paresseux des avis
        initializeLazyLoading();
    }
    
    /**
     * Initialise un formulaire d'avis spécifique
     * @param {HTMLElement} form - Formulaire d'avis
     */
    function initializeRatingForm(form) {
        const starsContainer = form.querySelector('.rating-stars');
        const ratingInput = form.querySelector('input[name="note"]');
        const stars = form.querySelectorAll('.star');
        const submitButton = form.querySelector('button[type="submit"]');
        
        if (!starsContainer || !ratingInput || !stars.length) return;
        
        // Désactiver le bouton de soumission tant qu'une note n'est pas donnée
        if (submitButton) {
            submitButton.disabled = !ratingInput.value;
        }
        
        // Gérer l'animation au survol et au clic sur les étoiles
        stars.forEach((star, index) => {
            // Survol des étoiles
            star.addEventListener('mouseover', () => {
                highlightStars(stars, index);
            });
            
            // Sortie du survol
            star.addEventListener('mouseout', () => {
                // Revenir à la sélection actuelle
                const currentRating = parseInt(ratingInput.value) || 0;
                resetStars(stars);
                highlightStars(stars, currentRating - 1);
            });
            
            // Clic sur une étoile
            star.addEventListener('click', () => {
                const rating = index + 1;
                ratingInput.value = rating;
                
                // Mettre à jour visuellement
                resetStars(stars);
                highlightStars(stars, index);
                
                // Animer la sélection
                stars.forEach((s, i) => {
                    if (i <= index) {
                        animateStar(s);
                    }
                });
                
                // Activer le bouton de soumission
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
        });
        
        // Gérer la soumission du formulaire
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérifier qu'une note a été donnée
            if (!ratingInput.value) {
                showFormError(form, 'Veuillez attribuer une note');
                return;
            }
            
            // Soumettre le formulaire via AJAX
            submitAvis(this);
        });
    }
    
    /**
     * Soumet un avis via AJAX
     * @param {HTMLFormElement} form - Formulaire d'avis
     */
    function submitAvis(form) {
        // Afficher un indicateur de chargement
        const submitButton = form.querySelector('button[type="submit"]');
        
        if (submitButton) {
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner"></span> Envoi en cours...';
        }
        
        // Créer un objet FormData
        const formData = new FormData(form);
        
        // Envoyer la requête
        fetch('/api/submit-avis.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la soumission de l\'avis');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Remplacer le formulaire par un message de succès
                form.innerHTML = `
                    <div class="alert alert-success animate-fade-in">
                        <p>Merci pour votre avis !</p>
                    </div>
                `;
                
                // Mettre à jour l'affichage de la moyenne si nécessaire
                updateAverageRating(data.target_id, data.new_average);
                
                // Ajouter l'avis à la liste si disponible
                if (data.avis) {
                    addNewAvisToList(data.avis);
                }
            } else {
                // Réactiver le bouton et afficher l'erreur
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Envoyer';
                }
                
                showFormError(form, data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            
            // Réactiver le bouton et afficher l'erreur
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Envoyer';
            }
            
            showFormError(form, 'Une erreur est survenue lors de la communication avec le serveur');
        });
    }
    
    /**
     * Met en évidence un nombre d'étoiles
     * @param {NodeList} stars - Collection d'étoiles
     * @param {number} index - Index de la dernière étoile à remplir (0-based)
     */
    function highlightStars(stars, index) {
        stars.forEach((star, i) => {
            if (i <= index) {
                star.classList.add(config.starFilledClass);
            } else {
                star.classList.remove(config.starFilledClass);
            }
        });
    }
    
    /**
     * Réinitialise toutes les étoiles
     * @param {NodeList} stars - Collection d'étoiles
     */
    function resetStars(stars) {
        stars.forEach(star => {
            star.classList.remove(config.starFilledClass);
        });
    }
    
    /**
     * Anime une étoile lors de la sélection
     * @param {HTMLElement} star - Élément étoile
     */
    function animateStar(star) {
        // Ajouter classe d'animation
        star.classList.add('animate-pulse');
        
        // Supprimer après l'animation
        setTimeout(() => {
            star.classList.remove('animate-pulse');
        }, config.animationDuration);
    }
    
    /**
     * Affiche une erreur sur un formulaire
     * @param {HTMLElement} form - Formulaire
     * @param {string} message - Message d'erreur
     */
    function showFormError(form, message) {
        // Chercher le conteneur d'erreur
        let errorContainer = form.querySelector('.form-error-container');
        
        // Créer le conteneur s'il n'existe pas
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.classList.add('form-error-container', 'mt-3');
            form.prepend(errorContainer);
        }
        
        // Afficher le message
        errorContainer.innerHTML = `
            <div class="alert alert-danger animate-fade-in">
                ${message}
            </div>
        `;
        
        // Faire défiler vers l'erreur
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    /**
     * Met à jour l'affichage de la note moyenne
     * @param {number} targetId - ID de la cible (utilisateur ou logement)
     * @param {number} newAverage - Nouvelle note moyenne
     */
    function updateAverageRating(targetId, newAverage) {
        // Mettre à jour toutes les moyennes pour cette cible
        const averageDisplays = document.querySelectorAll(`.average-rating[data-target-id="${targetId}"]`);
        
        averageDisplays.forEach(display => {
            // Mettre à jour la valeur
            const valueElement = display.querySelector('.rating-value');
            if (valueElement) {
                valueElement.textContent = newAverage.toFixed(1);
            }
            
            // Mettre à jour les étoiles
            const stars = display.querySelectorAll('.star');
            if (stars.length) {
                stars.forEach((star, index) => {
                    // Étoile pleine si inférieur à la moyenne
                    if (index < Math.floor(newAverage)) {
                        star.classList.add(config.starFilledClass);
                        star.classList.remove(config.starEmptyClass);
                    } 
                    // Étoile à moitié pleine
                    else if (index < Math.ceil(newAverage) && index >= Math.floor(newAverage)) {
                        star.classList.add('star-half-filled');
                    } 
                    // Étoile vide
                    else {
                        star.classList.remove(config.starFilledClass);
                        star.classList.add(config.starEmptyClass);
                    }
                });
            }
        });
    }
    
    /**
     * Ajoute un nouvel avis à la liste
     * @param {Object} avis - Données de l'avis
     */
    function addNewAvisToList(avis) {
        const avisList = document.querySelector('.avis-list');
        
        if (!avisList) return;
        
        // Créer l'élément d'avis
        const avisElement = document.createElement('div');
        avisElement.classList.add('avis-item', 'card', 'mb-3', 'animate-slide-up');
        
        // Formater la date
        const date = new Date(avis.date_creation);
        const dateFormatee = date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
        
        // Générer les étoiles
        let starsHTML = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= avis.note) {
                starsHTML += `<span class="star star-filled"></span>`;
            } else {
                starsHTML += `<span class="star"></span>`;
            }
        }
        
        // Remplir le contenu
        avisElement.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-between items-center mb-3">
                    <div class="d-flex items-center">
                        <div class="avatar avatar-md mr-3">
                            <img src="${avis.photo_profil || '/assets/images/default-avatar.png'}" alt="${avis.prenom} ${avis.nom}">
                        </div>
                        <div>
                            <h4 class="text-lg font-bold">${avis.prenom} ${avis.nom}</h4>
                            <div class="rating-stars">
                                ${starsHTML}
                            </div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        ${dateFormatee}
                    </div>
                </div>
                <p>${avis.commentaire || 'Aucun commentaire'}</p>
            </div>
        `;
        
        // Ajouter à la liste (au début)
        if (avisList.firstChild) {
            avisList.insertBefore(avisElement, avisList.firstChild);
        } else {
            avisList.appendChild(avisElement);
        }
        
        // Mettre à jour le compteur
        const countElement = document.querySelector('.avis-count');
        if (countElement) {
            const currentCount = parseInt(countElement.textContent) || 0;
            countElement.textContent = currentCount + 1;
        }
    }
    
    /**
     * Initialise les filtres d'avis
     */
    function initializeAvisFilters() {
        const filterSelect = document.getElementById('avis-filter');
        
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                const selectedValue = this.value;
                const avisList = document.querySelector('.avis-list');
                
                if (!avisList) return;
                
                // Réinitialiser le chargement paresseux
                avisList.setAttribute('data-page', '1');
                
                // Vider la liste
                avisList.innerHTML = '<div class="loading-spinner mx-auto my-4">Chargement des avis...</div>';
                
                // Charger les avis filtrés
                const targetId = avisList.getAttribute('data-target-id');
                const targetType = avisList.getAttribute('data-target-type');
                
                if (targetId && targetType) {
                    loadAvis(targetId, targetType, 1, selectedValue);
                }
            });
        }
    }
    
    /**
     * Initialise le chargement paresseux des avis
     */
    function initializeLazyLoading() {
        const avisList = document.querySelector('.avis-list');
        
        if (!avisList) return;
        
        // Créer l'observateur d'intersection
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Si l'élément de chargement est visible, charger plus d'avis
                    const loadMoreElement = entry.target;
                    
                    // Récupérer les informations de pagination
                    const page = parseInt(avisList.getAttribute('data-page')) || 1;
                    const hasMore = avisList.getAttribute('data-has-more') === 'true';
                    
                    if (hasMore) {
                        const nextPage = page + 1;
                        const targetId = avisList.getAttribute('data-target-id');
                        const targetType = avisList.getAttribute('data-target-type');
                        const filter = document.getElementById('avis-filter')?.value || 'all';
                        
                        // Charger la page suivante
                        loadAvis(targetId, targetType, nextPage, filter, true);
                        
                        // Mettre à jour la page courante
                        avisList.setAttribute('data-page', nextPage.toString());
                    }
                }
            });
        }, {
            threshold: 0.5
        });
        
        // Observer l'élément "charger plus"
        const loadMoreElement = document.querySelector('.load-more-avis');
        
        if (loadMoreElement) {
            observer.observe(loadMoreElement);
        }
    }
    
    /**
     * Charge les avis depuis le serveur
     * @param {number} targetId - ID de la cible (utilisateur ou logement)
     * @param {string} targetType - Type de la cible ('user' ou 'logement')
     * @param {number} page - Numéro de page
     * @param {string} filter - Filtre à appliquer ('all', 'positive', 'negative')
     * @param {boolean} append - Si true, ajoute à la liste existante, sinon remplace
     */
    function loadAvis(targetId, targetType, page = 1, filter = 'all', append = false) {
        const avisList = document.querySelector('.avis-list');
        
        if (!avisList) return;
        
        // Si on ne fait pas d'append, afficher un indicateur de chargement
        if (!append) {
            avisList.innerHTML = '<div class="loading-spinner mx-auto my-4">Chargement des avis...</div>';
        }
        
        // Construire l'URL
        const url = `/api/get-avis.php?target_id=${targetId}&target_type=${targetType}&page=${page}&filter=${filter}`;
        
        // Charger les avis
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des avis');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Si c'est la première page et qu'il n'y a pas de résultats
                    if (page === 1 && data.avis.length === 0) {
                        avisList.innerHTML = `
                            <div class="text-center py-4 text-gray-500">
                                <p>Aucun avis pour le moment.</p>
                            </div>
                        `;
                        return;
                    }
                    
                    // Supprimer l'indicateur de chargement
                    if (!append) {
                        avisList.innerHTML = '';
                    } else {
                        const loadingElement = avisList.querySelector('.loading-spinner');
                        if (loadingElement) {
                            loadingElement.remove();
                        }
                    }
                    
                    // Ajouter les avis
                    data.avis.forEach(avis => {
                        // Formater la date
                        const date = new Date(avis.date_creation);
                        const dateFormatee = date.toLocaleDateString('fr-FR', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                        
                        // Générer les étoiles
                        let starsHTML = '';
                        for (let i = 1; i <= 5; i++) {
                            if (i <= avis.note) {
                                starsHTML += `<span class="star star-filled"></span>`;
                            } else {
                                starsHTML += `<span class="star"></span>`;
                            }
                        }
                        
                        // Créer l'élément d'avis
                        const avisElement = document.createElement('div');
                        avisElement.classList.add('avis-item', 'card', 'mb-3');
                        
                        // Ajouter une animation si c'est un nouvel avis chargé
                        if (!append) {
                            avisElement.classList.add('animate-fade-in');
                        }
                        
                        // Remplir le contenu
                        avisElement.innerHTML = `
                            <div class="card-body">
                                <div class="d-flex justify-between items-center mb-3">
                                    <div class="d-flex items-center">
                                        <div class="avatar avatar-md mr-3">
                                            <img src="${avis.photo_profil || '/assets/images/default-avatar.png'}" alt="${avis.prenom} ${avis.nom}">
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-bold">${avis.prenom} ${avis.nom}</h4>
                                            <div class="rating-stars">
                                                ${starsHTML}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ${dateFormatee}
                                    </div>
                                </div>
                                <p>${avis.commentaire || 'Aucun commentaire'}</p>
                            </div>
                        `;
                        
                        // Ajouter à la liste
                        avisList.appendChild(avisElement);
                    });
                    
                    // Mettre à jour les informations de pagination
                    avisList.setAttribute('data-has-more', data.has_more.toString());
                    
                    // Ajouter un élément "charger plus" si nécessaire
                    if (data.has_more) {
                        // Supprimer l'élément existant s'il y en a un
                        const existingLoadMore = document.querySelector('.load-more-avis');
                        if (existingLoadMore) {
                            existingLoadMore.remove();
                        }
                        
                        const loadMoreElement = document.createElement('div');
                        loadMoreElement.classList.add('load-more-avis', 'text-center', 'py-4');
                        loadMoreElement.innerHTML = `
                            <button class="btn btn-secondary">
                                Charger plus d'avis
                            </button>
                        `;
                        
                        avisList.appendChild(loadMoreElement);
                        
                        // Ajouter l'observateur
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    const nextPage = parseInt(avisList.getAttribute('data-page')) + 1;
                                    loadAvis(targetId, targetType, nextPage, filter, true);
                                    avisList.setAttribute('data-page', nextPage.toString());
                                    
                                    // Arrêter d'observer cet élément
                                    observer.unobserve(entry.target);
                                }
                            });
                        }, {
                            threshold: 0.5
                        });
                        
                        observer.observe(loadMoreElement);
                    }
                } else {
                    // Afficher une erreur
                    avisList.innerHTML = `
                        <div class="alert alert-danger">
                            ${data.message || 'Une erreur est survenue lors du chargement des avis'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Afficher une erreur
                avisList.innerHTML = `
                    <div class="alert alert-danger">
                        Une erreur est survenue lors de la communication avec le serveur
                    </div>
                `;
            });
    }
    
    // API publique
    return {
        initialize: initialize,
        initializeRatingForm: initializeRatingForm,
        loadAvis: loadAvis
    };
})();

// Initialiser le gestionnaire d'avis au chargement de la page
document.addEventListener('DOMContentLoaded', AvisManager.initialize);

// Exposer l'API de AvisManager globalement pour utilisation dans d'autres scripts
window.AvisManager = AvisManager;
