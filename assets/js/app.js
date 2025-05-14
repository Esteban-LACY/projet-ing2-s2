// Script principal pour OmnesBnB

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des composants interactifs
    initDatePickers();
    setupMobileNav();
    setupAlerts();
    
    // Gestionnaires d'événements généraux
    setupEventListeners();
});

/**
 * Initialisation des sélecteurs de date
 */
function initDatePickers() {
    // Amélioration des inputs de type date
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Définir la date minimale à aujourd'hui pour les réservations
        const today = new Date().toISOString().split('T')[0];
        
        if (input.getAttribute('min') === null && 
            (input.name === 'date_debut' || 
             input.name === 'dates_debut[]' || 
             input.name === 'date_debut_dispo')) {
            input.setAttribute('min', today);
        }
        
        // Synchroniser les dates de fin avec les dates de début
        if (input.name === 'date_debut' || input.name === 'dates_debut[]') {
            input.addEventListener('change', function() {
                const debutValue = this.value;
                const finInput = this.closest('form').querySelector('input[name="date_fin"], input[name="dates_fin[]"]');
                
                if (finInput && (!finInput.value || new Date(finInput.value) <= new Date(debutValue))) {
                    // Si date de fin non définie ou inférieure à date de début, 
                    // définir date de fin à date de début + 1 jour
                    const nextDay = new Date(debutValue);
                    nextDay.setDate(nextDay.getDate() + 1);
                    finInput.value = nextDay.toISOString().split('T')[0];
                    finInput.setAttribute('min', debutValue);
                }
            });
        }
    });
}

/**
 * Configuration de la navigation mobile
 */
function setupMobileNav() {
    // Ajout de la classe active au lien de navigation actuel
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('nav a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        
        // Détection de la page d'accueil
        if (currentPath === '/' || currentPath.endsWith('/index.php') || currentPath.endsWith('/omnesbnb/')) {
            if (href.endsWith('/') || href.endsWith('/index.php') || href.endsWith('/omnesbnb/')) {
                link.classList.add('active');
            }
        } 
        // Détection de la page de publication
        else if (currentPath.includes('/publier.php')) {
            if (href.includes('/publier.php')) {
                link.classList.add('active');
            }
        } 
        // Détection de la page de profil
        else if (currentPath.includes('/profil.php') || 
                 currentPath.includes('/modifier-profil.php') || 
                 currentPath.includes('/changer-mot-de-passe.php')) {
            if (href.includes('/profil.php')) {
                link.classList.add('active');
            }
        }
    });
}

/**
 * Gestion des alertes
 */
function setupAlerts() {
    const alertes = document.querySelectorAll('[role="alert"]');
    
    alertes.forEach(alerte => {
        // Ajouter un bouton de fermeture
        const btnClose = document.createElement('button');
        btnClose.className = 'float-right ml-4 text-sm font-bold';
        btnClose.innerHTML = '&times;';
        btnClose.setAttribute('aria-label', 'Fermer');
        
        btnClose.addEventListener('click', () => {
            fadeOut(alerte);
        });
        
        alerte.insertBefore(btnClose, alerte.firstChild);
        
        // Faire disparaître l'alerte après 5 secondes
        setTimeout(() => {
            fadeOut(alerte);
        }, 5000);
    });
}

/**
 * Animation de disparition progressive
 * @param {HTMLElement} element - Élément à faire disparaître
 */
function fadeOut(element) {
    element.style.transition = 'opacity 0.3s ease-out';
    element.style.opacity = '0';
    
    setTimeout(() => {
        element.style.display = 'none';
        element.remove();
    }, 300);
}

/**
 * Configuration des écouteurs d'événements généraux
 */
function setupEventListeners() {
    // Validation côté client des formulaires
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredInputs = form.querySelectorAll('[required]');
            let hasError = false;
            
            requiredInputs.forEach(input => {
                // Supprimer les messages d'erreur précédents
                const errorElement = input.nextElementSibling;
                if (errorElement && errorElement.classList.contains('form-error')) {
                    errorElement.remove();
                }
                
                // Vérifier si l'input est vide
                if (!input.value.trim()) {
                    hasError = true;
                    
                    // Créer un message d'erreur
                    const error = document.createElement('p');
                    error.className = 'form-error';
                    error.textContent = 'Ce champ est obligatoire.';
                    
                    // Insérer après l'input
                    input.parentNode.insertBefore(error, input.nextSibling);
                }
            });
            
            if (hasError) {
                e.preventDefault();
            }
        });
    });
    
    // Toggle des sections dépliables
    const toggleButtons = document.querySelectorAll('[data-toggle]');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-toggle');
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                if (targetElement.classList.contains('hidden')) {
                    targetElement.classList.remove('hidden');
                    this.setAttribute('aria-expanded', 'true');
                } else {
                    targetElement.classList.add('hidden');
                    this.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });
}

/**
 * Fonction utilitaire pour les requêtes AJAX
 * @param {string} url - URL de la requête
 * @param {object} options - Options de la requête
 * @returns {Promise} Promesse avec la réponse
 */
function ajaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const mergedOptions = {...defaultOptions, ...options};
    
    return fetch(url, mergedOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        });
}

/**
 * Formatage d'un prix
 * @param {number} prix - Prix à formater
 * @returns {string} Prix formaté
 */
function formaterPrix(prix) {
    return new Intl.NumberFormat('fr-FR', { 
        style: 'currency', 
        currency: 'EUR' 
    }).format(prix);
}
