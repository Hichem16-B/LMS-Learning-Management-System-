<?php
session_start();
require 'db.php';

// Récupération des cours et classes
$cours = $pdo->query("SELECT * FROM cours")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes")->fetchAll();

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['classe_id'], $_POST['cours_id'], $_POST['jour'], $_POST['heure_debut'], $_POST['heure_fin'])) {
    // Vérifier si le créneau existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM planning WHERE classe_id = :classe_id AND cours_id = :cours_id AND jour = :jour AND heure_debut = :heure_debut AND heure_fin = :heure_fin");
    $stmt->execute([
        'classe_id' => $_POST['classe_id'],
        'cours_id' => $_POST['cours_id'],
        'jour' => $_POST['jour'],
        'heure_debut' => $_POST['heure_debut'],
        'heure_fin' => $_POST['heure_fin']
    ]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $_SESSION['message'] = "Ce créneau existe déjà dans le planning.";
        $_SESSION['message_type'] = 'warning';
    } else {
        // Insérer le créneau s'il n'existe pas
        $stmt = $pdo->prepare("INSERT INTO planning (classe_id, cours_id, jour, heure_debut, heure_fin) VALUES (:classe_id, :cours_id, :jour, :heure_debut, :heure_fin)");
        $stmt->execute([
            'classe_id' => $_POST['classe_id'],
            'cours_id' => $_POST['cours_id'],
            'jour' => $_POST['jour'],
            'heure_debut' => $_POST['heure_debut'],
            'heure_fin' => $_POST['heure_fin']
        ]);
        $_SESSION['message'] = "Créneau ajouté avec succès.";
        $_SESSION['message_type'] = 'success';
    }

    header('Location: planification.php');
    exit;
}

// Traitement de la suppression d'un créneau
if (isset($_POST['delete_planning'])) {
    $planning_id = intval($_POST['planning_id']);
    $stmt = $pdo->prepare("DELETE FROM planning WHERE id = :id");
    $stmt->execute(['id' => $planning_id]);
    $_SESSION['message'] = "Créneau supprimé avec succès.";
    $_SESSION['message_type'] = 'success';
    header('Location: planification.php');
    exit;
}

// Récupération du planning existant
$planning = $pdo->query("
    SELECT p.*, c.nom AS classe, co.nom AS cours
    FROM planning p
    JOIN classes c ON p.classe_id = c.id
    JOIN cours co ON p.cours_id = co.id
    ORDER BY p.jour, p.heure_debut
")->fetchAll();

// Récupération du message de session et suppression immédiate
$message = $_SESSION['message'] ?? null;
$message_type = $_SESSION['message_type'] ?? null;
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planification des cours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background-color: #f8f9fa;
                margin-left: 250px; }
        h1, h2 { text-align: center; margin-bottom: 2rem; }
    </style>
</head>
<body>

<?php if (isset($_SESSION['user']['type'])) {
        switch ($_SESSION['user']['type']) {
            case 2: // Professeur
                include 'navbar.profs.php';
                break;
            case 3: // Admin
                include 'navbar.admin.php';
                break;
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

    <h1>Planification des Cours</h1>
<div class="mb-4">
    <a href="home.admin.php" class="btn btn-secondary">Retour à l'accueil admin</a>
</div>
    <?php if (isset($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-4 rounded shadow-sm mb-5">
        <div class="row mb-3">
            <div class="col">
                <label for="classe_id" class="form-label">Classe</label>
                <select name="classe_id" id="classe_id" class="form-select" required>
                    <option value="">-- Choisir une classe --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col">
                <label for="cours_id" class="form-label">Cours</label>
                <select name="cours_id" id="cours_id" class="form-select" required>
                    <option value="">-- Choisir un cours --</option>
                    <?php foreach ($cours as $co): ?>
                        <option value="<?= $co['id'] ?>"><?= htmlspecialchars($co['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col">
                <label for="jour" class="form-label">Jour</label>
                <select name="jour" id="jour" class="form-select" required>
                    <option value="Lundi">Lundi</option>
                    <option value="Mardi">Mardi</option>
                    <option value="Mercredi">Mercredi</option>
                    <option value="Jeudi">Jeudi</option>
                    <option value="Vendredi">Vendredi</option>
                    <option value="Samedi">Samedi</option>
                </select>
            </div>
            <div class="col">
                <label for="heure_debut" class="form-label">Heure début</label>
                <input type="time" name="heure_debut" id="heure_debut" class="form-control" required>
            </div>
            <div class="col">
                <label for="heure_fin" class="form-label">Heure fin</label>
                <input type="time" name="heure_fin" id="heure_fin" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Ajouter au planning</button>
    </form>

    <h2>Planning Actuel</h2>

    <table class="table table-bordered bg-white">
        <thead class="table-light">
            <tr>
                <th>Classe</th>
                <th>Cours</th>
                <th>Jour</th>
                <th>Heure début</th>
                <th>Heure fin</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($planning as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['classe']) ?></td>
                    <td><?= htmlspecialchars($p['cours']) ?></td>
                    <td><?= htmlspecialchars($p['jour']) ?></td>
                    <td><?= htmlspecialchars($p['heure_debut']) ?></td>
                    <td><?= htmlspecialchars($p['heure_fin']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="planning_id" value="<?= $p['id'] ?>">
                            <button type="submit" name="delete_planning" class="btn btn-danger btn-sm">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>