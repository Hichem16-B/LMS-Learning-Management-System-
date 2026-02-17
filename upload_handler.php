<?php
session_start();
require 'db.php';

$action = $_POST['action'] ?? '';
switch ($action) {
    case 'create_devoir':
        $titre = trim($_POST['titre'] ?? '');
        if ($titre === '') { exit("Titre manquant."); }
        
        $desc = trim($_POST['description'] ?? '');
        $echeance = $_POST['date_echeance'] ?: null;

        $fichier_path = null;
        if (!empty($_FILES['fichier']['name']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $destDir = 'uploads/prof/';
            if (!is_dir($destDir)) mkdir($destDir, 0777, true);
            $fichier_path = $destDir . uniqid() . '_' . basename($_FILES['fichier']['name']);
            move_uploaded_file($_FILES['fichier']['tmp_name'], $fichier_path);
        }

        $sql = "INSERT INTO devoirs (titre, description, fichier_path, date_echeance, date_publication)
                VALUES (:t, :d, :f, :e, NOW())";
        $pdo->prepare($sql)->execute([
            't' => $titre, 
            'd' => $desc ?: null,
            'f' => $fichier_path, 
            'e' => $echeance
        ]);

        header("Location: prof_devoirs.php"); 
        exit;

    case 'submit_devoir':
        $devoir_id = (int)($_POST['devoir_id'] ?? 0);
        $eleve_id = (int)($_POST['eleve_id'] ?? 0);
        if (!$devoir_id || !$eleve_id) { exit("Paramètres manquants."); }

        if (empty($_FILES['fichier']['name']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            exit("Erreur upload.");
        }

        $destDir = 'uploads/eleves/';
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
        $fichier_path = $destDir . uniqid() . '_' . basename($_FILES['fichier']['name']);
        move_uploaded_file($_FILES['fichier']['tmp_name'], $fichier_path);

        $sql = "INSERT INTO devoirs_remises (devoir_id, eleve_id, fichier_path, date_remise)
                VALUES (:d, :e, :f, NOW())";
        $pdo->prepare($sql)->execute([
            'd' => $devoir_id, 
            'e' => $eleve_id, 
            'f' => $fichier_path
        ]);

        header("Location: eleve_upload.php?eleve_id={$eleve_id}");
        exit;

    default:
        exit("Action inconnue.");
}