<?php
header("Content-Type: application/json");


function extractToken() {
    // Tentons d'obtenir le token d'authentification de différentes manières
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        // Supprimez "Bearer" si présent
        return preg_replace('/^Bearer\s/', '', $_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        // Dans certains cas, comme avec PHP tournant sous FastCGI, le préfixe 'REDIRECT_' est ajouté
        return preg_replace('/^Bearer\s/', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } else {
        // Tenter de récupérer l'en-tête Authorization pour les serveurs qui ne le mettent pas dans le $_SERVER global
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        if (isset($headers['Authorization'])) {
            return preg_replace('/^Bearer\s/', '', $headers['Authorization']);
        }
    }
    // Si le token n'est pas dans les en-têtes HTTP, on essaie de le récupérer de $_POST .
    return $_POST["token"] ?? null;
}

// Connexion à la base de données
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

    // Récupération de l'ID utilisateur et du token
    $userId = $_POST['id'] ?? null;
    // $token = $_POST["token"] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
/*
    // Tentons d'obtenir le token d'authentification de différentes manières
    $authHeader = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        // Dans certains cas, comme avec PHP tournant sous FastCGI, le préfixe 'REDIRECT_' est ajouté
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } else {
        // Tenter de récupérer l'en-tête Authorization pour les serveurs qui ne le mettent pas dans le $_SERVER global
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        }
    }

    // Si nous avons récupéré un en-tête d'autorisation, nous extrayons le token
    if ($authHeader !== null) {
        // Supprimez "Bearer" si présent
        $token = preg_replace('/^Bearer\s/', '', $authHeader);
    } else {
        // Si le token n'est pas dans les en-têtes HTTP, on essaie de le récupérer de $_POST .
        $token = $_POST["token"] ?? null;
    }
*/
    $token = extractToken();
    // Vérification de la présence de l'ID utilisateur et du token
    if (!$userId || !$token) {
        throw new Exception("User ID or token not provided", 400);
    }

    // Vérification de la validité du token
    $tokenQuery = $pdo->prepare("SELECT TimeStamp FROM clients WHERE id = :id AND Token = :token");
    $tokenQuery->execute(['id' => $userId, 'token' => $token]);
    $user = $tokenQuery->fetch();

    if ($user) {
        $timeElapsed = time() - strtotime($user['TimeStamp']);
        if ($timeElapsed > 60) {
            throw new Exception("Token expired", 401);
        }

        // Le token est valide, on récupère les fiches de l'utilisateur
        $sheetsQuery = $pdo->prepare("SELECT * FROM sheets WHERE id_user = :id_user");
        $sheetsQuery->execute(['id_user' => $userId]);
        $sheets = $sheetsQuery->fetchAll();

        // Mise à jour du timestamp pour maintenir la session ouverte
        $updateTimestamp = $pdo->prepare("UPDATE clients SET TimeStamp = CURRENT_TIMESTAMP WHERE id = :id");
        $updateTimestamp->execute(['id' => $userId]);

        // Préparation de la réponse JSON
        $json = array("status" => 200, "message" => "Success", "sheets" => $sheets);
    } else {
        throw new Exception("Invalid token", 401);
    }
} catch (Exception $e) {
    // Capture des exceptions et formatage du message d'erreur
    $json = array("status" => $e->getCode(), "message" => $e->getMessage());
}

echo json_encode($json);

// Fermeture de la connexion à la base de données
$pdo = null;
