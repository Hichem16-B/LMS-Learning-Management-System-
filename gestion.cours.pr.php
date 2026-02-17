<?php
session_start();
require 'db.php';

// Vérification de session cohérente avec index.php
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 2) {
    $_SESSION['error'] = "Accès non autorisé";
    header('Location: index.php');
    exit();
}

$prof_id = (int)$_SESSION['user']['id'];

// Messages de session
$success_msg = $_SESSION['success'] ?? null;
$error_msg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Création d'un module
if (isset($_POST['create_module'])) {
    $nom_module = trim($_POST['nom_module']);

    if (!empty($nom_module)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO modules (nom, id_prof) VALUES (:nom, :id_prof)");
            $stmt->execute(['nom' => $nom_module, 'id_prof' => $prof_id]);
            $_SESSION['success'] = "Module créé avec succès.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la création du module: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Le nom du module est obligatoire.";
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Suppression d'un module
if (isset($_POST['delete_module'])) {
    $module_id = intval($_POST['module_id']);

    try {
        // Vérifier que le module appartient bien au professeur
        $check = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND id_prof = ?");
        $check->execute([$module_id, $prof_id]);
        
        if ($check->rowCount() > 0) {
            $pdo->beginTransaction();
            
            // Supprimer les cours associés
            $stmt = $pdo->prepare("DELETE FROM cours WHERE module_id = ?");
            $stmt->execute([$module_id]);

            $stmt = $pdo->prepare("DELETE FROM tests WHERE id = :module_id");
            $stmt->execute(['module_id' => $module_id]);
            
            // Supprimer le module
            $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
            $stmt->execute([$module_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Module supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Vous n'avez pas les droits sur ce module.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Suppression d'un cours
if (isset($_POST['delete_course'])) {
    $cours_id = intval($_POST['cours_id']);

    try {
        // Vérifier que le cours appartient à un module du professeur
        $check = $pdo->prepare("
            SELECT c.id 
            FROM cours c
            JOIN modules m ON c.module_id = m.id
            WHERE c.id = ? AND m.id_prof = ?
        ");
        $check->execute([$cours_id, $prof_id]);
        
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("DELETE FROM cours WHERE id = ?");
            $stmt->execute([$cours_id]);
            $_SESSION['success'] = "Cours supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Vous n'avez pas les droits sur ce cours.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Ajout d'un cours
if (isset($_POST['add_course'])) {
    $module_id = intval($_POST['module_id']);
    $nom = trim($_POST['titre']);
    $type = $_POST['type'];
    $fichier_path = null;
    $lien_externe = null;

    try {
        // Vérifier que le module appartient au professeur
        $check = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND id_prof = ?");
        $check->execute([$module_id, $prof_id]);
        
        if ($check->rowCount() > 0) {
            if ($type === 'file' && isset($_FILES['contenu']) && $_FILES['contenu']['error'] === 0) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir);
                $fileName = basename($_FILES['contenu']['name']);
                $targetPath = $uploadDir . time() . '_' . $fileName;
                if (move_uploaded_file($_FILES['contenu']['tmp_name'], $targetPath)) {
                    $fichier_path = $targetPath;
                } else {
                    throw new Exception("Erreur lors de l'upload du fichier");
                }
            } elseif ($type === 'link') {
                $lien_externe = filter_var(trim($_POST['contenu_link']), FILTER_VALIDATE_URL);
                if (!$lien_externe) {
                    throw new Exception("Lien invalide");
                }
            }

            if ($nom && ($fichier_path || $lien_externe)) {
                $stmt = $pdo->prepare("
                    INSERT INTO cours (module_id, prof_id, nom, fichier_path, lien_externe) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$module_id, $prof_id, $nom, $fichier_path, $lien_externe]);
                $_SESSION['success'] = "Cours ajouté avec succès.";
            } else {
                $_SESSION['error'] = "Tous les champs obligatoires ne sont pas remplis.";
            }
        } else {
            $_SESSION['error'] = "Vous n'avez pas les droits sur ce module.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Récupération des modules du professeur
try {
    $modules = $pdo->prepare("SELECT * FROM modules WHERE id_prof = ? ORDER BY nom");
    $modules->execute([$prof_id]);
    $modules = $modules->fetchAll(PDO::FETCH_ASSOC);

    $cours_by_module = [];
    foreach ($modules as $module) {
        $stmt = $pdo->prepare("SELECT * FROM cours WHERE module_id = ? ORDER BY nom");
        $stmt->execute([$module['id']]);
        $cours_by_module[$module['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Cours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-left: 250px;
            background-color: #f8f9fa;
            transition: padding 0.3s;
        }
        .container {
            margin-top: 2rem;
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #6c7ae0;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-danger {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.profs.php'; ?>

    <div class="container">
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h1 class="mb-4">Gestion des Cours</h1>

        <div class="card">
            <div class="card-header">
                <h2>Créer un Module</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="nom_module" class="form-label">Nom du module</label>
                        <input type="text" class="form-control" id="nom_module" name="nom_module" required>
                    </div>
                    <button type="submit" name="create_module" class="btn btn-primary">Créer le module</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Ajouter un Cours</h2>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="module_id" class="form-label">Module</label>
                        <select class="form-select" id="module_id" name="module_id" required>
                            <option value="">-- Choisir un module --</option>
                            <?php foreach ($modules as $mod): ?>
                                <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="titre" class="form-label">Nom du cours</label>
                        <input type="text" class="form-control" id="titre" name="titre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="typeSelect" class="form-label">Type de contenu</label>
                        <select class="form-select" id="typeSelect" name="type" required onchange="toggleContentInput()">
                            <option value="file">Fichier</option>
                            <option value="link">Lien</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="fileInput">
                        <label for="contenu" class="form-label">Fichier</label>
                        <input type="file" class="form-control" id="contenu" name="contenu">
                    </div>
                    
                    <div class="mb-3" id="linkInput" style="display:none;">
                        <label for="contenu_link" class="form-label">Lien</label>
                        <input type="url" class="form-control" id="contenu_link" name="contenu_link" placeholder="https://...">
                    </div>
                    
                    <button type="submit" name="add_course" class="btn btn-primary">Ajouter le cours</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Liste des Modules et Cours</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($modules)): ?>
                    <?php foreach ($modules as $module): ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="mb-0"><?= htmlspecialchars($module['nom']) ?></h3>
                                <form method="POST" class="mb-0">
                                    <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
                                    <button type="submit" name="delete_module" class="btn btn-danger btn-sm">
                                        Supprimer le module
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($cours_by_module[$module['id']])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Type</th>
                                                    <th>Contenu</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cours_by_module[$module['id']] as $cours): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($cours['nom']) ?></td>
                                                        <td><?= !empty($cours['fichier_path']) ? 'Fichier' : 'Lien' ?></td>
                                                        <td>
                                                            <?php if (!empty($cours['fichier_path'])): ?>
                                                                <a href="<?= htmlspecialchars($cours['fichier_path']) ?>" target="_blank">
                                                                    Voir le fichier
                                                                </a>
                                                            <?php elseif (!empty($cours['lien_externe'])): ?>
                                                                <a href="<?= htmlspecialchars($cours['lien_externe']) ?>" target="_blank">
                                                                    Ouvrir le lien
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="cours_id" value="<?= $cours['id'] ?>">
                                                                <button type="submit" name="delete_course" class="btn btn-danger btn-sm">
                                                                    Supprimer
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Aucun cours pour ce module.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Aucun module créé.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleContentInput() {
            const type = document.getElementById("typeSelect").value;
            document.getElementById("fileInput").style.display = type === "file" ? "block" : "none";
            document.getElementById("linkInput").style.display = type === "link" ? "block" : "none";
            
            // Rendre obligatoire le champ visible
            if (type === "file") {
                document.getElementById("contenu").required = true;
                document.getElementById("contenu_link").required = false;
            } else {
                document.getElementById("contenu").required = false;
                document.getElementById("contenu_link").required = true;
            }
        }
        
        // Initialiser l'état au chargement
        document.addEventListener('DOMContentLoaded', toggleContentInput);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>