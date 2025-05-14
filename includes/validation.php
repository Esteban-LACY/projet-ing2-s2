<?php
/**
 * Valide une adresse email
 * @param string $email Email à valider
 * @return bool Résultat de la validation
 */
function estEmailValide($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide un numéro de téléphone
 * @param string $telephone Téléphone à valider
 * @return bool Résultat de la validation
 */
function estTelephoneValide($telephone) {
    // Format international: +33612345678 ou format français: 0612345678
    return preg_match('/^(\+33|0)[1-9](\d{8})$/', $telephone) === 1;
}

/**
 * Valide une date au format Y-m-d
 * @param string $date Date à valider
 * @return bool Résultat de la validation
 */
function estDateValide($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Valide que la date de début est avant la date de fin
 * @param string $dateDebut Date de début
 * @param string $dateFin Date de fin
 * @return bool Résultat de la validation
 */
function estPeriodeValide($dateDebut, $dateFin) {
    if (!estDateValide($dateDebut) || !estDateValide($dateFin)) {
        return false;
    }
    
    $debut = new DateTime($dateDebut);
    $fin = new DateTime($dateFin);
    
    return $debut <= $fin;
}

/**
 * Valide un code postal français
 * @param string $codePostal Code postal à valider
 * @return bool Résultat de la validation
 */
function estCodePostalValide($codePostal) {
    return preg_match('/^[0-9]{5}$/', $codePostal) === 1;
}

/**
 * Valide un prix
 * @param mixed $prix Prix à valider
 * @return bool Résultat de la validation
 */
function estPrixValide($prix) {
    return is_numeric($prix) && $prix > 0;
}

/**
 * Valide une image uploadée
 * @param array $fichier Données du fichier ($_FILES['champ'])
 * @return array Résultat avec statut et message
 */
function validerImage($fichier) {
    // Vérifier s'il y a une erreur
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par PHP',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
        ];
        
        return [
            'valide' => false,
            'message' => $messages[$fichier['error']] ?? 'Erreur inconnue lors de l\'upload'
        ];
    }
    
    // Vérifier la taille
    if ($fichier['size'] > MAX_FILE_SIZE) {
        return [
            'valide' => false,
            'message' => 'Le fichier est trop volumineux (max ' . (MAX_FILE_SIZE / 1024 / 1024) . ' Mo)'
        ];
    }
    
    // Vérifier le type MIME
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->file($fichier['tmp_name']);
    $mimeAutorises = [
        'image/jpeg',
        'image/png'
    ];
    
    if (!in_array($mime, $mimeAutorises)) {
        return [
            'valide' => false,
            'message' => 'Type de fichier non autorisé (JPG, JPEG ou PNG uniquement)'
        ];
    }
    
    // Vérifier l'extension
    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return [
            'valide' => false,
            'message' => 'Extension de fichier non autorisée (JPG, JPEG ou PNG uniquement)'
        ];
    }
    
    return [
        'valide' => true,
        'message' => 'Image valide',
        'extension' => $extension
    ];
}
?>
