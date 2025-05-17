/**
 * Intégration Google Maps pour OmnesBnB
 * Gère l'affichage des cartes et la géolocalisation
 */

class GoogleMapsManager {
    constructor() {
        // Initialisation des variables
        this.map = null;
        this.markers = [];
        this.infoWindow = null;
        this.geocoder = null;
        this.autocomplete = null;
        this.currentPosition = null;
        
        // Options par défaut de la carte
        this.defaultCenter = { lat: 48.856614, lng: 2.3522219 }; // Paris
        this.defaultZoom = 13;
        
        // Styles personnalisés pour la carte mobile
        this.mapStyles = [
            {
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'transit',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'road',
                elementType: 'labels.icon',
                stylers: [{ visibility: 'off' }]
            }
        ];
        
        // Élément qui contiendra la carte
        this.mapContainer = null;
    }
    
    /**
     * Initialise Google Maps
     * @param {string} apiKey - Clé API Google Maps
     * @param {string} containerId - ID du conteneur de la carte
     */
    init(apiKey, containerId = 'map') {
        // Vérifier si la carte est déjà chargée
        if (window.google && window.google.maps) {
            this.setupMap(containerId);
            return;
        }
        
        // Créer la fonction de callback pour l'API Google Maps
        window.initGoogleMaps = () => {
            this.setupMap(containerId);
        };
        
        // Ajouter l'API Google Maps au DOM
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initGoogleMaps`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
    
    /**
     * Configure la carte une fois l'API chargée
     * @param {string} containerId - ID du conteneur de la carte
     */
    setupMap(containerId) {
        this.mapContainer = document.getElementById(containerId);
        if (!this.mapContainer) return;
        
        // Initialiser les composants Google Maps
        this.geocoder = new google.maps.Geocoder();
        this.infoWindow = new google.maps.InfoWindow();
        
        // Créer la carte
        this.map = new google.maps.Map(this.mapContainer, {
            center: this.defaultCenter,
            zoom: this.defaultZoom,
            styles: this.mapStyles,
            zoomControl: true,
            mapTypeControl: false,
            scaleControl: true,
            streetViewControl: false,
            rotateControl: false,
            fullscreenControl: false,
            gestureHandling: 'greedy', // Pour permettre le zoom avec un doigt sur mobile
            disableDefaultUI: true
        });
        
        // Adapter la taille de la carte au redimensionnement de la fenêtre
        window.addEventListener('resize', () => this.resizeMap());
        
        // Tenter d'obtenir la position actuelle de l'utilisateur
        this.getCurrentLocation();
        
        // Initialiser les fonctionnalités spécifiques
        this.initMapFunctionalities();
    }
    
    /**
     * Initialise les fonctionnalités spécifiques de la carte
     */
    initMapFunctionalities() {
        // Déterminer le type de page et initialiser en conséquence
        if (document.body.classList.contains('search-page')) {
            this.initSearchMap();
        } else if (document.body.classList.contains('detail-page')) {
            this.initDetailMap();
        } else if (document.body.classList.contains('publish-page')) {
            this.initPublishMap();
        }
        
        // Initialiser les champs d'autocomplete d'adresse
        this.initAddressAutocomplete();
    }
    
    /**
     * Obtient la localisation actuelle de l'utilisateur
     */
    getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.currentPosition = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Centrer la carte sur la position de l'utilisateur
                    if (this.map && !document.body.classList.contains('detail-page')) {
                        this.map.setCenter(this.currentPosition);
                        
                        // Ajouter un marqueur pour la position de l'utilisateur
                        this.addUserLocationMarker();
                    }
                },
                (error) => {
                    console.log('Erreur de géolocalisation:', error);
                },
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        }
    }
    
    /**
     * Ajoute un marqueur pour la position de l'utilisateur
     */
    addUserLocationMarker() {
        if (!this.currentPosition) return;
        
        const userMarker = new google.maps.Marker({
            position: this.currentPosition,
            map: this.map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: '#4285F4',
                fillOpacity: 1,
                strokeColor: '#FFFFFF',
                strokeWeight: 2
            },
            title: 'Votre position',
            zIndex: 999
        });
        
        this.markers.push(userMarker);
        
        // Ajouter un cercle autour de la position
        const circle = new google.maps.Circle({
            map: this.map,
            radius: 300, // Rayon en mètres
            fillColor: '#4285F4',
            fillOpacity: 0.1,
            strokeColor: '#4285F4',
            strokeOpacity: 0.3,
            strokeWeight: 1
        });
        circle.bindTo('center', userMarker, 'position');
    }
    
    /**
     * Initialise la carte pour la page de recherche
     */
    initSearchMap() {
        // Récupérer les logements depuis l'attribut data du conteneur
        const logementsData = this.mapContainer.getAttribute('data-logements');
        
        if (logementsData) {
            try {
                const logements = JSON.parse(logementsData);
                
                // Ajouter un marqueur pour chaque logement
                logements.forEach(logement => {
                    this.addPropertyMarker(logement);
                });
                
                // Ajuster la vue pour afficher tous les marqueurs
                if (this.markers.length > 0) {
                    this.fitMapToMarkers();
                }
                
                // Associer les cartes de logements aux marqueurs
                this.linkCardsToMarkers(logements);
            } catch (e) {
                console.error('Erreur de parsing des logements:', e);
            }
        }
        
        // Ajouter le bouton "Ma position"
        this.addLocationButton();
    }
    
    /**
     * Initialise la carte pour la page de détail
     */
    initDetailMap() {
        // Récupérer les coordonnées du logement
        const lat = parseFloat(this.mapContainer.dataset.lat);
        const lng = parseFloat(this.mapContainer.dataset.lng);
        
        if (isNaN(lat) || isNaN(lng)) return;
        
        const position = { lat, lng };
        
        // Centrer la carte sur le logement
        this.map.setCenter(position);
        this.map.setZoom(15);
        
        // Ajouter un marqueur pour le logement
        const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            icon: {
                url: '/assets/img/marker.png',
                scaledSize: new google.maps.Size(32, 32)
            },
            animation: google.maps.Animation.DROP
        });
        
        this.markers.push(marker);
        
        // Ajouter le titre du logement comme info-bulle
        const titre = this.mapContainer.dataset.titre;
        if (titre) {
            marker.addListener('click', () => {
                this.infoWindow.setContent(`<div class="marker-info">${titre}</div>`);
                this.infoWindow.open(this.map, marker);
            });
        }
        
        // Ajouter le bouton "Itinéraire"
        this.addDirectionsButton(position);
    }
    
    /**
     * Initialise la carte pour la page de publication
     */
    initPublishMap() {
        // Centrer sur la position actuelle ou la position par défaut
        const center = this.currentPosition || this.defaultCenter;
        this.map.setCenter(center);
        
        // Ajouter un marqueur déplaçable
        const marker = new google.maps.Marker({
            position: center,
            map: this.map,
            draggable: true,
            animation: google.maps.Animation.DROP
        });
        
        this.markers.push(marker);
        
        // Mettre à jour les coordonnées lorsque le marqueur est déplacé
        marker.addListener('dragend', () => {
            const position = marker.getPosition();
            this.updateCoordinatesInputs(position.lat(), position.lng());
            this.reverseGeocode(position);
        });
        
        // Permettre de cliquer sur la carte pour déplacer le marqueur
        this.map.addListener('click', (event) => {
            marker.setPosition(event.latLng);
            this.updateCoordinatesInputs(event.latLng.lat(), event.latLng.lng());
            this.reverseGeocode(event.latLng);
        });
        
        // Ajouter le bouton "Ma position"
        this.addLocationButton();
    }
    
    /**
     * Initialise l'autocomplétion d'adresse
     */
    initAddressAutocomplete() {
        const addressInputs = document.querySelectorAll('.address-autocomplete');
        
        addressInputs.forEach(input => {
            // Créer l'autocomplete pour chaque champ d'adresse
            const autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['address'],
                componentRestrictions: { country: 'fr' }
            });
            
            // Adapter l'autocomplete au mobile
            autocomplete.setFields(['address_components', 'formatted_address', 'geometry']);
            
            // Récupérer les champs associés
            const latField = document.getElementById(input.dataset.latField);
            const lngField = document.getElementById(input.dataset.lngField);
            const villeField = document.getElementById(input.dataset.villeField);
            const codePostalField = document.getElementById(input.dataset.codePostalField);
            
            // Gérer la sélection d'une adresse
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                
                if (!place.geometry) {
                    input.placeholder = 'Rechercher une adresse';
                    return;
                }
                
                // Mettre à jour les coordonnées
                if (latField && lngField) {
                    latField.value = place.geometry.location.lat();
                    lngField.value = place.geometry.location.lng();
                }
                
                // Extraire les composants de l'adresse
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
                
                // Si on est sur la page de publication, mettre à jour le marqueur
                if (document.body.classList.contains('publish-page') && this.map) {
                    const position = place.geometry.location;
                    
                    // Centrer la carte sur l'adresse sélectionnée
                    this.map.setCenter(position);
                    this.map.setZoom(15);
                    
                    // Mettre à jour le marqueur
                    if (this.markers.length > 0) {
                        this.markers[0].setPosition(position);
                    } else {
                        const marker = new google.maps.Marker({
                            position: position,
                            map: this.map,
                            draggable: true
                        });
                        
                        this.markers.push(marker);
                    }
                }
            });
        });
    }
    
    /**
     * Ajoute un marqueur pour un logement
     * @param {Object} logement - Données du logement
     */
    addPropertyMarker(logement) {
        if (!logement.latitude || !logement.longitude) return;
        
        const position = {
            lat: parseFloat(logement.latitude),
            lng: parseFloat(logement.longitude)
        };
        
        // Créer le marqueur
        const marker = new google.maps.Marker({
            position: position,
            map: this.map,
            title: logement.titre,
            icon: {
                url: '/assets/img/marker.png',
                scaledSize: new google.maps.Size(32, 32)
            },
            id: logement.id
        });
        
        this.markers.push(marker);
        
        // Créer le contenu de l'info-bulle
        const contentString = `
            <div class="map-infowindow">
                <h3>${logement.titre}</h3>
                <p>${logement.prix}€ / nuit</p>
                <a href="/logement/details.php?id=${logement.id}" class="view-property">Voir détails</a>
            </div>
        `;
        
        // Ajouter l'info-bulle au marqueur
        marker.addListener('click', () => {
            this.infoWindow.setContent(contentString);
            this.infoWindow.open(this.map, marker);
            
            // Faire défiler jusqu'à la carte correspondante
            this.highlightPropertyCard(logement.id);
        });
    }
    
    /**
     * Met en surbrillance la carte de logement correspondant à un marqueur
     * @param {number} id - ID du logement
     */
    highlightPropertyCard(id) {
        // Supprimer la surbrillance de toutes les cartes
        const cards = document.querySelectorAll('.property-card');
        cards.forEach(card => card.classList.remove('highlight'));
        
        // Ajouter la surbrillance à la carte correspondante
        const targetCard = document.querySelector(`.property-card[data-id="${id}"]`);
        if (targetCard) {
            targetCard.classList.add('highlight');
            
            // Faire défiler jusqu'à la carte
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    /**
     * Associe les cartes de logements aux marqueurs
     * @param {Array} logements - Liste des logements
     */
    linkCardsToMarkers(logements) {
        const cards = document.querySelectorAll('.property-card');
        
        cards.forEach(card => {
            card.addEventListener('click', () => {
                const id = parseInt(card.dataset.id);
                const logement = logements.find(l => l.id === id);
                
                if (logement) {
                    // Centrer la carte sur le logement
                    const position = {
                        lat: parseFloat(logement.latitude),
                        lng: parseFloat(logement.longitude)
                    };
                    
                    this.map.setCenter(position);
                    this.map.setZoom(15);
                    
                    // Ouvrir l'info-bulle du marqueur correspondant
                    const marker = this.markers.find(m => m.id === id);
                    if (marker) {
                        google.maps.event.trigger(marker, 'click');
                    }
                }
            });
        });
    }
    
    /**
     * Ajoute un bouton pour centrer la carte sur la position de l'utilisateur
     */
    addLocationButton() {
        const locationButton = document.createElement('div');
        locationButton.className = 'location-button';
        locationButton.innerHTML = '<span class="material-icons">my_location</span>';
        
        this.map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(locationButton);
        
        locationButton.addEventListener('click', () => {
            // Si on a déjà la position, centrer la carte
            if (this.currentPosition) {
                this.map.setCenter(this.currentPosition);
                this.map.setZoom(15);
            } else {
                // Sinon, tenter d'obtenir la position
                this.getCurrentLocation();
            }
        });
    }
    
    /**
     * Ajoute un bouton pour obtenir l'itinéraire vers un logement
     * @param {Object} destination - Coordonnées de destination
     */
    addDirectionsButton(destination) {
        const directionsButton = document.createElement('div');
        directionsButton.className = 'directions-button';
        directionsButton.innerHTML = '<span class="material-icons">directions</span>';
        
        this.map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(directionsButton);
        
        directionsButton.addEventListener('click', () => {
            // Ouvrir Google Maps avec l'itinéraire
            const url = `https://www.google.com/maps/dir/?api=1&destination=${destination.lat},${destination.lng}`;
            window.open(url, '_blank');
        });
    }
    
    /**
     * Ajuste la vue de la carte pour afficher tous les marqueurs
     */
    fitMapToMarkers() {
        if (this.markers.length === 0) return;
        
        const bounds = new google.maps.LatLngBounds();
        
        this.markers.forEach(marker => {
            bounds.extend(marker.getPosition());
        });
        
        this.map.fitBounds(bounds);
        
        // Si un seul marqueur, définir un zoom approprié
        if (this.markers.length === 1) {
            this.map.setZoom(15);
        }
    }
    
    /**
     * Met à jour les champs d'entrée de coordonnées
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     */
    updateCoordinatesInputs(lat, lng) {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        
        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
    }
    
    /**
     * Effectue un géocodage inverse pour obtenir l'adresse à partir des coordonnées
     * @param {Object} position - Position (LatLng)
     */
    reverseGeocode(position) {
        if (!this.geocoder) return;
        
        this.geocoder.geocode({ location: position }, (results, status) => {
            if (status === 'OK' && results[0]) {
                const address = results[0].formatted_address;
                
                // Mettre à jour le champ d'adresse
                const addressInput = document.querySelector('.address-autocomplete');
                if (addressInput) {
                    addressInput.value = address;
                }
                
                // Extraire les composants de l'adresse
                const villeField = document.getElementById(addressInput?.dataset.villeField);
                const codePostalField = document.getElementById(addressInput?.dataset.codePostalField);
                
                if (villeField || codePostalField) {
                    for (const component of results[0].address_components) {
                        const componentType = component.types[0];
                        
                        if (componentType === 'locality' && villeField) {
                            villeField.value = component.long_name;
                        } else if (componentType === 'postal_code' && codePostalField) {
                            codePostalField.value = component.long_name;
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Redimensionne la carte lors du redimensionnement de la fenêtre
     */
    resizeMap() {
        if (this.map && this.mapContainer) {
            google.maps.event.trigger(this.map, 'resize');
            
            // Réajuster la vue si nécessaire
            if (this.markers.length > 1) {
                this.fitMapToMarkers();
            } else if (this.markers.length === 1) {
                this.map.setCenter(this.markers[0].getPosition());
            }
        }
    }
    
    /**
     * Calcule la distance entre deux points
     * @param {Object} start - Point de départ {lat, lng}
     * @param {Object} end - Point d'arrivée {lat, lng}
     * @return {number} Distance en kilomètres
     */
    calculateDistance(start, end) {
        if (!start || !end) return 0;
        
        // Conversion en radians
        const toRad = (value) => value * Math.PI / 180;
        
        // Formule de Haversine pour calculer la distance entre deux points
        const R = 6371; // Rayon de la Terre en km
        const dLat = toRad(end.lat - start.lat);
        const dLng = toRad(end.lng - start.lng);
        
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(toRad(start.lat)) * Math.cos(toRad(end.lat)) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c;
        
        return Math.round(distance * 10) / 10; // Arrondi à 1 décimale
    }
}

// Initialiser l'instance unique
const googleMaps = new GoogleMapsManager();

// Exposer l'instance au niveau global
window.googleMaps = googleMaps;

// Initialiser la carte lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    // Récupérer la clé API depuis l'attribut de la balise script ou un élément de données
    const apiKey = document.querySelector('meta[name="google-maps-key"]')?.getAttribute('content') || 
                  document.getElementById('google-maps-script')?.getAttribute('data-key');
    
    if (apiKey) {
        // Déterminer l'ID du conteneur de carte
        let mapContainerId = 'map';
        
        if (document.getElementById('search-map')) {
            mapContainerId = 'search-map';
        } else if (document.getElementById('detail-map')) {
            mapContainerId = 'detail-map';
        } else if (document.getElementById('publish-map')) {
            mapContainerId = 'publish-map';
        }
        
        // Initialiser Google Maps
        googleMaps.init(apiKey, mapContainerId);
    }
});
