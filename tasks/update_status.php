<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les données du formulaire
        $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
        $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $redirect_page = filter_input(INPUT_POST, 'redirect_page', FILTER_SANITIZE_STRING); // Page d'origine

        // Vérifier que les données sont valides
        if (!$task_id || !in_array($new_status, ['todo', 'in_progress', 'done'])) {
            throw new Exception("Données invalides.");
        }

        // Mettre à jour le statut de la tâche
        $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :task_id AND user_id = :user_id");
        $stmt->execute([
            ':status' => $new_status,
            ':task_id' => $task_id,
            ':user_id' => $_SESSION['user_id']
        ]);

        // Vérifier si la mise à jour a réussi
        if ($stmt->rowCount() > 0) {
            $_SESSION['notification'] = "Statut de la tâche mis à jour avec succès.";
            $_SESSION['notification_type'] = "success";
        } else {
            throw new Exception("Impossible de mettre à jour le statut de la tâche.");
        }
    } catch (Exception $e) {
        $_SESSION['notification'] = "Erreur : " . $e->getMessage();
        $_SESSION['notification_type'] = "error";
    }

    // Rediriger vers la page d'origine (index.php ou list.php)
    if ($redirect_page === 'list') {
        header('Location: ../tasks/list.php');
    } else {
        header('Location: ../index.php'); // Par défaut, rediriger vers index.php
    }
    exit();
} else {
    // Rediriger si la méthode n'est pas POST
    header('Location: ../index.php');
    exit();
}
