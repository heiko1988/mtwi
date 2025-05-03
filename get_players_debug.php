<?php
/**
 * Motor Town Web Interface (MTWI) - Spielerdaten-API mit Debug-Zeitmessungen
 * 
 * Diese API-Datei ruft Spielerdaten vom Chat-Server ab und gibt sie als JSON zurück.
 * Bei Fehlern wird auf statische Testdaten zurückgegriffen.
 * 
 * Erweiterungen:
 * - Speichert und verwaltet Unternehmensinformationen in der lokalen Datenbank
 * - Stellt sicher, dass Offline-Spieler ihre Unternehmensinformationen behalten
 */

// Fehlerbehandlung für Debug aktivieren
error_reporting(E_ALL); // Debug-Modus
ini_set('display_errors', 1);

// DEBUG: Startzeit festhalten
$start_time = microtime(true);
$debug_times = [];
$debug_times['start'] = $start_time;

// Setze HTTP-Status explizit auf 200 OK
http_response_code(200);

// Content-Type setzen
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Protokollierung in Datei
function logMessage($message) {
    $logFile = __DIR__ . '/data/players_api.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// DEBUG: Zeit messen
function debugTime($label) {
    global $debug_times, $start_time;
    $now = microtime(true);
    $elapsed = $now - $start_time;
    $debug_times[$label] = $elapsed;
    logMessage("DEBUG_TIME: $label - " . number_format($elapsed, 4) . " Sekunden");
}

logMessage('API-Aufruf gestartet');
debugTime('after_log_start');

// Basis-Konfiguration und Datenbank-Klassen laden
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/database_players.php';
debugTime('after_includes');

// Funktion zum Abrufen der Spielerdaten vom Chat-Server
function getPlayersFromChatServer() {
    global $config;
    logMessage('Versuche Daten vom Chat-Server zu laden');
    debugTime('before_chat_server_request');
    
    try {
        // Chat-Server-Konfiguration laden
        if (!isset($config['chat_server'])) {
            logMessage('FEHLER: Keine Chat-Server-Konfiguration gefunden');
            debugTime('chat_server_config_error');
            return null;
        }
        
        // Chat-Server-Daten aus der Konfiguration verwenden
        $chatConfig = $config['chat_server'];
        $url = rtrim($chatConfig['url'], '/');
        $port = $chatConfig['port'];
        $username = $chatConfig['username'];
        $password = $chatConfig['password']; // Jetzt korrekt 'admin123'
        
        // API-URL für Spielerdaten
        $apiUrl = $url . ':' . $port . '/api/players';
        logMessage('API-URL: ' . $apiUrl);
        
        // HTTP-Kontext mit Basic-Auth
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . base64_encode($username . ':' . $password),
                'timeout' => 5  // Timeout in Sekunden
            ]
        ];
        
        $context = stream_context_create($opts);
        // Fehler protokollieren
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            logMessage("PHP-Fehler ($errno): $errstr in $errfile:$errline");
        });
        
        // HTTP-Antwortheader abrufen
        $http_response_header = null; // Wird von file_get_contents automatisch gesetzt
        
        // DEBUG: Messung direkt vor API-Aufruf
        debugTime('before_api_call');
        $result = @file_get_contents($apiUrl, false, $context);
        // DEBUG: Messung direkt nach API-Aufruf
        debugTime('after_api_call');
        
        restore_error_handler();
        
        if ($result === FALSE) {
            $error = error_get_last();
            logMessage('FEHLER: Keine Verbindung zum Chat-Server möglich');
            
            if ($error) {
                logMessage('PHP-Fehler: ' . $error['message']);
            }
            
            // HTTP-Header analysieren für detailliertere Fehlerbehandlung
            if (!empty($http_response_header)) {
                logMessage('HTTP-Antwort: ' . implode(', ', $http_response_header));
                
                // Prüfen auf 401-Fehler
                foreach ($http_response_header as $header) {
                    if (stripos($header, '401 Unauthorized') !== false) {
                        logMessage('Authentifizierungsfehler: Benutzeranmeldedaten wurden abgelehnt');
                        
                        // Auth-Header anzeigen (ohne Passwort)
                        $opts = stream_context_get_options($context);
                        if (isset($opts['http']) && isset($opts['http']['header'])) {
                            logMessage('Auth-Header (gesendet): ' . preg_replace('/Basic \S+/', 'Basic ***', $opts['http']['header']));
                        }
                    }
                }
            }
            
            debugTime('api_call_failed');
            return null;
        }
        
        // Versuche, die Antwort als JSON zu parsen
        $data = json_decode($result, true);
        
        // Prüfen, ob die Antwort erfolgreich geparst wurde
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            logMessage('FEHLER: JSON-Parsing-Fehler: ' . json_last_error_msg());
            logMessage('Rohdaten: ' . substr($result, 0, 255) . '...');
            debugTime('json_parse_error');
            return null;
        }
        
        // Formatierung und Spielerlisten-Extraktion
        if (isset($data['players']) && is_array($data['players'])) {
            logMessage('Spielerdaten erfolgreich geladen: ' . count($data['players']) . ' Spieler');
            debugTime('chat_server_success');
            return $data['players'];
        } else {
            logMessage('FEHLER: Unerwartetes Antwortformat vom Chat-Server');
            if (isset($data)) {
                logMessage('Antwortstruktur: ' . json_encode(array_keys($data)));
            }
            debugTime('unexpected_response_format');
            return null;
        }
        
    } catch (Exception $e) {
        logMessage('FEHLER: Exception beim Abrufen der Spielerdaten: ' . $e->getMessage());
        debugTime('chat_server_exception');
        return null;
    }
}

// Statische Testdaten als Fallback
function getStaticTestPlayers() {
    logMessage('Verwende statische Test-Spielerdaten');
    debugTime('static_test_data');
    
    return [
        [
            'name' => 'Spieler1',
            'steam_id' => '76561198123456789',
            'first_seen' => '12.03.2025 08:15:00',
            'last_seen' => '28.04.2025 20:30:45',
            'online' => true,
            'taxi_level' => 5,
            'bus_level' => 3,
            'wrecker_level' => 2,
            'police_level' => 4,
            'company' => 'Test Corp',
            'vehicles' => [
                [
                    'id' => '123',
                    'name' => 'Taxi #42',
                    'last_used' => '28.04.2025 20:28:12'
                ],
                [
                    'id' => '456',
                    'name' => 'Police Car #99',
                    'last_used' => '28.04.2025 19:15:00'
                ]
            ],
            'activities' => [
                [
                    'timestamp' => '28.04.2025 20:28:15',
                    'action' => 'vehicle_used',
                    'details' => 'Taxi #42'
                ],
                [
                    'timestamp' => '28.04.2025 20:30:45',
                    'action' => 'login',
                    'details' => null
                ]
            ]
        ],
        [
            'name' => 'Spieler2',
            'steam_id' => '76561198987654321',
            'first_seen' => '15.03.2025 10:30:00',
            'last_seen' => '28.04.2025 16:20:00',
            'online' => false,
            'taxi_level' => 2,
            'bus_level' => 7,
            'wrecker_level' => 1,
            'police_level' => 0,
            'company' => null,
            'vehicles' => [
                [
                    'id' => '789',
                    'name' => 'Bus #99',
                    'last_used' => '28.04.2025 16:15:00'
                ]
            ],
            'activities' => [
                [
                    'timestamp' => '28.04.2025 16:15:00',
                    'action' => 'vehicle_used',
                    'details' => 'Bus #99'
                ],
                [
                    'timestamp' => '28.04.2025 16:20:00',
                    'action' => 'logout',
                    'details' => null
                ]
            ]
        ]
    ];
}

// DEBUG: Zeit messen vor DB-Initialisierung
debugTime('before_db_init');

// Datenbank initialisieren
$dbPlayers = new DatabasePlayers($config['database']);

// DEBUG: Zeit messen nach DB-Initialisierung
debugTime('after_db_init');

// Versuche zuerst, Daten vom Chat-Server zu laden
$players = getPlayersFromChatServer();

// DEBUG: Zeit messen nach Chat-Server-Anfrage
debugTime('after_chat_server_request');

// Wenn keine Daten vom Chat-Server geladen werden konnten, verwende statische Testdaten
if ($players === null) {
    $players = getStaticTestPlayers();
    $message = 'Statische Testdaten geladen';
} else {
    $message = 'Echte Spielerdaten vom Server geladen';
}

// DEBUG: Zeit messen vor DB-Abgleich
debugTime('before_db_processing');

// Spielerdaten in der Datenbank speichern und ergänzen
$enrichedPlayers = [];

foreach ($players as $player) {
    // Prüfen, ob die erforderlichen Felder vorhanden sind
    $steamId = isset($player['steam_id']) ? $player['steam_id'] : null;
    $name = isset($player['name']) ? $player['name'] : 'Unbekannt';
    
    if (!$steamId) {
        logMessage("Spieler ohne Steam-ID übersprungen: " . json_encode($player));
        continue;
    }
    
    // Zeit-Konvertierungen
    $firstSeen = null;
    $lastSeen = null;
    
    if (isset($player['first_seen'])) {
        $firstSeen = strtotime($player['first_seen']) ?: null;
    }
    
    if (isset($player['last_seen'])) {
        $lastSeen = strtotime($player['last_seen']) ?: null;
    }
    
    // Unternehmensinformation
    $company = isset($player['company']) ? $player['company'] : null;
    
    // Berufs-Level erfassen
    $levels = [
        'taxi_level' => isset($player['taxi_level']) ? (int)$player['taxi_level'] : 0,
        'bus_level' => isset($player['bus_level']) ? (int)$player['bus_level'] : 0,
        'wrecker_level' => isset($player['wrecker_level']) ? (int)$player['wrecker_level'] : 0,
        'police_level' => isset($player['police_level']) ? (int)$player['police_level'] : 0,
        'driver_level' => isset($player['driver_level']) ? (int)$player['driver_level'] : 0,
        'truck_level' => isset($player['truck_level']) ? (int)$player['truck_level'] : 0
    ];
    
    // Daten in Datenbank speichern
    $result = $dbPlayers->addOrUpdatePlayer($name, $steamId, $firstSeen, $lastSeen, $company, $levels);
    logMessage("Spieler {$name} ({$steamId}) in Datenbank {$result}. Unternehmen: {$company}");
    
    // Falls der Spieler kein Unternehmen hat, aus der Datenbank abrufen
    if ($company === null || empty($company)) {
        // Spieler aus der Datenbank holen
        $stmt = $dbPlayers->getConnection()->prepare("SELECT company FROM players WHERE steam_id = ?");
        $stmt->execute([$steamId]);
        $dbPlayer = $stmt->fetch();
        
        if ($dbPlayer && $dbPlayer['company']) {
            $player['company'] = $dbPlayer['company'];
            logMessage("Unternehmen für {$name} aus Datenbank ergänzt: {$dbPlayer['company']}");
        }
    }
    
    $enrichedPlayers[] = $player;
}

// DEBUG: Zeit messen nach DB-Abgleich
debugTime('after_db_processing');

// Ergebnisstruktur erstellen
$response = [
    'success' => true,
    'message' => $message . ' und mit Datenbank abgeglichen',
    'players' => $enrichedPlayers,
    'debug_times' => $debug_times
];

// DEBUG: Zeit messen vor JSON-Ausgabe
debugTime('before_json_output');

// Daten als JSON ausgeben
logMessage('Sende JSON-Antwort');
echo json_encode($response);

// Ende des Scripts
logMessage('API-Aufruf beendet');
debugTime('end');
exit;
