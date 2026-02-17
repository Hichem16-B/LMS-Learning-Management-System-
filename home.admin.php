<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']['type']) || $_SESSION['user']['type'] != 3) {
    header('Location: index.php');
    exit;
}

// Pagination
$parPage = 8;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$total = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
$totalPages = ceil($total / $parPage);
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

$modules = $pdo->query("SELECT * FROM modules ORDER BY nom ASC LIMIT $offset, $parPage")
              ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-left: 250px; background-color: #f8f9fa; }
        .container { margin-top: 2rem; }
        .card { border-radius: 10px; overflow: hidden; }
        .card-header { background: #0d6efd; color: white; }
        .pagination .page-item.active .page-link { background-color: #0d6efd; }
        .action-btns .btn { margin: 0 3px; }
        @media (max-width: 992px) { body { padding-left: 0; } }
    </style>
</head>
<body>

<?php include 'navbar.admin.php'; ?>

<div class="container">
    <h1 class="mb-4 text-center">Gestion des Modules</h1>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php foreach ($modules as $module): ?>
            <?php 
            $coursCount = $pdo->prepare("SELECT COUNT(*) FROM cours WHERE module_id = ?");
            $coursCount->execute([$module['id']]);
            $nbCours = $coursCount->fetchColumn();
            ?>
            
            <div class="col">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><?= htmlspecialchars($module['nom']) ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <span class="badge bg-primary"><?= $nbCours ?> cours</span>
                        </p>
                    </div>
                    <div class="card-footer bg-white action-btns">
                      
                        <a href="supprimer_module.php?id=<?= $module['id'] ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Supprimer ce module?')">
                            Supprimer
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination améliorée -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <!-- Première page -->
                <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=1">&laquo;&laquo;</a>
                </li>
                
                <!-- Page précédente -->
                <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>">&laquo;</a>
                </li>
                
                <!-- Pages proches -->
                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <!-- Page suivante -->
                <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>">&raquo;</a>
                </li>
                
                <!-- Dernière page -->
                <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $totalPages ?>">&raquo;&raquo;</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>