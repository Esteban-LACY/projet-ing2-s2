/**
 * Script pour la gestion du profil utilisateur
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser l'upload de photo de profil
    initPhotoUpload();
    
    // Initialiser les onglets de profil
    initProfileTabs();
    
    // Initialiser la confirmation de suppression
    initDeleteConfirmation();
});

/**
 * Initialise l'upload de photo de profil
 */
function initPhotoUpload() {
    const photoInput = document.getElementById('photo_profil');
    const photoPreview = document.getElementById('photo-preview');
    
    if (!photoInput || !photoPreview) {
        return;
    }
    
    photoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                photoPreview.innerHTML = `
                    <div class="relative w-24 h-24 mx-auto">
                        <img src="${e.target.result}" alt="Prévisualisation" class="w-full h-full object-cover rounded-full">
                        <div class="absolute inset-0 bg-black bg-opacity-50 rounded-full flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                            <span class="text-white text-xs">Modifier</span>
                        </div>
                    </div>
                `;
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
}

/**
 * Initialise les onglets du profil
 */
function initProfileTabs() {
    const tabButtons = document.querySelectorAll('[data-tab]');
    const tabContents = document.querySelectorAll('[data-tab-content]');
    
    if (!tabButtons.length || !tabContents.length) {
        return;
    }
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            
            // Activer/désactiver les boutons
            tabButtons.forEach(btn => {
                btn.classList.remove('border-black', 'text-black', 'font-medium');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-black', 'text-black', 'font-medium');
            
            // Afficher/masquer les contenus
            tabContents.forEach(content => {
                if (content.getAttribute('data-tab-content') === tab) {
                    content.classList.remove('hidden');
                } else {
                    content.classList.add('hidden');
                }
            });
            
            // Stocker l'onglet actif dans localStorage
            localStorage.setItem('omnesbnb_profile_tab', tab);
        });
    });
    
    // Restaurer l'onglet actif
    const activeTab = localStorage.getItem('omnesbnb_profile_tab');
    
    if (activeTab) {
        const activeButton = document.querySelector(`[data-tab="${activeTab}"]`);
        
        if (activeButton) {
            activeButton.click();
        } else {
            // Activer le premier onglet par défaut
            tabButtons[0].click();
        }
    } else {
        // Activer le premier onglet par défaut
        tabButtons[0].click();
    }
}

/**
 * Initialise la confirmation de suppression
 */
function initDeleteConfirmation() {
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Êtes-vous sûr ?';
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Supprime un logement
 * @param {number} id - ID du logement
 * @param {Element} element - Élément HTML du logement
 */
function supprimerLogement(id, element) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce logement ?')) {
        return;
    }
    
    // Effectuer la requête AJAX
    fetch('supprimer-logement.php?id=' + id + '&ajax=1', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Supprimer l'élément du DOM
            element.remove();
            
            // Afficher une notification
            afficherNotification('Logement supprimé avec succès', 'success');
        } else {
            afficherNotification(data.message || 'Une erreur est survenue', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors de la suppression:', error);
        afficherNotification('Une erreur est survenue', 'error');
    });
}

/**
 * Annule une réservation
 * @param {number} id - ID de la réservation
 * @param {Element} element - Élément HTML de la réservation
 */
function annulerReservation(id, element) {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        return;
    }
    
    // Effectuer la requête AJAX
    fetch('annuler-reservation.php?id=' + id + '&ajax=1', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le statut de la réservation
            const statusElement = element.querySelector('.reservation-status');
            
            if (statusElement) {
                statusElement.textContent = 'Annulée';
                statusElement.classList.remove('bg-yellow-500', 'bg-green-500');
                statusElement.classList.add('bg-gray-500');
            }
            
            // Masquer le bouton d'annulation
            const cancelButton = element.querySelector('.cancel-button');
            
            if (cancelButton) {
                cancelButton.remove();
            }
            
            // Afficher une notification
            afficherNotification('Réservation annulée avec succès', 'success');
        } else {
            afficherNotification(data.message || 'Une erreur est survenue', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors de l\'annulation:', error);
        afficherNotification('Une erreur est survenue', 'error');
    });
}

/**
 * Affiche une notification
 * @param {string} message - Message à afficher
 * @param {string} type - Type de notification (success, error, warning, info)
 */
function afficherNotification(message, type = 'info') {
    const container = document.createElement('div');
    container.className = `fixed top-4 right-4 max-w-xs bg-white rounded-lg shadow-lg p-4 z-50 transform transition-transform duration-300 ease-in-out notification-${type}`;
    
    const colors = {
        success: 'border-l-4 border-green-500',
        error: 'border-l-4 border-red-500',
        warning: 'border-l-4 border-yellow-500',
        info: 'border-l-4 border-blue-500'
    };
    
    container.classList.add(colors[type] || colors.info);
    
    container.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-6 h-6 ${type === 'success' ? 'text-green-500' : type === 'error' ? 'text-red-500' : type === 'warning' ? 'text-yellow-500' : 'text-blue-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    ${type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' : 
                      type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>' : 
                      type === 'warning' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>' : 
                      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'}
                </svg>
                <p class="ml-3">${message}</p>
            </div>
            <button class="text-gray-400 hover:text-gray-500" onclick="this.parentNode.parentNode.remove()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(container);
    
    // Animation d'entrée
    setTimeout(() => {
        container.classList.add('translate-x-0');
    }, 10);
    
    // Disparition automatique
    setTimeout(() => {
        container.classList.add('translate-x-full');
        setTimeout(() => {
            container.remove();
        }, 300);
    }, 5000);
}
