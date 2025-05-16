<?php
session_start();
require_once 'includes/fonctions.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirection si l'utilisateur est déjà connecté
if (estConnecte()) {
    header('Location: index.php');
    exit();
}

$erreur = '';
$email = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    
    // Validation des données
    if (empty($email) || empty($motDePasse)) {
        $erreur = 'Tous les champs sont obligatoires.';
    } else {
        try {
            // Connexion à la base de données
            $pdo = connecterBDD();
            
            // Récupération de l'utilisateur par son email
            $requete = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = :email');
            $requete->bindParam(':email', $email);
            $requete->execute();
            
            $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);
            
            // Vérification de l'existence de l'utilisateur et du mot de passe
            if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
                // Vérification si l'email est vérifié
                if (!$utilisateur['est_verifie']) {
                    $erreur = 'Votre compte n\'est pas encore activé. Veuillez vérifier votre email.';
                } else {
                    // Connexion réussie - Utiliser la fonction de connexion standardisée
                    connecterUtilisateur($utilisateur);
                    
                    // Redirection vers la page d'accueil
                    header('Location: index.php');
                    exit();
                }
            } else {
                $erreur = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $erreur = 'Erreur lors de la connexion : ' . $e->getMessage();
        }
    }
}

// Titre de la page
$titre = 'Connexion';

// Inclusion du header
include 'views/commun/header.php';
?>

<div class="container-mobile mx-auto py-8">
    <h1 class="text-3xl font-bold mb-6">Connexion</h1>
    
    <?php if (!empty($erreur)) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $erreur; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="connexion.php" class="mb-4">
        <div class="mb-4">
            <label for="email" class="block text-gray-700 mb-2">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="input-field" required>
        </div>
        
        <div class="mb-6">
            <label for="mot_de_passe" class="block text-gray-700 mb-2">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" class="input-field" required>
        </div>
        
        <button type="submit" class="btn-primary">Se connecter</button>
    </form>
    
    <div class="text-center mt-4">
        <p>Vous n'avez pas de compte ? <a href="inscription.php" class="text-black underline">S'inscrire</a></p>
        <p class="mt-2"><a href="mot-de-passe-oublie.php" class="text-black underline">Mot de passe oublié ?</a></p>
    </div>
</div>

<?php
// Inclusion du footer
include 'views/commun/footer.php';
?>
