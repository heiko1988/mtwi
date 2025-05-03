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
     * Initialisiert die Spielerdaten-Tabellen und optimiert die Datenbank
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
            racer_level INTEGER DEFAULT 0,
            company TEXT,
            UNIQUE(steam_id)
        )");
        
        // Überprüfen und ggf. hinzufügen der company-Spalte für bestehende Tabellen
        $this->addColumnIfNotExists('players', 'company', 'TEXT');
        $this->addColumnIfNotExists('players', 'driver_level', 'INTEGER DEFAULT 0');
        $this->addColumnIfNotExists('players', 'truck_level', 'INTEGER DEFAULT 0');
        $this->addColumnIfNotExists('players', 'racer_level', 'INTEGER DEFAULT 0'); // Neu: Racer-Level hinzufügen
        
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
        
        // Datenbankoptimierungen basierend auf dem Datenbanktyp
        if ($dbType === 'sqlite') {
            // Performance-Optimierung für SQLite
            $db->exec("PRAGMA journal_mode = WAL");
            $db->exec("PRAGMA synchronous = NORMAL"); 
            $db->exec("PRAGMA temp_store = MEMORY");
            $db->exec("PRAGMA cache_size = 10000");
            $db->exec("PRAGMA foreign_keys = ON");
            
            // Indizes erstellen für optimale Performance
            $db->exec("CREATE INDEX IF NOT EXISTS idx_players_steam_id ON players(steam_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_player_id ON player_activities(player_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_timestamp ON player_activities(timestamp)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_combined ON player_activities(player_id, timestamp)");
            
            // Tabellen analysieren und optimieren
            $db->exec("ANALYZE");
        } else {
            // Performance-Optimierungen für MySQL
            $db->exec("CREATE INDEX IF NOT EXISTS idx_players_steam_id ON players(steam_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_player_id ON player_activities(player_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_timestamp ON player_activities(timestamp)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_combined ON player_activities(player_id, timestamp)");
            
            // In MySQL gibt es kein PRAGMA, aber wir können die Tabellen optimieren
            $db->exec("OPTIMIZE TABLE players, player_activities");
        }
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
     * Aktualisiert mehrere Spieler in einem Batch
     * 
     * @param array $playersData Array mit Spielerdaten
     * @return array Statistik über erfolgreiche Updates
     */
    public function batchUpdatePlayers($playersData) {
        $db = $this->getConnection();
        $stats = ['updated' => 0, 'added' => 0];
        
        // Transaktion starten für bessere Performance
        $db->beginTransaction();
        
        try {
            // Prepared Statements vorbereiten (nur einmal)
            $selectStmt = $db->prepare("SELECT id, first_seen, company FROM players WHERE steam_id = ?");
            $updateStmt = null; // Wird später initialisiert, wenn benötigt
            $insertStmt = $db->prepare("INSERT INTO players (
                name, steam_id, first_seen, last_seen, 
                company, taxi_level, bus_level, wrecker_level, 
                police_level, driver_level, truck_level
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($playersData as $player) {
                // Standardwerte setzen, falls nicht vorhanden
                $now = time();
                $name = $player['name'] ?? 'Unbekannt';
                $steamId = $player['steam_id'] ?? null;
                $firstSeen = ($player['first_seen'] === null) ? $now : (int)$player['first_seen'];
                $lastSeen = ($player['last_seen'] === null) ? $now : (int)$player['last_seen'];
                $company = $player['company'] ?? null;
                
                // Levels standardisieren
                $levels = [
                    'taxi_level' => $player['taxi_level'] ?? 0,
                    'bus_level' => $player['bus_level'] ?? 0,
                    'wrecker_level' => $player['wrecker_level'] ?? 0,
                    'police_level' => $player['police_level'] ?? 0,
                    'driver_level' => $player['driver_level'] ?? 0,
                    'truck_level' => $player['truck_level'] ?? 0
                ];
                
                if (!$steamId) continue; // Spieler ohne Steam-ID überspringen
                
                // Prüfen, ob Spieler existiert
                $selectStmt->execute([$steamId]);
                $existingPlayer = $selectStmt->fetch();
                $selectStmt->closeCursor(); // Wichtig für SQLite
                
                if ($existingPlayer) {
                    // Wenn company null ist und der Spieler eine Firma hat, diese beibehalten
                    if ($company === null && $existingPlayer['company']) {
                        $company = $existingPlayer['company'];
                    }
                    
                    // Spieler aktualisieren
                    if ($updateStmt === null) {
                        // Update Statement erstellen (nur einmal)
                        $updateStmt = $db->prepare("UPDATE players SET 
                            name = ?, last_seen = ?, company = ?,
                            taxi_level = ?, bus_level = ?, wrecker_level = ?,
                            police_level = ?, driver_level = ?, truck_level = ?
                            WHERE id = ?");
                    }
                    
                    $updateStmt->execute([
                        $name, $lastSeen, $company,
                        $levels['taxi_level'], $levels['bus_level'], $levels['wrecker_level'],
                        $levels['police_level'], $levels['driver_level'], $levels['truck_level'],
                        $existingPlayer['id']
                    ]);
                    
                    $stats['updated']++;
                } else {
                    // Neuen Spieler hinzufügen
                    $insertStmt->execute([
                        $name, $steamId, $firstSeen, $lastSeen, $company,
                        $levels['taxi_level'], $levels['bus_level'], $levels['wrecker_level'],
                        $levels['police_level'], $levels['driver_level'], $levels['truck_level']
                    ]);
                    
                    $stats['added']++;
                }
            }
            
            $db->commit();
            return $stats;
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Fügt eine Spalte zu einer Tabelle hinzu, falls sie noch nicht existiert
     * 
     * @param string $table  Tabellenname
     * @param string $column Spaltenname
     * @param string $type   Datentyp der Spalte
     */
    private function addColumnIfNotExists($table, $column, $type) {
        $db = $this->getConnection();
        $dbType = $this->getDatabaseType();
        
        if ($dbType == 'sqlite') {
            // SQLite Implementierung
            $columns = $db->query("PRAGMA table_info(" . $table . ")");
            $exists = false;
            
            while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
                if ($col['name'] == $column) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $db->exec("ALTER TABLE " . $table . " ADD COLUMN " . $column . " " . $type);
                return true;
            }
        } else {
            // MySQL Implementierung
            $stmt = $db->prepare("SHOW COLUMNS FROM " . $table . " LIKE ?");
            $stmt->execute([$column]);
            
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE " . $table . " ADD COLUMN " . $column . " " . $type);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Aktualisiert den Level eines Spielers für einen bestimmten Beruf
     * 
     * @param int    $playerId   ID des Spielers
     * @param string $columnName Name der Spalte für den Beruf
     * @param int    $levelValue Neuer Level-Wert
     * @return bool              Erfolg
     */
    public function updatePlayerLevel($playerId, $columnName, $levelValue) {
        $db = $this->getConnection();
        
        // Nur erlaubte Spalten aktualisieren
        $allowedColumns = [
            'taxi_level', 'bus_level', 'wrecker_level', 
            'police_level', 'driver_level', 'truck_level'
        ];
        
        if (!in_array($columnName, $allowedColumns)) {
            return false;
        }
        
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
            // Implementiere hier die Log-Parsing-Logik
        }
        
        return $stats;
    }
}
