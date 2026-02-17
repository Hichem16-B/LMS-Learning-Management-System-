<?php
session_start();
require 'db.php';
// Gestion de la suppression
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    
    // Supprime d'abord les remises associées
    $pdo->prepare("DELETE FROM devoirs_remises WHERE devoir_id = ?")->execute([$id]);
    
    // Puis supprime le devoir
    $pdo->prepare("DELETE FROM devoirs WHERE id = ?")->execute([$id]);
    
    header("Location: prof_devoirs.php");
    exit;
}

// Requête pour récupérer les devoirs publiés
$devoirs = $pdo->query("SELECT * FROM devoirs ORDER BY date_publication DESC")->fetchAll();
// Requête pour récupérer les devoirs publiés
$devoirs = $pdo->query("SELECT * FROM devoirs ORDER BY date_publication DESC")->fetchAll();

/* ---------- AFFICHAGE DES REMISES D'UN DEVOIR ---------- */
$remises = [];
if (isset($_GET['voir_remises'])) {
    $id = (int)$_GET['voir_remises'];
    $sql = "SELECT dr.*, dr.id AS remise_id, u.nom, u.prenom
            FROM devoirs_remises dr
            LEFT JOIN users u ON u.id = dr.eleve_id
            WHERE dr.devoir_id = :id
            ORDER BY dr.date_remise DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $remises = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace professeur</title>
    <link rel="stylesheet" href="style.css">

     <style>
        body {
        margin-top: 120px;
        margin-bottom: 100px;
        }
     
     </style>   
    <?php
    if (isset($_SESSION['user']['type'])) {
        switch ($_SESSION['user']['type']) {
            case 2: // Professeur
                include 'navbar.profs.php';
                break;
            case 3: // Admin
                include 'navbar.admin.php';
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
    }
    

    ?>
</head>
<body>
<div class="container">
    <h1>Publier un devoir</h1>

    <form action="upload_handler.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create_devoir">
        <div><label>Titre *</label><input type="text" name="titre" required></div>
        <div><label>Description</label><textarea name="description" rows="3"></textarea></div>
        <div><label>Date limite</label><input type="date" name="date_echeance"></div>
        <div><label>Pièce jointe (optionnel)</label><input type="file" name="fichier"></div>
        <button class="btn" type="submit">Publier</button>
    </form>

    <hr>

    <h2>Devoirs publiés</h2>
    <?php if (!$devoirs): ?>
        <p>Aucun devoir pour le moment.</p>
    <?php else: ?>
       <table>
    <thead>
        <tr>
            <th>Titre</th>
            <th>Échéance</th>
            <th>Fichier</th>
            <th>Actions</th> <!-- Colonne renommée -->
        </tr>
    </thead>
    <tbody>
    <?php foreach ($devoirs as $d): ?>
        <tr>
            <td><?= htmlspecialchars($d['titre']) ?></td>
            <td><?= $d['date_echeance'] ?: '—' ?></td>
            <td>
                <?= $d['fichier_path'] 
                    ? '<a href="'.htmlspecialchars($d['fichier_path']).'" download>Télécharger</a>' 
                    : '—' ?>
            </td>
            <td class="actions">
                <a class="btn" href="?voir_remises=<?= $d['id'] ?>">Voir</a>
                <a class="btn btn-modifier" href="modifier_devoir.php?id=<?= $d['id'] ?>">Modifier</a>
                <a class="btn btn-supprimer" href="?supprimer=<?= $d['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce devoir?')">Supprimer</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
    <?php endif; ?>

    <?php if ($remises): ?>
        <h3 style="margin-top:40px">Remises du devoir #<?= (int)$_GET['voir_remises'] ?></h3>
        <table>
            <thead><tr><th>Élève</th><th>Date remise</th><th>Fichier</th></tr></thead>
            <tbody>
            <?php foreach ($remises as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></td>
                    <td><?= $r['date_remise'] ?></td>
                    <td><a href="<?= htmlspecialchars($r['fichier_path']) ?>" download>Télécharger</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

   
</div>
</body>
</html>