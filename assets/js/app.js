/**
 * Script principal OmnesBnB
 * Gère les fonctionnalités communes à toutes les pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Référence aux éléments du DOM fréquemment utilisés
    const navItems = document.querySelectorAll('.nav-item');
    const pageContent = document.querySelector('.page-content');
    
    // Gestion de la navigation
    initNavigation();
    
    // Initialisation des interactions communes
    setupFormValidation();
    handleMobileInteractions();
    
    /**
     * Initialise la navigation mobile
     */
    function initNavigation() {
        // Marquer l'élément de navigation actif
        const currentPath = window.location.pathname;
        
        navItems.forEach(item => {
            const link = item.getAttribute('data-href');
            if (currentPath.includes(link)) {
                item.classList.add('nav-item-active');
                item.classList.remove('nav-item-inactive');
            } else {
                item.classList.add('nav-item-inactive');
                item.classList.remove('nav-item-active');
            }
            
            // Ajouter les écouteurs d'événements pour la navigation
            item.addEventListener('click', function() {
                const targetPage = this.getAttribute('data-href');
                if (targetPage) {
                    navigateToPage(targetPage);
                }
            });
        });
    }
    
    /**
     * Navigation vers une nouvelle page
     * @param {string} url - URL de destination
     */
    function navigateToPage(url) {
        if (pageContent) {
            pageContent.classList.add('loading');
        }
        window.location.href = url;
    }
    
    /**
     * Configure la validation basique des formulaires
     */
    function setupFormValidation() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('border-red-500');
                        
                        // Créer ou afficher un message d'erreur
                        let errorMsg = field.nextElementSibling;
                        if (!errorMsg || !errorMsg.classList.contains('error-msg')) {
                            errorMsg = document.createElement('p');
                            errorMsg.classList.add('error-msg', 'text-red-500', 'text-sm', 'mt-1');
                            field.parentNode.insertBefore(errorMsg, field.nextSibling);
                        }
                        errorMsg.textContent = 'Ce champ est requis';
                    } else {
                        field.classList.remove('border-red-500');
                        const errorMsg = field.nextElementSibling;
                        if (errorMsg && errorMsg.classList.contains('error-msg')) {
                            errorMsg.textContent = '';
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    }
    
    /**
     * Gère les interactions spécifiques aux mobiles
     */
    function handleMobileInteractions() {
        // Ajout de la classe pour l'espace de la navigation bottom
        if (pageContent) {
            pageContent.classList.add('mb-safe');
        }
        
        // Gestion du tap pour masquer les claviers virtuels
        document.addEventListener('click', function(e) {
            if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
                if (e.target !== document.activeElement) {
                    document.activeElement.blur();
                }
            }
        });
    }
});
