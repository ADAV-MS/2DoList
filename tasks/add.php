<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

// Initialisation des variables
$error = '';
$success = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupération et validation des données
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
        $priority = in_array($_POST['priority'] ?? 'medium', ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium';
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $selected_categories = $_POST['categories'] ?? [];

        // Validation du titre
        if (empty($title)) {
            throw new Exception("Le titre de la tâche est obligatoire.");
        }

        // Insertion de la tâche
        $stmt_task = $pdo->prepare("
            INSERT INTO tasks 
            (user_id, title, description, status, priority, due_date)
            VALUES (:user_id, :title, :description, 'todo', :priority, :due_date)
        ");
        
        $stmt_task->execute([
            ':user_id' => $_SESSION['user_id'],
            ':title' => $title,
            ':description' => $description,
            ':priority' => $priority,
            ':due_date' => $due_date
        ]);

        // Récupération de l'ID de la nouvelle tâche
        $task_id = $pdo->lastInsertId();

        // Association des catégories
        if (!empty($selected_categories)) {
            // Vérification de l'existence des catégories
            $placeholders = rtrim(str_repeat('?,', count($selected_categories)), ',');
            
            $stmt_check = $pdo->prepare("
                SELECT id FROM categories 
                WHERE user_id = ? 
                AND id IN ($placeholders)
            ");
            $stmt_check->execute(array_merge([$_SESSION['user_id']], $selected_categories));
            
            $valid_categories = $stmt_check->fetchAll(PDO::FETCH_COLUMN);

            // Insertion des associations
            $stmt_link = $pdo->prepare("INSERT INTO task_categories (task_id, category_id) VALUES (?, ?)");
            foreach ($valid_categories as $category_id) {
                $stmt_link->execute([$task_id, $category_id]);
            }
        }

        // Redirection avec message de succès
        header('Location: ../index.php?success=task_added');
        exit();
    }
} catch (PDOException $e) {
    $error = "Erreur de base de données : " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Si erreur, afficher le formulaire avec les données saisies
$_SESSION['form_data'] = $_POST;
$_SESSION['error'] = $error;
header('Location: ../index.php');
exit();
?>
