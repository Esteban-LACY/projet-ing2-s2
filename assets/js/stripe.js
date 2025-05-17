/**
 * Intégration Stripe pour OmnesBnB
 * Gère le processus de paiement de manière optimisée pour mobile
 */

class StripePaymentManager {
    constructor() {
        // Initialisation des variables
        this.stripe = null;
        this.elements = null;
        this.paymentElement = null;
        this.cardElement = null;
        this.paymentRequest = null;
        this.clientSecret = null;
        
        // Éléments du DOM
        this.form = null;
        this.submitButton = null;
        this.errorContainer = null;
        this.loadingOverlay = null;
        this.paymentSuccess = null;
        this.paymentFailure = null;
        
        // État du paiement
        this.isPaymentProcessing = false;
        
        // Configuration par défaut
        this.defaultTheme = {
            colorPrimary: '#000000',
            colorBackground: '#ffffff',
            colorText: '#32325d',
            colorDanger: '#ff4136',
            fontFamily: '"SF Pro Display", -apple-system, BlinkMacSystemFont, sans-serif',
            spacingUnit: '4px',
            borderRadius: '8px'
        };
    }
    
    /**
     * Initialise Stripe avec la clé publique
     * @param {string} publishableKey - Clé publique Stripe
     * @return {Promise} Promesse résolue lorsque Stripe est initialisé
     */
    async init(publishableKey) {
        if (!publishableKey) {
            console.error('Clé API Stripe manquante');
            return Promise.reject('Clé API Stripe manquante');
        }
        
        try {
            // Initialiser Stripe.js
            this.stripe = Stripe(publishableKey);
            
            // Vérifier si l'appareil prend en charge Apple Pay / Google Pay
            await this.checkWalletSupport();
            
            return Promise.resolve();
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de Stripe:', error);
            return Promise.reject(error);
        }
    }
    
    /**
     * Vérifie si l'appareil prend en charge Apple Pay / Google Pay
     * @return {Promise} Promesse résolue avec le résultat de la vérification
     */
    async checkWalletSupport() {
        if (!this.stripe) return Promise.resolve(false);
        
        try {
            const paymentRequest = this.stripe.paymentRequest({
                country: 'FR',
                currency: 'eur',
                total: { label: 'Vérification', amount: 0 },
                requestPayerName: true,
                requestPayerEmail: true
            });
            
            const result = await paymentRequest.canMakePayment();
            
            // Si Apple Pay ou Google Pay est pris en charge, conserver la référence
            if (result) {
                this.paymentRequest = paymentRequest;
                return true;
            }
            
            return false;
        } catch (error) {
            console.log('Paiement mobile non disponible:', error);
            return false;
        }
    }
    
    /**
     * Configure le formulaire de paiement
     * @param {string} clientSecret - Secret client pour la session de paiement
     * @param {string} formId - ID du formulaire de paiement
     * @param {Object} options - Options de configuration supplémentaires
     */
    setupPaymentForm(clientSecret, formId, options = {}) {
        // Vérifications de base
        if (!this.stripe || !clientSecret) {
            console.error('Stripe non initialisé ou client secret manquant');
            this.showErrorMessage('Configuration de paiement incomplète. Veuillez réessayer.');
            return;
        }
        
        this.clientSecret = clientSecret;
        
        // Récupérer les éléments du DOM
        this.form = document.getElementById(formId);
        if (!this.form) {
            console.error('Formulaire de paiement non trouvé');
            return;
        }
        
        this.submitButton = this.form.querySelector('#submit-payment');
        this.errorContainer = this.form.querySelector('#payment-error');
        this.loadingOverlay = document.getElementById('payment-loading');
        this.paymentSuccess = document.getElementById('payment-success');
        this.paymentFailure = document.getElementById('payment-failure');
        
        // Créer le conteneur pour les éléments Stripe s'il n'existe pas
        let paymentElementContainer = this.form.querySelector('#payment-element');
        if (!paymentElementContainer) {
            paymentElementContainer = document.createElement('div');
            paymentElementContainer.id = 'payment-element';
            this.form.insertBefore(paymentElementContainer, this.submitButton);
        }
        
        // Fusionner les options avec les options par défaut
        const elementOptions = {
            clientSecret,
            appearance: {
                theme: options.theme || 'stripe',
                variables: {
                    ...this.defaultTheme,
                    ...options.variables
                },
                rules: {
                    '.Input': {
                        border: 'none',
                        boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
                        padding: '12px'
                    },
                    '.Label': {
                        fontWeight: '500',
                        marginBottom: '8px'
                    },
                    '.Error': {
                        fontSize: '14px',
                        marginTop: '4px'
                    }
                }
            },
            loader: 'auto'
        };
        
        // Créer les éléments Stripe
        this.elements = this.stripe.elements(elementOptions);
        
        // Ajouter l'élément de paiement principal
        this.paymentElement = this.elements.create('payment', {
            layout: {
                type: 'tabs',
                defaultCollapsed: false
            }
        });
        this.paymentElement.mount('#payment-element');
        
        // Si Apple Pay / Google Pay est disponible, l'ajouter comme option
        if (this.paymentRequest) {
            this.setupWalletPayment();
        }
        
        // Configurer les écouteurs d'événements
        this.setupEventListeners();
    }
    
    /**
     * Configure le paiement via portefeuille mobile (Apple Pay / Google Pay)
     */
    setupWalletPayment() {
        // Créer et monter l'élément de paiement par portefeuille
        const paymentRequestButton = this.elements.create('paymentRequestButton', {
            paymentRequest: this.paymentRequest
        });
        
        // Créer un conteneur pour le bouton
        let walletButtonContainer = document.getElementById('wallet-payment-button');
        if (!walletButtonContainer) {
            walletButtonContainer = document.createElement('div');
            walletButtonContainer.id = 'wallet-payment-button';
            walletButtonContainer.className = 'mb-4';
            this.form.insertBefore(walletButtonContainer, this.form.firstChild);
        }
        
        // Monter le bouton
        paymentRequestButton.mount('#wallet-payment-button');
        
        // Configurer les événements de paiement
        this.paymentRequest.on('paymentmethod', async (event) => {
            this.showLoadingOverlay();
            
            try {
                // Confirmer le paiement avec le paymentMethod
                const { error, paymentIntent } = await this.stripe.confirmCardPayment(
                    this.clientSecret,
                    { payment_method: event.paymentMethod.id },
                    { handleActions: false }
                );
                
                if (error) {
                    // Informer le wallet et afficher l'erreur
                    event.complete('fail');
                    this.showErrorMessage(error.message);
                    this.hideLoadingOverlay();
                    return;
                }
                
                // Gérer le status du paiement
                if (paymentIntent.status === 'requires_action') {
                    // Authentification 3D Secure nécessaire
                    event.complete('success');
                    const { error } = await this.stripe.confirmCardPayment(this.clientSecret);
                    
                    if (error) {
                        this.showPaymentFailure(error.message);
                    } else {
                        this.showPaymentSuccess();
                    }
                } else {
                    // Paiement réussi directement
                    event.complete('success');
                    this.showPaymentSuccess();
                }
                
            } catch (error) {
                event.complete('fail');
                this.showErrorMessage('Une erreur est survenue. Veuillez réessayer.');
                this.hideLoadingOverlay();
            }
        });
    }
    
    /**
     * Configure les écouteurs d'événements du formulaire
     */
    setupEventListeners() {
        if (!this.form || !this.submitButton) return;
        
        // Soumettre le formulaire de paiement
        this.form.addEventListener('submit', async (event) => {
            event.preventDefault();
            
            if (this.isPaymentProcessing) return;
            
            this.startPaymentProcessing();
            
            const { error } = await this.stripe.confirmPayment({
                elements: this.elements,
                confirmParams: {
                    return_url: window.location.origin + '/paiement/confirmation.php',
                    payment_method_data: {
                        billing_details: {
                            email: this.form.querySelector('input[name="email"]')?.value
                        }
                    }
                }
            });
            
            // En cas d'erreur lors de la confirmation du paiement
            if (error) {
                this.stopPaymentProcessing();
                this.showErrorMessage(this.getErrorMessage(error));
                return;
            }
            
            // Le paiement est en cours ou réussi, la redirection est gérée par Stripe
        });
        
        // Valider le formulaire à la saisie
        this.paymentElement.on('change', (event) => {
            if (event.complete) {
                this.submitButton.classList.remove('disabled');
            } else {
                this.submitButton.classList.add('disabled');
            }
            
            if (event.error) {
                this.showErrorMessage(event.error.message);
            } else {
                this.hideErrorMessage();
            }
        });
    }
    
    /**
     * Démarre le traitement du paiement
     */
    startPaymentProcessing() {
        this.isPaymentProcessing = true;
        this.submitButton.disabled = true;
        this.submitButton.classList.add('processing');
        this.submitButton.innerHTML = `
            <div class="spinner"></div>
            <span>Traitement en cours...</span>
        `;
        
        this.showLoadingOverlay();
    }
    
    /**
     * Arrête le traitement du paiement
     */
    stopPaymentProcessing() {
        this.isPaymentProcessing = false;
        this.submitButton.disabled = false;
        this.submitButton.classList.remove('processing');
        this.submitButton.innerHTML = 'Payer';
        
        this.hideLoadingOverlay();
    }
    
    /**
     * Affiche un message d'erreur
     * @param {string} message - Message d'erreur à afficher
     */
    showErrorMessage(message) {
        if (!this.errorContainer) return;
        
        this.errorContainer.textContent = message || 'Une erreur est survenue.';
        this.errorContainer.style.display = 'block';
        
        // Faire défiler vers le message d'erreur
        this.errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    /**
     * Masque le message d'erreur
     */
    hideErrorMessage() {
        if (!this.errorContainer) return;
        
        this.errorContainer.textContent = '';
        this.errorContainer.style.display = 'none';
    }
    
    /**
     * Affiche le message de succès et masque le formulaire
     */
    showPaymentSuccess() {
        this.hideLoadingOverlay();
        
        if (this.paymentSuccess) {
            this.form.style.display = 'none';
            this.paymentSuccess.style.display = 'block';
            this.paymentSuccess.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Rediriger après un délai
        setTimeout(() => {
            window.location.href = window.location.origin + '/paiement/confirmation.php?status=success';
        }, 2000);
    }
    
    /**
     * Affiche le message d'échec et permet de réessayer
     * @param {string} message - Message d'erreur à afficher
     */
    showPaymentFailure(message) {
        this.hideLoadingOverlay();
        
        if (this.paymentFailure) {
            const errorDetail = this.paymentFailure.querySelector('.error-detail');
            if (errorDetail) {
                errorDetail.textContent = message || 'Votre paiement n\'a pas pu être traité.';
            }
            
            this.form.style.display = 'none';
            this.paymentFailure.style.display = 'block';
            this.paymentFailure.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Configurer le bouton pour réessayer
            const retryButton = this.paymentFailure.querySelector('.retry-button');
            if (retryButton) {
                retryButton.addEventListener('click', () => {
                    this.paymentFailure.style.display = 'none';
                    this.form.style.display = 'block';
                    this.stopPaymentProcessing();
                });
            }
        }
    }
    
    /**
     * Affiche l'overlay de chargement
     */
    showLoadingOverlay() {
        if (!this.loadingOverlay) {
            // Créer un overlay de chargement s'il n'existe pas
            this.loadingOverlay = document.createElement('div');
            this.loadingOverlay.className = 'loading-overlay';
            this.loadingOverlay.innerHTML = `
                <div class="loading-spinner"></div>
                <div class="loading-text">Traitement de votre paiement...</div>
            `;
            document.body.appendChild(this.loadingOverlay);
        }
        
        this.loadingOverlay.style.display = 'flex';
        document.body.classList.add('no-scroll');
    }
    
    /**
     * Masque l'overlay de chargement
     */
    hideLoadingOverlay() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'none';
            document.body.classList.remove('no-scroll');
        }
    }
    
    /**
     * Traduit les messages d'erreur Stripe en français
     * @param {Object} error - Objet d'erreur Stripe
     * @return {string} Message d'erreur traduit
     */
    getErrorMessage(error) {
        // Mapper les codes d'erreur Stripe à des messages en français
        const errorMessages = {
            'card_declined': 'Votre carte a été refusée.',
            'expired_card': 'Votre carte est expirée.',
            'incorrect_cvc': 'Le code de sécurité est incorrect.',
            'processing_error': 'Une erreur est survenue lors du traitement de votre carte.',
            'incorrect_number': 'Le numéro de carte est incorrect.',
            'incomplete_number': 'Le numéro de carte est incomplet.',
            'incomplete_cvc': 'Le code de sécurité est incomplet.',
            'incomplete_expiry': 'La date d\'expiration est incomplète.',
            'invalid_expiry_month': 'Le mois d\'expiration est invalide.',
            'invalid_expiry_year': 'L\'année d\'expiration est invalide.',
            'authentication_required': 'Une authentification supplémentaire est requise.'
        };
        
        // Retourner le message traduit ou le message d'origine
        return (error.type === 'card_error' && errorMessages[error.code]) 
            ? errorMessages[error.code] 
            : (error.message || 'Une erreur est survenue lors du paiement.');
    }
    
    /**
     * Vérifie le statut du paiement après redirection
     * @param {string} clientSecret - Secret client pour la session de paiement
     * @return {Promise} Promesse résolue avec le statut du paiement
     */
    async checkPaymentStatus(clientSecret) {
        if (!this.stripe) {
            return Promise.reject('Stripe non initialisé');
        }
        
        try {
            const { paymentIntent } = await this.stripe.retrievePaymentIntent(clientSecret);
            
            return {
                status: paymentIntent.status,
                id: paymentIntent.id,
                amount: paymentIntent.amount,
                success: paymentIntent.status === 'succeeded',
                requires_action: paymentIntent.status === 'requires_action',
                canceled: paymentIntent.status === 'canceled',
                processing: paymentIntent.status === 'processing'
            };
        } catch (error) {
            console.error('Erreur lors de la vérification du statut:', error);
            return Promise.reject(error);
        }
    }
    
    /**
     * Affiche le résultat du paiement sur la page de confirmation
     * @param {Object} result - Résultat du paiement
     */
    showPaymentResult(result) {
        const statusElement = document.getElementById('payment-status');
        const messageElement = document.getElementById('payment-message');
        const iconElement = document.getElementById('payment-icon');
        
        if (!statusElement) return;
        
        // Réinitialiser les classes
        statusElement.className = 'payment-status';
        
        // Définir les contenus en fonction du statut
        if (result.success) {
            statusElement.classList.add('success');
            statusElement.textContent = 'Paiement réussi';
            
            if (messageElement) {
                messageElement.textContent = 'Votre réservation est confirmée. Vous recevrez un email de confirmation.';
            }
            
            if (iconElement) {
                iconElement.innerHTML = '<span class="material-icons">check_circle</span>';
                iconElement.className = 'payment-icon success';
            }
        } else if (result.processing) {
            statusElement.classList.add('processing');
            statusElement.textContent = 'Paiement en cours de traitement';
            
            if (messageElement) {
                messageElement.textContent = 'Votre paiement est en cours de traitement. Nous vous informerons une fois la transaction terminée.';
            }
            
            if (iconElement) {
                iconElement.innerHTML = '<span class="material-icons">hourglass_top</span>';
                iconElement.className = 'payment-icon processing';
            }
        } else if (result.requires_action) {
            statusElement.classList.add('requires-action');
            statusElement.textContent = 'Authentification requise';
            
            if (messageElement) {
                messageElement.textContent = 'Une authentification supplémentaire est requise. Veuillez suivre les instructions de votre banque.';
            }
            
            if (iconElement) {
                iconElement.innerHTML = '<span class="material-icons">security</span>';
                iconElement.className = 'payment-icon requires-action';
            }
        } else if (result.canceled) {
            statusElement.classList.add('canceled');
            statusElement.textContent = 'Paiement annulé';
            
            if (messageElement) {
                messageElement.textContent = 'Votre paiement a été annulé. Vous pouvez réessayer à tout moment.';
            }
            
            if (iconElement) {
                iconElement.innerHTML = '<span class="material-icons">cancel</span>';
                iconElement.className = 'payment-icon canceled';
            }
        } else {
            statusElement.classList.add('error');
            statusElement.textContent = 'Paiement échoué';
            
            if (messageElement) {
                messageElement.textContent = 'Une erreur est survenue lors de votre paiement. Veuillez réessayer.';
            }
            
            if (iconElement) {
                iconElement.innerHTML = '<span class="material-icons">error</span>';
                iconElement.className = 'payment-icon error';
            }
        }
    }
}

// Initialiser l'instance unique
const stripePayment = new StripePaymentManager();

// Exposer l'instance au niveau global
window.stripePayment = stripePayment;

// Initialiser Stripe lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    // Récupérer la clé Stripe depuis l'attribut de la balise meta ou script
    const stripeKey = document.querySelector('meta[name="stripe-key"]')?.getAttribute('content') || 
                     document.getElementById('stripe-script')?.getAttribute('data-key');
    
    if (stripeKey) {
        // Initialiser Stripe
        stripePayment.init(stripeKey)
            .then(() => {
                console.log('Stripe initialisé avec succès');
                
                // Si on est sur la page de paiement, configurer le formulaire
                const clientSecret = document.querySelector('input[name="stripe_client_secret"]')?.value;
                if (clientSecret && document.getElementById('payment-form')) {
                    stripePayment.setupPaymentForm(clientSecret, 'payment-form');
                }
                
                // Si on est sur la page de confirmation, vérifier le statut du paiement
                const urlParams = new URLSearchParams(window.location.search);
                const paymentIntent = urlParams.get('payment_intent');
                const clientSecretParam = urlParams.get('payment_intent_client_secret');
                
                if (paymentIntent && clientSecretParam && document.getElementById('payment-status')) {
                    stripePayment.checkPaymentStatus(clientSecretParam)
                        .then(result => stripePayment.showPaymentResult(result))
                        .catch(error => console.error('Erreur lors de la vérification:', error));
                }
            })
            .catch(error => console.error('Erreur d\'initialisation Stripe:', error));
    }
});
