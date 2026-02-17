<?php
session_start();
require 'db.php';

// Vérifie d'abord la connexion
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Veuillez vous connecter";
    header('Location: index.php');
    exit;
}

// Ensuite vérifie le rôle
if ($_SESSION['user']['type'] !== 2) {
    $_SESSION['error'] = "Accès réservé aux enseignants";
    header('Location: index.php');
    exit;
}

// Configuration pagination
$itemsParPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Requête count
$totalModules = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
$totalPages = ceil($totalModules / $itemsParPage);
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Requête paginée
$offset = ($page - 1) * $itemsParPage;
$modules = $pdo->query("SELECT * FROM modules ORDER BY nom ASC LIMIT $offset, $itemsParPage")
              ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Professeur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

<?php include 'navbar.profs.php'; ?>

<div class="container">
    <h1 class="mb-4 text-center">Cours par Module</h1>
    
    <?php if (empty($modules)): ?>
        <div class="alert alert-info">Aucun module disponible.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($modules as $module): ?>
                <?php 
                $stmt = $pdo->prepare("SELECT * FROM cours WHERE module_id = ? ORDER BY nom ASC");
                $stmt->execute([$module['id']]);
                $cours = $stmt->fetchAll();
                ?>
                
                <div class="col">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?= htmlspecialchars($module['nom']) ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($cours)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($cours as $c): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><?= htmlspecialchars($c['nom']) ?></span>
                                                <div>
                                                    <?php if ($c['fichier_path']): ?>
                                                        <?php $ext = strtolower(pathinfo($c['fichier_path'], PATHINFO_EXTENSION)); ?>
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

        <!-- Pagination -->
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