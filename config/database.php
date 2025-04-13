<?php
// Paramètres de connexion à la base de données
$host = 'localhost';       // Hôte de la base de données (généralement localhost)
$dbname = '2DoList';       // Nom de votre base de données
$username = 'root';        // Nom d'utilisateur MySQL
$password = '';            // Mot de passe MySQL (vide par défaut sur XAMPP/WAMP)

try {
    // Création d'une connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Configuration des attributs PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Activer les exceptions pour les erreurs SQL
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Mode de récupération par défaut : tableau associatif
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Désactiver l'émulation des requêtes préparées pour plus de sécurité

} catch (PDOException $e) {
    // En cas d'erreur, afficher un message et arrêter le script
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
