<?php
/**
 * Motor Town Web Interface (MTWI) - Initialisierung
 */

// Fehlerberichterstattung aktivieren (für die Entwicklung)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sitzung starten
session_start();

// Konstanten definieren
define('MTWI_ROOT', dirname(__DIR__));
define('MTWI_VERSION', '1.0.0');

// Konfiguration laden
$configFile = MTWI_ROOT . '/config/config.php';
if (!file_exists($configFile)) {
    // Falls wir uns nicht bereits auf der Setup-Seite befinden, weiterleiten
    if (!isset($_GET['page']) || $_GET['page'] !== 'setup') {
        header('Location: index.php?page=setup');
        exit;
    }
} else {
    require_once $configFile;
}

// Klassen laden
if (!file_exists($configFile)) {
    // Falls wir uns nicht bereits auf der Setup-Seite befinden, weiterleiten
    if (!isset($_GET['page']) || $_GET['page'] !== 'setup') {
        header('Location: index.php?page=setup');
        exit;
    }
} else {
    require_once $configFile;

    // Klassen laden
    require_once MTWI_ROOT . '/includes/api_client.php';
    require_once MTWI_ROOT . '/includes/database.php';
    require_once MTWI_ROOT . '/includes/functions.php';

    // Datenbank initialisieren
    if (!isset($config['database']) || empty($config['database'])) {
        // Noch nicht konfiguriert, weiterleiten zum Setup
        if (!isset($_GET['page']) || $_GET['page'] !== 'setup') {
            header('Location: index.php?page=setup');
            exit;
        }
    } else {
        $db = new Database($config['database']);
    }

    // API-Client initialisieren
    $apiClient = null;
    if (!empty($config['api']['url']) && !empty($config['api']['password'])) {
        $apiClient = new ApiClient($config['api']['url'], $config['api']['password']);
    }

    // Sprache laden
    $currentLang = isset($_SESSION['language']) ? $_SESSION['language'] : $config['settings']['default_language'];
    $langFile = MTWI_ROOT . '/lang/' . $currentLang . '.php';

    if (!file_exists($langFile)) {
        // Fallback auf Standardsprache
        $currentLang = $config['settings']['default_language'];
        $langFile = MTWI_ROOT . '/lang/' . $currentLang . '.php';
    }

    require_once $langFile;

    // Theme-Einstellungen
    $currentTheme = isset($_SESSION['theme']) ? $_SESSION['theme'] : $config['settings']['default_theme'];

    // Seite bestimmen
    $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

    // Einrichtungsassistent anzeigen, wenn nötig
    if ((!isset($config['settings']['setup_completed']) || !$config['settings']['setup_completed']) && $currentPage != 'setup') {
        header('Location: index.php?page=setup');
        exit;
    }
}

// Prüfen, ob Benutzer angemeldet ist
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Zugriffsschutz
$publicPages = ['login', 'setup', 'ajax_handler']; // Seiten ohne Login-Pflicht
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';


// Anmeldung erfordern, wenn nötig
if (!isLoggedIn() && !in_array($currentPage, $publicPages)) {
    header('Location: index.php?page=login');
    exit;
}
