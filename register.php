<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation robuste
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $choix = (int)$_POST['choix'];

        // Vérifications
        if (empty($nom) || empty($prenom) || !$email || empty($password)) {
            throw new Exception("Tous les champs sont obligatoires.");
        }

        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Les mots de passe ne correspondent pas.");
        }

        if (!in_array($choix, [1, 2, 3])) {
            throw new Exception("Type d'utilisateur invalide.");
        }

        // Vérification email existant
        $check = $pdo->prepare("SELECT id FROM users WHERE mail = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            throw new Exception("Cet email est déjà utilisé.");
        }

        // Hash sécurisé
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // REQUÊTE CORRIGÉE avec bindParam pour plus de sécurité
        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, mail, mdp, type) 
                            VALUES (:nom, :prenom, :mail, :mdp, :type)");
        
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':mail', $email);
        $stmt->bindParam(':mdp', $hashed_password);
        $stmt->bindParam(':type', $choix, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            throw new Exception("Erreur SQL: " . $error[2]);
        }

        $_SESSION['success'] = "Inscription réussie!";
        header("Location: index.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscription</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <img src="logo-ecole-tech.png" alt="Logo" class="logo-img">
      <h2>Créer un compte</h2>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <form action="" method="post">
        <input type="text" name="nom" placeholder="Nom d'utilisateur" required>
        <input type="text" name="prenom" placeholder="Prenom d'utilisateur" required>
        <input type="email" name="email" placeholder="Adresse e-mail" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
       
        <div class="radio-group">
          <label>
            <input type="radio" name="choix" value="1" required> Étudiant
          </label>
          <label>
            <input type="radio" name="choix" value="2"> Prof
          </label>
          <label>
            <input type="radio" name="choix" value="3"> Administration
          </label>
        </div>

        <button type="submit">S'inscrire</button>
      </form>
      <p class="signup-text">Déjà inscrit ? <a href="index.php">Se connecter</a></p>
    </div>
  </div>
</body>
</html>