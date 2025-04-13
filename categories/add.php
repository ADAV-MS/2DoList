<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

$error = '';
$success = '';
$category_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_name = $name; // Conserver la valeur en cas d'erreur

    try {
        // Vérifier si le champ est vide
        if (empty($name)) {
            throw new Exception("Le nom de la catégorie est obligatoire.");
        }
        
        // Vérifier la longueur du nom (maximum 100 caractères selon la base de données)
        if (strlen($name) > 100) {
            throw new Exception("Le nom de la catégorie ne peut pas dépasser 100 caractères.");
        }

        // Vérifier si la catégorie existe déjà
        $stmt_check = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND user_id = ?");
        $stmt_check->execute([$name, $_SESSION['user_id']]);
        
        if ($stmt_check->rowCount() > 0) {
            throw new Exception("Cette catégorie existe déjà.");
        } else {
            // Insérer la nouvelle catégorie
            $stmt_insert = $pdo->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
            $stmt_insert->execute([$_SESSION['user_id'], $name]);

            // Définir un message de notification
            $_SESSION['notification'] = "La catégorie \"" . htmlspecialchars($name) . "\" a été créée avec succès.";
            $_SESSION['notification_type'] = "success";

            // Redirection vers la liste des catégories
            header('Location: ../index.php');
            exit();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Configuration de l'en-tête
$page_title = "Nouvelle catégorie";
$page_css = "../css/categories.css";
include '../includes/header.php';
?>

<main class="container">
    <h1><i class="fas fa-folder-plus"></i> Créer une nouvelle catégorie</h1>

    <!-- Message d'erreur -->
    <?php if (!empty($error)): ?>
        <div class="notification error">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Message de succès -->
    <?php if (!empty($success)): ?>
        <div class="notification success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Formulaire d'ajout de catégorie -->
    <div class="card">
        <form action="add.php" method="POST" class="form">
            <div class="form-group">
                <label for="name"><i class="fas fa-tag"></i> Nom de la catégorie :</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($category_name) ?>" 
                       placeholder="Exemple : Travail, Personnel..." 
                       required maxlength="100" autofocus>
                <small>Maximum 100 caractères</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">
                    <i class="fas fa-plus-circle"></i> Créer la catégorie
                </button>
                <a href="../index.php" class="btn secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
    
    <!-- Suggestions de catégories -->
    <div class="suggestions card">
        <h2><i class="fas fa-lightbulb"></i> Suggestions de catégories</h2>
        <p>Voici quelques idées de catégories couramment utilisées :</p>
        <div class="suggestion-tags">
            <span class="tag" onclick="fillCategoryName('Travail')">Travail</span>
            <span class="tag" onclick="fillCategoryName('Personnel')">Personnel</span>
            <span class="tag" onclick="fillCategoryName('Urgent')">Urgent</span>
            <span class="tag" onclick="fillCategoryName('Famille')">Famille</span>
            <span class="tag" onclick="fillCategoryName('Santé')">Santé</span>
            <span class="tag" onclick="fillCategoryName('Finances')">Finances</span>
            <span class="tag" onclick="fillCategoryName('Loisirs')">Loisirs</span>
            <span class="tag" onclick="fillCategoryName('Courses')">Courses</span>
        </div>
    </div>
</main>

<script>
function fillCategoryName(name) {
    document.getElementById('name').value = name;
    document.getElementById('name').focus();
}
</script>

<?php include '../includes/footer.php'; ?>
