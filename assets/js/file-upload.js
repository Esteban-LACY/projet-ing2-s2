/**
 * OmnesBnB - Gestion des téléchargements de fichiers
 * Gère les chargements de photos de profil et de photos de logements
 */

// Définir le module comme IIFE pour éviter les conflits de portée
const FileUploader = (function() {
    // Configuration privée
    const config = {
        previewClass: 'file-preview',
        previewImageClass: 'preview-image',
        previewContainerClass: 'preview-container',
        deleteButtonClass: 'delete-image',
        mainPhotoButtonClass: 'main-photo',
        dropzoneClass: 'dropzone',
        dropzoneActiveClass: 'dropzone-active',
        fileListClass: 'file-list',
        fileItemClass: 'file-item',
        maxFileSize: 5, // Mo
        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        fileErrorClass: 'file-error',
        validationErrorClass: 'validation-error',
        loadingClass: 'loading'
    };
    
    /**
     * Initialise le gestionnaire de téléchargement
     */
    function initialize() {
        // Initialiser les champs de téléchargement de photo de profil
        initializeProfileUpload();
        
        // Initialiser les champs de téléchargement de photos de logement
        initializeLogementUpload();
    }
    
    /**
     * Initialise le téléchargement de photos de profil
     */
    function initializeProfileUpload() {
        const profileInput = document.getElementById('photo_profil');
        
        if (!profileInput) return;
        
        // Trouver ou créer le conteneur de prévisualisation
        let previewContainer = document.querySelector(`.${config.previewContainerClass}`);
        
        if (!previewContainer) {
            previewContainer = document.createElement('div');
            previewContainer.classList.add(config.previewContainerClass);
            profileInput.parentNode.insertBefore(previewContainer, profileInput.nextSibling);
        }
        
        // Gérer le changement de fichier
        profileInput.addEventListener('change', function(e) {
            const files = e.target.files;
            
            // Vider le conteneur de prévisualisation
            previewContainer.innerHTML = '';
            
            if (files.length === 0) return;
            
            const file = files[0];
            
            // Valider le fichier
            if (!validateFile(file)) {
                showFileError(profileInput, 'Le fichier doit être une image (JPG, PNG, GIF, WEBP) de 5 Mo maximum.');
                return;
            }
            
            // Créer la prévisualisation
            const previewWrapper = document.createElement('div');
            previewWrapper.classList.add(config.previewClass);
            
            const img = document.createElement('img');
            img.classList.add(config.previewImageClass);
            img.src = URL.createObjectURL(file);
            img.onload = function() {
                URL.revokeObjectURL(this.src);
            };
            
            previewWrapper.appendChild(img);
            previewContainer.appendChild(previewWrapper);
            
            // Supprimer les erreurs
            clearFileError(profileInput);
        });
    }
    
    /**
     * Initialise le téléchargement de photos de logement
     */
    function initializeLogementUpload() {
        const logementInput = document.getElementById('photos_logement');
        
        if (!logementInput) return;
        
        // Configuration spécifique aux logements
        const multiple = logementInput.hasAttribute('multiple');
        const maxFiles = parseInt(logementInput.getAttribute('data-max-files') || '5');
        
        // Trouver ou créer le conteneur de prévisualisation
        let previewContainer = document.querySelector(`.${config.previewContainerClass}`);
        
        if (!previewContainer) {
            previewContainer = document.createElement('div');
            previewContainer.classList.add(config.previewContainerClass, 'grid', 'grid-cols-2', 'md:grid-cols-3', 'gap-4', 'mt-3');
            logementInput.parentNode.insertBefore(previewContainer, logementInput.nextSibling);
        }
        
        // Initialiser la dropzone si elle existe
        const dropzone = document.querySelector(`.${config.dropzoneClass}`);
        
        if (dropzone) {
            initializeDropzone(dropzone, logementInput, previewContainer, multiple, maxFiles);
        }
        
        // Gérer le changement de fichier
        logementInput.addEventListener('change', function(e) {
            const files = e.target.files;
            
            if (files.length === 0) return;
            
            // Vérifier le nombre de fichiers
            const currentFiles = previewContainer.querySelectorAll(`.${config.fileItemClass}`).length;
            
            if (multiple && currentFiles + files.length > maxFiles) {
                showFileError(logementInput, `Vous ne pouvez pas télécharger plus de ${maxFiles} photos.`);
                return;
            }
            
            // Traiter chaque fichier
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Valider le fichier
                if (!validateFile(file)) {
                    showFileError(logementInput, 'Les fichiers doivent être des images (JPG, PNG, GIF, WEBP) de 5 Mo maximum.');
                    continue;
                }
                
                // Créer la prévisualisation
                createFilePreview(file, previewContainer, multiple);
            }
            
            // Supprimer les erreurs
            clearFileError(logementInput);
            
            // Réinitialiser l'input pour permettre de télécharger le même fichier à nouveau
            if (multiple) {
                logementInput.value = '';
            }
        });
        
        // Initialiser les photos existantes
        initializeExistingPhotos(previewContainer);
    }
    
    /**
     * Initialise la dropzone pour le téléchargement de fichiers
     * @param {HTMLElement} dropzone - Élément dropzone
     * @param {HTMLElement} input - Champ d'entrée de fichier
     * @param {HTMLElement} previewContainer - Conteneur de prévisualisation
     * @param {boolean} multiple - Si plusieurs fichiers sont autorisés
     * @param {number} maxFiles - Nombre maximum de fichiers
     */
    function initializeDropzone(dropzone, input, previewContainer, multiple, maxFiles) {
        // Empêcher le comportement par défaut
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Gérer l'entrée et la sortie du glisser-déposer
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropzone.classList.add(config.dropzoneActiveClass);
        }
        
        function unhighlight() {
            dropzone.classList.remove(config.dropzoneActiveClass);
        }
        
        // Gérer le dépôt de fichiers
        dropzone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const files = e.dataTransfer.files;
            
            // Vérifier le nombre de fichiers
            const currentFiles = previewContainer.querySelectorAll(`.${config.fileItemClass}`).length;
            
            if (multiple && currentFiles + files.length > maxFiles) {
                showFileError(input, `Vous ne pouvez pas télécharger plus de ${maxFiles} photos.`);
                return;
            }
            
            // Traiter chaque fichier
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Valider le fichier
                if (!validateFile(file)) {
                    showFileError(input, 'Les fichiers doivent être des images (JPG, PNG, GIF, WEBP) de 5 Mo maximum.');
                    continue;
                }
                
                // Créer la prévisualisation
                createFilePreview(file, previewContainer, multiple);
                
                // Ajouter le fichier à l'input (pour la soumission du formulaire)
                if (multiple) {
                    // Pour les inputs multiples, il faut recréer un objet FileList
                    // Comme FileList est immuable, on utilise une technique de contournement
                    const dataTransfer = new DataTransfer();
                    
                    // Ajouter les fichiers existants
                    if (input.files) {
                        Array.from(input.files).forEach(f => dataTransfer.items.add(f));
                    }
                    
                    // Ajouter le nouveau fichier
                    dataTransfer.items.add(file);
                    
                    // Mettre à jour l'input
                    input.files = dataTransfer.files;
                } else {
                    // Pour les inputs simples, on peut simplement remplacer
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                    
                    // Arrêter après le premier fichier
                    break;
                }
            }
            
            // Supprimer les erreurs
            clearFileError(input);
        }
        
        // Gérer le clic sur la dropzone
        dropzone.addEventListener('click', function() {
            input.click();
        });
    }
    
    /**
     * Crée une prévisualisation pour un fichier
     * @param {File} file - Fichier à prévisualiser
     * @param {HTMLElement} container - Conteneur de prévisualisation
     * @param {boolean} multiple - Si plusieurs fichiers sont autorisés
     */
    function createFilePreview(file, container, multiple) {
        // Créer l'élément de prévisualisation
        const fileItem = document.createElement('div');
        fileItem.classList.add(config.fileItemClass, 'relative', 'rounded', 'overflow-hidden', 'border', 'border-gray-200', 'dark:border-gray-700');
        
        // Ajouter une classe de chargement
        fileItem.classList.add(config.loadingClass);
        
        // Créer l'image de prévisualisation
        const img = document.createElement('img');
        img.classList.add(config.previewImageClass, 'w-full', 'h-40', 'object-cover');
        
        // Charger l'image
        const reader = new FileReader();
        
        reader.onload = function(e) {
            img.src = e.target.result;
            fileItem.classList.remove(config.loadingClass);
        };
        
        reader.readAsDataURL(file);
        
        // Ajouter l'image au conteneur
        fileItem.appendChild(img);
        
        // Ajouter un bouton de suppression si multiple ou photo existante
        if (multiple) {
            const deleteButton = document.createElement('button');
            deleteButton.classList.add(config.deleteButtonClass, 'absolute', 'top-2', 'right-2', 'bg-red-500', 'text-white', 'rounded-full', 'p-1', 'hover:bg-red-600', 'focus:outline-none', 'focus:ring', 'focus:ring-red-300');
            deleteButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>';
            deleteButton.type = 'button';
            deleteButton.setAttribute('aria-label', 'Supprimer');
            
            // Gérer la suppression
            deleteButton.addEventListener('click', function() {
                fileItem.remove();
                
                // Mettre à jour l'input (pour la soumission du formulaire)
                updateInputFiles(file.name);
            });
            
            fileItem.appendChild(deleteButton);
            
            // Ajouter un bouton pour définir comme photo principale (seulement pour les logements)
            if (document.getElementById('photos_logement')) {
                const mainButton = document.createElement('button');
                mainButton.classList.add(config.mainPhotoButtonClass, 'absolute', 'top-2', 'left-2', 'bg-blue-500', 'text-white', 'rounded-full', 'p-1', 'hover:bg-blue-600', 'focus:outline-none', 'focus:ring', 'focus:ring-blue-300');
                mainButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>';
                mainButton.type = 'button';
                mainButton.setAttribute('aria-label', 'Définir comme photo principale');
                
                // Gérer la sélection de la photo principale
                mainButton.addEventListener('click', function() {
                    // Supprimer la classe de photo principale de toutes les photos
                    container.querySelectorAll(`.${config.mainPhotoButtonClass}`).forEach(btn => {
                        btn.classList.remove('bg-green-500', 'hover:bg-green-600');
                        btn.classList.add('bg-blue-500', 'hover:bg-blue-600');
                    });
                    
                    // Ajouter la classe à cette photo
                    mainButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                    mainButton.classList.add('bg-green-500', 'hover:bg-green-600');
                    
                    // Mettre à jour le champ caché
                    const mainPhotoInput = document.getElementById('photo_principale');
                    if (mainPhotoInput) {
                        mainPhotoInput.value = file.name;
                    }
                });
                
                fileItem.appendChild(mainButton);
            }
        }
        
        // Ajouter l'élément au conteneur
        container.appendChild(fileItem);
    }
    
    /**
     * Initialise les photos existantes
     * @param {HTMLElement} container - Conteneur de prévisualisation
     */
    function initializeExistingPhotos(container) {
        const existingPhotosInput = document.getElementById('existing_photos');
        
        if (!existingPhotosInput) return;
        
        try {
            const existingPhotos = JSON.parse(existingPhotosInput.value);
            
            if (!Array.isArray(existingPhotos) || existingPhotos.length === 0) return;
            
            // Créer une prévisualisation pour chaque photo existante
            existingPhotos.forEach(photo => {
                // Créer l'élément de prévisualisation
                const fileItem = document.createElement('div');
                fileItem.classList.add(config.fileItemClass, 'relative', 'rounded', 'overflow-hidden', 'border', 'border-gray-200', 'dark:border-gray-700');
                fileItem.setAttribute('data-photo-id', photo.id);
                
                // Créer l'image de prévisualisation
                const img = document.createElement('img');
                img.classList.add(config.previewImageClass, 'w-full', 'h-40', 'object-cover');
                img.src = photo.url;
                
                // Ajouter l'image au conteneur
                fileItem.appendChild(img);
                
                // Ajouter un bouton de suppression
                const deleteButton = document.createElement('button');
                deleteButton.classList.add(config.deleteButtonClass, 'absolute', 'top-2', 'right-2', 'bg-red-500', 'text-white', 'rounded-full', 'p-1', 'hover:bg-red-600', 'focus:outline-none', 'focus:ring', 'focus:ring-red-300');
                deleteButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>';
                deleteButton.type = 'button';
                deleteButton.setAttribute('aria-label', 'Supprimer');
                
                // Gérer la suppression
                deleteButton.addEventListener('click', function() {
                    // Ajouter l'ID de la photo à la liste des photos à supprimer
                    const deletedPhotosInput = document.getElementById('deleted_photos');
                    
                    if (deletedPhotosInput) {
                        let deletedPhotos = [];
                        
                        try {
                            deletedPhotos = JSON.parse(deletedPhotosInput.value);
                        } catch (e) {
                            deletedPhotos = [];
                        }
                        
                        deletedPhotos.push(photo.id);
                        deletedPhotosInput.value = JSON.stringify(deletedPhotos);
                    }
                    
                    // Supprimer l'élément
                    fileItem.remove();
                });
                
                fileItem.appendChild(deleteButton);
                
                // Ajouter un bouton pour définir comme photo principale (seulement pour les logements)
                if (document.getElementById('photos_logement')) {
                    const mainButton = document.createElement('button');
                    mainButton.classList.add(config.mainPhotoButtonClass, 'absolute', 'top-2', 'left-2', 'bg-blue-500', 'text-white', 'rounded-full', 'p-1', 'hover:bg-blue-600', 'focus:outline-none', 'focus:ring', 'focus:ring-blue-300');
                    mainButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>';
                    mainButton.type = 'button';
                    mainButton.setAttribute('aria-label', 'Définir comme photo principale');
                    
                    // Si c'est la photo principale, mettre en évidence
                    if (photo.principale) {
                        mainButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                        mainButton.classList.add('bg-green-500', 'hover:bg-green-600');
                    }
                    
                    // Gérer la sélection de la photo principale
                    mainButton.addEventListener('click', function() {
                        // Supprimer la classe de photo principale de toutes les photos
                        container.querySelectorAll(`.${config.mainPhotoButtonClass}`).forEach(btn => {
                            btn.classList.remove('bg-green-500', 'hover:bg-green-600');
                            btn.classList.add('bg-blue-500', 'hover:bg-blue-600');
                        });
                        
                        // Ajouter la classe à cette photo
                        mainButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                        mainButton.classList.add('bg-green-500', 'hover:bg-green-600');
                        
                        // Mettre à jour le champ caché
                        const mainPhotoInput = document.getElementById('photo_principale');
                        if (mainPhotoInput) {
                            mainPhotoInput.value = photo.id;
                        }
                    });
                    
                    fileItem.appendChild(mainButton);
                }
                
                // Ajouter l'élément au conteneur
                container.appendChild(fileItem);
            });
        } catch (e) {
            console.error('Erreur lors du chargement des photos existantes:', e);
        }
    }
    
    /**
     * Met à jour les fichiers de l'input après une suppression
     * @param {string} fileName - Nom du fichier à supprimer
     */
    function updateInputFiles(fileName) {
        const input = document.getElementById('photos_logement');
        
        if (!input || !input.files || input.files.length === 0) return;
        
        const dataTransfer = new DataTransfer();
        
        // Ajouter tous les fichiers sauf celui à supprimer
        Array.from(input.files).forEach(file => {
            if (file.name !== fileName) {
                dataTransfer.items.add(file);
            }
        });
        
        // Mettre à jour l'input
        input.files = dataTransfer.files;
    }
    
    /**
     * Valide un fichier
     * @param {File} file - Fichier à valider
     * @returns {boolean} - True si le fichier est valide
     */
    function validateFile(file) {
        // Vérifier le type
        if (!config.allowedTypes.includes(file.type)) {
            return false;
        }
        
        // Vérifier la taille
        if (file.size > config.maxFileSize * 1024 * 1024) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Affiche une erreur pour un champ de fichier
     * @param {HTMLElement} input - Champ d'entrée de fichier
     * @param {string} message - Message d'erreur
     */
    function showFileError(input, message) {
        // Supprimer les erreurs existantes
        clearFileError(input);
        
        // Créer l'élément d'erreur
        const errorElement = document.createElement('div');
        errorElement.classList.add(config.fileErrorClass, config.validationErrorClass, 'text-red-500', 'text-sm', 'mt-1');
        errorElement.textContent = message;
        
        // Ajouter l'erreur après l'input
        input.parentNode.insertBefore(errorElement, input.nextSibling);
    }
    
    /**
     * Supprime l'erreur d'un champ de fichier
     * @param {HTMLElement} input - Champ d'entrée de fichier
     */
    function clearFileError(input) {
        const errorElement = input.parentNode.querySelector(`.${config.fileErrorClass}`);
        
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    // API publique
    return {
        initialize: initialize,
        validateFile: validateFile
    };
})();

// Initialiser le gestionnaire de téléchargement au chargement de la page
document.addEventListener('DOMContentLoaded', FileUploader.initialize);

// Exposer l'API de FileUploader globalement pour utilisation dans d'autres scripts
window.FileUploader = FileUploader;
