<?php
session_start();
require 'db.php';



// Vérification de l'authentification
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Vérification des droits (type = 2 pour professeur)
if ($_SESSION['user']['type'] != 2) {
    die("Accès refusé : Réservé aux professeurs");
}

$prof_id = $_SESSION['user']['id'];

// Récupération des tests créés par ce professeur
try {
    $stmt = $pdo->prepare("SELECT t.id, t.titre, m.nom as module_nom 
                          FROM tests t
                          JOIN modules m ON t.module_id = m.id
                          WHERE t.createur_id = ?
                          ORDER BY t.titre");
    $stmt->execute([$prof_id]);
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des tests : " . $e->getMessage());
}

// Traitement de la sélection d'un test
$selected_test = null;
$results = [];
$test_info = [];
$stats = [];

if (isset($_GET['test_id']) && is_numeric($_GET['test_id'])) {
    $test_id = (int)$_GET['test_id'];
    
    try {
        // Vérification que le test appartient bien à ce professeur
        $stmt = $pdo->prepare("SELECT t.*, m.nom as module_nom 
                             FROM tests t
                             JOIN modules m ON t.module_id = m.id
                             WHERE t.id = ? AND t.createur_id = ?");
        $stmt->execute([$test_id, $prof_id]);
        $test_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_info) {
            $selected_test = $test_id;
            
            // Récupération des statistiques globales
            $stmt = $pdo->prepare("SELECT 
                                 COUNT(*) as total_students,
                                 AVG(pourcentage) as avg_percentage,
                                 MIN(pourcentage) as min_percentage,
                                 MAX(pourcentage) as max_percentage
                                 FROM resultats_qcm
                                 WHERE test_id = ?");
            $stmt->execute([$test_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Récupération des résultats détaillés
            $stmt = $pdo->prepare("SELECT r.*, u.nom, u.prenom 
                                 FROM resultats_qcm r
                                 JOIN users u ON r.etudiant_id = u.id
                                 WHERE r.test_id = ?
                                 ORDER BY r.pourcentage DESC");
            $stmt->execute([$test_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        die("Erreur lors de la récupération des résultats : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats des QCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-left: 250px;
            background-color: #f8f9fa;
        }
        .main-content {
            padding: 20px;
        }
        .stats-card {
            border-left: 4px solid;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 5px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .progress {
            height: 25px;
        }
        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.profs.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2>Résultats des étudiants</h2>
                </div>
                <div class="card-body">
                    <form method="get" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-9">
                                <select name="test_id" class="form-select" required>
                                    <option value="">Sélectionnez un test...</option>
                                    <?php foreach ($tests as $test): ?>
                                        <option value="<?= $test['id'] ?>" 
                                            <?= $selected_test == $test['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($test['titre']) ?> 
                                            (<?= htmlspecialchars($test['module_nom']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Afficher
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php if ($selected_test): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3><?= htmlspecialchars($test_info['titre']) ?></h3>
                                <p class="mb-0">Module: <?= htmlspecialchars($test_info['module_nom']) ?></p>
                            </div>
                            <div class="card-body">
                                <?php if (empty($results)): ?>
                                    <div class="alert alert-warning">
                                        Aucun résultat trouvé pour ce test.
                                    </div>
                                <?php else: ?>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-3">
                                            <div class="stats-card border-left-primary">
                                                <h5>Participants</h5>
                                                <p class="stat-value text-primary"><?= $stats['total_students'] ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stats-card border-left-success">
                                                <h5>Moyenne</h5>
                                                <p class="stat-value text-success"><?= round($stats['avg_percentage'], 1) ?>%</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stats-card border-left-info">
                                                <h5>Meilleur score</h5>
                                                <p class="stat-value text-info"><?= round($stats['max_percentage'], 1) ?>%</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="stats-card border-left-warning">
                                                <h5>Plus bas score</h5>
                                                <p class="stat-value text-warning"><?= round($stats['min_percentage'], 1) ?>%</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Étudiant</th>
                                                    <th>Score</th>
                                                    <th>Note</th>
                                                    <th>Pourcentage</th>
                                                    <th>Temps passé</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($results as $result): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($result['prenom'] . ' ' . $result['nom']) ?></td>
                                                        <td><?= $result['score'] ?>/<?= $result['total_questions'] ?></td>
                                                        <td><strong><?= $result['note'] ?></strong></td>
                                                        <td>
                                                            <div class="progress">
                                                                <div class="progress-bar" 
                                                                     style="width: <?= $result['pourcentage'] ?>%" 
                                                                     aria-valuenow="<?= $result['pourcentage'] ?>" 
                                                                     aria-valuemin="0" 
                                                                     aria-valuemax="100">
                                                                    <?= $result['pourcentage'] ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?= gmdate("i:s", $result['temps_passe']) ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($result['date_soumission'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Sélectionnez un test pour afficher les résultats.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>