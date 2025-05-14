<?php
/**
 * Contrôleur pour la gestion des réservations
 */
require_once 'config/config.php';
require_once 'models/reservation.php';
require_once 'models/logement.php';
require_once 'models/utilisateur.php';
require_once 'includes/fonctions.php';
require_once 'includes/validation.php';

class ReservationController {
    private $reservationModel;
    private $logementModel;
    private $utilisateurModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->reservationModel = new ReservationModel();
        $this->logementModel = new LogementModel();
        $this->utilisateurModel = new UtilisateurModel();
    }
    
    /**
     * Traite la création d'une réservation
     */
    public function creer() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            $_SESSION['url_apres_connexion'] = 'reservation.php';
            afficherMessage('Vous devez être connecté pour réserver un logement.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Vérification si l'utilisateur a vérifié son email
        if (EMAIL_VERIFICATION && !$_SESSION['utilisateur_est_verifie']) {
            afficherMessage('Vous devez vérifier votre email avant de réserver un logement.', 'avertissement');
            rediriger('index.php');
            return;
        }
        
        // Vérification si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Récupération et nettoyage des données
        $idLogement = isset($_POST['id_logement']) ? intval($_POST['id_logement']) : 0;
        $dateDebut = nettoyer($_POST['date_debut'] ?? '');
        $dateFin = nettoyer($_POST['date_fin'] ?? '');
        
        // Validation des données
        $erreurs = [];
        
        if ($idLogement <= 0) {
            $erreurs['id_logement'] = 'Logement invalide.';
        }
        
        if (empty($dateDebut)) {
            $erreurs['date_debut'] = 'La date d\'arrivée est obligatoire.';
        } elseif (!estDateValide($dateDebut)) {
            $erreurs['date_debut'] = 'Format de date d\'arrivée invalide.';
        }
        
        if (empty($dateFin)) {
            $erreurs['date_fin'] = 'La date de départ est obligatoire.';
        } elseif (!estDateValide($dateFin)) {
            $erreurs['date_fin'] = 'Format de date de départ invalide.';
        }
        
        if (!empty($dateDebut) && !empty($dateFin) && !estPeriodeValide($dateDebut, $dateFin)) {
            $erreurs['dates'] = 'La date d\'arrivée doit être antérieure à la date de départ.';
        }
        
        // S'il y a des erreurs, on les stocke en session
        if (!empty($erreurs)) {
            $_SESSION['erreurs_reservation'] = $erreurs;
            rediriger('logement.php?id=' . $idLogement);
            return;
        }
        
        // Récupération des informations du logement
        $logement = $this->logementModel->recupererParId($idLogement);
        
        if (!$logement) {
            afficherMessage('Logement introuvable.', 'erreur');
            rediriger('index.php');
            return;
        }
        
        // Vérification si l'utilisateur n'est pas le propriétaire
        if ($logement['id_proprietaire'] == $_SESSION['utilisateur_id']) {
            afficherMessage('Vous ne pouvez pas réserver votre propre logement.', 'erreur');
            rediriger('logement.php?id=' . $idLogement);
            return;
        }
        
        // Vérification de la disponibilité
        if (!$this->logementModel->estDisponible($idLogement, $dateDebut, $dateFin)) {
            afficherMessage('Ce logement n\'est pas disponible aux dates sélectionnées.', 'erreur');
            rediriger('logement.php?id=' . $idLogement);
            return;
        }
        
        // Calcul du prix total
        $debut = new DateTime($dateDebut);
        $fin = new DateTime($dateFin);
        $nbJours = $fin->diff($debut)->days;
        $prixTotal = $logement['prix'] * $nbJours;
        
        // Création de la réservation
        $donneesReservation = [
            'id_logement' => $idLogement,
            'id_locataire' => $_SESSION['utilisateur_id'],
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'prix_total' => $prixTotal,
            'statut' => 'en_attente'
        ];
        
        $idReservation = $this->reservationModel->creer($donneesReservation);
        
        if (!$idReservation) {
            afficherMessage('Une erreur est survenue lors de la création de la réservation.', 'erreur');
            rediriger('logement.php?id=' . $idLogement);
            return;
        }
        
        // Redirection vers la page de paiement
        $_SESSION['id_reservation'] = $idReservation;
        rediriger('paiement.php?id=' . $idReservation);
    }
    
    /**
     * Traite l'annulation d'une réservation
     */
    public function annuler() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            afficherMessage('Vous devez être connecté pour annuler une réservation.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Récupération de l'ID de la réservation
        $idReservation = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($idReservation <= 0) {
            afficherMessage('Réservation introuvable.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Récupération de la réservation
        $reservation = $this->reservationModel->recupererParId($idReservation);
        
        if (!$reservation) {
            afficherMessage('Réservation introuvable.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Récupération du logement
        $logement = $this->logementModel->recupererParId($reservation['id_logement']);
        
        // Vérification des droits
        $estLocataire = $reservation['id_locataire'] == $_SESSION['utilisateur_id'];
        $estProprietaire = $logement['id_proprietaire'] == $_SESSION['utilisateur_id'];
        
        if (!$estLocataire && !$estProprietaire && !$_SESSION['utilisateur_est_admin']) {
            afficherMessage('Vous n\'êtes pas autorisé à annuler cette réservation.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Vérification si la réservation peut être annulée
        if ($reservation['statut'] !== 'en_attente' && $reservation['statut'] !== 'acceptee') {
            afficherMessage('Cette réservation ne peut pas être annulée.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Annulation de la réservation
        $resultat = $this->reservationModel->changerStatut($idReservation, 'annulee');
        
        if (!$resultat) {
            afficherMessage('Une erreur est survenue lors de l\'annulation de la réservation.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Envoi d'emails de notification
        $locataire = $this->utilisateurModel->recupererParId($reservation['id_locataire']);
        $proprietaire = $this->utilisateurModel->recupererParId($logement['id_proprietaire']);
        
        // Email au locataire
        if ($estProprietaire && !empty($locataire['email'])) {
            // TODO: Envoyer email
        }
        
        // Email au propriétaire
        if ($estLocataire && !empty($proprietaire['email'])) {
            // TODO: Envoyer email
        }
        
        afficherMessage('La réservation a été annulée avec succès.', 'succes');
        rediriger('profil.php');
    }
    
    /**
     * Traite la confirmation d'une réservation par le propriétaire
     */
    public function confirmer() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            afficherMessage('Vous devez être connecté pour confirmer une réservation.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Récupération de l'ID de la réservation
        $idReservation = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($idReservation <= 0) {
            afficherMessage('Réservation introuvable.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Récupération de la réservation
        $reservation = $this->reservationModel->recupererParId($idReservation);
        
        if (!$reservation) {
            afficherMessage('Réservation introuvable.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Récupération du logement
        $logement = $this->logementModel->recupererParId($reservation['id_logement']);
        
        // Vérification des droits
        if ($logement['id_proprietaire'] != $_SESSION['utilisateur_id'] && !$_SESSION['utilisateur_est_admin']) {
            afficherMessage('Vous n\'êtes pas autorisé à confirmer cette réservation.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Vérification si la réservation peut être confirmée
        if ($reservation['statut'] !== 'en_attente') {
            afficherMessage('Cette réservation ne peut pas être confirmée.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Confirmation de la réservation
        $resultat = $this->reservationModel->changerStatut($idReservation, 'acceptee');
        
        if (!$resultat) {
            afficherMessage('Une erreur est survenue lors de la confirmation de la réservation.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Envoi d'un email de confirmation au locataire
        $locataire = $this->utilisateurModel->recupererParId($reservation['id_locataire']);
        
        if (!empty($locataire['email'])) {
            // TODO: Envoyer email
        }
        
        afficherMessage('La réservation a été confirmée avec succès.', 'succes');
        rediriger('profil.php');
    }
    
    /**
     * Traite le refus d'une réservation par le propriétaire
     */
    public function refuser() {
        // Vérification si l'utilisateur est connecté
        if (!estConnecte()) {
            afficherMessage('Vous devez être connecté pour refuser une réservation.', 'erreur');
            rediriger('connexion.php');
            return;
        }
        
        // Récupération de l'ID de la réservation
        $idReservation = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($idReservation <= 0) {
            afficherMessage('Réservation introuvable.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Récupération de la réservation
        $reservation = $this->reservationModel->recupererParId($idReservation);
        
        if (!$reservation) {
            afficherMessage('Réservation introuvable.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Récupération du logement
        $logement = $this->logementModel->recupererParId($reservation['id_logement']);
        
        // Vérification des droits
        if ($logement['id_proprietaire'] != $_SESSION['utilisateur_id'] && !$_SESSION['utilisateur_est_admin']) {
            afficherMessage('Vous n\'êtes pas autorisé à refuser cette réservation.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Vérification si la réservation peut être refusée
        if ($reservation['statut'] !== 'en_attente') {
            afficherMessage('Cette réservation ne peut pas être refusée.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Refus de la réservation
        $resultat = $this->reservationModel->changerStatut($idReservation, 'refusee');
        
        if (!$resultat) {
            afficherMessage('Une erreur est survenue lors du refus de la réservation.', 'erreur');
            rediriger('profil.php');
            return;
        }
        
        // Envoi d'un email d'information au locataire
        $locataire = $this->utilisateurModel->recupererParId($reservation['id_locataire']);
        
        if (!empty($locataire['email'])) {
            // TODO: Envoyer email
        }
        
        afficherMessage('La réservation a été refusée avec succès.', 'succes');
        rediriger('profil.php');
    }
    
    /**
     * Récupère les réservations d'un utilisateur
     * @param int $idUtilisateur ID de l'utilisateur
     * @param bool $estLocataire Indique si l'utilisateur est locataire ou propriétaire
     * @return array Réservations
     */
    public function recupererReservationsUtilisateur($idUtilisateur, $estLocataire = true) {
        if ($estLocataire) {
            return $this->reservationModel->recupererParLocataire($idUtilisateur);
        } else {
            return $this->reservationModel->recupererParProprietaire($idUtilisateur);
        }
    }
}
?>
