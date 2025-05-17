/**
 * OmnesBnB - Script JavaScript principal
 * Mobile-first, responsive pour tablette et desktop
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', () => {
    // Initialisation des composants
    initializeThemeSwitcher();
    initializeNavigation();
    initializeDropdowns();
    initializeTooltips();
    initializeModalHandlers();
    initializeFormValidation();
    initializeScrollAnimations();
    
    // Vérifier si la page actuelle nécessite des initialisations spécifiques
    const currentPath = window.location.pathname;
    
    if (currentPath.includes('logements/search')) {
        initializeSearchFilters();
    } else if (currentPath.includes('logements/details')) {
        initializeLogementDetails();
    } else if (currentPath.includes('auth/profile')) {
        initializeProfilePage();
    } else if (currentPath.includes('locations/mes-locations')) {
        initializeLocationsPage();
    }
});

/**
 * Initialise la navigation responsive
 */
function initializeNavigation() {
    // Gestion de la navigation mobile (menu bottom bar)
    const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
    
    if (mobileNavItems) {
        // Définir l'élément actif selon la page actuelle
        const currentPath = window.location.pathname;
        
        mobileNavItems.forEach(item => {
            const itemLink = item.getAttribute('data-link');
            
            if (currentPath.includes(itemLink)) {
                item.classList.add('active');
            }
            
            // Ajouter un effet de ripple sur les éléments de navigation mobile
            item.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                ripple.classList.add('nav-ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }
    
    // Gestion de la navigation desktop
    const desktopNavItems = document.querySelectorAll('.desktop-nav .nav-link');
    
    if (desktopNavItems) {
        // Définir l'élément actif selon la page actuelle
        const currentPath = window.location.pathname;
        
        desktopNavItems.forEach(item => {
            const itemHref = item.getAttribute('href');
            
            if (itemHref && currentPath.includes(itemHref)) {
                item.classList.add('nav-link-active');
            }
        });
    }
}

/**
 * Initialise le sélecteur de thème (clair/sombre)
 */
function initializeThemeSwitcher() {
    const themeSwitch = document.getElementById('theme-switch');
    const body = document.body;
    
    if (themeSwitch) {
        // Vérifier si une préférence de thème est déjà enregistrée
        const savedTheme = localStorage.getItem('theme');
        
        // Vérifier si l'utilisateur a défini une préférence de thème dans le système
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Appliquer le thème sauvegardé ou la préférence système
        if (savedTheme === 'dark' || (!savedTheme && prefersDarkScheme)) {
            body.classList.add('dark-mode');
            themeSwitch.checked = true;
        }
        
        // Mettre à jour le thème lors du changement du switch
        themeSwitch.addEventListener('change', () => {
            if (themeSwitch.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
                
                // Envoyer la préférence au serveur si l'utilisateur est connecté
                if (document.body.hasAttribute('data-user-id')) {
                    updateUserThemePreference('dark');
                }
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
                
                // Envoyer la préférence au serveur si l'utilisateur est connecté
                if (document.body.hasAttribute('data-user-id')) {
                    updateUserThemePreference('light');
                }
            }
        });
    }
}

/**
 * Met à jour la préférence de thème de l'utilisateur en base de données
 * @param {string} theme - 'light' ou 'dark'
 */
function updateUserThemePreference(theme) {
    const formData = new FormData();
    formData.append('theme', theme);
    
    fetch('/api/toggle-theme.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Erreur lors de la mise à jour du thème:', error);
    });
}

/**
 * Initialise les menus déroulants
 */
function initializeDropdowns() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdownId = toggle.getAttribute('data-dropdown');
            const dropdown = document.getElementById(dropdownId);
            
            // Fermer tous les autres dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu.id !== dropdownId) {
                    menu.classList.remove('show');
                }
            });
            
            dropdown.classList.toggle('show');
        });
    });
    
    // Fermer les dropdowns quand on clique ailleurs
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    });
}

/**
 * Initialise les infobulles
 */
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(element => {
        const tooltipText = element.getAttribute('data-tooltip');
        
        element.addEventListener('mouseenter', () => {
            const tooltip = document.createElement('div');
            tooltip.classList.add('tooltip');
            tooltip.textContent = tooltipText;
            
            // Positionner la tooltip
            document.body.appendChild(tooltip);
            
            const rect = element.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            tooltip.style.top = `${rect.top - tooltipRect.height - 10}px`;
            tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltipRect.width / 2)}px`;
            tooltip.classList.add('show');
            
            element.tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', () => {
            if (element.tooltip) {
                element.tooltip.remove();
                element.tooltip = null;
            }
        });
    });
}

/**
 * Initialise les gestionnaires de modales
 */
function initializeModalHandlers() {
    // Ouvrir les modales
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            
            const modalId = trigger.getAttribute('data-modal-target');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                openModal(modal);
            }
        });
    });
    
    // Fermer les modales
    const closeModalButtons = document.querySelectorAll('[data-close-modal]');
    
    closeModalButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Fermer les modales en cliquant sur l'overlay
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });
    
    // Fermer les modales avec la touche Échap
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                closeModal(modal);
            });
        }
    });
}

/**
 * Ouvre une modale
 * @param {HTMLElement} modal - Élément DOM de la modale
 */
function openModal(modal) {
    document.body.classList.add('modal-open');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    
    // Animation d'ouverture
    const modalDialog = modal.querySelector('.modal-dialog');
    modalDialog.classList.add('animate-slide-up');
    
    setTimeout(() => {
        modalDialog.classList.remove('animate-slide-up');
    }, 300);
}

/**
 * Ferme une modale
 * @param {HTMLElement} modal - Élément DOM de la modale
 */
function closeModal(modal) {
    const modalDialog = modal.querySelector('.modal-dialog');
    modalDialog.classList.add('animate-slide-down');
    
    setTimeout(() => {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        modalDialog.classList.remove('animate-slide-down');
    }, 200);
}

/**
 * Initialise la validation des formulaires
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            let isValid = true;
            
            // Vérifier tous les champs requis
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    showFieldError(field, 'Ce champ est obligatoire');
                } else {
                    clearFieldError(field);
                    
                    // Validations spécifiques selon le type de champ
                    if (field.type === 'email' && !validateEmail(field.value)) {
                        isValid = false;
                        showFieldError(field, 'Veuillez entrer une adresse email valide');
                    } else if (field.hasAttribute('data-validate-password') && !validatePassword(field.value)) {
                        isValid = false;
                        showFieldError(field, 'Le mot de passe doit contenir au moins 8 caractères');
                    } else if (field.hasAttribute('data-match-password')) {
                        const passwordField = document.getElementById(field.getAttribute('data-match-password'));
                        
                        if (passwordField && field.value !== passwordField.value) {
                            isValid = false;
                            showFieldError(field, 'Les mots de passe ne correspondent pas');
                        }
                    } else if (field.hasAttribute('data-validate-phone') && !validatePhone(field.value)) {
                        isValid = false;
                        showFieldError(field, 'Veuillez entrer un numéro de téléphone valide');
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Validation en temps réel
        const inputFields = form.querySelectorAll('input, textarea, select');
        
        inputFields.forEach(field => {
            field.addEventListener('blur', () => {
                if (field.hasAttribute('required') && !field.value.trim()) {
                    showFieldError(field, 'Ce champ est obligatoire');
                } else if (field.type === 'email' && field.value.trim() && !validateEmail(field.value)) {
                    showFieldError(field, 'Veuillez entrer une adresse email valide');
                } else if (field.hasAttribute('data-validate-password') && field.value.trim() && !validatePassword(field.value)) {
                    showFieldError(field, 'Le mot de passe doit contenir au moins 8 caractères');
                } else if (field.hasAttribute('data-match-password')) {
                    const passwordField = document.getElementById(field.getAttribute('data-match-password'));
                    
                    if (passwordField && field.value !== passwordField.value) {
                        showFieldError(field, 'Les mots de passe ne correspondent pas');
                    } else {
                        clearFieldError(field);
                    }
                } else if (field.hasAttribute('data-validate-phone') && field.value.trim() && !validatePhone(field.value)) {
                    showFieldError(field, 'Veuillez entrer un numéro de téléphone valide');
                } else {
                    clearFieldError(field);
                }
            });
        });
    });
}

/**
 * Affiche une erreur pour un champ spécifique
 * @param {HTMLElement} field - Champ du formulaire
 * @param {string} message - Message d'erreur
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    
    const errorElement = document.createElement('div');
    errorElement.classList.add('form-error');
    errorElement.textContent = message;
    
    field.parentNode.appendChild(errorElement);
}

/**
 * Efface l'erreur d'un champ
 * @param {HTMLElement} field - Champ du formulaire
 */
function clearFieldError(field) {
    field.classList.remove('error');
    
    const existingError = field.parentNode.querySelector('.form-error');
    
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Valide une adresse email
 * @param {string} email - Adresse email à valider
 * @returns {boolean} - True si l'email est valide
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const omnesRegex = /@(omnesintervenant\.com|ece\.fr|edu\.ece\.fr)$/i;
    
    return emailRegex.test(email) && omnesRegex.test(email);
}

/**
 * Valide un mot de passe
 * @param {string} password - Mot de passe à valider
 * @returns {boolean} - True si le mot de passe est valide
 */
function validatePassword(password) {
    return password.length >= 8;
}

/**
 * Valide un numéro de téléphone
 * @param {string} phone - Numéro de téléphone à valider
 * @returns {boolean} - True si le numéro est valide
 */
function validatePhone(phone) {
    const phoneRegex = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
    return phoneRegex.test(phone);
}

/**
 * Initialise les animations de défilement
 */
function initializeScrollAnimations() {
    const animatedElements = document.querySelectorAll('[data-animate]');
    
    // Observer pour détecter quand les éléments deviennent visibles
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const animation = element.getAttribute('data-animate');
                
                element.classList.add(animation);
                observer.unobserve(element);
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Observer tous les éléments animés
    animatedElements.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Initialise les filtres de recherche
 */
function initializeSearchFilters() {
    const filterForm = document.getElementById('search-filters');
    
    if (filterForm) {
        // Mise à jour des résultats en temps réel lors de l'utilisation des filtres
        const filterInputs = filterForm.querySelectorAll('input, select');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                // Soumettre le formulaire avec AJAX pour mise à jour sans rechargement
                const formData = new FormData(filterForm);
                
                fetch('/api/load-logements.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    updateSearchResults(data);
                })
                .catch(error => {
                    console.error('Erreur lors de la mise à jour des résultats:', error);
                });
            });
        });
        
        // Réinitialiser les filtres
        const resetButton = document.getElementById('reset-filters');
        
        if (resetButton) {
            resetButton.addEventListener('click', (e) => {
                e.preventDefault();
                
                filterForm.reset();
                
                // Déclencher l'événement change pour mettre à jour les résultats
                filterInputs[0].dispatchEvent(new Event('change'));
            });
        }
    }
}

/**
 * Met à jour les résultats de recherche
 * @param {Object} data - Données de résultats
 */
function updateSearchResults(data) {
    const resultsContainer = document.getElementById('search-results');
    
    if (resultsContainer) {
        // Afficher le message si aucun résultat
        if (data.logements.length === 0) {
            resultsContainer.innerHTML = '<div class="text-center py-8"><p>Aucun logement ne correspond à votre recherche.</p></div>';
            return;
        }
        
        // Créer le HTML pour les résultats
        let resultsHTML = '';
        
        data.logements.forEach(logement => {
            resultsHTML += `
                <div class="logement-card animate-fade-in">
                    <div class="relative">
                        <img src="${logement.photo_principale || '/assets/images/placeholder.jpg'}" 
                             alt="${logement.description}" 
                             class="logement-img">
                        <span class="badge badge-primary absolute top-2 right-2">
                            ${logement.type_logement === 'collocation' ? 'Colocation' : 
                              logement.type_logement === 'temporaire' ? 'Temporaire' : 'Définitif'}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="logement-title">${logement.adresse}</h3>
                            <span class="logement-price">${logement.prix}€</span>
                        </div>
                        <p class="logement-address mb-2">${logement.adresse_complete}</p>
                        <div class="logement-features">
                            <span class="badge">${logement.nb_places} place${logement.nb_places > 1 ? 's' : ''}</span>
                            <span class="badge ${logement.disponible ? 'badge-success' : 'badge-danger'}">
                                ${logement.disponible ? 'Disponible' : 'Indisponible'}
                            </span>
                        </div>
                        <a href="/logements/details.php?id=${logement.id}" class="btn btn-primary btn-full mt-3">
                            Voir les détails
                        </a>
                    </div>
                </div>
            `;
        });
        
        resultsContainer.innerHTML = resultsHTML;
    }
}

/**
 * Initialise la page de détails d'un logement
 */
function initializeLogementDetails() {
    // Gestion de la galerie d'images
    const mainImage = document.getElementById('main-image');
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    if (mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', () => {
                // Mettre à jour l'image principale
                mainImage.src = thumbnail.getAttribute('data-full');
                mainImage.alt = thumbnail.alt;
                
                // Mettre à jour la classe active
                thumbnails.forEach(t => t.classList.remove('active'));
                thumbnail.classList.add('active');
            });
        });
    }
    
    // Gestion du calendrier de disponibilité
    const calendarContainer = document.getElementById('availability-calendar');
    
    if (calendarContainer) {
        const logementId = calendarContainer.getAttribute('data-logement-id');
        
        if (logementId) {
            loadCalendarData(logementId);
        }
    }
    
    // Gestion du formulaire de réservation
    const reservationForm = document.getElementById('reservation-form');
    
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérifier si l'utilisateur est connecté
            if (!document.body.hasAttribute('data-user-id')) {
                // Rediriger vers la page de connexion
                const returnUrl = encodeURIComponent(window.location.href);
                window.location.href = `/auth/login.php?redirect=${returnUrl}`;
                return;
            }
            
            // Soumettre le formulaire avec AJAX
            const formData = new FormData(this);
            
            fetch('/locations/reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rediriger vers la page de confirmation
                    window.location.href = `/locations/confirmation.php?id=${data.location_id}`;
                } else {
                    // Afficher le message d'erreur
                    const errorContainer = document.getElementById('reservation-error');
                    
                    if (errorContainer) {
                        errorContainer.textContent = data.message;
                        errorContainer.classList.remove('hidden');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur lors de la réservation:', error);
            });
        });
    }
}

/**
 * Charge les données du calendrier de disponibilité
 * @param {number} logementId - ID du logement
 */
function loadCalendarData(logementId) {
    fetch(`/api/get-disponibilites.php?id=${logementId}`)
        .then(response => response.json())
        .then(data => {
            renderCalendar(data.disponibilites);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des disponibilités:', error);
        });
}

/**
 * Affiche le calendrier de disponibilité
 * @param {Array} disponibilites - Liste des disponibilités
 */
function renderCalendar(disponibilites) {
    const calendarContainer = document.getElementById('availability-calendar');
    
    if (!calendarContainer) return;
    
    // Mois actuel et année
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    
    // Création du calendrier pour le mois en cours et les 2 mois suivants
    for (let i = 0; i < 3; i++) {
        const monthDate = new Date(currentYear, currentMonth + i, 1);
        const month = monthDate.getMonth();
        const year = monthDate.getFullYear();
        
        const monthDiv = document.createElement('div');
        monthDiv.classList.add('calendar', 'mb-4');
        
        // En-tête du calendrier
        const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                           'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        
        monthDiv.innerHTML = `
            <div class="calendar-header">
                <h3>${monthNames[month]} ${year}</h3>
            </div>
            <div class="calendar-grid">
                <div class="calendar-day-header">Lun</div>
                <div class="calendar-day-header">Mar</div>
                <div class="calendar-day-header">Mer</div>
                <div class="calendar-day-header">Jeu</div>
                <div class="calendar-day-header">Ven</div>
                <div class="calendar-day-header">Sam</div>
                <div class="calendar-day-header">Dim</div>
            </div>
        `;
        
        const calendarGrid = monthDiv.querySelector('.calendar-grid');
        
        // Premier jour du mois (0 = Dimanche, 1 = Lundi, etc.)
        const firstDay = new Date(year, month, 1).getDay();
        // Décalage pour commencer par Lundi (1 = Lundi, 2 = Mardi, etc.)
        const startOffset = firstDay === 0 ? 6 : firstDay - 1;
        
        // Nombre de jours dans le mois
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Ajouter les cellules vides pour les jours avant le début du mois
        for (let j = 0; j < startOffset; j++) {
            const emptyDay = document.createElement('div');
            emptyDay.classList.add('calendar-day');
            calendarGrid.appendChild(emptyDay);
        }
        
        // Ajouter les jours du mois
        for (let day = 1; day <= daysInMonth; day++) {
            const currentDate = new Date(year, month, day);
            const dateString = currentDate.toISOString().split('T')[0];
            
            const dayCell = document.createElement('div');
            dayCell.classList.add('calendar-day');
            dayCell.textContent = day;
            
            // Vérifier la disponibilité de cette date
            const disponibilite = disponibilites.find(d => {
                const debut = new Date(d.date_debut);
                const fin = new Date(d.date_fin);
                return currentDate >= debut && currentDate <= fin;
            });
            
            if (disponibilite) {
                if (disponibilite.statut === 'disponible') {
                    dayCell.classList.add('calendar-day-available');
                } else if (disponibilite.statut === 'reserve') {
                    dayCell.classList.add('calendar-day-booked');
                } else {
                    dayCell.classList.add('calendar-day-unavailable');
                }
            } else {
                dayCell.classList.add('calendar-day-unavailable');
            }
            
            // Désactiver les jours passés
            if (currentDate < today) {
                dayCell.classList.remove('calendar-day-available');
                dayCell.classList.add('calendar-day-unavailable');
            }
            
            // Ajouter la date comme attribut
            dayCell.setAttribute('data-date', dateString);
            
            calendarGrid.appendChild(dayCell);
        }
        
        calendarContainer.appendChild(monthDiv);
    }
    
    // Ajouter les événements pour sélectionner les dates de réservation
    const dateInputs = document.querySelectorAll('#date_debut, #date_fin');
    const availableDays = document.querySelectorAll('.calendar-day-available');
    let selectedStartDate = null;
    
    if (dateInputs.length === 2 && availableDays.length > 0) {
        const startDateInput = dateInputs[0];
        const endDateInput = dateInputs[1];
        
        availableDays.forEach(day => {
            day.addEventListener('click', () => {
                const selectedDate = day.getAttribute('data-date');
                
                if (!selectedStartDate) {
                    // Première date sélectionnée
                    selectedStartDate = selectedDate;
                    startDateInput.value = selectedDate;
                    
                    // Marquer comme sélectionné
                    day.classList.add('selected-start');
                    
                    // Désactiver la sélection des dates antérieures à la date de début
                    availableDays.forEach(d => {
                        if (d.getAttribute('data-date') < selectedDate) {
                            d.classList.add('disabled');
                        }
                    });
                } else {
                    // Deuxième date sélectionnée
                    endDateInput.value = selectedDate;
                    
                    // Marquer comme sélectionné
                    day.classList.add('selected-end');
                    
                    // Marquer les dates intermédiaires
                    availableDays.forEach(d => {
                        const date = d.getAttribute('data-date');
                        
                        if (date > selectedStartDate && date < selectedDate) {
                            d.classList.add('selected-between');
                        }
                    });
                    
                    // Calculer le prix total
                    updateTotalPrice(selectedStartDate, selectedDate);
                    
                    // Réinitialiser pour permettre une nouvelle sélection
                    selectedStartDate = null;
                }
            });
        });
        
        // Bouton de réinitialisation des dates
        const resetDatesButton = document.getElementById('reset-dates');
        
        if (resetDatesButton) {
            resetDatesButton.addEventListener('click', () => {
                // Effacer les valeurs des inputs
                startDateInput.value = '';
                endDateInput.value = '';
                
                // Réinitialiser les classes
                document.querySelectorAll('.selected-start, .selected-end, .selected-between, .disabled').forEach(el => {
                    el.classList.remove('selected-start', 'selected-end', 'selected-between', 'disabled');
                });
                
                // Réinitialiser la variable
                selectedStartDate = null;
                
                // Réinitialiser le prix total
                const totalPriceElement = document.getElementById('total-price');
                if (totalPriceElement) {
                    totalPriceElement.textContent = '0';
                }
            });
        }
    }
}

/**
* Met à jour le prix total en fonction des dates sélectionnées
* @param {string} startDate - Date de début (YYYY-MM-DD)
* @param {string} endDate - Date de fin (YYYY-MM-DD)
*/
function updateTotalPrice(startDate, endDate) {
   const prixParJour = parseFloat(document.getElementById('prix-jour').getAttribute('data-prix'));
   
   if (isNaN(prixParJour)) return;
   
   // Calculer le nombre de jours
   const start = new Date(startDate);
   const end = new Date(endDate);
   const diffTime = Math.abs(end - start);
   const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
   
   // Ajouter 1 car on compte les nuitées (la nuit du départ est incluse)
   const nombreJours = diffDays + 1;
   
   // Calculer le prix total
   const prixTotal = prixParJour * nombreJours;
   
   // Afficher le prix total
   const totalPriceElement = document.getElementById('total-price');
   
   if (totalPriceElement) {
       totalPriceElement.textContent = prixTotal.toFixed(2);
   }
   
   // Mettre à jour le champ caché du formulaire
   const montantInput = document.getElementById('montant');
   
   if (montantInput) {
       montantInput.value = prixTotal.toFixed(2);
   }
}

/**
* Initialise la page de profil
*/
function initializeProfilePage() {
   // Prévisualisation de l'image de profil
   const photoInput = document.getElementById('photo_profil');
   const photoPreview = document.getElementById('photo-preview');
   
   if (photoInput && photoPreview) {
       photoInput.addEventListener('change', function() {
           if (this.files && this.files[0]) {
               const reader = new FileReader();
               
               reader.onload = function(e) {
                   photoPreview.src = e.target.result;
                   photoPreview.classList.remove('hidden');
               };
               
               reader.readAsDataURL(this.files[0]);
           }
       });
   }
   
   // Gestion des onglets
   const tabButtons = document.querySelectorAll('[data-tab]');
   
   if (tabButtons.length > 0) {
       tabButtons.forEach(button => {
           button.addEventListener('click', () => {
               const tabId = button.getAttribute('data-tab');
               
               // Masquer tous les onglets
               document.querySelectorAll('.tab-content').forEach(tab => {
                   tab.classList.add('hidden');
               });
               
               // Afficher l'onglet sélectionné
               document.getElementById(tabId).classList.remove('hidden');
               
               // Mettre à jour les classes actives
               tabButtons.forEach(btn => {
                   btn.classList.remove('active');
               });
               
               button.classList.add('active');
           });
       });
   }
   
   // Gestion de la suppression du compte
   const deleteAccountButton = document.getElementById('delete-account');
   
   if (deleteAccountButton) {
       deleteAccountButton.addEventListener('click', (e) => {
           e.preventDefault();
           
           // Afficher la modale de confirmation
           const confirmModal = document.getElementById('confirm-delete-modal');
           
           if (confirmModal) {
               openModal(confirmModal);
           }
       });
       
       // Confirmation de suppression
       const confirmDeleteButton = document.getElementById('confirm-delete');
       
       if (confirmDeleteButton) {
           confirmDeleteButton.addEventListener('click', () => {
               const userId = document.body.getAttribute('data-user-id');
               
               if (userId) {
                   // Envoyer la requête de suppression
                   fetch('/api/delete-account.php', {
                       method: 'POST',
                       headers: {
                           'Content-Type': 'application/json'
                       },
                       body: JSON.stringify({ id: userId })
                   })
                   .then(response => response.json())
                   .then(data => {
                       if (data.success) {
                           // Rediriger vers la page d'accueil
                           window.location.href = '/?message=account_deleted';
                       } else {
                           alert(data.message || 'Une erreur est survenue lors de la suppression du compte.');
                       }
                   })
                   .catch(error => {
                       console.error('Erreur lors de la suppression du compte:', error);
                   });
               }
           });
       }
   }
}

/**
* Initialise la page des locations
*/
function initializeLocationsPage() {
   // Gestion des onglets (bailleur/locataire)
   const roleToggle = document.getElementById('role-toggle');
   
   if (roleToggle) {
       roleToggle.addEventListener('change', function() {
           const bailleursTab = document.getElementById('bailleurs-tab');
           const locatairesTab = document.getElementById('locataires-tab');
           
           if (this.checked) {
               // Afficher les locations en tant que locataire
               bailleursTab.classList.add('hidden');
               locatairesTab.classList.remove('hidden');
           } else {
               // Afficher les locations en tant que bailleur
               bailleursTab.classList.remove('hidden');
               locatairesTab.classList.add('hidden');
           }
       });
   }
   
   // Gestion des actions sur les locations
   // Accepter une réservation
   const acceptButtons = document.querySelectorAll('.accept-reservation');
   
   acceptButtons.forEach(button => {
       button.addEventListener('click', function() {
           const locationId = this.getAttribute('data-location-id');
           
           updateLocationStatus(locationId, 'accepte');
       });
   });
   
   // Refuser une réservation
   const rejectButtons = document.querySelectorAll('.reject-reservation');
   
   rejectButtons.forEach(button => {
       button.addEventListener('click', function() {
           const locationId = this.getAttribute('data-location-id');
           
           updateLocationStatus(locationId, 'refuse');
       });
   });
   
   // Annuler une réservation
   const cancelButtons = document.querySelectorAll('.cancel-reservation');
   
   cancelButtons.forEach(button => {
       button.addEventListener('click', function() {
           const locationId = this.getAttribute('data-location-id');
           
           // Afficher une confirmation
           if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
               updateLocationStatus(locationId, 'annule');
           }
       });
   });
   
   // Système de notation (fonctionnalité bonus)
   initializeRatingSystem();
}

/**
* Met à jour le statut d'une location
* @param {number} locationId - ID de la location
* @param {string} statut - Nouveau statut ('accepte', 'refuse', 'annule')
*/
function updateLocationStatus(locationId, statut) {
   fetch('/api/update-location-status.php', {
       method: 'POST',
       headers: {
           'Content-Type': 'application/json'
       },
       body: JSON.stringify({
           id: locationId,
           statut: statut
       })
   })
   .then(response => response.json())
   .then(data => {
       if (data.success) {
           // Recharger la page pour afficher le nouveau statut
           window.location.reload();
       } else {
           alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
       }
   })
   .catch(error => {
       console.error('Erreur lors de la mise à jour du statut:', error);
   });
}

/**
* Initialise le système de notation (fonctionnalité bonus)
*/
function initializeRatingSystem() {
   const ratingForms = document.querySelectorAll('.rating-form');
   
   ratingForms.forEach(form => {
       const stars = form.querySelectorAll('.star');
       const ratingInput = form.querySelector('.rating-input');
       
       // Gestion du survol et du clic sur les étoiles
       stars.forEach((star, index) => {
           // Survol
           star.addEventListener('mouseover', () => {
               // Remplir les étoiles jusqu'à celle survolée
               for (let i = 0; i <= index; i++) {
                   stars[i].classList.add('star-filled');
               }
           });
           
           star.addEventListener('mouseout', () => {
               // Réinitialiser les étoiles au survol
               stars.forEach(s => {
                   s.classList.remove('star-filled');
               });
               
               // Rétablir la note actuelle
               const currentRating = parseInt(ratingInput.value) || 0;
               
               for (let i = 0; i < currentRating; i++) {
                   stars[i].classList.add('star-filled');
               }
           });
           
           // Clic
           star.addEventListener('click', () => {
               const rating = index + 1;
               
               // Mettre à jour l'input caché
               ratingInput.value = rating;
               
               // Mettre à jour l'affichage des étoiles
               stars.forEach((s, i) => {
                   if (i < rating) {
                       s.classList.add('star-filled');
                   } else {
                       s.classList.remove('star-filled');
                   }
               });
           });
       });
       
       // Soumission du formulaire d'avis
       form.addEventListener('submit', function(e) {
           e.preventDefault();
           
           // Vérifier qu'une note a été donnée
           if (!ratingInput.value) {
               alert('Veuillez attribuer une note.');
               return;
           }
           
           // Soumettre le formulaire avec AJAX
           const formData = new FormData(this);
           
           fetch('/api/submit-rating.php', {
               method: 'POST',
               body: formData
           })
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   // Masquer le formulaire et afficher un message de confirmation
                   this.innerHTML = '<div class="alert alert-success">Votre avis a été enregistré. Merci !</div>';
               } else {
                   alert(data.message || 'Une erreur est survenue lors de l\'enregistrement de votre avis.');
               }
           })
           .catch(error => {
               console.error('Erreur lors de l\'enregistrement de l\'avis:', error);
           });
       });
   });
}
