<?php
session_start();
require 'db.php';

// Vérification des droits (2 = prof, 3 = admin)
if (!isset($_SESSION['user']['type']) || ($_SESSION['user']['type'] != 2 && $_SESSION['user']['type'] != 3)) {
    $_SESSION['error'] = "Accès non autorisé";
    header('Location: index.php');
    exit;
}

// Récupération du devoir à modifier
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = "ID de devoir invalide";
    header('Location: prof_devoirs.php');
    exit;
}

// Récupération des données du devoir
$stmt = $pdo->prepare("SELECT * FROM devoirs WHERE id = ?");
$stmt->execute([$id]);
$devoir = $stmt->fetch();

if (!$devoir) {
    $_SESSION['error'] = "Devoir introuvable";
    header('Location: prof_devoirs.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validation des données
    $titre = trim($_POST['titre'] ?? '');
    if (empty($titre)) {
        $_SESSION['error'] = "Le titre est obligatoire";
        header("Location: modifier_devoir.php?id=$id");
        exit;
    }

    $desc = trim($_POST['description'] ?? '');
    $echeance = !empty($_POST['date_echeance']) ? $_POST['date_echeance'] : null;
    $supprimer_fichier = isset($_POST['supprimer_fichier']);

    // Gestion du fichier
    $fichier_path = $devoir['fichier_path'];

    // Si case "supprimer fichier" cochée
    if ($supprimer_fichier && $fichier_path) {
        if (file_exists($fichier_path)) {
            unlink($fichier_path);
        }
        $fichier_path = null;
    }

    // Si nouveau fichier uploadé
    if (!empty($_FILES['fichier']['name']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
        // Supprimer l'ancien fichier s'il existe
        if ($fichier_path && file_exists($fichier_path)) {
            unlink($fichier_path);
        }

        // Créer le dossier uploads si inexistant
        $destDir = 'uploads/prof/';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Générer un nom de fichier unique
        $extension = pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION);
        $nomFichier = uniqid().'.'.$extension;
        $fichier_path = $destDir . $nomFichier;

        // Déplacer le fichier uploadé
        if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $fichier_path)) {
            $_SESSION['error'] = "Erreur lors de l'enregistrement du fichier";
            header("Location: modifier_devoir.php?id=$id");
            exit;
        }
    }

    // Mise à jour en base de données
    try {
        $sql = "UPDATE devoirs SET 
                titre = :titre,
                description = :description,
                fichier_path = :fichier_path,
                date_echeance = :date_echeance
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titre' => $titre,
            ':description' => !empty($desc) ? $desc : null,
            ':fichier_path' => $fichier_path,
            ':date_echeance' => $echeance,
            ':id' => $id
        ]);

        $_SESSION['success'] = "Devoir modifié avec succès";
        header('Location: prof_devoirs.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données: " . $e->getMessage();
        header("Location: modifier_devoir.php?id=$id");
        exit;
    }
}

// Affichage de la navbar
include ($_SESSION['user']['type'] == 2) ? 'navbar.profs.php' : 'navbar.admin.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier devoir</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Modifier le devoir</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="modifier_devoir.php?id=<?= $id ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $devoir['id'] ?>">
        
        <div class="form-group">
            <label>Titre *</label>
            <input type="text" name="titre" value="<?= htmlspecialchars($devoir['titre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="5"><?= htmlspecialchars($devoir['description']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Date limite</label>
            <input type="date" name="date_echeance" value="<?= $devoir['date_echeance'] ?>">
        </div>
        
        <div class="form-group">
            <label>Pièce jointe</label>
            <?php if (!empty($devoir['fichier_path']) && file_exists($devoir['fichier_path'])): ?>
                <div class="current-file">
                    <p>Fichier actuel: 
                        <a href="<?= htmlspecialchars($devoir['fichier_path']) ?>" download>
                            <?= htmlspecialchars(basename($devoir['fichier_path'])) ?>
                        </a>
                    </p>
                    <label>
                        <input type="checkbox" name="supprimer_fichier">
                        Supprimer ce fichier
                    </label>
                </div>
            <?php endif; ?>
            <input type="file" name="fichier">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="prof_devoirs.php" class="btn btn-cancel">Annuler</a>
        </div>
    </form>
</div>
</body>
</html>