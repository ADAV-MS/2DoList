<?php
require_once 'config/database.php';
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: users/login.php');
    exit();
}

try {
    // Récupération des paramètres utilisateur
    $stmt_settings = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE user_id = ?");
    $stmt_settings->execute([$_SESSION['user_id']]);
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    // Format de date sécurisé avec fallback
    $date_format = isset($settings['date_format']) ? 
        htmlspecialchars($settings['date_format']) : 'd/m/Y';

    // Récupération des tâches avec jointure des catégories
    $stmt_tasks = $pdo->prepare("
        SELECT 
            t.id,
            t.title,
            t.description,
            t.priority,
            t.status,
            t.due_date,
            COALESCE(GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', '), 'Aucune') AS categories
        FROM tasks t
        LEFT JOIN task_categories tc ON t.id = tc.task_id
        LEFT JOIN categories c ON tc.category_id = c.id
        WHERE t.user_id = ?
        GROUP BY t.id
        ORDER BY t.due_date ASC, t.priority DESC
    ");
    $stmt_tasks->execute([$_SESSION['user_id']]);
    $tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des catégories pour le formulaire
    $stmt_categories = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = ?");
    $stmt_categories->execute([$_SESSION['user_id']]);
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2DoList - Tableau de bord</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="dashboard-container">
        <!-- Notifications -->
        <?php if (isset($_SESSION['notification'])): ?>
            <div class="notification <?= htmlspecialchars($_SESSION['notification_type'] ?? 'success') ?>">
                <?= htmlspecialchars($_SESSION['notification']) ?>
                <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php unset($_SESSION['notification'], $_SESSION['notification_type']); ?>
        <?php endif; ?>

        <div class="main-content">
            <h1><i class="fas fa-tasks"></i> Mes Tâches</h1>

            <!-- Formulaire d'ajout -->
            <form action="tasks/add.php" method="POST" class="task-form card">
                <div class="form-row">
                    <input type="text" name="title" placeholder="Titre de la tâche" required maxlength="255">
                    <select name="priority" class="priority-select">
                        <option value="low">Basse priorité</option>
                        <option value="medium" selected>Priorité moyenne</option>
                        <option value="high">Haute priorité</option>
                    </select>
                </div>
                
                <textarea name="description" placeholder="Description..." maxlength="1000"></textarea>
                
                <div class="form-footer">
                    <div class="category-selection">
                        <?php if (!empty($categories)): ?>
                            <label><i class="fas fa-tags"></i> Catégories :</label>
                            <?php foreach ($categories as $category): ?>
                                <label class="category-option chip">
                                    <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($category['id']) ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="date-picker">
                        <input type="date" name="due_date" min="<?= date('Y-m-d') ?>">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus-circle"></i> Ajouter
                        </button>
                    </div>
                </div>
            </form>

            <!-- Liste des tâches -->
            <div class="tasks-list">
                <?php if (!empty($tasks)): ?>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Description</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Échéance</th>
                                <th>Catégories</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr class="priority-<?= htmlspecialchars($task['priority'] ?? '') ?>">
                                    <!-- Titre -->
                                    <td><?= htmlspecialchars($task['title'] ?? 'Sans titre') ?></td>

                                    <!-- Description avec gestion Voir plus/Voir moins -->
                                    <td class="description-cell">
                                        <?php
                                        $description = htmlspecialchars($task['description'] ?? '');
                                        if (strlen($description) > 100): ?>
                                            <div class="description-preview" data-id="<?= $task['id'] ?>">
                                                <?= substr($description, 0, 100) ?>...
                                                <a href="#" class="toggle-description" data-id="<?= $task['id'] ?>" data-action="show">Voir plus</a>
                                            </div>
                                            <div class="description-full" id="desc-<?= $task['id'] ?>" style="display: none;">
                                                <?= $description ?>
                                                <a href="#" class="toggle-description" data-id="<?= $task['id'] ?>" data-action="hide">Voir moins</a>
                                            </div>
                                        <?php else: ?>
                                            <?= $description ?>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Priorité -->
                                    <td>
                                    <?= match($task['priority'] ?? '') {
                                    'low' => '<span class="badge priority-low"><i class="fas fa-arrow-down"></i> Basse</span>',
                                    'medium' => '<span class="badge priority-medium"><i class="fas fa-minus"></i> Moyenne</span>',
                                    'high' => '<span class="badge priority-high"><i class="fas fa-arrow-up"></i> Haute</span>',
                                    default => '<span class="badge">Non défini</span>'
                                    } ?>
                                    </td>

                                    <!-- Statut -->
                                    <td>
                                        <form action="tasks/update_status.php" method="POST">
                                            <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id']) ?>">
                                            <input type="hidden" name="redirect_page" value="index">
                                            <select name="status" onchange="this.form.submit()" 
                                                    class="status-select status-<?= htmlspecialchars($task['status']) ?>">
                                                <option value="todo" <?= $task['status'] === 'todo' ? 'selected' : '' ?>>À faire</option>
                                                <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                                                <option value="done" <?= $task['status'] === 'done' ? 'selected' : '' ?>>Terminé</option>
                                            </select>
                                        </form>
                                    </td>

                                    <!-- Date d'échéance -->
                                    <td>
                                        <?php if (!empty($task['due_date'])): ?>
                                            <i class="fas fa-calendar-day"></i> 
                                            <?= date($date_format, strtotime($task['due_date'])) ?>
                                        <?php else: ?>
                                            <em>Aucune</em>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Catégories -->
                                    <td>
                                        <?php if ($task['categories'] !== 'Aucune'): ?>
                                            <div class="category-tags">
                                                <?php foreach (explode(', ', $task['categories']) as $cat): ?>
                                                    <span class="chip"><i class="fas fa-tag"></i> <?= htmlspecialchars($cat) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <em>Aucune</em>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="actions">
                                        <a href="tasks/edit.php?id=<?= htmlspecialchars($task['id']) ?>" class="btn-icon edit" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="tasks/delete.php" method="POST" class="delete-form">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($task['id']) ?>">
                                            <button type="submit" class="btn-icon delete" title="Supprimer">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h2>Aucune tâche pour le moment</h2>
                        <p>Commencez par ajouter une tâche en utilisant le formulaire ci-dessus.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <aside class="categories-sidebar">
            <h2><i class="fas fa-folder"></i> Catégories</h2>
            
            <?php if (!empty($categories)): ?>
                <ul class="category-list">
                    <?php foreach ($categories as $category): ?>
                        <li class="category-item">
                            <div class="category-header">
                                <i class="fas fa-tag"></i>
                                <span><?= htmlspecialchars($category['name']) ?></span>
                            </div>
                            <div class="category-actions">
                                <a href="categories/edit.php?id=<?= htmlspecialchars($category['id']) ?>" class="btn-icon edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="categories/delete.php" method="POST" class="delete-form">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($category['id']) ?>">
                                    <button type="submit" class="btn-icon delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="empty-message">Aucune catégorie disponible.</p>
            <?php endif; ?>
            
            <a href="categories/add.php" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Nouvelle catégorie
            </a>
        </aside>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
    <script src="js/list.js"></script>
</body>
</html>
