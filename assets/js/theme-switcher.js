/**
 * OmnesBnB - Gestion du thème clair/sombre
 * Ce fichier gère la fonctionnalité de basculement entre les thèmes clair et sombre
 */

// Définir le module comme IIFE pour éviter les conflits de portée
const ThemeSwitcher = (function() {
    // Configuration privée
    const STORAGE_KEY = 'omnesbnb_theme';
    const DARK_CLASS = 'dark-mode';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';
    
    // Éléments DOM
    let switcherElement;
    let sunIcon;
    let moonIcon;
    
    /**
     * Initialise le gestionnaire de thème
     */
    function initialize() {
        // Récupérer les éléments DOM
        switcherElement = document.getElementById('theme-switch');
        sunIcon = document.querySelector('.theme-icon-sun');
        moonIcon = document.querySelector('.theme-icon-moon');
        
        if (!switcherElement) {
            console.warn('Élément switch de thème non trouvé');
            return;
        }
        
        // Appliquer le thème initial
        applyInitialTheme();
        
        // Ajouter l'écouteur d'événement pour le changement de thème
        switcherElement.addEventListener('change', handleThemeToggle);
        
        // Écouter les changements de préférence système
        listenForSystemPreferenceChanges();
    }
    
    /**
     * Applique le thème initial basé sur les préférences
     */
    function applyInitialTheme() {
        // Vérifier s'il existe une préférence sauvegardée
        const savedTheme = localStorage.getItem(STORAGE_KEY);
        
        // Vérifier si l'utilisateur a défini une préférence de thème dans son système
        const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Appliquer le thème sauvegardé ou la préférence système
        if (savedTheme === THEME_DARK || (!savedTheme && prefersDarkMode)) {
            applyDarkTheme();
        } else {
            applyLightTheme();
        }
    }
    
    /**
     * Gère le basculement du thème quand l'utilisateur utilise le switch
     */
    function handleThemeToggle() {
        if (switcherElement.checked) {
            applyDarkTheme();
        } else {
            applyLightTheme();
        }
    }
    
    /**
     * Applique le thème sombre
     */
    function applyDarkTheme() {
        document.body.classList.add(DARK_CLASS);
        
        if (switcherElement) {
            switcherElement.checked = true;
        }
        
        // Mettre à jour les icônes si elles existent
        if (sunIcon && moonIcon) {
            sunIcon.style.display = 'inline-block';
            moonIcon.style.display = 'none';
        }
        
        // Sauvegarder la préférence
        localStorage.setItem(STORAGE_KEY, THEME_DARK);
        
        // Mettre à jour l'attribut data-theme pour les composants qui l'utilisent
        document.documentElement.setAttribute('data-theme', THEME_DARK);
        
        // Envoyer la préférence au serveur si l'utilisateur est connecté
        updateServerPreference(THEME_DARK);
        
        // Déclencher un événement personnalisé
        dispatchThemeEvent(THEME_DARK);
    }
    
    /**
     * Applique le thème clair
     */
    function applyLightTheme() {
        document.body.classList.remove(DARK_CLASS);
        
        if (switcherElement) {
            switcherElement.checked = false;
        }
        
        // Mettre à jour les icônes si elles existent
        if (sunIcon && moonIcon) {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'inline-block';
        }
        
        // Sauvegarder la préférence
        localStorage.setItem(STORAGE_KEY, THEME_LIGHT);
        
        // Mettre à jour l'attribut data-theme pour les composants qui l'utilisent
        document.documentElement.setAttribute('data-theme', THEME_LIGHT);
        
        // Envoyer la préférence au serveur si l'utilisateur est connecté
        updateServerPreference(THEME_LIGHT);
        
        // Déclencher un événement personnalisé
        dispatchThemeEvent(THEME_LIGHT);
    }
    
    /**
     * Écoute les changements de préférence système
     */
    function listenForSystemPreferenceChanges() {
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        darkModeMediaQuery.addEventListener('change', (e) => {
            // Ne modifier le thème que si l'utilisateur n'a pas explicitement choisi un thème
            if (!localStorage.getItem(STORAGE_KEY)) {
                if (e.matches) {
                    applyDarkTheme();
                } else {
                    applyLightTheme();
                }
            }
        });
    }
    
    /**
     * Envoie la préférence de thème au serveur pour les utilisateurs connectés
     * @param {string} theme - Le thème ('light' ou 'dark')
     */
    function updateServerPreference(theme) {
        // Vérifier si l'utilisateur est connecté (présence de l'attribut data-user-id sur le body)
        const userId = document.body.getAttribute('data-user-id');
        
        if (!userId) {
            return; // L'utilisateur n'est pas connecté
        }
        
        // Envoyer la préférence au serveur
        fetch('/api/toggle-theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                theme: theme
            })
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour de la préférence de thème:', error);
        });
    }
    
    /**
     * Déclenche un événement personnalisé lors du changement de thème
     * @param {string} theme - Le thème appliqué ('light' ou 'dark')
     */
    function dispatchThemeEvent(theme) {
        const event = new CustomEvent('themeChanged', {
            detail: { theme: theme }
        });
        
        document.dispatchEvent(event);
    }
    
    /**
     * Obtient le thème actuel
     * @returns {string} - Le thème actuel ('light' ou 'dark')
     */
    function getCurrentTheme() {
        return document.body.classList.contains(DARK_CLASS) ? THEME_DARK : THEME_LIGHT;
    }
    
    /**
     * Bascule manuellement entre les thèmes
     */
    function toggleTheme() {
        if (getCurrentTheme() === THEME_LIGHT) {
            applyDarkTheme();
        } else {
            applyLightTheme();
        }
    }
    
    // API publique
    return {
        initialize: initialize,
        getCurrentTheme: getCurrentTheme,
        toggleTheme: toggleTheme,
        applyDarkTheme: applyDarkTheme,
        applyLightTheme: applyLightTheme
    };
})();

// Initialiser le gestionnaire de thème au chargement du document
document.addEventListener('DOMContentLoaded', ThemeSwitcher.initialize);

// Exposer l'API de ThemeSwitcher globalement pour utilisation dans d'autres scripts
window.ThemeSwitcher = ThemeSwitcher;
