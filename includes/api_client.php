<?php
/**
 * Motor Town Web Interface (MTWI) - API Client
 */

class ApiClient {
    private $baseUrl;
    private $password;
    private $lastError = '';
    
    /**
     * Konstruktor
     * 
     * @param string $baseUrl  API-Basis-URL (z.B. http://example.com:9999)
     * @param string $password API-Passwort
     */
    public function __construct($baseUrl, $password) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->password = $password;
    }
    
    /**
     * Gibt den letzten Fehler zurück
     * 
     * @return string Fehlermeldung
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Sendet eine GET-Anfrage an die API
     * 
     * @param string $endpoint API-Endpunkt (z.B. "player/count")
     * @param array $params    Zusätzliche Parameter (optional)
     * @return mixed           Antwort als Array oder false bei Fehler
     */
    public function get($endpoint, $params = []) {
        // Passwort zu den Parametern hinzufügen
        $params['password'] = $this->password;
        
        // URL formatieren (wichtig: Slash vor Parametern)
        $url = $this->baseUrl . '/' . $endpoint . '/?' . http_build_query($params);
        
        // Anfrage senden
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            $this->lastError = 'Curl error: ' . curl_error($curl);
            curl_close($curl);
            return false;
        }
        
        curl_close($curl);
        
        // Überprüfen des HTTP-Statuscodes
        if ($httpCode != 200) {
            $this->lastError = "HTTP-Fehler: $httpCode";
            return false;
        }
        
        // Antwort dekodieren
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'JSON-Dekodierungsfehler: ' . json_last_error_msg();
            return false;
        }
        
        // Erfolg überprüfen
        if (!isset($data['succeeded']) || !$data['succeeded']) {
            $this->lastError = isset($data['message']) ? $data['message'] : 'Unbekannter API-Fehler';
            return false;
        }
        
        // Daten zurückgeben
        return isset($data['data']) ? $data['data'] : [];
    }
    
    /**
     * Sendet eine POST-Anfrage an die API
     * 
     * @param string $endpoint API-Endpunkt (z.B. "player/kick")
     * @param array $params    Parameter für die Anfrage
     * @param bool $useRawUrlEncoding Ob rawurlencode für die Parameter verwendet werden soll
     * @return mixed           Antwort als Array oder false bei Fehler
     */
    public function post($endpoint, $params = [], $useRawUrlEncoding = false) {
        // Passwort zu den Parametern hinzufügen
        $params['password'] = $this->password;
        
        // URL formatieren (wichtig: Slash vor Parametern)
        if ($useRawUrlEncoding) {
            // Bei Verwendung von rawurlencode manuell die URL zusammenbauen
            $queryParts = [];
            foreach ($params as $key => $value) {
                $queryParts[] = urlencode($key) . '=' . rawurlencode($value);
            }
            $url = $this->baseUrl . '/' . $endpoint . '/?' . implode('&', $queryParts);
        } else {
            // Standard URL-Formatierung
            $url = $this->baseUrl . '/' . $endpoint . '/?' . http_build_query($params);
        }
        
        // Anfrage senden
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, ''); // Leerer Body, aber notwendig!
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Length: 0']); // Wichtig, sonst 411-Fehler
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            $this->lastError = 'Curl error: ' . curl_error($curl);
            curl_close($curl);
            return false;
        }
        
        curl_close($curl);
        
        // Überprüfen des HTTP-Statuscodes
        if ($httpCode != 200) {
            $this->lastError = "HTTP-Fehler: $httpCode";
            return false;
        }
        
        // Antwort dekodieren
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'JSON-Dekodierungsfehler: ' . json_last_error_msg();
            return false;
        }
        
        // Erfolg überprüfen
        if (!isset($data['succeeded']) || !$data['succeeded']) {
            $this->lastError = isset($data['message']) ? $data['message'] : 'Unbekannter API-Fehler';
            return false;
        }
        
        // Daten zurückgeben
        return isset($data['data']) ? $data['data'] : [];
    }
    
    /**
     * Abrufen der Spieleranzahl
     * 
     * @return array|false Array mit num_players oder false bei Fehler
     */
    public function getPlayerCount() {
        return $this->get('player/count');
    }
    
    /**
     * Abrufen der Spielerliste
     * 
     * @return array|false Array mit Spielern oder false bei Fehler
     */
    public function getPlayerList() {
        return $this->get('player/list');
    }
    
    /**
     * Kicken eines Spielers
     * 
     * @param string $uniqueId Steam-ID des Spielers
     * @return bool            true bei Erfolg, false bei Fehler
     */
    public function kickPlayer($uniqueId) {
        $result = $this->post('player/kick', ['unique_id' => $uniqueId]);
        return ($result !== false);
    }
    
    /**
     * Bannen eines Spielers
     * 
     * @param string $uniqueId Steam-ID des Spielers
     * @return bool            true bei Erfolg, false bei Fehler
     */
    public function banPlayer($uniqueId) {
        $result = $this->post('player/ban', ['unique_id' => $uniqueId]);
        return ($result !== false);
    }
    
    /**
     * Abrufen der Ban-Liste
     * 
     * @return array|false Array mit gebannten Spielern oder false bei Fehler
     */
    public function getBanList() {
        return $this->get('player/banlist');
    }
    
    /**
     * Entbannen eines Spielers
     * 
     * @param string $uniqueId Eindeutige ID des Spielers
     * @return bool            true bei Erfolg, false bei Fehler
     */
    public function unbanPlayer($uniqueId) {
        // Debugging aktivieren
        $debugLog = '/var/www/html/nextcloud/mtwi/data/unban_api_debug.log';
        file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Versuche Spieler zu entbannen: $uniqueId\n", FILE_APPEND);
        
        // Die API erwartet den Parameter als 'unique_id'
        // Wichtig: Format muss genau stimmen (URL-Parameter, nicht im Body)
        $result = $this->post('player/unban', ['unique_id' => $uniqueId]);
        
        // Debug-Ausgabe
        $status = ($result !== false) ? 'Erfolgreich' : 'Fehlgeschlagen: ' . $this->lastError;
        file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Unban Status: $status\n", FILE_APPEND);
        
        return ($result !== false);
    }
    
    /**
     * Senden einer Chat-Nachricht
     * 
     * @param string $message  Zu sendende Nachricht
     * @return bool            true bei Erfolg, false bei Fehler
     */
    public function sendChatMessage($message) {
        // rawurlencode verwenden, damit Leerzeichen als %20 und nicht als + kodiert werden
        $result = $this->post('chat', ['message' => $message], true);
        return ($result !== false);
    }
}
