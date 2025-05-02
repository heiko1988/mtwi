<?php
/**
 * Motor Town Web Interface (MTWI) - Setup-Assistent
 * 
 * Dieser Assistent hilft bei der Ersteinrichtung des MTWI-Systems.
 */

// Session starten
session_start();

// Fehlerbehandlung für Debug aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prüfen, ob das System bereits eingerichtet wurde
if (file_exists('../config/config.php')) {
    $config = include('../config/config.php');
    if (isset($config['settings']['setup_completed']) && $config['settings']['setup_completed'] === true) {
        // Wenn Setup bereits abgeschlossen ist, Zugriffsprüfung
        if (!isset($_GET['force']) || $_GET['force'] !== 'true') {
            header('Location: ../index.php');
            exit('System ist bereits eingerichtet. <a href="../index.php">Zurück zum Login</a>');
        }
    }
}

// Verfügbare Sprachen
$languages = [
    'de' => 'Deutsch',
    'en' => 'English'
];

// Standard-Sprache festlegen
$language = isset($_SESSION['setup_language']) ? $_SESSION['setup_language'] : 'de';

// Sprachdateien laden
include_once('../lang/' . $language . '.php');

// Verfügbare Schritte und deren Status
$steps = [
    1 => ['title' => 'Willkommen', 'file' => 'welcome.php', 'completed' => false],
    2 => ['title' => 'Systemanforderungen', 'file' => 'requirements.php', 'completed' => false],
    3 => ['title' => 'Datenbank-Einrichtung', 'file' => 'database.php', 'completed' => false],
    4 => ['title' => 'Server-Konfiguration', 'file' => 'server.php', 'completed' => false],
    5 => ['title' => 'Admin-Konto', 'file' => 'admin.php', 'completed' => false],
    6 => ['title' => 'Abschluss', 'file' => 'finish.php', 'completed' => false]
];

// Aktuellen Schritt ermitteln
$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($currentStep < 1 || $currentStep > count($steps)) {
    $currentStep = 1;
}

// Sprachänderung verarbeiten
if (isset($_GET['language']) && array_key_exists($_GET['language'], $languages)) {
    $_SESSION['setup_language'] = $_GET['language'];
    header('Location: ?step=' . $currentStep);
    exit;
}

// Verarbeitung der Weiterleitung nach Abschluss der Installation
if (isset($_GET['finish']) && $_GET['finish'] === 'complete') {
    // Session-Variablen löschen
    $_SESSION['setup_progress'] = null;
    $_SESSION['setup_completed_steps'] = null;
    $_SESSION['installation_success'] = null;
    $_SESSION['error_message'] = null;
    
    // Zur Anmeldeseite weiterleiten
    header('Location: ../index.php');
    exit;
}

/******************************
 * PRE-RENDERING PROZESSIERUNG *
 ******************************/

// Definieren von Hilfsfunktionen, die in jedem Schritt verfügbar sein sollen

// Funktion zum Speichern des Setup-Fortschritts
function saveSetupProgress($step, $data) {
    // Sicherstellen, dass das Arrays initialisiert sind
    if (!isset($_SESSION['setup_progress']) || !is_array($_SESSION['setup_progress'])) {
        $_SESSION['setup_progress'] = [];
    }
    
    if (!isset($_SESSION['setup_completed_steps']) || !is_array($_SESSION['setup_completed_steps'])) {
        $_SESSION['setup_completed_steps'] = [];
    }
    
    // Daten speichern
    $_SESSION['setup_progress'][$step] = $data;
    $_SESSION['setup_completed_steps'][$step] = true;
    
    // Sitzung sofort speichern, um Datenverlust zu vermeiden
    session_write_close();
    session_start();
}

// Funktion zum Abrufen des Setup-Fortschritts
function getSetupProgress($step) {
    // Prüfen, ob das Array initialisiert ist
    if (!isset($_SESSION['setup_progress']) || !is_array($_SESSION['setup_progress'])) {
        $_SESSION['setup_progress'] = [];
        return null;
    }
    
    // Daten abrufen
    $data = isset($_SESSION['setup_progress'][$step]) ? $_SESSION['setup_progress'][$step] : null;
    
    // Debug-Ausgabe für den Fortschritt
    debugLog("Lese Schritt $step aus der Session: " . ($data ? json_encode($data) : 'null'));
    
    return $data;
}

// Funktion zur Prüfung, ob ein Schritt abgeschlossen ist
function isStepCompleted($step) {
    return isset($_SESSION['setup_completed_steps'][$step]) && $_SESSION['setup_completed_steps'][$step] === true;
}

// Debug-Funktion für die Fehlerprotokollierung
function debugLog($message) {
    error_log('[MTWI SETUP DEBUG] ' . $message);
}

// Funktion zum Erstellen der Konfigurationsdatei
function createConfig() {
    debugLog('Starte Erstellung der Konfigurationsdatei');
    
    try {
        // Fortschrittsdaten laden
        $database = getSetupProgress(3);
        $server = getSetupProgress(4);
        $admin = getSetupProgress(5);
        
        debugLog('Setup-Fortschrittsdaten geladen');
        
        // Basisstruktur erstellen
        $config = [];
        
        // Datenbankeinstellungen
        $config['database'] = [
            'type' => $database['type'],
            'file' => $database['type'] === 'sqlite' ? $database['file'] : '',
            'host' => $database['type'] === 'mysql' ? $database['host'] : 'localhost',
            'name' => $database['type'] === 'mysql' ? $database['name'] : 'mtwi',
            'user' => $database['type'] === 'mysql' ? $database['user'] : '',
            'pass' => $database['type'] === 'mysql' ? $database['pass'] : '',
        ];
        
        // Chat-Server-Einstellungen
        $config['chat_server'] = [
            'username' => $server['chat_username'],
            'url' => $server['chat_url'],
            'port' => $server['chat_port'],
            'password' => $server['chat_password']
        ];
        
        // Steam-API-Einstellungen
        $config['steam_api'] = [
            'key' => $server['steam_api_key'],
            'enable_profiles' => true
        ];
        
        // API-Einstellungen
        $config['api'] = [
            'url' => $server['api_url'],
            'password' => $server['api_password']
        ];
        
        // Admin-Konto
        $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
        $config['master_admin'] = [
            'username' => $admin['username'],
            'password' => $hashedPassword
        ];
        
        // Admin-Liste
        $config['admins'] = [
            $admin['username'] => [
                'password' => $hashedPassword,
                'role' => 'master',
                'active' => true
            ]
        ];
        
        // Allgemeine Einstellungen
        $config['settings'] = [
            'default_language' => $admin['language'],
            'default_theme' => $admin['theme'],
            'refresh_interval' => 10,
            'setup_completed' => true
        ];
        
        debugLog('Basiskonfiguration erstellt');
        
        // Berechtigungen laden
        // Wir kopieren direkt die Berechtigungen aus der Template-Datei
        $templatePath = '../config/config.template.php';
        if (file_exists($templatePath)) {
            debugLog('Template-Datei gefunden: ' . $templatePath);
            
            // Template-Datei lesen
            $tempPermissions = [];
            include($templatePath);
            if (isset($config) && is_array($config) && isset($config['permissions'])) {
                $tempPermissions = $config['permissions'];
                debugLog('Berechtigungen aus Template geladen');
            } else {
                debugLog('Fehler: Berechtigungen in Template nicht gefunden');
            }
            
            // Lokale $config wiederherstellen, da include() es überschrieben hat
            $config = []; // $config zurücksetzen
            $database = getSetupProgress(3);
            $server = getSetupProgress(4);
            $admin = getSetupProgress(5);
            
            // Konfiguration neu aufbauen
            // Datenbankeinstellungen
            $config['database'] = [
                'type' => $database['type'],
                'file' => $database['type'] === 'sqlite' ? $database['file'] : '',
                'host' => $database['type'] === 'mysql' ? $database['host'] : 'localhost',
                'name' => $database['type'] === 'mysql' ? $database['name'] : 'mtwi',
                'user' => $database['type'] === 'mysql' ? $database['user'] : '',
                'pass' => $database['type'] === 'mysql' ? $database['pass'] : '',
            ];
            
            // Chat-Server-Einstellungen
            $config['chat_server'] = [
                'username' => $server['chat_username'],
                'url' => $server['chat_url'],
                'port' => $server['chat_port'],
                'password' => $server['chat_password']
            ];
            
            // Steam-API-Einstellungen
            $config['steam_api'] = [
                'key' => $server['steam_api_key'],
                'enable_profiles' => true
            ];
            
            // API-Einstellungen
            $config['api'] = [
                'url' => $server['api_url'],
                'password' => $server['api_password']
            ];
            
            // Admin-Konto
            $config['master_admin'] = [
                'username' => $admin['username'],
                'password' => $hashedPassword
            ];
            
            // Admin-Liste
            $config['admins'] = [
                $admin['username'] => [
                    'password' => $hashedPassword,
                    'role' => 'master',
                    'active' => true
                ]
            ];
            
            // Allgemeine Einstellungen
            $config['settings'] = [
                'default_language' => $admin['language'],
                'default_theme' => $admin['theme'],
                'refresh_interval' => 10,
                'setup_completed' => true
            ];
            
            // Berechtigungen hinzufügen
            $config['permissions'] = $tempPermissions;
            debugLog('Konfiguration mit Berechtigungen neu aufgebaut');
        } else {
            debugLog('Warnung: Template-Datei nicht gefunden: ' . $templatePath);
        }
        
        // Konfigurationsdatei im ursprünglichen Format erstellen
        $configContent = "<?php\n/**\n * Motor Town Web Interface (MTWI) - Konfigurationsdatei\n */\n\n";
        $configContent .= '$config = ' . var_export($config, true) . ";\n";
        $configContent .= "\nreturn \$config;\n";
        
        // Prüfen, ob config-Verzeichnis existiert
        $configDir = '../config';
        if (!file_exists($configDir)) {
            $result = mkdir($configDir, 0755, true);
            if (!$result) {
                debugLog('Fehler: Konnte Verzeichnis nicht erstellen: ' . $configDir);
                return false;
            }
            debugLog('Verzeichnis erstellt: ' . $configDir);
        }
        
        // Prüfen, ob wir in das Verzeichnis schreiben können
        if (!is_writable($configDir)) {
            debugLog('Fehler: Verzeichnis nicht beschreibbar: ' . $configDir);
            return false;
        }
        
        // Konfigurationsdatei schreiben
        $configFile = $configDir . '/config.php';
        
        // Die alte Konfigurationsdatei löschen, falls sie existiert
        if (file_exists($configFile)) {
            unlink($configFile);
            debugLog('Alte Konfigurationsdatei gelöscht.');
        }
        
        // Direkt in die Datei schreiben
        $result = file_put_contents($configFile, $configContent);
        
        if ($result === false) {
            debugLog('Fehler: Konnte Konfigurationsdatei nicht schreiben: ' . $configFile);
            error_log('Konfigurationsinhalt: ' . substr($configContent, 0, 500) . '...');
            return false;
        }
        
        debugLog('Konfigurationsdatei erfolgreich geschrieben: ' . $configFile . ' (' . $result . ' Bytes)');
        
        // Einfacher Inhalt zur direkten Überprüfung
        error_log('Erste 500 Zeichen der Konfiguration: ' . substr($configContent, 0, 500));
        
        // Permissions auf die Datei setzen
        chmod($configFile, 0644);

        // Prüfen, ob die Datei lesbar ist
        if (!is_readable($configFile)) {
            debugLog('Warnung: Konfigurationsdatei ist nicht lesbar');
        } else {
            debugLog('Konfigurationsdatei ist lesbar');
        }
        
        return true;
    } catch (Exception $e) {
        debugLog('Exception beim Erstellen der Konfiguration: ' . $e->getMessage());
        return false;
    }
}

// Funktion zum Erstellen der Datenbankdateien und -verzeichnisse
function setupDatabase() {
    $database = getSetupProgress(3);
    
    // Datenverzeichnis erstellen
    if (!file_exists('../data')) {
        mkdir('../data', 0755, true);
    }
    
    // Logverzeichnis erstellen
    if (!file_exists('../data/logs')) {
        mkdir('../data/logs', 0755, true);
    }
    
    // SQLite-Datei erstellen, wenn SQLite ausgewählt wurde
    if ($database['type'] === 'sqlite') {
        $dbFile = '../' . $database['file'];
        $dbDir = dirname($dbFile);
        
        if (!file_exists($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Leere SQLite-Datei erstellen
        if (!file_exists($dbFile)) {
            touch($dbFile);
            chmod($dbFile, 0666);
        }
    }
    
    return true;
}

// Alle Formulardaten zu Debugging-Zwecken ausgeben
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugLog('POST-Daten: ' . json_encode($_POST));
}

// Verarbeitung der Formulareinreichungen für jeden Schritt
// Dies geschieht VOR jeglicher HTML-Ausgabe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Schritt 1: Willkommen
    if ($currentStep === 1) {
        if (isset($_POST['language'])) {
            $_SESSION['setup_language'] = $_POST['language'];
            $_SESSION['setup_completed_steps'][1] = true;
            header('Location: ?step=2');
            exit;
        }
    }
    
    // Schritt 2: Systemanforderungen
    if ($currentStep === 2) {
        // Prüfen, ob alle kritischen Anforderungen erfüllt sind
        $criticalRequirementsMet = true;
        
        // PHP-Version
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $criticalRequirementsMet = false;
        }
        
        // PDO-Extension
        if (!extension_loaded('pdo')) {
            $criticalRequirementsMet = false;
        }
        
        // Dateiberechtigungen
        if (!is_writable('../') || (file_exists('../config') && !is_writable('../config'))) {
            $criticalRequirementsMet = false;
        }
        
        // Datenverzeichnis
        if (is_dir('../data') && !is_writable('../data')) {
            $criticalRequirementsMet = false;
        }
        
        // cURL-Extension
        if (!extension_loaded('curl')) {
            $criticalRequirementsMet = false;
        }
        
        // JSON-Extension
        if (!extension_loaded('json')) {
            $criticalRequirementsMet = false;
        }
        
        if ($criticalRequirementsMet) {
            $_SESSION['setup_completed_steps'][2] = true;
            header('Location: ?step=3');
            exit;
        }
    }
    
    // Schritt 3: Datenbank-Einrichtung
    if ($currentStep === 3) {
        $dbType = $_POST['db_type'] ?? '';
        if (!empty($dbType)) {
            $dbData = [
                'type' => $dbType
            ];
            
            if ($dbType === 'sqlite') {
                $dbData['file'] = $_POST['sqlite_file'] ?? 'data/mtwi.sqlite';
            } else {
                $dbData['host'] = $_POST['mysql_host'] ?? 'localhost';
                $dbData['name'] = $_POST['mysql_database'] ?? 'mtwi';
                $dbData['user'] = $_POST['mysql_username'] ?? '';
                $dbData['pass'] = $_POST['mysql_password'] ?? '';
            }
            
            // Datenbankverbindung testen
            $connectionSuccess = false;
            
            try {
                if ($dbType === 'sqlite') {
                    $dbPath = '../' . $dbData['file'];
                    $dbDir = dirname($dbPath);
                    
                    // Verzeichnis erstellen, falls es nicht existiert
                    if (!file_exists($dbDir)) {
                        mkdir($dbDir, 0755, true);
                    }
                    
                    // Verbindung testen
                    $pdo = new PDO('sqlite:' . $dbPath);
                    $connectionSuccess = true;
                } else {
                    // MySQL-Verbindung testen
                    $dsn = "mysql:host={$dbData['host']};dbname={$dbData['name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbData['user'], $dbData['pass']);
                    $connectionSuccess = true;
                }
            } catch (PDOException $e) {
                $errorMessage = "Datenbankverbindung fehlgeschlagen: " . $e->getMessage();
            }
            
            if ($connectionSuccess) {
                // Debug-Ausgabe vor dem Speichern
                debugLog('Speichere Datenbankdaten: ' . json_encode($dbData));
                
                // Fortschritt speichern
                saveSetupProgress(3, $dbData);
                
                // Prüfen, ob die Daten gespeichert wurden
                $savedData = getSetupProgress(3);
                debugLog('Gespeicherte Datenbankdaten: ' . ($savedData ? json_encode($savedData) : 'null'));
                
                // Weiterleiten zum nächsten Schritt
                header('Location: ?step=4');
                exit;
            }
        }
    }
    
    // Schritt 4: Server-Konfiguration
    if ($currentStep === 4) {
        if (isset($_POST['chat_url']) && isset($_POST['chat_port'])) {
            $serverData = [
                'chat_username' => $_POST['chat_username'] ?? 'admin',
                'chat_url' => 'http://' . rtrim(str_replace('http://', '', $_POST['chat_url']), '/'),
                'chat_port' => $_POST['chat_port'] ?? '',
                'chat_password' => $_POST['chat_password'] ?? '',
                'steam_api_key' => $_POST['steam_api_key'] ?? '',
                'api_url' => !empty($_POST['api_url']) ? 'http://' . rtrim(str_replace('http://', '', $_POST['api_url']), '/') : '',
                'api_password' => $_POST['api_password'] ?? ''
            ];
            
            // Debug-Ausgabe vor dem Speichern
            debugLog('Speichere Serverdaten: ' . json_encode($serverData));
            
            // Fortschritt speichern
            saveSetupProgress(4, $serverData);
            
            // Prüfen, ob die Daten gespeichert wurden
            $savedData = getSetupProgress(4);
            debugLog('Gespeicherte Serverdaten: ' . ($savedData ? json_encode($savedData) : 'null'));
            
            // Weiterleiten zum nächsten Schritt
            header('Location: ?step=5');
            exit;
        }
    }
    
    // Schritt 5: Admin-Konto
    if ($currentStep === 5) {
        // Validierung
        $errors = [];
        
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $passwordConfirm = trim($_POST['password_confirm'] ?? '');
        $language = $_POST['language'] ?? 'de';
        $theme = $_POST['theme'] ?? 'light';
        
        if (empty($username)) {
            $errors[] = "Benutzername darf nicht leer sein.";
        }
        
        if (empty($password)) {
            $errors[] = "Passwort darf nicht leer sein.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Das Passwort muss mindestens 8 Zeichen lang sein.";
        }
        
        if ($password !== $passwordConfirm) {
            $errors[] = "Die Passwörter stimmen nicht überein.";
        }
        
        if (empty($errors)) {
            // Daten speichern
            $adminData = [
                'username' => $username,
                'password' => $password, // Wird später gehasht
                'language' => $language,
                'theme' => $theme
            ];
            
            // Debug-Ausgabe vor dem Speichern
            debugLog('Speichere Admin-Daten: ' . json_encode($adminData));
            
            saveSetupProgress(5, $adminData);
            
            // Prüfen, ob die Daten gespeichert wurden
            $savedData = getSetupProgress(5);
            debugLog('Gespeicherte Admin-Daten: ' . ($savedData ? json_encode($savedData) : 'null'));
            
            // Weiterleiten zum nächsten Schritt
            header('Location: ?step=6');
            exit;
        }
    }
    
    // Schritt 6: Abschluss
    if ($currentStep === 6) {
        // Installation durchführen
        try {
            // 1. Verzeichnisse erstellen
            if (!file_exists('../data')) {
                mkdir('../data', 0755, true);
            }
            
            if (!file_exists('../data/logs')) {
                mkdir('../data/logs', 0755, true);
            }
            
            // 2. Datenbank einrichten
            setupDatabase();
            
            // 3. Konfigurationsdatei schreiben
            if (createConfig()) {
                // 4. Installation abgeschlossen markieren
                $_SESSION['installation_success'] = true;
                
                debugLog('Installation erfolgreich abgeschlossen');
                
                // Die Konfigurationsdatei sollte bereits das setup_completed = true Flag haben
                // Wir prüfen trotzdem, ob die Datei existiert und korrekt geschrieben wurde
                if (file_exists('../config/config.php')) {
                    debugLog('Konfigurationsdatei existiert');
                } else {
                    debugLog('Warnung: Konfigurationsdatei nicht gefunden nach Installation');
                    // Versuchen, sie erneut zu schreiben
                    createConfig();
                }
            } else {
                $_SESSION['error_message'] = "Fehler beim Schreiben der Konfigurationsdatei.";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Fehler bei der Installation: " . $e->getMessage();
        }
    }
}

// Schritt-Datei laden
$stepFile = $steps[$currentStep]['file'];

// Funktionen wurden in den Abschnitt PRE-RENDERING PROZESSIERUNG verschoben

// HTML-Header
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTWI Setup - <?php echo $steps[$currentStep]['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            padding-top: 20px;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 60px;
            position: relative;
            width: 100%;
        }
        .progress-steps:before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        .step-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 16%; /* 100% / 6 steps */
            position: relative;
        }
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 30px;
            height: 30px;
            line-height: 30px;
            background-color: #e0e0e0;
            color: #666;
            border-radius: 50%;
            font-weight: bold;
        }
        .step.active {
            background-color: #007bff;
            color: #fff;
        }
        .step.completed {
            background-color: #28a745;
            color: #fff;
        }
        .step-title {
            font-size: 12px;
            text-align: center;
            margin-top: 10px;
            white-space: nowrap;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .language-selector {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <h1 class="mb-4 text-center">
                <i class="bi bi-gear-fill me-2"></i>
                Motor Town Web Interface
            </h1>
            <h2 class="text-center mb-4">Setup-Assistent</h2>
            
            <!-- Sprachauswahl -->
            <div class="language-selector">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-globe"></i> <?php echo $languages[$language]; ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                        <?php foreach ($languages as $code => $name): ?>
                        <li><a class="dropdown-item <?php echo $code === $language ? 'active' : ''; ?>" href="?language=<?php echo $code; ?>&step=<?php echo $currentStep; ?>"><?php echo $name; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Fortschrittsschritte -->
            <div class="progress-steps">
                <?php foreach ($steps as $stepNumber => $step): ?>
                    <?php
                        $stepClass = '';
                        if ($stepNumber === $currentStep) {
                            $stepClass = 'active';
                        } elseif (isStepCompleted($stepNumber)) {
                            $stepClass = 'completed';
                        }
                    ?>
                    <div class="step-wrapper">
                        <div class="step <?php echo $stepClass; ?>"><?php echo $stepNumber; ?></div>
                        <div class="step-title"><?php echo $step['title']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Inhalt des aktuellen Schritts -->
            <div class="step-content">
                <?php include($stepFile); ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript für den Setup-Assistenten
        document.addEventListener('DOMContentLoaded', function() {
            // Form-Validierung aktivieren
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>
</html>
