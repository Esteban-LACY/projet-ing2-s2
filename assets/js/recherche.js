/**
 * Script pour la recherche de logements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les filtres de recherche
    initSearchFilters();
    
    // Initialiser la carte si présente
    if (typeof google !== 'undefined' && google.maps && document.getElementById('search-map')) {
        initSearchMap();
    }
});

/**
 * Initialise les filtres de recherche
 */
function initSearchFilters() {
    // Élément de formulaire de recherche
    const searchForm = document.getElementById('search-form');
    
    if (!searchForm) {
        return;
    }
    
    // Bouton de réinitialisation des filtres
    const resetButton = document.getElementById('reset-filters');
    
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Réinitialiser les champs de formulaire
            const inputs = searchForm.querySelectorAll('input:not([type="submit"]), select');
            
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
            
            // Soumettre le formulaire
            searchForm.submit();
        });
    }
    
    // Mise à jour du prix minimum et maximum
    const prixMinInput = document.getElementById('prix_min');
    const prixMaxInput = document.getElementById('prix_max');
    const prixMinLabel = document.getElementById('prix-min-label');
    const prixMaxLabel = document.getElementById('prix-max-label');
    
    if (prixMinInput && prixMinLabel) {
        prixMinInput.addEventListener('input', function() {
            prixMinLabel.textContent = this.value + ' €';
            
            // Mettre à jour le prix minimum du prix maximum
            if (prixMaxInput && parseInt(prixMaxInput.value) < parseInt(this.value)) {
                prixMaxInput.value = this.value;
                
                if (prixMaxLabel) {
                    prixMaxLabel.textContent = this.value + ' €';
                }
            }
        });
    }
    
    if (prixMaxInput && prixMaxLabel) {
        prixMaxInput.addEventListener('input', function() {
            prixMaxLabel.textContent = this.value + ' €';
            
            // Mettre à jour le prix maximum du prix minimum
            if (prixMinInput && parseInt(prixMinInput.value) > parseInt(this.value)) {
                prixMinInput.value = this.value;
                
                if (prixMinLabel) {
                    prixMinLabel.textContent = this.value + ' €';
                }
            }
        });
    }
    
    // Vue liste/carte
    const viewToggleButtons = document.querySelectorAll('[data-view]');
    const listView = document.getElementById('list-view');
    const mapView = document.getElementById('map-view');
    
    if (viewToggleButtons.length && listView && mapView) {
        viewToggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const view = this.getAttribute('data-view');
                
                // Activer/désactiver les boutons
                viewToggleButtons.forEach(btn => {
                    btn.classList.remove('bg-black', 'text-white');
                    btn.classList.add('bg-gray-100', 'text-gray-800');
                });
                
                this.classList.remove('bg-gray-100', 'text-gray-800');
                this.classList.add('bg-black', 'text-white');
                
                // Afficher/masquer les vues
                if (view === 'list') {
                    listView.classList.remove('hidden');
                    mapView.classList.add('hidden');
                } else {
                    listView.classList.add('hidden');
                    mapView.classList.remove('hidden');
                    
                    // Réinitialiser la carte
                    if (typeof google !== 'undefined' && google.maps) {
                        google.maps.event.trigger(window.searchMap, 'resize');
                    }
                }
            });
        });
    }
    
    // Formulaire de recherche avec soumission automatique
    const autoSubmitInputs = searchForm.querySelectorAll('.auto-submit');
    
    autoSubmitInputs.forEach(input => {
        input.addEventListener('change', function() {
            searchForm.submit();
        });
    });
}

/**
 * Affiche les résultats de recherche
 * @param {Array} resultats - Résultats de recherche
 * @param {string} container - ID du conteneur des résultats
 */
function afficherResultats(resultats, container) {
    const conteneur = document.getElementById(container);
    
    if (!conteneur) {
        return;
    }
    
    // Vider le conteneur
    conteneur.innerHTML = '';
    
    if (resultats.length === 0) {
        conteneur.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">Aucun résultat trouvé.</p></div>';
        return;
    }
    
    // Ajouter chaque résultat
    resultats.forEach(logement => {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-lg shadow-md overflow-hidden mb-4';
        
        const cardHtml = `
            <div class="flex flex-col sm:flex-row">
                <div class="w-full sm:w-1/3">
                    <img src="${logement.photo_url ? logement.photo_url : 'assets/img/placeholders/logement.jpg'}" 
                         alt="${logement.titre}" 
                         class="w-full h-48 sm:h-full object-cover">
                </div>
                <div class="w-full sm:w-2/3 p-4">
                    <h3 class="font-bold text-lg">${logement.titre}</h3>
                    <p class="text-gray-600 mb-2">${logement.ville}</p>
                    <p class="mb-2"><strong>${logement.prix} € / nuit</strong></p>
                    <p class="mb-4">
                        <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">
                            ${logement.type_logement === 'entier' ? 'Logement entier' : 
                              (logement.type_logement === 'collocation' ? 'Colocation' : 'Logement libéré')}
                        </span>
                        <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700">
                            ${logement.nb_places} place${logement.nb_places > 1 ? 's' : ''}
                        </span>
                    </p>
                    <div class="text-right">
                        <a href="logement.php?id=${logement.id}" class="text-black font-medium">
                            Voir plus &gt;
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        card.innerHTML = cardHtml;
        conteneur.appendChild(card);
    });
}

/**
 * Effectue une recherche avec AJAX
 * @param {Object} criteres - Critères de recherche
 * @param {Function} callback - Fonction de rappel
 */
function rechercheAjax(criteres, callback) {
    // Construire les paramètres de requête
    const params = new URLSearchParams();
    
    for (const key in criteres) {
        if (criteres[key]) {
            params.append(key, criteres[key]);
        }
    }
    
    // Ajouter le paramètre AJAX
    params.append('ajax', '1');
    
    // Effectuer la requête AJAX
    fetch('recherche.php?' + params.toString(), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la recherche:', error);
    });
}
