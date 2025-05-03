<?php
/**
 * Datenbankoptimierungs-Script für MTWI3
 * 
 * Dieses Script führt verschiedene Optimierungen für die Datenbank durch,
 * insbesondere für die Spielerdaten, um die Performance zu verbessern.
 */

// Fehlerbehandlung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Startzeit messen
$startTime = microtime(true);
echo "Starte Datenbankoptimierung...\n";

// Konfiguration und Datenbankklassen laden
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/database_players.php';

// Verbindung zur Datenbank herstellen
$dbPlayers = new DatabasePlayers($config['database']);
$db = $dbPlayers->getConnection();
$dbType = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

echo "Datenbanktyp: " . $dbType . "\n";

// 1. Überprüfung der Indizes
echo "\n--- Überprüfung und Erstellung von Indizes ---\n";

// Wir setzen einige Datenbankoptimierungen
if ($dbType == 'sqlite') {
    // Für SQLite
    $db->exec("PRAGMA journal_mode = WAL");
    $db->exec("PRAGMA synchronous = NORMAL");
    $db->exec("PRAGMA temp_store = MEMORY");
    $db->exec("PRAGMA cache_size = 10000");
    
    echo "SQLite-Parameter optimiert.\n";
    
    // Aktuelle Indizes überprüfen
    $stmt = $db->query("PRAGMA index_list('players')");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Vorhandene Indizes für players-Tabelle:\n";
    $hasPlayersSteamIdIndex = false;
    foreach ($indexes as $index) {
        echo "- " . $index['name'] . "\n";
        if ($index['name'] === 'idx_players_steam_id') {
            $hasPlayersSteamIdIndex = true;
        }
    }
    
    // Spieler-Indizes erstellen oder neu erstellen
    if (!$hasPlayersSteamIdIndex) {
        echo "Index für players.steam_id wird erstellt...\n";
        $db->exec("CREATE INDEX IF NOT EXISTS idx_players_steam_id ON players(steam_id)");
    }
    
    // Aktivitäten-Indizes überprüfen
    $stmt = $db->query("PRAGMA index_list('player_activities')");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nVorhandene Indizes für player_activities-Tabelle:\n";
    $hasActivitiesPlayerIdIndex = false;
    foreach ($indexes as $index) {
        echo "- " . $index['name'] . "\n";
        if ($index['name'] === 'idx_player_activities_player_id') {
            $hasActivitiesPlayerIdIndex = true;
        }
    }
    
    // Aktivitäten-Index erstellen oder neu erstellen
    if (!$hasActivitiesPlayerIdIndex) {
        echo "Index für player_activities.player_id wird erstellt...\n";
        $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_player_id ON player_activities(player_id)");
    }
    
    // Zusätzlichen Index für timestamp hinzufügen (für schnellere Abfragen nach Datum)
    echo "Index für player_activities.timestamp wird erstellt...\n";
    $db->exec("CREATE INDEX IF NOT EXISTS idx_player_activities_timestamp ON player_activities(timestamp)");
    
    // 2. Datenbankoptimierung durchführen
    echo "\n--- Datenbank-Optimierungen ---\n";
    $db->exec("ANALYZE");
    echo "Analyse-Statistiken wurden aktualisiert.\n";
    
    $db->exec("VACUUM");
    echo "Datenbank wurde bereinigt (VACUUM).\n";
} else {
    // Für MySQL/MariaDB
    echo "MySQL/MariaDB-Indizes werden überprüft...\n";
    
    // Prüfen und erstellen des players.steam_id Index
    $stmt = $db->query("SHOW INDEX FROM players WHERE Column_name = 'steam_id'");
    $hasPlayersSteamIdIndex = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hasPlayersSteamIdIndex) {
        echo "Index für players.steam_id wird erstellt...\n";
        $db->exec("CREATE INDEX idx_players_steam_id ON players(steam_id)");
    }
    
    // Prüfen und erstellen des player_activities.player_id Index
    $stmt = $db->query("SHOW INDEX FROM player_activities WHERE Column_name = 'player_id'");
    $hasActivitiesPlayerIdIndex = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hasActivitiesPlayerIdIndex) {
        echo "Index für player_activities.player_id wird erstellt...\n";
        $db->exec("CREATE INDEX idx_player_activities_player_id ON player_activities(player_id)");
    }
    
    // Zusätzlichen Index für timestamp
    $stmt = $db->query("SHOW INDEX FROM player_activities WHERE Column_name = 'timestamp'");
    $hasActivitiesTimestampIndex = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hasActivitiesTimestampIndex) {
        echo "Index für player_activities.timestamp wird erstellt...\n";
        $db->exec("CREATE INDEX idx_player_activities_timestamp ON player_activities(timestamp)");
    }
    
    // Tabellen optimieren
    echo "\nTabellen werden optimiert...\n";
    $db->exec("OPTIMIZE TABLE players, player_activities");
}

// 3. Beispielabfragen testen
echo "\n--- Performance-Tests ---\n";

// Test 1: Steam-ID-Abfrage
$start = microtime(true);
$stmt = $db->prepare("SELECT * FROM players WHERE steam_id = ?");
$stmt->execute(['76561198123456789']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$duration = microtime(true) - $start;
echo "Abfrage nach Steam-ID: " . number_format($duration * 1000, 2) . " ms\n";

// Test 2: Aktivitäten-Abfrage (falls Aktivitäten vorhanden)
$start = microtime(true);
$stmt = $db->query("SELECT COUNT(*) FROM player_activities");
$count = $stmt->fetchColumn();
$duration = microtime(true) - $start;
echo "Zählen aller Aktivitäten: " . number_format($duration * 1000, 2) . " ms (Anzahl: $count)\n";

// Test 3: Spielerliste
$start = microtime(true);
$stmt = $db->query("SELECT COUNT(*) FROM players");
$count = $stmt->fetchColumn();
$duration = microtime(true) - $start;
echo "Zählen aller Spieler: " . number_format($duration * 1000, 2) . " ms (Anzahl: $count)\n";

// Gesamtzeit ausgeben
$totalDuration = microtime(true) - $startTime;
echo "\nGesamtdauer der Optimierung: " . number_format($totalDuration, 2) . " Sekunden\n";
echo "Optimierung abgeschlossen!\n";
