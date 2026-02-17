<?php
session_start();
require 'db.php';

// Vérification connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validation ID sujet
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: forum.php");
    exit;
}
$sujet_id = $_GET['id'];

// Traitement de la réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['poster_reponse'])) {
    $contenu = htmlspecialchars(trim($_POST['contenu']));
    $auteur_id = $_SESSION['user_id'];

    if (!empty($contenu)) {
        try {
            $pdo->beginTransaction();
            
            // Insertion de la réponse
            $stmt = $pdo->prepare("INSERT INTO forum_reponses (sujet_id, auteur_id, contenu) VALUES (?, ?, ?)");
            $stmt->execute([$sujet_id, $auteur_id, $contenu]);
            
            // Mise à jour de la date de modification du sujet
            $pdo->prepare("UPDATE forum_sujets SET date_creation = NOW() WHERE id = ?")->execute([$sujet_id]);
            
            $pdo->commit();
            
            // Redirection vers l'ancre #reponses après envoi
            header("Location: sujet.php?id=$sujet_id#reponses");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $erreur = "Une erreur est survenue lors de l'envoi de votre réponse.";
        }
    } else {
        $erreur = "Votre réponse ne peut pas être vide.";
    }
}

// Récupération du sujet
$sujet = $pdo->prepare("
    SELECT fs.*, u.nom as auteur_nom 
    FROM forum_sujets fs
    JOIN users u ON fs.auteur_id = u.id
    WHERE fs.id = ?
");
$sujet->execute([$sujet_id]);
$sujet = $sujet->fetch(PDO::FETCH_ASSOC);

if (!$sujet) {
    header("Location: forum.php");
    exit;
}

// Récupération des réponses avec pagination simple
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$reponses = $pdo->prepare("
    SELECT fr.*, u.nom as auteur_nom 
    FROM forum_reponses fr
    JOIN users u ON fr.auteur_id = u.id
    WHERE fr.sujet_id = :sujet_id
    ORDER BY fr.date_creation ASC
    LIMIT $limit OFFSET $offset
");
$reponses->execute(['sujet_id' => $sujet_id]);
$reponses = $reponses->fetchAll(PDO::FETCH_ASSOC);

// Comptage total des réponses pour la pagination
try {
    $totalReponses = $pdo->prepare("SELECT COUNT(*) FROM forum_reponses WHERE sujet_id = ?");
    $totalReponses->execute([$sujet_id]);
    $totalReponses = $totalReponses->fetchColumn();
} catch (PDOException $e) {
    die("Erreur lors du comptage des réponses : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sujet['titre']) ?> - Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .message-content {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .reply-card {
            border-left: 3px solid #0d6efd;
            transition: transform 0.2s;
        }
        .reply-card:hover {
            transform: translateX(5px);
        }
        #form-reponse {
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.admin.php'; ?>

    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="forum.php"><i class="fas fa-home"></i> Forum</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($sujet['titre']) ?></li>
            </ol>
        </nav>

        <!-- Sujet principal -->
        <div class="card mb-4 shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h2 class="mb-0"><?= htmlspecialchars($sujet['titre']) ?></h2>
                <div>
                    <small class="text-white-50">Posté le <?= date('d/m/Y à H:i', strtotime($sujet['date_creation'])) ?></small>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                        <i class="fas fa-user-circle fa-3x text-muted"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mt-0"><?= htmlspecialchars($sujet['auteur_nom']) ?></h5>
                        <div class="message-content mt-3"><?= nl2br(htmlspecialchars($sujet['contenu'])) ?></div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <a href="#form-reponse" class="btn btn-primary btn-sm">
                    <i class="fas fa-reply"></i> Répondre à ce sujet
                </a>
            </div>
        </div>

        <!-- Section Réponses -->
        <section id="reponses" class="mb-5">
            <h3 class="mb-4 border-bottom pb-2">
                <i class="fas fa-comments me-2"></i>Réponses (<?= $totalReponses ?>)
            </h3>

            <?php if (!empty($reponses)): ?>
                <?php foreach ($reponses as $reponse): ?>
                    <div class="card mb-3 reply-card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-user-circle fa-2x text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h5 class="mb-0"><?= htmlspecialchars($reponse['auteur_nom']) ?></h5>
                                        <small class="text-muted">
                                            <?= date('d/m/Y à H:i', strtotime($reponse['date_creation'])) ?>
                                        </small>
                                    </div>
                                    <div class="message-content"><?= nl2br(htmlspecialchars($reponse['contenu'])) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalReponses > $limit): ?>
                    <nav aria-label="Pagination des réponses">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="sujet.php?id=<?= $sujet_id ?>&page=<?= $page-1 ?>#reponses">
                                        Précédent
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $totalPages = ceil($totalReponses / $limit);
                            for ($i = 1; $i <= $totalPages; $i++):
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="sujet.php?id=<?= $sujet_id ?>&page=<?= $i ?>#reponses">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="sujet.php?id=<?= $sujet_id ?>&page=<?= $page+1 ?>#reponses">
                                        Suivant
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Aucune réponse pour le moment. Soyez le premier à répondre !
                </div>
            <?php endif; ?>
        </section>

        <!-- Formulaire de réponse -->
        <section id="form-reponse" class="card shadow-lg">
            <div class="card-header bg-light">
                <h4 class="mb-0"><i class="fas fa-reply me-2"></i>Poster une réponse</h4>
            </div>
            <div class="card-body">
                <?php if (isset($erreur)): ?>
                    <div class="alert alert-danger"><?= $erreur ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="sujet_id" value="<?= $sujet_id ?>">
                    <div class="mb-3">
                        <label for="contenu" class="form-label">Votre message*</label>
                        <textarea class="form-control" id="contenu" name="contenu" rows="5" required
                                  placeholder="Écrivez votre réponse ici..."></textarea>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="reset" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-eraser"></i> Effacer
                        </button>
                        <button type="submit" name="poster_reponse" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer la réponse
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>







<?php
session_start();
require 'db.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Traitement du formulaire de création de sujet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['creer_sujet'])) {
        $titre = htmlspecialchars(trim($_POST['titre']));
        $contenu = htmlspecialchars(trim($_POST['contenu']));
        $auteur_id = $_SESSION['user_id'];

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
        .sujet-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .sujet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .truncate {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .badge-reponses {
            background-color: #0d6efd;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <?php include 'navbar.admin.php'; ?>

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
                <h1 class="text-center mb-4">
                    <i class="fas fa-comments me-2"></i>Forum de discussion
                </h1>

                <!-- Formulaire de création de sujet -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
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
        // Focus automatique sur le premier champ du formulaire
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('titre')) {
                document.getElementById('titre').focus();
            }
            
            // Animation pour les cartes
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





