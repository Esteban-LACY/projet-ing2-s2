// Script principal pour OmnesBnB

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des composants interactifs
    initDatePickers();
    setupMobileNav();
    
    // Gestion des alertes
    const alertes = document.querySelectorAll('[role="alert"]');
    alertes.forEach(alerte => {
        setTimeout(() => {
            alerte.classList.add('opacity-0');
            setTimeout(() => {
                alerte.remove();
            }, 300);
        }, 5000);
    });
});

/**
 * Initialisation des sélecteurs de date
 */
function initDatePickers() {
    // À implémenter avec un plugin ou natif selon les besoins
    const dateInputs = document.querySelectorAll('input[type="date"]');
    // Code pour améliorer l'expérience des datepickers si nécessaire
}

/**
 * Configuration de la navigation mobile
 */
function setupMobileNav() {
    // Ajout de la classe active au lien de navigation actuel
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('nav a');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath === linkPath || 
            (currentPath.includes('/logement') && linkPath.includes('/logement')) ||
            (currentPath.includes('/recherche') && linkPath.includes('/recherche'))) {
            link.classList.add('text-black', 'font-bold');
        }
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
