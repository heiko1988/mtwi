<?php
/**
 * Motor Town Web Interface (MTWI) - Spielerdaten-Erweiterung für die Datenbankklasse
 */

/**
 * Erweitert die Database-Klasse um Methoden zur Verwaltung von Spielerdaten
 */
class DatabasePlayers extends Database {
    
    /**
     * Konstruktor für die DatabasePlayers-Klasse
     * 
     * @param array $config Datenbank-Konfiguration
     */
    public function __construct($config = null) {
        // Stellen sicher, dass die Konfiguration übergeben wird
        if ($config === null) {
            global $config;
            $dbConfig = $config['database'];
        } else {
            $dbConfig = $config;
        }
        
        // Elternkonstruktor aufrufen, der die Datenbankverbindung initialisiert
        parent::__construct($dbConfig);
        
        // Erst nachdem die Datenbankverbindung initialisiert wurde, die Tabellen erstellen
        $this->initializePlayerTables();
    }
    
    /**
     * Initialisiert die Spielerdaten-Tabellen
     */
    public function initializePlayerTables() {
        // Verbindung zur Datenbank holen
        $db = $this->getConnection();
        
        // Datenbanktyp ermitteln (SQLite oder MySQL)
        $dbType = $this->getDatabaseType();
        $autoIncrement = ($dbType == 'sqlite') ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        
        // Spieler-Tabelle
        $db->exec("CREATE TABLE IF NOT EXISTS players (
            id INTEGER PRIMARY KEY {$autoIncrement},
            name TEXT NOT NULL,
            steam_id TEXT NOT NULL,
            first_seen INTEGER,
            last_seen INTEGER,
            taxi_level INTEGER DEFAULT 0,
            bus_level INTEGER DEFAULT 0,
            wrecker_level INTEGER DEFAULT 0,
            police_level INTEGER DEFAULT 0,
            driver_level INTEGER DEFAULT 0,
            truck_level INTEGER DEFAULT 0,
            company TEXT,
            UNIQUE(steam_id)
        )");
        
        // Überprüfen und ggf. hinzufügen der company-Spalte für bestehende Tabellen
        $this->addColumnIfNotExists('players', 'company', 'TEXT');
        $this->addColumnIfNotExists('players', 'driver_level', 'INTEGER DEFAULT 0');
        $this->addColumnIfNotExists('players', 'truck_level', 'INTEGER DEFAULT 0');
        
        // Spieler-Aktivitäten-Tabelle
        $db->exec("CREATE TABLE IF NOT EXISTS player_activities (
            id INTEGER PRIMARY KEY {$autoIncrement},
            player_id INTEGER NOT NULL,
            timestamp INTEGER NOT NULL,
            action TEXT NOT NULL,
            vehicle_name TEXT,
            vehicle_id TEXT,
            FOREIGN KEY (player_id) REFERENCES players(id)
        )");
        
        // Indizes für schnellere Abfragen
        $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_player_id ON player_activities(player_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_players_steam_id ON players(steam_id)");
    }
    
    /**
     * Ermittelt den Datenbanktyp (SQLite oder MySQL)
     * 
     * @return string 'sqlite' oder 'mysql'
     */
    private function getDatabaseType() {
        $db = $this->getConnection();
        $driverName = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $driverName == 'sqlite' ? 'sqlite' : 'mysql';
    }
    
    /**
     * Überschreibt die initialize-Methode der Elternklasse
     */
    public function initialize() {
        parent::initialize();
        $this->initializePlayerTables();
    }
    
    /**
     * Fügt einen neuen Spieler hinzu oder aktualisiert einen bestehenden
     * 
     * @param string $name      Name des Spielers
     * @param string $steamId   Steam-ID des Spielers
     * @param int    $firstSeen Zeitpunkt, wann der Spieler zuerst gesehen wurde (optional)
     * @param int    $lastSeen  Zeitpunkt, wann der Spieler zuletzt gesehen wurde (optional)
     * @param string $company   Name des Unternehmens des Spielers (optional)
     * @param array  $levels    Levels des Spielers für verschiedene Berufe (optional)
     * @return string           'updated' oder 'added'
     */
    public function addOrUpdatePlayer($name, $steamId, $firstSeen = null, $lastSeen = null, $company = null, $levels = []) {
        $now = time();
        $db = $this->getConnection();
        
        // Zeitstempel validieren
        $firstSeen = ($firstSeen === null) ? $now : (int)$firstSeen;
        $lastSeen = ($lastSeen === null) ? $now : (int)$lastSeen;
        
        // Prüfen, ob der Spieler bereits existiert
        $stmt = $db->prepare("SELECT id, first_seen, company FROM players WHERE steam_id = ?");
        $stmt->execute([$steamId]);
        $player = $stmt->fetch();
        
        // Wenn $company null ist und der Spieler existiert und eine Firma hat, behalten wir die bestehende Firma bei
        if ($company === null && $player && $player['company']) {
            $company = $player['company'];
        }
        
        // Levels standardisieren
        $levels = array_merge([
            'taxi_level' => 0,
            'bus_level' => 0,
            'wrecker_level' => 0,
            'police_level' => 0,
            'driver_level' => 0,
            'truck_level' => 0
        ], $levels);
        
        if ($player) {
            // Spieler aktualisieren
            $sql = "UPDATE players SET 
                name = ?, 
                last_seen = ?";  
            
            $params = [$name, $lastSeen];
            
            // Company nur aktualisieren, wenn ein Wert übergeben wurde
            if ($company !== null) {
                $sql .= ", company = ?";
                $params[] = $company;
            }
            
            // Levels aktualisieren, wenn sie explizit übergeben wurden
            foreach ($levels as $key => $value) {
                if ($value !== null) {
                    $sql .= ", $key = ?";
                    $params[] = (int)$value;
                }
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $player['id'];
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return 'updated';
        } else {
            // Neuen Spieler hinzufügen
            $sql = "INSERT INTO players (
                name, steam_id, first_seen, last_seen, 
                company, taxi_level, bus_level, wrecker_level, 
                police_level, driver_level, truck_level
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; 
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $name, $steamId, $firstSeen, $lastSeen,
                $company,
                $levels['taxi_level'],
                $levels['bus_level'],
                $levels['wrecker_level'],
                $levels['police_level'],
                $levels['driver_level'],
                $levels['truck_level']
            ]);
            return 'added';
        }
    }
    
    /**
     * Fügt eine Spalte zu einer Tabelle hinzu, falls sie noch nicht existiert
     * 
     * @param string $table  Tabellenname
     * @param string $column Spaltenname
     * @param string $type   Datentyp der Spalte
     */
    public function addColumnIfNotExists($table, $column, $type) {
        $db = $this->getConnection();
        $dbType = $this->getDatabaseType();
        
        try {
            if ($dbType == 'sqlite') {
                // SQLite-spezifisch
                $stmt = $db->prepare("PRAGMA table_info($table)");
                $stmt->execute();
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $columnExists = false;
                foreach ($columns as $col) {
                    if ($col['name'] == $column) {
                        $columnExists = true;
                        break;
                    }
                }
                
                if (!$columnExists) {
                    $db->exec("ALTER TABLE $table ADD COLUMN $column $type");
                }
            } else {
                // MySQL-spezifisch
                $stmt = $db->prepare("SHOW COLUMNS FROM $table LIKE ?");
                $stmt->execute([$column]);
                if ($stmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE $table ADD COLUMN $column $type");
                }
            }
        } catch (PDOException $e) {
            // Fehler protokollieren
            error_log("Fehler beim Hinzufügen der Spalte $column: " . $e->getMessage());
        }
    }
    
    /**
     * Aktualisiert das Level eines Spielers
     * 
     * @param int    $playerId   ID des Spielers
     * @param string $levelType  Typ des Levels (taxi, bus, wrecker, police, driver, truck)
     * @param int    $levelValue Wert des Levels
     * @return bool              Erfolg
     */
    public function updatePlayerLevel($playerId, $levelType, $levelValue) {
        $validLevelTypes = ['taxi', 'bus', 'wrecker', 'police'];
        
        if (!in_array($levelType, $validLevelTypes)) {
            return false;
        }
        
        $columnName = $levelType . '_level';
        $db = $this->getConnection();
        
        $stmt = $db->prepare("UPDATE players SET {$columnName} = ? WHERE id = ?");
        $stmt->execute([$levelValue, $playerId]);
        
        return $stmt->rowCount() > 0;
    }
    

    
    /**
     * Aktualisiert alle Level eines Spielers auf einmal
     * 
     * @param string $steamId     Steam-ID des Spielers
     * @param int    $taxiLevel    Taxi-Level
     * @param int    $busLevel     Bus-Level
     * @param int    $wreckerLevel Abschlepp-Level
     * @param int    $policeLevel  Polizei-Level
     * @return bool                Erfolg
     */
    public function updatePlayerLevels($steamId, $taxiLevel, $busLevel, $wreckerLevel, $policeLevel) {
        // Spieler anhand der Steam-ID finden
        $player = $this->getPlayerBySteamId($steamId);
        if (!$player) {
            return false;
        }
        
        $playerId = $player['id'];
        $db = $this->getConnection();
        
        // Alle Level in einer Abfrage aktualisieren
        $stmt = $db->prepare("UPDATE players SET 
            taxi_level = ?,
            bus_level = ?,
            wrecker_level = ?,
            police_level = ? 
            WHERE id = ?");
            
        $stmt->execute([$taxiLevel, $busLevel, $wreckerLevel, $policeLevel, $playerId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Fügt eine Spieleraktivität hinzu
     * 
     * @param int    $playerId    ID des Spielers
     * @param string $action      Beschreibung der Aktivität
     * @param string $vehicleName Name des Fahrzeugs (optional)
     * @param string $vehicleId   ID des Fahrzeugs (optional)
     * @return int                ID der neuen Aktivität
     */
    public function addPlayerActivity($playerId, $action, $vehicleName = null, $vehicleId = null) {
        $db = $this->getConnection();
        $stmt = $db->prepare("INSERT INTO player_activities 
            (player_id, timestamp, action, vehicle_name, vehicle_id) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$playerId, time(), $action, $vehicleName, $vehicleId]);
        
        return $db->lastInsertId();
    }
    
    /**
     * Fügt eine Spieleraktivität mit Zeitstempel hinzu anhand der Steam-ID
     * 
     * @param string $steamId   Steam-ID des Spielers
     * @param int    $timestamp Zeitstempel der Aktivität
     * @param string $action    Beschreibung der Aktivität
     * @param string $details   JSON-codierte Details zur Aktivität (optional)
     * @return int|false        ID der neuen Aktivität oder false bei Fehler
     */
    public function addPlayerActivityWithTimestamp($steamId, $timestamp, $action, $details = null) {
        $db = $this->getConnection();
        
        // Zuerst die interne ID des Spielers ermitteln
        $stmt = $db->prepare("SELECT id FROM players WHERE steam_id = ?");
        $stmt->execute([$steamId]);
        $playerId = $stmt->fetchColumn();
        
        if (!$playerId) {
            return false; // Spieler nicht gefunden
        }
        
        // Details parsen, wenn als JSON übergeben
        $vehicleName = null;
        $vehicleId = null;
        
        if ($details) {
            // Versuchen, die Details zu dekodieren, falls es ein JSON-String ist
            if (is_string($details)) {
                $detailsArray = json_decode($details, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($detailsArray)) {
                    // Falls Details ein Fahrzeug enthalten
                    if (isset($detailsArray['vehicle'])) {
                        $vehicleName = $detailsArray['vehicle'];
                    }
                    if (isset($detailsArray['vehicle_id'])) {
                        $vehicleId = $detailsArray['vehicle_id'];
                    }
                }
            }
        }
        
        // Aktivität hinzufügen
        $stmt = $db->prepare("INSERT INTO player_activities 
            (player_id, timestamp, action, vehicle_name, vehicle_id) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$playerId, $timestamp, $action, $vehicleName, $vehicleId]);
        
        return $db->lastInsertId();
    }
    
    /**
     * Holt alle Spieler
     * 
     * @return array Liste der Spieler
     */
    public function getAllPlayers() {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM players ORDER BY last_seen DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Holt einen Spieler anhand seiner ID
     * 
     * @param int $playerId ID des Spielers
     * @return array|false  Spielerdaten oder false, wenn nicht gefunden
     */
    public function getPlayerById($playerId) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->execute([$playerId]);
        return $stmt->fetch();
    }
    
    /**
     * Holt einen Spieler anhand seiner Steam-ID
     * 
     * @param string $steamId  Steam-ID des Spielers
     * @return array|false     Spielerdaten oder false, wenn nicht gefunden
     */
    public function getPlayerBySteamId($steamId) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM players WHERE steam_id = ?");
        $stmt->execute([$steamId]);
        return $stmt->fetch();
    }
    
    /**
     * Holt die Aktivitäten eines Spielers
     * 
     * @param int $playerId ID des Spielers
     * @param int $limit    Maximale Anzahl zurückzugebender Aktivitäten
     * @return array        Liste der Aktivitäten
     */
    public function getPlayerActivities($playerId, $limit = 50) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM player_activities 
            WHERE player_id = ? ORDER BY timestamp DESC LIMIT ?");
        $stmt->execute([$playerId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Verarbeitet Server-Log-Einträge und aktualisiert die Spielerdaten
     * 
     * @param array $logEntries Array mit Log-Einträgen
     * @return array            Statistik über verarbeitete Einträge
     */
    public function processServerLogs($logEntries) {
        $stats = [
            'players_added' => 0,
            'players_updated' => 0,
            'levels_updated' => 0,
            'activities_added' => 0
        ];
        
        foreach ($logEntries as $entry) {
            // Spieler-Login erkennen
            if (strpos($entry, 'Player Login:') !== false) {
                preg_match('/Player Login: ([^\(]+) \(([0-9]+)\)/', $entry, $matches);
                if (count($matches) === 3) {
                    $playerName = trim($matches[1]);
                    $steamId = $matches[2];
                    
                    // Spieler hinzufügen oder aktualisieren
                    $playerId = $this->addOrUpdatePlayer($playerName, $steamId);
                    
                    if ($this->db->lastInsertId()) {
                        $stats['players_added']++;
                    } else {
                        $stats['players_updated']++;
                    }
                    
                    // Aktivität hinzufügen
                    $this->addPlayerActivity($playerId, 'login');
                    $stats['activities_added']++;
                }
            }
            
            // Level-Änderungen erkennen
            elseif (strpos($entry, 'Player level changed') !== false) {
                preg_match('/Player level changed\. Player=([^\(]+) \(([0-9]+)\) Level=([^(]+)\(([0-9]+)\)/', $entry, $matches);
                if (count($matches) === 5) {
                    $playerName = trim($matches[1]);
                    $steamId = $matches[2];
                    $levelType = trim($matches[3]);
                    $levelValue = intval($matches[4]);
                    
                    // Spieler finden oder erstellen
                    $player = $this->getPlayerBySteamId($steamId);
                    if (!$player) {
                        $playerId = $this->addOrUpdatePlayer($playerName, $steamId);
                        $stats['players_added']++;
                    } else {
                        $playerId = $player['id'];
                    }
                    
                    // Level-Typ bestimmen
                    $levelTypeMapping = [
                        'CL_Taxi' => 'taxi',
                        'CL_Bus' => 'bus',
                        'CL_Wrecker' => 'wrecker',
                        'CL_Police' => 'police'
                    ];
                    
                    $dbLevelType = isset($levelTypeMapping[$levelType]) ? $levelTypeMapping[$levelType] : null;
                    
                    if ($dbLevelType) {
                        // Level aktualisieren
                        $this->updatePlayerLevel($playerId, $dbLevelType, $levelValue);
                        $stats['levels_updated']++;
                        
                        // Aktivität hinzufügen
                        $this->addPlayerActivity($playerId, "level_change_{$dbLevelType}", null, $levelValue);
                        $stats['activities_added']++;
                    }
                }
            }
            
            // Fahrzeug betreten erkennen
            elseif (strpos($entry, 'Player entered vehicle') !== false) {
                preg_match('/Player entered vehicle\. Player=([^\(]+) \(([0-9]+)\) Vehicle=([^(]+)\(([0-9]+)\)/', $entry, $matches);
                if (count($matches) === 5) {
                    $playerName = trim($matches[1]);
                    $steamId = $matches[2];
                    $vehicleName = trim($matches[3]);
                    $vehicleId = $matches[4];
                    
                    // Spieler finden oder erstellen
                    $player = $this->getPlayerBySteamId($steamId);
                    if (!$player) {
                        $playerId = $this->addOrUpdatePlayer($playerName, $steamId);
                        $stats['players_added']++;
                    } else {
                        $playerId = $player['id'];
                    }
                    
                    // Aktivität hinzufügen
                    $this->addPlayerActivity($playerId, 'entered_vehicle', $vehicleName, $vehicleId);
                    $stats['activities_added']++;
                }
            }
            
            // Fahrzeug verlassen erkennen
            elseif (strpos($entry, 'Player exited vehicle') !== false) {
                preg_match('/Player exited vehicle\. Player=([^\(]+) \(([0-9]+)\) Vehicle=([^(]+)\(([0-9]+)\)/', $entry, $matches);
                if (count($matches) === 5) {
                    $playerName = trim($matches[1]);
                    $steamId = $matches[2];
                    $vehicleName = trim($matches[3]);
                    $vehicleId = $matches[4];
                    
                    // Spieler finden oder erstellen
                    $player = $this->getPlayerBySteamId($steamId);
                    if (!$player) {
                        $playerId = $this->addOrUpdatePlayer($playerName, $steamId);
                        $stats['players_added']++;
                    } else {
                        $playerId = $player['id'];
                    }
                    
                    // Aktivität hinzufügen
                    $this->addPlayerActivity($playerId, 'exited_vehicle', $vehicleName, $vehicleId);
                    $stats['activities_added']++;
                }
            }
            
            // Unternehmen hinzugefügt erkennen
            elseif (strpos($entry, 'Company added') !== false) {
                preg_match('/Company added\. Name=([^(]+)\(Corp\?([^\)]+)\) Owner=([^\(]+)\(([0-9]+)\)/', $entry, $matches);
                if (count($matches) === 5) {
                    $companyName = trim($matches[1]);
                    $isCorp = trim($matches[2]) === 'true';
                    $ownerName = trim($matches[3]);
                    $steamId = $matches[4];
                    
                    // Spieler finden oder erstellen
                    $player = $this->getPlayerBySteamId($steamId);
                    if (!$player) {
                        $playerId = $this->addOrUpdatePlayer($ownerName, $steamId);
                        $stats['players_added']++;
                    } else {
                        $playerId = $player['id'];
                    }
                    
                    // Aktivität hinzufügen
                    $this->addPlayerActivity($playerId, 'company_added', $companyName, $isCorp ? 'corporation' : 'personal');
                    $stats['activities_added']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Liest Server-Logs aus einer Datei und verarbeitet sie
     * 
     * @param string $logFile Pfad zur Log-Datei
     * @return array          Statistik über verarbeitete Einträge
     */
    public function processServerLogFile($logFile) {
        if (!file_exists($logFile)) {
            return ['error' => 'Log-Datei nicht gefunden'];
        }
        
        $logEntries = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $this->processServerLogs($logEntries);
    }
}
