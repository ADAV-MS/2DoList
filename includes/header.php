<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2DoList - <?= htmlspecialchars($page_title ?? 'Tableau de bord') ?></title>
    <link rel="stylesheet" href="../css/style.css"> <!-- Styles globaux -->
    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($page_css) ?>"> <!-- Styles spécifiques à la page -->
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- Font Awesome -->
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1><i class="fas fa-list-alt"></i> 2DoList</h1>
            <?php if (isset($_SESSION['user_id'])): ?>
                <nav class="main-nav">
                    <a href="../index.php"><i class="fas fa-home"></i> Accueil</a>
                    <a href="../tasks/list.php"><i class="fas fa-tasks"></i> Mes Tâches</a>
                    <a href="../categories/add.php"><i class="fas fa-folder-plus"></i> Nouvelle Catégorie</a>
                    <a href="../users/settings.php"><i class="fas fa-cog"></i> Paramètres</a> <!-- Nouveau lien ajouté -->
                    <a href="../users/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </nav>
            <?php else: ?>
                <nav class="main-nav">
                    <a href="../users/login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a>
                    <a href="../users/register.php"><i class="fas fa-user-plus"></i> Inscription</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>
