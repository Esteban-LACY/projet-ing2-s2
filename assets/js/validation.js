/**
 * OmnesBnB - Validation des formulaires
 * Ce fichier gère toutes les validations côté client des formulaires de l'application
 */

// Définir le module comme IIFE pour éviter les conflits de portée
const FormValidator = (function() {
    // Configuration par défaut
    const config = {
        errorClass: 'form-error',
        inputErrorClass: 'input-error',
        successClass: 'input-success',
        omnesEmailDomains: ['omnesintervenant.com', 'ece.fr', 'edu.ece.fr']
    };
    
    // Règles de validation
    const rules = {
        // Motifs regex pour les validations courantes
        patterns: {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/,
            password: /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/
        },
        
        // Fonctions de validation personnalisées
        validators: {
            required: function(value) {
                return value.trim() !== '';
            },
            email: function(value) {
                return rules.patterns.email.test(value);
            },
            omnesEmail: function(value) {
                if (!rules.validators.email(value)) return false;
                
                const domain = value.split('@')[1];
                return config.omnesEmailDomains.includes(domain);
            },
            phone: function(value) {
                return rules.patterns.phone.test(value);
            },
            password: function(value) {
                return rules.patterns.password.test(value);
            },
            minLength: function(value, minLength) {
                return value.length >= minLength;
            },
            maxLength: function(value, maxLength) {
                return value.length <= maxLength;
            },
            equalTo: function(value, targetId) {
                const targetValue = document.getElementById(targetId).value;
                return value === targetValue;
            },
            date: function(value) {
                const date = new Date(value);
                return !isNaN(date.getTime());
            },
            futureDate: function(value) {
                if (!rules.validators.date(value)) return false;
                
                const date = new Date(value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                return date >= today;
            },
            dateAfter: function(value, targetId) {
                if (!rules.validators.date(value)) return false;
                
                const targetValue = document.getElementById(targetId).value;
                if (!rules.validators.date(targetValue)) return false;
                
                const date = new Date(value);
                const targetDate = new Date(targetValue);
                
                return date > targetDate;
            },
            number: function(value) {
                return !isNaN(parseFloat(value)) && isFinite(value);
            },
            min: function(value, min) {
                return parseFloat(value) >= min;
            },
            max: function(value, max) {
                return parseFloat(value) <= max;
            },
            fileType: function(fileInput, types) {
                if (!fileInput.files || fileInput.files.length === 0) return true;
                
                const file = fileInput.files[0];
                const fileType = file.type;
                
                return types.includes(fileType);
            },
            fileSize: function(fileInput, maxSize) {
                if (!fileInput.files || fileInput.files.length === 0) return true;
                
                const file = fileInput.files[0];
                return file.size <= maxSize * 1024 * 1024; // Convertir en octets
            }
        },
        
        // Messages d'erreur
        messages: {
            required: 'Ce champ est obligatoire',
            email: 'Veuillez entrer une adresse email valide',
            omnesEmail: 'Veuillez utiliser une adresse email Omnes (omnesintervenant.com, ece.fr, edu.ece.fr)',
            phone: 'Veuillez entrer un numéro de téléphone valide',
            password: 'Le mot de passe doit contenir au moins 8 caractères, dont au moins 1 lettre et 1 chiffre',
            minLength: 'Ce champ doit contenir au moins {0} caractères',
            maxLength: 'Ce champ ne doit pas dépasser {0} caractères',
            equalTo: 'Ce champ doit être identique au champ {0}',
            date: 'Veuillez entrer une date valide',
            futureDate: 'La date doit être future ou aujourd\'hui',
            dateAfter: 'La date doit être postérieure à la date de début',
            number: 'Veuillez entrer un nombre valide',
            min: 'La valeur doit être supérieure ou égale à {0}',
            max: 'La valeur doit être inférieure ou égale à {0}',
            fileType: 'Type de fichier non autorisé. Types acceptés: {0}',
            fileSize: 'La taille du fichier ne doit pas dépasser {0} Mo'
        }
    };
    
    /**
     * Initialise la validation des formulaires
     * @param {string|HTMLElement} formSelector - Sélecteur CSS ou élément DOM du formulaire
     * @param {Object} options - Options de configuration
     */
    function initialize(formSelector, options = {}) {
        // Fusionner les options avec la configuration par défaut
        const mergedConfig = Object.assign({}, config, options);
        
        // Sélectionner le formulaire
        const form = typeof formSelector === 'string' 
            ? document.querySelector(formSelector) 
            : formSelector;
        
        if (!form) {
            console.error('Formulaire non trouvé:', formSelector);
            return;
        }
        
        // Ajouter l'écouteur d'événement pour la soumission du formulaire
        form.addEventListener('submit', function(e) {
            // Valider le formulaire avant la soumission
            if (!validateForm(form, mergedConfig)) {
                e.preventDefault();
            }
        });
        
        // Ajouter les écouteurs d'événements pour la validation en temps réel
        setupLiveValidation(form, mergedConfig);
    }
    
    /**
     * Configure la validation en temps réel pour les champs du formulaire
     * @param {HTMLElement} form - Élément DOM du formulaire
     * @param {Object} config - Configuration
     */
    function setupLiveValidation(form, config) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Valider lors de la perte de focus
            input.addEventListener('blur', function() {
                validateField(this, form, config);
            });
            
            // Réinitialiser les erreurs lors de la saisie
            input.addEventListener('input', function() {
                const errorElement = this.parentNode.querySelector('.' + config.errorClass);
                
                if (errorElement) {
                    errorElement.remove();
                }
                
                this.classList.remove(config.inputErrorClass);
            });
        });
    }
    
    /**
     * Valide un formulaire complet
     * @param {HTMLElement} form - Élément DOM du formulaire
     * @param {Object} config - Configuration
     * @returns {boolean} - True si le formulaire est valide
     */
    function validateForm(form, config) {
        let isValid = true;
        const inputs = form.querySelectorAll('input, select, textarea');
        
        // Supprimer toutes les erreurs existantes
        form.querySelectorAll('.' + config.errorClass).forEach(el => el.remove());
        
        // Valider chaque champ
        inputs.forEach(input => {
            if (!validateField(input, form, config)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Valide un champ spécifique
     * @param {HTMLElement} field - Élément DOM du champ
     * @param {HTMLElement} form - Élément DOM du formulaire
     * @param {Object} config - Configuration
     * @returns {boolean} - True si le champ est valide
     */
    function validateField(field, form, config) {
        // Ignorer les champs désactivés ou cachés
        if (field.disabled || field.type === 'hidden' || field.style.display === 'none') {
            return true;
        }
        
        let isValid = true;
        let errorMessage = '';
        
        // Récupérer les attributs de validation
        const validationRules = getValidationRules(field);
        
        // Vérifier chaque règle de validation
        for (const rule in validationRules) {
            const ruleValue = validationRules[rule];
            
            if (rule === 'required' && rules.validators.required) {
                if (!rules.validators.required(field.value)) {
                    isValid = false;
                    errorMessage = rules.messages.required;
                    break;
                }
            } else if (field.value.trim() !== '') {
                // Appliquer les autres validations seulement si le champ n'est pas vide
                if (rules.validators[rule]) {
                    if (!rules.validators[rule](field.value, ruleValue)) {
                        isValid = false;
                        
                        // Formater le message d'erreur
                        errorMessage = rules.messages[rule].replace('{0}', ruleValue);
                        
                        if (rule === 'equalTo') {
                            const targetField = document.getElementById(ruleValue);
                            if (targetField && targetField.labels && targetField.labels.length > 0) {
                                errorMessage = errorMessage.replace('{0}', targetField.labels[0].textContent);
                            }
                        }
                        
                        break;
                    }
                }
            }
        }
        
        // Traitement spécial pour les champs de fichier
        if (field.type === 'file' && field.files.length > 0) {
            // Vérifier le type de fichier
            if (validationRules.fileType) {
                const allowedTypes = validationRules.fileType.split(',');
                if (!rules.validators.fileType(field, allowedTypes)) {
                    isValid = false;
                    errorMessage = rules.messages.fileType.replace('{0}', allowedTypes.join(', '));
                }
            }
            
            // Vérifier la taille du fichier
            if (validationRules.fileSize) {
                const maxSize = validationRules.fileSize;
                if (!rules.validators.fileSize(field, maxSize)) {
                    isValid = false;
                    errorMessage = rules.messages.fileSize.replace('{0}', maxSize);
                }
            }
        }
        
        // Appliquer les classes et messages d'erreur
        if (!isValid) {
            // Ajouter la classe d'erreur au champ
            field.classList.add(config.inputErrorClass);
            field.classList.remove(config.successClass);
            
            // Afficher le message d'erreur
            showErrorMessage(field, errorMessage, config);
        } else {
            // Ajouter la classe de succès
            field.classList.remove(config.inputErrorClass);
            field.classList.add(config.successClass);
            
            // Supprimer les messages d'erreur
            const errorElement = field.parentNode.querySelector('.' + config.errorClass);
            if (errorElement) {
                errorElement.remove();
            }
        }
        
        return isValid;
    }
    
    /**
     * Récupère les règles de validation à partir des attributs HTML
     * @param {HTMLElement} field - Élément DOM du champ
     * @returns {Object} - Règles de validation
     */
    function getValidationRules(field) {
        const rules = {};
        
        // Attributs de validation HTML standard
        if (field.required) rules.required = true;
        if (field.type === 'email') rules.email = true;
        if (field.minLength) rules.minLength = field.minLength;
        if (field.maxLength) rules.maxLength = field.maxLength;
        if (field.min) rules.min = field.min;
        if (field.max) rules.max = field.max;
        if (field.pattern) rules.pattern = field.pattern;
        
        // Attributs de validation personnalisés
        if (field.dataset.validate) {
            const validators = field.dataset.validate.split(' ');
            validators.forEach(validator => {
                if (validator === 'omnesEmail' || validator === 'phone' || 
                    validator === 'password' || validator === 'date' || 
                    validator === 'futureDate') {
                    rules[validator] = true;
                }
            });
        }
        
        // Validation de correspondance (pour les mots de passe)
        if (field.dataset.equalTo) {
            rules.equalTo = field.dataset.equalTo;
        }
        
        // Validation de date après une autre
        if (field.dataset.dateAfter) {
            rules.dateAfter = field.dataset.dateAfter;
        }
        
        // Validation de type de fichier
        if (field.dataset.fileType) {
            rules.fileType = field.dataset.fileType;
        }
        
        // Validation de taille de fichier
        if (field.dataset.fileSize) {
            rules.fileSize = field.dataset.fileSize;
        }
        
        return rules;
    }
    
    /**
     * Affiche un message d'erreur pour un champ
     * @param {HTMLElement} field - Élément DOM du champ
     * @param {string} message - Message d'erreur
     * @param {Object} config - Configuration
     */
    function showErrorMessage(field, message, config) {
        // Supprimer les messages d'erreur existants
        const existingError = field.parentNode.querySelector('.' + config.errorClass);
        if (existingError) {
            existingError.remove();
        }
        
        // Créer l'élément d'erreur
        const errorElement = document.createElement('div');
        errorElement.className = config.errorClass;
        errorElement.textContent = message;
        
        // Ajouter l'élément d'erreur après le champ
        field.parentNode.appendChild(errorElement);
    }
    
    /**
     * Valide un champ spécifique de manière asynchrone (pour la vérification d'email, etc.)
     * @param {string} fieldId - ID du champ
     * @param {string} url - URL de l'endpoint de validation
     * @param {string} errorMessage - Message d'erreur en cas d'échec
     * @returns {Promise} - Promise résolue avec le résultat de la validation
     */
    function validateFieldAsync(fieldId, url, errorMessage) {
        const field = document.getElementById(fieldId);
        
        if (!field) {
            console.error('Champ non trouvé:', fieldId);
            return Promise.reject(new Error('Champ non trouvé'));
        }
        
        const value = field.value.trim();
        
        if (!value) {
            return Promise.resolve(true);
        }
        
        // Création de FormData pour l'envoi
        const formData = new FormData();
        formData.append('value', value);
        
        // Afficher un indicateur de chargement
        field.classList.add('loading');
        
        return fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            field.classList.remove('loading');
            
            if (!data.valid) {
                // Afficher l'erreur
                showErrorMessage(field, errorMessage, config);
                field.classList.add(config.inputErrorClass);
                field.classList.remove(config.successClass);
                return false;
            } else {
                // Supprimer l'erreur
                const errorElement = field.parentNode.querySelector('.' + config.errorClass);
                if (errorElement) {
                    errorElement.remove();
                }
                
                field.classList.remove(config.inputErrorClass);
                field.classList.add(config.successClass);
                return true;
            }
        })
        .catch(error => {
            field.classList.remove('loading');
            console.error('Erreur lors de la validation asynchrone:', error);
            return false;
        });
    }
    
    // API publique
    return {
        initialize: initialize,
        validateForm: validateForm,
        validateField: validateField,
        validateFieldAsync: validateFieldAsync,
        rules: rules
    };
})();

// Initialiser la validation des formulaires au chargement du document
document.addEventListener('DOMContentLoaded', function() {
    // Validation du formulaire de connexion
    if (document.getElementById('login-form')) {
        FormValidator.initialize('#login-form');
    }
    
    // Validation du formulaire d'inscription
    if (document.getElementById('register-form')) {
        FormValidator.initialize('#register-form');
        
        // Vérification d'unicité de l'email
        const emailField = document.getElementById('email');
        
        if (emailField) {
            emailField.addEventListener('blur', function() {
                if (FormValidator.rules.validators.email(this.value)) {
                    FormValidator.validateFieldAsync(
                        'email',
                        '/api/check-email.php',
                        'Cette adresse email est déjà utilisée'
                    );
                }
            });
        }
    }
    
    // Validation du formulaire de profil
    if (document.getElementById('profile-form')) {
        FormValidator.initialize('#profile-form');
    }
    
    // Validation du formulaire de création de logement
    if (document.getElementById('logement-form')) {
        FormValidator.initialize('#logement-form');
    }
    
    // Validation du formulaire de réservation
    if (document.getElementById('reservation-form')) {
        FormValidator.initialize('#reservation-form');
    }
    
    // Validation du formulaire d'avis
    if (document.querySelectorAll('.rating-form').length > 0) {
        document.querySelectorAll('.rating-form').forEach(form => {
            FormValidator.initialize(form);
        });
    }
});

// Exposer l'API de FormValidator globalement pour utilisation dans d'autres scripts
window.FormValidator = FormValidator;
