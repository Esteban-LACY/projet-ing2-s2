<?php
/**
 * Page d'accueil de l'administration
 * 
 * Cette page affiche le tableau de bord de l'administration avec diverses statistiques
 * 
 * @author OmnesBnB
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once CHEMIN_INCLUDES . '/auth.php';

// Vérifier que l'utilisateur est administrateur
if (!estConnecte() || !estAdmin()) {
    rediriger(URL_SITE . '/connexion.php?redirect=admin');
}

// Titre de la page
$titre = 'Tableau de bord - Administration';

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
                <h1 class="h2">Tableau de bord</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Cartes statistiques -->
            <div class="row mb-4">
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Utilisateurs</h5>
                            <p class="card-text display-4" id="stats-utilisateurs">...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Total des utilisateurs inscrits</small>
                                <a href="utilisateurs.php" class="btn btn-sm btn-primary">Voir tous</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Logements</h5>
                            <p class="card-text display-4" id="stats-logements">...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Total des logements publiés</small>
                                <a href="logements.php" class="btn btn-sm btn-primary">Voir tous</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Réservations</h5>
                            <p class="card-text display-4" id="stats-reservations">...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Total des réservations</small>
                                <a href="reservations.php" class="btn btn-sm btn-primary">Voir toutes</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Revenus</h5>
                            <p class="card-text display-4" id="stats-revenus">...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Frais de service perçus</small>
                                <button class="btn btn-sm btn-primary" disabled>Détails</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Graphiques -->
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Évolution des inscriptions</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chart-inscriptions" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Réservations par statut</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chart-reservations" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dernières activités et villes populaires -->
            <div class="row mb-4">
                <div class="col-md-8 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Dernières activités</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Utilisateur</th>
                                            <th>Action</th>
                                            <th>Détails</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activites-recentes">
                                        <tr>
                                            <td colspan="4" class="text-center">Chargement...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Villes populaires</h5>
                        </div>
                        <div class="card-body">
                            <div id="villes-populaires">Chargement...</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Charger les statistiques initiales
    chargerStatistiques();
    
    // Actualiser les statistiques lorsqu'on clique sur le bouton
    document.getElementById('btn-refresh').addEventListener('click', function() {
        chargerStatistiques();
    });
    
    /**
     * Charge les statistiques via une requête AJAX
     */
    function chargerStatistiques() {
        fetch('../controllers/admin.php?action=statistiques')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    afficherStatistiques(data.statistiques);
                } else {
                    console.error('Erreur lors du chargement des statistiques');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la requête:', error);
            });
    }
    
    /**
     * Affiche les statistiques dans l'interface
     */
    function afficherStatistiques(stats) {
        // Mettre à jour les compteurs
        document.getElementById('stats-utilisateurs').textContent = stats.utilisateurs.total;
        document.getElementById('stats-logements').textContent = stats.logements.total;
        document.getElementById('stats-reservations').textContent = stats.reservations.total;
        document.getElementById('stats-revenus').textContent = formaterPrix(stats.paiements.frais_service);
        
        // Créer le graphique des inscriptions
        creerGraphiqueInscriptions(stats.utilisateurs_par_mois);
        
        // Créer le graphique des réservations
        creerGraphiqueReservations(stats.reservations);
        
        // Afficher les villes populaires
        afficherVillesPopulaires(stats.villes_populaires);
        
        // Afficher les activités récentes
        afficherActivitesRecentes(stats.activites_recentes);
    }
    
    /**
     * Crée le graphique d'évolution des inscriptions
     */
    function creerGraphiqueInscriptions(donnees) {
        const ctx = document.getElementById('chart-inscriptions').getContext('2d');
        
        if (window.chartInscriptions) {
            window.chartInscriptions.destroy();
        }
        
        window.chartInscriptions = new Chart(ctx, {
            type: 'line',
            data: {
                labels: donnees.map(item => item.mois),
                datasets: [{
                    label: 'Inscriptions',
                    data: donnees.map(item => item.nombre),
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Crée le graphique des réservations par statut
     */
    function creerGraphiqueReservations(donnees) {
        const ctx = document.getElementById('chart-reservations').getContext('2d');
        
        if (window.chartReservations) {
            window.chartReservations.destroy();
        }
        
        window.chartReservations = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['En attente', 'Acceptées', 'Refusées', 'Annulées', 'Terminées'],
                datasets: [{
                    data: [
                        donnees.en_attente,
                        donnees.acceptees,
                        donnees.refusees,
                        donnees.annulees,
                        donnees.terminees
                    ],
                    backgroundColor: [
                        'rgba(255, 206, 86, 0.7)',   // Jaune
                        'rgba(75, 192, 192, 0.7)',   // Vert
                        'rgba(255, 99, 132, 0.7)',   // Rouge
                        'rgba(153, 102, 255, 0.7)',  // Violet
                        'rgba(54, 162, 235, 0.7)'    // Bleu
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    /**
     * Affiche les villes populaires
     */
    function afficherVillesPopulaires(villes) {
        const container = document.getElementById('villes-populaires');
        
        if (!villes || villes.length === 0) {
            container.innerHTML = '<p class="text-center">Aucune donnée disponible</p>';
            return;
        }
        
        let html = '<ul class="list-group list-group-flush">';
        
        villes.forEach(ville => {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${ville.ville}
                    <span class="badge bg-primary rounded-pill">${ville.nb_logements}</span>
                </li>
            `;
        });
        
        html += '</ul>';
        
        container.innerHTML = html;
    }
    
    /**
     * Affiche les activités récentes
     */
    function afficherActivitesRecentes(activites) {
        const tbody = document.getElementById('activites-recentes');
        
        if (!activites || activites.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Aucune activité récente</td></tr>';
            return;
        }
        
        let html = '';
        
        activites.forEach(activite => {
            html += `
                <tr>
                    <td>${formaterDate(activite.date)}</td>
                    <td>
                        <a href="utilisateurs.php?id=${activite.id_utilisateur}">
                            ${activite.prenom} ${activite.nom}
                        </a>
                    </td>
                    <td>${activite.action}</td>
                    <td>${activite.details}</td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
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
    
    /**
     * Formate une date
     */
    function formaterDate(dateStr) {
        const date = new Date(dateStr);
        return new Intl.DateTimeFormat('fr-FR', {
            dateStyle: 'short',
            timeStyle: 'short'
        }).format(date);
    }
});
</script>

<?php
// Inclure le pied de page
include CHEMIN_VUES . '/admin/footer.php';
?>
