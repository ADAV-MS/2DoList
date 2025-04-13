<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

// Initialisation des variables
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_name = '';
$error_message = '';
$success_message = '';

// Vérification que la catégorie existe et appartient à l'utilisateur
if ($category_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':id' => $category_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            $_SESSION['notification'] = "Catégorie introuvable ou accès non autorisé.";
            $_SESSION['notification_type'] = "error";
            header('Location: index.php');
            exit();
        }
        
        $category_name = $category['name'];
        
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la récupération de la catégorie : " . $e->getMessage();
    }
} else {
    $_SESSION['notification'] = "ID de catégorie invalide.";
    $_SESSION['notification_type'] = "error";
    header('Location: index.php');
    exit();
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name'] ?? '');
    
    // Validation du nom de la catégorie
    if (empty($new_name)) {
        $error_message = "Le nom de la catégorie ne peut pas être vide.";
    } elseif (strlen($new_name) > 100) {
        $error_message = "Le nom de la catégorie ne peut pas dépasser 100 caractères.";
    } else {
        try {
            // Vérifier si une catégorie avec ce nom existe déjà pour cet utilisateur
            $check_stmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name AND user_id = :user_id AND id != :id");
            $check_stmt->execute([
                ':name' => $new_name,
                ':user_id' => $_SESSION['user_id'],
                ':id' => $category_id
            ]);
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = "Une catégorie avec ce nom existe déjà.";
            } else {
                // Mise à jour de la catégorie
                $update_stmt = $pdo->prepare("UPDATE categories SET name = :name WHERE id = :id AND user_id = :user_id");
                $update_stmt->execute([
                    ':name' => $new_name,
                    ':id' => $category_id,
                    ':user_id' => $_SESSION['user_id']
                ]);
                
                $success_message = "La catégorie a été mise à jour avec succès.";
                $category_name = $new_name;
                
                // Définir une notification pour la redirection
                $_SESSION['notification'] = "La catégorie a été mise à jour avec succès.";
                $_SESSION['notification_type'] = "success";
                
                // Redirection vers la liste des catégories
                header('Location: index.php');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la mise à jour de la catégorie : " . $e->getMessage();
        }
    }
}

// Configuration de l'en-tête
$page_title = "Modifier une catégorie";
include '../includes/header.php';
?>

<main class="container">
    <h1><i class="fas fa-tag"></i> Modifier une catégorie</h1>
    
    <!-- Affichage des messages d'erreur -->
    <?php if ($error_message): ?>
        <div class="notification error">
            <?= htmlspecialchars($error_message) ?>
            <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>
    
    <!-- Affichage des messages de succès -->
    <?php if ($success_message): ?>
        <div class="notification success">
            <?= htmlspecialchars($success_message) ?>
            <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>
    
    <!-- Formulaire de modification -->
    <div class="card">
        <form method="POST" action="" class="form">
            <div class="form-group">
                <label for="name">Nom de la catégorie :</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($category_name) ?>" required maxlength="100" autofocus>
                <small>Maximum 100 caractères</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="index.php" class="btn secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
    
    <!-- Informations sur les tâches associées -->
    <div class="related-info card">
        <h2>Tâches associées</h2>
        <?php
        try {
            // Récupérer les tâches associées à cette catégorie
            $tasks_stmt = $pdo->prepare("
                SELECT t.id, t.title, t.status 
                FROM tasks t
                JOIN task_categories tc ON t.id = tc.task_id
                WHERE tc.category_id = :category_id AND t.user_id = :user_id
                ORDER BY t.due_date ASC
            ");
            $tasks_stmt->execute([
                ':category_id' => $category_id,
                ':user_id' => $_SESSION['user_id']
            ]);
            
            $related_tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($related_tasks) > 0): ?>
                <p>Cette catégorie est utilisée par <?= count($related_tasks) ?> tâche(s) :</p>
                <ul class="related-tasks-list">
                    <?php foreach ($related_tasks as $task): ?>
                        <li class="status-<?= htmlspecialchars($task['status']) ?>">
                            <a href="../tasks/edit.php?id=<?= $task['id'] ?>">
                                <?= htmlspecialchars($task['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Aucune tâche n'utilise cette catégorie.</p>
            <?php endif;
            
        } catch (PDOException $e) {
            echo "<p class='error'>Erreur lors de la récupération des tâches associées.</p>";
        }
        ?>
    </div>
</main>

<style>
.related-tasks-list {
    list-style-type: none;
    padding: 0;
    margin: 1rem 0;
}

.related-tasks-list li {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border-left: 3px solid #ccc;
    background-color: #f9f9f9;
}

.related-tasks-list li.status-todo {
    border-left-color: #ff9800;
}

.related-tasks-list li.status-in_progress {
    border-left-color: #2196F3;
}

.related-tasks-list li.status-done {
    border-left-color: #4CAF50;
    text-decoration: line-through;
}

.related-info {
    margin-top: 2rem;
}
</style>

<?php include '../includes/footer.php'; ?>
