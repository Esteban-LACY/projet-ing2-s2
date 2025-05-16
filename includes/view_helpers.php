<?php
/**
* Fonctions d'assistance pour les vues
* 
* Ce fichier contient des fonctions utilitaires pour l'affichage et le formatage des données dans les vues
* 
* @author OmnesBnB
*/

/**
* Formate un prix pour l'affichage
* 
* @param float $prix Prix à formater
* @param bool $avecDevise Inclure le symbole de devise
* @return string Prix formaté
*/
function formaterPrix($prix, $avecDevise = true) {
   return number_format($prix, 2, ',', ' ') . ($avecDevise ? ' €' : '');
}

/**
* Formate une date pour l'affichage
* 
* @param string $date Date au format SQL (Y-m-d)
* @param string $format Format de date souhaité
* @return string Date formatée
*/
function formaterDate($date, $format = 'd/m/Y') {
   if (empty($date)) {
       return '';
   }
   
   $timestamp = strtotime($date);
   
   if ($timestamp === false) {
       return '';
   }
   
   return date($format, $timestamp);
}

/**
* Tronque un texte à une longueur donnée
* 
* @param string $texte Texte à tronquer
* @param int $longueur Longueur maximale
* @param string $suite Caractères à ajouter en fin de texte tronqué
* @return string Texte tronqué
*/
function tronquerTexte($texte, $longueur = 100, $suite = '...') {
   if (mb_strlen($texte) <= $longueur) {
       return $texte;
   }
   
   $texte = mb_substr($texte, 0, $longueur);
   $dernierEspace = mb_strrpos($texte, ' ');
   
   if ($dernierEspace !== false) {
       $texte = mb_substr($texte, 0, $dernierEspace);
   }
   
   return $texte . $suite;
}

/**
* Génère une URL complète
* 
* @param string $chemin Chemin relatif (optionnel)
* @return string URL complète
*/
function url($chemin = '') {
   return rtrim(URL_SITE, '/') . '/' . ltrim($chemin, '/');
}

/**
* Génère une URL pour un asset
* 
* @param string $chemin Chemin relatif de l'asset
* @return string URL complète de l'asset
*/
function urlAsset($chemin = '') {
   return url('assets/' . ltrim($chemin, '/'));
}

/**
* Génère une URL pour une photo de logement
* 
* @param string $url URL relative de la photo
* @return string URL complète de la photo ou image par défaut
*/
function urlPhotoLogement($url) {
   return empty($url) ? urlAsset('img/placeholders/logement.jpg') : url($url);
}

/**
* Génère une URL pour une photo de profil
* 
* @param string $url URL relative de la photo
* @return string URL complète de la photo ou image par défaut
*/
function urlPhotoProfil($url) {
   return empty($url) ? urlAsset('img/placeholders/profil.jpg') : url($url);
}

/**
* Affiche les messages d'erreur d'un formulaire
* 
* @param array $erreurs Tableau d'erreurs
* @param string $classe Classe CSS pour le conteneur (optionnel)
* @return void
*/
function afficherErreurs($erreurs, $classe = 'alert alert-danger') {
   if (empty($erreurs)) {
       return;
   }
   
   echo '<div class="' . $classe . '"><ul>';
   
   foreach ($erreurs as $erreur) {
       echo '<li>' . $erreur . '</li>';
   }
   
   echo '</ul></div>';
}

/**
* Affiche un message de succès
* 
* @param string $message Message à afficher
* @param string $classe Classe CSS (optionnel)
* @return void
*/
function afficherSucces($message, $classe = 'alert alert-success') {
   if (empty($message)) {
       return;
   }
   
   echo '<div class="' . $classe . '">' . $message . '</div>';
}

/**
* Affiche un message d'information
* 
* @param string $message Message à afficher
* @param string $classe Classe CSS (optionnel)
* @return void
*/
function afficherInfo($message, $classe = 'alert alert-info') {
   if (empty($message)) {
       return;
   }
   
   echo '<div class="' . $classe . '">' . $message . '</div>';
}

/**
* Affiche un message d'avertissement
* 
* @param string $message Message à afficher
* @param string $classe Classe CSS (optionnel)
* @return void
*/
function afficherAvertissement($message, $classe = 'alert alert-warning') {
   if (empty($message)) {
       return;
   }
   
   echo '<div class="' . $classe . '">' . $message . '</div>';
}

/**
* Génère des options pour un select à partir d'un tableau
* 
* @param array $options Tableau d'options (valeur => texte)
* @param string|int $valeurSelectionnee Valeur actuellement sélectionnée
* @return string HTML des options
*/
function genererOptions($options, $valeurSelectionnee = '') {
   $html = '';
   
   foreach ($options as $valeur => $texte) {
       $selected = ($valeur == $valeurSelectionnee) ? ' selected' : '';
       $html .= '<option value="' . htmlspecialchars($valeur) . '"' . $selected . '>' . htmlspecialchars($texte) . '</option>';
   }
   
   return $html;
}

/**
* Génère un badge de statut avec couleur appropriée
* 
* @param string $statut Statut à afficher
* @return string HTML du badge
*/
function genererBadgeStatut($statut) {
   $classes = [
       'en_attente' => 'bg-warning',
       'acceptee' => 'bg-success',
       'refusee' => 'bg-danger',
       'annulee' => 'bg-secondary',
       'terminee' => 'bg-primary',
       'complete' => 'bg-success',
       'rembourse' => 'bg-info',
       'echoue' => 'bg-danger'
   ];
   
   $textes = [
       'en_attente' => 'En attente',
       'acceptee' => 'Acceptée',
       'refusee' => 'Refusée',
       'annulee' => 'Annulée',
       'terminee' => 'Terminée',
       'complete' => 'Payé',
       'rembourse' => 'Remboursé',
       'echoue' => 'Échoué'
   ];
   
   $classe = isset($classes[$statut]) ? $classes[$statut] : 'bg-dark';
   $texte = isset($textes[$statut]) ? $textes[$statut] : $statut;
   
   return '<span class="badge ' . $classe . '">' . $texte . '</span>';
}

/**
* Retourne l'état actuel de la page pour les liens actifs du menu
* 
* @param string $page Nom de la page à vérifier
* @return string Classe active si la page courante correspond
*/
function menuActif($page) {
   $pageCourante = basename($_SERVER['PHP_SELF']);
   
   if ($pageCourante == $page || 
       ($page == 'index.php' && $pageCourante == '') || 
       (!empty($_GET['page']) && $_GET['page'] == $page)) {
       return 'active';
   }
   
   return '';
}

/**
* Génère un jeton CSRF pour un formulaire
* 
* @return string HTML du champ caché avec jeton CSRF
*/
function genererCsrfToken() {
   if (!isset($_SESSION['csrf_token'])) {
       $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   }
   
   return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
* Affiche la pagination
* 
* @param int $pageCourante Page actuelle
* @param int $totalPages Nombre total de pages
* @param string $baseUrl URL de base pour les liens de pagination
* @return string HTML de la pagination
*/
function afficherPagination($pageCourante, $totalPages, $baseUrl) {
   if ($totalPages <= 1) {
       return '';
   }
   
   $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center">';
   
   // Lien précédent
   $html .= '<li class="page-item ' . ($pageCourante <= 1 ? 'disabled' : '') . '">';
   $html .= '<a class="page-link" href="' . ($pageCourante > 1 ? $baseUrl . ($pageCourante - 1) : '#') . '" aria-label="Précédent">';
   $html .= '<span aria-hidden="true">&laquo;</span></a></li>';
   
   // Pages numérotées
   $debut = max(1, $pageCourante - 2);
   $fin = min($totalPages, $pageCourante + 2);
   
   if ($debut > 1) {
       $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '1">1</a></li>';
       if ($debut > 2) {
           $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
       }
   }
   
   for ($i = $debut; $i <= $fin; $i++) {
       $html .= '<li class="page-item ' . ($i == $pageCourante ? 'active' : '') . '">';
       $html .= '<a class="page-link" href="' . $baseUrl . $i . '">' . $i . '</a></li>';
   }
   
   if ($fin < $totalPages) {
       if ($fin < $totalPages - 1) {
           $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
       }
       $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $totalPages . '">' . $totalPages . '</a></li>';
   }
   
   // Lien suivant
   $html .= '<li class="page-item ' . ($pageCourante >= $totalPages ? 'disabled' : '') . '">';
   $html .= '<a class="page-link" href="' . ($pageCourante < $totalPages ? $baseUrl . ($pageCourante + 1) : '#') . '" aria-label="Suivant">';
   $html .= '<span aria-hidden="true">&raquo;</span></a></li>';
   
   $html .= '</ul></nav>';
   
   return $html;
}

/**
* Affiche une étoile de notation
* 
* @param float $note Note à afficher (de 0 à 5)
* @param int $taille Taille des étoiles en pixels
* @return string HTML des étoiles
*/
function afficherEtoiles($note, $taille = 16) {
   $note = max(0, min(5, $note));
   $etoilesPleine = floor($note);
   $demiEtoile = $note - $etoilesPleine >= 0.5;
   $etoilesVides = 5 - $etoilesPleine - ($demiEtoile ? 1 : 0);
   
   $html = '<div class="notation" style="font-size: ' . $taille . 'px;">';
   
   // Étoiles pleines
   for ($i = 0; $i < $etoilesPleine; $i++) {
       $html .= '<i class="bi bi-star-fill text-warning"></i>';
   }
   
   // Demi-étoile si nécessaire
   if ($demiEtoile) {
       $html .= '<i class="bi bi-star-half text-warning"></i>';
   }
   
   // Étoiles vides
   for ($i = 0; $i < $etoilesVides; $i++) {
       $html .= '<i class="bi bi-star text-warning"></i>';
   }
   
   $html .= '</div>';
   
   return $html;
}

/**
* Détermine le type d'appareil de l'utilisateur
* 
* @return string Type d'appareil (mobile, tablet, desktop)
*/
function detecterAppareil() {
   $userAgent = $_SERVER['HTTP_USER_AGENT'];
   
   if (preg_match('/(android|iphone|ipod|iemobile)/i', $userAgent)) {
       return 'mobile';
   } else if (preg_match('/(ipad|tablet)/i', $userAgent)) {
       return 'tablet';
   } else {
       return 'desktop';
   }
}

/**
* Format d'affichage d'une durée
* 
* @param int $dureeEnJours Durée en jours
* @return string Durée formatée
*/
function formaterDuree($dureeEnJours) {
   if ($dureeEnJours <= 0) {
       return '0 jour';
   } else if ($dureeEnJours == 1) {
       return '1 jour';
   } else if ($dureeEnJours < 30) {
       return $dureeEnJours . ' jours';
   } else if ($dureeEnJours < 365) {
       $mois = floor($dureeEnJours / 30);
       $jours = $dureeEnJours % 30;
       
       $resultat = $mois . ' mois';
       if ($jours > 0) {
           $resultat .= ' et ' . $jours . ' jour' . ($jours > 1 ? 's' : '');
       }
       
       return $resultat;
   } else {
       $annees = floor($dureeEnJours / 365);
       $mois = floor(($dureeEnJours % 365) / 30);
       
       $resultat = $annees . ' an' . ($annees > 1 ? 's' : '');
       if ($mois > 0) {
           $resultat .= ' et ' . $mois . ' mois';
       }
       
       return $resultat;
   }
}
?>
