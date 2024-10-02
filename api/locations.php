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
include_once __DIR__ . '/config/notif.php'; // Fichier de gestion des notifications

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Création ou mise à jour d'une location
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id)) {
            // Mise à jour d'une location existante
            $location_id = intval($data->id);

            if (!empty($data->datetime_retour)) {
                // Mise à jour de la date de retour
                $datetimeRetour = DateTime::createFromFormat('d-m-Y H:i', $data->datetime_retour);
                $now = new DateTime();
                $statut = ($datetimeRetour && $datetimeRetour < $now) ? 'terminé' : 'en cours';

                $query = "UPDATE locations SET datetime_retour = :datetime_retour, statut = :statut WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':datetime_retour', $data->datetime_retour);
                $stmt->bindParam(':statut', $statut);
                $stmt->bindParam(':id', $location_id);

                if ($stmt->execute()) {
                    // Mise à jour du statut du véhicule
                    if ($statut === 'terminé') {
                        updateVehicleStatus($db, $data->vehicule_id, 'Disponible');
                    }

                    // Notification pour la modification de datetime_retour
                    $locataire = getLocataireInfo($db, $data->locataire_id);
                    $message = "La date de retour pour votre location a été modifiée. Nouvelle date de retour : " . $data->datetime_retour;
                    sendWhatsAppNotification($locataire['contacts'], $message); // Envoi immédiat de la notification

                    // Reprogrammer la notification de rappel 3 heures avant la nouvelle date de retour
                    if (!empty($data->datetime_retour)) {
                        scheduleReminder($locataire['contacts'], $data->datetime_retour);  // Planification du rappel
                    }

                    http_response_code(200);
                    echo json_encode(["message" => "Date de retour mise à jour avec succès."]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Erreur lors de la mise à jour de la date de retour."]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Date de retour manquante."]);
            }
        } else {
            // Création d'une nouvelle location
            if (!empty($data->locataire_id) && !empty($data->vehicule_id) && !empty($data->datetime_depart)) {
                $datetimeDepart = DateTime::createFromFormat('d-m-Y H:i', $data->datetime_depart);
                $datetimeRetour = !empty($data->datetime_retour) ? DateTime::createFromFormat('d-m-Y H:i', $data->datetime_retour) : null;
                $now = new DateTime();
                $statut = ($datetimeRetour && $datetimeRetour < $now) ? 'terminé' : 'en cours';

                $query = "INSERT INTO locations (locataire_id, vehicule_id, datetime_depart, datetime_retour, statut) 
                          VALUES (:locataire_id, :vehicule_id, :datetime_depart, :datetime_retour, :statut)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':locataire_id', $data->locataire_id);
                $stmt->bindParam(':vehicule_id', $data->vehicule_id);
                $stmt->bindParam(':datetime_depart', $data->datetime_depart);
                $stmt->bindParam(':datetime_retour', $data->datetime_retour);
                $stmt->bindParam(':statut', $statut);

                if ($stmt->execute()) {
                    updateVehicleStatus($db, $data->vehicule_id, 'Indisponible');

                    // Envoi de la notification WhatsApp pour la création
                    $locataire = getLocataireInfo($db, $data->locataire_id);
                    $message = "Votre location est enregistree . Date de départ : " . $data->datetime_depart . 
                               ", Date de retour : " . $data->datetime_retour;
                    sendWhatsAppNotification($locataire['contacts'], $message);

                    // Planification du rappel 3 heures avant la date de retour
                    if (!empty($data->datetime_retour)) {
                        scheduleReminder($locataire['contacts'], $data->datetime_retour);
                    }

                    http_response_code(201);
                    echo json_encode(["message" => "Location créée avec succès."]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Erreur lors de la création de la location."]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Données incomplètes."]);
            }
        }
        break;

    case 'GET':
        // Récupérer une ou plusieurs locations
        if (isset($_GET['id'])) {
            $location_id = intval($_GET['id']);
            $query = "SELECT * FROM locations WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $location_id);
            $stmt->execute();
            $location = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($location) {
                http_response_code(200);
                echo json_encode($location);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Location non trouvée."]);
            }
        } else {
            $query = "SELECT * FROM locations";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($locations === false || empty($locations)) {
                $locations = [];
            }

            http_response_code(200);
            echo json_encode($locations);
        }
        break;

    case 'DELETE':
        // Suppression d'une location
        $data = json_decode(file_get_contents("php://input"));
        if (isset($data->id)) {
            $location_id = intval($data->id);
            $query = "DELETE FROM locations WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $location_id);

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["message" => "Location supprimée avec succès."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Erreur lors de la suppression de la location."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID de location manquant."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Méthode non autorisée."]);
        break;
}

// Fonction pour mettre à jour le statut du véhicule
function updateVehicleStatus($db, $vehicule_id, $statut) {
    $query = "UPDATE vehicules SET statut = :statut WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':statut', $statut);
    $stmt->bindParam(':id', $vehicule_id);
    $stmt->execute();
}