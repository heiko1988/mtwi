<?php
/**
 * Motor Town Web Interface (MTWI) - AJAX-Handler
 */

// Initialisierung
require_once 'includes/init.php';

// Antwort-Funktion
function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    echo json_encode($response);
    exit;
}

// Prüfen, ob eine Aktion angegeben wurde
if (!isset($_POST['action'])) {
    sendResponse(false, 'Keine Aktion angegeben');
}

$action = $_POST['action'];

// CSRF-Token überprüfen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Für Testzwecke: Deaktiviere CSRF-Check temporär, wenn action=get_admins
    if ($action !== 'get_admins' && (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token']))) {
        sendResponse(false, 'Ungültiges oder fehlendes CSRF-Token');
    }
}

// Aktionen, die keine Anmeldung erfordern
$publicActions = ['login', 'setup_step'];

// Anmeldung prüfen, außer für öffentliche Aktionen
if (!in_array($action, $publicActions) && !isLoggedIn()) {
    sendResponse(false, 'Nicht angemeldet');
}

// Aktionen verarbeiten
switch ($action) {
    // Server Control (Start/Stop/Restart/Status)
    case 'server_control':
        $cmd = isset($_POST['command']) ? $_POST['command'] : '';
        $allowed = ['start','stop','restart','status'];
        if (!in_array($cmd, $allowed)) {
            sendResponse(false, 'Ungültiger Befehl!');
        }
        $chatConfig = $config['chat_server'];
        $url = rtrim($chatConfig['url'], '/');
        $port = $chatConfig['port'];
        $username = $chatConfig['username'];
        $password = $chatConfig['password'];
        $endpoint = $url . ':' . $port . '/server/' . $cmd;
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Authorization: Basic ' . base64_encode($username . ':' . $password) . "\r\nContent-Length: 0",
                'content' => ''
            ]
        ];
        $context = stream_context_create($opts);
        $result = @file_get_contents($endpoint, false, $context);
        if ($result === FALSE) {
            sendResponse(false, 'Server nicht erreichbar!');
        }
        $data = json_decode($result, true);
        if (!$data || !is_array($data)) {
            sendResponse(false, 'Ungültige Antwort vom Server!');
        }
        // Erwartet: { success: true/false, status: 'running'/'stopped'/..., message: '...' }
        sendResponse($data['success'] ?? false, $data['message'] ?? '', [ 'status' => $data['status'] ?? '', 'detail' => $data['detail'] ?? '' ]);
        break;
    // LIVE CHAT: Nachrichten abrufen
    case 'get_live_chat_messages':
        $chatConfig = $config['chat_server'];
        $url = rtrim($chatConfig['url'], '/');
        $port = $chatConfig['port'];
        $username = $chatConfig['username'];
        $password = $chatConfig['password'];
        $apiUrl = $url . ':' . $port . '/chatlog';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . base64_encode($username . ':' . $password)
            ]
        ];
        $context = stream_context_create($opts);
        $result = @file_get_contents($apiUrl, false, $context);
        if ($result === FALSE) {
            sendResponse(false, 'Chat-Server nicht erreichbar!');
        }
        $data = json_decode($result, true);
        if (!$data || !is_array($data)) {
            sendResponse(false, 'Ungültige Antwort vom Chat-Server!');
        }
        // Mappe die Felder auf das vom Frontend erwartete Format
        $mapped = array_map(function($item) {
            return [
                'author' => $item['user'] ?? '',
                'text' => $item['message'] ?? '',
                'timestamp' => $item['time'] ?? ''
            ];
        }, $data);
        sendResponse(true, 'Nachrichten geladen', $mapped);
        break;

    // LIVE CHAT: Nachricht senden
    case 'send_live_chat_message':
        // Prüfen, ob der Benutzer die Berechtigung hat, Nachrichten zu senden
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Senden von Chat-Nachrichten!');
        }
        
        // HIER: Endpunkt ggf. anpassen, falls /chatlog/send o.ä. existiert
        sendResponse(false, 'Nachricht senden ist noch nicht implementiert (API-Endpunkt fehlt)!');
        break;
    // Zeitgesteuerte Nachricht hinzufügen
    case 'add_scheduled_message':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Hinzufügen zeitgesteuerter Nachrichten!');
        }
        
        // Daten validieren
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $scheduleType = isset($_POST['schedule_type']) ? trim($_POST['schedule_type']) : '';
        $scheduleData = isset($_POST['schedule_data']) ? trim($_POST['schedule_data']) : '{}';
        $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
        
        if (empty($message) || empty($scheduleType)) {
            sendResponse(false, 'Nachricht und Zeitplantyp müssen angegeben werden!');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Nachricht speichern
        $result = $scheduledMessages->create($message, $scheduleType, $scheduleData, $active);
        
        if (!$result) {
            sendResponse(false, 'Fehler beim Speichern der zeitgesteuerten Nachricht');
        }
        
        // Aktualisierte Liste zurückgeben
        $all = $scheduledMessages->getAll();
        sendResponse(true, 'Zeitgesteuerte Nachricht erfolgreich hinzugefügt', ['scheduled_messages' => $all]);
        break;
        
    // Zeitgesteuerte Nachricht aktualisieren
    case 'update_scheduled_message':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Bearbeiten zeitgesteuerter Nachrichten!');
        }
        
        // Daten validieren
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $scheduleType = isset($_POST['schedule_type']) ? trim($_POST['schedule_type']) : '';
        $scheduleData = isset($_POST['schedule_data']) ? trim($_POST['schedule_data']) : '{}';
        $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
        
        if ($id <= 0 || empty($message) || empty($scheduleType)) {
            sendResponse(false, 'ID, Nachricht und Zeitplantyp müssen angegeben werden!');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Nachricht aktualisieren
        $result = $scheduledMessages->update($id, $message, $scheduleType, $scheduleData, $active);
        
        if (!$result) {
            sendResponse(false, 'Fehler beim Aktualisieren der zeitgesteuerten Nachricht');
        }
        
        // Aktualisierte Nachricht zurückgeben
        $updated = $scheduledMessages->getById($id);
        sendResponse(true, 'Zeitgesteuerte Nachricht erfolgreich aktualisiert', $updated);
        break;
        
    // Zeitgesteuerte Nachricht löschen
    case 'delete_scheduled_message':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Löschen zeitgesteuerter Nachrichten!');
        }
        
        // Daten validieren
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id <= 0) {
            sendResponse(false, 'ID muss angegeben werden!');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Nachricht löschen
        $result = $scheduledMessages->delete($id);
        
        if (!$result) {
            sendResponse(false, 'Fehler beim Löschen der zeitgesteuerten Nachricht');
        }
        
        sendResponse(true, 'Zeitgesteuerte Nachricht erfolgreich gelöscht');
        break;
        
    // Status einer zeitgesteuerten Nachricht umschalten (aktiv/inaktiv)
    case 'toggle_scheduled_message':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Ändern zeitgesteuerter Nachrichten!');
        }
        
        // Daten validieren
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
        
        if ($id <= 0) {
            sendResponse(false, 'ID muss angegeben werden!');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Status umschalten
        $result = $scheduledMessages->toggleActive($id, $active);
        
        if (!$result) {
            sendResponse(false, 'Fehler beim Ändern des Status der zeitgesteuerten Nachricht');
        }
        
        sendResponse(true, 'Status der zeitgesteuerten Nachricht erfolgreich geändert');
        break;
        
    // Alle zeitgesteuerten Nachrichten abrufen
    case 'get_scheduled_messages':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_view')) {
            sendResponse(false, 'Keine Berechtigung zum Anzeigen zeitgesteuerter Nachrichten!');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Alle Nachrichten abrufen
        $messages = $scheduledMessages->getAll();
        
        sendResponse(true, 'Zeitgesteuerte Nachrichten erfolgreich abgerufen', ['scheduled_messages' => $messages]);
        break;
        
    // Einzelne zeitgesteuerte Nachricht abrufen
    case 'get_scheduled_message':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Anzeigen zeitgesteuerter Nachrichten!');
        }
        
        // ID validieren
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if (empty($id)) {
            sendResponse(false, 'Keine gültige ID angegeben');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Nachricht abrufen
        $message = $scheduledMessages->getById($id);
        
        if (!$message) {
            sendResponse(false, 'Zeitgesteuerte Nachricht nicht gefunden');
        }
        
        sendResponse(true, 'Zeitgesteuerte Nachricht gefunden', ['message_data' => $message]);
        break;
        
    // Aktiv-Status einer zeitgesteuerten Nachricht umschalten
    case 'toggle_scheduled_message':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Verwalten zeitgesteuerter Nachrichten!');
        }
        
        // Daten validieren
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
        
        if (empty($id)) {
            sendResponse(false, 'Keine gültige ID angegeben');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Status umschalten
        $result = $scheduledMessages->toggleActive($id, $active);
        
        if (!$result) {
            sendResponse(false, 'Fehler beim Ändern des Aktiv-Status');
        }
        
        $statusText = $active ? 'aktiviert' : 'deaktiviert';
        sendResponse(true, "Zeitgesteuerte Nachricht wurde $statusText");
        break;
        
    // Zeitgesteuerte Nachricht löschen
    case 'delete_scheduled_message':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Löschen zeitgesteuerter Nachrichten!');
        }
        
        // ID validieren
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if (empty($id)) {
            sendResponse(false, 'Keine gültige ID angegeben');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Nachricht löschen
        $result = $scheduledMessages->delete($id);
        
        if (!$result) {
            sendResponse(false, 'Fehler beim Löschen der zeitgesteuerten Nachricht');
        }
        
        sendResponse(true, 'Zeitgesteuerte Nachricht erfolgreich gelöscht');
        break;
        
    // Cron-Status prüfen
    case 'check_cron_status':
        // Prüfen, ob der Benutzer die Berechtigung hat
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Prüfen des Cron-Status!');
        }
        
        // Zeitgesteuerte Nachrichten-Verarbeitung initialisieren
        require_once __DIR__ . '/includes/scheduled_messages.php';
        $scheduledMessages = new ScheduledMessages($db);
        
        // Letzten Cron-Log-Eintrag abrufen
        $lastLog = $scheduledMessages->getLastCronLog('send_scheduled_messages');
        
        if (!$lastLog) {
            sendResponse(false, 'Keine Cron-Logs gefunden, möglicherweise wurde der Cron-Job noch nie ausgeführt');
        }
        
        // Status prüfen
        $status = 'unknown';
        $message = '';
        $lastRun = null;
        $progressPercent = 0;
        
        if ($lastLog['status'] === 'running') {
            // Prüfen, ob der Job möglicherweise hängt (älter als 10 Minuten)
            $startTime = strtotime($lastLog['start_time']);
            $timeDiff = time() - $startTime;
            
            if ($timeDiff > 600) { // 10 Minuten
                $status = 'warning';
                $message = 'Der Cron-Job scheint hängen geblieben zu sein. Letzter Start vor ' . floor($timeDiff / 60) . ' Minuten.';
            } else {
                $status = 'running';
                $message = 'Der Cron-Job läuft derzeit. Gestartet vor ' . floor($timeDiff / 60) . ' Minuten.';
                
                // Fortschritt basierend auf der Zeit schätzen (Annahme: 5 Minuten Laufzeit)
                $progressPercent = min(95, ($timeDiff / 300) * 100);
            }
        } else if ($lastLog['status'] === 'completed') {
            $status = 'running';
            $message = 'Der Cron-Job wurde erfolgreich ausgeführt.';
            $lastRun = date('d.m.Y H:i:s', strtotime($lastLog['end_time']));
            $progressPercent = 100;
            
            // Prüfen, ob der letzte Lauf zu lange her ist (> 15 Minuten)
            $endTime = strtotime($lastLog['end_time']);
            $timeDiff = time() - $endTime;
            
            if ($timeDiff > 900) { // 15 Minuten
                $status = 'warning';
                $message = 'Der Cron-Job wurde zuletzt vor ' . floor($timeDiff / 60) . ' Minuten ausgeführt.';
            }
        } else if ($lastLog['status'] === 'error') {
            $status = 'stopped';
            $message = 'Der Cron-Job ist mit einem Fehler fehlgeschlagen: ' . $lastLog['message'];
            $lastRun = date('d.m.Y H:i:s', strtotime($lastLog['end_time']));
        }
        
        sendResponse(true, $message, [
            'cron_status' => $status,
            'last_run' => $lastRun,
            'progress_percent' => $progressPercent
        ]);
        break;
        
    // API-Einstellungen speichern
    case 'save_api_settings':
        // Prüfen, ob der Benutzer die Berechtigung hat, Einstellungen zu ändern
        if (!hasPermission('settings_edit')) {
            sendResponse(false, 'Keine Berechtigung zum Ändern der API-Einstellungen!');
        }
        
        // Daten validieren
        $url = isset($_POST['api_url']) ? trim($_POST['api_url']) : '';
        $password = isset($_POST['api_password']) ? trim($_POST['api_password']) : '';
        
        if (empty($url) || empty($password)) {
            sendResponse(false, 'URL und Passwort müssen ausgefüllt werden!');
        }
        
        // In Konfiguration speichern
        $config['api'] = [
            'url' => $url,
            'password' => $password
        ];
        if (!saveConfig($config)) {
            sendResponse(false, 'Fehler beim Speichern der Konfiguration');
        }
        sendResponse(true, 'API-Einstellungen erfolgreich gespeichert');
        break;
        
    // Chat-Server Einstellungen speichern
    case 'save_chat_server_settings':
        $username = isset($_POST['chat_server_username']) ? trim($_POST['chat_server_username']) : '';
        $url = isset($_POST['chat_server_url']) ? trim($_POST['chat_server_url']) : '';
        $port = isset($_POST['chat_server_port']) ? trim($_POST['chat_server_port']) : '';
        $password = isset($_POST['chat_server_password']) ? trim($_POST['chat_server_password']) : '';
        if (empty($username) || empty($url) || empty($port) || empty($password)) {
            sendResponse(false, 'Alle Felder müssen ausgefüllt werden!');
        }
        $config['chat_server'] = [
            'username' => $username,
            'url' => $url,
            'port' => $port,
            'password' => $password
        ];
        if (!saveConfig($config)) {
            sendResponse(false, 'Fehler beim Speichern der Konfiguration');
        }
        sendResponse(true, 'Chat-Server Einstellungen gespeichert');
        break;

    // API-Verbindung testen - Verbesserte Version mit ApiClient
    case 'test_api_connection':
        error_log('API-Verbindungstest gestartet mit POST-Daten: ' . print_r($_POST, true));
        
        $url = isset($_POST['url']) ? trim($_POST['url']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
        if (empty($url) || empty($password)) {
            error_log('API-Test: Fehlende Parameter');
            sendResponse(false, 'Bitte füllen Sie alle Felder aus');
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            error_log('API-Test: Ungültige URL');
            sendResponse(false, 'Die angegebene URL ist ungültig');
        }
        
        error_log('API-Test: Teste Verbindung zu ' . $url . ' mit ApiClient-Klasse');
        
        // ApiClient-Klasse einbinden (mit Fehlerprüfung)
        $api_client_path = __DIR__ . '/includes/api_client.php';
        if (!file_exists($api_client_path)) {
            error_log('API-Test Fehler: ApiClient-Datei nicht gefunden: ' . $api_client_path);
            sendResponse(false, 'Interner Fehler: ApiClient nicht gefunden');
            break;
        }
        
        require_once $api_client_path;
        
        // Direkte cURL-Implementierung mit korrektem API-Endpunkt-Format
        // Basierend auf der ApiClient-Klasse: player/count ist der korrekte Endpunkt!
        error_log('API-Test: Verwende direkte cURL Implementierung mit korrektem Endpunkt');
        
        $ch = curl_init();
        // ACHTUNG: Die Motor Town API erwartet den Endpunkt als "player/count" und das Passwort als GET-Parameter
        $testUrl = rtrim($url, '/') . '/player/count/?password=' . urlencode($password);
        
        // cURL-Optionen setzen
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 Sekunden Timeout
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // KEINE speziellen Header für die Motor Town API nötig,
        // da das Passwort bereits als GET-Parameter gesendet wird
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        // cURL ausführen
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMsg = curl_error($ch);
        $errorNumber = curl_errno($ch);
        curl_close($ch);
        
        // Ergebnis protokollieren
        error_log('API-Test cURL Response: ' . $response);
        error_log('API-Test HTTP Code: ' . $httpCode);
        if ($errorMsg) {
            error_log('API-Test cURL Error: [' . $errorNumber . '] ' . $errorMsg);
        }
        
        // Antwort analysieren
        if ($errorNumber > 0) {
            // cURL-Fehler (z.B. Server nicht erreichbar)
            error_log('API-Test: Verbindung fehlgeschlagen. Grund: ' . $errorMsg);
            sendResponse(false, 'Verbindung fehlgeschlagen: Server nicht gefunden');
        } else if ($httpCode == 401) {
            // Unauthorized (falsches Passwort)
            error_log('API-Test: Verbindung fehlgeschlagen. Grund: Unauthorized (401)');
            sendResponse(false, 'Verbindung fehlgeschlagen: Falsches Passwort');
        } else if ($httpCode == 200) {
            // Erfolgreiche Antwort
            $data = @json_decode($response, true);
            if ($data === null) {
                error_log('API-Test: Ungültige JSON-Antwort: ' . $response);
                sendResponse(false, 'Verbindung fehlgeschlagen: Ungültige Antwort vom Server');
            } else {
                // Prüfen, ob das erwartete Format vorliegt (succeeded und data)
                if (isset($data['succeeded']) && $data['succeeded']) {
                    error_log('API-Test: Verbindung erfolgreich. Daten: ' . print_r($data, true));
                    sendResponse(true, 'Verbindung erfolgreich!', $data);
                } else {
                    // API-Fehler verarbeiten
                    $errorMsg = isset($data['message']) ? $data['message'] : 'Unbekannter API-Fehler';
                    error_log('API-Test: API-Fehler: ' . $errorMsg);
                    sendResponse(false, 'Verbindung fehlgeschlagen: ' . $errorMsg);
                }
            }
        } else {
            // Sonstiger HTTP-Fehler
            error_log('API-Test: HTTP-Fehler: ' . $httpCode);
            sendResponse(false, 'Verbindung fehlgeschlagen: HTTP-Fehler ' . $httpCode);            
        }
        break;
        
    // Chat-Server Verbindung testen
    case 'test_chat_server_connection':
        // Logging für Debug-Zwecke
        error_log('Chat-Server Test: ' . print_r($_POST, true));
        
        // Verschiedene mögliche Parameter-Namen akzeptieren
        $username = '';
        if (isset($_POST['username'])) {
            $username = trim($_POST['username']);
        } elseif (isset($_POST['chat_server_username'])) {
            $username = trim($_POST['chat_server_username']);
        }
        
        $url = '';
        if (isset($_POST['url'])) {
            $url = trim($_POST['url']);
        } elseif (isset($_POST['chat_server_url'])) {
            $url = trim($_POST['chat_server_url']);
        }
        
        $port = '';
        if (isset($_POST['port'])) {
            $port = trim($_POST['port']);
        } elseif (isset($_POST['chat_server_port'])) {
            $port = trim($_POST['chat_server_port']);
        }
        
        $password = '';
        if (isset($_POST['password'])) {
            $password = trim($_POST['password']);
        } elseif (isset($_POST['chat_server_password'])) {
            $password = trim($_POST['chat_server_password']);
        }
        
        error_log("Parsed params: username=$username, url=$url, port=$port, password=***");
        
        if (empty($username) || empty($url) || empty($password)) {
            sendResponse(false, 'Chat-Server: Benutzername, URL und Passwort werden benötigt');
        }
        
        // Port hat einen Standardwert, falls nicht angegeben
        if (empty($port)) {
            $port = '5005'; // Standardwert
        }
        // Der Chat-Server bietet den Endpoint '/chatlog' ohne Parameter an
        $apiUrl = rtrim($url, '/') . ':' . $port . '/chatlog';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . base64_encode($username . ':' . $password)
            ]
        ];
        // Verbesserte Implementierung mit cURL statt file_get_contents
        error_log('Chat-Server-Test: Verbinde mit URL ' . $apiUrl);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 Sekunden Timeout
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Basic Auth-Header setzen
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        
        // cURL ausführen
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMsg = curl_error($ch);
        $errorNumber = curl_errno($ch);
        curl_close($ch);
        
        // Detaillierte Protokollierung für die Fehleranalyse
        error_log('Chat-Server-Test: HTTP Code: ' . $httpCode);
        error_log('Chat-Server-Test: Antwort: ' . substr($response, 0, 500)); // Begrenzen auf 500 Zeichen
        if ($errorMsg) {
            error_log('Chat-Server-Test: cURL-Fehler: [' . $errorNumber . '] ' . $errorMsg);
        }
        
        // Fehleranalyse
        if ($errorNumber > 0) {
            // cURL-Fehler (z.B. Server nicht erreichbar)
            error_log('Chat-Server-Test: Verbindung fehlgeschlagen. Grund: ' . $errorMsg);
            sendResponse(false, 'Verbindung zum Chat-Server fehlgeschlagen: ' . $errorMsg);
            return;
        }
        
        // HTTP-Fehler prüfen
        if ($httpCode != 200) {
            if ($httpCode == 401) {
                error_log('Chat-Server-Test: Authentifizierungsfehler (401)');
                sendResponse(false, 'Authentifizierungsfehler: Benutzername oder Passwort falsch');
            } else {
                error_log('Chat-Server-Test: HTTP-Fehler ' . $httpCode);
                sendResponse(false, 'HTTP-Fehler ' . $httpCode);
            }
            return;
        }
        
        // Antwort verarbeiten
        $data = @json_decode($response, true);
        if ($data === null) {
            error_log('Chat-Server-Test: Keine gültige JSON-Antwort! Rohtext: ' . substr($response, 0, 100) . '...');
            sendResponse(false, 'Ungültige Antwort vom Chat-Server: Keine JSON-Daten');
            return;
        }
        
        // Basierend auf dem Python-Code gibt der Server ein Array von Chat-Nachrichten zurück
        // Wir prüfen ob es ein Array ist statt nach dem Feld 'last_logfile' zu suchen
        if (!is_array($data)) {
            error_log('Chat-Server-Test: Unerwartetes Format in der Antwort. Erwartet ein Array, erhalten: ' . json_encode($data));
            sendResponse(false, 'Ungültige Antwort vom Chat-Server: Kein Array erhalten');
            return;
        }
        
        // Erfolg! Wir senden die ersten paar Chat-Nachrichten zurück als Referenz
        $chatCount = count($data);
        $sampleData = array_slice($data, 0, min(3, $chatCount)); // Erste 3 Nachrichten
        
        error_log('Chat-Server-Test: Verbindung erfolgreich. ' . $chatCount . ' Chat-Nachrichten gefunden.');
        sendResponse(true, 'Verbindung erfolgreich', [
            'chat_count' => $chatCount,
            'sample_data' => $sampleData
        ]);
        break;
    // API-Verbindung testen (Setup-Schritt 2 oder API-Test-Button)
    case 'setup_step':
        if (isset($_POST['step']) && $_POST['step'] == 2) {
            $apiUrl = isset($_POST['api_url']) ? trim($_POST['api_url']) : '';
            $apiPassword = isset($_POST['api_password']) ? trim($_POST['api_password']) : '';
            
            if (empty($apiUrl) || empty($apiPassword)) {
                sendResponse(false, 'API-URL und Passwort erforderlich!');
            }
            // Test: Spieleranzahl abrufen
            require_once 'includes/api_client.php';
            require_once __DIR__ . '/includes/api_client.php';
$testClient = new ApiClient($apiUrl, $apiPassword);
            $playerCount = $testClient->getPlayerCount();
            if ($playerCount !== false) {
                sendResponse(true, 'Verbindung erfolgreich!');
            } else {
                $errorMsg = $testClient->getLastError();
                if (stripos($errorMsg, 'curl error') !== false) {
                    $errorMsg = t('server_not_found');
                }
                sendResponse(false, 'Verbindung fehlgeschlagen: ' . $errorMsg);
            }
        }
        // Falls nicht Step 2, Standard-Setup-Logik (falls vorhanden)
        break;
    // Dashboard
    case 'get_dashboard_data':
        // Spieleranzahl abrufen
        $playerCount = $apiClient->getPlayerCount();
        if ($playerCount === false) {
            $playerCount = 0; // Fehler unterdrücken, einfach 0 Spieler anzeigen
        }
        // Spielerliste abrufen
        $playerList = $apiClient->getPlayerList();
        if ($playerList === false) {
            $playerList = [];
        }
        
        // Spielerliste in Array umwandeln
        $activePlayersList = [];
        $now = time();
        
        // Online-Spieler verarbeiten und aktualisieren
        foreach ($playerList as $index => $player) {
            $activePlayersList[] = $player;
            
            // Spieler in der Historie aktualisieren, wenn wir die nötigen Daten haben
            if (isset($player['unique_id']) && isset($player['name'])) {
                // Prüfen, ob der Spieler bereits in der Datenbank ist
                $existingPlayer = $db->getPlayerByUniqueId($player['unique_id']);
                
                if ($existingPlayer) {
                    // Spieler aktualisieren (last_seen und Status)
                    $db->updatePlayerStatus($player['unique_id'], $player['name'], $now, 'online');
                } else {
                    // Neuen Spieler hinzufügen
                    $db->addPlayerHistory($player['unique_id'], $player['name'], $now, $now, 'online');
                }
            }
        }
        
        // Offline-Spieler markieren - direkt im AJAX-Request, ohne auf den Cron-Job zu warten
        $db->markOfflinePlayers($playerList);
        
        // Kürzlich abgemeldete Spieler abrufen (jetzt aktuell nach dem Markieren)
        $recentPlayers = $db->getRecentlyOfflinePlayers(10);
        
        // Aktuelle Bans zählen
        $banCount = count($db->getActiveBans());
        
        // Steam-Profildaten abrufen, wenn verfügbar
        $steamProfiles = [];
        if ($steamAPI !== null) {
            // Alle Steam IDs aus den aktiven und kürzlich abgemeldeten Spielern sammeln
            $steamIds = [];
            foreach ($activePlayersList as $player) {
                if (isset($player['unique_id'])) {
                    $steamIds[] = $player['unique_id'];
                }
            }
            foreach ($recentPlayers as $player) {
                if (isset($player['unique_id'])) {
                    $steamIds[] = $player['unique_id'];
                }
            }
            
            // Steam-Profile abrufen
            if (!empty($steamIds)) {
                $steamProfiles = $steamAPI->getMultipleProfiles($steamIds);
            }
        }

        // Server-Ressourcen abrufen
        $chatConfig = $config['chat_server'];
        $url = rtrim($chatConfig['url'], '/');
        $port = $chatConfig['port'];
        $username = $chatConfig['username'];
        $password = $chatConfig['password'];
        $resourceUrl = $url . ':' . $port . '/server/resources';
        $opts = [
            'http' => [
                'method'  => 'GET',
                'header'  => 'Authorization: Basic ' . base64_encode($username . ':' . $password)
            ]
        ];
        $context = stream_context_create($opts);
        $res = @file_get_contents($resourceUrl, false, $context);
        // Standardwerte initialisieren
        $cpu_percent = 0;
        $disk_total_gb = $disk_used_gb = 0;
        $ram_total_mb = $ram_used_mb = 0;
        $net_in_mb = $net_out_mb = 0;
        $memory_percent = $disk_percent = 0;
        if ($res !== FALSE) {
            $resData = json_decode($res, true);
            if (is_array($resData)) {
                $cpu_percent = isset($resData['cpu_percent']) ? round($resData['cpu_percent']) : 0;
                $disk_total_gb = isset($resData['disk_total_gb']) ? $resData['disk_total_gb'] : 0;
                $disk_used_gb = isset($resData['disk_used_gb']) ? $resData['disk_used_gb'] : 0;
                $ram_total_mb = isset($resData['ram_total_mb']) ? $resData['ram_total_mb'] : 0;
                $ram_used_mb = isset($resData['ram_used_mb']) ? $resData['ram_used_mb'] : 0;
                $net_in_mb = isset($resData['net_in_mb']) ? $resData['net_in_mb'] : 0;
                $net_out_mb = isset($resData['net_out_mb']) ? $resData['net_out_mb'] : 0;

                // Prozentuale Auslastung berechnen
                $memory_percent = $ram_total_mb > 0 ? round($ram_used_mb / $ram_total_mb * 100) : 0;
                $disk_percent = $disk_total_gb > 0 ? round($disk_used_gb / $disk_total_gb * 100) : 0;
            }
        }

        // Daten zurückgeben
        sendResponse(true, '', [
            'player_count'    => isset($playerCount['num_players']) ? $playerCount['num_players'] : 0,
            'ban_count'      => $banCount,
            'active_players' => $activePlayersList,
            'recent_players'  => $recentPlayers,
            'steam_profiles'  => $steamProfiles,
            'cpu_percent'     => $cpu_percent,
            'ram_total_mb'    => $ram_total_mb,
            'ram_used_mb'     => $ram_used_mb,
            'memory_percent'  => $memory_percent,
            'disk_total_gb'   => $disk_total_gb,
            'disk_used_gb'    => $disk_used_gb,
            'disk_percent'    => $disk_percent,
            'net_in_mb'       => $net_in_mb,
            'net_out_mb'      => $net_out_mb
        ]);
        break;
        
    // Spieler kicken
    case 'kick_player':
        if (!isset($_POST['id'])) {
            sendResponse(false, 'Keine Spieler-ID angegeben');
        }
        
        $uniqueId = $_POST['id'];
        $result = $apiClient->kickPlayer($uniqueId);
        
        if ($result) {
            logActivity('info', 'Spieler gekickt', 'Unique ID: ' . $uniqueId);
            sendResponse(true, t('success_player_kicked'));
        } else {
            sendResponse(false, 'Fehler beim Kicken des Spielers: ' . $apiClient->getLastError());
        }
        break;
        
    // Spieler bannen
    case 'ban_player':
        if (!isset($_POST['unique_id'])) {
            sendResponse(false, 'Keine Spieler-ID angegeben');
        }
        
        $uniqueId = $_POST['unique_id'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
        
        // Prüfen, ob der Spieler schon gebannt ist
        if ($db->isPlayerBanned($uniqueId)) {
            sendResponse(false, t('error_already_banned'));
        }
        
        // Spielerinformationen abrufen
        $playerList = $apiClient->getPlayerList();
        $playerName = '';
        
        if ($playerList) {
            foreach ($playerList as $player) {
                if ($player['unique_id'] == $uniqueId) {
                    $playerName = $player['name'];
                    break;
                }
            }
        }
        
        if (empty($playerName)) {
            // Falls Spielername nicht gefunden wird, in der Datenbank suchen
            $players = $db->getPlayerByUniqueId($uniqueId);
            if ($players && !empty($players)) {
                $playerName = $players[0]['player_name'];
            } else {
                sendResponse(false, t('error_player_not_found'));
            }
        }
        
        // Ban in der Datenbank speichern (als aktiv markieren)
        $banId = $db->addBan($uniqueId, $playerName, $reason, $duration, true);
        
        if (!$banId) {
            sendResponse(false, 'Fehler beim Speichern des Bans');
        }
        
        // Ban auf dem Server durchführen
        $result = $apiClient->banPlayer($uniqueId);
        
        if ($result) {
            logActivity('info', 'Spieler gebannt', 'Name: ' . $playerName . ', Unique ID: ' . $uniqueId . ', Grund: ' . $reason . ', Dauer: ' . $duration);
            sendResponse(true, t('success_player_banned'));
        } else {
            // Ban aus der Datenbank entfernen, falls er auf dem Server nicht durchgeführt werden konnte
            $db->removeBan($banId);
            sendResponse(false, 'Fehler beim Bannen des Spielers: ' . $apiClient->getLastError());
        }
        break;
        
    // Ausstehenden Ban hinzufügen
    case 'add_pending_ban':
        if (!isset($_POST['unique_id']) || !isset($_POST['player_name'])) {
            sendResponse(false, 'Fehlende Parameter');
        }
        
        $uniqueId = $_POST['unique_id'];
        $playerName = $_POST['player_name'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
        
        // Prüfen, ob der Spieler schon gebannt ist
        if ($db->isPlayerBanned($uniqueId)) {
            sendResponse(false, t('error_already_banned'));
        }
        
        // Ban in der Datenbank speichern (als inaktiv markieren)
        $banId = $db->addBan($uniqueId, $playerName, $reason, $duration, false);
        
        if ($banId) {
            logActivity('info', 'Ausstehender Ban hinzugefügt', 'Name: ' . $playerName . ', Unique ID: ' . $uniqueId . ', Grund: ' . $reason . ', Dauer: ' . $duration);
            sendResponse(true, 'Ausstehender Ban wurde hinzugefügt');
        } else {
            sendResponse(false, 'Fehler beim Hinzufügen des ausstehenden Bans');
        }
        break;
        
    // Ban-Liste abrufen
    case 'get_ban_list':
        // Aktive Bans vom Server abrufen
        $serverBanList = $apiClient->getBanList();
        
        if ($serverBanList === false) {
            sendResponse(false, 'Fehler beim Abrufen der Ban-Liste: ' . $apiClient->getLastError());
        }
        
        // Server-Bans in Array umwandeln
        $serverBans = [];
        foreach ($serverBanList as $ban) {
            $serverBans[] = $ban;
        }
        
        // Lokale Ban-Daten abrufen
        $activeBans = $db->getActiveBans();
        $pendingBans = $db->getPendingBans();
        
        // Daten zurückgeben
        sendResponse(true, '', [
            'active_bans' => $activeBans,
            'pending_bans' => $pendingBans
        ]);
        break;
        
    // Spieler entbannen
    case 'unban_player':
        if (!isset($_POST['id'])) {
            sendResponse(false, 'Keine Spieler-ID angegeben');
        }
        
        $uniqueId = $_POST['id'];
        $result = $apiClient->unbanPlayer($uniqueId);
        
        // Auch wenn der API-Aufruf fehlschlägt, prüfen wir, ob der Ban noch auf dem Server ist
        $success = false;
        $message = '';
        
        if ($result) {
            // API-Aufruf erfolgreich
            $success = true;
            $message = t('success_player_unbanned');
        } else {
            // API-Aufruf fehlgeschlagen - prüfen, ob der Ban noch auf dem Server existiert
            $serverBanList = $apiClient->getBanList();
            
            if ($serverBanList !== false) {
                // Prüfen, ob der Ban noch auf dem Server existiert
                $banStillExists = false;
                foreach ($serverBanList as $ban) {
                    if (isset($ban['unique_id']) && $ban['unique_id'] === $uniqueId) {
                        $banStillExists = true;
                        break;
                    }
                }
                
                if (!$banStillExists) {
                    // Ban existiert nicht mehr auf dem Server
                    $success = true;
                    $message = 'Spieler ist bereits entbannt';
                } else {
                    // Ban existiert noch, API-Fehler ausgeben
                    $success = false;
                    $message = 'Fehler beim Entbannen des Spielers: ' . $apiClient->getLastError();
                }
            } else {
                // Konnte Ban-Liste nicht abrufen
                $success = false;
                $message = 'Fehler beim Überprüfen des Ban-Status: ' . $apiClient->getLastError();
            }
        }
        
        // In jedem Fall den Ban aus der lokalen Datenbank entfernen, wenn wir erfolgreich waren
        if ($success) {
            // Direkt aus der Datenbank entfernen, unabhängig vom API-Ergebnis
            if (method_exists($db, 'removeActiveBanByUniqueId')) {
                $db->removeActiveBanByUniqueId($uniqueId);
            } else {
                // Alternativ nach ID in der Datenbank suchen und löschen
                $bans = $db->getActiveBans();
                foreach ($bans as $ban) {
                    if ($ban['unique_id'] === $uniqueId) {
                        $db->removeBan($ban['id']);
                        break;
                    }
                }
            }
            
            logActivity('info', 'Spieler entbannt', 'Unique ID: ' . $uniqueId);
        }
        
        sendResponse($success, $message);
        break;
        
    // Ausstehenden Ban entfernen
    case 'remove_pending_ban':
        if (!isset($_POST['id'])) {
            sendResponse(false, 'Keine Ban-ID angegeben');
        }
        
        $banId = (int)$_POST['id'];
        $result = $db->removeBan($banId);
        
        if ($result) {
            logActivity('info', 'Ausstehender Ban entfernt', 'Ban-ID: ' . $banId);
            sendResponse(true, 'Ausstehender Ban wurde entfernt');
        } else {
            sendResponse(false, 'Fehler beim Entfernen des ausstehenden Bans');
        }
        break;
        
    // Chat-Nachricht senden
    case 'send_chat_message':
        // Prüfen, ob der Benutzer die Berechtigung hat, Nachrichten zu senden
        if (!hasPermission('chat_send')) {
            sendResponse(false, 'Keine Berechtigung zum Senden von Chat-Nachrichten!');
        }
        
        if (!isset($_POST['message'])) {
            sendResponse(false, 'Keine Nachricht angegeben');
        }
        
        $message = $_POST['message'];
        $result = $apiClient->sendChatMessage($message);
        
        if ($result) {
            logActivity('info', 'Chat-Nachricht gesendet', 'Nachricht: ' . $message);
            sendResponse(true, t('message_sent'));
        } else {
            sendResponse(false, 'Fehler beim Senden der Nachricht: ' . $apiClient->getLastError());
        }
        break;
        
    // Sprache ändern
    case 'set_language':
        if (!isset($_POST['language'])) {
            sendResponse(false, 'Keine Sprache angegeben');
        }
        
        $language = $_POST['language'];
        
        // Prüfen, ob die Sprachdatei existiert
        if (!file_exists(MTWI_ROOT . "/lang/{$language}.php")) {
            sendResponse(false, 'Ungültige Sprache');
        }
        
        // Sprache in der Session speichern
        $_SESSION['language'] = $language;
        
        sendResponse(true, 'Sprache geändert');
        break;
        
    // Theme ändern
    case 'set_theme':
        if (!isset($_POST['theme'])) {
            sendResponse(false, 'Kein Theme angegeben');
        }
        
        $theme = $_POST['theme'];
        
        // Prüfen, ob das Theme gültig ist
        if ($theme != 'light' && $theme != 'dark') {
            sendResponse(false, 'Ungültiges Theme');
        }
        
        // Theme in der Session speichern
        $_SESSION['theme'] = $theme;
        
        sendResponse(true, 'Theme geändert');
        break;
        
    // Login
    case 'login':
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            sendResponse(false, 'Benutzername oder Passwort fehlt');
        }
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Prüfen, ob die Anmeldedaten korrekt sind
        if ($username === $config['admin']['username'] && verifyPassword($password, $config['admin']['password'])) {
            // Anmeldung in der Session speichern
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            
            logActivity('info', 'Anmeldung', 'Benutzer: ' . $username);
            sendResponse(true, 'Anmeldung erfolgreich');
        } else {
            logActivity('warning', 'Fehlgeschlagene Anmeldung', 'Benutzer: ' . $username);
            sendResponse(false, t('invalid_credentials'));
        }
        break;
        
    // Einrichtungsassistent - Schritt-Handler
    case 'setup_step':
        if (!isset($_POST['step'])) {
            sendResponse(false, 'Kein Schritt angegeben');
        }
        
        $step = (int)$_POST['step'];
        
        switch ($step) {
            // Schritt 1: Sprache & Basis-Einstellungen
            case 1:
                if (!isset($_POST['language']) || !isset($_POST['theme'])) {
                    sendResponse(false, 'Fehlende Parameter');
                }
                
                $language = $_POST['language'];
                $theme = $_POST['theme'];
                $refreshInterval = isset($_POST['refresh_interval']) ? (int)$_POST['refresh_interval'] : 10;
                
                // Einstellungen aktualisieren
                $config['settings']['default_language'] = $language;
                $config['settings']['default_theme'] = $theme;
                $config['settings']['refresh_interval'] = $refreshInterval;
                
                // In der Session speichern
                $_SESSION['language'] = $language;
                $_SESSION['theme'] = $theme;
                
                // Konfiguration speichern
                if (!saveConfig($config)) {
                    sendResponse(false, 'Fehler beim Speichern der Konfiguration');
                }
                
                sendResponse(true, 'Schritt 1 abgeschlossen');
                break;
                
            // Schritt 2: API-Verbindung
            case 2:
                if (!isset($_POST['api_url']) || !isset($_POST['api_password'])) {
                    sendResponse(false, 'Fehlende Parameter');
                }
                
                $apiUrl = $_POST['api_url'];
                $apiPassword = $_POST['api_password'];
                
                // URL-Format überprüfen
                if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
                    sendResponse(false, t('error_invalid_url'));
                }
                
                // Temporären API-Client erstellen und Verbindung testen
                $tempApiClient = new ApiClient($apiUrl, $apiPassword);
                $testResult = $tempApiClient->getPlayerCount();
                
                if ($testResult === false) {
                    sendResponse(false, 'Fehler bei der Verbindung zur API: ' . $tempApiClient->getLastError());
                }
                
                // API-Einstellungen speichern
                $config['api']['url'] = $apiUrl;
                $config['api']['password'] = $apiPassword;
                
                // Konfiguration speichern
                if (!saveConfig($config)) {
                    sendResponse(false, 'Fehler beim Speichern der Konfiguration');
                }
                
                sendResponse(true, t('connection_successful'));
                break;
                
            // Schritt 3: Admin-Konto
            case 3:
                if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['confirm_password'])) {
                    sendResponse(false, 'Fehlende Parameter');
                }
                
                $username = $_POST['username'];
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];
                
                // Passwörter überprüfen
                if ($password !== $confirmPassword) {
                    sendResponse(false, t('error_password_mismatch'));
                }
                
                // Passwortlänge überprüfen
                if (strlen($password) < 8) {
                    sendResponse(false, t('error_password_too_short'));
                }
                
                // Admin-Konto speichern
                $config['admin']['username'] = $username;
                $config['admin']['password'] = hashPassword($password);
                
                // Konfiguration speichern
                if (!saveConfig($config)) {
                    sendResponse(false, 'Fehler beim Speichern der Konfiguration');
                }
                
                sendResponse(true, 'Admin-Konto erstellt');
                break;
                
            // Schritt 4: Fertigstellen
            case 4:
                // Einrichtung als abgeschlossen markieren
                $config['settings']['setup_completed'] = true;
                
                // Konfiguration speichern
                if (!saveConfig($config)) {
                    sendResponse(false, 'Fehler beim Speichern der Konfiguration');
                }
                
                // Automatisch anmelden
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $config['admin']['username'];
                
                logActivity('info', 'Einrichtung abgeschlossen');
                sendResponse(true, t('setup_completed'));
                break;
                
            default:
                sendResponse(false, 'Ungültiger Schritt');
        }
        break;
        
    // Einstellungen speichern
    case 'save_settings':
        // Allgemeine Einstellungen
        if (isset($_POST['language'])) {
            $config['settings']['default_language'] = $_POST['language'];
            $_SESSION['language'] = $_POST['language'];
        }
        
        if (isset($_POST['theme'])) {
            $config['settings']['default_theme'] = $_POST['theme'];
            $_SESSION['theme'] = $_POST['theme'];
        }
        
        if (isset($_POST['refresh_interval'])) {
            $config['settings']['refresh_interval'] = (int)$_POST['refresh_interval'];
        }
        
        // API-Einstellungen
        if (isset($_POST['api_url']) && isset($_POST['api_password'])) {
            $apiUrl = $_POST['api_url'];
            $apiPassword = $_POST['api_password'];
            
            // URL-Format überprüfen
            if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
                sendResponse(false, t('error_invalid_url'));
            }
            
            $config['api']['url'] = $apiUrl;
            $config['api']['password'] = $apiPassword;
        }
        
        // Konfiguration speichern
        if (!saveConfig($config)) {
            sendResponse(false, 'Fehler beim Speichern der Konfiguration');
        }
        sendResponse(true, t('settings_saved'));
        break;
        
    // Passwort ändern
    // Admin-Verwaltung: Liste der Admins abrufen
    case 'get_admins':
        // Debug-Output
        error_log('get_admins: ' . json_encode($config['admins']));
        
        // Erlaube auch normalen Administratoren Zugriff auf die Admin-Liste
        if (!isMasterAdmin() && !hasPermission('settings_admins')) {
            sendResponse(false, 'Keine Berechtigung für die Verwaltung von Admin-Konten');
        }
        
        $admins = [];
        foreach ($config['admins'] as $username => $adminData) {
            $admins[] = [
                'username' => $username,
                'role' => $adminData['role'],
                'active' => $adminData['active']
            ];
        }
        
        sendResponse(true, '', ['admins' => $admins]);
        break;
        
    // Admin-Verwaltung: Neuen Admin hinzufügen
    case 'add_admin':
        if (!isMasterAdmin() && !hasPermission('settings_admins')) {
            sendResponse(false, 'Keine Berechtigung für die Verwaltung von Admin-Konten');
        }
        
        if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['role'])) {
            sendResponse(false, 'Unvollständige Daten');
        }
        
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $active = isset($_POST['active']) && $_POST['active'] === 'true';
        
        // Prüfen, ob der Benutzername bereits existiert
        if (isset($config['admins'][$username]) || $username === $config['master_admin']['username']) {
            sendResponse(false, 'Dieser Benutzername existiert bereits');
        }
        
        // Neuen Admin hinzufügen
        $config['admins'][$username] = [
            'password' => hashPassword($password),
            'role' => $role,
            'active' => $active
        ];
        
        // Konfiguration speichern
        if (!saveConfig($config)) {
            sendResponse(false, 'Fehler beim Speichern der Konfiguration');
        }
        
        sendResponse(true, t('admin_added'));
        break;
    
    // Admin-Verwaltung: Admin aktualisieren
    case 'update_admin':
        if (!isMasterAdmin() && !hasPermission('settings_admins')) {
            sendResponse(false, 'Keine Berechtigung für die Verwaltung von Admin-Konten');
        }
        
        if (!isset($_POST['username']) || !isset($_POST['role'])) {
            sendResponse(false, 'Unvollständige Daten');
        }
        
        $username = trim($_POST['username']);
        $originalUsername = trim($_POST['original_username'] ?? $username);
        $role = $_POST['role'];
        $active = isset($_POST['active']) && $_POST['active'] === 'true';
        $password = isset($_POST['password']) ? $_POST['password'] : null;
        
        // Prüfen, ob der Admin existiert
        if (!isset($config['admins'][$originalUsername])) {
            sendResponse(false, 'Admin nicht gefunden');
        }
        
        // Master-Admin darf nicht deaktiviert werden
        if ($config['admins'][$originalUsername]['role'] === 'master' && (!$active || $role !== 'master')) {
            sendResponse(false, 'Der Master-Admin kann nicht deaktiviert oder in seiner Rolle geändert werden');
        }
        
        // Admin aktualisieren
        $adminData = $config['admins'][$originalUsername];
        
        // Wenn der Benutzername geändert wurde
        if ($username !== $originalUsername) {
            // Prüfen, ob der neue Benutzername bereits existiert
            if (isset($config['admins'][$username])) {
                sendResponse(false, 'Dieser Benutzername existiert bereits');
            }
            
            // Admin unter neuem Namen anlegen
            $config['admins'][$username] = $adminData;
            // Alten Admin entfernen
            unset($config['admins'][$originalUsername]);
        }
        
        // Daten aktualisieren
        $config['admins'][$username]['role'] = $role;
        $config['admins'][$username]['active'] = $active;
        
        // Passwort aktualisieren, wenn angegeben
        if ($password) {
            $config['admins'][$username]['password'] = hashPassword($password);
        }
        
        // Konfiguration speichern
        if (!saveConfig($config)) {
            sendResponse(false, 'Fehler beim Speichern der Konfiguration');
        }
        
        sendResponse(true, t('admin_updated'));
        break;
    
    // Admin-Verwaltung: Admin löschen
    case 'delete_admin':
        if (!isMasterAdmin() && !hasPermission('settings_admins')) {
            sendResponse(false, 'Keine Berechtigung für die Verwaltung von Admin-Konten');
        }
        
        if (!isset($_POST['username'])) {
            sendResponse(false, 'Kein Benutzername angegeben');
        }
        
        $username = trim($_POST['username']);
        
        // Prüfen, ob der Admin existiert
        if (!isset($config['admins'][$username])) {
            sendResponse(false, 'Admin nicht gefunden');
        }
        
        // Master-Admin darf nicht gelöscht werden
        if ($config['admins'][$username]['role'] === 'master') {
            sendResponse(false, 'Der Master-Admin kann nicht gelöscht werden');
        }
        
        // Admin entfernen
        unset($config['admins'][$username]);
        
        // Konfiguration speichern
        if (!saveConfig($config)) {
            sendResponse(false, 'Fehler beim Speichern der Konfiguration');
        }
        
        sendResponse(true, t('admin_deleted'));
        break;
        
    case 'change_password':
        // Prüfen ob alle Felder gesetzt sind
        if (!isset($_POST['current_password']) || !isset($_POST['new_password']) || !isset($_POST['confirm_password'])) {
            sendResponse(false, 'Unvollständige Daten');
        }
        
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Aktuelles Passwort überprüfen
        if (!verifyPassword($currentPassword, $config['admin']['password'])) {
            sendResponse(false, 'Aktuelles Passwort ist falsch');
        }
        
        // Neue Passwörter überprüfen
        if ($newPassword !== $confirmPassword) {
            sendResponse(false, t('error_password_mismatch'));
        }
        
        // Passwortlänge überprüfen
        if (strlen($newPassword) < 8) {
            sendResponse(false, t('error_password_too_short'));
        }
        
        // Passwort aktualisieren
        $config['admin']['password'] = hashPassword($newPassword);
        
        // Konfiguration speichern
        if (!saveConfig($config)) {
            sendResponse(false, 'Fehler beim Speichern der Konfiguration');
        }
        
        sendResponse(true, t('password_changed'));
        break;
        
    // Quick Messages verwalten
    case 'get_quick_messages':
        $quickMessages = $db->getQuickMessages();
        $formattedMessages = [];
        
        foreach ($quickMessages as $qm) {
            // Wenn die Nachricht mit t: beginnt, handelt es sich um einen Übersetzungsschlüssel
            $isTranslated = substr($qm['message'], 0, 2) === 't:';
            $displayMessage = $isTranslated 
                ? t(substr($qm['message'], 2)) 
                : $qm['message'];
                
            $formattedMessages[] = [
                'id' => $qm['id'],
                'message' => $qm['message'],  // Original-Nachricht mit t:-Präfix falls vorhanden
                'display_message' => $displayMessage,  // Übersetzte/angezeigte Nachricht
                'is_translated' => $isTranslated
            ];
        }
        
        sendResponse(true, '', [
            'quick_messages' => $formattedMessages
        ]);
        break;
        
    case 'add_quick_message':
        if (!isset($_POST['message']) || empty($_POST['message'])) {
            sendResponse(false, 'Keine Nachricht angegeben');
        }
        
        $message = trim($_POST['message']);
        $id = $db->addQuickMessage($message);
        
        if ($id) {
            logActivity('info', 'Schnellnachricht hinzugefügt', 'ID: ' . $id);
            sendResponse(true, t('add_quick_message'), [
                'id' => $id,
                'message' => $message
            ]);
        } else {
            sendResponse(false, 'Fehler beim Hinzufügen der Schnellnachricht');
        }
        break;
        
    case 'update_quick_message':
        if (!isset($_POST['id']) || !isset($_POST['message']) || empty($_POST['message'])) {
            sendResponse(false, 'Fehlende Parameter');
        }
        
        $id = (int)$_POST['id'];
        $message = trim($_POST['message']);
        
        $result = $db->updateQuickMessage($id, $message);
        
        if ($result) {
            logActivity('info', 'Schnellnachricht aktualisiert', 'ID: ' . $id);
            sendResponse(true, t('edit_quick_message'), [
                'id' => $id,
                'message' => $message
            ]);
        } else {
            sendResponse(false, 'Fehler beim Aktualisieren der Schnellnachricht');
        }
        break;
        
    case 'delete_quick_message':
        if (!isset($_POST['id'])) {
            sendResponse(false, 'Keine ID angegeben');
        }
        
        $id = (int)$_POST['id'];
        $result = $db->deleteQuickMessage($id);
        
        if ($result) {
            logActivity('info', 'Schnellnachricht gelöscht', 'ID: ' . $id);
            sendResponse(true, t('delete_quick_message'), [
                'id' => $id
            ]);
        } else {
            sendResponse(false, 'Fehler beim Löschen der Schnellnachricht');
        }
        break;
        
    // Berechtigungen für Rollenverwaltung abrufen
    case 'get_permissions':
        // Nur für Benutzer mit entsprechenden Berechtigungen
        if (!hasPermission('settings_roles') && !isMasterAdmin()) {
            sendResponse(false, 'Keine Berechtigung für die Rollenverwaltung');
        }
        
        // Aktuelle Rolle des Benutzers bestimmen
        $currentRole = 'viewer'; // Standardwert
        if (isMasterAdmin()) {
            $currentRole = 'master';
        } else {
            $username = getCurrentUsername();
            if (isset($config['admins'][$username]['role'])) {
                $currentRole = $config['admins'][$username]['role'];
            }
        }
        
        // Berechtigungen aus der Konfiguration zurückgeben
        sendResponse(true, '', [
            'permissions' => $config['permissions'],
            'current_role' => $currentRole
        ]);
        break;
        
    // Berechtigungen speichern
    case 'save_permissions':
        // Nur für Benutzer mit entsprechenden Berechtigungen
        if (!hasPermission('settings_roles') && !isMasterAdmin()) {
            sendResponse(false, 'Keine Berechtigung für die Rollenverwaltung');
        }
        
        if (!isset($_POST['permissions'])) {
            sendResponse(false, 'Keine Berechtigungen angegeben');
        }
        
        // Berechtigungen aus dem POST-Parameter dekodieren
        $updatedPermissions = json_decode($_POST['permissions'], true);
        if (!$updatedPermissions || !isset($updatedPermissions['roles'])) {
            sendResponse(false, 'Ungültiges Format der Berechtigungen');
        }
        
        // Aktuelle Rolle des Benutzers bestimmen
        $currentRole = 'viewer'; // Standardwert
        if (isMasterAdmin()) {
            $currentRole = 'master';
        } else {
            $username = getCurrentUsername();
            if (isset($config['admins'][$username]['role'])) {
                $currentRole = $config['admins'][$username]['role'];
            }
        }
        
        // Rollengewichte definieren
        $roleWeights = [
            'master' => 4,
            'admin' => 3,
            'moderator' => 2,
            'viewer' => 1
        ];
        
        $currentRoleWeight = $roleWeights[$currentRole] ?? 0;
        
        // Vor Beginn der Verifizierung prüfen, welche Rollen tatsächlich bearbeitet werden
        // Nur Rollen bearbeiten, die auch in der Anfrage enthalten sind und die der Benutzer bearbeiten darf
        $rolesToUpdate = [];
        
        foreach ($updatedPermissions['roles'] as $role => $roleData) {
            $roleWeight = $roleWeights[$role] ?? 0;
            
            // Master Admin darf alle Rollen bearbeiten
            if ($currentRole === 'master' || $roleWeight < $currentRoleWeight) {
                $rolesToUpdate[] = $role;
            }
        }
        
        // Berechtigungen in der Konfiguration aktualisieren
        // Nur die Rollen aktualisieren, die als gültig markiert wurden
        if (empty($rolesToUpdate)) {
            sendResponse(false, "Sie haben keine Berechtigungen zum Bearbeiten der angegebenen Rollen");
        }
        
        foreach ($rolesToUpdate as $role) {
            if (isset($updatedPermissions['roles'][$role])) {
                // Berechtigungen aktualisieren
                $config['permissions']['roles'][$role]['permissions'] = $updatedPermissions['roles'][$role]['permissions'];
            }
        }
        
        // Konfiguration speichern
        if (!saveConfig($config)) {
            sendResponse(false, 'Fehler beim Speichern der Konfiguration');
        }
        
        // Aktivitätsprotokoll
        logActivity('info', 'Berechtigungen aktualisiert', "Benutzer: $username");
        
        sendResponse(true, 'Berechtigungen erfolgreich gespeichert');
        break;

    default:
        sendResponse(false, 'Unbekannte Aktion');
}
