/**
 * Intégration Google Maps pour OmnesBnB
 * Gère l'affichage des cartes et la géolocalisation
 */

class GoogleMapsIntegration {
    constructor() {
        // Initialisation des variables
        this.map = null;
        this.geocoder = null;
        this.markers = [];
        this.apiKey = null;
    }
    
    /**
     * Initialise Google Maps avec la clé API
     * @param {string} apiKey - Clé API Google Maps
     */
    init(apiKey) {
        this.apiKey = apiKey;
        this.loadGoogleMapsScript();
    }
    
    /**
     * Charge le script Google Maps de manière asynchrone
     */
    loadGoogleMapsScript() {
        if (!this.apiKey) {
            console.error('Clé API Google Maps manquante');
            return;
        }
        
        // Éviter de recharger le script s'il est déjà chargé
        if (window.google && window.google.maps) {
            this.onGoogleMapsLoaded();
            return;
        }
        
        // Créer la fonction de callback
        window.initGoogleMaps = this.onGoogleMapsLoaded.bind(this);
        
        // Charger le script
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&libraries=places&callback=initGoogleMaps`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
    
    /**
     * Callback appelé quand Google Maps est chargé
     */
    onGoogleMapsLoaded() {
        this.geocoder = new google.maps.Geocoder();
        this.initializeMaps();
    }
    
    /**
     * Initialise toutes les cartes sur la page
     */
    initializeMaps() {
        // Initialiser la carte sur la page de détails
        const detailMapElement = document.getElementById('detail-map');
        if (detailMapElement) {
            this.initDetailMap(detailMapElement);
        }
        
        // Initialiser la carte sur la page de recherche
        const searchMapElement = document.getElementById('search-map');
        if (searchMapElement) {
            this.initSearchMap(searchMapElement);
        }
        
        // Initialiser l'autocomplete pour les champs d'adresse
        const addressInputs = document.querySelectorAll('.address-autocomplete');
        addressInputs.forEach(input => {
            this.setupAddressAutocomplete(input);
        });
    }
    
    /**
     * Initialise la carte sur la page de détails d'un logement
     * @param {HTMLElement} mapElement - Élément DOM pour la carte
     */
    initDetailMap(mapElement) {
        const lat = parseFloat(mapElement.dataset.lat);
        const lng = parseFloat(mapElement.dataset.lng);
        
        if (isNaN(lat) || isNaN(lng)) {
            console.error('Coordonnées invalides pour la carte');
            return;
        }
        
        const position = { lat, lng };
        
        this.map = new google.maps.Map(mapElement, {
            center: position,
            zoom: 15,
            disableDefaultUI: true,
            zoomControl: true,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });
        
        // Ajouter un marqueur
        const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            icon: {
                url: '/assets/img/logos/marker.png',
                scaledSize: new google.maps.Size(40, 40)
            }
        });
        
        this.markers.push(marker);
    }
    
    /**
     * Initialise la carte sur la page de recherche
     * @param {HTMLElement} mapElement - Élément DOM pour la carte
     */
    initSearchMap(mapElement) {
        // Position par défaut (Paris)
        const defaultPosition = { lat: 48.856614, lng: 2.3522219 };
        
        this.map = new google.maps.Map(mapElement, {
            center: defaultPosition,
            zoom: 13,
            disableDefaultUI: true,
            zoomControl: true,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });
        
        // Récupérer les logements depuis l'attribut data
        const logements = JSON.parse(mapElement.dataset.logements || '[]');
        
        // Ajouter les marqueurs pour chaque logement
        logements.forEach(logement => {
            this.addPropertyMarker(logement);
        });
        
        // Ajuster la vue pour afficher tous les marqueurs
        if (this.markers.length > 0) {
            this.fitMapToMarkers();
        }
    }
    
    /**
     * Ajoute un marqueur pour un logement
     * @param {Object} logement - Données du logement
     */
    addPropertyMarker(logement) {
        const position = {
            lat: parseFloat(logement.latitude),
            lng: parseFloat(logement.longitude)
        };
        
        if (isNaN(position.lat) || isNaN(position.lng)) {
            console.error('Coordonnées invalides pour le logement', logement.id);
            return;
        }
        
        const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            title: logement.titre,
            icon: {
                url: '/assets/img/logos/marker.png',
                scaledSize: new google.maps.Size(40, 40)
            }
        });
        
        // Ajouter une infowindow au clic
        const infowindow = new google.maps.InfoWindow({
            content: `
                <div class="infowindow">
                    <h3 class="font-bold">${logement.titre}</h3>
                    <p>${logement.prix}€ / nuit</p>
                    <a href="/logement/details.php?id=${logement.id}" class="text-black underline">Voir détails</a>
                </div>
            `
        });
        
        marker.addListener('click', () => {
            infowindow.open(this.map, marker);
        });
        
        this.markers.push(marker);
    }
    
    /**
     * Ajuste la vue de la carte pour afficher tous les marqueurs
     */
    fitMapToMarkers() {
        if (!this.map || this.markers.length === 0) {
            return;
        }
        
        const bounds = new google.maps.LatLngBounds();
        
        this.markers.forEach(marker => {
            bounds.extend(marker.getPosition());
        });
        
        this.map.fitBounds(bounds);
        
        // Si un seul marqueur, zoom approprié
        if (this.markers.length === 1) {
            this.map.setZoom(15);
        }
    }
    
    /**
     * Configure l'autocomplete pour les champs d'adresse
     * @param {HTMLElement} input - Champ de saisie d'adresse
     */
    setupAddressAutocomplete(input) {
        if (!input || !google.maps.places) {
            return;
        }
        
        const autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['address'],
            componentRestrictions: { country: 'fr' } // Limiter à la France
        });
        
        // Récupérer les champs associés
        const latField = document.getElementById(input.dataset.latField);
        const lngField = document.getElementById(input.dataset.lngField);
        const villeField = document.getElementById(input.dataset.villeField);
        const codePostalField = document.getElementById(input.dataset.codePostalField);
        
        // Listener pour la sélection d'une adresse
        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            
            if (!place.geometry) {
                console.error('Aucune information géométrique pour cette adresse');
                return;
            }
            
            // Mise à jour des champs cachés
            if (latField) {
                latField.value = place.geometry.location.lat();
            }
            
            if (lngField) {
                lngField.value = place.geometry.location.lng();
            }
            
            // Extraction des composants de l'adresse
            if (villeField || codePostalField) {
                for (const component of place.address_components) {
                    const componentType = component.types[0];
                    
                    if (componentType === 'locality' && villeField) {
                        villeField.value = component.long_name;
                    } else if (componentType === 'postal_code' && codePostalField) {
                        codePostalField.value = component.long_name;
                    }
                }
            }
        });
    }
    
    /**
     * Géocode une adresse pour obtenir les coordonnées
     * @param {string} address - Adresse à géocoder
     * @returns {Promise} - Promise contenant les coordonnées
     */
    geocodeAddress(address) {
        return new Promise((resolve, reject) => {
            if (!this.geocoder) {
                reject(new Error('Geocoder non initialisé'));
                return;
            }
            
            this.geocoder.geocode({ address }, (results, status) => {
                if (status === 'OK' && results && results.length > 0) {
                    const location = results[0].geometry.location;
                    resolve({
                        lat: location.lat(),
                        lng: location.lng(),
                        formattedAddress: results[0].formatted_address
                    });
                } else {
                    reject(new Error(`Geocoding échoué: ${status}`));
                }
            });
        });
    }
}

// Export de l'instance pour utilisation dans d'autres fichiers
const googleMaps = new GoogleMapsIntegration();
window.googleMaps = googleMaps;
