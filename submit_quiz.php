<?php
session_start();
require 'db.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Vérifier que l'utilisateur a bien passé un test
if (!isset($_SESSION['test_data'])) {
    header("Location: take_quiz.php");
    exit();
}

// Vérifier si des réponses ont été soumises
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['answers'])) {
    die("Aucune réponse soumise");
}

// Vérifier que le test correspond à la session
if ($_POST['test_id'] != $_SESSION['test_data']['test_id']) {
    die("Incohérence dans les données du test");
}

// Vérifier le temps écoulé si limité
if ($_SESSION['test_data']['time_limit'] > 0) {
    $time_taken = time() - $_SESSION['test_data']['start_time'];
    if ($time_taken > $_SESSION['test_data']['time_limit']) {
        die("Temps écoulé !");
    }
}

// Calculer le score
$score = 0;
$total_questions = 0;
$detailed_results = [];

// Pour chaque question
foreach ($_POST['answers'] as $question_id => $user_answers) {
    $total_questions++;
    $user_answers = is_array($user_answers) ? $user_answers : [$user_answers];
    
    // Récupérer les informations de la question
    $stmt = $pdo->prepare("SELECT texte_question, type_question FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();
    
    // Récupérer toutes les réponses possibles
    $stmt = $pdo->prepare("SELECT id, texte_reponse, est_correcte FROM reponses_possibles WHERE question_id = ?");
    $stmt->execute([$question_id]);
    $all_answers = $stmt->fetchAll();
    
    // Trouver les bonnes réponses
    $correct_answers = array_filter($all_answers, function($a) { return $a['est_correcte']; });
    $correct_answer_ids = array_column($correct_answers, 'id');
    
    // Vérifier les réponses de l'utilisateur
    $is_correct = false;
    
    if ($question['type_question'] === 'multiple') {
        // Pour les questions à réponses multiples, toutes les bonnes réponses doivent être sélectionnées
        $is_correct = count(array_intersect($user_answers, $correct_answer_ids)) === count($correct_answer_ids)
                   && count($user_answers) === count($correct_answer_ids);
    } else {
        // Pour les questions à réponse unique
        $is_correct = in_array($user_answers[0], $correct_answer_ids);
    }
    
    if ($is_correct) {
        $score++;
    }
    
    // Stocker les résultats détaillés
    $detailed_results[] = [
        'question_id' => $question_id,
        'question_text' => $question['texte_question'],
        'user_answers' => array_map(function($id) use ($all_answers) {
            return $all_answers[array_search($id, array_column($all_answers, 'id'))]['texte_reponse'];
        }, $user_answers),
        'correct_answer_ids' => $correct_answer_ids,
        'is_correct' => $is_correct,
        'all_answers' => $all_answers
    ];
}

// Calculer la note selon le système choisi
$percentage = ($score / $total_questions) * 100;
$grade = calculateGrade($percentage, $_SESSION['test_data']['grading_system']);

// Enregistrement des résultats dans la base de données
try {
    $pdo->beginTransaction();
    
    // Enregistrement du résultat global
    $stmt = $pdo->prepare("
        INSERT INTO resultats_qcm (test_id, etudiant_id, score, total_questions, pourcentage, note, temps_passe)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['test_data']['test_id'],
        $_SESSION['user']['id'],
        $score,
        $total_questions,
        $percentage,
        $grade,
        time() - $_SESSION['test_data']['start_time']
    ]);
    $result_id = $pdo->lastInsertId();
    
    // Enregistrement des réponses détaillées
    foreach ($detailed_results as $result) {
        foreach ($result['user_answers'] as $user_answer) {
            $answer_id = array_search($user_answer, array_column($result['all_answers'], 'texte_reponse'));
            $answer_id = $result['all_answers'][$answer_id]['id'];
            
            $stmt = $pdo->prepare("
                INSERT INTO reponses_etudiants (resultat_id, question_id, reponse_id, est_correcte)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $result_id,
                $result['question_id'],
                $answer_id,
                $result['is_correct'] ? 1 : 0
            ]);
        }
    }
    
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erreur lors de l'enregistrement des résultats: " . $e->getMessage());
}

// Fonction de calcul de la note
function calculateGrade($percentage, $grading_system) {
    switch ($grading_system) {
        case '10':
            return round($percentage / 10, 1) . '/10';
        case '20':
            return round($percentage / 5, 1) . '/20';
        case '100':
            return round($percentage) . '/100';
        case 'mention':
            if ($percentage >= 90) return 'Excellent';
            if ($percentage >= 75) return 'Très bien';
            if ($percentage >= 60) return 'Bien';
            if ($percentage >= 50) return 'Passable';
            return 'Insuffisant';
        default:
            return $percentage . '%';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats du QCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-left: 250px; background-color: #f8f9fa; }
        .container { margin-top: 20px; padding: 20px; }
        .score-summary { 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 30px;
            text-align: center;
        }
        .score-summary.success { background-color: #d4edda; color: #155724; }
        .score-summary.fail { background-color: #f8d7da; color: #721c24; }
        .question-result { margin-bottom: 20px; padding: 15px; border-radius: 5px; }
        .question-result.correct { background-color: #d4edda; border-left: 5px solid #28a745; }
        .question-result.incorrect { background-color: #f8d7da; border-left: 5px solid #dc3545; }
        .result-icon { font-weight: bold; margin-left: 10px; }
        .result-icon.correct { color: #28a745; }
        .result-icon.incorrect { color: #dc3545; }
        @media (max-width: 992px) { body { padding-left: 0; } }
    </style>
</head>
<body>
    <?php 
    if ($_SESSION['user']['type'] == 2) {
        include 'navbar.profs.php';
    } else {
        include 'navbar.php';
    }
    ?>

    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1>Résultats du QCM</h1>
            </div>
            <div class="card-body">
                <h2><?= htmlspecialchars($_SESSION['test_data']['title']) ?></h2>
                
                <div class="score-summary <?= $percentage >= 50 ? 'success' : 'fail' ?>">
                    <div class="display-4"><?= round($percentage, 1) ?>%</div>
                    <div class="h3">Score: <?= $score ?>/<?= $total_questions ?></div>
                    <div class="h2">Note: <?= $grade ?></div>
                    <?php if ($_SESSION['test_data']['time_limit'] > 0): ?>
                        <div class="text-muted mt-2">
                            Temps passé: <?= floor((time() - $_SESSION['test_data']['start_time']) / 60) ?> min 
                            <?= (time() - $_SESSION['test_data']['start_time']) % 60 ?> sec
                        </div>
                    <?php endif; ?>
                </div>
                
                <h3 class="mb-3">Détail des réponses:</h3>
                
                <?php foreach ($detailed_results as $i => $result): ?>
                    <div class="question-result <?= $result['is_correct'] ? 'correct' : 'incorrect' ?>">
                        <div class="question-text mb-2">
                            <strong>Question <?= $i+1 ?>:</strong> <?= htmlspecialchars($result['question_text']) ?>
                        </div>
                        
                        <div class="user-answer mb-2">
                            <strong>Votre réponse:</strong> 
                            <?= implode(', ', array_map('htmlspecialchars', $result['user_answers'])) ?>
                            <span class="result-icon <?= $result['is_correct'] ? 'correct' : 'incorrect' ?>">
                                <?= $result['is_correct'] ? '✓ Correct' : '✗ Incorrect' ?>
                            </span>
                        </div>
                        
                        <?php if (!$result['is_correct']): ?>
                            <div class="correct-answer">
                                <strong>Réponse(s) correcte(s):</strong> 
                                <?= implode(', ', array_map(function($id) use ($result) {
                                    return htmlspecialchars($result['all_answers'][array_search($id, array_column($result['all_answers'], 'id'))]['texte_reponse']);
                                }, $result['correct_answer_ids'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="take_quiz.php?test_id=<?= $_SESSION['test_data']['test_id'] ?>" class="btn btn-primary">
                        Refaire ce test
                    </a>
                    <a href="take_quiz.php" class="btn btn-secondary">
                        Retour à la liste des tests
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Nettoyer la session
    unset($_SESSION['test_data']);
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>