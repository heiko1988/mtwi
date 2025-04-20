<?php
/**
 * Motor Town Web Interface (MTWI) - Datenbankklasse
 */

class Database {
    private $db;
    private $config;
    
    /**
     * Konstruktor
     * 
     * @param array $config Datenbank-Konfiguration
     */
    public function __construct($config) {
        $this->config = $config;
        $this->connect();
        $this->initialize();
    }
    
    /**
     * Verbindet zur Datenbank
     */
    private function connect() {
        try {
            if ($this->config['type'] == 'sqlite') {
                // Stellen Sie sicher, dass das Verzeichnis existiert
                $dir = dirname($this->config['file']);
                if (!is_dir($dir)) {
                    if (!@mkdir($dir, 0755, true)) {
                        throw new \Exception('Datenbankfehler: Das Verzeichnis <code>' . htmlspecialchars($dir) . '</code> konnte nicht erstellt werden!<br>'
                          . 'Bitte erstellen Sie das Verzeichnis manuell und geben Sie dem Webserver-Benutzer Schreibrechte.');
                    }
                }
                if (!is_writable($dir)) {
                    throw new \Exception('Datenbankfehler: Das Verzeichnis <code>' . htmlspecialchars($dir) . '</code> ist nicht beschreibbar!<br>'
                      . 'Bitte geben Sie dem Webserver-Benutzer Schreibrechte.');
                }
                
                $this->db = new PDO('sqlite:' . $this->config['file']);
            } else {
                // MySQL-Verbindung
                $dsn = 'mysql:host=' . $this->config['host'] . ';dbname=' . $this->config['name'] . ';charset=utf8mb4';
                $this->db = new PDO($dsn, $this->config['user'], $this->config['pass']);
            }
            
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            die('Datenbankfehler: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialisiert die Datenbank (erstellt Tabellen falls nötig)
     */
    private function initialize() {
        // Bans-Tabelle
        $this->db->exec("CREATE TABLE IF NOT EXISTS bans (
            id INTEGER PRIMARY KEY " . ($this->config['type'] == 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
            unique_id TEXT NOT NULL,
            player_name TEXT NOT NULL,
            reason TEXT,
            created_at INTEGER NOT NULL,
            duration INTEGER NOT NULL,
            expires_at INTEGER,
            is_active INTEGER DEFAULT 0,
            is_permanent INTEGER DEFAULT 0
        )");
        
        // Spielerhistorie-Tabelle
        $this->db->exec("CREATE TABLE IF NOT EXISTS player_history (
            id INTEGER PRIMARY KEY " . ($this->config['type'] == 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
            unique_id TEXT NOT NULL,
            player_name TEXT NOT NULL,
            first_seen INTEGER NOT NULL,
            last_seen INTEGER NOT NULL,
            last_action TEXT
        )");
        
        // Logbuch-Tabelle
        $this->db->exec("CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY " . ($this->config['type'] == 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
            timestamp INTEGER NOT NULL,
            type TEXT NOT NULL,
            message TEXT NOT NULL,
            details TEXT
        )");
        
        // Schnellnachrichten-Tabelle
        $this->db->exec("CREATE TABLE IF NOT EXISTS quick_messages (
            id INTEGER PRIMARY KEY " . ($this->config['type'] == 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
            message TEXT NOT NULL,
            created_at INTEGER NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0
        )");
        
        // Standardnachrichten hinzufügen, falls noch keine vorhanden sind
        $this->initializeDefaultQuickMessages();
    }
    
    /**
     * Fügt einen Ban hinzu
     * 
     * @param string $uniqueId     Steam-ID des Spielers
     * @param string $playerName   Name des Spielers
     * @param string $reason       Grund für den Ban
     * @param int    $duration     Dauer in Sekunden (0 = permanent)
     * @param bool   $isActive     Ist der Ban bereits aktiv?
     * @return int                 ID des neuen Bans
     */
    public function addBan($uniqueId, $playerName, $reason, $duration, $isActive = false) {
        $now = time();
        $isPermanent = ($duration === 0);
        $expiresAt = $isPermanent ? null : ($now + $duration);
        
        $stmt = $this->db->prepare("INSERT INTO bans 
            (unique_id, player_name, reason, created_at, duration, expires_at, is_active, is_permanent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([
            $uniqueId,
            $playerName,
            $reason,
            $now,
            $duration,
            $expiresAt,
            $isActive ? 1 : 0,
            $isPermanent ? 1 : 0
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Aktiviert einen ausstehenden Ban
     * 
     * @param int $banId ID des Bans
     * @return bool     Erfolg
     */
    public function activateBan($banId) {
        $stmt = $this->db->prepare("UPDATE bans SET is_active = 1 WHERE id = ?");
        return $stmt->execute([$banId]);
    }
    
    /**
     * Entfernt einen Ban
     * 
     * @param int $banId ID des Bans
     * @return bool     Erfolg
     */
    public function removeBan($banId) {
        $stmt = $this->db->prepare("DELETE FROM bans WHERE id = ?");
        return $stmt->execute([$banId]);
    }
    
    /**
     * Entfernt einen aktiven Ban anhand der unique_id
     * 
     * @param string $uniqueId Eindeutige ID des Spielers
     * @return bool            Erfolg
     */
    public function removeActiveBanByUniqueId($uniqueId) {
        $stmt = $this->db->prepare("DELETE FROM bans WHERE unique_id = ? AND is_active = 1");
        $success = $stmt->execute([$uniqueId]);
        
    
        return $success;
    }
    
    /**
     * Prüft, ob ein Spieler bereits gebannt ist
     * 
     * @param string $uniqueId Steam-ID des Spielers
     * @return bool           true, wenn gebannt, sonst false
     */
    public function isPlayerBanned($uniqueId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM bans 
            WHERE unique_id = ? AND (expires_at > ? OR is_permanent = 1)");
        $stmt->execute([$uniqueId, time()]);
        $result = $stmt->fetch();
        return ($result['count'] > 0);
    }
    
    /**
     * Holt ausstehende Bans (inaktive Bans)
     * 
     * @return array Liste der ausstehenden Bans
     */
    public function getPendingBans() {
        $stmt = $this->db->prepare("SELECT * FROM bans 
            WHERE is_active = 0 AND (expires_at > ? OR is_permanent = 1)");
        $stmt->execute([time()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Holt aktive Bans
     * 
     * @return array Liste der aktiven Bans
     */
    public function getActiveBans() {
        $stmt = $this->db->prepare("SELECT * FROM bans 
            WHERE is_active = 1 AND (expires_at > ? OR is_permanent = 1)");
        $stmt->execute([time()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Gibt abgelaufene Bans zurück
     * 
     * @return array Liste der abgelaufenen Bans
     */
    public function getExpiredBans() {
        $stmt = $this->db->prepare("SELECT * FROM bans 
                               WHERE is_active = 1 
                               AND duration > 0 
                               AND expires_at < ?");
        $stmt->execute([time()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Aktiviert einen ausstehenden Ban
     * 
     * @param int $banId ID des Bans
     * @return bool true bei Erfolg, false bei Fehler
     */
    public function activatePendingBan($banId) {
        $stmt = $this->db->prepare("SELECT * FROM bans WHERE id = ? AND is_active = 0");
        $stmt->execute([$banId]);
        $pendingBan = $stmt->fetch();
        
        if ($pendingBan) {
            // Hole aktuelle Server-Daten für den Ban
            $serverBan = $this->getBanFromServer($pendingBan['unique_id']);
            
            // Lösche den alten ausstehenden Ban
            $this->db->prepare("DELETE FROM bans WHERE id = ?")->execute([$banId]);
            
            // Erstelle neuen aktiven Ban mit Server-Daten
            $id = $this->addBan(
                $pendingBan['unique_id'],
                $serverBan['player_name'] ?? $pendingBan['player_name'],
                $serverBan['reason'] ?? $pendingBan['reason'],
                $serverBan['duration'] ?? $pendingBan['duration'],
                true
            );
            
            return $id !== false;
        }
        
        return false;
    }
    
    /**
     * Entfernt abgelaufene Bans
     * 
     * @return int Anzahl der entfernten Bans
     */
    public function removeExpiredBans() {
        $stmt = $this->db->prepare("DELETE FROM bans 
            WHERE is_permanent = 0 AND expires_at < ?");
        $stmt->execute([time()]);
        return $stmt->rowCount();
    }
    
    /**
     * Holt einen Ban vom Server
     * 
     * @param string $uniqueId Unique ID des Spielers
     * @return array|false Server-Ban-Daten oder false bei Fehler
     */
    private function getBanFromServer($uniqueId) {
        global $apiClient;
        if (!$apiClient) {
            return false;
        }
        
        $banList = $apiClient->getBanList();
        if ($banList === false) {
            return false;
        }
        
        foreach ($banList as $ban) {
            if ($ban['unique_id'] == $uniqueId) {
                return $ban;
            }
        }
        
        return false;
    }

    /**
     * Holt einen ausstehenden Ban anhand der Unique ID
     * 
     * @param string $uniqueId Unique ID des Spielers
     * @return array|false Ban-Daten oder false bei Fehler
     */
    public function getPendingBanByUniqueId($uniqueId) {
        $stmt = $this->db->prepare("SELECT * FROM bans WHERE unique_id = ? AND is_active = 0");
        $stmt->execute([$uniqueId]);
        return $stmt->fetch();
    }
    
    /**
     * Aktualisiert die Spielerhistorie
     * 
     * @param array $playerList Liste der aktuellen Spieler
     */
    public function updatePlayerHistory($playerList) {
        $now = time();
        
        // Alle aktuellen Spieler als "zuletzt gesehen" markieren
        foreach ($playerList as $player) {
            $stmt = $this->db->prepare("SELECT * FROM player_history WHERE unique_id = ?");
            $stmt->execute([$player['unique_id']]);
            $existingPlayer = $stmt->fetch();
            
            if ($existingPlayer) {
                // Spieler existiert bereits, Update
                $stmt = $this->db->prepare("UPDATE player_history SET 
                    player_name = ?, last_seen = ? WHERE unique_id = ?");
                $stmt->execute([$player['name'], $now, $player['unique_id']]);
            } else {
                // Neuer Spieler, Insert
                $stmt = $this->db->prepare("INSERT INTO player_history 
                    (unique_id, player_name, first_seen, last_seen) VALUES (?, ?, ?, ?)");
                $stmt->execute([$player['unique_id'], $player['name'], $now, $now]);
            }
        }
    }
    
    /**
     * Holt die letzten N zuletzt gesehenen Spieler (ohne Duplikate)
     * 
     * @param int $limit Maximale Anzahl zurückzugebender Spieler
     * @return array     Liste der Spieler
     */
    public function getRecentlyOfflinePlayers($limit = 10) {
        // Vereinfachte Abfrage: Hole direkt die Spieler mit last_action='offline'
        // Sortiere nach letztem Sichtungszeitpunkt (absteigend)
        
        $stmt = $this->db->prepare("SELECT * FROM player_history 
            WHERE last_action = 'offline'
            GROUP BY unique_id 
            ORDER BY last_seen DESC LIMIT ?");
            
        $stmt->execute([$limit]);
        $result = $stmt->fetchAll();
        

        return $result;
    }
    
    /**
     * Holt die IDs der aktiven Spieler
     * 
     * @param bool $idsOnly Wenn true, werden nur die unique_ids zurückgegeben
     * @return array        Liste der aktiven Spieler oder deren IDs
     */
    public function getActivePlayers($idsOnly = false) {
        $activeIds = [];
        $stmt = $this->db->prepare("SELECT * FROM player_history WHERE last_action = 'online'");
        $stmt->execute();
        $activePlayers = $stmt->fetchAll();
        
        if ($idsOnly) {
            foreach ($activePlayers as $player) {
                $activeIds[] = $player['unique_id'];
            }
            return $activeIds;
        }
        
        return $activePlayers;
    }
    
    /**
     * Holt einen Spieler anhand seiner eindeutigen ID
     * 
     * @param string $uniqueId  Eindeutige ID des Spielers (z.B. Steam ID)
     * @return array|false      Spielerdaten oder false, wenn nicht gefunden
     */
    public function getPlayerByUniqueId($uniqueId) {
        $stmt = $this->db->prepare("SELECT * FROM player_history 
            WHERE unique_id = ? ORDER BY last_seen DESC LIMIT 1");
        $stmt->execute([$uniqueId]);
        return $stmt->fetch();
    }
    
    /**
     * Aktualisiert den Status eines Spielers in der Historie
     * 
     * @param string $uniqueId     Eindeutige ID des Spielers
     * @param string $playerName   Name des Spielers
     * @param int $lastSeen        Zeitpunkt, wann der Spieler zuletzt gesehen wurde
     * @param string $lastAction   Letzte Aktion des Spielers (online, offline, etc.)
     * @return bool                true bei Erfolg, false bei Fehler
     */
    public function updatePlayerStatus($uniqueId, $playerName, $lastSeen, $lastAction) {
        $stmt = $this->db->prepare("UPDATE player_history 
            SET player_name = ?, last_seen = ?, last_action = ? 
            WHERE unique_id = ?");
        return $stmt->execute([$playerName, $lastSeen, $lastAction, $uniqueId]);
    }
    
    /**
     * Fügt einen neuen Spieler zur Historie hinzu
     * 
     * @param string $uniqueId     Eindeutige ID des Spielers
     * @param string $playerName   Name des Spielers
     * @param int $firstSeen       Zeitpunkt, wann der Spieler zuerst gesehen wurde
     * @param int $lastSeen        Zeitpunkt, wann der Spieler zuletzt gesehen wurde
     * @param string $lastAction   Letzte Aktion des Spielers
     * @return int                 ID des neuen Eintrags oder false bei Fehler
     */
    public function addPlayerHistory($uniqueId, $playerName, $firstSeen, $lastSeen, $lastAction) {
        $stmt = $this->db->prepare("INSERT INTO player_history 
            (unique_id, player_name, first_seen, last_seen, last_action) 
            VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$uniqueId, $playerName, $firstSeen, $lastSeen, $lastAction]);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Markiert Spieler als offline, die nicht mehr in der aktiven Spielerliste sind
     * 
     * @param array $playerList Liste der aktuellen Spieler
     * @return int Anzahl der markierten Spieler
     */
    public function markOfflinePlayers($playerList) {
        // Aktive Spieler-IDs sammeln
        $activeIds = [];
        foreach ($playerList as $player) {
            if (isset($player['unique_id'])) {
                $activeIds[] = $player['unique_id'];
            }
        }
        // Alle aktuell als online markierten Spieler abrufen
        $stmt = $this->db->prepare("SELECT unique_id FROM player_history WHERE last_action = 'online'");
        $stmt->execute();
        $onlinePlayers = $stmt->fetchAll();
        // Offline-Kandidaten identifizieren
        $offlineCandidates = [];
        foreach ($onlinePlayers as $player) {
            if (!in_array($player['unique_id'], $activeIds)) {
                $offlineCandidates[] = $player['unique_id'];
            }
        }
        if (!empty($offlineCandidates)) {
            $placeholdersOffline = implode(',', array_fill(0, count($offlineCandidates), '?'));
            $stmt = $this->db->prepare("UPDATE player_history SET last_action = 'offline', last_seen = ? WHERE unique_id IN ($placeholdersOffline)");
            $params = array_merge([time()], $offlineCandidates);
            $stmt->execute($params);
            $updatedCount = $stmt->rowCount();
        } else {
            $updatedCount = 0;
        }
        return $updatedCount;
    }
    
    /**
     * Fügt einen Logeintrag hinzu
     * 
     * @param string $type    Typ des Eintrags (info, warning, error)
     * @param string $message Nachricht
     * @param string $details Weitere Details (optional)
     */
    public function addLog($type, $message, $details = null) {
        $stmt = $this->db->prepare("INSERT INTO logs 
            (timestamp, type, message, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([time(), $type, $message, $details]);
    }
    
    /**
     * Fügt Standardnachrichten zur quick_messages-Tabelle hinzu, falls diese leer ist
     */
    private function initializeDefaultQuickMessages() {
        $count = $this->db->query("SELECT COUNT(*) as count FROM quick_messages")->fetch()['count'];
        
        if ($count == 0) {
            // Standard-Schnellnachrichten einfügen
            $defaultMessages = [
                't:quick_message_1', // Verwenden des Sprachschlüssels mit t: Präfix
                't:quick_message_2',
                't:quick_message_3',
                't:quick_message_4'
            ];
            
            $stmt = $this->db->prepare("INSERT INTO quick_messages 
                (message, created_at, sort_order) VALUES (?, ?, ?)");
                
            foreach ($defaultMessages as $index => $message) {
                $stmt->execute([$message, time(), $index]);
            }
        }
    }
    
    /**
     * Holt alle Quick Messages
     * 
     * @return array Liste der Quick Messages
     */
    public function getQuickMessages() {
        $stmt = $this->db->prepare("SELECT * FROM quick_messages ORDER BY sort_order ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Fügt eine neue Quick Message hinzu
     * 
     * @param string $message    Der Nachrichtentext
     * @return int               ID der neuen Quick Message
     */
    public function addQuickMessage($message) {
        // Höchste sort_order ermitteln
        $maxOrder = $this->db->query("SELECT MAX(sort_order) as max_order FROM quick_messages")->fetch()['max_order'];
        $nextOrder = ($maxOrder === null) ? 0 : $maxOrder + 1;
        
        $stmt = $this->db->prepare("INSERT INTO quick_messages 
            (message, created_at, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$message, time(), $nextOrder]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Aktualisiert eine Quick Message
     * 
     * @param int    $id         ID der Quick Message
     * @param string $message    Neuer Nachrichtentext
     * @return bool              Erfolg
     */
    public function updateQuickMessage($id, $message) {
        $stmt = $this->db->prepare("UPDATE quick_messages SET message = ? WHERE id = ?");
        $stmt->execute([$message, $id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Löscht eine Quick Message
     * 
     * @param int $id    ID der Quick Message
     * @return bool      Erfolg
     */
    public function deleteQuickMessage($id) {
        $stmt = $this->db->prepare("DELETE FROM quick_messages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
