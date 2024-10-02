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

// Définir les headers CORS pour toutes les autres réponses
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");


function uploadImage($file, $id, $entityType) {
    // Chemin absolu pour le répertoire des images, avec des sous-dossiers pour chaque entité
    $target_base_dir = __DIR__ . "/images/";

    // Liste des entités autorisées
    $allowedEntities = ['chauffeur', 'locataire', 'vehicule'];

    if (!in_array($entityType, $allowedEntities)) {
        return ["success" => false, "message" => "Entité non reconnue pour l'upload d'images."];
    }

    // Créer un répertoire pour chaque entité (par exemple, /images/chauffeurs/, /images/locataires/)
    $target_dir = $target_base_dir . $entityType . "/";

    // Extensions de fichiers autorisées
    $allowed_file_types = ['jpg', 'jpeg', 'png', 'gif'];

    // Vérification si le fichier est une image valide
    if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return ["success" => false, "message" => "Le fichier téléchargé est invalide ou n'existe pas."];
    }

    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ["success" => false, "message" => "Le fichier n'est pas une image valide."];
    }

    // Limitation de la taille du fichier (50 MB max)
    if ($file['size'] > 50000000) { // 50MB
        return ["success" => false, "message" => "Le fichier est trop grand. La taille maximale est de 50MB."];
    }

    // Vérification du type de fichier
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowed_file_types)) {
        return ["success" => false, "message" => "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés."];
    }

    // Créer le répertoire de destination s'il n'existe pas déjà
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            error_log("Erreur lors de la création du répertoire : " . $target_dir);
            return ["success" => false, "message" => "Erreur lors de la création du répertoire de destination."];
        }
    }

    // Définir le chemin complet du fichier
    $new_file_path = $target_dir . "photo_" . $id . "." . $fileType;

    // Supprimer l'ancienne image s'il y en a une
    foreach ($allowed_file_types as $extension) {
        $old_file = $target_dir . "photo_" . $id . "." . $extension;
        if (file_exists($old_file) && $old_file !== $new_file_path) {
            unlink($old_file);
        }
    }

    // Déplacer le fichier téléchargé dans le répertoire cible
    if (move_uploaded_file($file['tmp_name'], $new_file_path)) {
        // Retourner le chemin relatif pour stocker dans la base de données
        $relative_path = "/backend/api/images/" . $entityType . "/photo_" . $id . "." . $fileType;

        return ["success" => true, "file_path" => $relative_path];
    } else {
        return ["success" => false, "message" => "Erreur lors du téléchargement de l'image."];
    }
}
?>