<?php
/**
 * Contrôleur pour la gestion des paiements
 */
require_once 'config/config.php';
require_once 'models/paiement.php';
require_once 'models/reservation.php';
require_once 'models/logement.php';
require_once 'models/utilisateur.php';
require_once 'includes/fonctions.php';
require_once 'includes/validation.php';

class PaiementController {
    private $paiementModel;
    private $reservationModel;
    private $logementModel;
    private $utilisateurModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->paiementModel = new PaiementModel();
        $this->reservationModel = new ReservationModel();
        $this->logementModel = new LogementModel();
        $this->utilisateurModel = new UtilisateurModel();
    }
    
    /**
     * Traite la création d'une session de paiement
     * @param int $idReservation ID de la réservation
     * @return array|bool Données de la session ou false
     */
    public function creerSessionPaiement($idReservation) {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            return false;
        }
        
        // Récupération de la réservation
        $reservation = $this->reservationModel->recupererParId($idReservation);
        
        if (!$reservation) {
            return false;
        }
        
        // Vérification si l'utilisateur est le locataire
        if ($reservation['id_locataire'] != $_SESSION['utilisateur_id']) {
            return false;
        }
        
        // Vérification si la réservation est en attente
        if ($reservation['statut'] !== 'en_attente') {
            return false;
        }
        
        // Récupération du logement
        $logement = $this->logementModel->recupererParId($reservation['id_logement']);
        
        if (!$logement) {
            return false;
        }
        
        // Création de la session de paiement Stripe
        initialiserStripe();
        
        $options = [
            'line_items' => [[
                'price_data' => [
                    'currency' => STRIPE_CURRENCY,
                    'unit_amount' => round($reservation['prix_total'] * 100),
                    'product_data' => [
                        'name' => $logement['titre'],
                        'description' => 'Du ' . formaterDate($reservation['date_debut']) . ' au ' . formaterDate($reservation['date_fin']),
                        'images' => $this->getImagesUrl($logement['id'])
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'id_reservation' => $reservation['id'],
                'id_logement' => $reservation['id_logement'],
                'id_locataire' => $reservation['id_locataire']
            ],
            'client_reference_id' => $reservation['id'],
            'success_url' => APP_URL . '/paiement-succes.php?id=' . $reservation['id'],
            'cancel_url' => APP_URL . '/paiement-annule.php?id=' . $reservation['id']
        ];
        
        $session = creerSessionStripe($options);
        
        if (!$session) {
            return false;
        }
        
        // Enregistrement du paiement
        $donneesPaiement = [
            'id_reservation' => $reservation['id'],
            'montant' => $reservation['prix_total'],
            'statut' => 'en_attente'
        ];
        
        $idPaiement = $this->paiementModel->creer($donneesPaiement);
        
        if (!$idPaiement) {
            return false;
        }
        
        return [
            'id_session' => $session->id,
            'url' => $session->url,
            'id_paiement' => $idPaiement
        ];
    }
    
    /**
     * Traite le succès d'un paiement
     * @param int $idReservation ID de la réservation
     * @return bool Résultat de l'opération
     */
    public function traiterSuccesPaiement($idReservation) {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            return false;
        }
        
        // Récupération de la réservation
        $reservation = $this->reservationModel->recupererParId($idReservation);
        
        if (!$reservation) {
            return false;
        }
        
        // Vérification si l'utilisateur est le locataire
        if ($reservation['id_locataire'] != $_SESSION['utilisateur_id']) {
            return false;
        }
        
        // Vérification si la réservation est en attente
        if ($reservation['statut'] !== 'en_attente') {
            return false;
        }
        
        // Récupération du paiement
        $paiement = $this->paiementModel->recupererParReservation($idReservation);
        
        if (!$paiement) {
            return false;
        }
        
        // Vérification du statut de la session Stripe (à implémenter)
        // Pour l'instant, on considère que le paiement est réussi
        
        // Mise à jour du statut du paiement
        $this->paiementModel->changerStatut($paiement['id'], 'complete');
        
        // Mise à jour du statut de la réservation
        $this->reservationModel->changerStatut($idReservation, 'acceptee');
        
        // Récupération du logement
        $logement = $this->logementModel->recupererParId($reservation['id_logement']);
        
        // Envoi d'emails de confirmation
        $locataire = $this->utilisateurModel->recupererParId($reservation['id_locataire']);
        $proprietaire = $this->utilisateurModel->recupererParId($logement['id_proprietaire']);
        
        // Email au locataire
        if (!empty($locataire['email'])) {
            // TODO: Envoyer email de confirmation au locataire
        }
        
        // Email au propriétaire
        if (!empty($proprietaire['email'])) {
            // TODO: Envoyer email de notification au propriétaire
        }
        
        return true;
    }
    
    /**
     * Traite l'annulation d'un paiement
     * @param int $idReservation ID de la réservation
     * @return bool Résultat de l'opération
     */
    public function traiterAnnulationPaiement($idReservation) {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            return false;
        }
        
        // Récupération de la réservation
        $reservation = $this->reservationModel->recupererParId($idReservation);
        
        if (!$reservation) {
            return false;
        }
        
        // Vérification si l'utilisateur est le locataire
        if ($reservation['id_locataire'] != $_SESSION['utilisateur_id']) {
            return false;
        }
        
        // Récupération du paiement
        $paiement = $this->paiementModel->recupererParReservation($idReservation);
        
        if (!$paiement) {
            return false;
        }
        
        // Mise à jour du statut du paiement
        $this->paiementModel->changerStatut($paiement['id'], 'echoue');
        
        return true;
    }
    
    /**
     * Traite le remboursement d'un paiement
     * @param int $idReservation ID de la réservation
     * @return bool Résultat de l'opération
     */
    public function rembourserPaiement($idReservation) {
        // Vérification si l'utilisateur est connecté et admin
        if (!estConnecte() || !$_SESSION['utilisateur_est_admin']) {
            return false;
        }
        
        // Récupération de la réservation
        $reservation = $this->reservationModel->recupererParId($idReservation);
        
        if (!$reservation) {
            return false;
        }
        
        // Récupération du paiement
        $paiement = $this->paiementModel->recupererParReservation($idReservation);
        
        if (!$paiement || $paiement['statut'] !== 'complete') {
            return false;
        }
        
        // Effectuer le remboursement via Stripe
        if (!empty($paiement['id_transaction'])) {
            $refund = rembourserPaiementStripe($paiement['id_transaction']);
            
            if (!$refund) {
                return false;
            }
        }
        
        // Mise à jour du statut du paiement
        $this->paiementModel->changerStatut($paiement['id'], 'rembourse');
        
        // Mise à jour du statut de la réservation
        $this->reservationModel->changerStatut($idReservation, 'annulee');
        
        // Envoi d'emails de notification
        $locataire = $this->utilisateurModel->recupererParId($reservation['id_locataire']);
        
        if (!empty($locataire['email'])) {
            // TODO: Envoyer email de confirmation de remboursement
        }
        
        return true;
    }
    
    /**
     * Traite un webhook Stripe
     * @param string $payload Contenu du webhook
     * @param string $sigHeader En-tête de signature
     * @return bool Résultat du traitement
     */
    public function traiterWebhook($payload, $sigHeader) {
        // Vérification de la signature
        $event = verifierWebhookStripe($payload, $sigHeader);
        
        if (!$event) {
            return false;
        }
        
        // Traitement de l'événement
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $idReservation = $session->client_reference_id;
                
                // Récupération du paiement
                $paiement = $this->paiementModel->recupererParReservation($idReservation);
                
                if (!$paiement) {
                    return false;
                }
                
                // Mise à jour du paiement
                $this->paiementModel->mettreAJour($paiement['id'], [
                    'id_transaction' => $session->payment_intent,
                    'statut' => 'complete'
                ]);
                
                // Mise à jour de la réservation
                $this->reservationModel->changerStatut($idReservation, 'acceptee');
                
                break;
                
            case 'charge.refunded':
                $charge = $event->data->object;
                $idTransaction = $charge->payment_intent;
                
                // Récupération du paiement
                $paiement = $this->paiementModel->recupererParTransaction($idTransaction);
                
                if (!$paiement) {
                    return false;
                }
                
                // Mise à jour du paiement
                $this->paiementModel->changerStatut($paiement['id'], 'rembourse');
                
                // Mise à jour de la réservation
                $this->reservationModel->changerStatut($paiement['id_reservation'], 'annulee');
                
                break;
        }
        
        return true;
    }
    
    /**
     * Récupère les URLs des images d'un logement
     * @param int $idLogement ID du logement
     * @return array URLs des images
     */
    private function getImagesUrl($idLogement) {
        $photos = $this->logementModel->recupererPhotos($idLogement);
        $urls = [];
        
        foreach ($photos as $photo) {
            $urls[] = APP_URL . '/uploads/logements/' . $photo['url'];
        }
        
        // Si pas de photos, utiliser une image par défaut
        if (empty($urls)) {
            $urls[] = APP_URL . '/assets/img/placeholders/logement.jpg';
        }
        
        return $urls;
    }
}
?>
