<?php
session_start();
require 'db.php';

// Récupération du planning existant
$planning = $pdo->query("
    SELECT p.*, c.nom AS classe, co.nom AS cours
    FROM planning p
    JOIN classes c ON p.classe_id = c.id
    JOIN cours co ON p.cours_id = co.id
    ORDER BY p.jour, p.heure_debut
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning des Professeurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background-color: #f8f9fa; }
        h1 { text-align: center; margin-bottom: 2rem; }
    </style>
</head>
<body>

    <h1>Planning des Cours</h1>

    <table class="table table-bordered bg-white">
        <thead class="table-light">
            <tr>
                <th>Classe</th>
                <th>Cours</th>
                <th>Jour</th>
                <th>Heure début</th>
                <th>Heure fin</th>
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
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>