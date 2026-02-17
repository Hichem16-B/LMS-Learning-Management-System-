<?php
session_start();
require 'db.php';

// Vérification connexion étudiant
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 1) {
    $_SESSION['error'] = "Veuillez vous connecter";
    header('Location: index.php');
    exit;
}

$etudiant_id = (int)$_SESSION['user']['id'];

// Récupération des absences avec jointures sur planning → cours
$stmt = $pdo->prepare("
    SELECT 
        a.planning_id,
        p.jour AS date_seance,
        p.heure_debut,
        p.heure_fin,
        c.nom AS cours_nom,
        a.statut,
        a.justificatif
    FROM absences a
    JOIN planning p ON a.planning_id = p.id
    JOIN cours c ON p.cours_id = c.id
    WHERE a.etudiant_id = ?
    ORDER BY p.jour DESC
");
$stmt->execute([$etudiant_id]);
$absences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des stats
$stats = ['present' => 0, 'absent' => 0, 'justifie' => 0];
foreach ($absences as $a) {
    $stats[$a['statut']]++;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Absences</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* ---------- STYLES UNIFIÉS POUR ABSENCES.ETUDIANT.PHP ---------- */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f8f9fa;
        color: #2c3e50;
        padding-left: 250px;
        transition: all 0.3s;
    }

    .container {
        padding: 20px;
        margin-top: 20px;
        max-width: 1200px;
    }

    /* Titre principal */
    .page-title {
        color: #2c3e50;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
        font-weight: 600;
        text-align: center;
    }

    /* Cartes de statistiques */
    .stats-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s;
        text-align: center;
        height: 100%;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stats-card .card-body {
        padding: 20px;
    }

    .stats-card .card-title {
        font-size: 1.1rem;
        margin-bottom: 10px;
        font-weight: 500;
    }

    .stats-card .card-text {
        font-size: 2.5rem;
        font-weight: 600;
    }

    /* Tableau des absences */
    .table-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-top: 20px;
    }

    .table {
        width: 100%;
        margin-bottom: 0;
        color: #2c3e50;
    }

    .table th {
        background-color: #2c3e50;
        color: white;
        font-weight: 500;
        padding: 12px 15px;
        border-bottom: none;
    }

    .table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(52, 152, 219, 0.05);
    }

    /* Badges de statut */
    .badge {
        padding: 8px 12px;
        font-weight: 500;
        border-radius: 20px;
        font-size: 0.85rem;
        text-transform: uppercase;
    }

    .badge-present {
        background-color: #28a745;
        color: white;
    }

    .badge-absent {
        background-color: #dc3545;
        color: white;
    }

    .badge-justifie {
        background-color: #ffc107;
        color: #212529;
    }

    /* Alertes */
    .alert {
        border-radius: 8px;
        padding: 15px 20px;
        margin: 20px 0;
        border: none;
    }

    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    /* Responsive */
    @media (max-width: 992px) {
        body {
            padding-left: 0;
        }
        
        .container {
            padding: 15px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
    }

    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .page-title {
            font-size: 1.5rem;
        }
        
        .stats-card .card-text {
            font-size: 2rem;
        }
    }
</style>
    <?php include 'navbar.php'; ?>
</head>
<body>

<div class="container">

    <h1 class="mb-4 text-center">Mes Présences / Absences</h1>

    <!-- Statistiques -->
    <div class="row g-4 mb-4 justify-content-center">
        <div class="col-md-3">
            <div class="card bg-success text-white text-center">
                <div class="card-body">
                    <h5 class="card-title">Présences</h5>
                    <p class="card-text display-6"><?= $stats['present'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white text-center">
                <div class="card-body">
                    <h5 class="card-title">Absences</h5>
                    <p class="card-text display-6"><?= $stats['absent'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark text-center">
                <div class="card-body">
                    <h5 class="card-title">Justifiées</h5>
                    <p class="card-text display-6"><?= $stats['justifie'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des absences -->
    <?php if (!empty($absences)): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Date du cours</th>
                        <th>Cours</th>
                        <th>Horaire</th>
                        <th>Statut</th>
                        <th>Justificatif</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($absences as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst($a['date_seance'])) ?></td>
                            <td><?= htmlspecialchars($a['cours_nom']) ?></td>
                            <td><?= substr($a['heure_debut'], 0, 5) ?> - <?= substr($a['heure_fin'], 0, 5) ?></td>
                            <td>
                                <span class="badge rounded-pill badge-<?= $a['statut'] === 'present' ? 'present' : ($a['statut'] === 'absent' ? 'absent' : 'justifie') ?>">
                                    <?= ucfirst($a['statut']) ?>
                                </span>
                            </td>
                            <td><?= !empty($a['justificatif']) ? htmlspecialchars($a['justificatif']) : '<em>Aucun</em>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            Aucune absence ou présence enregistrée.
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>