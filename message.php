<?php
session_start();
require 'db.php';

// Vérification de l'utilisateur connecté
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: index.php');
    exit;
}

// Traitement de l'envoi de message privé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destinataire_id'], $_POST['contenu'])) {
    $destinataireId = intval($_POST['destinataire_id']);
    $contenu = trim($_POST['contenu']);

    if (!empty($contenu) && $destinataireId > 0) {
        $stmt = $pdo->prepare("INSERT INTO messages_prives (expediteur_id, destinataire_id, contenu) VALUES (:expediteur_id, :destinataire_id, :contenu)");
        $stmt->execute([
            'expediteur_id' => $userId,
            'destinataire_id' => $destinataireId,
            'contenu' => $contenu
        ]);
        $success = "Message envoyé avec succès.";
    } else {
        $error = "Le contenu du message ne peut pas être vide.";
    }
}

// Récupération des messages reçus
$messagesRecus = $pdo->prepare("
    SELECT mp.*, u.nom AS expediteur_nom
    FROM messages_prives mp
    JOIN users u ON mp.expediteur_id = u.id
    WHERE mp.destinataire_id = :user_id
    ORDER BY mp.date_envoi DESC
");
$messagesRecus->execute(['user_id' => $userId]);
$messagesRecus = $messagesRecus->fetchAll();

// Récupération des utilisateurs pour la liste des destinataires
$utilisateurs = $pdo->query("SELECT id, nom FROM users WHERE id != $userId")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie privée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background-color: #f8f9fa; margin-left: 250px; }
        h1 { text-align: center; margin-bottom: 2rem; }
        .message-form { margin-bottom: 2rem; }
        .message-list { margin-top: 2rem; }
        .message-item { padding: 1rem; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 1rem; background-color: #fff; }
        .message-item .sender { font-weight: bold; color: #3498db; }
        .message-item .timestamp { font-size: 0.8rem; color: #888; }
    </style>
    <?php if (isset($_SESSION['user']['type'])) {
        switch ($_SESSION['user']['type']) {
            case 2: // Professeur
                include 'navbar.profs.php';
                break;
            case 3: // Admin
                include 'navbar.admin.php';
            case 1: // Étudiant
                include 'navbar.php';
                break;
            default:
                // Redirection si le type n'est pas autorisé
                header('Location: index.php');
                exit;
        }
    } else {
        // Si pas de session, rediriger vers la page de login
        header('Location: index.php');
        exit;
    } ?>
</head>
<body>

    

    <div class="container">
        <h1>Messagerie privée</h1>

        <!-- Affichage des messages de succès ou d'erreur -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Formulaire d'envoi de message -->
        <form method="POST" class="message-form bg-white p-4 rounded shadow-sm">
            <div class="mb-3">
                <label for="destinataire_id" class="form-label">Destinataire</label>
                <select name="destinataire_id" id="destinataire_id" class="form-select" required>
                    <option value="">-- Choisir un destinataire --</option>
                    <?php foreach ($utilisateurs as $utilisateur): ?>
                        <option value="<?= $utilisateur['id'] ?>"><?= htmlspecialchars($utilisateur['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="contenu" class="form-label">Votre message</label>
                <textarea name="contenu" id="contenu" class="form-control" rows="4" required></textarea>
            </div>
            <button type="submit" id="sendMessageButton" class="btn btn-primary">Envoyer</button>
        </form>

        <!-- Affichage des messages reçus -->
        <div class="message-list">
            <h2>Messages reçus</h2>
            <?php if (!empty($messagesRecus)): ?>
                <?php foreach ($messagesRecus as $msg): ?>
                    <div class="message-item">
                        <p class="sender"><?= htmlspecialchars($msg['expediteur_nom']) ?></p>
                        <p><?= htmlspecialchars($msg['contenu']) ?></p>
                        <p class="timestamp"><?= htmlspecialchars($msg['date_envoi']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Aucun message reçu pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.message-form');
            const sendMessageButton = document.getElementById('sendMessageButton');

            // Empêche la soumission automatique
            form.addEventListener('submit', function (event) {
                const destinataire = document.getElementById('destinataire_id').value;
                const contenu = document.getElementById('contenu').value.trim();

                if (!destinataire || !contenu) {
                    event.preventDefault(); // Empêche la soumission
                    alert('Veuillez remplir tous les champs avant d\'envoyer le message.');
                }
            });
        });
    </script>
</body>
</html>