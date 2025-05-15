<?php
/**
 * Fonctions de validation des données
 * 
 * Ce fichier contient des fonctions pour valider les données des formulaires
 * 
 * @author OmnesBnB
 */

// Inclusion des fonctions utilitaires
require_once __DIR__ . '/fonctions.php';

/**
 * Valide les données d'inscription d'un utilisateur
 * 
 * @param array $donnees Données d'inscription
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerInscription($donnees) {
    $erreurs = [];
    
    // Validation du nom
    if (empty($donnees['nom'])) {
        $erreurs[] = 'Le nom est obligatoire';
    } elseif (strlen($donnees['nom']) > 100) {
        $erreurs[] = 'Le nom ne doit pas dépasser 100 caractères';
    }
    
    // Validation du prénom
    if (empty($donnees['prenom'])) {
        $erreurs[] = 'Le prénom est obligatoire';
    } elseif (strlen($donnees['prenom']) > 100) {
        $erreurs[] = 'Le prénom ne doit pas dépasser 100 caractères';
    }
    
    // Validation de l'email
    if (empty($donnees['email'])) {
        $erreurs[] = 'L\'email est obligatoire';
    } elseif (!estEmailValide($donnees['email'])) {
        $erreurs[] = 'L\'email n\'est pas valide';
    } elseif (!estDomainEmailAutorise($donnees['email'])) {
        $erreurs[] = 'L\'email doit appartenir à un domaine autorisé';
    }
    
    // Validation du mot de passe
    if (empty($donnees['mot_de_passe'])) {
        $erreurs[] = 'Le mot de passe est obligatoire';
    } elseif (strlen($donnees['mot_de_passe']) < 8) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères';
    }
    
    // Validation de la confirmation du mot de passe
    if (empty($donnees['confirmer_mot_de_passe'])) {
        $erreurs[] = 'La confirmation du mot de passe est obligatoire';
    } elseif ($donnees['mot_de_passe'] !== $donnees['confirmer_mot_de_passe']) {
        $erreurs[] = 'Les mots de passe ne correspondent pas';
    }
    
    // Validation du téléphone (optionnel)
    if (!empty($donnees['telephone']) && !estTelephoneValide($donnees['telephone'])) {
        $erreurs[] = 'Le numéro de téléphone n\'est pas valide';
    }
    
    return $erreurs;
}

/**
 * Valide les données de connexion d'un utilisateur
 * 
 * @param array $donnees Données de connexion
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerConnexion($donnees) {
    $erreurs = [];
    
    // Validation de l'email
    if (empty($donnees['email'])) {
        $erreurs[] = 'L\'email est obligatoire';
    } elseif (!estEmailValide($donnees['email'])) {
        $erreurs[] = 'L\'email n\'est pas valide';
    }
    
    // Validation du mot de passe
    if (empty($donnees['mot_de_passe'])) {
        $erreurs[] = 'Le mot de passe est obligatoire';
    }
    
    return $erreurs;
}

/**
 * Valide les données de profil d'un utilisateur
 * 
 * @param array $donnees Données de profil
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerProfil($donnees) {
    $erreurs = [];
    
    // Validation du nom
    if (empty($donnees['nom'])) {
        $erreurs[] = 'Le nom est obligatoire';
    } elseif (strlen($donnees['nom']) > 100) {
        $erreurs[] = 'Le nom ne doit pas dépasser 100 caractères';
    }
    
    // Validation du prénom
    if (empty($donnees['prenom'])) {
        $erreurs[] = 'Le prénom est obligatoire';
    } elseif (strlen($donnees['prenom']) > 100) {
        $erreurs[] = 'Le prénom ne doit pas dépasser 100 caractères';
    }
    
    // Validation de l'email
    if (empty($donnees['email'])) {
        $erreurs[] = 'L\'email est obligatoire';
    } elseif (!estEmailValide($donnees['email'])) {
        $erreurs[] = 'L\'email n\'est pas valide';
    } elseif (!estDomainEmailAutorise($donnees['email'])) {
        $erreurs[] = 'L\'email doit appartenir à un domaine autorisé';
    }
    
    // Validation du téléphone (optionnel)
    if (!empty($donnees['telephone']) && !estTelephoneValide($donnees['telephone'])) {
        $erreurs[] = 'Le numéro de téléphone n\'est pas valide';
    }
    
    return $erreurs;
}

/**
 * Valide les données de changement de mot de passe
 * 
 * @param array $donnees Données de changement de mot de passe
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerChangementMotDePasse($donnees) {
    $erreurs = [];
    
    // Validation de l'ancien mot de passe
    if (empty($donnees['ancien_mot_de_passe'])) {
        $erreurs[] = 'L\'ancien mot de passe est obligatoire';
    }
    
    // Validation du nouveau mot de passe
    if (empty($donnees['nouveau_mot_de_passe'])) {
        $erreurs[] = 'Le nouveau mot de passe est obligatoire';
    } elseif (strlen($donnees['nouveau_mot_de_passe']) < 8) {
        $erreurs[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères';
    }
    
    // Validation de la confirmation du nouveau mot de passe
    if (empty($donnees['confirmer_mot_de_passe'])) {
        $erreurs[] = 'La confirmation du mot de passe est obligatoire';
    } elseif ($donnees['nouveau_mot_de_passe'] !== $donnees['confirmer_mot_de_passe']) {
        $erreurs[] = 'Les mots de passe ne correspondent pas';
    }
    
    return $erreurs;
}

/**
 * Valide les données d'un logement
 * 
 * @param array $donnees Données du logement
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerLogement($donnees) {
    $erreurs = [];
    
    // Validation du titre
    if (empty($donnees['titre'])) {
        $erreurs[] = 'Le titre est obligatoire';
    } elseif (strlen($donnees['titre']) > 255) {
        $erreurs[] = 'Le titre ne doit pas dépasser 255 caractères';
    }
    
    // Validation de la description
    if (empty($donnees['description'])) {
        $erreurs[] = 'La description est obligatoire';
    }
    
    // Validation de l'adresse
    if (empty($donnees['adresse'])) {
        $erreurs[] = 'L\'adresse est obligatoire';
    } elseif (strlen($donnees['adresse']) > 255) {
        $erreurs[] = 'L\'adresse ne doit pas dépasser 255 caractères';
    }
    
    // Validation de la ville
    if (empty($donnees['ville'])) {
        $erreurs[] = 'La ville est obligatoire';
    } elseif (strlen($donnees['ville']) > 100) {
        $erreurs[] = 'La ville ne doit pas dépasser 100 caractères';
    }
    
    // Validation du code postal
    if (empty($donnees['code_postal'])) {
        $erreurs[] = 'Le code postal est obligatoire';
    } elseif (!estCodePostalValide($donnees['code_postal'])) {
        $erreurs[] = 'Le code postal n\'est pas valide';
    }
    
    // Validation du prix
    if (empty($donnees['prix'])) {
        $erreurs[] = 'Le prix est obligatoire';
    } elseif (!is_numeric($donnees['prix']) || $donnees['prix'] <= 0) {
        $erreurs[] = 'Le prix doit être un nombre positif';
    }
    
    // Validation du type de logement
    if (empty($donnees['type_logement'])) {
        $erreurs[] = 'Le type de logement est obligatoire';
    } elseif (!in_array($donnees['type_logement'], ['entier', 'collocation', 'libere'])) {
        $erreurs[] = 'Le type de logement n\'est pas valide';
    }
    
    // Validation du nombre de places
    if (empty($donnees['nb_places'])) {
        $erreurs[] = 'Le nombre de places est obligatoire';
    } elseif (!is_numeric($donnees['nb_places']) || $donnees['nb_places'] <= 0) {
        $erreurs[] = 'Le nombre de places doit être un nombre positif';
    }
    
    return $erreurs;
}

/**
 * Valide les données d'une disponibilité
 * 
 * @param array $donnees Données de la disponibilité
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerDisponibilite($donnees) {
    $erreurs = [];
    
    // Validation de la date de début
    if (empty($donnees['date_debut'])) {
        $erreurs[] = 'La date de début est obligatoire';
    } elseif (!estDateValide($donnees['date_debut'])) {
        $erreurs[] = 'La date de début n\'est pas valide';
    }
    
    // Validation de la date de fin
    if (empty($donnees['date_fin'])) {
        $erreurs[] = 'La date de fin est obligatoire';
    } elseif (!estDateValide($donnees['date_fin'])) {
        $erreurs[] = 'La date de fin n\'est pas valide';
    }
    
    // Vérifier que la date de fin est après la date de début
    if (estDateValide($donnees['date_debut']) && estDateValide($donnees['date_fin'])) {
        if (strtotime($donnees['date_fin']) <= strtotime($donnees['date_debut'])) {
            $erreurs[] = 'La date de fin doit être après la date de début';
        }
    }
    
    return $erreurs;
}

/**
 * Valide les données d'une réservation
 * 
 * @param array $donnees Données de la réservation
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerReservation($donnees) {
    $erreurs = [];
    
    // Validation du logement
    if (empty($donnees['id_logement'])) {
        $erreurs[] = 'Le logement est obligatoire';
    } elseif (!is_numeric($donnees['id_logement']) || $donnees['id_logement'] <= 0) {
        $erreurs[] = 'ID de logement invalide';
    }
    
    // Validation de la date de début
    if (empty($donnees['date_debut'])) {
        $erreurs[] = 'La date de début est obligatoire';
    } elseif (!estDateValide($donnees['date_debut'])) {
        $erreurs[] = 'La date de début n\'est pas valide';
    }
    
    // Validation de la date de fin
    if (empty($donnees['date_fin'])) {
        $erreurs[] = 'La date de fin est obligatoire';
    } elseif (!estDateValide($donnees['date_fin'])) {
        $erreurs[] = 'La date de fin n\'est pas valide';
    }
    
    // Vérifier que la date de fin est après la date de début
    if (estDateValide($donnees['date_debut']) && estDateValide($donnees['date_fin'])) {
        if (strtotime($donnees['date_fin']) <= strtotime($donnees['date_debut'])) {
            $erreurs[] = 'La date de fin doit être après la date de début';
        }
    }
    
    return $erreurs;
}

/**
 * Valide les données d'une recherche
 * 
 * @param array $donnees Données de recherche
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerRecherche($donnees) {
    $erreurs = [];
    
    // Validation des dates (si présentes)
    if (!empty($donnees['date_debut'])) {
        if (!estDateValide($donnees['date_debut'])) {
            $erreurs[] = 'La date de début n\'est pas valide';
        }
    }
    
    if (!empty($donnees['date_fin'])) {
        if (!estDateValide($donnees['date_fin'])) {
            $erreurs[] = 'La date de fin n\'est pas valide';
        }
    }
    
    // Vérifier que la date de fin est après la date de début
    if (!empty($donnees['date_debut']) && !empty($donnees['date_fin'])) {
        if (estDateValide($donnees['date_debut']) && estDateValide($donnees['date_fin'])) {
            if (strtotime($donnees['date_fin']) <= strtotime($donnees['date_debut'])) {
                $erreurs[] = 'La date de fin doit être après la date de début';
            }
        }
    }
    
    // Validation du prix minimum (si présent)
    if (isset($donnees['prix_min']) && $donnees['prix_min'] !== '') {
        if (!is_numeric($donnees['prix_min']) || $donnees['prix_min'] < 0) {
            $erreurs[] = 'Le prix minimum doit être un nombre positif ou nul';
        }
    }
    
    // Validation du prix maximum (si présent)
    if (isset($donnees['prix_max']) && $donnees['prix_max'] !== '') {
        if (!is_numeric($donnees['prix_max']) || $donnees['prix_max'] <= 0) {
            $erreurs[] = 'Le prix maximum doit être un nombre positif';
        }
    }
    
    // Vérifier que le prix minimum est inférieur au prix maximum
    if (isset($donnees['prix_min']) && isset($donnees['prix_max']) && 
        $donnees['prix_min'] !== '' && $donnees['prix_max'] !== '') {
        if (is_numeric($donnees['prix_min']) && is_numeric($donnees['prix_max'])) {
            if ($donnees['prix_min'] > $donnees['prix_max']) {
                $erreurs[] = 'Le prix minimum doit être inférieur au prix maximum';
            }
        }
    }
    
    // Validation du nombre de places (si présent)
    if (isset($donnees['nb_places']) && $donnees['nb_places'] !== '') {
        if (!is_numeric($donnees['nb_places']) || $donnees['nb_places'] <= 0) {
            $erreurs[] = 'Le nombre de places doit être un nombre positif';
        }
    }
    
    return $erreurs;
}

/**
 * Valide un mot de passe
 * 
 * @param string $motDePasse Mot de passe à valider
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerMotDePasse($motDePasse) {
    $erreurs = [];
    
    if (strlen($motDePasse) < 8) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères';
    }
    
    return $erreurs;
}

/**
 * Valide un email
 * 
 * @param string $email Email à valider
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerEmail($email) {
    $erreurs = [];
    
    if (!estEmailValide($email)) {
        $erreurs[] = 'L\'email n\'est pas valide';
    } elseif (!estDomainEmailAutorise($email)) {
        $erreurs[] = 'L\'email doit appartenir à un domaine autorisé';
    }
    
    return $erreurs;
}

/**
 * Valide un numéro de téléphone
 * 
 * @param string $telephone Numéro de téléphone à valider
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerTelephone($telephone) {
    $erreurs = [];
    
    if (!estTelephoneValide($telephone)) {
        $erreurs[] = 'Le numéro de téléphone n\'est pas valide';
    }
    
    return $erreurs;
}

/**
 * Valide un code postal
 * 
 * @param string $codePostal Code postal à valider
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerCodePostal($codePostal) {
    $erreurs = [];
    
    if (!estCodePostalValide($codePostal)) {
        $erreurs[] = 'Le code postal n\'est pas valide';
    }
    
    return $erreurs;
}

/**
 * Valide une date
 * 
 * @param string $date Date à valider
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerDate($date) {
    $erreurs = [];
    
    if (!estDateValide($date)) {
        $erreurs[] = 'La date n\'est pas valide';
    }
    
    return $erreurs;
}

/**
 * Valide que la date est future
 * 
 * @param string $date Date à valider
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerDateFuture($date) {
    $erreurs = validerDate($date);
    
    if (empty($erreurs) && !estDateFuture($date)) {
        $erreurs[] = 'La date doit être future';
    }
    
    return $erreurs;
}

/**
 * Valide une image uploadée
 * 
 * @param array $fichier Données du fichier ($_FILES['champ'])
 * @param int $tailleMax Taille maximale en octets
 * @param array $typesAutorises Types MIME autorisés
 * @return array Tableau d'erreurs (vide si tout est valide)
 */
function validerImage($fichier, $tailleMax = 2097152, $typesAutorises = ['image/jpeg', 'image/png', 'image/gif']) {
    $erreurs = [];
    
    // Vérifier si un fichier a été uploadé
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        switch ($fichier['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $erreurs[] = 'Le fichier est trop volumineux';
                break;
            case UPLOAD_ERR_PARTIAL:
                $erreurs[] = 'Le fichier n\'a été que partiellement uploadé';
                break;
            case UPLOAD_ERR_NO_FILE:
                $erreurs[] = 'Aucun fichier n\'a été uploadé';
                break;
            default:
                $erreurs[] = 'Une erreur est survenue lors de l\'upload';
                break;
        }
        
        return $erreurs;
    }
    
    // Vérifier la taille du fichier
    if ($fichier['size'] > $tailleMax) {
        $erreurs[] = 'Le fichier est trop volumineux (maximum ' . formatTaille($tailleMax) . ')';
    }
    
    // Vérifier le type du fichier
    if (!in_array($fichier['type'], $typesAutorises)) {
        $erreurs[] = 'Le type de fichier n\'est pas autorisé';
    }
    
    return $erreurs;
}

/**
 * Formate une taille en octets en une chaîne lisible
 * 
 * @param int $taille Taille en octets
 * @return string Taille formatée
 */
function formatTaille($taille) {
    $unites = ['o', 'Ko', 'Mo', 'Go', 'To'];
    $i = 0;
    
    while ($taille >= 1024 && $i < count($unites) - 1) {
        $taille /= 1024;
        $i++;
    }
    
    return round($taille, 2) . ' ' . $unites[$i];
}
?>
