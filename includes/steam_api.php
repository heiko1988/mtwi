<?php
/**
 * Motor Town Web Interface (MTWI) - Steam API Integration
 */

class SteamAPI {
    private $api_key;
    private $cache_duration = 86400; // 24 Stunden Cache-Dauer in Sekunden
    private $db;
    
    /**
     * Konstruktor
     * 
     * @param PDO $db Datenbank-Verbindung
     * @param string $api_key Steam Web API Key
     */
    public function __construct($db, $api_key = '') {
        $this->db = $db;
        $this->api_key = $api_key;
        $this->initializeTable();
    }
    
    /**
     * Tabelle für Steam-Profildaten initialisieren
     */
    private function initializeTable() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS steam_profiles (
            steam_id TEXT PRIMARY KEY,
            profile_name TEXT,
            avatar_url TEXT,
            last_updated INTEGER,
            is_public INTEGER DEFAULT 1
        )");
    }
    
    /**
     * Daten eines Steam-Profils abrufen (aus Cache oder Steam API)
     * 
     * @param string $steamId SteamID des Spielers
     * @param bool $forceRefresh Erzwinge Aktualisierung der Daten
     * @return array|null Array mit Profildaten oder null bei Fehler
     */
    public function getPlayerProfile($steamId, $forceRefresh = false) {
        // Versuche zuerst aus dem Cache zu laden
        if (!$forceRefresh) {
            $cachedData = $this->getFromCache($steamId);
            if ($cachedData) {
                return $cachedData;
            }
        }
        
        // Wenn kein API-Key konfiguriert ist, Standardwerte zurückgeben
        if (empty($this->api_key)) {
            return [
                'steam_id' => $steamId,
                'profile_name' => null,
                'avatar_url' => null,
                'is_public' => 1
            ];
        }
        
        // Von der Steam API abrufen
        return $this->fetchFromSteamAPI($steamId);
    }
    
    /**
     * Profildaten aus dem Cache laden
     * 
     * @param string $steamId SteamID des Spielers
     * @return array|null Profildaten oder null, wenn nicht im Cache oder veraltet
     */
    private function getFromCache($steamId) {
        $stmt = $this->db->prepare("SELECT * FROM steam_profiles WHERE steam_id = :steam_id");
        $stmt->execute(['steam_id' => $steamId]);
        $data = $stmt->fetch();
        
        if ($data) {
            $now = time();
            // Prüfen, ob der Cache noch gültig ist
            if (($now - $data['last_updated']) < $this->cache_duration) {
                return $data;
            }
        }
        
        return null;
    }
    
    /**
     * Profildaten von der Steam API abrufen
     * 
     * @param string $steamId SteamID des Spielers
     * @return array|null Profildaten oder null bei Fehler
     */
    private function fetchFromSteamAPI($steamId) {
        $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key={$this->api_key}&steamids={$steamId}";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            // Fehler beim API-Aufruf - leere Daten im Cache speichern
            $profileData = [
                'steam_id' => $steamId,
                'profile_name' => null,
                'avatar_url' => null,
                'is_public' => 0,
                'last_updated' => time()
            ];
            $this->saveToCache($profileData);
            return $profileData;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['response']['players']) && !empty($data['response']['players'])) {
            $player = $data['response']['players'][0];
            
            $profileData = [
                'steam_id' => $steamId,
                'profile_name' => $player['personaname'] ?? null,
                'avatar_url' => $player['avatarmedium'] ?? null,
                'is_public' => 1,
                'last_updated' => time()
            ];
            
            // Im Cache speichern
            $this->saveToCache($profileData);
            
            return $profileData;
        }
        
        // Keine Spielerdaten gefunden
        $profileData = [
            'steam_id' => $steamId,
            'profile_name' => null,
            'avatar_url' => null,
            'is_public' => 0,
            'last_updated' => time()
        ];
        $this->saveToCache($profileData);
        return $profileData;
    }
    
    /**
     * Profildaten im Cache speichern
     * 
     * @param array $profileData Profildaten
     * @return bool Erfolg
     */
    private function saveToCache($profileData) {
        try {
            $stmt = $this->db->prepare("INSERT OR REPLACE INTO steam_profiles 
                (steam_id, profile_name, avatar_url, last_updated, is_public) 
                VALUES (:steam_id, :profile_name, :avatar_url, :last_updated, :is_public)");
                
            return $stmt->execute([
                'steam_id' => $profileData['steam_id'],
                'profile_name' => $profileData['profile_name'],
                'avatar_url' => $profileData['avatar_url'],
                'last_updated' => $profileData['last_updated'],
                'is_public' => $profileData['is_public']
            ]);
        } catch (PDOException $e) {
            error_log('Fehler beim Speichern des Steam-Profils: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mehrere Steam-Profile auf einmal abrufen
     * 
     * @param array $steamIds Array von Steam IDs
     * @return array Array mit Steam-Profilen
     */
    public function getMultipleProfiles($steamIds) {
        if (empty($steamIds)) {
            return [];
        }
        
        $profiles = [];
        
        // Zuerst alle vorhandenen Profile aus dem Cache laden
        $placeholders = implode(',', array_fill(0, count($steamIds), '?'));
        $stmt = $this->db->prepare("SELECT * FROM steam_profiles WHERE steam_id IN ($placeholders)");
        $stmt->execute($steamIds);
        $cachedProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Profile nach steam_id indexieren
        $cachedProfilesById = [];
        foreach ($cachedProfiles as $profile) {
            $cachedProfilesById[$profile['steam_id']] = $profile;
        }
        
        $now = time();
        $missingProfiles = [];
        
        // Für jede ID prüfen, ob sie im Cache und aktuell ist
        foreach ($steamIds as $steamId) {
            if (isset($cachedProfilesById[$steamId]) && 
                ($now - $cachedProfilesById[$steamId]['last_updated'] < $this->cache_duration)) {
                $profiles[$steamId] = $cachedProfilesById[$steamId];
            } else {
                $missingProfiles[] = $steamId;
            }
        }
        
        // Wenn API-Key fehlt, Standard-Profile für fehlende IDs erstellen
        if (empty($this->api_key) && !empty($missingProfiles)) {
            foreach ($missingProfiles as $steamId) {
                $profiles[$steamId] = [
                    'steam_id' => $steamId,
                    'profile_name' => null,
                    'avatar_url' => null,
                    'is_public' => 1,
                    'last_updated' => $now
                ];
            }
            return $profiles;
        }
        
        // Fehlende Profile über die API abrufen (bis zu 100 pro Anfrage)
        $chunkedIds = array_chunk($missingProfiles, 100);
        foreach ($chunkedIds as $chunk) {
            $steamIdsStr = implode(',', $chunk);
            $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key={$this->api_key}&steamids={$steamIdsStr}";
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                
                if (isset($data['response']['players'])) {
                    $players = $data['response']['players'];
                    
                    // Für jeden abgerufenen Spieler ein Profil erstellen und im Cache speichern
                    foreach ($players as $player) {
                        $steamId = $player['steamid'];
                        $profileData = [
                            'steam_id' => $steamId,
                            'profile_name' => $player['personaname'] ?? null,
                            'avatar_url' => $player['avatarmedium'] ?? null,
                            'is_public' => 1,
                            'last_updated' => $now
                        ];
                        
                        $this->saveToCache($profileData);
                        $profiles[$steamId] = $profileData;
                        
                        // Diese ID aus der Liste der fehlenden Profile entfernen
                        $missingProfiles = array_diff($missingProfiles, [$steamId]);
                    }
                }
            }
        }
        
        // Für alle verbleibenden IDs leere Profile erstellen
        foreach ($missingProfiles as $steamId) {
            $profileData = [
                'steam_id' => $steamId,
                'profile_name' => null,
                'avatar_url' => null,
                'is_public' => 0,
                'last_updated' => $now
            ];
            
            $this->saveToCache($profileData);
            $profiles[$steamId] = $profileData;
        }
        
        return $profiles;
    }
}
