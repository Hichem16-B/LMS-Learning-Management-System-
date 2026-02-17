<?php
session_start();
require 'db.php';

// Vérification de la connexion et du rôle étudiant (type = 1)
if (!isset($_SESSION['user']['type']) || $_SESSION['user']['type'] != 1) {
    header('Location: index.php');
    exit;
}

// Configuration identique à la pagination des professeurs
$itemsParPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Même requête COUNT que pour les profs
$totalModules = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
$totalPages = ceil($totalModules / $itemsParPage);
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Même requête paginée
$offset = ($page - 1) * $itemsParPage;
$modules = $pdo->query("SELECT * FROM modules ORDER BY nom ASC LIMIT $offset, $itemsParPage")
              ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Même style que home.profs.php -->
    <style>
        body { padding-left: 250px; background-color: #f8f9fa; transition: padding 0.3s; }
        .container { margin-top: 2rem; padding: 20px; }
        .card { transition: transform 0.3s, box-shadow 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .pagination .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; }
        @media (max-width: 992px) { body { padding-left: 0; } }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <!-- Même titre que la version prof -->
    <h1 class="mb-4 text-center">Cours par Module</h1>
    
    <?php if (empty($modules)): ?>
        <div class="alert alert-info">Aucun module disponible.</div>
    <?php else: ?>
        <!-- Même structure de grille -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($modules as $module): ?>
                <?php 
                // Même requête pour récupérer les cours par module
                $stmt = $pdo->prepare("SELECT * FROM cours WHERE module_id = ? ORDER BY nom ASC");
                $stmt->execute([$module['id']]);
                $cours = $stmt->fetchAll();
                ?>
                
                <!-- Même carte que pour les profs -->
                <div class="col">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?= htmlspecialchars($module['nom']) ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($cours)): ?>
                                <!-- Même liste groupée -->
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($cours as $c): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><?= htmlspecialchars($c['nom']) ?></span>
                                                <div>
                                                    <?php if ($c['fichier_path']): ?>
                                                        <?php $ext = strtolower(pathinfo($c['fichier_path'], PATHINFO_EXTENSION)); ?>
                                                        <!-- Mêmes boutons que pour les profs -->
                                                        <a href="<?= htmlspecialchars($c['fichier_path']) ?>" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           target="<?= in_array($ext, ['pdf','jpg','png']) ? '_blank' : '_self' ?>">
                                                            <?= in_array($ext, ['pdf','jpg','png']) ? 'Voir' : 'Télécharger' ?>
                                                        </a>
                                                    <?php elseif ($c['lien_externe']): ?>
                                                        <a href="<?= htmlspecialchars($c['lien_externe']) ?>" 
                                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                                            Lien
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">Aucun cours dans ce module</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination identique -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>" aria-label="Previous">
                            &laquo;
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?>" aria-label="Next">
                            &raquo;
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>