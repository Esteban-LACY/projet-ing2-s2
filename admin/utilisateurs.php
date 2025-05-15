<?php
/**
 * Gestion des utilisateurs (Administration)
 * 
 * Cette page permet de gérer les utilisateurs de l'application
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_INCLUDES . '/auth.php';

// Vérifier que l'utilisateur est administrateur
if (!estConnecte() || !estAdmin()) {
    rediriger(URL_SITE . '/connexion.php?redirect=admin/utilisateurs.php');
}

// Titre de la page
$titre = 'Gestion des utilisateurs - Administration';

// Paramètres de pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limite = 10;

// Récupérer les filtres
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';
$estAdmin = isset($_GET['est_admin']) ? $_GET['est_admin'] : '';
$estVerifie = isset($_GET['est_verifie']) ? $_GET['est_verifie'] : '';

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
                <h1 class="h2">Gestion des utilisateurs</h1>
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
                            <label for="recherche" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="recherche" name="recherche" placeholder="Nom, prénom ou email..." value="<?= htmlspecialchars($recherche) ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="est_admin" class="form-label">Administrateur</label>
                            <select class="form-select" id="est_admin" name="est_admin">
                                <option value="">Tous</option>
                                <option value="1" <?= $estAdmin === '1' ? 'selected' : '' ?>>Oui</option>
                                <option value="0" <?= $estAdmin === '0' ? 'selected' : '' ?>>Non</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="est_verifie" class="form-label">Email vérifié</label>
                            <select class="form-select" id="est_verifie" name="est_verifie">
                                <option value="">Tous</option>
                                <option value="1" <?= $estVerifie === '1' ? 'selected' : '' ?>>Oui</option>
                                <option value="0" <?= $estVerifie === '0' ? 'selected' : '' ?>>Non</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des utilisateurs -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Vérifié</th>
                                    <th>Admin</th>
                                    <th>Inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="liste-utilisateurs">
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
            
            <!-- Modal détails utilisateur -->
            <div class="modal fade" id="modalUtilisateur" tabindex="-1" aria-labelledby="modalUtilisateurLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalUtilisateurLabel">Détails de l'utilisateur</h5>
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
                                <!-- Informations utilisateur -->
                                <div class="row mb-4">
                                    <div class="col-md-3 text-center">
                                        <img id="user-photo" src="" alt="Photo de profil" class="img-fluid rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                                    </div>
                                    <div class="col-md-9">
                                        <h4 id="user-name"></h4>
                                        <p id="user-email" class="mb-1"></p>
                                        <p id="user-phone" class="mb-1"></p>
                                        <p id="user-status" class="mb-0"></p>
                                    </div>
                                </div>
                                
                                <!-- Formulaire de modification -->
                                <form id="form-utilisateur">
                                    <input type="hidden" id="user-id" name="id_utilisateur">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="nom" name="nom" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="prenom" class="form-label">Prénom</label>
                                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="telephone" class="form-label">Téléphone</label>
                                            <input type="tel" class="form-control" id="telephone" name="telephone">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="est_admin" name="est_admin" value="1">
                                                <label class="form-check-label" for="est_admin">Administrateur</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="est_verifie" name="est_verifie" value="1">
                                                <label class="form-check-label" for="est_verifie">Email vérifié</label>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Statistiques -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Logements</h5>
                                                <p class="display-6" id="user-logements">0</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Réservations</h5>
                                                <p class="display-6" id="user-reservations">0</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Bilan</h5>
                                                <p class="display-6" id="user-bilan">0€</p>
                                            </div>
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
    const listeUtilisateurs = document.getElementById('liste-utilisateurs');
    const pagination = document.getElementById('pagination');
    const modalUtilisateur = new bootstrap.Modal(document.getElementById('modalUtilisateur'));
    const btnEnregistrer = document.getElementById('btn-enregistrer');
    const btnSupprimer = document.getElementById('btn-supprimer');
    const formUtilisateur = document.getElementById('form-utilisateur');
    const modalLoading = document.getElementById('modal-loading');
    const modalContent = document.getElementById('modal-content');
    
    // Paramètres courants
    let paramsActuels = {
        page: <?= $page ?>,
        limite: <?= $limite ?>,
        recherche: <?= json_encode($recherche) ?>,
        est_admin: <?= json_encode($estAdmin) ?>,
        est_verifie: <?= json_encode($estVerifie) ?>
    };
    
    // Charger les utilisateurs
    chargerUtilisateurs();
    
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
        window.history.pushState({}, '', 'utilisateurs.php?' + new URLSearchParams(paramsActuels).toString());
        
        // Recharger les utilisateurs
        chargerUtilisateurs();
    });
    
    // Exportation CSV
    document.getElementById('btn-export-csv').addEventListener('click', function() {
        exporterCSV();
    });
    
    // Événements de la modal
    btnEnregistrer.addEventListener('click', function() {
        enregistrerUtilisateur();
    });
    
    btnSupprimer.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
            supprimerUtilisateur();
        }
    });
    
    /**
     * Charge la liste des utilisateurs via une requête AJAX
     */
    function chargerUtilisateurs() {
        const params = new URLSearchParams();
        
        for (const [key, value] of Object.entries(paramsActuels)) {
            if (value !== '') {
                params.append(key, value);
            }
        }
        
        // Afficher un état de chargement
        listeUtilisateurs.innerHTML = '<tr><td colspan="8" class="text-center">Chargement...</td></tr>';
        pagination.innerHTML = '<li class="page-item disabled"><a class="page-link" href="#">Chargement...</a></li>';
        
        fetch('../controllers/admin.php?action=recuperer_utilisateurs&' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    afficherUtilisateurs(data.utilisateurs, data.total, data.page, data.limite, data.total_pages);
                } else {
                    listeUtilisateurs.innerHTML = '<tr><td colspan="8" class="text-center">Erreur lors du chargement des données</td></tr>';
                    pagination.innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                listeUtilisateurs.innerHTML = '<tr><td colspan="8" class="text-center">Erreur lors du chargement des données</td></tr>';
                pagination.innerHTML = '';
            });
    }
    
   /**
     * Affiche la liste des utilisateurs dans le tableau
     */
    function afficherUtilisateurs(utilisateurs, total, page, limite, totalPages) {
        if (utilisateurs.length === 0) {
            listeUtilisateurs.innerHTML = '<tr><td colspan="8" class="text-center">Aucun utilisateur trouvé</td></tr>';
            pagination.innerHTML = '';
            return;
        }
        
        let html = '';
        
        utilisateurs.forEach(utilisateur => {
            const dateInscription = new Date(utilisateur.date_creation).toLocaleDateString('fr-FR');
            
            html += `
                <tr>
                    <td>${utilisateur.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${utilisateur.photo_profil || '../assets/img/placeholders/profil.jpg'}" class="rounded-circle me-2" width="32" height="32" alt="">
                            ${utilisateur.prenom} ${utilisateur.nom}
                        </div>
                    </td>
                    <td>${utilisateur.email}</td>
                    <td>${utilisateur.telephone || '-'}</td>
                    <td>
                        <span class="badge ${utilisateur.est_verifie ? 'bg-success' : 'bg-warning'}">
                            ${utilisateur.est_verifie ? 'Oui' : 'Non'}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${utilisateur.est_admin ? 'bg-primary' : 'bg-secondary'}">
                            ${utilisateur.est_admin ? 'Oui' : 'Non'}
                        </span>
                    </td>
                    <td>${dateInscription}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-details" data-id="${utilisateur.id}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        listeUtilisateurs.innerHTML = html;
        
        // Ajouter les événements sur les boutons de détails
        document.querySelectorAll('.btn-details').forEach(button => {
            button.addEventListener('click', function() {
                const idUtilisateur = this.getAttribute('data-id');
                ouvrirModalUtilisateur(idUtilisateur);
            });
        });
        
        // Créer la pagination
        creerPagination(page, totalPages);
    }
    
    /**
     * Crée les liens de pagination
     */
    function creerPagination(page, totalPages) {
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Bouton précédent
        html += `
            <li class="page-item ${page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${page - 1}" aria-label="Précédent">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;
        
        // Pages
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, page + 2);
        
        // Afficher la première page si on n'y est pas
        if (startPage > 1) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
            `;
            
            if (startPage > 2) {
                html += `
                    <li class="page-item disabled">
                        <a class="page-link" href="#">...</a>
                    </li>
                `;
            }
        }
        
        // Pages centrales
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // Afficher la dernière page si on n'y est pas
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `
                    <li class="page-item disabled">
                        <a class="page-link" href="#">...</a>
                    </li>
                `;
            }
            
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                </li>
            `;
        }
        
        // Bouton suivant
        html += `
            <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${page + 1}" aria-label="Suivant">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        
        pagination.innerHTML = html;
        
        // Ajouter les événements sur les liens de pagination
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.parentNode.classList.contains('disabled')) {
                    return;
                }
                
                const page = parseInt(this.getAttribute('data-page'));
                paramsActuels.page = page;
                
                // Mettre à jour l'URL
                window.history.pushState({}, '', 'utilisateurs.php?' + new URLSearchParams(paramsActuels).toString());
                
                // Recharger les utilisateurs
                chargerUtilisateurs();
            });
        });
    }
    
    /**
     * Ouvre la modal avec les détails d'un utilisateur
     */
    function ouvrirModalUtilisateur(idUtilisateur) {
        // Réinitialiser le formulaire
        formUtilisateur.reset();
        
        // Afficher le chargement
        modalLoading.style.display = 'block';
        modalContent.style.display = 'none';
        
        // Ouvrir la modal
        modalUtilisateur.show();
        
        // Charger les données de l'utilisateur
        fetch(`../controllers/admin.php?action=recuperer_utilisateur&id_utilisateur=${idUtilisateur}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    afficherDetailsUtilisateur(data.data);
                } else {
                    alert('Erreur lors du chargement des détails de l\'utilisateur');
                    modalUtilisateur.hide();
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors du chargement des détails de l\'utilisateur');
                modalUtilisateur.hide();
            });
    }
    
    /**
     * Affiche les détails d'un utilisateur dans la modal
     */
    function afficherDetailsUtilisateur(data) {
        const utilisateur = data.utilisateur;
        
        // Informations générales
        document.getElementById('user-id').value = utilisateur.id;
        document.getElementById('user-photo').src = utilisateur.photo_profil || '../assets/img/placeholders/profil.jpg';
        document.getElementById('user-name').textContent = `${utilisateur.prenom} ${utilisateur.nom}`;
        document.getElementById('user-email').textContent = utilisateur.email;
        document.getElementById('user-phone').textContent = utilisateur.telephone || 'Pas de téléphone';
        
        // Statut
        let statut = '';
        if (utilisateur.est_admin) {
            statut += '<span class="badge bg-primary me-1">Administrateur</span>';
        }
        if (utilisateur.est_verifie) {
            statut += '<span class="badge bg-success me-1">Email vérifié</span>';
        } else {
            statut += '<span class="badge bg-warning me-1">Email non vérifié</span>';
        }
        statut += `<span class="badge bg-info">Inscrit le ${new Date(utilisateur.date_creation).toLocaleDateString('fr-FR')}</span>`;
        document.getElementById('user-status').innerHTML = statut;
        
        // Formulaire
        document.getElementById('nom').value = utilisateur.nom;
        document.getElementById('prenom').value = utilisateur.prenom;
        document.getElementById('email').value = utilisateur.email;
        document.getElementById('telephone').value = utilisateur.telephone || '';
        document.getElementById('est_admin').checked = utilisateur.est_admin;
        document.getElementById('est_verifie').checked = utilisateur.est_verifie;
        
        // Statistiques
        document.getElementById('user-logements').textContent = data.logements.length;
        document.getElementById('user-reservations').textContent = data.reservations_locataire.length + data.reservations_proprietaire.length;
        document.getElementById('user-bilan').textContent = formatMonetaire(data.bilan_financier.solde);
        
        // Masquer le chargement et afficher le contenu
        modalLoading.style.display = 'none';
        modalContent.style.display = 'block';
    }
    
    /**
     * Enregistre les modifications d'un utilisateur
     */
    function enregistrerUtilisateur() {
        // Désactiver le bouton pendant l'enregistrement
        btnEnregistrer.disabled = true;
        btnEnregistrer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
        
        // Récupérer les données du formulaire
        const formData = new FormData(formUtilisateur);
        
        // Ajouter les valeurs des checkboxes (si non cochées)
        if (!formData.has('est_admin')) {
            formData.append('est_admin', '0');
        }
        if (!formData.has('est_verifie')) {
            formData.append('est_verifie', '0');
        }
        
        // Envoyer la requête
        fetch('../controllers/admin.php?action=modifier_utilisateur', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                btnEnregistrer.disabled = false;
                btnEnregistrer.innerHTML = 'Enregistrer';
                
                if (data.success) {
                    // Fermer la modal
                    modalUtilisateur.hide();
                    
                    // Recharger les utilisateurs
                    chargerUtilisateurs();
                    
                    // Afficher un message de succès
                    alert('Utilisateur modifié avec succès');
                } else {
                    alert(data.message || 'Erreur lors de la modification de l\'utilisateur');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors de la modification de l\'utilisateur');
                
                // Réactiver le bouton
                btnEnregistrer.disabled = false;
                btnEnregistrer.innerHTML = 'Enregistrer';
            });
    }
    
    /**
     * Supprime un utilisateur
     */
    function supprimerUtilisateur() {
        // Désactiver le bouton pendant la suppression
        btnSupprimer.disabled = true;
        btnSupprimer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Suppression...';
        
        // Récupérer l'ID de l'utilisateur
        const idUtilisateur = document.getElementById('user-id').value;
        
        // Envoyer la requête
        fetch('../controllers/admin.php?action=supprimer_utilisateur', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_utilisateur=${idUtilisateur}`
        })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                btnSupprimer.disabled = false;
                btnSupprimer.innerHTML = 'Supprimer';
                
                if (data.success) {
                    // Fermer la modal
                    modalUtilisateur.hide();
                    
                    // Recharger les utilisateurs
                    chargerUtilisateurs();
                    
                    // Afficher un message de succès
                    alert('Utilisateur supprimé avec succès');
                } else {
                    alert(data.message || 'Erreur lors de la suppression de l\'utilisateur');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
                alert('Erreur lors de la suppression de l\'utilisateur');
                
                // Réactiver le bouton
                btnSupprimer.disabled = false;
                btnSupprimer.innerHTML = 'Supprimer';
            });
    }
    
    /**
     * Exporte la liste des utilisateurs au format CSV
     */
    function exporterCSV() {
        const params = new URLSearchParams();
        
        for (const [key, value] of Object.entries(paramsActuels)) {
            if (value !== '' && key !== 'page' && key !== 'limite') {
                params.append(key, value);
            }
        }
        
        // Rediriger vers l'export CSV
        window.location.href = `../controllers/admin.php?action=exporter_utilisateurs_csv&${params.toString()}`;
    }
    
    /**
     * Formate un montant en euros
     */
    function formatMonetaire(montant) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(montant);
    }
});
</script>

<?php
// Inclure le pied de page
include CHEMIN_VUES . '/admin/footer.php';
?>
