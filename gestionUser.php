<?php
// admin_users.php
session_start();
require_once 'db.php';

// Vérification de l'authentification et des droits admin
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 3) {
    $_SESSION['error'] = "Veuillez vous connecter";
    header("Location: index.php");
    exit;
}

// Initialisation
$message = '';
$users = [];

// Récupération des utilisateurs
try {
    $stmt = $pdo->query("SELECT id, nom, prenom, mail, type FROM users ORDER BY nom, prenom");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Erreur de chargement des utilisateurs</div>';
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$user_id) {
        $message = '<div class="alert alert-danger">ID utilisateur invalide</div>';
    } elseif ($user_id == $_SESSION['user_id']) {
        $message = '<div class="alert alert-warning">Vous ne pouvez pas supprimer votre propre compte</div>';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Désactiver temporairement les contraintes (solution alternative)
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            
            // Liste des tables avec contraintes vers users
            $tables = [
                'absences' => 'etudiant_id',
                'devoirs_remises' => 'eleve_id',
                'forum_reponses' => 'auteur_id',
                'forum_sujets' => 'auteur_id',
                'messages_prives' => 'expediteur_id',
                'messages_prives' => 'destinataire_id',
                'reponses_etudiants' => 'resultat_id IN (SELECT id FROM resultats_qcm WHERE etudiant_id = ?)',
                'resultats_qcm' => 'etudiant_id'
            ];
            
            // Suppression des dépendances
            foreach ($tables as $table => $condition) {
                if (strpos($condition, ' IN (SELECT') !== false) {
                    $sql = "DELETE FROM $table WHERE $condition";
                    $pdo->prepare($sql)->execute([$user_id]);
                } else {
                    $sql = "DELETE FROM $table WHERE $condition = ?";
                    $pdo->prepare($sql)->execute([$user_id]);
                }
            }
            
            // Suppression de l'utilisateur
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Réactiver les contraintes
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            
            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                $message = '<div class="alert alert-success">Utilisateur supprimé avec succès</div>';
                // Rafraîchir la liste
                $stmt = $pdo->query("SELECT id, nom, prenom, mail, type FROM users ORDER BY nom, prenom");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $pdo->rollBack();
                $message = '<div class="alert alert-danger">Utilisateur non trouvé</div>';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = htmlspecialchars($e->getMessage());
            $message = "<div class='alert alert-danger'>Erreur: $error</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs | LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/lms.css">
    <style>
        .user-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .user-table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
        }
        .badge-type {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        .badge-student { background-color: #17a2b8; }
        .badge-teacher { background-color: #28a745; }
        .badge-admin { background-color: #dc3545; }
        .action-btn { transition: all 0.2s; }
        .action-btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
   
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-primary">
                <i class="fas fa-users-cog me-2"></i>Gestion des Utilisateurs
            </h2>
            <a href="home.admin.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
        
        <?php echo $message; ?>
        
        <div class="card user-card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des Utilisateurs</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="fw-bold">#<?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['nom']) ?></td>
                                    <td><?= htmlspecialchars($user['prenom']) ?></td>
                                    <td><a href="mailto:<?= htmlspecialchars($user['mail']) ?>"><?= htmlspecialchars($user['mail']) ?></a></td>
                                    <td>
                                        <?php
                                        $badge = match($user['type']) {
                                            1 => ['class' => 'badge-student', 'text' => 'Étudiant'],
                                            2 => ['class' => 'badge-teacher', 'text' => 'Professeur'],
                                            3 => ['class' => 'badge-admin', 'text' => 'Admin'],
                                            default => ['class' => 'bg-secondary', 'text' => 'Inconnu']
                                        };
                                        ?>
                                        <span class="badge rounded-pill <?= $badge['class'] ?>"><?= $badge['text'] ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="delete_user" 
                                                        class="btn btn-sm btn-danger action-btn"
                                                        onclick="return confirm('Cette action supprimera définitivement l\\'utilisateur et toutes ses données associées. Continuer ?')">
                                                    <i class="fas fa-trash-alt me-1"></i> Supprimer
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">(Votre compte)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script pour améliorer l'UX
        document.addEventListener('DOMContentLoaded', function() {
            // Animation sur les boutons
            const buttons = document.querySelectorAll('.action-btn');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', () => {
                    btn.style.boxShadow = '0 4px 8px rgba(0,0,0,0.15)';
                });
                btn.addEventListener('mouseleave', () => {
                    btn.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>