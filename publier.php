<?php
session_start();
require_once 'includes/fonctions.php';
require_once 'config/database.php';

// Redirection si l'utilisateur n'est pas connecté
if (!estConnecte()) {
    header('Location: connexion.php');
    exit();
}

$erreurs = [];
$succes = false;
$formData = [
    'titre' => '',
    'adresse' => '',
    'ville' => '',
    'code_postal' => '',
    'description' => '',
    'prix' => '',
    'nb_places' => '1',
    'type_logement' => 'entier',
    'date_debut' => '',
    'date_fin' => ''
];

// Traitement du formulaire de publication
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $formData = [
        'titre' => trim($_POST['titre'] ?? ''),
        'adresse' => trim($_POST['adresse'] ?? ''),
        'ville' => trim($_POST['ville'] ?? ''),
        'code_postal' => trim($_POST['code_postal'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'prix' => floatval($_POST['prix'] ?? 0),
        'nb_places' => intval($_POST['nb_places'] ?? 1),
        'type_logement' => $_POST['type_logement'] ?? 'entier',
        'date_debut' => $_POST['date_debut'] ?? '',
        'date_fin' => $_POST['date_fin'] ?? '',
        'latitude' => floatval($_POST['latitude'] ?? 0),
        'longitude' => floatval($_POST['longitude'] ?? 0)
    ];
    
    // Validation des données
    if (empty($formData['titre'])) {
        $erreurs['titre'] = 'Le titre est obligatoire.';
    }
    
    if (empty($formData['adresse'])) {
        $erreurs['adresse'] = 'L\'adresse est obligatoire.';
    }
    
    if (empty($formData['ville'])) {
        $erreurs['ville'] = 'La ville est obligatoire.';
    }
    
    if (empty($formData['code_postal'])) {
        $erreurs['code_postal'] = 'Le code postal est obligatoire.';
    } elseif (!preg_match('/^[0-9]{5}$/', $formData['code_postal'])) {
        $erreurs['code_postal'] = 'Format de code postal invalide.';
    }
    
    if (empty($formData['prix']) || $formData['prix'] <= 0) {
        $erreurs['prix'] = 'Le prix doit être supérieur à 0.';
    }
    
    if (empty($formData['date_debut'])) {
        $erreurs['date_debut'] = 'La date de début est obligatoire.';
    }
    
    if (empty($formData['date_fin'])) {
        $erreurs['date_fin'] = 'La date de fin est obligatoire.';
    } elseif ($formData['date_debut'] >= $formData['date_fin']) {
        $erreurs['date_fin'] = 'La date de fin doit être postérieure à la date de début.';
    }
    
    // Vérification que les coordonnées GPS sont renseignées
    if (empty($formData['latitude']) || empty($formData['longitude'])) {
        // Si pas de coordonnées, utiliser l'API de géocodage pour les obtenir
        if (empty($erreurs['adresse']) && empty($erreurs['ville']) && empty($erreurs['code_postal'])) {
            $adresseComplete = $formData['adresse'] . ', ' . $formData['code_postal'] . ' ' . $formData['ville'] . ', France';
            
            try {
                // Appel à l'API de géocodage (à implémenter)
                $coordonnees = geocoderAdresse($adresseComplete);
                
                if ($coordonnees) {
                    $formData['latitude'] = $coordonnees['lat'];
                    $formData['longitude'] = $coordonnees['lng'];
                } else {
                    $erreurs['adresse'] = 'Impossible de géolocaliser cette adresse.';
                }
            } catch (Exception $e) {
                $erreurs['adresse'] = 'Erreur lors de la géolocalisation : ' . $e->getMessage();
            }
        }
    }
    
    // Si aucune erreur, procéder à la publication
    if (empty($erreurs)) {
        try {
            // Connexion à la base de données
            $pdo = connecterBDD();
            
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Insertion du logement dans la base de données
            $requete = $pdo->prepare('
                INSERT INTO logements (
                    id_proprietaire,
                    titre,
                    description,
                    adresse,
                    ville,
                    code_postal,
                    latitude,
                    longitude,
                    prix,
                    type_logement,
                    nb_places,
                    date_creation
                ) 
                VALUES (
                    :id_proprietaire,
                    :titre,
                    :description,
                    :adresse,
                    :ville,
                    :code_postal,
                    :latitude,
                    :longitude,
                    :prix,
                    :type_logement,
                    :nb_places,
                    NOW()
                )
            ');
            
            $idProprietaire = $_SESSION['utilisateur']['id'];
            
            $requete->bindParam(':id_proprietaire', $idProprietaire);
            $requete->bindParam(':titre', $formData['titre']);
            $requete->bindParam(':description', $formData['description']);
            $requete->bindParam(':adresse', $formData['adresse']);
            $requete->bindParam(':ville', $formData['ville']);
            $requete->bindParam(':code_postal', $formData['code_postal']);
            $requete->bindParam(':latitude', $formData['latitude']);
            $requete->bindParam(':longitude', $formData['longitude']);
            $requete->bindParam(':prix', $formData['prix']);
            $requete->bindParam(':type_logement', $formData['type_logement']);
            $requete->bindParam(':nb_places', $formData['nb_places']);
            
            $requete->execute();
            
            // Récupération de l'ID du logement nouvellement créé
            $idLogement = $pdo->lastInsertId();
            
            // Insertion des disponibilités
            $requeteDisponibilite = $pdo->prepare('
                INSERT INTO disponibilites (id_logement, date_debut, date_fin) 
                VALUES (:id_logement, :date_debut, :date_fin)
            ');
            
            $requeteDisponibilite->bindParam(':id_logement', $idLogement);
            $requeteDisponibilite->bindParam(':date_debut', $formData['date_debut']);
            $requeteDisponibilite->bindParam(':date_fin', $formData['date_fin']);
            
            $requeteDisponibilite->execute();
            
            // Traitement des photos
            if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                $photos = $_FILES['photos'];
                $nombrePhotos = count($photos['name']);
                
                // Créer le dossier d'upload si nécessaire
                $dossierUpload = 'uploads/logements/';
                if (!file_exists($dossierUpload)) {
                    mkdir($dossierUpload, 0777, true);
                }
                
                // Insertion des photos
                $requetePhoto = $pdo->prepare('
                    INSERT INTO photos_logement (id_logement, url, est_principale) 
                    VALUES (:id_logement, :url, :est_principale)
                ');
                
                for ($i = 0; $i < $nombrePhotos; $i++) {
                    if ($photos['error'][$i] === 0) {
                        $extension = pathinfo($photos['name'][$i], PATHINFO_EXTENSION);
                        $nouveauNom = uniqid('logement_' . $idLogement . '_') . '.' . $extension;
                        $cheminFichier = $dossierUpload . $nouveauNom;
                        
                        if (move_uploaded_file($photos['tmp_name'][$i], $cheminFichier)) {
                            $estPrincipale = ($i === 0) ? 1 : 0; // La première photo est la principale
                            
                            $requetePhoto->bindParam(':id_logement', $idLogement);
                            $requetePhoto->bindParam(':url', $nouveauNom);
                            $requetePhoto->bindParam(':est_principale', $estPrincipale);
                            
                            $requetePhoto->execute();
                        }
                    }
                }
            }
            
            // Valider la transaction
            $pdo->commit();
            
            $succes = true;
            
            // Redirection vers la page de détails du logement
            header('Location: logement/details.php?id=' . $idLogement . '&publie=1');
            exit();
            
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollBack();
            $erreurs['general'] = 'Erreur lors de la publication : ' . $e->getMessage();
        }
    }
}

// Fonction de géocodage (à remplacer par un appel à l'API Google Maps)
function geocoderAdresse($adresse) {
    // Cette fonction devrait utiliser l'API Google Maps
    // Pour l'instant, on retourne des coordonnées fictives
    return [
        'lat' => 48.85341,
        'lng' => 2.3488
    ];
}

// Titre de la page
$titre = 'Publier un logement';

// Inclusion du header
include 'views/commun/header.php';
?>

<div class="container-mobile mx-auto py-8 mb-safe">
    <h1 class="text-3xl font-bold mb-6">Publier un logement</h1>
    
    <?php if (isset($erreurs['general'])) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erreurs['general']; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($succes) : ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Votre logement a été publié avec succès !
        </div>
    <?php endif; ?>
    
    <form method="POST" action="publier.php" enctype="multipart/form-data" class="mb-4">
        <div class="mb-4">
            <label for="titre" class="block text-gray-700 mb-2">Titre</label>
            <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($formData['titre']); ?>" class="input-field <?php echo isset($erreurs['titre']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['titre'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['titre']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="adresse" class="block text-gray-700 mb-2">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($formData['adresse']); ?>" class="input-field address-autocomplete <?php echo isset($erreurs['adresse']) ? 'border-red-500' : ''; ?>" data-lat-field="latitude" data-lng-field="longitude" data-ville-field="ville" data-code-postal-field="code_postal" required>
            <?php if (isset($erreurs['adresse'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['adresse']; ?></p>
            <?php endif; ?>
            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($formData['latitude'] ?? ''); ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($formData['longitude'] ?? ''); ?>">
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="ville" class="block text-gray-700 mb-2">Ville</label>
                <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($formData['ville']); ?>" class="input-field <?php echo isset($erreurs['ville']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['ville'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['ville']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="w-1/2">
                <label for="code_postal" class="block text-gray-700 mb-2">Code postal</label>
                <input type="text" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($formData['code_postal']); ?>" class="input-field <?php echo isset($erreurs['code_postal']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['code_postal'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['code_postal']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 mb-2">Description</label>
            <textarea id="description" name="description" rows="4" class="input-field <?php echo isset($erreurs['description']) ? 'border-red-500' : ''; ?>"><?php echo htmlspecialchars($formData['description']); ?></textarea>
            <?php if (isset($erreurs['description'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['description']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="type_logement" class="block text-gray-700 mb-2">Type de logement</label>
            <select id="type_logement" name="type_logement" class="input-field">
                <option value="entier" <?php echo $formData['type_logement'] === 'entier' ? 'selected' : ''; ?>>Logement entier</option>
                <option value="collocation" <?php echo $formData['type_logement'] === 'collocation' ? 'selected' : ''; ?>>Collocation</option>
                <option value="libere" <?php echo $formData['type_logement'] === 'libere' ? 'selected' : ''; ?>>Logement libéré</option>
            </select>
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="prix" class="block text-gray-700 mb-2">Prix par nuit (€)</label>
                <input type="number" id="prix" name="prix" value="<?php echo htmlspecialchars($formData['prix']); ?>" min="1" step="0.01" class="input-field <?php echo isset($erreurs['prix']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['prix'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['prix']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="w-1/2">
                <label for="nb_places" class="block text-gray-700 mb-2">Nombre de places</label>
                <input type="number" id="nb_places" name="nb_places" value="<?php echo htmlspecialchars($formData['nb_places']); ?>" min="1" class="input-field" required>
            </div>
        </div>
        
        <div class="flex space-x-4 mb-4">
            <div class="w-1/2">
                <label for="date_debut" class="block text-gray-700 mb-2">Date de début</label>
                <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($formData['date_debut']); ?>" class="input-field <?php echo isset($erreurs['date_debut']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['date_debut'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['date_debut']; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="w-1/2">
                <label for="date_fin" class="block text-gray-700 mb-2">Date de fin</label>
                <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($formData['date_fin']); ?>" class="input-field <?php echo isset($erreurs['date_fin']) ? 'border-red-500' : ''; ?>" required>
                <?php if (isset($erreurs['date_fin'])) : ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['date_fin']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-6">
            <label for="photos" class="block text-gray-700 mb-2">Photos (facultatif)</label>
            <input type="file" id="photos" name="photos[]" multiple accept="image/*" class="input-field">
            <p class="text-gray-500 text-sm mt-1">Vous pouvez sélectionner plusieurs photos. La première sera utilisée comme image principale.</p>
        </div>
        
        <button type="submit" class="btn-primary">Publier</button>
    </form>
</div>

<!-- Initialisation de Google Maps -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.googleMaps) {
            window.googleMaps.init('<?php echo GOOGLE_MAPS_API_KEY; ?>');
        }
    });
</script>

<?php
// Inclusion du footer
include 'views/commun/footer.php';
?>
