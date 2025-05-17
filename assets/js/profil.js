/**
 * Gestion des fonctionnalités liées au profil utilisateur
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des gestionnaires de profil
    const profileManager = new ProfileManager();
    profileManager.init();
});

/**
 * Classe de gestion du profil utilisateur
 */
class ProfileManager {
    constructor() {
        // Éléments du DOM
        this.profileForm = document.getElementById('profile-form');
        this.passwordForm = document.getElementById('password-form');
        this.uploadButton = document.getElementById('upload-photo-btn');
        this.photoInput = document.getElementById('photo-input');
        this.profileImage = document.getElementById('profile-image');
        this.previewImage = document.getElementById('profile-image-preview');
        this.deleteAccountBtn = document.getElementById('delete-account-btn');
        this.tabs = document.querySelectorAll('.profile-tab');
        this.tabContents = document.querySelectorAll('.tab-content');
        
        // État
        this.isUploading = false;
        this.photoChanged = false;
    }
    
    /**
     * Initialise tous les gestionnaires d'événements
     */
    init() {
        this.initProfileForm();
        this.initPasswordForm();
        this.initPhotoUpload();
        this.initDeleteAccount();
        this.initTabs();
        this.initLocationServices();
        this.initSwipeGestures();
    }
    
    /**
     * Initialise le formulaire de modification du profil
     */
    initProfileForm() {
        if (!this.profileForm) return;
        
        this.profileForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Ajouter un indicateur de chargement
            this.showLoading(this.profileForm);
            
            // Valider le formulaire
            if (!this.validateProfileForm()) {
                this.hideLoading(this.profileForm);
                return;
            }
            
            // Préparer les données
            const formData = new FormData(this.profileForm);
            
            // Ajouter la photo si elle a été changée
            if (this.photoChanged && this.photoInput.files.length > 0) {
                formData.append('photo', this.photoInput.files[0]);
            }
            
            // Soumettre le formulaire via AJAX
            fetch('controllers/utilisateur.php?action=modifier_profil', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.hideLoading(this.profileForm);
                
                if (data.success) {
                    this.showNotification('Profil mis à jour avec succès', 'success');
                    
                    // Mettre à jour les informations affichées
                    this.updateProfileDisplay(formData.get('prenom'), formData.get('nom'), formData.get('email'));
                    
                    // Réinitialiser l'état de la photo
                    this.photoChanged = false;
                } else {
                    // Afficher les erreurs
                    if (data.erreurs) {
                        this.displayFormErrors(this.profileForm, data.erreurs);
                    } else {
                        this.showNotification(data.message || 'Une erreur est survenue', 'error');
                    }
                }
            })
            .catch(error => {
                this.hideLoading(this.profileForm);
                console.error('Erreur lors de la mise à jour du profil:', error);
                this.showNotification('Une erreur de connexion est survenue', 'error');
            });
        });
        
        // Validation en temps réel
        const inputs = this.profileForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateInput(input);
            });
        });
    }
    
    /**
     * Initialise le formulaire de changement de mot de passe
     */
    initPasswordForm() {
        if (!this.passwordForm) return;
        
        this.passwordForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Ajouter un indicateur de chargement
            this.showLoading(this.passwordForm);
            
            // Valider le formulaire
            if (!this.validatePasswordForm()) {
                this.hideLoading(this.passwordForm);
                return;
            }
            
            // Soumettre le formulaire via AJAX
            const formData = new FormData(this.passwordForm);
            
            fetch('controllers/utilisateur.php?action=modifier_mot_de_passe', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.hideLoading(this.passwordForm);
                
                if (data.success) {
                    this.showNotification('Mot de passe modifié avec succès', 'success');
                    this.passwordForm.reset();
                    
                    // Masquer cette section après succès sur mobile
                    if (window.innerWidth < 768) {
                        // Fermer la section mot de passe et revenir à l'onglet profil
                        this.switchTab('profile-tab');
                    }
                } else {
                    this.showNotification(data.message || 'Une erreur est survenue', 'error');
                }
            })
            .catch(error => {
                this.hideLoading(this.passwordForm);
                console.error('Erreur lors de la modification du mot de passe:', error);
                this.showNotification('Une erreur de connexion est survenue', 'error');
            });
        });
        
        // Validation en temps réel du nouveau mot de passe
        const newPassword = this.passwordForm.querySelector('[name="nouveau_mot_de_passe"]');
        const confirmPassword = this.passwordForm.querySelector('[name="confirmer_mot_de_passe"]');
        
        if (newPassword && confirmPassword) {
            [newPassword, confirmPassword].forEach(input => {
                input.addEventListener('input', () => {
                    // Vérifier la correspondance des mots de passe
                    if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                        this.showInputError(confirmPassword, 'Les mots de passe ne correspondent pas');
                    } else {
                        this.hideInputError(confirmPassword);
                    }
                    
                    // Vérifier la force du mot de passe
                    if (newPassword.value.length > 0) {
                        this.updatePasswordStrength(newPassword);
                    }
                });
            });
        }
    }
    
    /**
     * Initialise l'upload de photo de profil
     */
    initPhotoUpload() {
        if (!this.uploadButton || !this.photoInput || !this.profileImage) return;
        
        // Clic sur le bouton d'upload déclenche l'input file
        this.uploadButton.addEventListener('click', () => {
            if (!this.isUploading) {
                this.photoInput.click();
            }
        });
        
        // Changement de fichier dans l'input
        this.photoInput.addEventListener('change', () => {
            if (this.photoInput.files && this.photoInput.files[0]) {
                const file = this.photoInput.files[0];
                
                // Valider le type de fichier
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    this.showNotification('Format de fichier non supporté. Utilisez JPG, PNG ou GIF.', 'error');
                    return;
                }
                
                // Valider la taille (max 2Mo)
                if (file.size > 2 * 1024 * 1024) {
                    this.showNotification('La taille de l\'image ne doit pas dépasser 2Mo', 'error');
                    return;
                }
                
                // Afficher l'aperçu
                this.showPhotoPreview(file);
                
                // Marquer la photo comme changée
                this.photoChanged = true;
                
                // Sur mobile, montrer un indicateur visuel que la photo a été sélectionnée
                if (window.innerWidth < 768) {
                    this.uploadButton.classList.add('photo-selected');
                    this.uploadButton.textContent = 'Photo sélectionnée';
                }
            }
        });
        
        // Double tap sur la photo pour ouvrir la sélection (mobile)
        let lastTap = 0;
        this.profileImage.addEventListener('touchend', (e) => {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;
            
            if (tapLength < 500 && tapLength > 0) {
                // Double tap détecté
                e.preventDefault();
                this.photoInput.click();
            }
            
            lastTap = currentTime;
        });
        
        // Si prévisualisation disponible, ajouter bouton d'annulation
        if (this.previewImage) {
            const cancelButton = document.createElement('button');
            cancelButton.className = 'cancel-upload-btn';
            cancelButton.innerHTML = '<span class="material-icons">close</span>';
            cancelButton.type = 'button';
            
            this.previewImage.parentNode.appendChild(cancelButton);
            
            cancelButton.addEventListener('click', () => {
                // Masquer la prévisualisation
                this.previewImage.src = '';
                this.previewImage.parentNode.classList.remove('has-preview');
                
                // Réinitialiser l'input file
                this.photoInput.value = '';
                
                // Réinitialiser l'état
                this.photoChanged = false;
                
                // Réinitialiser le bouton
                this.uploadButton.classList.remove('photo-selected');
                this.uploadButton.textContent = 'Changer de photo';
            });
        }
    }
    
    /**
     * Affiche la prévisualisation d'une photo
     * @param {File} file - Fichier image
     */
    showPhotoPreview(file) {
        if (!this.previewImage) return;
        
        const reader = new FileReader();
        
        reader.onload = (e) => {
            this.previewImage.src = e.target.result;
            this.previewImage.parentNode.classList.add('has-preview');
            
            // Facultatif: Upload automatique sur mobile
            if (window.innerWidth < 768 && this.photoChanged) {
                // Si on est sur mobile et dans le formulaire de profil,
                // la photo sera envoyée avec le formulaire
                if (!this.profileForm) {
                    this.uploadPhoto(file);
                }
            }
        };
        
        reader.readAsDataURL(file);
    }
    
    /**
     * Upload une photo directement
     * @param {File} file - Fichier image
     */
    uploadPhoto(file) {
        // Éviter les uploads multiples
        if (this.isUploading) return;
        
        this.isUploading = true;
        
        // Ajouter un indicateur de chargement
        this.uploadButton.classList.add('loading');
        this.uploadButton.innerHTML = '<span class="spinner"></span> Envoi en cours...';
        
        const formData = new FormData();
        formData.append('photo', file);
        
        fetch('controllers/utilisateur.php?action=upload_photo', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.isUploading = false;
            this.uploadButton.classList.remove('loading');
            
            if (data.success) {
                this.showNotification('Photo de profil mise à jour', 'success');
                
                // Mettre à jour l'image de profil affichée
                if (data.photo_url && this.profileImage) {
                    this.profileImage.src = data.photo_url + '?t=' + new Date().getTime();
                    this.photoChanged = false;
                    
                    // Réinitialiser le bouton
                    this.uploadButton.textContent = 'Changer de photo';
                    this.uploadButton.classList.remove('photo-selected');
                }
            } else {
                this.showNotification(data.message || 'Erreur lors de l\'upload', 'error');
                this.uploadButton.textContent = 'Réessayer';
            }
        })
        .catch(error => {
            this.isUploading = false;
            this.uploadButton.classList.remove('loading');
            this.uploadButton.textContent = 'Réessayer';
            console.error('Erreur lors de l\'upload:', error);
            this.showNotification('Erreur de connexion', 'error');
        });
    }
    
    /**
     * Initialise le bouton de suppression de compte
     */
    initDeleteAccount() {
        if (!this.deleteAccountBtn) return;
        
        this.deleteAccountBtn.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Sur mobile, utiliser une boîte de dialogue native
            if (window.innerWidth < 768) {
                if (confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
                    this.deleteAccount();
                }
            } else {
                // Sur desktop, utiliser une modal plus élaborée
                this.showDeleteConfirmation();
            }
        });
    }
    
    /**
     * Affiche une modal de confirmation pour la suppression de compte
     */
    showDeleteConfirmation() {
        // Créer la modal
        const modal = document.createElement('div');
        modal.className = 'confirmation-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>Supprimer votre compte</h3>
                <p>Cette action est irréversible. Toutes vos données personnelles, logements et réservations seront supprimés.</p>
                <div class="modal-actions">
                    <button class="btn-secondary cancel-btn">Annuler</button>
                    <button class="btn-danger confirm-btn">Supprimer définitivement</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Animation d'entrée
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        
        // Gérer les actions
        const cancelBtn = modal.querySelector('.cancel-btn');
        const confirmBtn = modal.querySelector('.confirm-btn');
        
        cancelBtn.addEventListener('click', () => {
            modal.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(modal);
            }, 300);
        });
        
        confirmBtn.addEventListener('click', () => {
            // Remplacer les boutons par un indicateur de chargement
            const modalActions = modal.querySelector('.modal-actions');
            modalActions.innerHTML = '<div class="spinner-container"><div class="spinner"></div><p>Suppression en cours...</p></div>';
            
            // Supprimer le compte
            this.deleteAccount(modal);
        });
    }
    
    /**
     * Supprime le compte utilisateur
     * @param {HTMLElement} modal - Élément modal (facultatif)
     */
    deleteAccount(modal = null) {
        fetch('controllers/utilisateur.php?action=supprimer_compte', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès
                this.showNotification('Compte supprimé avec succès', 'success');
                
                // Fermer la modal si elle existe
                if (modal) {
                    modal.querySelector('.modal-content').innerHTML = `
                        <div class="success-message">
                            <span class="material-icons">check_circle</span>
                            <h3>Compte supprimé</h3>
                            <p>Votre compte a été supprimé avec succès.</p>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    // Rediriger vers la page d'accueil
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                }
            } else {
                if (modal) {
                    // Afficher l'erreur dans la modal
                    modal.querySelector('.modal-content').innerHTML = `
                        <div class="error-message">
                            <span class="material-icons">error</span>
                            <h3>Erreur</h3>
                            <p>${data.message || 'Une erreur est survenue'}</p>
                            <button class="btn-secondary close-btn">Fermer</button>
                        </div>
                    `;
                    
                    modal.querySelector('.close-btn').addEventListener('click', () => {
                        modal.classList.remove('show');
                        setTimeout(() => {
                            document.body.removeChild(modal);
                        }, 300);
                    });
                } else {
                    // Afficher l'erreur via notification
                    this.showNotification(data.message || 'Une erreur est survenue', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression du compte:', error);
            
            if (modal) {
                // Afficher l'erreur dans la modal
                modal.querySelector('.modal-content').innerHTML = `
                    <div class="error-message">
                        <span class="material-icons">error</span>
                        <h3>Erreur de connexion</h3>
                        <p>Impossible de communiquer avec le serveur. Veuillez réessayer plus tard.</p>
                        <button class="btn-secondary close-btn">Fermer</button>
                    </div>
                `;
                
                modal.querySelector('.close-btn').addEventListener('click', () => {
                    modal.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(modal);
                    }, 300);
                });
            } else {
                // Afficher l'erreur via notification
                this.showNotification('Erreur de connexion', 'error');
            }
        });
    }
    
    /**
     * Initialise les onglets de la page profil
     */
    initTabs() {
        if (!this.tabs.length) return;
        
        this.tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = tab.getAttribute('data-target');
                this.switchTab(targetId);
            });
        });
        
        // Activer l'onglet par défaut ou celui spécifié dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            this.switchTab(tabParam);
        } else {
            // Activer le premier onglet par défaut
            const defaultTab = this.tabs[0].getAttribute('data-target');
            this.switchTab(defaultTab);
        }
    }
    
    /**
     * Change d'onglet actif
     * @param {string} targetId - ID de l'onglet cible
     */
    switchTab(targetId) {
        // Désactiver tous les onglets
        this.tabs.forEach(tab => {
            tab.classList.remove('active');
            
            if (tab.getAttribute('data-target') === targetId) {
                tab.classList.add('active');
            }
        });
        
        // Masquer tous les contenus d'onglet
        this.tabContents.forEach(content => {
            content.classList.remove('active');
            
            if (content.id === targetId) {
                content.classList.add('active');
                
                // Animation d'entrée
                content.style.opacity = 0;
                content.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    content.style.opacity = 1;
                    content.style.transform = 'translateY(0)';
                }, 50);
            }
        });
        
        // Mettre à jour l'URL sans recharger la page
        const url = new URL(window.location);
        url.searchParams.set('tab', targetId);
        window.history.replaceState({}, '', url);
    }
    
    /**
     * Initialise la détection de position pour le profil
     */
    initLocationServices() {
        const locationToggle = document.querySelector('.location-toggle');
        
        if (locationToggle) {
            // Vérifier l'état initial
            const isEnabled = localStorage.getItem('location_enabled') === 'true';
            
            // Mettre à jour l'état visuel
            locationToggle.classList.toggle('enabled', isEnabled);
            
            // Gérer le changement d'état
            locationToggle.addEventListener('click', () => {
                const newState = !locationToggle.classList.contains('enabled');
                
                if (newState) {
                    // Demander la permission de géolocalisation
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            // Succès
                            (position) => {
                                locationToggle.classList.add('enabled');
                                localStorage.setItem('location_enabled', 'true');
                                this.showNotification('Localisation activée', 'success');
                            },
                            // Erreur
                            (error) => {
                                console.error('Erreur de géolocalisation:', error);
                                this.showNotification('Impossible d\'accéder à votre position', 'error');
                            }
                        );
                    } else {
                        this.showNotification('Géolocalisation non supportée par votre navigateur', 'error');
                    }
                } else {
                    // Désactiver la localisation
                    locationToggle.classList.remove('enabled');
                    localStorage.setItem('location_enabled', 'false');
                    this.showNotification('Localisation désactivée', 'info');
                }
            });
        }
    }
    
    /**
     * Initialise les gestes de balayage pour changer d'onglet sur mobile
     */
    initSwipeGestures() {
        if (window.innerWidth >= 768 || !this.tabContents.length) return;
        
        let startX, startY, distX, distY;
        const threshold = 100; // Distance minimum pour considérer comme un swipe
        const restraint = 100; // Contrainte de direction (horizontal vs vertical)
        
        // Obtenir la liste des IDs d'onglets dans l'ordre
        const tabIds = Array.from(this.tabs).map(tab => tab.getAttribute('data-target'));
        
        this.tabContents.forEach(content => {
            // Événements tactiles
            content.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            }, false);
            
            content.addEventListener('touchmove', (e) => {
                // Empêcher le défilement de la page pendant le swipe
                if (Math.abs(e.touches[0].clientX - startX) > 10) {
                    e.preventDefault();
                }
            }, false);
            
            content.addEventListener('touchend', (e) => {
                distX = e.changedTouches[0].clientX - startX;
                distY = e.changedTouches[0].clientY - startY;
                
                // Vérifier si c'est un swipe horizontal
                if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint) {
                    const currentIndex = tabIds.indexOf(content.id);
                    
                    // Swipe vers la gauche
                    if (distX < 0 && currentIndex < tabIds.length - 1) {
                        this.switchTab(tabIds[currentIndex + 1]);
                    }
                    // Swipe vers la droite
                    else if (distX > 0 && currentIndex > 0) {
                        this.switchTab(tabIds[currentIndex - 1]);
                    }
                }
            }, false);
        });
    }
    
    /**
     * Valide le formulaire de profil
     * @returns {boolean} True si le formulaire est valide, sinon False
     */
    validateProfileForm() {
        if (!this.profileForm) return false;
        
        let isValid = true;
        const inputs = this.profileForm.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            if (!this.validateInput(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Valide un champ de formulaire
     * @param {HTMLElement} input - Champ à valider
     * @returns {boolean} True si le champ est valide, sinon False
     */
    validateInput(input) {
        // Vider d'abord les erreurs
        this.hideInputError(input);
        
        // Vérifier si le champ est vide (si requis)
        if (input.hasAttribute('required') && !input.value.trim()) {
            this.showInputError(input, 'Ce champ est requis');
            return false;
        }
        
        // Validation spécifique selon le type
        if (input.type === 'email' && input.value.trim()) {
            // Validation basique de l'email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(input.value)) {
                this.showInputError(input, 'Format d\'email invalide');
                return false;
            }
            
            // Validation du domaine autorisé
            const domainPattern = /@(omnesintervenant\.com|ece\.fr|edu\.ece\.fr)$/;
            if (!domainPattern.test(input.value)) {
                this.showInputError(input, 'Vous devez utiliser une adresse institutionnelle');
                return false;
            }
        }
        
        // Validation du téléphone (si c'est un champ téléphone)
        if (input.name === 'telephone' && input.value.trim()) {
            const phonePattern = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
            if (!phonePattern.test(input.value)) {
                this.showInputError(input, 'Format de téléphone invalide');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valide le formulaire de mot de passe
     * @returns {boolean} True si le formulaire est valide, sinon False
     */
    validatePasswordForm() {
        if (!this.passwordForm) return false;
        
        let isValid = true;
        
        // Vérifier l'ancien mot de passe
        const oldPassword = this.passwordForm.querySelector('[name="ancien_mot_de_passe"]');
        if (!oldPassword.value.trim()) {
            this.showInputError(oldPassword, 'Veuillez saisir votre mot de passe actuel');
            isValid = false;
        }
        
        // Vérifier le nouveau mot de passe
        const newPassword = this.passwordForm.querySelector('[name="nouveau_mot_de_passe"]');
        if (!newPassword.value.trim()) {
            this.showInputError(newPassword, 'Veuillez saisir un nouveau mot de passe');
            isValid = false;
        } else if (newPassword.value.length < 8) {
            this.showInputError(newPassword, 'Le mot de passe doit contenir au moins 8 caractères');
            isValid = false;
        }
        
        // Vérifier la confirmation
        const confirmPassword = this.passwordForm.querySelector('[name="confirmer_mot_de_passe"]');
        if (!confirmPassword.value.trim()) {
            this.showInputError(confirmPassword, 'Veuillez confirmer votre mot de passe');
            isValid = false;
        } else if (confirmPassword.value !== newPassword.value) {
            this.showInputError(confirmPassword, 'Les mots de passe ne correspondent pas');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Affiche une erreur pour un champ
     * @param {HTMLElement} input - Champ concerné
     * @param {string} message - Message d'erreur
     */
    showInputError(input, message) {
        input.classList.add('invalid');
        
        // Créer ou mettre à jour le message d'erreur
        let errorMsg = input.nextElementSibling;
        if (!errorMsg || !errorMsg.classList.contains('error-message')) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            input.parentNode.insertBefore(errorMsg, input.nextSibling);
        }
        
        errorMsg.textContent = message;
    }
    
    /**
     * Masque l'erreur d'un champ
     * @param {HTMLElement} input - Champ concerné
     */
    hideInputError(input) {
        input.classList.remove('invalid');
        
        // Supprimer le message d'erreur
        const errorMsg = input.nextElementSibling;
        if (errorMsg && errorMsg.classList.contains('error-message')) {
            errorMsg.textContent = '';
        }
    }
    
    /**
     * Affiche les erreurs d'un formulaire
     * @param {HTMLElement} form - Formulaire
     * @param {Object} errors - Objet contenant les erreurs par champ
     */
    displayFormErrors(form, errors) {
        // Réinitialiser les erreurs précédentes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => this.hideInputError(input));
        
        // Afficher les nouvelles erreurs
        Object.entries(errors).forEach(([fieldName, message]) => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input) {
                this.showInputError(input, message);
            }
        });
    }
    
    /**
    * Met à jour l'indicateur de force du mot de passe
    * @param {HTMLElement} input - Champ de mot de passe
    */
   updatePasswordStrength(input) {
       // Vérifier si l'indicateur existe déjà
       let strengthIndicator = input.parentNode.querySelector('.password-strength');
       
       // Créer l'indicateur s'il n'existe pas
       if (!strengthIndicator) {
           strengthIndicator = document.createElement('div');
           strengthIndicator.className = 'password-strength';
           strengthIndicator.innerHTML = `
               <div class="strength-bar">
                   <div class="strength-level"></div>
               </div>
               <div class="strength-text"></div>
           `;
           
           // Insérer après le champ
           input.parentNode.insertBefore(strengthIndicator, input.nextSibling);
       }
       
       // Évaluer la force du mot de passe
       const password = input.value;
       const level = this.getPasswordStrength(password);
       
       // Mettre à jour l'indicateur visuel
       const strengthLevel = strengthIndicator.querySelector('.strength-level');
       const strengthText = strengthIndicator.querySelector('.strength-text');
       
       // Réinitialiser les classes
       strengthLevel.className = 'strength-level';
       
       // Appliquer les styles selon le niveau
       if (level === 0) {
           // Mot de passe vide ou trop court
           strengthLevel.style.width = '0%';
           strengthText.textContent = '';
           strengthIndicator.style.display = 'none';
       } else {
           strengthIndicator.style.display = 'block';
           
           if (level === 1) {
               strengthLevel.classList.add('weak');
               strengthLevel.style.width = '33%';
               strengthText.textContent = 'Faible';
           } else if (level === 2) {
               strengthLevel.classList.add('medium');
               strengthLevel.style.width = '66%';
               strengthText.textContent = 'Moyen';
           } else {
               strengthLevel.classList.add('strong');
               strengthLevel.style.width = '100%';
               strengthText.textContent = 'Fort';
           }
       }
   }
   
   /**
    * Évalue la force d'un mot de passe
    * @param {string} password - Mot de passe à évaluer
    * @returns {number} Niveau de force (0-3)
    */
   getPasswordStrength(password) {
       if (!password || password.length < 8) {
           return 0; // Trop court
       }
       
       let strength = 0;
       
       // Contient des lettres minuscules
       if (/[a-z]/.test(password)) strength++;
       
       // Contient des lettres majuscules
       if (/[A-Z]/.test(password)) strength++;
       
       // Contient des chiffres
       if (/[0-9]/.test(password)) strength++;
       
       // Contient des caractères spéciaux
       if (/[^a-zA-Z0-9]/.test(password)) strength++;
       
       // Longueur > 12
       if (password.length > 12) strength++;
       
       // Normaliser le score (1-3)
       return Math.min(3, Math.max(1, Math.floor(strength / 2)));
   }
   
   /**
    * Affiche un indicateur de chargement sur un élément
    * @param {HTMLElement} element - Élément à modifer
    */
   showLoading(element) {
       // Ajouter une classe de chargement
       element.classList.add('loading');
       
       // Rechercher le bouton de soumission
       const submitButton = element.querySelector('button[type="submit"]');
       
       if (submitButton) {
           // Sauvegarder le texte original du bouton
           submitButton.dataset.originalText = submitButton.innerHTML;
           
           // Remplacer par un spinner
           submitButton.innerHTML = '<span class="spinner"></span> Chargement...';
           submitButton.disabled = true;
       }
   }
   
   /**
    * Masque l'indicateur de chargement
    * @param {HTMLElement} element - Élément à modifier
    */
   hideLoading(element) {
       // Retirer la classe de chargement
       element.classList.remove('loading');
       
       // Restaurer le bouton de soumission
       const submitButton = element.querySelector('button[type="submit"]');
       
       if (submitButton && submitButton.dataset.originalText) {
           submitButton.innerHTML = submitButton.dataset.originalText;
           submitButton.disabled = false;
       }
   }
   
   /**
    * Met à jour l'affichage du profil après modification
    * @param {string} prenom - Prénom de l'utilisateur
    * @param {string} nom - Nom de l'utilisateur
    * @param {string} email - Email de l'utilisateur
    */
   updateProfileDisplay(prenom, nom, email) {
       // Mettre à jour le nom affiché
       const nomAffiche = document.getElementById('nom-affiche');
       if (nomAffiche) {
           nomAffiche.textContent = `${prenom} ${nom}`;
       }
       
       // Mettre à jour l'email affiché
       const emailAffiche = document.getElementById('email-affiche');
       if (emailAffiche) {
           emailAffiche.textContent = email;
       }
       
       // Mettre à jour le nom dans l'en-tête (si présent)
       const headerName = document.querySelector('.header-username');
       if (headerName) {
           headerName.textContent = `${prenom} ${nom}`;
       }
   }
   
   /**
    * Affiche une notification à l'utilisateur
    * @param {string} message - Message à afficher
    * @param {string} type - Type de notification (success, error, info)
    */
   showNotification(message, type = 'info') {
       // Vérifier si une notification existe déjà
       let notification = document.querySelector('.notification');
       
       if (!notification) {
           // Créer la notification
           notification = document.createElement('div');
           notification.className = 'notification';
           document.body.appendChild(notification);
       }
       
       // Réinitialiser les classes
       notification.className = 'notification';
       notification.classList.add(type);
       
       // Définir le contenu
       const iconMap = {
           'success': 'check_circle',
           'error': 'error',
           'info': 'info'
       };
       
       notification.innerHTML = `
           <span class="material-icons">${iconMap[type] || 'info'}</span>
           <span class="message">${message}</span>
       `;
       
       // Afficher avec animation
       notification.classList.add('show');
       
       // Masquer après un délai
       setTimeout(() => {
           notification.classList.remove('show');
           
           // Supprimer l'élément après l'animation
           setTimeout(() => {
               if (notification.parentNode) {
                   notification.parentNode.removeChild(notification);
               }
           }, 300);
       }, 3000);
   }
}
