<?php

// Inclusion de la configuration de la base de données
include_once __DIR__ . '/database.php';

$database = new Database();  // Correction ici
$db = $database->getConnection();  // Utilisation correcte de l'objet

// Clé API Wassenger (à remplacer par votre propre clé API)
define('WASSENGER_API_KEY', '94d5c9bc3f6be03dc9c35da7c510d79c8c267d3f3303828e9c161673dea23e6ba9f7382c4ac33bed');

// Fonction pour envoyer une notification WhatsApp
function sendWhatsAppNotification($phoneNumber, $message) {
    $apiUrl = 'https://api.wassenger.com/v1/messages';

    // Format du numéro de téléphone
    $formattedPhoneNumber = formatPhoneNumber($phoneNumber);

    // Préparation de la requête
    $data = [
        'phone' => $formattedPhoneNumber,
        'message' => $message
    ];

    $headers = [
        'Authorization: Bearer ' . WASSENGER_API_KEY,
        'Content-Type: application/json'
    ];

    $options = [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($data)
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    if ($response === false) {
        error_log('Erreur lors de l\'envoi de la notification WhatsApp: ' . curl_error($ch));
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            error_log('Erreur HTTP lors de l\'envoi de la notification WhatsApp: ' . $response);
        }
    }

    curl_close($ch);
}

// Fonction pour formater les numéros de téléphone selon les exigences de Wassenger
function formatPhoneNumber($phoneNumber) {
    // Suppression des espaces, des caractères spéciaux et ajout de l'indicatif pays si nécessaire
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

    // Vérifiez si l'indicatif pays est manquant (par exemple, pour un numéro ivoirien)
    if (substr($phoneNumber, 0, 2) !== '22') {
        $phoneNumber = '225' . $phoneNumber;  // Ajoute l'indicatif de la Côte d'Ivoire, par exemple
    }

    return $phoneNumber;
}

// Fonction pour récupérer les informations d'un locataire à partir de l'ID
function getLocataireInfo($db, $locataire_id) {
    $query = "SELECT contacts FROM locataires WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $locataire_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour planifier un rappel 3 heures avant la date/heure de retour (géré par cron)
function scheduleReminder($phoneNumber, $datetimeRetour) {
    global $db;

    $reminderTime = new DateTime($datetimeRetour);
    $reminderTime->modify('-3 hours');  // Rappel 3 heures avant la date de retour
    $formattedReminderTime = $reminderTime->format('Y-m-d H:i:s');

    // Insérer la tâche de rappel dans une table des tâches planifiées
    $query = "INSERT INTO reminders (phone_number, message, reminder_time) VALUES (:phone_number, :message, :reminder_time)";
    $stmt = $db->prepare($query);
    $message = "Rappel : Il reste 3 heures avant le retour de votre location. Merci de retourner le véhicule à temps.";
    $stmt->bindParam(':phone_number', $phoneNumber);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':reminder_time', $formattedReminderTime);
    $stmt->execute();
}

// Fonction pour envoyer des notifications de rappel planifiées (gérée par le cron job)
function sendReturnReminder() {
    global $db;

    // Obtenez l'heure actuelle
    $now = new DateTime();
    $formattedNow = $now->format('Y-m-d H:i:s');

    // Rechercher les rappels planifiés pour maintenant ou avant
    $query = "SELECT phone_number, message FROM reminders WHERE reminder_time <= :now";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':now', $formattedNow);
    $stmt->execute();

    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Envoyer chaque rappel et supprimer de la table
    foreach ($reminders as $reminder) {
        sendWhatsAppNotification($reminder['phone_number'], $reminder['message']);

        // Supprimer le rappel une fois envoyé
        $deleteQuery = "DELETE FROM reminders WHERE phone_number = :phone_number AND message = :message";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':phone_number', $reminder['phone_number']);
        $deleteStmt->bindParam(':message', $reminder['message']);
        $deleteStmt->execute();
    }
}

// Appeler cette fonction depuis le cron job pour envoyer les rappels
sendReturnReminder();

?>