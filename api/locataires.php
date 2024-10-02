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
include_once __DIR__ . '/config/notif.php';  // Inclusion du fichier de gestion des notifications

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Fonction pour valider et sécuriser les données
function validateInput($data) {
    return htmlspecialchars(strip_tags($data));
}

// Fonction pour gérer l'upload de l'image avec un nom basé sur l'ID du locataire
function handleImageUpload($file, $locataire_id) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_file_size = 10 * 1024 * 1024; // Limite à 10 MB

    // Validation du type et de la taille de l'image
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Type d\'image non valide. Seuls JPEG, PNG et GIF sont autorisés.'];
    }

    if ($file['size'] > $max_file_size) {
        return ['success' => false, 'message' => 'La taille du fichier dépasse la limite maximale de 10MB.'];
    }

    // Définir le répertoire de destination
    $target_dir = __DIR__ . "/images/locataire/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true); // Crée le répertoire si nécessaire
    }

    // Générer un nom de fichier basé sur l'ID du locataire
    $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $filename = "photo_{$locataire_id}.{$extension}";
    $target_file = $target_dir . $filename;

    // Déplacer le fichier uploadé vers le répertoire de destination
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'file_path' => "images/locataire/" . $filename];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'upload de l\'image.'];
    }
}

try {
    switch ($method) {
        case 'POST':
            // POST : Créer ou mettre à jour un locataire
            $data = $_POST;
            $files = $_FILES;

            // Validation des données
            $nom = validateInput($data['nom'] ?? '');
            $prenom = validateInput($data['prenom'] ?? '');
            $contacts = validateInput($data['contacts'] ?? '');
            $adresse = validateInput($data['adresse'] ?? '');
            $statut = validateInput($data['statut'] ?? '');
            $nni = validateInput($data['nni'] ?? '');

            if (empty($nom) || empty($prenom) || empty($contacts) || empty($adresse) || empty($statut) || empty($nni)) {
                http_response_code(400);
                echo json_encode(["message" => "Données incomplètes."]);
                exit();
            }

            if (!empty($data['id'])) {
                // Mise à jour d'un locataire existant
                $locataire_id = intval($data['id']);

                $query = "UPDATE locataires SET nom = :nom, prenom = :prenom, contacts = :contacts, adresse = :adresse, 
                          statut = :statut, nni = :nni WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $locataire_id, PDO::PARAM_INT);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':contacts', $contacts);
                $stmt->bindParam(':adresse', $adresse);
                $stmt->bindParam(':statut', $statut);
                $stmt->bindParam(':nni', $nni);

                if ($stmt->execute()) {
                    // Gérer l'upload de l'image
                    if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
                        $upload_result = handleImageUpload($files['photo'], $locataire_id);
                        if ($upload_result['success']) {
                            $image_path = $upload_result['file_path'];
                            $stmt = $db->prepare("UPDATE locataires SET photo_profil = :photo WHERE id = :id");
                            $stmt->bindParam(':photo', $image_path);
                            $stmt->bindParam(':id', $locataire_id);
                            $stmt->execute();
                        } else {
                            throw new Exception($upload_result['message']);
                        }
                    }

                    http_response_code(200);
                    echo json_encode(["message" => "Locataire mis à jour avec succès.", "id" => $locataire_id], JSON_UNESCAPED_SLASHES);
                } else {
                    throw new Exception("Erreur lors de la mise à jour du locataire.");
                }
            } else {
                // Création d'un nouveau locataire
                $query = "INSERT INTO locataires (nom, prenom, contacts, adresse, statut, nni) 
                          VALUES (:nom, :prenom, :contacts, :adresse, :statut, :nni)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':contacts', $contacts);
                $stmt->bindParam(':adresse', $adresse);
                $stmt->bindParam(':statut', $statut);
                $stmt->bindParam(':nni', $nni);

                if ($stmt->execute()) {
                    $locataire_id = $db->lastInsertId();

                    // Gérer l'upload de l'image
                    if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
                        $upload_result = handleImageUpload($files['photo'], $locataire_id);
                        if ($upload_result['success']) {
                            $image_path = $upload_result['file_path'];
                            $stmt = $db->prepare("UPDATE locataires SET photo_profil = :photo WHERE id = :id");
                            $stmt->bindParam(':photo', $image_path);
                            $stmt->bindParam(':id', $locataire_id);
                            $stmt->execute();
                        }
                    }

                    // Envoi de la notification WhatsApp de bienvenue
                    $message = "Bienvenue dans le club des prestigieux clients de Akwary Group Location de Véhicules VIP.";
                    sendWhatsAppNotification($contacts, $message);

                    http_response_code(201);
                    echo json_encode(["message" => "Locataire créé avec succès.", "id" => $locataire_id], JSON_UNESCAPED_SLASHES);
                } else {
                    throw new Exception("Erreur lors de la création du locataire.");
                }
            }
            break;

        case 'GET':
            // GET : Récupérer un ou plusieurs locataires
            if (isset($_GET['id'])) {
                // Récupérer un locataire
                $locataire_id = intval($_GET['id']);
                $query = "SELECT * FROM locataires WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $locataire_id);
                $stmt->execute();
                $locataire = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($locataire) {
                    http_response_code(200);
                    echo json_encode($locataire, JSON_UNESCAPED_SLASHES);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Locataire non trouvé."]);
                }
            } else {
                // Récupérer tous les locataires
                $query = "SELECT * FROM locataires";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $locataires = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($locataires) {
                    http_response_code(200);
                    echo json_encode($locataires, JSON_UNESCAPED_SLASHES);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Aucun locataire trouvé."]);
                }
            }
            break;

        case 'DELETE':
            // DELETE : Supprimer un locataire
            if (isset($_GET['id'])) {
                $locataire_id = intval($_GET['id']);
                
                // Vérifier si le locataire existe
                $checkQuery = "SELECT id FROM locataires WHERE id = :id";
                $stmtCheck = $db->prepare($checkQuery);
                $stmtCheck->bindParam(':id', $locataire_id);
                $stmtCheck->execute();

                if ($stmtCheck->rowCount() > 0) {
                    $query = "DELETE FROM locataires WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $locataire_id);
                    if ($stmt->execute()) {
                        http_response_code(200);
                        echo json_encode(["message" => "Locataire supprimé avec succès."]);
                    } else {
                        throw new Exception("Erreur lors de la suppression du locataire.");
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Locataire non trouvé."]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "ID de locataire manquant."]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["message" => "Méthode non autorisée."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erreur serveur : " . $e->getMessage()]);
}
?>