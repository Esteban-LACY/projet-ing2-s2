<?php
/**
 * Sidebar pour les pages d'administration
 * 
 * @author OmnesBnB
 */

// Déterminer la page active
$page = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $page === 'index.php' ? 'active' : '' ?>" href="index.php">
                    <i class="bi bi-speedometer2 me-1"></i>
                    Tableau de bord
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'utilisateurs.php' ? 'active' : '' ?>" href="utilisateurs.php">
                    <i class="bi bi-people me-1"></i>
                    Utilisateurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'logements.php' ? 'active' : '' ?>" href="logements.php">
                    <i class="bi bi-house me-1"></i>
                    Logements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'reservations.php' ? 'active' : '' ?>" href="reservations.php">
                    <i class="bi bi-calendar-check me-1"></i>
                    Réservations
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Rapports</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    Revenus
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-graph-up me-1"></i>
                    Statistiques
                </a>
            </li>
        </ul>
    </div>
</nav>
