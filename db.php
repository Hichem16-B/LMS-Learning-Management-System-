<?php
//Différente méthode de connexion à la base de données en fonction de l'environnement si mac ou windows (mac rajoutée les ports 3306/8889)
$dsn = "mysql:host=localhost;dbname=lms;charset=utf8";
$user = "root";
$pass = "root";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    // echo "Connexion réussie à la base de données .";
} catch (PDOException $e) {
    die("Échec de la connexion : " . $e->getMessage());
}