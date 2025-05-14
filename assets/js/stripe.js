/**
 * Script pour l'intégration de Stripe
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser Stripe
    initStripe();
});

/**
 * Initialise Stripe
 */
function initStripe() {
    // Vérifier si l'élément de formulaire de paiement existe
    const paymentForm = document.getElementById('payment-form');
    
    if (!paymentForm) {
        return;
    }
    
    // Récupérer la clé publique Stripe
    const stripePublicKey = paymentForm.getAttribute('data-stripe-key');
    
    if (!stripePublicKey) {
        console.error('Clé publique Stripe manquante');
        return;
    }
    
    // Initialiser Stripe
    const stripe = Stripe(stripePublicKey);
    
    // Récupérer l'ID de session
    const sessionId = paymentForm.getAttribute('data-session-id');
    
    if (sessionId) {
        // Redirection vers la page de paiement Stripe
        stripe.redirectToCheckout({ sessionId: sessionId })
            .then(function(result) {
                if (result.error) {
                    // Afficher l'erreur
                    const errorElement = document.getElementById('stripe-error');
                    if (errorElement) {
                        errorElement.textContent = result.error.message;
                        errorElement.style.display = 'block';
                    }
                }
            });
    } else {
        // Créer un élément de carte
        const elements = stripe.elements();
        
        // Personnalisation de l'élément de carte
        const style = {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };
        
        // Créer un élément de carte
        const card = elements.create('card', { style: style });
        
        // Monter l'élément de carte dans le DOM
        card.mount('#card-element');
        
        // Gérer les erreurs de validation en temps réel
        card.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        
        // Gérer la soumission du formulaire
        paymentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Désactiver le bouton de soumission pour éviter les clics multiples
            const submitButton = paymentForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<div class="spinner"></div> Traitement en cours...';
            
            // Créer un token de paiement
            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    // Afficher l'erreur
                    const errorElement = document.getElementById('card-errors');
                    if (errorElement) {
                        errorElement.textContent = result.error.message;
                    }
                    
                    // Réactiver le bouton
                    submitButton.disabled = false;
                    submitButton.textContent = 'Payer';
                } else {
                    // Envoyer le token au serveur
                    stripeTokenHandler(result.token);
                }
            });
        });
        
        /**
         * Envoie le token Stripe au serveur
         * @param {object} token - Token Stripe
         */
        function stripeTokenHandler(token) {
            // Insérer le token ID dans le formulaire pour qu'il soit envoyé au serveur
            const hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'stripeToken');
            hiddenInput.setAttribute('value', token.id);
            paymentForm.appendChild(hiddenInput);
            
            // Soumettre le formulaire
            paymentForm.submit();
        }
    }
}

/**
 * Calcule le prix total d'une réservation
 * @param {number} prixNuit - Prix par nuit
 * @param {string} dateDebut - Date de début (YYYY-MM-DD)
 * @param {string} dateFin - Date de fin (YYYY-MM-DD)
 * @returns {number} Prix total
 */
function calculerPrixTotal(prixNuit, dateDebut, dateFin) {
    if (!dateDebut || !dateFin) {
        return 0;
    }
    
    const debut = new Date(dateDebut);
    const fin = new Date(dateFin);
    
    // Calcul de la différence en jours
    const diffTime = Math.abs(fin - debut);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    return prixNuit * diffDays;
}
