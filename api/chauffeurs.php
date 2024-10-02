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
include_once __DIR__ . '/upload_image.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];


// Validate data before using it
function validateInput($data) {
    return htmlspecialchars(strip_tags($data));
}

// Restrict file types and sizes for image uploads
function validateImageUpload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_file_size = 10 * 1024 * 1024; // 2 MB limit

    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid image type. Only JPEG, PNG, and GIF are allowed.'];
    }

    if ($file['size'] > $max_file_size) {
        return ['success' => false, 'message' => 'File size exceeds the maximum limit of 2MB.'];
    }

    return ['success' => true];
}

try {
    switch ($method) {
        case 'POST':
            // POST: Create or update chauffeur
            $data = $_POST;
            $files = $_FILES;

            $nom = validateInput($data['nom'] ?? '');
            $prenom = validateInput($data['prenom'] ?? '');
            $statut = validateInput($data['statut'] ?? '');
            $vehicule = validateInput($data['vehicule'] ?? '');
            $immatriculation = validateInput($data['immatriculation'] ?? '');
            $contacts = validateInput($data['contacts'] ?? '');
            $syndicat = validateInput($data['syndicat'] ?? '');

            if (empty($nom) || empty($prenom) || empty($statut) || empty($vehicule) ||
                empty($immatriculation) || empty($contacts) || empty($syndicat)) {
                http_response_code(400);
                echo json_encode(["message" => "Données incomplètes."]);
                exit();
            }

            if (!empty($data['id'])) {
                // Updating an existing chauffeur
                $chauffeur_id = intval($data['id']);

                $query = "UPDATE chauffeurs SET nom = :nom, prenom = :prenom, statut = :statut, vehicule = :vehicule,
                          immatriculation = :immatriculation, contacts = :contacts, syndicat = :syndicat WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $chauffeur_id, PDO::PARAM_INT);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':statut', $statut);
                $stmt->bindParam(':vehicule', $vehicule);
                $stmt->bindParam(':immatriculation', $immatriculation);
                $stmt->bindParam(':contacts', $contacts);
                $stmt->bindParam(':syndicat', $syndicat);

                if ($stmt->execute()) {
                    // Handle image upload
                    if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
                        $image_validation = validateImageUpload($files['photo']);
                        if (!$image_validation['success']) {
                            throw new Exception($image_validation['message']);
                        }

                        $upload_result = uploadImage($files['photo'], $chauffeur_id, 'chauffeur');
                        if ($upload_result['success']) {
                            $image_path = $upload_result['file_path'];
                            $stmt = $db->prepare("UPDATE chauffeurs SET photo_profil = :photo WHERE id = :id");
                            $stmt->bindParam(':photo', $image_path);
                            $stmt->bindParam(':id', $chauffeur_id);
                            $stmt->execute();
                        } else {
                            throw new Exception("Erreur lors de l'upload de l'image.");
                        }
                    }

                    http_response_code(200);
                    echo json_encode(["message" => "Chauffeur mis à jour avec succès.", "id" => $chauffeur_id]);
                } else {
                    throw new Exception("Erreur lors de la mise à jour du chauffeur.");
                }
            } else {
                // Creating a new chauffeur
                $query = "INSERT INTO chauffeurs (nom, prenom, statut, vehicule, immatriculation, contacts, syndicat)
                          VALUES (:nom, :prenom, :statut, :vehicule, :immatriculation, :contacts, :syndicat)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':statut', $statut);
                $stmt->bindParam(':vehicule', $vehicule);
                $stmt->bindParam(':immatriculation', $immatriculation);
                $stmt->bindParam(':contacts', $contacts);
                $stmt->bindParam(':syndicat', $syndicat);

                if ($stmt->execute()) {
                    $chauffeur_id = $db->lastInsertId();

                    if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
                        $image_validation = validateImageUpload($files['photo']);
                        if (!$image_validation['success']) {
                            throw new Exception($image_validation['message']);
                        }

                        $upload_result = uploadImage($files['photo'], $chauffeur_id, 'chauffeur');
                        if ($upload_result['success']) {
                            $image_path = $upload_result['file_path'];
                            $stmt = $db->prepare("UPDATE chauffeurs SET photo_profil = :photo WHERE id = :id");
                            $stmt->bindParam(':photo', $image_path);
                            $stmt->bindParam(':id', $chauffeur_id);
                            $stmt->execute();
                        }
                    }

                    http_response_code(201);
                    echo json_encode(["message" => "Chauffeur créé avec succès.", "id" => $chauffeur_id]);
                } else {
                    throw new Exception("Erreur lors de la création du chauffeur.");
                }
            }
            break;

        case 'GET':
            // GET: Fetch one or more chauffeurs
            if (isset($_GET['id'])) {
                // Fetch a single chauffeur
                $chauffeur_id = intval($_GET['id']);
                $query = "SELECT * FROM chauffeurs WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $chauffeur_id);
                $stmt->execute();
                $chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($chauffeur) {
                    http_response_code(200);
                    echo json_encode($chauffeur);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Chauffeur non trouvé."]);
                }
            } else {
                // Fetch all chauffeurs
                $query = "SELECT * FROM chauffeurs";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($chauffeurs) {
                    http_response_code(200);
                    echo json_encode($chauffeurs);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Aucun chauffeur trouvé."]);
                }
            }
            break;

        case 'DELETE':
            // DELETE: Delete a chauffeur
            $data = json_decode(file_get_contents("php://input"));
            if (isset($data->id)) {
                $chauffeur_id = intval($data->id);
                $query = "DELETE FROM chauffeurs WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $chauffeur_id);
                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode(["message" => "Chauffeur supprimé avec succès."]);
                } else {
                    throw new Exception("Erreur lors de la suppression du chauffeur.");
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "ID de chauffeur manquant."]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    logError($e->getMessage());
    echo json_encode(["message" => "Erreur serveur : " . $e->getMessage()]);
}