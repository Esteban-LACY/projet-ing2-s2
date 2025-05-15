<?php
/**
 * Gestion des réservations (Administration)
 * 
 * Cette page permet de gérer les réservations de l'application
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_INCLUDES . '/auth.php';

// Vérifier que l'utilisateur est administrateur
if (!estConnecte() || !estAdmin()) {
    rediriger(URL_SITE . '/connexion.php?redirect=admin/reservations.php');
}

// Titre de la page
$titre = 'Gestion des réservations - Administration';

// Paramètres de pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limite = 10;

// Récupérer les filtres
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$idLogement = isset($_GET['id_logement']) ? $_GET['id_logement'] : '';
$idLocataire = isset($_GET['id_locataire']) ? $_GET['id_locataire'] : '';

// Inclure l'en-tête
include CHEMIN_VUES . '/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include CHEMIN_VUES . '/admin/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des réservations</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" id="btn-export-csv">
                        <i class="bi bi-download"></i> Exporter CSV
                    </button>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form id="form-filtres" method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="">Tous</option>
                                <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="acceptee" <?= $statut === 'acceptee' ? 'selected' : '' ?>>Acceptée</option>
                                <option value="refusee" <?= $statut === 'refusee' ? 'selected' : '' ?>>Refusée</option>
                                <option value="annulee" <?= $statut === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                                <option value="terminee" <?= $statut === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="id_logement" class="form-label">Logement (ID)</label>
                            <input type="number" class="form-control" id="id_logement" name="id_logement" placeholder="ID du logement..." value="<?= htmlspecialchars($idLogement) ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="id_locataire" class="form-label">Locataire (ID)</label>
                            <input type="number" class="form-control" id="id_locataire" name="id_locataire" placeholder="ID du locataire..." value="<?= htmlspecialchars($idLocataire) ?>">
                        </div>
                        
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des réservations -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Logement</th>
                                    <th>Locataire</th>
                                    <th>Dates</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th>Paiement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="liste-reservations">
                                <tr>
                                    <td colspan="8" class="text-center">Chargement...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center" id="pagination">
                            <li class="page-item disabled"><a class="page-link" href="#">Chargement...</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
            
            <!-- Modal détails réservation -->
            <div class="modal fade" id="modalReservation" tabindex="-1" aria-labelledby="modalReservationLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalReservationLabel">Détails de la réservation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4" id="modal-loading">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2">Chargement des données...</p>
                            </div>
                            
                            <div id="modal-content" style="display: none;">
                                <!-- Informations de réservation -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Informations générales</h5>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>ID :</strong> <span id="reservation-id"></span></p>
                                                <p><strong>Dates :</strong> <span id="reservation-dates"></span></p>
                                                <p><strong>Prix :</strong> <span id="reservation-prix"></span></p>
                                                <p><strong>Statut :</strong> <span id="reservation-statut"></span></p>
                                                <p><strong>Date de création :</strong> <span id="reservation-creation"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Paiement</h5>
                                            </div>
                                            <div class="card-body">
                                                <div id="paiement-info">
                                                    <p><strong>ID :</strong> <span id="paiement-id"></span></p>
                                                    <p><strong>Montant :</strong> <span id="paiement-montant"></span></p>
                                                    <p><strong>Transaction :</strong> <span id="paiement-transaction"></span></p>
                                                    <p><strong>Statut :</strong> <span id="paiement-statut"></span></p>
                                                    <p><strong>Date :</strong> <span id="paiement-date"></span></p>
                                                </div>
                                                <div id="paiement-none" style="display: none;">
                                                    <p class="text-center">Aucun paiement associé</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Logement et utilisateurs -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card border-0 shadow-sm mb-4">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Logement</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <img id="logement-photo" src="" alt="Photo du logement" class="img-fluid rounded mb-2" style="width: 100%; height: 150px; object-fit: cover;">
                                                    </div>
                                                    <div class="col-md-8">
                                                        <h5 id="logement-titre"></h5>
                                                        <p id="logement-adresse" class="mb-1"></p>
                                                        <p id="logement-type" class="mb-1"></p>
                                                        <p id="logement-prix" class="mb-0"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Locataire</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4 text-center">
                                                        <img id="locataire-photo" src="" alt="Photo de profil" class="img-fluid rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                                    </div>
                                                    <div class="col-md-8">
                                                        <h5 id="locataire-name"></h5>
                                                        <p id="locataire-email" class="mb-1"></p>
                                                        <p id="locataire-phone" class="mb-0"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Propriétaire</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4 text-center">
                                                        <img id="proprietaire-photo" src="" alt="Photo de profil" class="img-fluid rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                                    </div>
                                                    <div class="col-md-8">
                                                        <h5 id="proprietaire-name"></h5>
                                                        <p id="proprietaire-email" class="mb-1"></p>
                                                        <p id="proprietaire-phone" class="mb-0"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Formulaire de modification du statut -->
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">Modifier le statut</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="form-statut">
                                            <input type="hidden" id="reservation-id-form" name="id_reservation">
                                            
                                            <div class="mb-3">
                                                <label for="statut" class="form-label">Nouveau statut</label>
                                                <select class="form-select" id="statut-form" name="statut" required>
                                                    <option value="en_attente">En attente</option>
                                                    <option value="acceptee">Acceptée</option>
                                                    <option value="refusee">Refusée</option>
                                                    <option value="annulee">Annulée</option>
                                                    <option value="terminee">Terminée</option>
                                                </select>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary" id="btn-modifier-statut">Modifier le statut</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger me-auto" id="btn-supprimer">Supprimer</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const listeReservations = document.getElementById('liste-reservations');
    const pagination = document.getElementById('pagination');
    const modalReservation = new bootstrap.Modal(document.getElementById('modalReservation'));
    const btnSupprimer = document.getElementById('btn-supprimer');
    const formStatut = document.getElementById('form-statut');
    const modalLoading = document.getElementById('modal-loading');
    const modalContent = document.getElementById('modal-content');
    
    // Paramètres courants
    let paramsActuels = {
        page: <?= $page ?>,
        limite: <?= $limite ?>,
        statut: <?= json_encode($statut) ?>,
        id_logement: <?= json_encode($idLogement) ?>,
        id_locataire: <?= json_encode($idLocataire) ?>
    };
    
    // Charger les réservations
    chargerReservations();
    
    // Événement de soumission du formulaire de filtres
    document.getElementById('form-filtres').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        paramsActuels = {
            page: 1,
            limite: <?= $limite ?>
        };
        
        for (const [key, value] of formData.entries()) {
            paramsActuels[key] = value;
        }
        
        // Mettre à jour l'URL
        window.history.pushState({}, '', 'reservations.php?' + new URLSearchParams(paramsActuels).toString());
        
        // Recharger les réservations
        chargerReservations();
    });
    
    // Exportation CSV
    document.getElementById('btn-export-csv').addEventListener('click', function() {
        exporterCSV();
    });
    
    // Événements de la modal
    btnSupprimer.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette réservation ? Cette action est irréversible.')) {
            supprimerReservation();
        }
    });
    
    // Soumission du formulaire de modification de statut
    formStatut.addEventListener('submit', function(e) {
        e.preventDefault();
        modifierStatutReservation();
    });
    
    /**
     * Charge la liste des réservations via une requête AJAX
     */
    function chargerReservations() {
        const params = new URLSearchParams();
        
        for (const [key, value] of Object.entries(paramsActuels)) {
            if (value !== '') {
                params.append(key, value);
            }
        }
        
        // Afficher un état de chargement
        listeReservations.innerHTML = '<tr><td colspan="8" class="text-center">Chargement...</td></tr>';
        pagination.innerHTML = '<li class="page-item disabled"><a class="page-link" href="#">Chargement...</a></li>';
        
        fetch('../controllers/admin.php?action=recuperer_reservations&' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    afficherReservations(data.reservations, data.total, data.page, data.limite, data.total_pages);
                } else {
                    listeReservations.innerHTML = '<tr><td colspan="8" class="text-center">Erreur lors du chargement des données</td></tr>';
                    pagination.innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                listeReservations.innerHTML = '<tr><td colspan="8" class="text-center">Erreur lors du chargement des données</td></tr>';
                pagination.innerHTML = '';
            });
    }
    
    /**
     * Affiche la liste des réservations dans le tableau
     */
    function afficherReservations(reservations, total, page, limite, totalPages) {
        if (reservations.length === 0) {
            listeReservations.innerHTML = '<tr><td colspan="8" class="text-center">Aucune réservation trouvée</td></tr>';
            pagination.innerHTML = '';
            return;
        }
        
        let html = '';
        
        reservations.forEach(reservation => {
            // Formater les dates
            const dateDebut = new Date(reservation.date_debut).toLocaleDateString('fr-FR');
            const dateFin = new Date(reservation.date_fin).toLocaleDateString('fr-FR');
            
            // Formater le prix
            const prix = formaterPrix(reservation.prix_total);
            
            // Déterminer la classe de badge pour le statut
            let badgeClass = '';
            
            switch (reservation.statut) {
                case 'en_attente':
                    badgeClass = 'bg-warning';
                    break;
                case 'acceptee':
                    badgeClass = 'bg-success';
                    break;
                case 'refusee':
                    badgeClass = 'bg-danger';
                    break;
                case 'annulee':
                    badgeClass = 'bg-secondary';
                    break;
                case 'terminee':
                    badgeClass = 'bg-primary';
                    break;
                default:
                    badgeClass = 'bg-dark';
            }
            
            // Formater le statut
            let statut = '';
            
            switch (reservation.statut) {
                case 'en_attente':
                    statut = 'En attente';
                    break;
                case 'acceptee':
                    statut = 'Acceptée';
                    break;
                case 'refusee':
                    statut = 'Refusée';
                    break;
                case 'annulee':
                    statut = 'Annulée';
                    break;
                case 'terminee':
                    statut = 'Terminée';
                    break;
                default:
                    statut = reservation.statut;
            }
            
            // Déterminer le statut du paiement
            let paiementStatut = 'Non payé';
            let paiementClass = 'bg-danger';
            
            if (reservation.paiement) {
                switch (reservation.paiement.statut) {
                    case 'en_attente':
                        paiementStatut = 'En attente';
                        paiementClass = 'bg-warning';
                        break;
                    case 'complete':
                        paiementStatut = 'Payé';
                        paiementClass = 'bg-success';
                        break;
                    case 'rembourse':
                        paiementStatut = 'Remboursé';
                        paiementClass = 'bg-info';
                        break;
                    case 'echoue':
                        paiementStatut = 'Échoué';
                        paiementClass = 'bg-danger';
                        break;
                    default:
                        paiementStatut = reservation.paiement.statut;
                        paiementClass = 'bg-dark';
                }
            }
            
            html += `
                <tr>
                    <td>${reservation.id}</td>
                    <td>${reservation.logement ? reservation.logement.titre : 'N/A'}</td>
                    <td>${reservation.locataire ? reservation.locataire.prenom + ' ' + reservation.locataire.nom : 'N/A'}</td>
                    <td>${dateDebut} - ${dateFin}</td>
                    <td>${prix}</td>
                    <td><span class="badge ${badgeClass}">${statut}</span></td>
                    <td><span class="badge ${paiementClass}">${paiementStatut}</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary btn-details" data-id="${reservation.id}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        listeReservations.innerHTML = html;
        
        // Ajouter les événements sur les boutons de détails
        document.querySelectorAll('.btn-details').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                ouvrirModalReservation(id);
            });
        });
        
        // Construire la pagination
        construirePagination(page, totalPages);
        
        // Afficher le nombre total de réservations
        const debut = (page - 1) * limite + 1;
        const fin = Math.min(page * limite, total);
        
        document.querySelector('.card-body').insertAdjacentHTML('afterbegin', `
            <p class="text-muted mb-3">Affichage de ${debut} à ${fin} sur ${total} réservations</p>
        `);
    }
    
    /**
     * Construit la pagination
     */
    function construirePagination(pageCourante, totalPages) {
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Bouton précédent
        html += `
            <li class="page-item ${pageCourante === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pageCourante - 1}" aria-label="Précédent">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;
        
        // Pages
        for (let i = 1; i <= totalPages; i++) {
            if (
                i === 1 ||                       // Première page
                i === totalPages ||              // Dernière page
                (i >= pageCourante - 1 && i <= pageCourante + 1)  // 1 page avant et après la courante
            ) {
                html += `
                    <li class="page-item ${i === pageCourante ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (
                i === 2 && pageCourante > 3 ||   // Ellipsis après la première page
                i === totalPages - 1 && pageCourante < totalPages - 2  // Ellipsis avant la dernière page
            ) {
                html += '<li class="page-item disabled"><a class="page-link" href="#">&hellip;</a></li>';
            }
        }
        
        // Bouton suivant
        html += `
            <li class="page-item ${pageCourante === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pageCourante + 1}" aria-label="Suivant">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        
        pagination.innerHTML = html;
        
        // Ajouter les événements sur les liens de pagination
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.parentElement.classList.contains('disabled')) {
                    return;
                }
                
                const page = parseInt(this.getAttribute('data-page'));
                
                if (page !== paramsActuels.page) {
                    paramsActuels.page = page;
                    
                    // Mettre à jour l'URL
                    window.history.pushState({}, '', 'reservations.php?' + new URLSearchParams(paramsActuels).toString());
                    
                    // Recharger les réservations
                    chargerReservations();
                }
            });
        });
    }
    
    /**
     * Ouvre la modal avec les détails d'une réservation
     */
    function ouvrirModalReservation(id) {
        // Afficher l'état de chargement
        modalLoading.style.display = 'block';
        modalContent.style.display = 'none';
        
        // Ouvrir la modal
        modalReservation.show();
        
        // Charger les détails de la réservation
        fetch(`../controllers/admin.php?action=recuperer_reservation&id_reservation=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    remplirModalReservation(data.data);
                } else {
                    alert('Erreur lors du chargement des données de la réservation');
                    modalReservation.hide();
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors du chargement des données de la réservation');
                modalReservation.hide();
            });
    }
    
    /**
     * Remplit la modal avec les détails d'une réservation
     */
    function remplirModalReservation(data) {
        const reservation = data.reservation;
        const logement = data.logement;
        const locataire = data.locataire;
        const proprietaire = data.proprietaire;
        const paiement = data.paiement;
        
        // Titre de la modal
        document.getElementById('modalReservationLabel').textContent = `Réservation #${reservation.id}`;
        
        // Informations de réservation
        document.getElementById('reservation-id').textContent = reservation.id;
        document.getElementById('reservation-id-form').value = reservation.id;
        
        // Dates
        const dateDebut = new Date(reservation.date_debut).toLocaleDateString('fr-FR');
        const dateFin = new Date(reservation.date_fin).toLocaleDateString('fr-FR');
        document.getElementById('reservation-dates').textContent = `${dateDebut} - ${dateFin}`;
        
        // Prix
        document.getElementById('reservation-prix').textContent = formaterPrix(reservation.prix_total);
        
        // Statut
        let statut = '';
        let badgeClass = '';
        
        switch (reservation.statut) {
            case 'en_attente':
                statut = 'En attente';
                badgeClass = 'bg-warning';
                break;
            case 'acceptee':
                statut = 'Acceptée';
                badgeClass = 'bg-success';
                break;
            case 'refusee':
                statut = 'Refusée';
                badgeClass = 'bg-danger';
                break;
            case 'annulee':
                statut = 'Annulée';
                badgeClass = 'bg-secondary';
                break;
            case 'terminee':
                statut = 'Terminée';
                badgeClass = 'bg-primary';
                break;
            default:
                statut = reservation.statut;
                badgeClass = 'bg-dark';
        }
        
        document.getElementById('reservation-statut').innerHTML = `<span class="badge ${badgeClass}">${statut}</span>`;
        
        // Date de création
        const dateCreation = new Date(reservation.date_creation).toLocaleString('fr-FR');
        document.getElementById('reservation-creation').textContent = dateCreation;
        
        // Paiement
        if (paiement) {
            document.getElementById('paiement-info').style.display = 'block';
            document.getElementById('paiement-none').style.display = 'none';
            
            document.getElementById('paiement-id').textContent = paiement.id;
            document.getElementById('paiement-montant').textContent = formaterPrix(paiement.montant);
            document.getElementById('paiement-transaction').textContent = paiement.id_transaction || 'N/A';
            
            let paiementStatut = '';
            let paiementClass = '';
            
            switch (paiement.statut) {
                case 'en_attente':
                    paiementStatut = 'En attente';
                    paiementClass = 'bg-warning';
                    break;
                case 'complete':
                    paiementStatut = 'Payé';
                    paiementClass = 'bg-success';
                    break;
                case 'rembourse':
                    paiementStatut = 'Remboursé';
                    paiementClass = 'bg-info';
                    break;
                case 'echoue':
                    paiementStatut = 'Échoué';
                    paiementClass = 'bg-danger';
                    break;
                default:
                    paiementStatut = paiement.statut;
                    paiementClass = 'bg-dark';
            }
            
            document.getElementById('paiement-statut').innerHTML = `<span class="badge ${paiementClass}">${paiementStatut}</span>`;
            
            // Date du paiement
            const datePaiement = new Date(paiement.date_paiement).toLocaleString('fr-FR');
            document.getElementById('paiement-date').textContent = datePaiement;
        } else {
            document.getElementById('paiement-info').style.display = 'none';
            document.getElementById('paiement-none').style.display = 'block';
        }
        
        // Logement
        document.getElementById('logement-titre').textContent = logement.titre;
        document.getElementById('logement-adresse').textContent = `${logement.adresse}, ${logement.code_postal} ${logement.ville}`;
        
        // Type de logement
        let typeLogement = '';
        
        switch (logement.type_logement) {
            case 'entier':
                typeLogement = 'Logement entier';
                break;
            case 'collocation':
                typeLogement = 'Collocation';
                break;
            case 'libere':
                typeLogement = 'Libéré';
                break;
            default:
                typeLogement = logement.type_logement;
        }
        
        document.getElementById('logement-type').textContent = `${typeLogement} - ${logement.nb_places} place(s)`;
        document.getElementById('logement-prix').textContent = `${formaterPrix(logement.prix)} par nuit`;
        
        // Photo du logement
        document.getElementById('logement-photo').src = logement.photo_principale || '/assets/img/placeholders/logement.jpg';
        
        // Locataire
        document.getElementById('locataire-name').textContent = `${locataire.prenom} ${locataire.nom}`;
        document.getElementById('locataire-email').textContent = locataire.email;
        document.getElementById('locataire-phone').textContent = locataire.telephone || 'Aucun numéro de téléphone';
        document.getElementById('locataire-photo').src = locataire.photo_profil || '/assets/img/placeholders/profil.jpg';
        
        // Propriétaire
        document.getElementById('proprietaire-name').textContent = `${proprietaire.prenom} ${proprietaire.nom}`;
        document.getElementById('proprietaire-email').textContent = proprietaire.email;
        document.getElementById('proprietaire-phone').textContent = proprietaire.telephone || 'Aucun numéro de téléphone';
        document.getElementById('proprietaire-photo').src = proprietaire.photo_profil || '/assets/img/placeholders/profil.jpg';
        
        // Statut actuel pour le formulaire
        document.getElementById('statut-form').value = reservation.statut;
        
        // Masquer le chargement et afficher le contenu
        modalLoading.style.display = 'none';
        modalContent.style.display = 'block';
    }
    
    /**
     * Modifie le statut d'une réservation
     */
    function modifierStatutReservation() {
        // Récupérer les données du formulaire
        const idReservation = document.getElementById('reservation-id-form').value;
        const statut = document.getElementById('statut-form').value;
        
        // Désactiver le bouton pendant la requête
        const btnModifier = document.getElementById('btn-modifier-statut');
        btnModifier.disabled = true;
        btnModifier.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Modification...';
        
        // Envoyer la requête
        fetch('../controllers/admin.php?action=modifier_reservation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id_reservation=${idReservation}&statut=${statut}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Statut modifié avec succès');
                    modalReservation.hide();
                    chargerReservations();
                } else {
                    alert(data.message || 'Erreur lors de la modification du statut');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors de la modification du statut');
            })
            .finally(() => {
                // Réactiver le bouton
                btnModifier.disabled = false;
                btnModifier.innerHTML = 'Modifier le statut';
            });
    }
    
    /**
     * Supprime une réservation
     */
    function supprimerReservation() {
        const idReservation = document.getElementById('reservation-id-form').value;
        
        // Désactiver le bouton pendant la requête
        btnSupprimer.disabled = true;
        btnSupprimer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Suppression...';
        
        // Envoyer la requête
        fetch('../controllers/admin.php?action=supprimer_reservation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id_reservation=${idReservation}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Réservation supprimée avec succès');
                    modalReservation.hide();
                    chargerReservations();
                } else {
                    alert(data.message || 'Erreur lors de la suppression de la réservation');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors de la suppression de la réservation');
            })
            .finally(() => {
                // Réactiver le bouton
                btnSupprimer.disabled = false;
                btnSupprimer.innerHTML = 'Supprimer';
            });
    }
    
    /**
     * Exporte la liste des réservations au format CSV
     */
    function exporterCSV() {
        const params = new URLSearchParams();
        
        for (const [key, value] of Object.entries(paramsActuels)) {
            if (value !== '') {
                params.append(key, value);
            }
        }
        
        params.delete('page');
        params.delete('limite');
        params.append('export', 'csv');
        
        window.location.href = '../controllers/admin.php?action=recuperer_reservations&' + params.toString();
    }
    
    /**
     * Formate un prix
     */
    function formaterPrix(prix) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(prix);
    }
});
</script>

<?php
// Inclure le pied de page
include CHEMIN_VUES . '/admin/footer.php';
?>
