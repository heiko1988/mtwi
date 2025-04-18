<?php
/**
 * Motor Town Web Interface (MTWI) - Ban Management Script
 * 
 * Dieses Skript wird regelmäßig per Cron ausgeführt, um:
 * 1. Abgelaufene Bans zu entfernen
 * 2. Ausstehende Bans für Offline-Spieler zu aktivieren, wenn sie online kommen
 */

// Verzeichnispfad bestimmen und initialisieren
$scriptPath = __DIR__;
$rootPath = dirname($scriptPath);
require_once $rootPath . '/includes/cron_init.php';

// Log-Verzeichnis sicherstellen
$dataDir = $rootPath . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// Log-Funktion
function cronLog($message) {
    $logLine = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    // Stelle sicher, dass das Verzeichnis existiert und beschreibbar ist
    if (!is_writable(__DIR__)) {
        chmod(__DIR__, 0777);
    }
    
    file_put_contents(__DIR__ . '/ban_cron.log', $logLine, FILE_APPEND);
    echo $logLine; // Auch für manuelle Ausführung ausgeben
}

cronLog("========== Ban Management Cron gestartet ==========");

// API-Verfügbarkeit prüfen
$playerCount = $apiClient->getPlayerCount();
if ($playerCount === false) {
    cronLog("WARNUNG: API nicht erreichbar - versuche es später erneut: " . $apiClient->getLastError());
    cronLog("API-URL: {$config['api']['url']}");
    exit(1); // Frühzeitig beenden, wenn API nicht verfügbar ist
} else {
    cronLog("API-Verbindung erfolgreich. Aktuell " . $playerCount . " Spieler online.");
}

// 1. Abgelaufene Bans entfernen
$expiredBans = $db->getExpiredBans();

if (count($expiredBans) > 0) {
    cronLog("Entferne " . count($expiredBans) . " abgelaufene Bans");
    
    foreach ($expiredBans as $ban) {
        // Korrekte ID-Behandlung (unique_id statt steam_id verwenden)
        $uniqueId = isset($ban['unique_id']) ? $ban['unique_id'] : (isset($ban['steam_id']) ? $ban['steam_id'] : 'unbekannt');
        
        $result = $db->removeBan($ban['id']);
        
        if ($result) {
            cronLog("Ban für Unique ID " . $uniqueId . " wurde entfernt (abgelaufen)");
            logActivity('info', 'Ban automatisch entfernt (abgelaufen)', 'Unique ID: ' . $uniqueId);
            
            // Stelle sicher, dass der Ban auch auf dem Server entfernt wird (doppelte Sicherheit)
            if ($uniqueId !== 'unbekannt') {
                $apiResult = $apiClient->unbanPlayer($uniqueId);
                cronLog("API-Unban für abgelaufenen Ban: " . ($apiResult ? "Erfolgreich" : "Fehlgeschlagen: " . $apiClient->getLastError()));
            }
        } else {
            cronLog("FEHLER: Konnte Ban für Unique ID " . $uniqueId . " nicht entfernen");
        }
    }
} else {
    cronLog("Keine abgelaufenen Bans gefunden");
}

// 2. Ausstehende Bans für Online-Spieler aktivieren
$pendingBans = $db->getPendingBans();

if (count($pendingBans) > 0) {
    cronLog("Prüfe " . count($pendingBans) . " ausstehende Bans");
    
    // Aktive Spielerliste abrufen
    $playerList = $apiClient->getPlayerList();
    
    if ($playerList) {
        // Sammle alle unique_ids von online Spielern
        $onlineUniqueIds = [];
        foreach ($playerList as $player) {
            if (isset($player['unique_id'])) {
                $onlineUniqueIds[] = $player['unique_id'];
            }
        }
        
        cronLog("Anzahl online Spieler: " . count($onlineUniqueIds));
        
        foreach ($pendingBans as $ban) {
            // Prüfen, ob der Spieler mit ausstehendem Ban online ist
            if (in_array($ban['unique_id'], $onlineUniqueIds)) {
                cronLog("Spieler mit Unique ID " . $ban['unique_id'] . " ist online, aktiviere Ban");
                
                // Ban auf dem Server aktivieren
                // Korrigiere die Parameterreihenfolge - sie muss ($uniqueId, $duration, $reason) sein
                $banResult = $apiClient->banPlayer($ban['unique_id'], $ban['duration'], $ban['reason']);
                
                cronLog("  - API-Ban-Anfrage: unique_id={$ban['unique_id']}, duration={$ban['duration']}, reason={$ban['reason']}");
                
                if ($banResult) {
                    // Ban-Status in der Datenbank aktualisieren
                    $db->activatePendingBan($ban['id']);
                    
                    cronLog("Ban für Unique ID " . $ban['unique_id'] . " wurde aktiviert");
                    logActivity('info', 'Ausstehender Ban aktiviert', 'Unique ID: ' . $ban['unique_id']);
                } else {
                    cronLog("FEHLER: Konnte Ban für Unique ID " . $ban['unique_id'] . " nicht aktivieren: " . $apiClient->getLastError());
                }
            }
        }
    } else {
        cronLog("FEHLER: Konnte Spielerliste nicht abrufen: " . $apiClient->getLastError());
    }
} else {
    cronLog("Keine ausstehenden Bans gefunden");
}

// 3. Ban-Liste mit dem Server synchronisieren
cronLog("Synchronisiere Ban-Liste mit dem Server");

// 3.1 Alle Bans aus der Datenbank abrufen
$dbBans = [];
$activeBans = $db->getActiveBans();

// Detaillierte Logging der aktiven Bans in der Datenbank
cronLog("Aktive Bans in der Datenbank: " . count($activeBans));
foreach ($activeBans as $ban) {
    cronLog("  - DB Ban ID: {$ban['id']}, Unique ID: {$ban['unique_id']}, Name: {$ban['player_name']}");
    $dbBans[$ban['unique_id']] = $ban;
}

// 3.2 Aktuelle Ban-Liste vom Server abrufen
$serverBanList = $apiClient->getBanList();

if ($serverBanList === false) {
    cronLog("FEHLER: Konnte Server-Ban-Liste nicht abrufen: " . $apiClient->getLastError());
} else {
    $serverBans = [];
    
    // Detaillierte Logging der Server-Bans
    cronLog("Server-Bans: " . count($serverBanList));
    foreach ($serverBanList as $ban) {
        if (isset($ban['unique_id'])) {
            $uniqueId = $ban['unique_id'];
            $playerName = isset($ban['player_name']) ? $ban['player_name'] : 'Unbekannt';
            $duration = isset($ban['duration']) ? $ban['duration'] : 0;
            $reason = isset($ban['reason']) ? $ban['reason'] : '';
            
            // Debug-Logging für Fehlersuche
            cronLog("  - Server Ban: Unique ID: {$uniqueId}, Name: {$playerName}, Duration: {$duration}, Reason: {$reason}");
            
            $serverBans[$uniqueId] = [
                'unique_id' => $uniqueId,
                'player_name' => $playerName,
                'duration' => $duration,
                'reason' => $reason
            ];
        }
    }

    cronLog("Lokale Datenbank: " . count($dbBans) . " Bans, Server: " . count($serverBans) . " Bans");

    // 3.3 Bans entfernen, die nicht mehr auf dem Server sind
    $bansToRemove = array_diff_key($dbBans, $serverBans);
    
    cronLog("Zu prüfende Bans: " . count($bansToRemove));
    foreach ($bansToRemove as $uniqueId => $ban) {
        // Nur entfernen, wenn der Ban nicht mehr aktiv ist
        if (!$ban['is_active']) {
            cronLog("Entferne nicht aktiven Ban für Unique ID {$uniqueId} aus der Datenbank");
            $result = $db->removeActiveBanByUniqueId($uniqueId);
            if ($result) {
                cronLog("Ban für Spieler mit ID " . $uniqueId . " wurde aus der Datenbank entfernt, da er nicht mehr aktiv ist");
                logActivity('info', 'Ban automatisch entfernt (nicht aktiv)', 'Unique ID: ' . $uniqueId);
            } else {
                cronLog("FEHLER: Konnte Ban für Spieler mit ID " . $uniqueId . " nicht aus der Datenbank entfernen");
            }
        } else {
            // Wenn Ban aktiv ist, aber nicht mehr auf dem Server, dann:
            // 1. Prüfe ob Ban abgelaufen ist
            $currentTime = time();
            $isExpired = ($ban['duration'] > 0 && $ban['expires_at'] < $currentTime);
            
            if ($isExpired) {
                // Wenn abgelaufen, entferne Ban
                $result = $db->removeActiveBanByUniqueId($uniqueId);
                if ($result) {
                    cronLog("Ban für Spieler mit ID " . $uniqueId . " wurde aus der Datenbank entfernt, da er abgelaufen ist");
                    logActivity('info', 'Ban automatisch entfernt (abgelaufen)', 'Unique ID: ' . $uniqueId);
                } else {
                    cronLog("FEHLER: Konnte Ban für Spieler mit ID " . $uniqueId . " nicht aus der Datenbank entfernen");
                }
            } else {
                // Wenn noch nicht abgelaufen, Ban nicht entfernen
                cronLog("Aktiven Ban für Unique ID {$uniqueId} nicht entfernt, da er noch aktiv ist");
            }
        }
    }

    // 3.4 Bans aktualisieren, die auf dem Server sind
    foreach ($serverBans as $uniqueId => $serverBan) {
        // Prüfe, ob Ban bereits in der Datenbank existiert
        if (isset($dbBans[$uniqueId])) {
            // Aktualisiere bestehenden Ban
            $dbBan = $dbBans[$uniqueId];
            
            // Nur aktualisieren, wenn Ban aktiv ist UND Server-Daten vorhanden sind
            if ($dbBan['is_active'] && (!empty($serverBan['reason']) || $serverBan['duration'] > 0)) {
                // Nur aktualisieren, wenn Ban noch aktiv ist
                $stmt = $db->db->prepare("UPDATE bans 
                    SET player_name = ?, 
                        reason = ?,
                        duration = ?,
                        expires_at = CASE 
                            WHEN duration > 0 THEN UNIX_TIMESTAMP() + duration 
                            ELSE 0 
                        END
                    WHERE unique_id = ?");
                
                $result = $stmt->execute([
                    $serverBan['player_name'] ?? $dbBan['player_name'],
                    $serverBan['reason'] ?? $dbBan['reason'],
                    $serverBan['duration'] ?? $dbBan['duration'],
                    $uniqueId
                ]);
                
                if ($result) {
                    cronLog("Ban für Spieler mit ID " . $uniqueId . " wurde aktualisiert");
                    logActivity('info', 'Ban aktualisiert', 'Unique ID: ' . $uniqueId);
                } else {
                    cronLog("FEHLER: Konnte Ban für Spieler mit ID " . $uniqueId . " nicht aktualisieren");
                }
            } else {
                // Wenn Ban nicht aktiv ist oder Server-Daten leer sind, nicht aktualisieren
                cronLog("Ban für Spieler mit ID " . $uniqueId . " wurde nicht aktualisiert, da er nicht aktiv ist oder Server-Daten leer sind");
            }
        } else {
            // Prüfe ob es einen ausstehenden Ban für diesen Spieler gibt
            $pendingBan = $db->getPendingBanByUniqueId($uniqueId);
            if ($pendingBan) {
                // Wenn ja, aktiviere ihn
                $db->activatePendingBan($pendingBan['id']);
                cronLog("Ausstehender Ban für Spieler mit ID " . $uniqueId . " wurde aktiviert");
                logActivity('info', 'Ausstehender Ban aktiviert', 'Unique ID: ' . $uniqueId);
            } else {
                // Wenn nein, erstelle neuen Ban
                $id = $db->addBan(
                    $uniqueId,
                    $serverBan['player_name'] ?? 'Unbekannt',
                    $serverBan['reason'] ?? 'Kein Grund angegeben',
                    $serverBan['duration'] ?? 0,
                    true
                );
                
                if ($id) {
                    cronLog("Ban für Spieler mit ID " . $uniqueId . " wurde zur Datenbank hinzugefügt");
                    logActivity('info', 'Ban hinzugefügt', 'Unique ID: ' . $uniqueId);
                } else {
                    cronLog("FEHLER: Konnte Ban für Spieler mit ID " . $uniqueId . " nicht zur Datenbank hinzufügen");
                }
            }
        }
    }

    cronLog("Synchronisierung abgeschlossen");
}

// 4. Spielerhistorie aktualisieren
cronLog("Aktualisiere Spielerhistorie");

// 4.1 Aktive Spieler abrufen
$playerList = $apiClient->getPlayerList();

if ($playerList === false) {
    cronLog("FEHLER: Konnte Spielerliste nicht abrufen: " . $apiClient->getLastError());
} else {
    cronLog("Anzahl aktive Spieler: " . count($playerList));
    
    // 4.2 Für jeden aktiven Spieler den Eintrag aktualisieren
    foreach ($playerList as $player) {
        if (isset($player['unique_id']) && isset($player['name'])) {
            // Prüfen, ob der Spieler bereits in der Datenbank ist
            $existingPlayer = $db->getPlayerByUniqueId($player['unique_id']);
            
            $now = time();
            
            if ($existingPlayer) {
                // Spieler aktualisieren (last_seen)
                $db->updatePlayerStatus($player['unique_id'], $player['name'], $now, 'online');
            } else {
                // Neuen Spieler hinzufügen
                $db->addPlayerHistory($player['unique_id'], $player['name'], $now, $now, 'online');
            }
        }
    }
    
    // 4.3 Offline-Spieler markieren (Spieler, die nicht mehr online sind)
    // Diese Funktion müsste in der Datenbank-Klasse implementiert werden
    $db->markOfflinePlayers($playerList);
    
    cronLog("Spielerhistorie wurde aktualisiert");
}

cronLog("========== Ban Management Cron beendet ==========");
