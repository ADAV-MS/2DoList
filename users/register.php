<?php
require_once '../config/database.php';

// Initialisation des variables
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        // Validation des entrées
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception("Tous les champs sont obligatoires.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Adresse email invalide.");
        }

        if (strlen($password) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères.");
        }

        // Vérification des doublons
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Nom d'utilisateur ou email déjà utilisé.");
        }

        // Hachage du mot de passe
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insertion de l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $passwordHash]);

        header('Location: login.php?success=1');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Configuration de l'en-tête
$page_title = "Inscription";
$page_css = "../css/users.css";
include '../includes/header.php';
?>

<main class="container">
    <div class="auth-card">
        <h1><i class="fas fa-user-plus"></i> Création de compte</h1>

        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur</label>
                <input type="text" id="username" name="username" 
                       pattern="[A-Za-z0-9]{3,20}" 
                       title="3-20 caractères alphanumériques"
                       required>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Adresse email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" 
                           minlength="8"
                           required>
                    <i class="fas fa-eye password-toggle"></i>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-user-check"></i> S'inscrire
            </button>
        </form>

        <div class="auth-links">
            <p>Déjà inscrit ? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Se connecter</a></p>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
document.querySelector('.password-toggle').addEventListener('click', function() {
    const passwordField = document.getElementById('password');
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    this.classList.toggle('fa-eye-slash');
});
</script>
