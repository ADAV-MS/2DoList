<?php
require_once '../config/database.php';
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

// Initialisation des messages
$success_message = '';
$error_message = '';

// Récupération des paramètres actuels de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des paramètres: " . $e->getMessage();
}

// Valeurs par défaut si les paramètres n'existent pas
$date_format = $current_settings['date_format'] ?? 'd/m/Y';
$tasks_per_page = $current_settings['tasks_per_page'] ?? '10';
$default_view = $current_settings['default_view'] ?? 'list';
$theme = $current_settings['theme'] ?? 'light';

// Traitement du formulaire lors de la soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération uniquement du format de date (seul paramètre fonctionnel)
        $new_date_format = $_POST['date_format'] ?? 'd/m/Y';

        // Validation du format de date
        $valid_date_formats = ['d/m/Y', 'Y-m-d', 'm/d/Y', 'd.m.Y'];
        if (!in_array($new_date_format, $valid_date_formats)) {
            $new_date_format = 'd/m/Y';
        }

        // Vérifier si le paramètre existe déjà
        $check_stmt = $pdo->prepare("SELECT id FROM settings WHERE user_id = ? AND `key` = ?");
        $check_stmt->execute([$_SESSION['user_id'], 'date_format']);
        
        if ($check_stmt->rowCount() > 0) {
            // Mise à jour
            $update_stmt = $pdo->prepare("UPDATE settings SET `value` = ? WHERE user_id = ? AND `key` = ?");
            $update_stmt->execute([$new_date_format, $_SESSION['user_id'], 'date_format']);
        } else {
            // Insertion
            $insert_stmt = $pdo->prepare("INSERT INTO settings (user_id, `key`, `value`) VALUES (?, ?, ?)");
            $insert_stmt->execute([$_SESSION['user_id'], 'date_format', $new_date_format]);
        }

        // Mise à jour de la variable locale
        $date_format = $new_date_format;

        $success_message = "Votre format de date a été mis à jour avec succès.";
        
        // Définir une notification pour la session
        $_SESSION['notification'] = "Votre format de date a été mis à jour avec succès.";
        $_SESSION['notification_type'] = "success";
        
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la mise à jour des paramètres: " . $e->getMessage();
        
        // Définir une notification d'erreur
        $_SESSION['notification'] = "Erreur lors de la mise à jour des paramètres.";
        $_SESSION['notification_type'] = "error";
    }
}

// Configuration de l'en-tête
$page_title = "Paramètres";
$page_css = "../css/settings.css";
include '../includes/header.php';
?>

<main class="container">
    <h1><i class="fas fa-cog"></i> Paramètres utilisateur</h1>
    
    <!-- Notifications -->
    <?php if (isset($_SESSION['notification'])): ?>
        <div class="notification <?= $_SESSION['notification_type'] ?? 'success' ?>">
            <?= htmlspecialchars($_SESSION['notification']) ?>
            <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php unset($_SESSION['notification'], $_SESSION['notification_type']); ?>
    <?php endif; ?>
    
    <!-- Formulaire des paramètres -->
    <div class="settings-card">
        <form action="settings.php" method="POST">
            <div class="settings-section">
                <h2><i class="fas fa-calendar-alt"></i> Affichage des dates</h2>
                
                <div class="form-group">
                    <label for="date_format">Format de date :</label>
                    <select id="date_format" name="date_format">
                        <option value="d/m/Y" <?= $date_format === 'd/m/Y' ? 'selected' : '' ?>>31/12/2025 (JJ/MM/AAAA)</option>
                        <option value="Y-m-d" <?= $date_format === 'Y-m-d' ? 'selected' : '' ?>>2025-12-31 (AAAA-MM-JJ)</option>
                        <option value="m/d/Y" <?= $date_format === 'm/d/Y' ? 'selected' : '' ?>>12/31/2025 (MM/JJ/AAAA)</option>
                        <option value="d.m.Y" <?= $date_format === 'd.m.Y' ? 'selected' : '' ?>>31.12.2025 (JJ.MM.AAAA)</option>
                    </select>
                    <p class="form-help">Ce format sera utilisé pour afficher les dates dans toute l'application.</p>
                </div>
            </div>

            <div class="settings-section">
                <h2><i class="fas fa-list"></i> Affichage des tâches</h2>
                
                <div class="form-group">
                    <label for="tasks_per_page">Nombre de tâches par page : <span class="coming-soon">(Fonctionnalité à venir)</span></label>
                    <input type="number" id="tasks_per_page" name="tasks_per_page" value="<?= htmlspecialchars($tasks_per_page) ?>" min="5" max="100" disabled>
                    <p class="form-help">Définit combien de tâches sont affichées par page dans la liste des tâches.</p>
                </div>
                
                <div class="form-group">
                    <label for="default_view">Vue par défaut : <span class="coming-soon">(Fonctionnalité à venir)</span></label>
                    <select id="default_view" name="default_view" disabled>
                        <option value="list" <?= $default_view === 'list' ? 'selected' : '' ?>>Liste</option>
                        <option value="calendar" <?= $default_view === 'calendar' ? 'selected' : '' ?>>Calendrier</option>
                    </select>
                    <p class="form-help">Choisissez comment vous souhaitez voir vos tâches par défaut.</p>
                </div>
            </div>

            <div class="settings-section">
                <h2><i class="fas fa-paint-brush"></i> Apparence</h2>
                
                <div class="form-group">
                    <label>Thème : <span class="coming-soon">(Fonctionnalité à venir)</span></label>
                    <div class="theme-options">
                        <label class="theme-card <?= $theme === 'light' ? 'selected' : '' ?>">
                            <input type="radio" name="theme" value="light" <?= $theme === 'light' ? 'checked' : '' ?> disabled>
                            <div class="theme-preview light-theme">
                                <i class="fas fa-sun"></i>
                                <span>Clair</span>
                            </div>
                        </label>
                        
                        <label class="theme-card <?= $theme === 'dark' ? 'selected' : '' ?>">
                            <input type="radio" name="theme" value="dark" <?= $theme === 'dark' ? 'checked' : '' ?> disabled>
                            <div class="theme-preview dark-theme">
                                <i class="fas fa-moon"></i>
                                <span>Sombre</span>
                            </div>
                        </label>
                        
                        <label class="theme-card <?= $theme === 'system' ? 'selected' : '' ?>">
                            <input type="radio" name="theme" value="system" <?= $theme === 'system' ? 'checked' : '' ?> disabled>
                            <div class="theme-preview system-theme">
                                <i class="fas fa-laptop"></i>
                                <span>Système</span>
                            </div>
                        </label>
                    </div>
                    <p class="form-help">Le thème sombre peut réduire la fatigue oculaire en conditions de faible luminosité.</p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Enregistrer les paramètres
                </button>
                <a href="../index.php" class="btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="../js/script.js"></script>

<style>
.coming-soon {
    display: inline-block;
    background-color: #f0ad4e;
    color: #fff;
    font-size: 0.8em;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 8px;
}

/* Style pour les éléments désactivés */
input:disabled, select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.theme-card input:disabled + .theme-preview {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
