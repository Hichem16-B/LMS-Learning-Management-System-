<?php
session_start();
require 'db.php';

// Vérification que l'utilisateur est un professeur
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 2) {
    header('Location: index.php');
    exit;
}

$prof_id = $_SESSION['user']['id'];

// Suppression d'un test
if (isset($_GET['delete_test'])) {
    $test_id = (int)$_GET['delete_test'];
    
    // Vérifier que le test appartient bien au professeur
    $stmt = $pdo->prepare("SELECT id FROM tests WHERE id = ? AND createur_id = ?");
    $stmt->execute([$test_id, $prof_id]);
    
    if ($stmt->fetch()) {
        try {
            $pdo->beginTransaction();
            
            // Supprimer les réponses associées
            $pdo->prepare("DELETE r FROM reponses_possibles r 
                          JOIN questions q ON r.question_id = q.id 
                          WHERE q.test_id = ?")->execute([$test_id]);
            
           // Supprimer les résultats
            $pdo->prepare("DELETE FROM resultats_qcm WHERE test_id = ?")->execute([$test_id]);
            

            // Supprimer les questions
            $pdo->prepare("DELETE FROM questions WHERE test_id = ?")->execute([$test_id]);
            
            
            // Supprimer le test
            $pdo->prepare("DELETE FROM tests WHERE id = ?")->execute([$test_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Test supprimé avec succès";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de la suppression du test";
        }
    }
    
    header("Location: my_tests.php");
    exit;
}

// Récupérer les tests du professeur avec stats
$tests = $pdo->prepare("SELECT t.id, t.titre, t.date_creation, m.nom as module_nom,
                       (SELECT COUNT(*) FROM questions WHERE test_id = t.id) as question_count,
                       (SELECT COUNT(*) FROM resultats_qcm WHERE test_id = t.id) as result_count
                       FROM tests t
                       JOIN modules m ON t.module_id = m.id
                       WHERE t.createur_id = ?
                       ORDER BY t.date_creation DESC");
$tests->execute([$prof_id]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { padding-left: 250px; background-color: #f8f9fa; }
        .main-content { padding: 20px; }
        .test-card { transition: transform 0.3s, box-shadow 0.3s; }
        .test-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <?php include 'navbar.profs.php'; ?>

    <div class="main-content">
        <div class="container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Mes Tests</h1>
                <a href="create_qcm.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouveau Test
                </a>
            </div>

            <?php if ($tests->rowCount() === 0): ?>
                <div class="alert alert-info">
                    Vous n'avez créé aucun test pour le moment.
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php while ($test = $tests->fetch()): ?>
                        <div class="col">
                            <div class="card test-card h-100">
                                <div class="card-header bg-light d-flex justify-content-between">
                                    <h3 class="h5 mb-0"><?= htmlspecialchars($test['titre']) ?></h3>
                                    <div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?= $test['question_count'] ?> Q
                                        </span>
                                        <span class="badge bg-secondary rounded-pill ms-1">
                                            <?= $test['result_count'] ?> É
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <i class="fas fa-book text-muted"></i> 
                                        <?= htmlspecialchars($test['module_nom']) ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-calendar text-muted"></i> 
                                        Créé le <?= date('d/m/Y', strtotime($test['date_creation'])) ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-white d-flex justify-content-between">
                                    <a href="view_results.php?test_id=<?= $test['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-chart-bar"></i> Résultats
                                    </a>
                                    <div>
                                        <a href="take_quiz.php?test_id=<?= $test['id'] ?>" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-eye"></i> Prévisualiser
                                        </a>
                                        <a href="?delete_test=<?= $test['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce test ? Toutes les données associées seront perdues.');">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>