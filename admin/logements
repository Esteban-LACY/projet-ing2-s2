<?php
/**
 * Gestion des logements (Administration)
 * 
 * Cette page permet de gérer les logements de l'application
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_INCLUDES . '/auth.php';

// Vérifier que l'utilisateur est administrateur
if (!estConnecte() || !estAdmin()) {
    rediriger(URL_SITE . '/connexion.php?redirect=admin/logements.php');
}

// Titre de la page
$titre = 'Gestion des logements - Administration';

// Paramètres de pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limite = 10;

// Récupérer les filtres
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';
$ville = isset($_GET['ville']) ? $_GET['ville'] : '';
$typeLogement = isset($_GET['type_logement']) ? $_GET['type_logement'] : '';
$idProprietaire = isset($_GET['id_proprietaire']) ? $_GET['id_proprietaire'] : '';

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
                <h1 class="h2">Gestion des logements</h1>
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
                        <div class="col-md-3">
                            <label for="recherche" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="recherche" name="recherche" placeholder="Titre ou description..." value="<?= htmlspecialchars($recherche) ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="ville" class="form-label">Ville</label>
                            <input type="text" class="form-control" id="ville" name="ville" placeholder="Ville..." value="<?= htmlspecialchars($ville) ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="type_logement" class="form-label">Type de logement</label>
                            <select class="form-select" id="type_logement" name="type_logement">
                                <option value="">Tous</option>
                                <option value="entier" <?= $typeLogement === 'entier' ? 'selected' : '' ?>>Logement entier</option>
                                <option value="collocation" <?= $typeLogement === 'collocation' ? 'selected' : '' ?>>Collocation</option>
                                <option value="libere" <?= $typeLogement === 'libere' ? 'selected' : '' ?>>Libéré</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="id_proprietaire" class="form-label">Propriétaire (ID)</label>
                            <input type="number" class="form-control" id="id_proprietaire" name="id_proprietaire" placeholder="ID du propriétaire..." value="<?= htmlspecialchars($idProprietaire) ?>">
                        </div>
                        
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des logements -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Photo</th>
                                    <th>Titre</th>
                                    <th>Ville</th>
                                    <th>Type</th>
                                    <th>Prix</th>
                                    <th>Propriétaire</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="liste-logements">
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
            
            <!-- Modal détails logement -->
            <div class="modal fade" id="modalLogement" tabindex="-1" aria-labelledby="modalLogementLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalLogementLabel">Détails du logement</h5>
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
                                <!-- Carousel des photos -->
                                <div id="carousel-photos" class="carousel slide mb-4" data-bs-ride="carousel">
                                    <div class="carousel-inner" id="carousel-inner">
                                        <!-- Les photos seront ajoutées ici dynamiquement -->
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel-photos" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Précédent</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carousel-photos" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Suivant</span>
                                    </button>
                                </div>
                                
                                <!-- Formulaire de modification -->
                                <form id="form-logement">
                                    <input type="hidden" id="logement-id" name="id_logement">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label for="titre" class="form-label">Titre</label>
                                            <input type="text" class="form-control" id="titre" name="titre" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="prix" class="form-label">Prix par nuit (€)</label>
                                            <input type="number" class="form-control" id="prix" name="prix" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="adresse" class="form-label">Adresse</label>
                                            <input type="text" class="form-control" id="adresse" name="adresse" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="ville" class="form-label">Ville</label>
                                            <input type="text" class="form-control" id="ville" name="ville" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="code_postal" class="form-label">Code postal</label>
                                            <input type="text" class="form-control" id="code_postal" name="code_postal" required pattern="[0-9]{5}">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="type_logement" class="form-label">Type de logement</label>
                                            <select class="form-select" id="type_logement" name="type_logement" required>
                                                <option value="entier">Logement entier</option>
                                                <option value="collocation">Collocation</option>
                                                <option value="libere">Libéré</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nb_places" class="form-label">Nombre de places</label>
                                            <input type="number" class="form-control" id="nb_places" name="nb_places" min="1" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="latitude" class="form-label">Latitude</label>
                                            <input type="text" class="form-control" id="latitude" name="latitude" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="longitude" class="form-label">Longitude</label>
                                            <input type="text" class="form-control" id="longitude" name="longitude" readonly>
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Informations propriétaire -->
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">Propriétaire</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2 text-center">
                                                <img id="proprietaire-photo" src="" alt="Photo de profil" class="img-fluid rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                            </div>
                                            <div class="col-md-10">
                                                <h5 id="proprietaire-name"></h5>
                                                <p id="proprietaire-email" class="mb-1"></p>
                                                <p id="proprietaire-phone" class="mb-0"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Liste des réservations -->
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">Réservations</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Locataire</th>
                                                        <th>Dates</th>
                                                        <th>Prix</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="liste-reservations">
                                                    <tr>
                                                        <td colspan="5" class="text-center">Aucune réservation</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger me-auto" id="btn-supprimer">Supprimer</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="button" class="btn btn-primary" id="btn-enregistrer">Enregistrer</button>
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
    const listeLogements = document.getElementById('liste-logements');
    const pagination = document.getElementById('pagination');
    const modalLogement = new bootstrap.Modal(document.getElementById('modalLogement'));
    const btnEnregistrer = document.getElementById('btn-enregistrer');
    const btnSupprimer = document.getElementById('btn-supprimer');
    const formLogement = document.getElementById('form-logement');
    const modalLoading = document.getElementById('modal-loading');
    const modalContent = document.getElementById('modal-content');
    
    // Paramètres courants
    let paramsActuels = {
        page: <?= $page ?>,
        limite: <?= $limite ?>,
        recherche: <?= json_encode($recherche) ?>,
        ville: <?= json_encode($ville) ?>,
        type_logement: <?= json_encode($typeLogement) ?>,
        id_proprietaire: <?= json_encode($idProprietaire) ?>
    };
    
    // Charger les logements
    chargerLogements();
    
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
        window.history.pushState({}, '', 'logements.php?' + new URLSearchParams(paramsActuels).toString());
        
        // Recharger les logements
        chargerLogements();
    });
    
    // Exportation CSV
    document.getElementById('btn-export-csv').addEventListener('click', function() {
        exporterCSV();
    });
    
    // Événements de la modal
    btnEnregistrer.addEventListener('click', function() {
        enregistrerLogement();
    });
    
    btnSupprimer.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce logement ? Cette action est irréversible.')) {
            supprimerLogement();
        }
    });
    
    /**
     * Charge la liste des logements via une requête AJAX
     */
    function chargerLogements() {
        const params = new URLSearchParams();
        
        for (const [key, value] of Object.entries(paramsActuels)) {
            if (value !== '') {
                params.append(key, value);
            }
        }
        
        // Afficher un état de chargement
        listeLogements.innerHTML = '<tr><td colspan="8" class="text-center">Chargement...</td></tr>';
        pagination.innerHTML = '<li class="page-item disabled"><a class="page-link" href="#">Chargement...</a></li>';
        
        fetch('../controllers/admin.php?action=recuperer_logements&' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    afficherLogements(data.logements, data.total, data.page, data.limite, data.total_pages);
                } else {
                    listeLogements.innerHTML = '<tr><td colspan="8" class="text-center">Erreur lors du chargement des données</td></tr>';
                    pagination.innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                listeLogements.innerHTML = '<tr><td colspan="8" class="text-center">Erreur lors du chargement des données</td></tr>';
                pagination.innerHTML = '';
            });
    }
    
    /**
     * Affiche la liste des logements dans le tableau
     */
    function afficherLogements(logements, total, page, limite, totalPages) {
        if (logements.length === 0) {
            listeLogements.innerHTML = '<tr><td colspan="8" class="text-center">Aucun logement trouvé</td></tr>';
            pagination.innerHTML = '';
            return;
        }
        
        let html = '';
        
        logements.forEach(logement => {
            // Formater le prix
            const prix = formaterPrix(logement.prix);
            
            // Déterminer le type de logement
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
            
            html += `
                <tr>
                    <td>${logement.id}</td>
                    <td>
                        <img src="${logement.photo_principale || '/assets/img/placeholders/logement.jpg'}" alt="${logement.titre}" class="img-thumbnail" width="80">
                    </td>
                    <td>${logement.titre}</td>
                    <td>${logement.ville}</td>
                    <td>${typeLogement}</td>
                    <td>${prix}</td>
                    <td>${logement.proprietaire}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary btn-details" data-id="${logement.id}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        listeLogements.innerHTML = html;
        
        // Ajouter les événements sur les boutons de détails
        document.querySelectorAll('.btn-details').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                ouvrirModalLogement(id);
            });
        });
        
        // Construire la pagination
        construirePagination(page, totalPages);
        
        // Afficher le nombre total de logements
        const debut = (page - 1) * limite + 1;
        const fin = Math.min(page * limite, total);
        
        document.querySelector('.card-body').insertAdjacentHTML('afterbegin', `
            <p class="text-muted mb-3">Affichage de ${debut} à ${fin} sur ${total} logements</p>
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
                    window.history.pushState({}, '', 'logements.php?' + new URLSearchParams(paramsActuels).toString());
                    
                    // Recharger les logements
                    chargerLogements();
                }
            });
        });
    }
    
    /**
     * Ouvre la modal avec les détails d'un logement
     */
    function ouvrirModalLogement(id) {
        // Afficher l'état de chargement
        modalLoading.style.display = 'block';
        modalContent.style.display = 'none';
        
        // Ouvrir la modal
        modalLogement.show();
        
        // Charger les détails du logement
        fetch(`../controllers/admin.php?action=recuperer_logement&id_logement=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    remplirModalLogement(data.data);
                } else {
                    alert('Erreur lors du chargement des données du logement');
                    modalLogement.hide();
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors du chargement des données du logement');
                modalLogement.hide();
            });
    }
    
    /**
     * Remplit la modal avec les détails d'un logement
     */
    function remplirModalLogement(data) {
        const logement = data.logement;
        const proprietaire = data.proprietaire;
        const photos = data.photos;
        const reservations = data.reservations;
        
        // Titre de la modal
        document.getElementById('modalLogementLabel').textContent = logement.titre;
        
        // Formualaire
        document.getElementById('logement-id').value = logement.id;
        document.getElementById('titre').value = logement.titre;
        document.getElementById('description').value = logement.description;
        document.getElementById('adresse').value = logement.adresse;
        document.getElementById('ville').value = logement.ville;
        document.getElementById('code_postal').value = logement.code_postal;
        document.getElementById('prix').value = logement.prix;
        document.getElementById('type_logement').value = logement.type_logement;
        document.getElementById('nb_places').value = logement.nb_places;
        document.getElementById('latitude').value = logement.latitude;
        document.getElementById('longitude').value = logement.longitude;
        
        // Carousel des photos
        const carouselInner = document.getElementById('carousel-inner');
        carouselInner.innerHTML = '';
        
        if (photos.length === 0) {
            carouselInner.innerHTML = `
                <div class="carousel-item active">
                    <img src="/assets/img/placeholders/logement.jpg" class="d-block w-100" alt="Aucune photo" style="height: 400px; object-fit: cover;">
                </div>
            `;
        } else {
            photos.forEach((photo, index) => {
                carouselInner.innerHTML += `
                    <div class="carousel-item ${index === 0 ? 'active' : ''}">
                        <img src="${photo.url}" class="d-block w-100" alt="${logement.titre}" style="height: 400px; object-fit: cover;">
                    </div>
                `;
            });
        }
        
        // Informations propriétaire
        document.getElementById('proprietaire-name').textContent = `${proprietaire.prenom} ${proprietaire.nom}`;
        document.getElementById('proprietaire-email').textContent = proprietaire.email;
        document.getElementById('proprietaire-phone').textContent = proprietaire.telephone || 'Aucun numéro de téléphone';
        document.getElementById('proprietaire-photo').src = proprietaire.photo_profil || '/assets/img/placeholders/profil.jpg';
        
        // Liste des réservations
        const listeReservations = document.getElementById('liste-reservations');
        
        if (reservations.length === 0) {
            listeReservations.innerHTML = '<tr><td colspan="5" class="text-center">Aucune réservation</td></tr>';
        } else {
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
                
                html += `
                    <tr>
                        <td>${reservation.id}</td>
                        <td>${reservation.locataire.prenom} ${reservation.locataire.nom}</td>
                        <td>${dateDebut} - ${dateFin}</td>
                        <td>${prix}</td>
                        <td><span class="badge ${badgeClass}">${statut}</span></td>
                    </tr>
                `;
            });
            
            listeReservations.innerHTML = html;
        }
        
        // Masquer le chargement et afficher le contenu
        modalLoading.style.display = 'none';
        modalContent.style.display = 'block';
    }
    
    /**
     * Enregistre les modifications d'un logement
     */
    function enregistrerLogement() {
        // Valider le formulaire
        if (!formLogement.checkValidity()) {
            formLogement.reportValidity();
            return;
        }
        
        // Récupérer les données du formulaire
        const formData = new FormData(formLogement);
        
        // Désactiver le bouton pendant la requête
        btnEnregistrer.disabled = true;
        btnEnregistrer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
        
        // Envoyer la requête
        fetch('../controllers/admin.php?action=modifier_logement', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Logement modifié avec succès');
                    modalLogement.hide();
                    chargerLogements();
                } else {
                    alert(data.message || 'Erreur lors de la modification du logement');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors de la modification du logement');
            })
            .finally(() => {
                // Réactiver le bouton
                btnEnregistrer.disabled = false;
                btnEnregistrer.innerHTML = 'Enregistrer';
            });
    }
    
    /**
     * Supprime un logement
     */
    function supprimerLogement() {
        const idLogement = document.getElementById('logement-id').value;
        
        // Désactiver le bouton pendant la requête
        btnSupprimer.disabled = true;
        btnSupprimer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Suppression...';
        
        // Envoyer la requête
        fetch('../controllers/admin.php?action=supprimer_logement', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id_logement=${idLogement}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Logement supprimé avec succès');
                    modalLogement.hide();
                    chargerLogements();
                } else {
                    alert(data.message || 'Erreur lors de la suppression du logement');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors de la suppression du logement');
            })
            .finally(() => {
                // Réactiver le bouton
                btnSupprimer.disabled = false;
                btnSupprimer.innerHTML = 'Supprimer';
            });
    }
    
    /**
     * Exporte la liste des logements au format CSV
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
        
        window.location.href = '../controllers/admin.php?action=recuperer_logements&' + params.toString();
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
