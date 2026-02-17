<?php
session_start();
/**
 * Navbar verticale pour Prof/Admin – École de Développement Technologique
 * À inclure avec : <?php include 'navbar_admin_prof.php'; ?>
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>École de Développement Technologique</title>
    <style>
        .navbar-vertical {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .navbar-logo {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }

        .navbar-logo img {
            max-width: 80%;
            height: auto;
            margin-bottom: 15px;
        }

        .navbar-logo h1 {
            color: white;
            font-size: 1.2rem;
            margin: 0;
            line-height: 1.3;
        }

        .nav-links {
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-links a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .nav-links a:hover {
            background-color: #34495e;
            padding-left: 30px;
        }

        .nav-links a.active {
            background-color: #3498db;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar-vertical">
        <div class="navbar-logo">
            <img src="logo-ecole-tech.png" alt="Logo École">
            <h1>ÉCOLE DE DÉVELOPPEMENT TECHNOLOGIQUE</h1>
        </div>

        <div class="nav-links">
            <a href="home.profs.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.profs.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Accueil
            </a>
            <a href="create_qcm.php" class="<?= basename($_SERVER['PHP_SELF']) === 'create_qcm.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> Devoirs & Examens
            </a>
            <a href="gestion.cours.pr.php" class="<?= basename($_SERVER['PHP_SELF']) === 'cours.php' ? 'active' : '' ?>">
                <i class="fas fa-book"></i> Cours
            </a>
            <a href="message.php" class="<?= basename($_SERVER['PHP_SELF']) === 'gestion.cours.pr.php' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="forum.php" class="<?= basename($_SERVER['PHP_SELF']) === 'forum.php' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> Forum
            </a>
            <a href="absences.prof.php" class="<?= basename($_SERVER['PHP_SELF']) === 'absences.prof.php' ? 'active' : '' ?>">
                <i class="fas fa-key"></i> Authentification SSO
            </a>
            <a href="plan.prof.etud.php" class="<?= basename($_SERVER['PHP_SELF']) === 'plan.prof.etud.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar"></i> Planning
            </a>
             <a href="prof_devoirs.php" class="<?= basename($_SERVER['PHP_SELF']) === 'prof_devoirs.php' ? 'active' : '' ?>">
                <i class="fas fa-file-upload"></i> Créer Devoirs Maisons
            <!-- Ajoutez ces liens -->
            <a href="my_tests.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_tests.php' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> Mes Tests
            </a>
            <a href="view_results.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view_results.php' ? 'active' : '' ?>">
                 <i class="fas fa-chart-bar"></i> Résultats
            </a>
            </a>
            <a href="logout.php" class="<?= basename($_SERVER['PHP_SELF']) === 'logout.php' ? 'active' : '' ?>">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </nav>

    <div class="main-content">
        <!-- Contenu principal ici -->
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</body>
</html>
