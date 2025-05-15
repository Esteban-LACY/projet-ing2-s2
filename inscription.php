<?php
session_start();
require_once 'includes/fonctions.php';
require_once 'config/database.php';
require_once 'includes/email.php';

// Redirection si l'utilisateur est déjà connecté
if (estConnecte()) {
    header('Location: index.php');
    exit();
}

$erreurs = [];
$formData = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'telephone' => ''
];

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $formData = [
        'nom' => trim($_POST['nom'] ?? ''),
        'prenom' => trim($_POST['prenom'] ?? ''),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'telephone' => trim($_POST['telephone'] ?? ''),
        'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
        'confirmer_mot_de_passe' => $_POST['confirmer_mot_de_passe'] ?? ''
    ];
    
    // Validation des données
    if (empty($formData['nom'])) {
        $erreurs['nom'] = 'Le nom est obligatoire.';
    }
    
    if (empty($formData['prenom'])) {
        $erreurs['prenom'] = 'Le prénom est obligatoire.';
    }
    
    if (empty($formData['email'])) {
        $erreurs['email'] = 'L\'email est obligatoire.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = 'Format d\'email invalide.';
    } else {
        // Vérifier que l'email est une adresse institutionnelle
        $domainesValides = ['omnesintervenant.com', 'ece.fr', 'edu.ece.fr'];
        $domaineValide = false;
        foreach ($domainesValides as $domaine) {
            if (strpos($formData['email'], '@' . $domaine) !== false) {
                $domaineValide = true;
                break;
            }
        }
        
        if (!$domaineValide) {
            $erreurs['email'] = 'Vous devez utiliser une adresse email institutionnelle.';
        }
    }
    
    if (empty($formData['telephone'])) {
        $erreurs['telephone'] = 'Le numéro de téléphone est obligatoire.';
    } elseif (!preg_match('/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/', $formData['telephone'])) {
        $erreurs['telephone'] = 'Format de téléphone invalide.';
    }
    
    if (empty($formData['mot_de_passe'])) {
        $erreurs['mot_de_passe'] = 'Le mot de passe est obligatoire.';
    } elseif (strlen($formData['mot_de_passe']) < 8) {
        $erreurs['mot_de_passe'] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    
    if ($formData['mot_de_passe'] !== $formData['confirmer_mot_de_passe']) {
        $erreurs['confirmer_mot_de_passe'] = 'Les mots de passe ne correspondent pas.';
    }
    
    // Si aucune erreur, procéder à l'inscription
    if (empty($erreurs)) {
        try {
            // Connexion à la base de données
            $pdo = connecterBDD();
            
            // Vérifier si l'email existe déjà
            $requete = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE email = :email');
            $requete->bindParam(':email', $formData['email']);
            $requete->execute();
            
            if ($requete->fetchColumn() > 0) {
                $erreurs['email'] = 'Cet email est déjà utilisé.';
            } else {
                // Hachage du mot de passe
                $motDePasseHash = password_hash($formData['mot_de_passe'], PASSWORD_DEFAULT);
                
                // Génération d'un token de vérification
                $tokenVerification = bin2hex(random_bytes(32));
                
                // Insertion de l'utilisateur dans la base de données
                $requete = $pdo->prepare('
                    INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, token_verification, date_creation) 
                    VALUES (:nom, :prenom, :email, :mot_de_passe, :telephone, :token_verification, NOW())
                ');
                
                $requete->bindParam(':nom', $formData['nom']);
                $requete->bindParam(':prenom', $formData['prenom']);
                $requete->bindParam(':email', $formData['email']);
                $requete->bindParam(':mot_de_passe', $motDePasseHash);
                $requete->bindParam(':telephone', $formData['telephone']);
                $requete->bindParam(':token_verification', $tokenVerification);
                
                $requete->execute();
                
                // Envoi de l'email de vérification
                $lienVerification = SITE_URL . 'verification.php?token=' . $tokenVerification;
                $sujet = 'OmnesBnB - Vérification de votre compte';
                $message = "Bonjour " . $formData['prenom'] . " " . $formData['nom'] . ",\n\n";
                $message .= "Merci de vous être inscrit sur OmnesBnB. Pour activer votre compte, veuillez cliquer sur le lien suivant :\n\n";
                $message .= $lienVerification . "\n\n";
                $message .= "Si vous n'avez pas créé de compte, veuillez ignorer cet email.\n\n";
                $message .= "Cordialement,\nL'équipe OmnesBnB";
                
                if (envoyerEmail($formData['email'], $sujet, $message)) {
                    // Redirection vers une page de confirmation
                    header('Location: confirmation-inscription.php');
                    exit();
                } else {
                    $erreurs['general'] = 'Erreur lors de l\'envoi de l\'email de vérification.';
                }
            }
        } catch (PDOException $e) {
            $erreurs['general'] = 'Erreur lors de l\'inscription : ' . $e->getMessage();
        }
    }
}

// Titre de la page
$titre = 'Inscription';

// Inclusion du header
include 'views/commun/header.php';
?>

<div class="container-mobile mx-auto py-8">
    <h1 class="text-3xl font-bold mb-6">Inscription</h1>
    
    <?php if (isset($erreurs['general'])) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erreurs['general']; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="inscription.php" class="mb-4">
        <div class="mb-4">
            <label for="nom" class="block text-gray-700 mb-2">Nom</label>
            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom']); ?>" class="input-field <?php echo isset($erreurs['nom']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['nom'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['nom']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="prenom" class="block text-gray-700 mb-2">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($formData['prenom']); ?>" class="input-field <?php echo isset($erreurs['prenom']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['prenom'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['prenom']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-gray-700 mb-2">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" class="input-field <?php echo isset($erreurs['email']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['email'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['email']; ?></p>
            <?php endif; ?>
            <p class="text-gray-500 text-sm mt-1">Utilisez votre adresse email institutionnelle (@omnesintervenant.com, @ece.fr, @edu.ece.fr).</p>
        </div>
        
        <div class="mb-4">
            <label for="telephone" class="block text-gray-700 mb-2">Téléphone</label>
            <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($formData['telephone']); ?>" class="input-field <?php echo isset($erreurs['telephone']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['telephone'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['telephone']; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="mot_de_passe" class="block text-gray-700 mb-2">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" class="input-field <?php echo isset($erreurs['mot_de_passe']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['mot_de_passe'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['mot_de_passe']; ?></p>
            <?php endif; ?>
            <p class="text-gray-500 text-sm mt-1">8 caractères minimum.</p>
        </div>
        
        <div class="mb-6">
            <label for="confirmer_mot_de_passe" class="block text-gray-700 mb-2">Confirmer le mot de passe</label>
            <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" class="input-field <?php echo isset($erreurs['confirmer_mot_de_passe']) ? 'border-red-500' : ''; ?>" required>
            <?php if (isset($erreurs['confirmer_mot_de_passe'])) : ?>
                <p class="text-red-500 text-sm mt-1"><?php echo $erreurs['confirmer_mot_de_passe']; ?></p>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn-primary">S'inscrire</button>
    </form>
    
    <div class="text-center mt-4">
        <p>Vous avez déjà un compte ? <a href="connexion.php" class="text-black underline">Se connecter</a></p>
    </div>
</div>

<?php
// Inclusion du footer
include 'views/commun/footer.php';
?>
