<?php
class Database {
    private $host = "localhost";
    private $db_name = "database name";
    private $username = "database username";
    private $password = "database password";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->createTables();
        } catch(PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
        }
        return $this->conn;
    }

    private function createTables() {
        // Table chauffeurs
        $chauffeursColumns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'nom' => 'VARCHAR(100) NOT NULL',
            'prenom' => 'VARCHAR(100) NOT NULL',
            'statut' => 'VARCHAR(50) NOT NULL',
            'photo_profil' => 'VARCHAR(255)',
            'vehicule' => 'VARCHAR(255) NOT NULL',
            'immatriculation' => 'VARCHAR(100) NOT NULL',
            'contacts' => 'VARCHAR(255)',
            'syndicat' => 'VARCHAR(100)'
        ];
        $this->createOrUpdateTable('chauffeurs', $chauffeursColumns);

        // Table utilisateurs
        $usersColumns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'username' => 'VARCHAR(100) NOT NULL',
            'password' => 'VARCHAR(255) NOT NULL',
            'role' => 'VARCHAR(50) NOT NULL DEFAULT \'admin\''
        ];
        $this->createOrUpdateTable('users', $usersColumns);

        // Table locataires
        $locatairesColumns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'nom' => 'VARCHAR(100) NOT NULL',
            'prenom' => 'VARCHAR(100) NOT NULL',
            'contacts' => 'VARCHAR(255)',
            'email' => 'VARCHAR(100)',
            'adresse' => 'TEXT',
            'statut' => 'VARCHAR(50) NOT NULL DEFAULT \'Actif\'',
            'nni' => 'VARCHAR(20) NOT NULL',
            'photo_profil' => 'VARCHAR(255)'
        ];
        $this->createOrUpdateTable('locataires', $locatairesColumns);

        // Table véhicules
        $vehiculesColumns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'vehicule' => 'VARCHAR(100) NOT NULL',
            'immatriculation' => 'VARCHAR(100) NOT NULL',
            'statut' => 'ENUM(\'Disponible\', \'Indisponible\', \'En panne\') NOT NULL DEFAULT \'Disponible\''
        ];
        $this->createOrUpdateTable('vehicules', $vehiculesColumns);

        // Table locations avec gestion des notifications
        $locationsColumns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'locataire_id' => 'INT NOT NULL',
            'vehicule_id' => 'INT NOT NULL',
            'datetime_depart' => 'VARCHAR(20) NOT NULL',  // Format DD-MM-YYYY HH:MM
            'datetime_retour' => 'VARCHAR(20)',  // Format DD-MM-YYYY HH:MM
            'statut' => 'VARCHAR(50) NOT NULL',
            'reminder_sent' => 'BOOLEAN DEFAULT FALSE',  // Nouvelle colonne pour savoir si le rappel a été envoyé
            'FOREIGN KEY (locataire_id)' => 'REFERENCES locataires(id) ON DELETE CASCADE',
            'FOREIGN KEY (vehicule_id)' => 'REFERENCES vehicules(id) ON DELETE CASCADE'
        ];
        $this->createOrUpdateTable('locations', $locationsColumns);

        // Vérification de l'existence d'un utilisateur administrateur par défaut
        $stmtCheck = $this->conn->prepare("SELECT COUNT(*) FROM users");
        $stmtCheck->execute();
        $userCount = $stmtCheck->fetchColumn();

        if ($userCount == 0) {
            $defaultUsername = 'admin';
            $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
            $stmtInsert = $this->conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
            $stmtInsert->bindParam(':username', $defaultUsername);
            $stmtInsert->bindParam(':password', $defaultPassword);
            $stmtInsert->execute();
        }
    }

    private function createOrUpdateTable($tableName, $columns) {
        try {
            $tableExists = $this->conn->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;

            if (!$tableExists) {
                $columnsDefinition = implode(", ", array_map(
                    function($name, $definition) { return "$name $definition"; },
                    array_keys($columns), $columns
                ));
                $createQuery = "CREATE TABLE $tableName ($columnsDefinition)";
                $this->conn->exec($createQuery);
            } else {
                // Mise à jour de la table si elle existe déjà
                $existingColumns = $this->getColumns($tableName);
                foreach ($columns as $columnName => $columnDefinition) {
                    if (!in_array($columnName, $existingColumns)) {
                        $alterQuery = "ALTER TABLE $tableName ADD $columnName $columnDefinition";
                        $this->conn->exec($alterQuery);
                    }
                }
            }
        } catch (PDOException $e) {
            echo "Erreur lors de la gestion de la table $tableName : " . $e->getMessage();
        }
    }

    private function getColumns($tableName) {
        $query = "SHOW COLUMNS FROM $tableName";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}