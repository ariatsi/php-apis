**Création et Stockage d'un token pour une API en PHP** 

La création et le stockage d'un token pour une API en PHP impliquent généralement plusieurs étapes. Les tokens sont utilisés pour l'authentification et la gestion des sessions, permettant à l'API de vérifier l'identité des utilisateurs et de maintenir la sécurité. Voici une approche générale : 

1. **Génération du Token** 

Un token est souvent une chaîne de caractères générée de manière aléatoire. Pour une sécurité accrue, vous pouvez utiliser des fonctions comme **openssl\_random\_pseudo\_bytes** et **bin2hex** : 

```php
function generateToken($length = 32) {
    return bin2hex(openssl_random_pseudo_bytes($length));
}
$token = generateToken(); // Génère un token aléatoire
```
2. **Stockage du Token** 

Le token peut être stocké de différentes manières, en fonction de vos besoins : 

- **Base de données :** Stocker le token associé à un utilisateur dans une base de données. 
- **Session PHP :** Stocker le token dans une session PHP si l'utilisateur est connecté temporairement. 
- **Fichier :** Moins sécurisé, mais possible de stocker dans un fichier sur le serveur. 
3. **Envoi du Token au Client** 

Le token est ensuite envoyé au client, généralement lors de la connexion ou de l'inscription : 

```php
echo json_encode(["token" => $token]);
```

4. **Utilisation du Token pour les Requêtes Suivantes** 

Le client doit envoyer ce token avec chaque requête nécessitant une authentification. Ce token est souvent envoyé dans les en-têtes HTTP. 

5. **Vérification du Token** 

À chaque requête, l'API doit vérifier la validité du token envoyé. Ceci peut être fait en comparant le token reçu avec celui stocké dans la base de données ou dans la session PHP. 

**Sécurité** 

- **Expiration :** Les tokens doivent avoir une date d'expiration pour réduire les risques en cas de compromission. 
- **HTTPS :** Utilisez toujours HTTPS pour protéger les données transmises, notamment les tokens. 

**Exemple de vérification :** 
```php
function verifyToken($receivedToken) {
    // Logique pour vérifier le token (comparaison avec la base de données ou session)
    // Retourner true si le token est valide, false sinon
}

if (!verifyToken($_SERVER['HTTP_AUTHORIZATION'])) {
    http_response_code(401); // Non autorisé
    exit;
}
```
Cette approche constitue un cadre de base pour gérer les tokens dans une API PHP. La mise en œuvre détaillée dépendra de la structure spécifique de votre application et de vos exigences en matière de sécurité. 

**Attention !**  

La fonction **generateToken** utilise la fonction **bin2hex** sur le résultat de **openssl\_random\_pseudo\_bytes**, qui génère des octets aléatoires. La fonction **bin2hex** convertit chaque octet en deux caractères hexadécimaux. Cela signifie que si vous générez un token de 32 octets aléatoires avec **openssl\_random\_pseudo\_bytes**, après la conversion avec **bin2hex**, vous obtiendrez une chaîne de 64 caractères. 

Par conséquent, le champ **token** dans votre base de données doit être capable de stocker 64 caractères. Si vous utilisez le type de données **VARCHAR** pour le champ **token** dans MySQL ou un système de gestion de base de données similaire, la déclaration devrait être : 

```sql
token VARCHAR(64)
```

Cela garantira que le champ peut stocker le token généré par la fonction PHP. **Envoyer le token via Postman**  

Pour envoyer le token d'authentification via Postman, vous devez ajouter un en-tête personnalisé dans la requête. Voici comment procéder : 

1. Ouvrez Postman et créez une nouvelle requête ou sélectionnez-en une existante. 
1. Allez dans l'onglet "Headers" de la requête. 
1. Dans la section des en-têtes (headers), vous verrez deux champs : un pour la clé (Key) et l'autre pour la valeur (Value). 
1. Dans le champ "Key", tapez **Authorization**. 
1. Dans le champ "Value", tapez "**Bearer** "** suivi de votre token (espace après "**Bearer**" est important). Par exemple : "**Bearer abc123456token**". 

Une fois que vous avez ajouté l'en-tête, vous pouvez envoyer votre requête, et le serveur recevra l'en-tête **HTTP\_AUTHORIZATION** avec la valeur du token que vous avez spécifiée. 

Si l'en-tête n'est pas envoyé correctement par le client (Postman dans notre cas) ou si la ***configuration du serveur ne transmet pas les en-têtes d'authentification*** au script PHP, assurez-vous que votre script PHP utilise la bonne variable globale pour accéder à l'en-tête d'autorisation. Vous pourriez essayer d'accéder à l'en-tête d'authentification de cette manière : 

```php
<?php
header("Content-Type: application/json");

function extractToken() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        // Supprimez "Bearer" si présent
        return preg_replace('/^Bearer\s/', '', $_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        // Dans certains cas, comme avec PHP tournant sous FastCGI, le préfixe 'REDIRECT_' est ajouté
        return preg_replace('/^Bearer\s/', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } else {
        // Tenter de récupérer l'en-tête Authorization 
        // pour les serveurs qui ne le mettent pas dans le $_SERVER global
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        if (isset($headers['Authorization'])) {
            return preg_replace('/^Bearer\s/', '', $headers['Authorization']);
        }
    }
    // Si le token n'est pas dans les en-têtes HTTP, on essaie de le récupérer de $_POST .
    return $_POST["token"] ?? null;
}

// Connexion à la base de données
// ... (reste de la configuration de la base de données)

try {
    $pdo = new PDO(//options...);

    // Extraction du token
    $token = extractToken();

    // Vérification de la présence du token
    if (!$token) {
        throw new Exception("User ID or token not provided", 400);
    }

    // ... (reste de la logique de vérification et de récupération des données)

} catch (Exception $e) {
    // ... (gestion des exceptions)
}

echo json_encode($json);

// ... (fermeture de la connexion à la base de données)

```

**Sur le serveur :** 

Certains serveurs, en particulier ceux exécutant PHP via FastCGI, peuvent ne pas passer l'en-tête **Authorization** au script PHP. Si c'est le cas, vous pourriez avoir besoin d'une configuration supplémentaire dans votre fichier **.htaccess** (pour Apache) ou dans votre configuration Nginx. 

Pour Apache, vous pouvez ajouter ce qui suit à votre **.htaccess** : 
```apacheconf
RewriteEngine On
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

Pour Nginx, vous pouvez ajouter cette ligne à la configuration de votre site : 
```nginx
fastcgi_pass_header Authorization;
```
N'oubliez pas de redémarrer le serveur web après avoir modifié ces configurations. 
