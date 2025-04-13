<?php
session_start();
require_once '../config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

// Validation de l'ID de tâche
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header('Location: ../index.php');
    exit();
}

$task_id = (int)$_GET['id'];

try {
    // Récupération de la tâche
    $stmt_task = $pdo->prepare("
        SELECT t.*, GROUP_CONCAT(c.id) AS category_ids 
        FROM tasks t
        LEFT JOIN task_categories tc ON t.id = tc.task_id
        LEFT JOIN categories c ON tc.category_id = c.id
        WHERE t.id = ? AND t.user_id = ?
        GROUP BY t.id
    ");
    $stmt_task->execute([$task_id, $_SESSION['user_id']]);
    $task = $stmt_task->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        throw new Exception("Tâche introuvable");
    }

    // Récupération des catégories
    $stmt_categories = $pdo->prepare("SELECT * FROM categories WHERE user_id = ?");
    $stmt_categories->execute([$_SESSION['user_id']]);
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
        $priority = in_array($_POST['priority'], ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium';
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $selected_categories = $_POST['categories'] ?? [];

        // Mise à jour de la tâche
        $stmt_update = $pdo->prepare("
            UPDATE tasks SET 
            title = ?, 
            description = ?, 
            priority = ?, 
            due_date = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt_update->execute([$title, $description, $priority, $due_date, $task_id, $_SESSION['user_id']]);

        // Gestion des catégories
        $pdo->beginTransaction();
        $stmt_delete = $pdo->prepare("DELETE FROM task_categories WHERE task_id = ?");
        $stmt_delete->execute([$task_id]);

        if (!empty($selected_categories)) {
            $stmt_insert = $pdo->prepare("INSERT INTO task_categories (task_id, category_id) VALUES (?, ?)");
            foreach ($selected_categories as $category_id) {
                $stmt_insert->execute([$task_id, (int)$category_id]);
            }
        }
        $pdo->commit();

        header('Location: ../index.php?success=task_updated');
        exit();
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Configuration de l'en-tête
$page_title = "Modifier la tâche";
$page_css = "../css/tasks.css";
include '../includes/header.php';
?>

<main class="container">
    <h1><i class="fas fa-edit"></i> <?= htmlspecialchars($page_title) ?></h1>
    
    <form action="edit.php?id=<?= htmlspecialchars($task_id) ?>" method="POST">
        <div class="form-group">
            <label for="title"><i class="fas fa-heading"></i> Titre :</label>
            <input type="text" id="title" name="title" 
                value="<?= htmlspecialchars($task['title']) ?>" 
                required
                minlength="3"
                maxlength="255">
        </div>

        <div class="form-group">
            <label for="description"><i class="fas fa-align-left"></i> Description :</label>
            <textarea id="description" name="description" rows="4"><?= 
                htmlspecialchars($task['description']) 
            ?></textarea>
        </div>

        <div class="form-group">
            <label for="priority"><i class="fas fa-exclamation-circle"></i> Priorité :</label>
            <select id="priority" name="priority" class="priority-select">
                <option value="low" <?= $task['priority'] === 'low' ? 'selected' : '' ?>>
                    <i class="fas fa-arrow-down"></i> Basse
                </option>
                <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>
                    <i class="fas fa-minus"></i> Moyenne
                </option>
                <option value="high" <?= $task['priority'] === 'high' ? 'selected' : '' ?>>
                    <i class="fas fa-arrow-up"></i> Haute
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="due_date"><i class="fas fa-calendar-day"></i> Date d'échéance :</label>
            <input type="date" id="due_date" name="due_date" 
                value="<?= htmlspecialchars($task['due_date'] ?? '') ?>"
                min="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label><i class="fas fa-tags"></i> Catégories :</label>
            <div class="categories-grid">
                <?php 
                $associated_categories = explode(',', $task['category_ids'] ?? '');
                foreach ($categories as $category): 
                ?>
                    <label class="category-card">
                        <input type="checkbox" 
                            name="categories[]" 
                            value="<?= htmlspecialchars($category['id']) ?>"
                            <?= in_array($category['id'], $associated_categories) ? 'checked' : '' ?>>
                        <span class="category-name">
                            <?= htmlspecialchars($category['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn-save">
            <i class="fas fa-save"></i> Enregistrer
        </button>
    </form>
</main>

<?php include '../includes/footer.php'; ?>
