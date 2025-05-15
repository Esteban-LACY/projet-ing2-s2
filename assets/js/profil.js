/**
 * Gestion des fonctionnalités liées au profil utilisateur
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const profileForm = document.getElementById('profile-form');
    const passwordForm = document.getElementById('password-form');
    const uploadPhotoBtn = document.getElementById('upload-photo-btn');
    const photoInput = document.getElementById('photo-input');
    const previewImage = document.getElementById('profile-image-preview');
    const deleteAccountBtn = document.getElementById('delete-account-btn');
    
    // Initialiser les fonctionnalités du profil
    initProfileForm();
    initPasswordForm();
    initPhotoUpload();
    initDeleteAccount();
    initReservationsTabs();
    
    /**
     * Initialise le formulaire de modification du profil
     */
    function initProfileForm() {
        if (!profileForm) return;
        
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Valider les champs
            if (!validateProfileForm()) {
                return;
            }
            
            // Soumettre le formulaire via AJAX
            const formData = new FormData(profileForm);
            
            fetch('controllers/utilisateur.php?action=modifier_profil', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Profil mis à jour avec succès', 'success');
                        
                        // Mettre à jour les informations affichées
                        const nomAffiche = document.getElementById('nom-affiche');
                        const emailAffiche = document.getElementById('email-affiche');
                        
                        if (nomAffiche) {
                            nomAffiche.textContent = formData.get('prenom') + ' ' + formData.get('nom');
                        }
                        
                        if (emailAffiche) {
                            emailAffiche.textContent = formData.get('email');
                        }
                    } else {
                        showNotification(data.message || 'Une erreur est survenue', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la mise à jour du profil:', error);
                    showNotification('Une erreur est survenue', 'error');
                });
        });
    }
    
    /**
     * Valide le formulaire de profil
     * @returns {boolean} - Formulaire valide ou non
     */
    function validateProfileForm() {
        if (!profileForm) return false;
        
        let isValid = true;
        const requiredFields = profileForm.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
                
                // Afficher un message d'erreur
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
        
        // Valider le format de l'email
        const emailField = profileForm.querySelector('[name="email"]');
        if (emailField && emailField.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailField.value.trim())) {
                isValid = false;
                emailField.classList.add('border-red-500');
                
                let errorMsg = emailField.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-msg')) {
                    errorMsg = document.createElement('p');
                    errorMsg.classList.add('error-msg', 'text-red-500', 'text-sm', 'mt-1');
                    emailField.parentNode.insertBefore(errorMsg, emailField.nextSibling);
                }
                errorMsg.textContent = 'Format d\'email invalide';
            }
        }
        
        // Vérifier que l'email est une adresse institutionnelle
        if (emailField && emailField.value.trim() && isValid) {
            const email = emailField.value.trim();
            const domaines = ['omnesintervenant.com', 'ece.fr', 'edu.ece.fr'];
            let domaineValide = false;
            
            for (const domaine of domaines) {
                if (email.endsWith('@' + domaine)) {
                    domaineValide = true;
                    break;
                }
            }
            
            if (!domaineValide) {
                isValid = false;
                emailField.classList.add('border-red-500');
                
                let errorMsg = emailField.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-msg')) {
                    errorMsg = document.createElement('p');
                    errorMsg.classList.add('error-msg', 'text-red-500', 'text-sm', 'mt-1');
                    emailField.parentNode.insertBefore(errorMsg, emailField.nextSibling);
                }
                errorMsg.textContent = 'Vous devez utiliser une adresse email institutionnelle';
            }
        }
        
        // Valider le format du téléphone
        const telField = profileForm.querySelector('[name="telephone"]');
        if (telField && telField.value.trim()) {
            const telRegex = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
            if (!telRegex.test(telField.value.trim())) {
                isValid = false;
                telField.classList.add('border-red-500');
                
                let errorMsg = telField.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-msg')) {
                    errorMsg = document.createElement('p');
                    errorMsg.classList.add('error-msg', 'text-red-500', 'text-sm', 'mt-1');
                    telField.parentNode.insertBefore(errorMsg, telField.nextSibling);
                }
                errorMsg.textContent = 'Format de téléphone invalide';
            }
        }
        
        return isValid;
    }
    
    /**
     * Initialise le formulaire de changement de mot de passe
     */
    function initPasswordForm() {
        if (!passwordForm) return;
        
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Valider les mots de passe
            if (!validatePasswordForm()) {
                return;
            }
            
            // Soumettre le formulaire via AJAX
            const formData = new FormData(passwordForm);
            
            fetch('controllers/utilisateur.php?action=modifier_mot_de_passe', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Mot de passe modifié avec succès', 'success');
                        passwordForm.reset();
                    } else {
                        showNotification(data.message || 'Une erreur est survenue', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la modification du mot de passe:', error);
                    showNotification('Une erreur est survenue', 'error');
                });
        });
    }
    
    /**
     * Valide le formulaire de changement de mot de passe
     * @returns {boolean} - Formulaire valide ou non
     */
    function validatePasswordForm() {
        if (!passwordForm) return false;
        
        let isValid = true;
        
        // Vérifier que tous les champs sont remplis
        const oldPassword = passwordForm.querySelector('[name="ancien_mot_de_passe"]');
        const newPassword = passwordForm.querySelector('[name="nouveau_mot_de_passe"]');
        const confirmPassword = passwordForm.querySelector('[name="confirmer_mot_de_passe"]');
        
        if (!oldPassword.value.trim()) {
            showFieldError(oldPassword, 'Le mot de passe actuel est requis');
            isValid = false;
        } else {
            clearFieldError(oldPassword);
        }
        
        if (!newPassword.value.trim()) {
            showFieldError(newPassword, 'Le nouveau mot de passe est requis');
            isValid = false;
        } else if (newPassword.value.length < 8) {
            showFieldError(newPassword, 'Le mot de passe doit contenir au moins 8 caractères');
            isValid = false;
        } else {
            clearFieldError(newPassword);
        }
        
        if (!confirmPassword.value.trim()) {
            showFieldError(confirmPassword, 'La confirmation du mot de passe est requise');
            isValid = false;
        } else if (confirmPassword.value !== newPassword.value) {
            showFieldError(confirmPassword, 'Les mots de passe ne correspondent pas');
            isValid = false;
        } else {
            clearFieldError(confirmPassword);
        }
        
        return isValid;
    }
    
    /**
     * Affiche une erreur pour un champ
     * @param {HTMLElement} field - Champ avec erreur
     * @param {string} message - Message d'erreur
     */
    function showFieldError(field, message) {
        field.classList.add('border-red-500');
        
        let errorMsg = field.nextElementSibling;
        if (!errorMsg || !errorMsg.classList.contains('error-msg')) {
            errorMsg = document.createElement('p');
            errorMsg.classList.add('error-msg', 'text-red-500', 'text-sm', 'mt-1');
            field.parentNode.insertBefore(errorMsg, field.nextSibling);
        }
        errorMsg.textContent = message;
    }
    
    /**
     * Efface une erreur pour un champ
     * @param {HTMLElement} field - Champ à nettoyer
     */
    function clearFieldError(field) {
        field.classList.remove('border-red-500');
        
        const errorMsg = field.nextElementSibling;
        if (errorMsg && errorMsg.classList.contains('error-msg')) {
            errorMsg.textContent = '';
        }
    }
    
    /**
     * Initialise l'upload de photo de profil
     */
    function initPhotoUpload() {
        if (!uploadPhotoBtn || !photoInput || !previewImage) return;
        
        // Clic sur le bouton d'upload
        uploadPhotoBtn.addEventListener('click', function() {
            photoInput.click();
        });
        
        // Changement de fichier
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Vérifier le type de fichier
                const fileTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!fileTypes.includes(file.type)) {
                    showNotification('Le fichier doit être une image (JPEG, PNG, GIF)', 'error');
                    return;
                }
                
                // Vérifier la taille (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('L\'image ne doit pas dépasser 2MB', 'error');
                    return;
                }
                
                // Prévisualiser l'image
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                // Upload automatique
                uploadProfilePhoto(file);
            }
        });
    }
    
    /**
     * Upload la photo de profil
     * @param {File} file - Fichier image à uploader
     */
    function uploadProfilePhoto(file) {
        const formData = new FormData();
        formData.append('photo', file);
        
        fetch('controllers/utilisateur.php?action=upload_photo', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Photo de profil mise à jour', 'success');
                } else {
                    showNotification(data.message || 'Une erreur est survenue', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'upload de la photo:', error);
                showNotification('Une erreur est survenue', 'error');
            });
    }
    
    /**
     * Initialise la suppression de compte
     */
    function initDeleteAccount() {
        if (!deleteAccountBtn) return;
        
        deleteAccountBtn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
                fetch('controllers/utilisateur.php?action=supprimer_compte', {
                    method: 'POST'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Compte supprimé avec succès', 'success');
                            setTimeout(() => {
                                window.location.href = 'index.php';
                            }, 2000);
                        } else {
                            showNotification(data.message || 'Une erreur est survenue', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors de la suppression du compte:', error);
                        showNotification('Une erreur est survenue', 'error');
                    });
            }
        });
    }
    
    /**
     * Initialise les onglets pour les réservations
     */
    function initReservationsTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        if (tabButtons.length === 0 || tabContents.length === 0) return;
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Désactiver tous les onglets
                tabButtons.forEach(btn => btn.classList.remove('bg-black', 'text-white'));
                tabButtons.forEach(btn => btn.classList.add('bg-gray-200', 'text-gray-700'));
                
                // Activer l'onglet cliqué
                this.classList.remove('bg-gray-200', 'text-gray-700');
                this.classList.add('bg-black', 'text-white');
                
                // Cacher tous les contenus
                tabContents.forEach(content => content.classList.add('hidden'));
                
                // Afficher le contenu correspondant
                const activeContent = document.getElementById(tabId);
                if (activeContent) {
                    activeContent.classList.remove('hidden');
                }
            });
        });
        
        // Activer le premier onglet par défaut
        if (tabButtons[0]) {
            tabButtons[0].click();
        }
    }
    
    /**
     * Affiche une notification
     * @param {string} message - Message à afficher
     * @param {string} type - Type de notification (success, error)
     */
    function showNotification(message, type = 'success') {
        // Vérifier si une notification existe déjà
        let notification = document.querySelector('.notification');
        
        if (!notification) {
            // Créer une nouvelle notification
            notification = document.createElement('div');
            notification.className = 'notification fixed top-4 right-4 p-4 rounded-md shadow-md transition-opacity duration-300 z-50';
            document.body.appendChild(notification);
        }
        
        // Définir le style en fonction du type
        if (type === 'success') {
            notification.className = notification.className.replace(/bg-\w+-\d+/g, '');
            notification.classList.add('bg-green-500', 'text-white');
        } else {
            notification.className = notification.className.replace(/bg-\w+-\d+/g, '');
            notification.classList.add('bg-red-500', 'text-white');
        }
        
        // Mettre à jour le message
        notification.textContent = message;
        notification.style.opacity = '1';
        
        // Cacher après 3 secondes
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
});
