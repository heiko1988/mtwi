<?php
/**
 * Motor Town Web Interface (MTWI) - Spielerübersicht
 * Zeigt Online- und Offline-Spieler in separaten Tabellen an
 */

// Berechtigungsprüfung einbinden
require_once 'includes/init.php';

// Prüfen, ob der Benutzer berechtigt ist, diese Seite zu sehen
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Prüfen, ob der Benutzer die Berechtigung für Spieleransicht hat
if (!hasPermission('player_view')) {
    header('Location: index.php?error=no_permission');
    exit;
}

// Spielerdaten vom API abrufen
function getPlayerData() {
    global $config;
    
    // Log-Datei für Debugging
    $logFile = __DIR__ . '/data/player_overview.log';
    $log = '[' . date('Y-m-d H:i:s') . '] Spielerübersicht aufgerufen';
    file_put_contents($logFile, $log . "\n", FILE_APPEND);
    
    $chatConfig = $config['chat_server'];
    $url = rtrim($chatConfig['url'], '/');
    $port = $chatConfig['port'];
    $username = $chatConfig['username'];
    $password = $chatConfig['password'];
    
    // API-URL für Spielerdaten
    $apiUrl = $url . ':' . $port . '/api/players';
    
    // HTTP-Kontext mit Basic-Auth
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'Authorization: Basic ' . base64_encode($username . ':' . $password),
            'timeout' => 5  // Timeout in Sekunden
        ]
    ];
    
    $context = stream_context_create($opts);
    $result = @file_get_contents($apiUrl, false, $context);
    
    if ($result === FALSE) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] FEHLER: Keine Verbindung zum Chat-Server möglich\n", FILE_APPEND);
        return getStaticTestPlayers(); // Testdaten als Fallback
    }
    
    // JSON dekodieren
    $data = json_decode($result, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['players']) || !is_array($data['players'])) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] FEHLER: Ungültiges JSON oder keine Spielerdaten\n", FILE_APPEND);
        return getStaticTestPlayers(); // Testdaten als Fallback
    }
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Erfolgreich " . count($data['players']) . " Spieler geladen\n", FILE_APPEND);
    return $data['players'];
}

// Testdaten für den Fall, dass keine Verbindung zum Server möglich ist
function getStaticTestPlayers() {
    return [
        [
            'id' => 1,
            'name' => 'TestSpieler',
            'steam_id' => '76561198123456789',
            'first_seen' => '01.04.2025 12:00:00',
            'last_seen' => '28.04.2025 19:35:00',
            'online' => true,
            'taxi_level' => 5,
            'bus_level' => 3,
            'wrecker_level' => 2,
            'police_level' => 1,
            'driver_level' => 8, 
            'truck_level' => 4,
            'company' => 'Fast Taxi GmbH'
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
            'driver_level' => 3,
            'truck_level' => 6,
            'company' => null
        ]
    ];
}

// Spielerdaten abrufen
$players = getPlayerData();

// Spieler nach Online-Status und Login-Zeit sortieren
$onlinePlayers = [];
$offlinePlayers = [];

foreach ($players as $player) {
    if (isset($player['online']) && $player['online'] === true) {
        $onlinePlayers[] = $player;
    } else {
        $offlinePlayers[] = $player;
    }
}

// Sortierung nach last_seen (neueste zuerst)
usort($onlinePlayers, function($a, $b) {
    $timeA = isset($a['last_seen']) ? strtotime($a['last_seen']) : 0;
    $timeB = isset($b['last_seen']) ? strtotime($b['last_seen']) : 0;
    return $timeB - $timeA; // Absteigend (neueste zuerst)
});

usort($offlinePlayers, function($a, $b) {
    $timeA = isset($a['last_seen']) ? strtotime($a['last_seen']) : 0;
    $timeB = isset($b['last_seen']) ? strtotime($b['last_seen']) : 0;
    return $timeB - $timeA; // Absteigend (neueste zuerst)
});

// Seitentitel
$pageTitle = 'Spielerübersicht';

// HTML-Header einbinden
include 'includes/header.php';
?>

<div class="container-fluid mt-3">
    <h1><?php echo $pageTitle; ?></h1>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Online Spieler (<?php echo count($onlinePlayers); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Steam ID</th>
                                    <th>Login-Zeit</th>
                                    <th>Taxi</th>
                                    <th>Bus</th>
                                    <th>Abschlepp</th>
                                    <th>Polizei</th>
                                    <th>Fahrer</th>
                                    <th>LKW</th>
                                    <th>Unternehmen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($onlinePlayers as $player): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($player['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($player['steam_id']); ?></td>
                                    <td><?php echo isset($player['last_seen']) ? htmlspecialchars($player['last_seen']) : 'Unbekannt'; ?></td>
                                    <td><?php echo isset($player['taxi_level']) ? $player['taxi_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['bus_level']) ? $player['bus_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['wrecker_level']) ? $player['wrecker_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['police_level']) ? $player['police_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['driver_level']) ? $player['driver_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['truck_level']) ? $player['truck_level'] : '0'; ?></td>
                                    <td><?php echo !empty($player['company']) ? htmlspecialchars($player['company']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($onlinePlayers)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">Keine Spieler online</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Offline Spieler (<?php echo count($offlinePlayers); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Steam ID</th>
                                    <th>Zuletzt gesehen</th>
                                    <th>Taxi</th>
                                    <th>Bus</th>
                                    <th>Abschlepp</th>
                                    <th>Polizei</th>
                                    <th>Fahrer</th>
                                    <th>LKW</th>
                                    <th>Unternehmen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($offlinePlayers as $player): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($player['name']); ?></td>
                                    <td><?php echo htmlspecialchars($player['steam_id']); ?></td>
                                    <td><?php echo isset($player['last_seen']) ? htmlspecialchars($player['last_seen']) : 'Unbekannt'; ?></td>
                                    <td><?php echo isset($player['taxi_level']) ? $player['taxi_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['bus_level']) ? $player['bus_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['wrecker_level']) ? $player['wrecker_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['police_level']) ? $player['police_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['driver_level']) ? $player['driver_level'] : '0'; ?></td>
                                    <td><?php echo isset($player['truck_level']) ? $player['truck_level'] : '0'; ?></td>
                                    <td><?php echo !empty($player['company']) ? htmlspecialchars($player['company']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($offlinePlayers)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">Keine Offline-Spieler gefunden</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// HTML-Footer einbinden
include 'includes/footer.php';
?>
