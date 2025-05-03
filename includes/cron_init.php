<?php
/**
 * Motor Town Web Interface (MTWI) - Cron Initialisierung
 * 
 * Diese Datei initialisiert das System für Cron-Jobs ohne Sitzungsvariablen
 */

// Fehlerberichterstattung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Konstanten definieren
define('MTWI_ROOT', dirname(__DIR__));
define('MTWI_VERSION', '1.0.0');

// Konfiguration laden
$configFile = MTWI_ROOT . '/config/config.php';
if (!file_exists($configFile)) {
    die('Konfigurationsdatei nicht gefunden. Bitte führen Sie die Installation durch.');
}
require_once $configFile;

// Klassen laden
require_once MTWI_ROOT . '/includes/api_client.php';
require_once MTWI_ROOT . '/includes/database.php';
require_once MTWI_ROOT . '/includes/functions.php';

// Sprache direkt aus Konfiguration laden
$currentLang = $config['settings']['default_language'];
$langFile = MTWI_ROOT . '/lang/' . $currentLang . '.php';

if (!file_exists($langFile)) {
    die('Sprachdatei nicht gefunden: ' . $langFile);
}

require_once $langFile;

// Theme-Einstellungen direkt aus Konfiguration laden
$currentTheme = $config['settings']['default_theme'];

// API und Datenbank initialisieren
$apiClient = null;
if (!empty($config['api']['url']) && !empty($config['api']['password'])) {
    $apiClient = new ApiClient($config['api']['url'], $config['api']['password']);
}

$db = new Database($config['database']);
