<?php
require_once '../config/database.php';
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
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

    // Récupération des paramètres de filtre avec validation
    $priority_filter = isset($_GET['priority']) ? htmlspecialchars($_GET['priority']) : 'all';
    $category_filter = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 'all';
    $status_filter = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : 'all';
    $search_query = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
    $sort_by = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'due_date';
    $sort_order = isset($_GET['order']) ? htmlspecialchars($_GET['order']) : 'ASC';

    // Validation des paramètres de tri
    $valid_sort_fields = ['title', 'due_date', 'priority', 'created_at'];
    $valid_sort_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_by, $valid_sort_fields)) {
        $sort_by = 'due_date';
    }
    
    if (!in_array(strtoupper($sort_order), $valid_sort_orders)) {
        $sort_order = 'ASC';
    }

    // Construction de la requête SQL avec filtres
    $sql = "
        SELECT 
            t.id,
            t.title,
            t.description,
            t.priority,
            t.status,
            t.due_date,
            COALESCE(GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', '), 'Aucune') AS categories,
            GROUP_CONCAT(c.id SEPARATOR ',') AS category_ids
        FROM tasks t
        LEFT JOIN task_categories tc ON t.id = tc.task_id
        LEFT JOIN categories c ON tc.category_id = c.id
        WHERE t.user_id = :user_id
    ";
    
    $params = [':user_id' => $_SESSION['user_id']];
    
    // Filtre de priorité
    if ($priority_filter !== 'all') {
        $sql .= " AND t.priority = :priority";
        $params[':priority'] = $priority_filter;
    }
    
    // Filtre de statut
    if ($status_filter !== 'all') {
        $sql .= " AND t.status = :status";
        $params[':status'] = $status_filter;
    }
    
    // Recherche
    if (!empty($search_query)) {
        $sql .= " AND (t.title LIKE :search OR t.description LIKE :search)";
        $params[':search'] = "%$search_query%";
    }
    
    // Filtre de catégorie
    if ($category_filter !== 'all') {
        $sql .= " AND EXISTS (
            SELECT 1 FROM task_categories tc2 
            WHERE tc2.task_id = t.id AND tc2.category_id = :category_id
        )";
        $params[':category_id'] = $category_filter;
    }
    
    $sql .= " GROUP BY t.id";
    $sql .= " ORDER BY t.$sort_by $sort_order";
    
    // Exécution de la requête principale
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des catégories pour les filtres
    $stmt_categories = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = ? ORDER BY name ASC");
    $stmt_categories->execute([$_SESSION['user_id']]);
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['notification'] = "Erreur de base de données : " . $e->getMessage();
    $_SESSION['notification_type'] = "error";
    header('Location: ../index.php');
    exit();
} catch (Exception $e) {
    $_SESSION['notification'] = "Erreur : " . $e->getMessage();
    $_SESSION['notification_type'] = "error";
    header('Location: ../index.php');
    exit();
}

// Configuration de l'en-tête
$page_title = "Liste des tâches";
$page_css = "../css/tasks.css";
include '../includes/header.php';
?>

<main class="container">
    <h1><i class="fas fa-list"></i> Liste des tâches</h1>
    
    <!-- Notifications -->
    <?php if (isset($_SESSION['notification'])): ?>
        <div class="notification <?= $_SESSION['notification_type'] ?? 'success' ?>">
            <?= htmlspecialchars($_SESSION['notification']) ?>
            <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php unset($_SESSION['notification'], $_SESSION['notification_type']); ?>
    <?php endif; ?>

    <!-- Filtres et recherche -->
    <div class="filters-container card">
        <form action="list.php" method="GET" class="filters-form">
            <div class="filter-group">
                <label for="search"><i class="fas fa-search"></i> Rechercher :</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Titre ou description...">
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="priority"><i class="fas fa-exclamation"></i> Priorité :</label>
                    <select id="priority" name="priority">
                        <option value="all" <?= $priority_filter === 'all' ? 'selected' : '' ?>>Toutes</option>
                        <option value="high" <?= $priority_filter === 'high' ? 'selected' : '' ?>>Haute</option>
                        <option value="medium" <?= $priority_filter === 'medium' ? 'selected' : '' ?>>Moyenne</option>
                        <option value="low" <?= $priority_filter === 'low' ? 'selected' : '' ?>>Basse</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category"><i class="fas fa-tag"></i> Catégorie :</label>
                    <select id="category" name="category">
                        <option value="all" <?= $category_filter === 'all' ? 'selected' : '' ?>>Toutes</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status"><i class="fas fa-tasks"></i> Statut :</label>
                    <select id="status" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tous</option>
                        <option value="todo" <?= $status_filter === 'todo' ? 'selected' : '' ?>>À faire</option>
                        <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                        <option value="done" <?= $status_filter === 'done' ? 'selected' : '' ?>>Terminé</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="sort"><i class="fas fa-sort"></i> Trier par :</label>
                    <select id="sort" name="sort">
                        <option value="due_date" <?= $sort_by === 'due_date' ? 'selected' : '' ?>>Date d'échéance</option>
                        <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>>Titre</option>
                        <option value="priority" <?= $sort_by === 'priority' ? 'selected' : '' ?>>Priorité</option>
                        <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Date de création</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="order"><i class="fas fa-sort-amount-down"></i> Ordre :</label>
                    <select id="order" name="order">
                        <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Croissant</option>
                        <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="list.php" class="btn-secondary">
                        <i class="fas fa-sync-alt"></i> Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des tâches -->
    <div class="tasks-table card">
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
                        <tr class="priority-<?= htmlspecialchars($task['priority'] ?? '') ?> status-<?= htmlspecialchars($task['status'] ?? '') ?>">
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
                                <form action="../tasks/update_status.php" method="POST" class="status-form">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="redirect_page" value="list">
                                    <select name="status" class="status-select status-<?= $task['status'] ?>" onchange="this.form.submit()">
                                        <option value="todo" <?= $task['status'] === 'todo' ? 'selected' : '' ?>>À faire</option>
                                        <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                                        <option value="done" <?= $task['status'] === 'done' ? 'selected' : '' ?>>Terminé</option>
                                    </select>
                                </form>
                            </td>

                            <!-- Date d'échéance -->
                            <td>
                                <?php if (!empty($task['due_date'])): 
                                    $due_date = DateTime::createFromFormat('Y-m-d', $task['due_date']);
                                    $today = new DateTime();
                                    if ($due_date):
                                        $interval = $today->diff($due_date);
                                        $is_past = $due_date < $today;
                                ?>
                                    <span class="due-date <?= $is_past ? 'overdue' : ($interval->days <= 2 ? 'due-soon' : '') ?>">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars($due_date->format($date_format)) ?>
                                        <?php if ($is_past): ?>
                                            <span class="overdue-badge">En retard</span>
                                        <?php elseif ($interval->days <= 2): ?>
                                            <span class="due-soon-badge">Bientôt</span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <em>Date invalide</em>
                                <?php endif; ?>
                                <?php else: ?>
                                    <em>Aucune</em>
                                <?php endif; ?>
                            </td>

                            <!-- Catégories -->
                            <td>
                                <?php if (!empty($task['categories']) && $task['categories'] !== 'Aucune'): ?>
                                    <div class="category-tags">
                                        <?php 
                                        $cat_names = explode(', ', $task['categories']);
                                        $cat_ids = explode(',', $task['category_ids'] ?? '');
                                        foreach ($cat_names as $index => $cat_name): 
                                            $cat_id = $cat_ids[$index] ?? null;
                                            if ($cat_id):
                                        ?>
                                            <a href="?category=<?= $cat_id ?>" class="category-chip">
                                                <i class="fas fa-tag"></i> <?= htmlspecialchars($cat_name) ?>
                                            </a>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <em>Aucune</em>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="actions">
                                <!-- Modifier -->
                                <a href="../tasks/edit.php?id=<?= htmlspecialchars($task['id']) ?>" class="btn-icon edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Supprimer -->
                                <form action="../tasks/delete.php" method="POST" class="delete-form">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($task['id']) ?>">
                                    <input type="hidden" name="redirect" value="list">
                                    <button type="submit" class="btn-icon delete" title="Supprimer" onclick="return confirm('Supprimer cette tâche ?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Message si aucune tâche -->
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h2>Aucune tâche trouvée</h2>
                <p>Aucune tâche ne correspond à vos critères de recherche.</p>
                <a href="list.php" class="btn-primary">
                    <i class="fas fa-sync-alt"></i>
                    <span>Réinitialiser les filtres</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="../js/script.js"></script>
<script src="../js/list.js"></script>
