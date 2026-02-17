<?php

session_start();
require 'db.php';

// Vérification connexion professeur
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 2) {
    $_SESSION['error'] = "Veuillez vous connecter";
    header("Location: index.php");
    exit;
}
$prof_id = (int)$_SESSION['user']['id'];

// Gestion des messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Récupération des cours du professeur
$stmt = $pdo->prepare("SELECT id, nom FROM cours WHERE prof_id = ?");
$stmt->execute([$prof_id]);
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des plannings
$plannings = [];
if (!empty($cours)) {
    $cours_ids = array_column($cours, 'id');
    $placeholders = implode(',', array_fill(0, count($cours_ids), '?'));

    $stmt = $pdo->prepare("
        SELECT p.*, c.nom AS cours_nom 
        FROM planning p
        JOIN cours c ON p.cours_id = c.id
        WHERE p.cours_id IN ($placeholders)
        ORDER BY p.jour, p.heure_debut
    ");
    $stmt->execute($cours_ids);
    $plannings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Traitement formulaire absences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_absences'])) {
    $planning_id = (int)$_POST['planning_id'];

    // Vérifie que le cours appartient au prof
    $check = $pdo->prepare("
        SELECT p.id 
        FROM planning p
        JOIN cours c ON p.cours_id = c.id
        WHERE p.id = ? AND c.prof_id = ?
    ");
    $check->execute([$planning_id, $prof_id]);

    if ($check->rowCount() > 0) {
        try {
            $pdo->beginTransaction();

            foreach ($_POST['statut'] as $etudiant_id => $statut) {
                $etudiant_id = (int)$etudiant_id;
                $justificatif = isset($_POST['justificatif'][$etudiant_id]) ? 
                    trim(htmlspecialchars($_POST['justificatif'][$etudiant_id])) : null;

                $stmt = $pdo->prepare("
                    INSERT INTO absences (planning_id, etudiant_id, statut, justificatif)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        statut = VALUES(statut),
                        justificatif = VALUES(justificatif)
                ");
                $stmt->execute([$planning_id, $etudiant_id, $statut, $justificatif]);
            }

            $_SESSION['success'] = "Absences mises à jour avec succès.";
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de la mise à jour des absences.";
        }
    } else {
        $_SESSION['error'] = "Vous n'avez pas les droits sur cette séance.";
    }

    header("Location: absences.prof.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Absences</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-left: 250px; background-color: #f8f9fa; }
        .container { margin-top: 2rem; padding: 20px; }
        @media (max-width: 992px) { body { padding-left: 0; } }
        <style>
    /* ---------- STYLES POUR LA PAGE ABSENCES ---------- */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fa;
        padding-left: 250px;
        color: #2c3e50;
        transition: all 0.3s ease;
    }

    .container {
        margin-top: 20px;
        padding: 20px;
        max-width: 1200px;
    }

    /* Cartes */
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .card-header {
        background-color: #3498db;
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        border-bottom: none;
    }

    /* Accordéon */
    .accordion-item {
        margin-bottom: 10px;
        border: 1px solid #e0e6ed;
        border-radius: 8px;
        overflow: hidden;
    }

    .accordion-button {
        background-color: #f8f9fa;
        font-weight: 500;
        padding: 15px 20px;
    }

    .accordion-button:not(.collapsed) {
        background-color: #e3f2fd;
        color: #2c3e50;
        box-shadow: none;
    }

    .accordion-body {
        padding: 20px;
        background-color: #fff;
    }

    /* Tableaux */
    .table {
        width: 100%;
        margin-bottom: 20px;
    }

    .table th {
        background-color: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        padding: 12px 15px;
        border-bottom: 2px solid #e0e6ed;
    }

    .table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e0e6ed;
        vertical-align: middle;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(52, 152, 219, 0.05);
    }

    /* Formulaires */
    .form-select, .form-control {
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 8px 12px;
        transition: all 0.3s;
    }

    .form-select:focus, .form-control:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }

    /* Boutons */
    .btn {
        border-radius: 6px;
        padding: 8px 16px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-primary {
        background-color: #3498db;
        border-color: #3498db;
    }

    .btn-primary:hover {
        background-color: #2980b9;
        border-color: #2980b9;
    }

    /* Alertes */
    .alert {
        border-radius: 6px;
        padding: 12px 20px;
        margin-bottom: 20px;
        border: none;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    /* Titres */
    h1 {
        color: #2c3e50;
        margin-bottom: 25px;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 992px) {
        body {
            padding-left: 0;
        }
        
        .container {
            padding: 15px;
        }
    }

    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .accordion-button {
            padding: 12px 15px;
            font-size: 0.95rem;
        }
    }
</style>
    </style>
</head>
<body>

<?php include 'navbar.profs.php'; ?>

<div class="container">

    <!-- Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h1 class="mb-4 text-center">Gestion des Absences</h1>

    <!-- Liste des séances -->
    <div class="card">
        <div class="card-header bg-primary text-white"><h5 class="mb-0">Séances passées</h5></div>
        <div class="card-body">

            <?php if (!empty($plannings)): ?>
                <div class="accordion" id="planningAccordion">
                    <?php foreach ($plannings as $p): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#p<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['cours_nom']) ?> - <?= ucfirst($p['jour']) ?> - <?= substr($p['heure_debut'], 0, 5) ?> à <?= substr($p['heure_fin'], 0, 5) ?>
                                </button>
                            </h2>
                            <div id="p<?= $p['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#planningAccordion">
                                <div class="accordion-body">
                                    <form method="POST">
                                        <input type="hidden" name="planning_id" value="<?= $p['id'] ?>">

                                        <?php
                                        // Récupère TOUS les étudiants (sans classe)
                                        $stmt = $pdo->query("SELECT id, nom, prenom FROM users WHERE type = 1 ORDER BY nom");
                                        $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        // Récupère les absences existantes
                                        $stmt = $pdo->prepare("SELECT * FROM absences WHERE planning_id = ?");
                                        $stmt->execute([$p['id']]);
                                        $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        $abs_map = array_column($absences, null, 'etudiant_id');
                                        ?>

                                        <?php if (!empty($etudiants)): ?>
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Étudiant</th>
                                                        <th>Statut</th>
                                                        <th>Justificatif</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($etudiants as $e): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></td>
                                                            <td>
                                                                <select name="statut[<?= $e['id'] ?>]" class="form-select">
                                                                    <option value="present" <?= (isset($abs_map[$e['id']]) && $abs_map[$e['id']]['statut'] == 'present') ? 'selected' : '' ?>>Présent</option>
                                                                    <option value="absent" <?= (!isset($abs_map[$e['id']]) || $abs_map[$e['id']]['statut'] == 'absent') ? 'selected' : '' ?>>Absent</option>
                                                                    <option value="justifie" <?= (isset($abs_map[$e['id']]) && $abs_map[$e['id']]['statut'] == 'justifie') ? 'selected' : '' ?>>Justifié</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="justificatif[<?= $e['id'] ?>]"
                                                                       class="form-control"
                                                                       value="<?= isset($abs_map[$e['id']]) ? htmlspecialchars($abs_map[$e['id']]['justificatif'] ?? '') : '' ?>">
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <button type="submit" name="update_absences" class="btn btn-primary">Enregistrer</button>
                                        <?php else: ?>
                                            <div class="alert alert-info">Aucun étudiant trouvé.</div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Aucune séance trouvée.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>