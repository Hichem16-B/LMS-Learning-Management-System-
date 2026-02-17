<?php
session_start();
require 'db.php'; // Connexion PDO à la base

// Création d'un module
if (isset($_POST['create_module'])) {
    $nom_module = trim($_POST['nom_module']);
    $id_prof = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    if (!empty($nom_module) && $id_prof > 0) {
        $stmt = $pdo->prepare("INSERT INTO modules (nom, id_prof) VALUES (:nom, :id_prof)");
        $stmt->execute(['nom' => $nom_module, 'id_prof' => $id_prof]);
        $_SESSION['success'] = "Module créé avec succès.";
    } else {
        $_SESSION['error'] = "Le nom du module et le professeur sont obligatoires.";
    }
}

// Suppression d'un module
if (isset($_POST['delete_module'])) {
    $module_id = intval($_POST['module_id']);

    // Supprimer les cours associés au module
    $stmt = $pdo->prepare("DELETE FROM cours WHERE module_id = :module_id");
    $stmt->execute(['module_id' => $module_id]);

    // Supprimer le module
    $stmt = $pdo->prepare("DELETE FROM modules WHERE id = :module_id");
    $stmt->execute(['module_id' => $module_id]);

    $_SESSION['success'] = "Module supprimé avec succès.";
}

// Suppression d'un cours
if (isset($_POST['delete_course'])) {
    $cours_id = intval($_POST['cours_id']);

    // Supprimer le cours
    $stmt = $pdo->prepare("DELETE FROM cours WHERE id = :cours_id");
    $stmt->execute(['cours_id' => $cours_id]);

    $_SESSION['success'] = "Cours supprimé avec succès.";
}

// Traitement de l'ajout d'un cours
if (isset($_POST['add_course'])) {
    $module_id = intval($_POST['module_id']);
    $nom = trim($_POST['titre']);
    $type = $_POST['type'];
    $fichier_path = null;
    $lien_externe = null;

    if ($type === 'file' && isset($_FILES['contenu']) && $_FILES['contenu']['error'] === 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir);
        $fileName = basename($_FILES['contenu']['name']);
        $targetPath = $uploadDir . time() . '_' . $fileName;
        move_uploaded_file($_FILES['contenu']['tmp_name'], $targetPath);
        $fichier_path = $targetPath;
    } elseif ($type === 'link') {
        $lien_externe = trim($_POST['contenu_link']);
    }

    if ($module_id && $nom && ($fichier_path || $lien_externe)) {
        $stmt = $pdo->prepare("INSERT INTO cours (module_id, nom, fichier_path, lien_externe) VALUES (:module_id, :nom, :fichier_path, :lien_externe)");
        $stmt->execute([
            'module_id' => $module_id,
            'nom' => $nom,
            'fichier_path' => $fichier_path,
            'lien_externe' => $lien_externe
        ]);
        $_SESSION['success'] = "Cours ajouté avec succès.";
    } else {
        $_SESSION['error'] = "Tous les champs obligatoires ne sont pas remplis.";
    }
}

// Récupération des modules et cours
$modules = $pdo->query("SELECT * FROM modules")->fetchAll(PDO::FETCH_ASSOC);
$cours_by_module = [];
foreach ($modules as $module) {
    $stmt = $pdo->prepare("SELECT * FROM cours WHERE module_id = :id");
    $stmt->execute(['id' => $module['id']]);
    $cours_by_module[$module['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Cours</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 30px;
      background-color: #f4f4f4;
      margin-left: 250px;
    }
    h1 {
      text-align: center;
      color: #333;
    }
    .section {
      background: #fff;
      padding: 20px;
      margin-bottom: 40px;
      border-radius: 8px;
      box-shadow: 0 0 10px #ccc;
    }
    form {
      margin-bottom: 20px;
    }
    input, select, button {
      padding: 8px;
      margin: 6px 0;
      width: 100%;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      border: 1px solid #bbb;
      padding: 10px;
      text-align: left;
    }
    th {
      background-color: #ddd;
    }
    .module-title {
      background-color: #6c7ae0;
      color: white;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 10px;
    }
    .btn-danger {
      background-color: #e74c3c;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      cursor: pointer;
    }
    .btn-danger:hover {
      background-color: #c0392b;
    }
  </style>
</head>
<body>

<?php include 'navbar.profs.php'; ?>
  <h1>Gestion des Cours</h1>

  <div class="section">
    <h2>Créer un Module</h2>
    <form method="POST">
      <input type="text" name="nom_module" placeholder="Nom du module" required>
      <button type="submit" name="create_module">Créer le module</button>
    </form>
  </div>

  <div class="section">
    <h2>Ajouter un Cours à un Module</h2>
    <form method="POST" enctype="multipart/form-data">
      <select name="module_id" required>
        <option value="">-- Choisir un module --</option>
        <?php foreach ($modules as $mod): ?>
          <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['nom']) ?></option>
        <?php endforeach; ?>
      </select>

      <input type="text" name="titre" placeholder="Nom du cours" required>

      <select name="type" id="typeSelect" required onchange="toggleContentInput()">
        <option value="file">Fichier</option>
        <option value="link">Lien</option>
      </select>

      <input type="file" name="contenu" id="fileInput">
      <input type="text" name="contenu_link" id="linkInput" style="display:none;" placeholder="Coller un lien (ex: YouTube)">

      <button type="submit" name="add_course">Ajouter le cours</button>
    </form>
  </div>

  <div class="section">
    <h2>Liste des Modules et Cours</h2>
    <?php foreach ($modules as $module): ?>
      <div class="module-title">
        <?= htmlspecialchars($module['nom']) ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
          <button type="submit" name="delete_module" class="btn-danger">Supprimer le module</button>
        </form>
      </div>
      <?php if (!empty($cours_by_module[$module['id']])): ?>
        <table>
          <thead>
            <tr>
              <th>Nom du cours</th>
              <th>Type</th>
              <th>Contenu</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cours_by_module[$module['id']] as $cours): ?>
              <tr>
                <td><?= htmlspecialchars($cours['nom']) ?></td>
                <td>
                  <?= !empty($cours['fichier_path']) ? 'Fichier' : 'Lien' ?>
                </td>
                <td>
                  <?php if (!empty($cours['fichier_path'])): ?>
                    <a href="<?= htmlspecialchars($cours['fichier_path']) ?>" target="_blank">Voir le fichier</a>
                  <?php elseif (!empty($cours['lien_externe'])): ?>
                    <a href="<?= htmlspecialchars($cours['lien_externe']) ?>" target="_blank">Ouvrir le lien</a>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="cours_id" value="<?= $cours['id'] ?>">
                    <button type="submit" name="delete_course" class="btn-danger">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>Aucun cours pour ce module.</p>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <script>
    function toggleContentInput() {
      const type = document.getElementById("typeSelect").value;
      document.getElementById("fileInput").style.display = type === "file" ? "block" : "none";
      document.getElementById("linkInput").style.display = type === "link" ? "block" : "none";
    }
  </script>
</body>
</html>