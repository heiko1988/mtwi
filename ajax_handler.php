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

// CSRF-Token überprüfen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        sendResponse(false, 'Ungültiges oder fehlendes CSRF-Token');
    }
}

// Prüfen, ob eine Aktion angegeben wurde
if (!isset($_POST['action'])) {
    sendResponse(false, 'Keine Aktion angegeben');
}

$action = $_POST['action'];

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
        // HIER: Endpunkt ggf. anpassen, falls /chatlog/send o.ä. existiert
        sendResponse(false, 'Nachricht senden ist noch nicht implementiert (API-Endpunkt fehlt)!');
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

    // Chat-Server Verbindung testen
    case 'test_chat_server_connection':
        $username = isset($_POST['chat_server_username']) ? trim($_POST['chat_server_username']) : '';
        $url = isset($_POST['chat_server_url']) ? trim($_POST['chat_server_url']) : '';
        $port = isset($_POST['chat_server_port']) ? trim($_POST['chat_server_port']) : '';
        $password = isset($_POST['chat_server_password']) ? trim($_POST['chat_server_password']) : '';
        if (empty($username) || empty($url) || empty($port) || empty($password)) {
            sendResponse(false, 'Alle Felder müssen ausgefüllt werden!');
        }
        $apiUrl = rtrim($url, '/') . ':' . $port . '/chatlog?lastfile=1';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . base64_encode($username . ':' . $password)
            ]
        ];
        $context = stream_context_create($opts);
        $result = @file_get_contents($apiUrl, false, $context);
        if ($result === FALSE) {
            sendResponse(false, 'Verbindung zum Chat-Server fehlgeschlagen!');
        }
        $data = json_decode($result, true);
        if (!$data || !isset($data['last_logfile'])) {
            sendResponse(false, 'Ungültige Antwort vom Chat-Server!');
        }
        sendResponse(true, 'Verbindung erfolgreich', ['last_logfile' => $data['last_logfile']]);
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
        // Daten zurückgeben
        sendResponse(true, '', [
            'player_count' => isset($playerCount['num_players']) ? $playerCount['num_players'] : 0,
            'ban_count' => $banCount,
            'active_players' => $activePlayersList,
            'recent_players' => $recentPlayers
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
    case 'change_password':
        if (!isset($_POST['current_password']) || !isset($_POST['new_password']) || !isset($_POST['confirm_password'])) {
            sendResponse(false, 'Fehlende Parameter');
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
        
    default:
        sendResponse(false, 'Unbekannte Aktion');
}
