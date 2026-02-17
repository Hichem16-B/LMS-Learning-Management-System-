<?php
session_start();
require 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs !";
        header("Location: index.php");
        exit();
    }
    $sql = "SELECT * FROM users WHERE mail = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mdp'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'type' => $user['type'],
            'email' => $user['mail'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom']
        ];
         $_SESSION['user_id'] = $user['id']; 
        // Debug
        error_log("Connexion réussie: ".$user['mail']);

        switch ($user['type']) {
            case 1:
                header("Location: home.etudiant.php");
                break;
            case 2:
                header("Location: home.profs.php");
                break;
            case 3:
                header("Location: home.admin.php");
                break;
            default:
                $_SESSION['error'] = "Type d'utilisateur inconnu";
                header("Location: index.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Email ou mot de passe incorrect !";
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connexion - École de Développement Technologique</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <?php if (!empty($_SESSION['error'])): ?>
    <div class="error-message">
        <?= htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="success-message">
        <?= htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
  <div class="login-container">
    <div class="login-box">
      <div class="logo">
        <img src="logo-ecole-tech.png" alt="Logo École" class="logo-img" />
      </div>
      <h2>Connexion</h2>
      <form action="" method="post">
        <input type="email" name="email" placeholder="Adresse e-mail" required />
        <input type="password" name="password" placeholder="Mot de passe" required />
        <button type="submit">Se connecter</button>
      </form>
      <p class="signup-text">Vous n’avez pas de compte ? <a href="register.php">Créer un compte</a></p>
    </div>
  </div>
</body>
</html>
