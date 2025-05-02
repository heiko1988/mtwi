<?php
/**
 * Hilfsskript zum Überprüfen und Einfügen von Testspielerdaten
 */

// Initialisierung
require_once 'includes/init.php';
require_once MTWI_ROOT . '/includes/database_players.php';

echo "<h1>Spielerdaten-Prüfung</h1>";

try {
    // Datenbank-Verbindung herstellen
    $dbPlayers = new DatabasePlayers($config['database']);
    
    // Spielerliste abrufen
    $players = $dbPlayers->getAllPlayers();
    
    echo "<h2>Vorhandene Spieler: " . count($players) . "</h2>";
    
    if (count($players) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Steam-ID</th><th>Erster Login</th><th>Letzter Login</th></tr>";
        
        foreach ($players as $player) {
            echo "<tr>";
            echo "<td>" . $player['id'] . "</td>";
            echo "<td>" . $player['name'] . "</td>";
            echo "<td>" . $player['steam_id'] . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $player['first_seen']) . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $player['last_seen']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Keine Spielerdaten gefunden. Testdaten werden erstellt...</p>";
        
        // Testdaten einfügen
        $testPlayers = [
            ['name' => 'Max Mustermann', 'steam_id' => '76561198123456789'],
            ['name' => 'Erika Musterfrau', 'steam_id' => '76561198987654321'],
            ['name' => 'John Doe', 'steam_id' => '76561198555555555'],
            ['name' => 'Jane Smith', 'steam_id' => '76561198444444444'],
            ['name' => 'Bob Johnson', 'steam_id' => '76561198333333333']
        ];
        
        foreach ($testPlayers as $player) {
            $result = $dbPlayers->addOrUpdatePlayer($player['name'], $player['steam_id']);
            echo "<p>Spieler hinzugefügt: " . $player['name'] . " (Ergebnis: $result)</p>";
            
            // Level setzen
            if ($result === 'added') {
                // Spieler nochmal holen, um die ID zu bekommen
                $playerData = $dbPlayers->getPlayerBySteamId($player['steam_id']);
                if ($playerData) {
                    $playerId = $playerData['id'];
                    
                    // Zufällige Level setzen
                    $dbPlayers->updatePlayerLevel($playerId, 'taxi', rand(1, 10));
                    $dbPlayers->updatePlayerLevel($playerId, 'bus', rand(1, 10));
                    $dbPlayers->updatePlayerLevel($playerId, 'wrecker', rand(1, 10));
                    $dbPlayers->updatePlayerLevel($playerId, 'police', rand(1, 10));
                    
                    echo "<p>Level für Spieler ID $playerId gesetzt.</p>";
                    
                    // Aktivitäten hinzufügen
                    $activities = ['login', 'entered_vehicle', 'exited_vehicle'];
                    foreach ($activities as $activity) {
                        $dbPlayers->addPlayerActivity($playerId, $activity, 'Test Vehicle', '123');
                    }
                    echo "<p>Aktivitäten für Spieler ID $playerId hinzugefügt.</p>";
                }
            }
        }
        
        // Nochmal Spielerliste abrufen
        $players = $dbPlayers->getAllPlayers();
        
        echo "<h2>Spieler nach dem Hinzufügen: " . count($players) . "</h2>";
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Steam-ID</th><th>Erster Login</th><th>Letzter Login</th></tr>";
        
        foreach ($players as $player) {
            echo "<tr>";
            echo "<td>" . $player['id'] . "</td>";
            echo "<td>" . $player['name'] . "</td>";
            echo "<td>" . $player['steam_id'] . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $player['first_seen']) . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $player['last_seen']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2>Fehler:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<h3>Stack-Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
