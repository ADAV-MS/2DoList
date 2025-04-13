<?php
require_once '../config/database.php';

// Initialisation des variables
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ../index.php');
            exit();
        } else {
            $error = "Identifiants incorrects";
        }
    } catch (PDOException $e) {
        $error = "Erreur de connexion : " . $e->getMessage();
    }
}

// Configuration de l'en-tête
$page_title = "Connexion";
$page_css = "../css/users.css";
include '../includes/header.php';
?>

<main class="container">
    <div class="auth-card">
        <h1><i class="fas fa-sign-in-alt"></i> Connexion</h1>
        
        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div class="auth-links">
            <p>Pas de compte ? <a href="register.php"><i class="fas fa-user-plus"></i> S'inscrire</a></p>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
