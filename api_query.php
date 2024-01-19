<?php


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $url = 'http://localhost/apis/login.php'; // Remplacez avec l'URL de votre API

    $data = array(
        'email' => $_POST['email'],
        'password' => $_POST['password']
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        echo "Erreur lors de l'appel à l'API";
    } else {
        $responseData = json_decode($response, true);
        //$cles = array_keys($responseData); // si vous voulez savoir que les clés

        foreach ($responseData as $cle => $valeur) {
            echo $cle . ': ' . $valeur . '<br>';
        }

        //echo 'Status: ' . $responseData['status'] . ', Message: ' . $responseData['message'];
    }
} else {
    echo "Méthode de requête non autorisée";
}
