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
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Important pour que PDO lance des exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);

    // Récupération des valeurs envoyées par l'application externe
    $email = $_POST["email"];
    $password = $_POST["password"]; // Ceci devrait être hashé et vérifié correctement

    // Requête pour récupérer l'utilisateur
    $bdd = $pdo->prepare("SELECT * FROM clients WHERE Email = :email");
    $bdd->execute(['email' => $email]);
    $user = $bdd->fetch();

    // Vérification du mot de passe et mise à jour du token
    if ($user && $user['Password'] === $password) {
        // Génération du token
        $token = generateToken();

        // Mise à jour du token dans la base de données
        $updateToken = $pdo->prepare("UPDATE clients SET Token = :token, TimeStamp = CURRENT_TIMESTAMP WHERE id = :id");
        $updateToken->execute(['token' => $token, 'id' => $user['id']]);

        // Suppression du mot de passe des informations retournées
        unset($user['Password'], $user['Token'], $user['TimeStamp']);

        // Préparation de la réponse JSON
        $json = array("status" => 200, "message" => "Success", "token" => $token, "user" => $user);
    } else {
        // Informations de connexion incorrectes
        $json = array("status" => 400, "message" => "Invalid email or password");
    }
} catch (PDOException $e) {
    // Formatage du message d'erreur avec le code d'erreur
    $errorMsg = "Database error " . $e->getCode() . ": " . $e->getMessage();
    $json = array("status" => 500, "message" => $errorMsg);
}

echo json_encode($json);

// Fermeture de la connexion à la base de données
$pdo = null;
