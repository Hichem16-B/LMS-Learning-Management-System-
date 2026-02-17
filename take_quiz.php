<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

// Résultats de l'utilisateur
$results_stmt = $pdo->prepare("SELECT r.*, t.titre as test_titre 
    FROM resultats_qcm r
    JOIN tests t ON r.test_id = t.id
    WHERE r.etudiant_id = ? 
    ORDER BY r.date_soumission DESC");
$results_stmt->execute([$user_id]);
$user_results = $results_stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

// Test sélectionné
if (isset($_GET['test_id'])) {
    $test_id = (int)$_GET['test_id'];

    // Récup test
    $stmt = $pdo->prepare("SELECT t.*, m.nom as module_nom 
        FROM tests t 
        JOIN modules m ON t.module_id = m.id 
        WHERE t.id = ?");
    $stmt->execute([$test_id]);
    $test = $stmt->fetch();

    if (!$test) {
        die("Test introuvable");
    }

    // Vérif présence questions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE test_id = ?");
    $stmt->execute([$test_id]);
    if ($stmt->fetchColumn() == 0) {
        header("Location: create_qcm.php?test_id=".$test_id);
        exit();
    }

    // Récupération questions
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ? ORDER BY RAND()");
    $stmt->execute([$test_id]);
    $questions = $stmt->fetchAll();

    $_SESSION['test_data'] = [
        'test_id' => $test_id,
        'title' => $test['titre'],
        'start_time' => time(),
        'time_limit' => $test['temps_limite'] * 60,
        'grading_system' => $test['systeme_notation'],
        'questions' => $questions
    ];
} else {
    $stmt = $pdo->query("SELECT t.*, m.nom as module_nom 
        FROM tests t 
        JOIN modules m ON t.module_id = m.id 
        ORDER BY t.date_creation DESC");
    $tests = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Passer un QCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-left: 250px;
            background-color: #f8f9fa;
        }
        .navbar-vertical {
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
        }
        .main-content {
            padding: 20px;
        }
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .result-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        @media (max-width: 768px) {
            body {
                padding-left: 0;
            }
            .navbar-vertical {
                width: 100%;
                height: auto;
                position: relative;
            }
        }
    </style>
</head>
<body>

<div class="navbar-vertical">
    <?php 
    if ($_SESSION['user']['type'] == 2) {
        include 'navbar.profs.php';
    } else {
        include 'navbar.php';
    }
    ?>
</div>

<div class="main-content">
    <div class="container py-4">

        <?php if (isset($test)): ?>
            <?php if ($test['temps_limite'] > 0): ?>
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <span>Temps restant :</span>
                    <span class="fw-bold" id="time"><?= floor($test['temps_limite']) ?>:00</span>
                </div>
            <?php endif; ?>

            <h1><?= htmlspecialchars($test['titre']) ?></h1>
            <form method="post" action="submit_quiz.php" id="quizForm">
                <input type="hidden" name="test_id" value="<?= $test_id ?>">

                <?php foreach ($questions as $i => $q): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Question <?= $i + 1 ?>: <?= htmlspecialchars($q['texte_question']) ?></h3>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM reponses_possibles WHERE question_id = ? ORDER BY RAND()");
                            $stmt->execute([$q['id']]);
                            $answers = $stmt->fetchAll();
                            ?>
                            <?php foreach ($answers as $a): ?>
                                <div class="form-check mb-2">
                                    <?php if ($q['type_question'] === 'multiple'): ?>
                                        <input class="form-check-input" type="checkbox" name="answers[<?= $q['id'] ?>][]" value="<?= $a['id'] ?>" id="a<?= $a['id'] ?>">
                                    <?php else: ?>
                                        <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $a['id'] ?>" id="a<?= $a['id'] ?>" required>
                                    <?php endif; ?>
                                    <label class="form-check-label" for="a<?= $a['id'] ?>">
                                        <?= htmlspecialchars($a['texte_reponse']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5 py-2">Soumettre le QCM</button>
                </div>
            </form>

            <?php if ($test['temps_limite'] > 0): ?>
                <script>
                    window.onload = function () {
                        startTimer(<?= $test['temps_limite'] * 60 ?>);
                    };
                </script>
            <?php endif; ?>

        <?php else: ?>
            <h1 class="mb-4">Choisissez un QCM à passer</h1>
            <?php if (empty($tests)): ?>
                <div class="alert alert-info">Aucun test disponible.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($tests as $t): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card test-card">
                                <?php if (isset($user_results[$t['id']])): ?>
                                    <span class="result-badge"><i class="fas fa-check"></i></span>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h3><?= htmlspecialchars($t['titre']) ?></h3>
                                    <p class="text-muted">Module : <?= htmlspecialchars($t['module_nom']) ?></p>
                                    <p>Durée : <?= $t['temps_limite'] ? $t['temps_limite'] . ' min' : 'Illimitée' ?></p>
                                    <a href="take_quiz.php?test_id=<?= $t['id'] ?>" class="btn btn-primary w-100">
                                        <?= isset($user_results[$t['id']]) ? 'Repasser' : 'Passer' ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<!-- Script anti-cheat et dépendances -->
<script src="anti_cheat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>