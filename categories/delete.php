<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
        header('Location: ../index.php?error=invalid_id');
        exit();
    }

    $category_id = (int)$_POST['id'];

    try {
        // Supprimer les associations avec les tâches
        $stmt_cleanup = $pdo->prepare("DELETE FROM task_categories WHERE category_id = ?");
        $stmt_cleanup->execute([$category_id]);

        // Supprimer la catégorie
        $stmt_delete = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $stmt_delete->execute([$category_id, $_SESSION['user_id']]);

        if ($stmt_delete->rowCount() > 0) {
            header('Location: ../index.php?success=category_deleted');
        } else {
            header('Location: ../index.php?error=category_not_found');
        }
        exit();
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
