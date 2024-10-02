<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akwary Group - API Backend</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #333;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            margin-top: 0;
        }
        p {
            line-height: 1.6;
        }
        .endpoint {
            margin: 10px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #0073aa;
            border-radius: 4px;
        }
        .endpoint h2 {
            margin: 0;
            font-size: 1.2em;
        }
        .endpoint p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #555;
        }
        .footer {
            text-align: center;
            padding: 10px;
            background-color: #333;
            color: white;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bienvenue sur l'API Backend Akwary Group</h1>
    </div>
    <div class="container">
        <h1>À propos</h1>
        <p>Cette API permet de gérer les informations des chauffeurs et des véhicules pour Akwary Group.</p>
        
        <div class="endpoint">
            <h2>Créer un chauffeur</h2>
            <p>POST /api/chauffeurs/create.php</p>
            <p>Créer un nouveau chauffeur avec les informations nécessaires.</p>
        </div>
        <div class="endpoint">
            <h2>Lire un chauffeur</h2>
            <p>GET /api/chauffeurs/read.php?id={id}</p>
            <p>Obtenir les détails d'un chauffeur spécifique en utilisant son identifiant unique.</p>
        </div>
        <div class="endpoint">
            <h2>Lire tous les chauffeurs</h2>
            <p>GET /api/chauffeurs/read_all.php</p>
            <p>Obtenir la liste de tous les chauffeurs enregistrés dans le système.</p>
        </div>
        <div class="endpoint">
            <h2>Mettre à jour un chauffeur</h2>
            <p>PUT /api/chauffeurs/update.php</p>
            <p>Mettre à jour les informations d'un chauffeur existant.</p>
        </div>
        <div class="endpoint">
            <h2>Supprimer un chauffeur</h2>
            <p>DELETE /api/chauffeurs/delete.php</p>
            <p>Supprimer un chauffeur de la base de données.</p>
        </div>
        <div class="endpoint">
            <h2>Connexion utilisateur</h2>
            <p>POST /api/users/login.php</p>
            <p>Authentification des utilisateurs pour accéder aux fonctionnalités de gestion.</p>
        </div>
        <div class="endpoint">
            <h2>Télécharger une image</h2>
            <p>POST /api/upload_image.php?id={id}</p>
            <p>Télécharger ou mettre à jour l'image de profil d'un chauffeur.</p>
        </div>
    </div>
    <div class="footer">
        <p>&copy; <?php echo date("Y"); ?> Akwary Group - Tous droits réservés.</p>
    </div>
</body>
</html>