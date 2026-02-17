<?php
session_start();
require 'db.php';

// Vérification connexion et permissions (1=étudiant, 2=prof, 3=admin)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['type'], [1, 2, 3])) {
    $_SESSION['error'] = "Veuillez vous connecter";
    header('Location: index.php');
    exit;
}

// Traitement du formulaire de création de sujet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['creer_sujet'])) {
        $titre = htmlspecialchars(trim($_POST['titre']));
        $contenu = htmlspecialchars(trim($_POST['contenu']));
        $auteur_id = $_SESSION['user']['id'];

        if (!empty($titre) && !empty($contenu)) {
            $stmt = $pdo->prepare("INSERT INTO forum_sujets (titre, contenu, auteur_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$titre, $contenu, $auteur_id])) {
                $_SESSION['success'] = "Votre sujet a été créé avec succès!";
                header("Location: forum.php");
                exit;
            } else {
                $erreur = "Une erreur est survenue lors de la création du sujet.";
            }
        } else {
            $erreur = "Le titre et le contenu ne peuvent pas être vides.";
        }
    }
}

// Récupération des sujets avec pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Requête pour les sujets
$sujets = $pdo->query("
    SELECT fs.*, u.nom as auteur_nom, 
    (SELECT COUNT(*) FROM forum_reponses fr WHERE fr.sujet_id = fs.id) as nb_reponses
    FROM forum_sujets fs
    JOIN users u ON fs.auteur_id = u.id
    ORDER BY fs.date_creation DESC
    LIMIT $limit OFFSET $offset
")->fetchAll(PDO::FETCH_ASSOC);

// Comptage total des sujets
$totalSujets = $pdo->query("SELECT COUNT(*) FROM forum_sujets")->fetchColumn();
$totalPages = ceil($totalSujets / $limit);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum de discussion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #2c3e50;
            padding-left: 250px;
            transition: all 0.3s;
        }

        .container {
            padding: 20px;
            margin-top: 20px;
            max-width: 1200px;
        }

        .sujet-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .sujet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: #2c3e50;
            color: white;
            border-radius: 8px 8px 0 0 !important;
        }

        .truncate {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .badge-reponses {
            background-color: #3498db;
        }

        .page-title {
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 600;
        }

        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php 
    if (isset($_SESSION['user']['type'])) {
        switch ($_SESSION['user']['type']) {
            case 2: // Professeur
                include 'navbar.profs.php';
                break;
            case 3: // Admin
                include 'navbar.admin.php';
                break;
            case 1: // Étudiant
            default:
                include 'navbar.php';
                break;
        }
    }
    ?>

    <div class="container py-4">
        <!-- Messages flash -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($erreur)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $erreur ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="page-title text-center">
                    <i class="fas fa-comments me-2"></i>Forum de discussion
                </h1>

                <!-- Formulaire de création de sujet -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Créer un nouveau sujet</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="titre" class="form-label">Titre du sujet*</label>
                                <input type="text" class="form-control" id="titre" name="titre" required maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label for="contenu" class="form-label">Contenu*</label>
                                <textarea class="form-control" id="contenu" name="contenu" rows="5" required></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="creer_sujet" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Publier le sujet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des sujets -->
                <h2 class="mb-3">
                    <i class="fas fa-list me-2"></i>Sujets récents
                    <span class="badge bg-secondary ms-2"><?= $totalSujets ?></span>
                </h2>

                <?php if (!empty($sujets)): ?>
                    <div class="row row-cols-1 g-4">
                        <?php foreach ($sujets as $sujet): ?>
                            <div class="col">
                                <div class="card sujet-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h3 class="card-title h5 mb-3">
                                                <a href="sujet.php?id=<?= $sujet['id'] ?>" class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($sujet['titre']) ?>
                                                </a>
                                            </h3>
                                            <span class="badge badge-reponses text-white">
                                                <i class="fas fa-comment me-1"></i><?= $sujet['nb_reponses'] ?>
                                            </span>
                                        </div>
                                        <div class="card-text text-muted mb-3 truncate">
                                            <?= htmlspecialchars($sujet['contenu']) ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($sujet['auteur_nom']) ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?= date('d/m/Y à H:i', strtotime($sujet['date_creation'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <a href="sujet.php?id=<?= $sujet['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="fas fa-eye me-1"></i>Voir le sujet
                                        </a>
                                        <a href="sujet.php?id=<?= $sujet['id'] ?>#repondre" class="btn btn-sm btn-primary">
                                            <i class="fas fa-reply me-1"></i>Répondre
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Pagination des sujets" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="forum.php?page=<?= $page-1 ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="forum.php?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="forum.php?page=<?= $page+1 ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>Aucun sujet disponible. Soyez le premier à en créer un !
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus automatique sur le premier champ
            if (document.getElementById('titre')) {
                document.getElementById('titre').focus();
            }
            
            // Animation des cartes
            const cards = document.querySelectorAll('.sujet-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transitionDelay = `${index * 0.1}s`;
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });
        });
    </script>
</body>
</html>