/**
 * Motor Town Web Interface (MTWI) - Spielerübersicht
 * Performance-optimierte Version mit Batch-Rendering und Datenbank-Integration
 * - Online-Spieler werden live vom Server abgefragt
 * - Offline-Spieler werden aus der Datenbank geladen (nur bei Bedarf)
 */
$(document).ready(function() {
    console.log('Players.js geladen - Performance-optimierte Version mit DB-Integration');

    // Status-Variablen für die Aktualisierung
    var updateInterval = null;
    var isUpdating = false;
    var isLoadingOffline = false;
    var lastUpdateTime = 0;
    var showOfflinePlayers = false; // Standardmäßig nur Online-Spieler zeigen
    
    // Cache für Spielerdaten (Online/Offline-Trennung)
    var onlinePlayers = [];
    var offlinePlayers = [];
    var playerCache = {};
    
    // Batch-Rendering Konfiguration
    var BATCH_SIZE = 40; // Spieler pro Batch
    
    // UI-Elemente für Tab-Navigation einfügen
    preparePlayerTabs();
    
    /**
     * Tab-System für Online- und Offline-Spieler vorbereiten
     */
    function preparePlayerTabs() {
        // Tabnavigation vor der Spielertabelle einfügen
        var tabHtml = '<div class="card-header pb-0 border-bottom-0">' +
            '<ul class="nav nav-tabs card-header-tabs" id="playerTabs">' +
            '    <li class="nav-item">' +
            '        <a class="nav-link active" id="online-tab" data-bs-toggle="tab" href="#online">Online-Spieler</a>' +
            '    </li>' +
            '    <li class="nav-item">' +
            '        <a class="nav-link" id="offline-tab" data-bs-toggle="tab" href="#offline">Offline-Spieler</a>' +
            '    </li>' +
            '</ul></div>';
        
        // Tab-Container erstellen
        var contentHtml = '<div class="tab-content" id="playerTabContent">' +
            '    <div class="tab-pane fade show active" id="online" role="tabpanel">' +
            '        <div class="table-responsive">' +
            '            <table class="table table-striped table-hover" id="onlinePlayerTable">' +
            '                <thead>' +
            '                    <tr>' +
            '                        <th>' + (typeof t === 'function' ? t('player_name') : 'Spielername') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('steam_id') : 'Steam ID') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('last_seen') : 'Zuletzt gesehen') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('taxi_level') : 'Taxi') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('bus_level') : 'Bus') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('wrecker_level') : 'Abschlepp') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('police_level') : 'Polizei') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('actions') : 'Aktionen') + '</th>' +
            '                    </tr>' +
            '                </thead>' +
            '                <tbody>' +
            '                    <tr>' +
            '                        <td colspan="8" class="text-center">' +
            '                            <div class="spinner-border spinner-border-sm" role="status"></div> ' +
            '                            ' + (typeof t === 'function' ? t('loading_player_data') : 'Lade Spielerdaten...') +
            '                        </td>' +
            '                    </tr>' +
            '                </tbody>' +
            '            </table>' +
            '        </div>' +
            '    </div>' +
            '    <div class="tab-pane fade" id="offline" role="tabpanel">' +
            '        <div class="d-flex justify-content-between align-items-center mb-2 mt-2 px-2">' +
            '            <span id="offlinePlayerCount">0 Offline-Spieler</span>' +
            '            <button class="btn btn-sm btn-secondary" id="loadOfflinePlayers">' +
            '                <i class="bi bi-database-down"></i> ' + (typeof t === 'function' ? t('load_offline_players') : 'Offline-Spieler laden') +
            '            </button>' +
            '        </div>' +
            '        <div class="table-responsive">' +
            '            <table class="table table-striped table-hover" id="offlinePlayerTable">' +
            '                <thead>' +
            '                    <tr>' +
            '                        <th>' + (typeof t === 'function' ? t('player_name') : 'Spielername') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('steam_id') : 'Steam ID') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('last_seen') : 'Zuletzt gesehen') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('taxi_level') : 'Taxi') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('bus_level') : 'Bus') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('wrecker_level') : 'Abschlepp') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('police_level') : 'Polizei') + '</th>' +
            '                        <th>' + (typeof t === 'function' ? t('actions') : 'Aktionen') + '</th>' +
            '                    </tr>' +
            '                </thead>' +
            '                <tbody>' +
            '                    <tr>' +
            '                        <td colspan="8" class="text-center">' + 
            '                            ' + (typeof t === 'function' ? t('click_to_load_offline') : 'Klicken Sie auf "Offline-Spieler laden", um Spieler anzuzeigen.') + 
            '                        </td>' +
            '                    </tr>' +
            '                </tbody>' +
            '            </table>' +
            '        </div>' +
            '    </div>' +
            '</div>';
        
        // Vorhandene Kartenstruktur anpassen
        $('.card-header').replaceWith(tabHtml);
        $('.table-responsive').replaceWith(contentHtml);
        
        // Event-Handler für Tabs
        $('#online-tab').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
            showOfflinePlayers = false;
        });
        
        $('#offline-tab').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
            showOfflinePlayers = true;
            
            // Offline-Spieler nur laden, wenn der Tab aktiv wird und sie nicht bereits geladen wurden
            if (offlinePlayers.length === 0 && !isLoadingOffline) {
                $('#loadOfflinePlayers').trigger('click');
            }
        });
        
        // Event-Handler für den "Offline-Spieler laden"-Button
        $('#loadOfflinePlayers').on('click', function() {
            loadOfflinePlayers();
        });
    }
    
    /**
     * Spielerdaten laden mit Performance-Optimierungen (nur Online-Spieler)
     */
    function loadPlayerData() {
        // Wenn bereits eine Aktualisierung läuft, abbrechen
        if (isUpdating) return;
        
        isUpdating = true;
        $('#onlinePlayerTable tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> ' + 
            (typeof t === 'function' ? t('loading_player_data') : 'Lade Online-Spieler...') + '</td></tr>');
        
        $.ajax({
            url: 'get_players.php',  // Standardmäßig nur Online-Spieler
            type: 'GET',
            dataType: 'json',
            data: {
                include_offline: false  // Explizit nur Online-Spieler anfordern
            },
            success: function(data) {
                isUpdating = false;
                lastUpdateTime = Date.now();
                
                // Direkt mit dem JSON-Objekt arbeiten
                if (data.success && Array.isArray(data.data)) {
                    // Online-Spieler verarbeiten
                    onlinePlayers = data.data.filter(function(player) {
                        return player.online === true || player.online === undefined;
                    });
                    
                    // Cache für Online-Spieler aufbauen
                    playerCache = {};
                    onlinePlayers.forEach(function(player) {
                        if (player.steam_id) {
                            playerCache[player.steam_id] = player;
                        }
                    });
                    
                    // Online-Spieler-Tabelle aktualisieren
                    $('#onlinePlayerTable tbody').empty();
                    
                    if (onlinePlayers.length === 0) {
                        $('#onlinePlayerTable tbody').html('<tr><td colspan="8" class="text-center">' + 
                            (typeof t === 'function' ? t('no_online_players') : 'Keine Spieler online.') + '</td></tr>');
                    } else {
                        // Spieler rendern
                        renderPlayersInBatches(onlinePlayers, true, 'onlinePlayerTable');
                        
                        // Online-Spielerzähler aktualisieren
                        updateOnlineCounter();
                    }
                } else {
                    $('#onlinePlayerTable tbody').html('<tr><td colspan="8" class="text-center text-danger">' + 
                        (data.message || 'Fehler beim Laden der Spielerdaten.') + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                isUpdating = false;
                console.error('AJAX-Fehler:', status, error);
                $('#onlinePlayerTable tbody').html('<tr><td colspan="8" class="text-center text-danger">' + 
                    (typeof t === 'function' ? t('server_error') : 'Serverfehler beim Laden der Daten.') + '</td></tr>');
            }
        });
    }
    
    /**
     * Offline-Spieler aus der Datenbank laden
     */
    function loadOfflinePlayers() {
        // Wenn bereits eine Aktualisierung läuft, abbrechen
        if (isLoadingOffline) return;
        
        isLoadingOffline = true;
        $('#loadOfflinePlayers').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Laden...');
        $('#offlinePlayerTable tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> ' + 
            (typeof t === 'function' ? t('loading_offline_players') : 'Lade Offline-Spieler aus Datenbank...') + '</td></tr>');
        
        $.ajax({
            url: 'get_players.php',
            type: 'GET',
            dataType: 'json',
            data: {
                only_offline: true  // Nur Offline-Spieler aus der Datenbank anfordern
            },
            success: function(data) {
                isLoadingOffline = false;
                
                // Button zurücksetzen
                $('#loadOfflinePlayers').prop('disabled', false).html('<i class="bi bi-database-down"></i> ' + 
                    (typeof t === 'function' ? t('refresh_offline_players') : 'Offline-Spieler aktualisieren'));
                
                // Direkt mit dem JSON-Objekt arbeiten
                if (data.success && Array.isArray(data.data)) {
                    // Offline-Spieler verarbeiten
                    offlinePlayers = data.data;
                    
                    // Offline-Spieler-Tabelle aktualisieren
                    $('#offlinePlayerTable tbody').empty();
                    
                    if (offlinePlayers.length === 0) {
                        $('#offlinePlayerTable tbody').html('<tr><td colspan="8" class="text-center">' + 
                            (typeof t === 'function' ? t('no_offline_players') : 'Keine Offline-Spieler gefunden.') + '</td></tr>');
                    } else {
                        // Offline-Spieler rendern
                        renderPlayersInBatches(offlinePlayers, false, 'offlinePlayerTable');
                        
                        // Offline-Spielerzähler aktualisieren
                        $('#offlinePlayerCount').text(offlinePlayers.length + ' Offline-Spieler');
                    }
                } else {
                    $('#offlinePlayerTable tbody').html('<tr><td colspan="8" class="text-center text-danger">' + 
                        (data.message || 'Fehler beim Laden der Offline-Spielerdaten.') + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                isLoadingOffline = false;
                $('#loadOfflinePlayers').prop('disabled', false).html('<i class="bi bi-database-down"></i> ' + 
                    (typeof t === 'function' ? t('reload_offline_players') : 'Offline-Spieler laden'));
                    
                console.error('AJAX-Fehler:', status, error);
                $('#offlinePlayerTable tbody').html('<tr><td colspan="8" class="text-center text-danger">' + 
                    (typeof t === 'function' ? t('server_error') : 'Serverfehler beim Laden der Offline-Daten.') + '</td></tr>');
            }
        });
    }
    
    /**
     * Online-Spielerzähler im Tab aktualisieren
     */
    function updateOnlineCounter() {
        var text = onlinePlayers.length + ' ' + (typeof t === 'function' ? t('online_players') : 'Online');
        $('#online-tab').html(text);
    }
    
    /**
     * Spieler in Batches rendern, um UI-Blockierung zu vermeiden
     * @param {Array} players - Liste der Spieler
     * @param {boolean} isOnline - Ob es sich um Online-Spieler handelt
     * @param {string} tableId - ID der Zieltabelle (default: 'playerTable')
     */
    function renderPlayersInBatches(players, isOnline, tableId) {
        var totalPlayers = players.length;
        tableId = tableId || 'playerTable'; // Standardwert, falls nicht angegeben
        
        if (totalPlayers === 0) return;
        
        // Batch-Processing, um UI-Freezes zu vermeiden
        var currentBatch = 0;
        var totalBatches = Math.ceil(totalPlayers / BATCH_SIZE);
        var $tbody = $('#' + tableId + ' tbody');
        
        function processBatch() {
            if (currentBatch >= totalBatches) {
                // Alle Batches verarbeitet - Event-Handler hinzufügen
                attachPlayerEventHandlers(tableId);
                return;
            }
            
            // Berechnung der Batch-Grenzen
            var start = currentBatch * BATCH_SIZE;
            var end = Math.min(start + BATCH_SIZE, totalPlayers);
            
            // HTML für aktuellen Batch erstellen
            var html = '';
            for (var i = start; i < end; i++) {
                var player = players[i];
                html += generatePlayerRow(player, isOnline);
            }
            
            // HTML anhängen (nicht ersetzen)
            $tbody.append(html);
            
            // Nächsten Batch verarbeiten
            currentBatch++;
            setTimeout(processBatch, 0); // setTimeout mit 0 gibt UI Zeit zum Atmen
        }
        
        // Batch-Processing starten
        processBatch();
    }
    
    /**
     * HTML für eine einzelne Spieler-Zeile generieren
     * @param {Object} player - Spieler-Objekt
     * @param {boolean} isOnline - Ist der Spieler online
     * @return {string} HTML der Zeile
     */
    function generatePlayerRow(player, isOnline) {
        var html = '<tr class="' + (isOnline ? 'online-player' : 'offline-player') + '" data-steam-id="' + (player.steam_id || '') + '">';
        
        // Online-Status visuell markieren
        if (isOnline) {
            html += '<td><span class="badge bg-success me-1">●</span> ' + (player.name || '-') + '</td>';
        } else {
            html += '<td><span class="badge bg-secondary me-1">●</span> ' + (player.name || '-') + '</td>';
        }
        
        html += '<td>';
        html += '<span class="steam-id-full">' + (player.steam_id || '-') + '</span>';
        if (player.steam_id) {
            var shortSteamId = player.steam_id.substring(0, 6) + '...' + player.steam_id.substring(player.steam_id.length - 4);
            html += '<span class="steam-id-short">' + shortSteamId + '</span>';
        } else {
            html += '<span class="steam-id-short">-</span>';
        }
        html += '</td>';
        
        html += '<td>' + (player.last_seen || '-') + '</td>';
        html += '<td>' + (player.taxi_level || '0') + '</td>';
        html += '<td>' + (player.bus_level || '0') + '</td>';
        html += '<td>' + (player.wrecker_level || '0') + '</td>';
        html += '<td>' + (player.police_level || '0') + '</td>';
        html += '<td>';
        html += '<button class="btn btn-sm btn-info view-player-details" data-player-id="' + player.steam_id + '"><i class="bi bi-eye"></i></button> ';
        if (hasPermission && hasPermission('player_ban')) {
            html += '<button class="btn btn-sm btn-danger ban-player" data-player-id="' + player.steam_id + '" data-player-name="' + player.name + '"><i class="bi bi-shield-x"></i></button>';
        }
        html += '</td>';
        html += '</tr>';
        
        return html;
    }
    
    /**
     * Event-Handler für Spieleraktionen hinzufügen
     */
    function attachPlayerEventHandlers() {
        // Event-Handler für Spieler-Details
        $('.view-player-details').on('click', function() {
            var playerId = $(this).data('player-id');
            showPlayerDetails(playerId);
        });
        
        // Event-Handler für Spieler bannen
        $('.ban-player').on('click', function() {
            var playerId = $(this).data('player-id');
            var playerName = $(this).data('player-name');
            // Hier könnte man ein Modal zum Bannen öffnen
            if (typeof showBanModal === 'function') {
                showBanModal(playerId, playerName);
            } else {
                alert('Ban-Funktion nicht verfügbar.');
            }
        });
    }
    
    /**
     * Spieler-Details anzeigen
     * @param {string} playerId - ID des Spielers
     */
    function showPlayerDetails(playerId) {
        $('#playerDetailsTitle').text(typeof t === 'function' ? t('loading_player_details') : 'Lade Spielerdetails...');
        $('#playerDetailsContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
        $('#playerDetailsModal').modal('show');
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'get_player_details',
                player_id: playerId,
                csrf_token: CSRF_TOKEN
            },
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    if (data.success && data.data) {
                        var player = data.data;
                        
                        $('#playerDetailsTitle').text(player.name || 'Spielerdetails');
                        
                        var html = '<div class="row">';
                        
                        // Linke Spalte - Grundinformationen
                        html += '<div class="col-md-6">';
                        html += '<h6>' + (typeof t === 'function' ? t('basic_info') : 'Grundinformationen') + '</h6>';
                        html += '<table class="table table-sm">';
                        html += '<tr><th>' + (typeof t === 'function' ? t('player_name') : 'Spielername') + '</th><td>' + (player.name || '-') + '</td></tr>';
                        html += '<tr><th>' + (typeof t === 'function' ? t('steam_id') : 'Steam ID') + '</th><td>' + (player.steam_id || '-') + '</td></tr>';
                        html += '<tr><th>' + (typeof t === 'function' ? t('first_seen') : 'Zuerst gesehen') + '</th><td>' + (player.first_seen || '-') + '</td></tr>';
                        html += '<tr><th>' + (typeof t === 'function' ? t('last_seen') : 'Zuletzt gesehen') + '</th><td>' + (player.last_seen || '-') + '</td></tr>';
                        html += '</table>';
                        html += '</div>';
                        
                        // Rechte Spalte - Level-Informationen
                        html += '<div class="col-md-6">';
                        html += '<h6>' + (typeof t === 'function' ? t('level_info') : 'Level-Informationen') + '</h6>';
                        html += '<table class="table table-sm">';
                        html += '<tr><th>' + (typeof t === 'function' ? t('taxi_level') : 'Taxi-Level') + '</th><td>' + (player.taxi_level || '0') + '</td></tr>';
                        html += '<tr><th>' + (typeof t === 'function' ? t('bus_level') : 'Bus-Level') + '</th><td>' + (player.bus_level || '0') + '</td></tr>';
                        html += '<tr><th>' + (typeof t === 'function' ? t('wrecker_level') : 'Abschlepper-Level') + '</th><td>' + (player.wrecker_level || '0') + '</td></tr>';
                        html += '<tr><th>' + (typeof t === 'function' ? t('police_level') : 'Polizei-Level') + '</th><td>' + (player.police_level || '0') + '</td></tr>';
                        html += '</table>';
                        html += '</div>';
                        
                        // Aktivitätslog
                        html += '<div class="col-12 mt-3">';
                        html += '<h6>' + (typeof t === 'function' ? t('activity_log') : 'Aktivitätslog') + '</h6>';
                        
                        if (player.activity_log && player.activity_log.length > 0) {
                            html += '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
                            html += '<table class="table table-sm table-striped">';
                            html += '<thead><tr><th>' + (typeof t === 'function' ? t('timestamp') : 'Zeitpunkt') + '</th><th>' + (typeof t === 'function' ? t('action') : 'Aktion') + '</th></tr></thead>';
                            html += '<tbody>';
                            
                            player.activity_log.forEach(function(log) {
                                html += '<tr>';
                                html += '<td>' + log.timestamp + '</td>';
                                html += '<td>' + log.action + '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table>';
                            html += '</div>';
                        } else {
                            html += '<p class="text-muted">' + (typeof t === 'function' ? t('no_activity_log') : 'Keine Aktivitäten aufgezeichnet.') + '</p>';
                        }
                        
                        html += '</div>';
                        html += '</div>'; // Ende der Row
                        
                        $('#playerDetailsContent').html(html);
                    } else {
                        $('#playerDetailsContent').html('<div class="alert alert-danger">' + (data.message || 'Fehler beim Laden der Spielerdetails.') + '</div>');
                    }
                } catch (error) {
                    console.error('Fehler beim Parsen der Antwort:', error);
                    $('#playerDetailsContent').html('<div class="alert alert-danger">Fehler beim Verarbeiten der Antwort.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', status, error);
                $('#playerDetailsContent').html('<div class="alert alert-danger">Serverfehler beim Laden der Spielerdetails.</div>');
            }
        });
    }
    
    /**
     * Spielerdaten aktualisieren
     */
    function updatePlayerData() {
        $('#refreshPlayerData').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> ' + (typeof t === 'function' ? t('updating') : 'Aktualisiere...'));
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'update_player_data',
                csrf_token: CSRF_TOKEN
            },
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    if (data.success) {
                        showNotification(data.message || 'Spielerdaten aktualisiert.', 'success');
                        loadPlayerData();
                    } else {
                        showNotification(data.message || 'Fehler beim Aktualisieren der Spielerdaten.', 'danger');
                    }
                } catch (error) {
                    console.error('Fehler beim Parsen der Antwort:', error);
                    showNotification('Fehler beim Verarbeiten der Antwort.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', status, error);
                showNotification('Serverfehler beim Aktualisieren der Spielerdaten.', 'danger');
            },
            complete: function() {
                $('#refreshPlayerData').prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> ' + (typeof t === 'function' ? t('refresh') : 'Aktualisieren'));
            }
        });
    }
    
    // Event-Handler für Aktualisieren-Button
    $('#refreshPlayerData').on('click', function() {
        updatePlayerData();
    });
    
    /**
     * Automatische Aktualisierung einrichten
     * Effizienter durch Trennung von Online/Offline-Spielern
     */
    function setupAutoRefresh() {
        // Bestehende Intervalle beenden
        if (updateInterval) {
            clearInterval(updateInterval);
        }
        
        // Neuen Intervall starten (alle 60 Sekunden)
        updateInterval = setInterval(function() {
            // Spielerdaten nur aktualisieren, wenn keine andere Aktualisierung läuft
            // und die letzte Aktualisierung mindestens 30 Sekunden her ist
            if (!isUpdating && (Date.now() - lastUpdateTime) > 30000) {
                console.log('Auto-Refresh: Spielerdaten werden aktualisiert...');
                loadPlayerData();
            }
        }, 60000); // 60 Sekunden
    }
    
    // CSS für Rotationsanimation und Spieler-Klassen
    $('<style>').html(
        '.rotating {' +
        '    animation: rotating 2s linear infinite;' +
        '}' +
        '@keyframes rotating {' +
        '    from { transform: rotate(0deg); }' +
        '    to { transform: rotate(360deg); }' +
        '}' +
        '.online-player {' +
        '    background-color: rgba(25, 135, 84, 0.05);' + // Leicht grünlicher Hintergrund
        '}' +
        '.offline-player {' +
        '    opacity: 0.8;' + // Offline-Spieler etwas gedimmt
        '}'
    ).appendTo('head');
    
    // Spielerdaten initial laden
    loadPlayerData();
    
    // Automatische Aktualisierung starten
    setupAutoRefresh();
});
