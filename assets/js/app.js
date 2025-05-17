/**
 * Script principal OmnesBnB
 * Gère les fonctionnalités communes à toutes les pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des composants de l'interface
    initInterface();
    
    // Configuration de la navigation
    initNavigation();
    
    // Initialisation des validations de formulaire
    initFormValidation();
    
    // Gestion des animations et transitions
    initAnimations();
});

/**
 * Initialise les composants principaux de l'interface
 */
function initInterface() {
    // Masquer le splash screen si présent
    const splashScreen = document.querySelector('.splash-screen');
    if (splashScreen) {
        setTimeout(() => {
            splashScreen.classList.add('fade-out');
            setTimeout(() => {
                splashScreen.style.display = 'none';
            }, 300);
        }, 1000);
    }
    
    // Ajouter la classe pour le comportement mobile
    document.body.classList.add('mobile-view');
    
    // Initialiser le menu hamburger pour mobile
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
    }
    
    // Fermer le menu quand on clique à l'extérieur
    document.addEventListener('click', function(e) {
        if (mobileMenu && mobileMenu.classList.contains('active') && 
            !mobileMenu.contains(e.target) && 
            !menuToggle.contains(e.target)) {
            mobileMenu.classList.remove('active');
            menuToggle.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });
    
    // Gérer la barre de recherche fixe en tête
    const searchBar = document.querySelector('.search-bar');
    if (searchBar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                searchBar.classList.add('fixed');
            } else {
                searchBar.classList.remove('fixed');
            }
        });
    }
}

/**
 * Configure la navigation de l'application
 */
function initNavigation() {
    // Navigation bottom bar style Uber
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            // Supprimer la classe active de tous les éléments
            navItems.forEach(navItem => navItem.classList.remove('active'));
            
            // Ajouter la classe active à l'élément cliqué
            this.classList.add('active');
            
            // Navigation vers la page si data-href est défini
            const targetUrl = this.getAttribute('data-href');
            if (targetUrl) {
                navigateToPage(targetUrl);
            }
        });
    });
    
    // Mettre en surbrillance l'élément de navigation actif
    highlightActiveNavItem();
    
    // Gestion des boutons retour
    const backButtons = document.querySelectorAll('.back-button');
    backButtons.forEach(button => {
        button.addEventListener('click', function() {
            history.back();
        });
    });
}

/**
 * Met en surbrillance l'élément de navigation correspondant à la page courante
 */
function highlightActiveNavItem() {
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const href = item.getAttribute('data-href');
        if (href && currentPath.includes(href)) {
            item.classList.add('active');
        }
    });
}

/**
 * Navigation vers une page avec transition fluide
 * @param {string} url - URL de destination
 */
function navigateToPage(url) {
    // Ajouter une classe pour l'animation de sortie
    document.body.classList.add('page-transition-out');
    
    // Attendre la fin de l'animation avant de rediriger
    setTimeout(() => {
        window.location.href = url;
    }, 300);
}

/**
 * Initialise la validation des formulaires
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Validation à la soumission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Vérifier les champs requis
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                    highlightInvalidField(field);
                } else {
                    removeInvalidHighlight(field);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showValidationAlert();
            }
        });
        
        // Validation à la saisie pour une meilleure expérience utilisateur
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') || this.value.trim() !== '') {
                    if (!validateField(this)) {
                        highlightInvalidField(this);
                    } else {
                        removeInvalidHighlight(this);
                    }
                }
            });
            
            // Réinitialiser l'état visuel quand l'utilisateur commence à taper
            input.addEventListener('focus', function() {
                removeInvalidHighlight(this);
            });
        });
    });
}

/**
 * Valide un champ de formulaire
 * @param {HTMLElement} field - Champ à valider
 * @return {boolean} True si valide, false sinon
 */
function validateField(field) {
    // Si le champ est vide et requis
    if (field.hasAttribute('required') && field.value.trim() === '') {
        return false;
    }
    
    // Validation des emails
    if (field.type === 'email' && field.value.trim() !== '') {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(field.value)) {
            return false;
        }
    }
    
    // Validation des téléphones
    if (field.getAttribute('data-type') === 'phone' && field.value.trim() !== '') {
        const phonePattern = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
        if (!phonePattern.test(field.value)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Ajoute une mise en forme visuelle pour les champs invalides
 * @param {HTMLElement} field - Champ invalide
 */
function highlightInvalidField(field) {
    field.classList.add('invalid');
    
    // Ajouter un message d'erreur
    let errorMessage = field.nextElementSibling;
    if (!errorMessage || !errorMessage.classList.contains('error-message')) {
        errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        field.parentNode.insertBefore(errorMessage, field.nextSibling);
    }
    
    if (field.value.trim() === '') {
        errorMessage.textContent = 'Ce champ est requis';
    } else if (field.type === 'email') {
        errorMessage.textContent = 'Email invalide';
    } else if (field.getAttribute('data-type') === 'phone') {
        errorMessage.textContent = 'Numéro de téléphone invalide';
    }
}

/**
 * Supprime la mise en forme d'erreur d'un champ
 * @param {HTMLElement} field - Champ à réinitialiser
 */
function removeInvalidHighlight(field) {
    field.classList.remove('invalid');
    
    const errorMessage = field.nextElementSibling;
    if (errorMessage && errorMessage.classList.contains('error-message')) {
        errorMessage.textContent = '';
    }
}

/**
 * Affiche une alerte de validation pour mobile
 */
function showValidationAlert() {
    const alert = document.createElement('div');
    alert.className = 'validation-alert';
    alert.textContent = 'Veuillez corriger les erreurs du formulaire';
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.add('show');
        
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(alert);
            }, 300);
        }, 3000);
    }, 10);
}

/**
 * Initialise les animations de l'interface
 */
function initAnimations() {
    // Animation d'entrée pour la page
    document.body.classList.add('page-transition-in');
    
    // Enlever l'animation une fois terminée
    setTimeout(() => {
        document.body.classList.remove('page-transition-in');
    }, 500);
    
    // Animations lors du défilement
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    if (animatedElements.length > 0) {
        // Observer les éléments qui entrent dans le viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });
        
        animatedElements.forEach(element => {
            observer.observe(element);
        });
    }
}
