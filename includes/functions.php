<?php
/**
 * Motor Town Web Interface (MTWI) - Hilfsfunktionen
 */

/**
 * Übersetzt einen Text basierend auf den Sprachdateien
 * 
 * @param string $key    Schlüssel in der Sprachdatei
 * @param array  $params Parameter für Platzhalter (optional)
 * @return string        Übersetzter Text
 */
function t($key, $params = []) {
    global $lang;
    
    if (!isset($lang[$key])) {
        return $key; // Fallback auf den Schlüssel selbst
    }
    
    $text = $lang[$key];
    
    // Parameter ersetzen
    if (!empty($params)) {
        foreach ($params as $param => $value) {
            $text = str_replace('{' . $param . '}', $value, $text);
        }
    }
    
    return $text;
}

/**
 * Formatiert ein Unix-Timestamp in ein lesbares Datum
 * 
 * @param int    $timestamp Unix-Timestamp
 * @param string $format    Datumsformat (optional)
 * @return string           Formatiertes Datum
 */
function formatDate($timestamp, $format = null) {
    global $currentLang;
    
    if ($format === null) {
        $format = ($currentLang == 'de') ? 'd.m.Y H:i:s' : 'Y-m-d H:i:s';
    }
    
    return date($format, $timestamp);
}

/**
 * Formatiert eine Zeitdauer in ein lesbares Format
 * 
 * @param int $seconds Sekunden
 * @return string      Formatierte Zeitdauer
 */
function formatDuration($seconds) {
    global $currentLang;
    
    if ($seconds == 0) {
        return t('duration_permanent');
    }
    
    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);
    $days = floor($hours / 24);
    
    if ($days > 0) {
        return $days . ' ' . ($days == 1 ? t('duration_day') : t('duration_days'));
    } elseif ($hours > 0) {
        return $hours . ' ' . ($hours == 1 ? t('duration_hour') : t('duration_hours'));
    } elseif ($minutes > 0) {
        return $minutes . ' ' . ($minutes == 1 ? t('duration_minute') : t('duration_minutes'));
    } else {
        return $seconds . ' ' . ($seconds == 1 ? t('duration_second') : t('duration_seconds'));
    }
}

/**
 * Gibt vordefinierte Ban-Optionen zurück
 * 
 * @return array Liste der Ban-Optionen
 */
function getBanOptions() {
    return [
        60 => '1 ' . t('duration_minute'),
        600 => '10 ' . t('duration_minutes'),
        1800 => '30 ' . t('duration_minutes'),
        3600 => '1 ' . t('duration_hour'),
        7200 => '2 ' . t('duration_hours'),
        43200 => '12 ' . t('duration_hours'),
        86400 => '24 ' . t('duration_hours'),
        172800 => '2 ' . t('duration_days'),
        604800 => '7 ' . t('duration_days'),
        2592000 => '30 ' . t('duration_days'),
        0 => t('duration_permanent')
    ];
}

/**
 * Erstellt eine sichere Hash-Version eines Passworts
 * 
 * @param string $password Klartextpasswort
 * @return string          Gehashtes Passwort
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Überprüft ein Passwort gegen einen Hash
 * 
 * @param string $password Klartextpasswort
 * @param string $hash     Gehashtes Passwort
 * @return bool            true wenn übereinstimmend, sonst false
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Speichert die Konfiguration
 * 
 * @param array $newConfig Neue Konfiguration
 * @return bool           Erfolg
 */
function saveConfig($newConfig) {
    $configFile = MTWI_ROOT . '/config/config.php';
    $configDir = dirname($configFile);

    // Prüfe, ob das Verzeichnis beschreibbar ist
    if (!is_writable($configDir)) {
        // Fehlertext für Setup-Assistent
        $_SESSION['config_write_error'] =
            'Das Verzeichnis <code>' . htmlspecialchars($configDir) . '</code> ist nicht beschreibbar! ' .
            'Bitte geben Sie dem Webserver-Benutzer Schreibrechte auf dieses Verzeichnis.';
        return false;
    }
    
    // KRITISCH: Sicherstellen, dass setup_completed=true erhalten bleibt
    // Wenn setup_completed bereits in der Konfiguration existiert und true ist,
    // sollte es immer auf true bleiben
    if (isset($newConfig['settings']) && is_array($newConfig['settings'])) {
        // Wenn eine vorhandene Konfigurationsdatei existiert, setup_completed auslesen
        if (file_exists($configFile)) {
            $oldConfig = [];
            include($configFile);
            if (isset($config) && is_array($config) && 
                isset($config['settings']['setup_completed']) && 
                $config['settings']['setup_completed'] === true) {
                // setup_completed=true von der alten Konfiguration übernehmen
                $newConfig['settings']['setup_completed'] = true;
            }
        }
    }

    $content = "<?php\n/**\n * Motor Town Web Interface (MTWI) - Konfigurationsdatei\n */\n\n";
    $content .= '$config = ' . var_export($newConfig, true) . ";\n";
    $content .= "\nreturn \$config;\n";
    
    // Fehler beim Schreiben abfangen
    if (file_put_contents($configFile, $content) === false) {
        $_SESSION['config_write_error'] =
            'Die Datei <code>' . htmlspecialchars($configFile) . '</code> konnte nicht geschrieben werden!';
        return false;
    }
    // Fehler zurücksetzen, falls erfolgreich
    unset($_SESSION['config_write_error']);
    return true;
}

/**
 * Protokolliert einen Vorgang
 * 
 * @param string $type    Art des Vorgangs (info, warning, error)
 * @param string $message Nachricht
 * @param string $details Details (optional)
 */
function logActivity($type, $message, $details = null) {
    global $db;
    
    if (isset($db)) {
        $db->addLog($type, $message, $details);
    }
}

/**
 * Gibt die URL der aktuellen Seite zurück
 * 
 * @return string URL
 */
function getCurrentUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Erstellt einen sicheren CSRF-Token
 * 
 * @return string Token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Überprüft einen CSRF-Token
 * 
 * @param string $token Zu überprüfender Token
 * @return bool        true wenn gültig, sonst false
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Zeigt eine Fehlermeldung an
 * 
 * @param string $title    Titel der Fehlermeldung
 * @param string $message  Detaillierte Fehlermeldung
 */
function displayError($title, $message) {
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">';
    echo '<h4 class="alert-heading">' . htmlspecialchars($title) . '</h4>';
    echo '<p>' . htmlspecialchars($message) . '</p>';
    echo '<hr>';
    echo '<p class="mb-0">' . t('redirecting_in_seconds', ['seconds' => '5']) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '<script>setTimeout(function() { window.location.href = "index.php?page=dashboard"; }, 5000);</script>';
}

/**
 * Gibt die vollständige URL zum Chat-Server zurück
 * 
 * @return string URL zum Chat-Server mit Port
 */
function getChatServerUrl() {
    global $config;
    
    if (!isset($config['chat_server']) || !isset($config['chat_server']['url']) || !isset($config['chat_server']['port'])) {
        return '';
    }
    
    return $config['chat_server']['url'] . ':' . $config['chat_server']['port'];
}

/**
 * Erstellt einen Auth-Kontext für Verbindungen zum Chat-Server
 * 
 * @return resource Stream-Kontext mit Auth-Header
 */
function createAuthContext() {
    global $config;
    
    if (!isset($config['chat_server']) || !isset($config['chat_server']['username']) || !isset($config['chat_server']['password'])) {
        logApiMessage('Keine Chat-Server Anmeldedaten konfiguriert');
        return stream_context_create([]);
    }
    
    $username = $config['chat_server']['username'];
    $password = $config['chat_server']['password'];
    
    // Debug-Log
    logApiMessage('Verwende Auth: ' . $username . ':' . str_repeat('*', strlen($password)));
    
    // Verschiedene Formate für den Auth-Header testen
    $authString = $username . ':' . $password;
    $base64Auth = base64_encode($authString);
    
    return stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Basic $base64Auth\r\n",
            'timeout' => 5, // 5 Sekunden Timeout
            'ignore_errors' => true // Wichtig, um Fehlerdetails zu erhalten
        ]
    ]);
}

/**
 * Protokolliert einen API-Aufruf
 * 
 * @param string $message Nachricht für das Log
 */
function logApiMessage($message) {
    $logFile = MTWI_ROOT . '/data/players_api.log';
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
