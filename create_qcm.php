<?php
session_start();
require 'db.php';

// Vérification que l'utilisateur est un professeur
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 2) {
    header('Location: index.php');
    exit;
}

$prof_id = $_SESSION['user']['id'];

// Création d'un nouveau test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
    $stmt = $pdo->prepare("INSERT INTO tests (titre, systeme_notation, temps_limite, createur_id, module_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['test_title'],
        $_POST['grading_system'],
        $_POST['time_limit'],
        $prof_id,
        $_POST['module_id']
    ]);
    $_SESSION['test_id'] = $pdo->lastInsertId();
    $_SESSION['test_title'] = $_POST['test_title'];
    header("Location: create_qcm.php");
    exit();
}

// Ajout d'une question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $question_type = isset($_POST['multiple_answers']) ? 'multiple' : 'simple';
    
    $stmt = $pdo->prepare("INSERT INTO questions (test_id, texte_question, type_question) VALUES (?, ?, ?)");
    $stmt->execute([
        $_SESSION['test_id'],
        $_POST['question'],
        $question_type
    ]);
    $question_id = $pdo->lastInsertId();

    // Insertion des réponses
    foreach ($_POST['choices'] as $index => $choice) {
        if (!empty(trim($choice))) {
            $is_correct = in_array($index, $_POST['correct_answers'] ?? []) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO reponses_possibles (question_id, texte_reponse, est_correcte) VALUES (?, ?, ?)");
            $stmt->execute([$question_id, trim($choice), $is_correct]);
        }
    }
    
    header("Location: create_qcm.php");
    exit();
}

// Réinitialisation
if (isset($_GET['reset'])) {
    unset($_SESSION['test_id']);
    unset($_SESSION['test_title']);
    header("Location: create_qcm.php");
    exit();
}

// Vérifier si le test a des questions
$has_questions = false;
if (isset($_SESSION['test_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE test_id = ?");
    $stmt->execute([$_SESSION['test_id']]);
    $has_questions = $stmt->fetchColumn() > 0;
}

// Récupérer les modules du professeur
$modules = $pdo->prepare("SELECT * FROM modules WHERE id_prof = ?");
$modules->execute([$prof_id]);
$modules = $modules->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de QCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-left: 250px; background-color: #f8f9fa; }
        .container { margin-top: 20px; padding: 20px; }
        .card { margin-bottom: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .answer-group { margin-bottom: 10px; display: flex; align-items: center; }
        .answer-group input[type="text"] { flex: 1; margin-right: 10px; }
        @media (max-width: 992px) { body { padding-left: 0; } }
    </style>
</head>
<body>
    <?php include 'navbar.profs.php'; ?>

    <div class="container">
        <?php if (!isset($_SESSION['test_id'])): ?>
            <form method="post" class="card">
                <div class="card-header bg-primary text-white">
                    <h2>Créer un nouveau QCM</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="test_title" class="form-label">Titre du test</label>
                        <input type="text" class="form-control" id="test_title" name="test_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="module_id" class="form-label">Module associé</label>
                        <select class="form-select" id="module_id" name="module_id" required>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="grading_system" class="form-label">Système de notation</label>
                        <select class="form-select" id="grading_system" name="grading_system" required>
                            <option value="20">Sur 20</option>
                            <option value="10">Sur 10</option>
                            <option value="100">Sur 100</option>
                            <option value="mention">Mentions</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="time_limit" class="form-label">Temps limite (minutes)</label>
                        <input type="number" class="form-control" id="time_limit" name="time_limit" min="0" value="30">
                    </div>
                    
                    <button type="submit" name="create_test" class="btn btn-primary">Créer le test</button>
                </div>
            </form>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <h2>Éditer le QCM : <?= htmlspecialchars($_SESSION['test_title']) ?></h2>
                    <a href="?reset=1" class="btn btn-danger">Terminer</a>
                </div>
                
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="question" class="form-label">Question</label>
                            <textarea class="form-control" id="question" name="question" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="multiple_answers" id="multiple_answers">
                            <label class="form-check-label" for="multiple_answers">Autoriser plusieurs réponses correctes</label>
                        </div>
                        
                        <div class="mb-3">
                            <h4>Réponses :</h4>
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <div class="answer-group mb-2">
                                    <input type="text" class="form-control" name="choices[]" placeholder="Réponse <?= $i+1 ?>" required>
                                    <div class="form-check ms-2">
                                        <input class="form-check-input" type="checkbox" name="correct_answers[]" value="<?= $i ?>">
                                        <label class="form-check-label">Correcte</label>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="add_question" class="btn btn-primary">Ajouter la question</button>
                            <?php if ($has_questions): ?>
                                <a href="take_quiz.php?test_id=<?= $_SESSION['test_id'] ?>" class="btn btn-success">Prévisualiser le test</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des questions existantes -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Questions existantes</h3>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ?");
                    $stmt->execute([$_SESSION['test_id']]);
                    $questions = $stmt->fetchAll();
                    
                    if (empty($questions)): ?>
                        <p class="text-muted">Aucune question ajoutée pour le moment.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($questions as $q): ?>
                                <li class="list-group-item">
                                    <strong><?= htmlspecialchars($q['texte_question']) ?></strong>
                                    <small class="text-muted">(<?= $q['type_question'] === 'multiple' ? 'Multiples réponses' : 'Une seule réponse' ?>)</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>