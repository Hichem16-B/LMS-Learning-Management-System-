<?php
session_start();
require 'db.php';

// Vérification plus complète de la session
if (!isset($_SESSION['user']['id']) || empty($_SESSION['user']['id'])) {
    // Redirection vers la page de connexion avec un message d'erreur
    $_SESSION['error'] = "Veuillez vous connecter pour accéder à cette page";
    header('Location: login.php');
    exit;
}

$eleve_id = (int)$_SESSION['user']['id']; // Conversion en entier pour la sécurité

// Vérification supplémentaire si l'utilisateur est bien un élève (type = 1)
if ($_SESSION['user']['type'] != 1) {
    $_SESSION['error'] = "Accès réservé aux étudiants";
    header('Location: index.php');
    exit;
}

/* ---------- RÉCUP INFOS ÉLÈVE ---------- */
$stmt = $pdo->prepare("SELECT nom, prenom FROM users WHERE id = ?");
$stmt->execute([$eleve_id]);
$eleve = $stmt->fetch();

/* ---------- RÉCUP DEVOIRS ---------- */
$devoirs = $pdo->query("SELECT * FROM devoirs ORDER BY date_publication DESC")->fetchAll();

/* ---------- RÉCUP REMISES ---------- */
$sql = "SELECT devoir_id FROM devoirs_remises WHERE eleve_id = :id";
$remis = $pdo->prepare($sql); 
$remis->execute(['id' => $eleve_id]);
$deja_remis = array_column($remis->fetchAll(), 'devoir_id');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace élève - <?= htmlspecialchars($eleve['prenom'] ?? '') ?></title>
    <link rel="stylesheet" href="style.css">
    <?php include 'navbar.php'; ?>
</head>
<body>
<div class="container">
    <h1>Espace élève: <?= htmlspecialchars($eleve['prenom'].' '.$eleve['nom'] ?? '') ?></h1>
    
    <h2>Devoirs à faire</h2>
    <?php if (!$devoirs): ?>
        <p>Aucun devoir disponible.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Description</th>
                    <th>Échéance</th>
                    <th>Fichier</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($devoirs as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['titre']) ?></td>
                    <td><?= nl2br(htmlspecialchars($d['description'])) ?></td>
                    <td><?= $d['date_echeance'] ?: '—' ?></td>
                    <td>
                        <?= $d['fichier_path']
                            ? '<a href="'.htmlspecialchars($d['fichier_path']).'" download>Télécharger</a>'
                            : '—' ?>
                    </td>
                    <td>
                        <?php if (in_array($d['id'], $deja_remis)): ?>
                            ✅ Déjà remis
                        <?php else: ?>
                        <form action="upload_handler.php" method="post" enctype="multipart/form-data" style="display:inline">
                            <input type="hidden" name="action" value="submit_devoir">
                            <input type="hidden" name="devoir_id" value="<?= $d['id'] ?>">
                            <input type="hidden" name="eleve_id" value="<?= $eleve_id ?>">
                            <input type="file" name="fichier" required>
                            <button type="submit" class="btn">Déposer</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

   
</div>
</body>
</html>