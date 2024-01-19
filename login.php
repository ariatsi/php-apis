<?php
header("Content-Type: application/json");

function generateToken($length = 32) {
    return bin2hex(openssl_random_pseudo_bytes($length));
}

// Connexion à la base de données avec PDO
$host = "localhost";
$dbname = "apis";
$username = "root";
$password = "";
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Récupération des valeurs envoyées par l'application externe
$email = $_POST["email"];
$password = $_POST["password"];

// Requête pour vérifier si les valeurs correspondent à celles de la base de données
$bdd = $pdo->prepare("SELECT * FROM clients WHERE Email=:email AND Password=:password");
$bdd->execute(['email' => $email, 'password' => $password]);
$count = $bdd->rowCount();

// Affichage du message de succès ou d'erreur sous forme de tableau JSON
if ($count > 0) {
    $token = generateToken(); // Génère un token aléatoire
    $json = array("status" => 200, "message" => "Success", "token" => $token);
} else {
    $json = array("status" => 400, "message" => "Error");
}

echo json_encode($json);

// Fermeture de la connexion à la base de données
$pdo = null;
?>