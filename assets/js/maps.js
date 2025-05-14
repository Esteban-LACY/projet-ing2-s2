/**
 * Script pour l'intégration de Google Maps
 */

/**
 * Initialise la carte de recherche de logements
 */
function initSearchMap() {
    const mapElement = document.getElementById('search-map');
    
    if (!mapElement) {
        return;
    }
    
    // Position par défaut (France)
    const defaultPosition = { lat: 46.2276, lng: 2.2137 };
    
    const map = new google.maps.Map(mapElement, {
        center: defaultPosition,
        zoom: 5,
        styles: getMapStyles(),
        mapTypeControl: false,
        fullscreenControl: false,
        streetViewControl: false
    });
    
    // Récupérer les logements depuis le data attribute
    const logementsData = mapElement.getAttribute('data-logements');
    
    if (!logementsData) {
        return;
    }
    
    const logements = JSON.parse(logementsData);
    const bounds = new google.maps.LatLngBounds();
    const infoWindow = new google.maps.InfoWindow();
    
    // Ajouter des marqueurs pour chaque logement
    logements.forEach(logement => {
        if (logement.latitude && logement.longitude) {
            const position = { lat: parseFloat(logement.latitude), lng: parseFloat(logement.longitude) };
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: logement.titre,
                animation: google.maps.Animation.DROP,
                icon: {
                    url: 'assets/img/logos/marker.png',
                    scaledSize: new google.maps.Size(32, 32)
                }
            });
            
            // Contenu de l'infoWindow
            const content = `
                <div class="p-2">
                    <h3 class="font-bold text-base">${logement.titre}</h3>
                    <p class="text-sm text-gray-600">${logement.ville}</p>
                    <p class="text-sm font-medium my-1">${logement.prix} € / nuit</p>
                    <a href="${logement.url}" class="text-sm text-blue-500 hover:underline">Voir le logement</a>
                </div>
            `;
            
            // Ajouter un événement click sur le marqueur
            marker.addListener('click', () => {
                infoWindow.setContent(content);
                infoWindow.open(map, marker);
            });
            
            // Étendre les limites de la carte pour inclure ce marqueur
            bounds.extend(position);
        }
    });
    
    // Ajuster la carte pour afficher tous les marqueurs
    if (logements.length > 0) {
        map.fitBounds(bounds);
        
        // Limiter le zoom
        google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
            if (map.getZoom() > 15) {
                map.setZoom(15);
            }
        });
    }
}

/**
 * Initialise la carte pour la page de détail d'un logement
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @param {string} title - Titre du logement
 */
function initDetailMap(lat, lng, title) {
    const mapElement = document.getElementById('map');
    
    if (!mapElement) {
        return;
    }
    
    const position = { lat: parseFloat(lat), lng: parseFloat(lng) };
    
    const map = new google.maps.Map(mapElement, {
        center: position,
        zoom: 15,
        styles: getMapStyles(),
        mapTypeControl: false,
        fullscreenControl: false,
        streetViewControl: true
    });
    
    const marker = new google.maps.Marker({
        position: position,
        map: map,
        title: title,
        animation: google.maps.Animation.DROP,
        icon: {
            url: 'assets/img/logos/marker.png',
            scaledSize: new google.maps.Size(32, 32)
        }
    });
    
    // Ajouter un cercle autour du marqueur pour indiquer la zone approximative
    const circle = new google.maps.Circle({
        map: map,
        radius: 150, // Rayon en mètres
        fillColor: '#3B82F6',
        fillOpacity: 0.2,
        strokeColor: '#3B82F6',
        strokeOpacity: 0.5,
        strokeWeight: 1,
        center: position
    });
}

/**
 * Initialise la carte pour la page de publication de logement
 */
function initPublishMap() {
    const mapElement = document.getElementById('publish-map');
    
    if (!mapElement) {
        return;
    }
    
    // Position par défaut (France)
    const defaultPosition = { lat: 46.2276, lng: 2.2137 };
    
    const map = new google.maps.Map(mapElement, {
        center: defaultPosition,
        zoom: 5,
        styles: getMapStyles(),
        mapTypeControl: false
    });
    
    const marker = new google.maps.Marker({
        position: defaultPosition,
        map: map,
        draggable: true,
        animation: google.maps.Animation.DROP
    });
    
    // Champs de latitude et longitude
    const latitudeField = document.getElementById('latitude');
    const longitudeField = document.getElementById('longitude');
    const adresseField = document.getElementById('adresse');
    const villeField = document.getElementById('ville');
    const codePostalField = document.getElementById('code_postal');
    
    // Initialiser l'autocomplétion d'adresse
    if (adresseField) {
        const autocomplete = new google.maps.places.Autocomplete(adresseField, {
            types: ['address'],
            componentRestrictions: { country: 'fr' }
        });
        
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            
            if (!place.geometry) {
                return;
            }
            
            // Mettre à jour la carte
            map.setCenter(place.geometry.location);
            map.setZoom(15);
            marker.setPosition(place.geometry.location);
            
            // Mettre à jour les champs de latitude et longitude
            if (latitudeField && longitudeField) {
                latitudeField.value = place.geometry.location.lat();
                longitudeField.value = place.geometry.location.lng();
            }
            
            // Extraire les informations d'adresse
            if (place.address_components) {
                let ville = '';
                let codePostal = '';
                
                for (const component of place.address_components) {
                    if (component.types.includes('locality')) {
                        ville = component.long_name;
                    } else if (component.types.includes('postal_code')) {
                        codePostal = component.long_name;
                    }
                }
                
                if (villeField && ville) {
                    villeField.value = ville;
                }
                
                if (codePostalField && codePostal) {
                    codePostalField.value = codePostal;
                }
            }
        });
    }
    
    // Mettre à jour les coordonnées quand le marqueur est déplacé
    google.maps.event.addListener(marker, 'dragend', function() {
        if (latitudeField && longitudeField) {
            latitudeField.value = marker.getPosition().lat();
            longitudeField.value = marker.getPosition().lng();
        }
        
        // Faire une géocodage inverse pour mettre à jour l'adresse
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: marker.getPosition() }, function(results, status) {
            if (status === 'OK' && results[0]) {
                if (adresseField) {
                    adresseField.value = results[0].formatted_address;
                }
                
                // Extraire les informations d'adresse
                for (const component of results[0].address_components) {
                    if (component.types.includes('locality') && villeField) {
                        villeField.value = component.long_name;
                    } else if (component.types.includes('postal_code') && codePostalField) {
                        codePostalField.value = component.long_name;
                    }
                }
            }
        });
    });
    
    // Si on a déjà des coordonnées (édition d'un logement existant), mettre à jour la carte
    if (latitudeField && longitudeField && 
        latitudeField.value && longitudeField.value && 
        latitudeField.value !== '0' && longitudeField.value !== '0') {
        
        const position = { 
            lat: parseFloat(latitudeField.value), 
            lng: parseFloat(longitudeField.value) 
        };
        
        map.setCenter(position);
        map.setZoom(15);
        marker.setPosition(position);
    }
}

/**
 * Géocode une adresse et centre la carte sur cette position
 * @param {string} address - Adresse à géocoder
 * @param {object} map - Instance de la carte Google Maps
 * @param {object} marker - Instance du marqueur
 * @param {function} callback - Fonction de rappel après géocodage
 */
function geocodeAddress(address, map, marker, callback) {
    const geocoder = new google.maps.Geocoder();
    
    geocoder.geocode({ address: address }, function(results, status) {
        if (status === 'OK' && results[0]) {
            map.setCenter(results[0].geometry.location);
            map.setZoom(15);
            marker.setPosition(results[0].geometry.location);
            
            if (typeof callback === 'function') {
                callback(results[0]);
            }
        }
    });
}

/**
 * Retourne les styles pour les cartes Google Maps
 * @returns {array} Styles pour Google Maps
 */
function getMapStyles() {
    return [
        {
            "featureType": "administrative",
            "elementType": "labels.text.fill",
            "stylers": [
                {
                    "color": "#444444"
                }
            ]
        },
        {
            "featureType": "landscape",
            "elementType": "all",
            "stylers": [
                {
                    "color": "#f2f2f2"
                }
            ]
        },
        {
            "featureType": "poi",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "road",
            "elementType": "all",
            "stylers": [
                {
                    "saturation": -100
                },
                {
                    "lightness": 45
                }
            ]
        },
        {
            "featureType": "road.highway",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "simplified"
                }
            ]
        },
        {
            "featureType": "road.arterial",
            "elementType": "labels.icon",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "transit",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "water",
            "elementType": "all",
            "stylers": [
                {
                    "color": "#b4d4e1"
                },
                {
                    "visibility": "on"
                }
            ]
        }
    ];
}
