# Akwary Group - Backend API (PHP)

## Description

Ce projet constitue l'API backend pour **Akwary Group**, une société spécialisée dans la location de véhicules VIP et la gestion des chauffeurs VTC. Le backend fournit des API pour gérer les locataires, les véhicules, les chauffeurs VTC, et les locations. Il gère également l'envoi de notifications WhatsApp via l'intégration avec l'API Wassenger.

## Fonctionnalités principales

- **Gestion des locataires** : API pour la création, la modification et la suppression des fiches de locataires.
- **Gestion des véhicules** : API pour gérer les véhicules, leur disponibilité et leur statut.
- **Gestion des chauffeurs VTC** : API pour gérer les chauffeurs VTC et leur affectation à des véhicules.
- **Gestion des locations** : API pour gérer les locations de véhicules, avec suivi des dates de départ et de retour.
- **Notifications WhatsApp** : Envoi automatique de notifications aux locataires via l'API Wassenger, avec des rappels 3 heures avant le retour des véhicules.
- **Planification des rappels** : Utilisation de tâches cron pour envoyer des rappels planifiés avant la fin de la location.

## Technologies

- **Langage** : PHP
- **Base de données** : MySQL
- **Notifications** : Intégration avec l'API WhatsApp Wassenger
- **Sécurité** : Gestion des utilisateurs avec rôles (admin) et authentification.

## Installation

### Prérequis

- Serveur Web Apache avec PHP 7.4 ou supérieur.
- MySQL 5.7 ou supérieur.
- [Wassenger API](https://wassenger.com/) pour l'intégration WhatsApp.
- Accès au terminal pour configurer les tâches cron.

### Étapes d'installation

1. **Cloner le dépôt** :
    ```bash
    git clone https://github.com/akenewa/AkwaryGroupbackend.git
    cd 
    ```

2. **Configurer la base de données** :
    - Créez une base de données MySQL et configurez les paramètres dans le fichier `/config/database.php` :
    ```php
    class Database {
        private $host = "localhost";
        private $db_name = "nom_de_la_base";
        private $username = "utilisateur";
        private $password = "mot_de_passe";
        public $conn;
    }
    ```

3. **Importer le schéma de base de données** :
    - Le fichier `schema.sql` dans le dossier `sql/` contient les tables à importer dans votre base de données.

4. **Configurer l'API Wassenger** :
    - Dans le fichier `/config/notif.php`, ajoutez votre clé API Wassenger :
    ```php
    define('WASSENGER_API_KEY', 'votre_cle_api_wassenger');
    ```

5. **Configurer Cron pour les rappels** :
    - Ajoutez la tâche cron suivante pour exécuter le script de rappel toutes les minutes :
    ```bash
    * * * * * /usr/bin/php /chemin/vers/votre/dossier/api/config/notif.php
    ```

6. **Lancer l'API** :
    - Utilisez un serveur Apache et assurez-vous que les configurations CORS sont correctement définies dans les fichiers PHP.

## Structure du projet

- `/api/config/database.php` : Configuration de la base de données.
- `/api/config/notif.php` : Gestion des notifications WhatsApp et des rappels planifiés.
- `/api/locataires.php` : API CRUD pour gérer les locataires.
- `/api/vehicules.php` : API CRUD pour gérer les véhicules.
- `/api/chauffeurs.php` : API CRUD pour gérer les chauffeurs VTC.
- `/api/locations.php` : API CRUD pour gérer les locations et les rappels automatiques.

## Fonctionnalités détaillées

- **Notifications automatiques** : Lors de la création d'une location, une notification est envoyée au locataire. Un rappel est également envoyé 3 heures avant la fin de la location.
- **Planification de tâches avec cron** : Un script PHP est exécuté régulièrement via cron pour vérifier les rappels à envoyer et planifier les notifications futures.
- **Gestion des images** : L'API prend en charge l'upload et la gestion des images pour les locataires et chauffeurs.
- **Sécurité** : Les utilisateurs sont gérés avec des rôles et des permissions (administrateur uniquement).

## Auteurs

- **Équipe de développement Akwary Group** : Développement de l'API backend.
