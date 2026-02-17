<?php
session_start();
/**
 * Navbar verticale pour l'École de Développement Technologique
 * À inclure dans les pages avec: <?php include 'navbar.php'; ?>
 */
?>
<!-- Navbar Verticale -->
<nav class="navbar-vertical">
    <div class="navbar-logo">
        <img src="logo-ecole-tech.png" alt="Logo École">
        <h1>ÉCOLE DE DÉVELOPPEMENT TECHNOLOGIQUE</h1>
    </div>
    
    <div class="nav-links">
        <a href="home.etudiant.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.etudiant.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Modules et Cours
        </a>
        <a href="take_quiz.php" class="<?= basename($_SERVER['PHP_SELF']) === 'take_quiz.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> Devoirs & Examens
            </a>
        <a href="message.php" class="<?= basename($_SERVER['PHP_SELF']) === 'message.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Messages
        </a>
        <a href="forum.php" class="<?= basename($_SERVER['PHP_SELF']) === 'forum.php' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> Forum
            </a>
        <a href="plan.prof.etud.php" class="<?= basename($_SERVER['PHP_SELF']) === 'plan.prof.etud.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar"></i> Planning
        </a>
        <a href="absences.etudiant.php" class="<?= basename($_SERVER['PHP_SELF']) === 'absences.etudiant.php' ? 'active' : '' ?>">
            <i class="fas fa-key"></i> Authentification SSO
        </a>
         <a href="eleve_upload.php" class="<?= basename($_SERVER['PHP_SELF']) === 'prof_devoirs.php' ? 'active' : '' ?>">
                <i class="fas fa-file-upload"></i> Devoirs Maisons
            </a>
            <a href="logout.php" class="<?= basename($_SERVER['PHP_SELF']) === 'logout.php' ? 'active' : '' ?>">
                <i class="fas fa-file-upload"></i> Deconnexion
            </a>
       
    </div>
</nav>

<!-- Styles et scripts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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