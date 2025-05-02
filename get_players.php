<?php
/**
 * Motor Town Web Interface (MTWI) - Spielerdaten-API
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

logMessage('API-Aufruf gestartet');

// Basis-Konfiguration und Datenbank-Klassen laden
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/database_players.php';

// Funktion zum Abrufen der Spielerdaten vom Chat-Server
function getPlayersFromChatServer() {
    global $config;
    logMessage('Versuche Daten vom Chat-Server zu laden');
    
    try {
        // Chat-Server-Konfiguration laden
        if (!isset($config['chat_server'])) {
            logMessage('FEHLER: Keine Chat-Server-Konfiguration gefunden');
            return null;
        }
        
        // Chat-Server-Daten aus der Konfiguration verwenden
        $chatConfig = $config['chat_server'];
        $url = rtrim($chatConfig['url'], '/');
        $port = $chatConfig['port'];
        $username = $chatConfig['username'];
        $password = $chatConfig['password'];
        
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
        
        $result = @file_get_contents($apiUrl, false, $context);
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
            
            return null;
        }
        
        // JSON dekodieren
        $data = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('FEHLER: Ungültiges JSON vom Chat-Server: ' . json_last_error_msg());
            return null;
        }
        
        // Prüfen, ob Spielerdaten vorhanden sind
        if (!isset($data['players']) || !is_array($data['players'])) {
            logMessage('FEHLER: Keine Spielerdaten im JSON gefunden');
            return null;
        }
        
        logMessage('Erfolgreich ' . count($data['players']) . ' Spieler vom Chat-Server geladen');
        return $data['players'];
    } catch (Exception $e) {
        logMessage('EXCEPTION: ' . $e->getMessage());
        return null;
    }
}

// Statische Testdaten als Fallback
function getStaticTestPlayers() {
    logMessage('Verwende statische Testdaten');
    
    return [
        [
            'id' => 1,
            'name' => 'TestSpieler',
            'steam_id' => '76561198123456789',
            'first_seen' => '01.04.2025 12:00:00',
            'last_seen' => '28.04.2025 18:45:00',
            'online' => true,
            'taxi_level' => 5,
            'bus_level' => 3,
            'wrecker_level' => 2,
            'police_level' => 1,
            'company' => 'Fast Taxi GmbH',
            'vehicles' => [
                [
                    'id' => '123',
                    'name' => 'Taxi #42',
                    'last_used' => '28.04.2025 18:30:00'
                ],
                [
                    'id' => '456',
                    'name' => 'Bus #15',
                    'last_used' => '27.04.2025 15:20:00'
                ]
            ],
            'activities' => [
                [
                    'timestamp' => '28.04.2025 18:30:00',
                    'action' => 'vehicle_used',
                    'details' => 'Taxi #42'
                ],
                [
                    'timestamp' => '28.04.2025 17:15:00',
                    'action' => 'login',
                    'details' => null
                ]
            ]
        ],
        [
            'id' => 2,
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

// Datenbank initialisieren
$dbPlayers = new DatabasePlayers($config['database']);

// Versuche zuerst, Daten vom Chat-Server zu laden
$players = getPlayersFromChatServer();

// Wenn keine Daten vom Chat-Server geladen werden konnten, verwende statische Testdaten
if ($players === null) {
    $players = getStaticTestPlayers();
    $message = 'Statische Testdaten geladen';
} else {
    $message = 'Echte Spielerdaten vom Server geladen';
}

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

// Ergebnisstruktur erstellen
$response = [
    'success' => true,
    'message' => $message . ' und mit Datenbank abgeglichen',
    'players' => $enrichedPlayers
];

// Daten als JSON ausgeben
logMessage('Sende JSON-Antwort');
echo json_encode($response);

// Ende des Scripts
logMessage('API-Aufruf beendet');
exit;
