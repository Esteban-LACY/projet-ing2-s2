/**
 * Intégration Stripe pour OmnesBnB
 * Gère le processus de paiement
 */

class StripePayment {
    constructor() {
        // Initialisation des variables
        this.stripe = null;
        this.elements = null;
        this.paymentElement = null;
        this.formElement = null;
        
        // Éléments du DOM
        this.submitButton = null;
        this.errorMessage = null;
    }
    
    /**
     * Initialise Stripe avec la clé publique
     * @param {string} publishableKey - Clé publique Stripe
     */
    init(publishableKey) {
        if (!publishableKey) {
            console.error('Clé API Stripe manquante');
            return;
        }
        
        this.stripe = Stripe(publishableKey);
    }
    
    /**
     * Configure le formulaire de paiement
     * @param {string} clientSecret - Secret client pour la session de paiement
     * @param {string} formId - ID du formulaire de paiement
     */
    setupPaymentForm(clientSecret, formId) {
        if (!this.stripe || !clientSecret) {
            console.error('Stripe non initialisé ou client secret manquant');
            return;
        }
        
        // Récupérer les éléments du DOM
        this.formElement = document.getElementById(formId);
        if (!this.formElement) {
            console.error('Formulaire de paiement non trouvé');
            return;
        }
        
        this.submitButton = this.formElement.querySelector('#submit-payment');
        this.errorMessage = this.formElement.querySelector('#payment-error');
        
        // Créer les éléments Stripe
        this.elements = this.stripe.elements({
            clientSecret: clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#000000',
                    colorBackground: '#ffffff',
                    colorText: '#333333',
                    colorDanger: '#ff0000',
                    fontFamily: 'SF Pro Display, sans-serif',
                    borderRadius: '8px'
                }
            }
        });
        
        // Créer et monter l'élément de paiement
        this.paymentElement = this.elements.create('payment');
        this.paymentElement.mount('#payment-element');
        
        // Ajouter les écouteurs d'événements
        this.setupEventListeners();
    }
    
    /**
     * Configure les écouteurs d'événements pour le formulaire
     */
    setupEventListeners() {
        if (!this.formElement) return;
        
        this.formElement.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!this.stripe || !this.elements) {
                return;
            }
            
            // Désactiver le bouton durant le traitement
            if (this.submitButton) {
                this.submitButton.disabled = true;
                this.submitButton.textContent = 'Traitement en cours...';
            }
            
            // Confirmer le paiement
            const result = await this.stripe.confirmPayment({
                elements: this.elements,
                confirmParams: {
                    return_url: `${window.location.origin}/reservation/confirmation.php`
                }
            });
            
            // Gérer les erreurs potentielles
            if (result.error) {
                if (this.errorMessage) {
                    this.errorMessage.textContent = result.error.message;
                    this.errorMessage.style.display = 'block';
                }
                
                // Réactiver le bouton
                if (this.submitButton) {
                    this.submitButton.disabled = false;
                    this.submitButton.textContent = 'Payer';
                }
            }
            // Le paiement est traité et l'utilisateur sera redirigé
        });
    }
    
    /**
     * Vérifie le statut du paiement lors de la redirection
     * @param {string} clientSecret - Secret client pour la session de paiement 
     */
    async checkPaymentStatus(clientSecret) {
        if (!this.stripe) {
            console.error('Stripe non initialisé');
            return;
        }
        
        const { paymentIntent } = await this.stripe.retrievePaymentIntent(clientSecret);
        
        const statusElement = document.getElementById('payment-status');
        if (!statusElement) return;
        
        switch (paymentIntent.status) {
            case 'succeeded':
                statusElement.textContent = 'Paiement réussi !';
                statusElement.classList.add('text-green-500');
                break;
            case 'processing':
                statusElement.textContent = 'Paiement en cours de traitement.';
                statusElement.classList.add('text-yellow-500');
                break;
            case 'requires_payment_method':
                statusElement.textContent = 'Paiement échoué. Veuillez réessayer.';
                statusElement.classList.add('text-red-500');
                break;
            default:
                statusElement.textContent = 'Une erreur est survenue.';
                statusElement.classList.add('text-red-500');
                break;
        }
    }
}

// Export de l'instance pour utilisation dans d'autres fichiers
const stripePayment = new StripePayment();
window.stripePayment = stripePayment;
