<?php

// Gestion de la requête préflight pour les CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}

include_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'POST':
        // Création d'un nouveau véhicule
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->vehicule) && !empty($data->immatriculation)) {
            // Statut par défaut : Disponible
            $statut = isset($data->statut) ? $data->statut : 'Disponible';

            $query = "INSERT INTO vehicules (vehicule, immatriculation, statut) VALUES (:vehicule, :immatriculation, :statut)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':vehicule', $data->vehicule);
            $stmt->bindParam(':immatriculation', $data->immatriculation);
            $stmt->bindParam(':statut', $statut);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["message" => "Véhicule créé avec succès."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Erreur lors de la création du véhicule."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Données incomplètes."]);
        }
        break;

    case 'PUT':
        // Mise à jour d'un véhicule
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->id) && !empty($data->vehicule) && !empty($data->immatriculation)) {
            $query = "UPDATE vehicules SET vehicule = :vehicule, immatriculation = :immatriculation, statut = :statut WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data->id);
            $stmt->bindParam(':vehicule', $data->vehicule);
            $stmt->bindParam(':immatriculation', $data->immatriculation);
            $stmt->bindParam(':statut', $data->statut);
            
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["message" => "Véhicule mis à jour avec succès."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Erreur lors de la mise à jour du véhicule."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Données incomplètes."]);
        }
        break;

    case 'GET':
        // Récupérer un ou plusieurs véhicules
        if (isset($_GET['id'])) {
            $vehicule_id = intval($_GET['id']);
            $query = "SELECT * FROM vehicules WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $vehicule_id);
            $stmt->execute();
            $vehicule = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($vehicule) {
                http_response_code(200);
                echo json_encode($vehicule);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Véhicule non trouvé."]);
            }
        } else {
            $query = "SELECT * FROM vehicules";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode($vehicules);
        }
        break;

    case 'DELETE':
        // Suppression d'un véhicule
        if (isset($_GET['id'])) {
            $vehicule_id = intval($_GET['id']);
            
            // Vérification si le véhicule existe
            $checkQuery = "SELECT id FROM vehicules WHERE id = :id";
            $stmtCheck = $db->prepare($checkQuery);
            $stmtCheck->bindParam(':id', $vehicule_id);
            $stmtCheck->execute();

            if ($stmtCheck->rowCount() > 0) {
                // Suppression du véhicule si trouvé
                $query = "DELETE FROM vehicules WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $vehicule_id);
                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode(["message" => "Véhicule supprimé avec succès."]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Erreur lors de la suppression du véhicule."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Véhicule non trouvé."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID du véhicule manquant."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Méthode non autorisée."]);
        break;
}

?>