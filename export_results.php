<?php
session_start();
require 'db.php';

// Vérification des droits
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 2) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['test_id']) || !is_numeric($_GET['test_id'])) {
    header('Location: view_results.php');
    exit;
}

$test_id = (int)$_GET['test_id'];
$prof_id = $_SESSION['user']['id'];

// Vérification que le test appartient au professeur
$test_query = $pdo->prepare("SELECT t.id, t.titre, m.nom as module_nom 
                           FROM tests t
                           JOIN modules m ON t.module_id = m.id
                           WHERE t.id = ? AND t.createur_id = ?");
$test_query->execute([$test_id, $prof_id]);
$test_info = $test_query->fetch(PDO::FETCH_ASSOC);

if (!$test_info) {
    header('Location: view_results.php');
    exit;
}

// Récupération des résultats
$results_query = $pdo->prepare("SELECT u.nom, u.prenom, r.score, r.total_questions, 
                               r.pourcentage, r.note, r.temps_passe, r.date_soumission
                               FROM resultats_qcm r
                               JOIN users u ON r.etudiant_id = u.id
                               WHERE r.test_id = ?
                               ORDER BY r.pourcentage DESC");
$results_query->execute([$test_id]);

// Préparation du fichier CSV
$filename = 'resultats_' . preg_replace('/[^a-z0-9]/i', '_', $test_info['titre']) . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// En-têtes CSV
fputcsv($output, [
    'Nom',
    'Prénom',
    'Score',
    'Total Questions',
    'Pourcentage',
    'Note',
    'Temps passé (secondes)',
    'Date de soumission'
], ';');

// Données
while ($row = $results_query->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['nom'],
        $row['prenom'],
        $row['score'],
        $row['total_questions'],
        $row['pourcentage'],
        $row['note'],
        $row['temps_passe'],
        $row['date_soumission']
    ], ';');
}

fclose($output);
exit;