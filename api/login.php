<?php

// Gestion de la requête préflight pour les CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 86400");
    http_response_code(204); // No Content
    exit();
}

include_once __DIR__ . '/config/database.php';
class Login {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function authenticate($username, $password) {
        // Requête SQL pour récupérer l'utilisateur
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification des informations d'identification
        if ($user && $password === $user['password']) {
            // Réponse réussie avec l'identifiant de l'utilisateur et le rôle
            return json_encode([
                'success' => true,
                'userId' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]);
        } else {
            // Mauvaises informations d'identification
            http_response_code(401);
            return json_encode(['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect']);
        }
    }
}

// Gestion de la requête POST
$data = json_decode(file_get_contents("php://input"));
if (isset($data->username) && isset($data->password)) {
    $login = new Login();
    echo $login->authenticate($data->username, $data->password);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données de connexion manquantes']);
}
?>