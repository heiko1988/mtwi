$(document).ready(function() {
    console.log('Players.js geladen');

    // Status-Variablen für die Aktualisierung
    let updateInterval = null;
    let isUpdating = false;
    let lastUpdateTime = 0;
    
    // Spielerdaten laden
    function loadPlayerData() {
        // Wenn bereits eine Aktualisierung läuft, abbrechen
        if (isUpdating) return;
        
        isUpdating = true;
        $('#playerTable tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> ' + (typeof t === 'function' ? t('loading_player_data') : 'Lade Spielerdaten...') + '</td></tr>');
        
        $.ajax({
            url: 'get_players.php',
            type: 'GET',
            dataType: 'json',  // Erwarte JSON-Antwort
            success: function(data) {
                isUpdating = false;
                lastUpdateTime = Date.now();
                
                // Direkt mit dem JSON-Objekt arbeiten
                if (data.success && Array.isArray(data.data)) {
                    if (data.data.length === 0) {
                        $('#playerTable tbody').html('<tr><td colspan="8" class="text-center">' + (typeof t === 'function' ? t('no_players_found') : 'Keine Spieler gefunden.') + '</td></tr>');
                        return;
                    }
                    
                    let html = '';
                    data.data.forEach(player => {
                        html += '<tr>';
                        html += '<td>' + (player.name || '-') + '</td>';
                        html += '<td>' + (player.steam_id || '-') + '</td>';
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
                    });
                    
                    $('#playerTable tbody').html(html);
                    
                    // Event-Handler für Spieler-Details
                    $('.view-player-details').on('click', function() {
                        const playerId = $(this).data('player-id');
                        showPlayerDetails(playerId);
                    });
                    
                    // Event-Handler für Spieler bannen
                    $('.ban-player').on('click', function() {
                        const playerId = $(this).data('player-id');
                        const playerName = $(this).data('player-name');
                        // Hier könnte man ein Modal zum Bannen öffnen
                        if (typeof showBanModal === 'function') {
                            showBanModal(playerId, playerName);
                        } else {
                            alert('Ban-Funktion nicht verfügbar.');
                        }
                    });
                } else {
                    $('#playerTable tbody').html('<tr><td colspan="8" class="text-center text-danger">' + (data.message || 'Fehler beim Laden der Spielerdaten.') + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                isUpdating = false;
                console.error('AJAX-Fehler:', status, error);
                let errorMsg = 'Serverfehler beim Laden der Spielerdaten.';
                
                // Versuche, einen Fehler aus der Antwort zu extrahieren
                try {
                    const responseJson = JSON.parse(xhr.responseText);
                    if (responseJson && responseJson.message) {
                        errorMsg = responseJson.message;
                    }
                } catch (e) {
                    // Fallback auf xhr.statusText, wenn Parsen fehlschlägt
                    if (xhr.statusText) {
                        errorMsg += ' ' + xhr.statusText;
                    }
                }
                
                $('#playerTable tbody').html('<tr><td colspan="8" class="text-center text-danger">' + errorMsg + '</td></tr>');
            }
        });
    }
    
    // Serverlogs aktualisieren, um neue Spielerdaten zu erhalten
    function updatePlayerData() {
        // Animation für den Aktualisierungsbutton starten
        const $refreshBtn = $('#refreshPlayerData');
        $refreshBtn.prop('disabled', true).find('i').addClass('rotating');
        
        // Serverlog-Aktualisierung durchführen
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'update_player_data',
                csrf_token: CSRF_TOKEN
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    // Benachrichtigung anzeigen
                    let alertClass = data.success ? 'alert-success' : 'alert-danger';
                    let alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show">' +
                        data.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
                    
                    // Alert über der Tabelle einfügen
                    $('#playerTable').before(alertHtml);
                    
                    // Timeout-Funktion, um die Nachricht nach 5 Sekunden zu entfernen
                    setTimeout(function() {
                        $('.alert').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 5000);
                    
                    // Bei Erfolg sofort die Daten neu laden
                    if (data.success) {
                        loadPlayerData();
                    }
                } catch (error) {
                    console.error('Fehler beim Parsen der Antwort:', error);
                }
                
                // Animation beenden
                $refreshBtn.prop('disabled', false).find('i').removeClass('rotating');
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', status, error);
                
                // Animation beenden
                $refreshBtn.prop('disabled', false).find('i').removeClass('rotating');
            }
        });
    }
    
    // Spieler-Details anzeigen
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
                    const data = JSON.parse(response);
                    if (data.success && data.data) {
                        const player = data.data;
                        
                        $('#playerDetailsTitle').text(player.name || 'Spielerdetails');
                        
                        let html = '<div class="row">';
                        
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
                            
                            player.activity_log.forEach(log => {
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
    
    // Spielerdaten aktualisieren
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
                    const data = JSON.parse(response);
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
    // Button-Event für manuelles Aktualisieren
    $('#refreshPlayerData').on('click', function() {
        updatePlayerData();
    });
    
    // Automatische Aktualisierung einrichten
    function setupAutoRefresh() {
        // Bestehenden Intervall beenden, falls vorhanden
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
    
    // CSS für Rotationsanimation hinzufügen
    $('<style>').html(
        '.rotating {' +
        '    animation: rotating 2s linear infinite;' +
        '}' +
        '@keyframes rotating {' +
        '    from { transform: rotate(0deg); }' +
        '    to { transform: rotate(360deg); }' +
        '}'
    ).appendTo('head');
    
    // Spielerdaten initial laden
    loadPlayerData();
    
    // Automatische Aktualisierung starten
    setupAutoRefresh();
});
