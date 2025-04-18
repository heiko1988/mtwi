/**
 * Motor Town Web Interface (MTWI) - Dashboard-Skript
 */

let refreshTimer;

$(document).ready(function() {
    // Initial Daten laden
    refreshData();
    
    // Regelmäßiges Aktualisieren einrichten
    refreshTimer = setInterval(refreshData, REFRESH_INTERVAL);
    
    // Ban-Modal Initialisierung - setzt Status basierend auf dem Button, der geklickt wurde
    $('#banModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const uniqueId = button.data('unique-id');
        const playerName = button.data('player-name');
        const isOfflinePlayer = button.hasClass('offline-player-ban');
        
        // Modal-Formular zurücksetzen
        $('#banPlayerForm')[0].reset();
        $('#customDurationContainer').addClass('d-none');
        
        // Modal mit den Spielerdaten füllen
        $('#banUniqueId').val(uniqueId);
        $('#banPlayerName').text(playerName);
        
        // Status für unterschiedliche Behandlung speichern (online vs. offline)
        $('#banPlayerForm').data('player-status', isOfflinePlayer ? 'offline' : 'online');
    });
    
    // Event-Listener für das Ban-Formular
    $('#banPlayerForm').on('submit', function(e) {
        e.preventDefault();
        
        const uniqueId = $('#banUniqueId').val();
        const playerName = $('#banPlayerName').text();
        const reason = $('#banReason').val();
        let duration = $('#banDuration').val();
        // Flag, um zu unterscheiden, ob es sich um einen Online-Spieler oder kürzlich abgemeldeten Spieler handelt
        const isOfflinePlayer = $(this).data('player-status') === 'offline';
        
        // Bei benutzerdefinierter Dauer die eingegebene Zeit verwenden
        if (duration === 'custom') {
            const customValue = $('#customDurationValue').val();
            const customUnit = $('#customDurationUnit').val();
            
            if (!customValue || isNaN(customValue) || customValue <= 0) {
                showNotification('Bitte geben Sie eine gültige Dauer ein', 'danger');
                return;
            }
            
            // Umrechnen in Sekunden
            switch (customUnit) {
                case 'minutes':
                    duration = customValue * 60;
                    break;
                case 'hours':
                    duration = customValue * 3600;
                    break;
                case 'days':
                    duration = customValue * 86400;
                    break;
                default:
                    duration = customValue * 60; // Default: Minuten
            }
        }
        
        // AJAX-Anfrage - unterscheidet zwischen Online-Ban und ausstehendem Ban
        const action = isOfflinePlayer ? 'add_pending_ban' : 'ban_player';
        const data = {
            action: action,
            unique_id: uniqueId,
            reason: reason,
            duration: duration,
            csrf_token: CSRF_TOKEN
        };
        
        // Wenn es ein ausstehender Ban ist, benötigen wir auch den Spielernamen
        if (isOfflinePlayer) {
            data.player_name = playerName;
        }
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('#banSubmitButton').prop('disabled', true);
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        $('#banModal').modal('hide');
                        refreshData();
                    } else {
                        showNotification(data.message, 'danger');
                    }
                } catch (e) {
                    showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
                }
            },
            error: function() {
                showNotification('Serverfehler', 'danger');
            },
            complete: function() {
                $('#banSubmitButton').prop('disabled', false);
            }
        });
    });
});

// Daten vom Server laden
function refreshData() {
    $.ajax({
        url: 'ajax_handler.php',
        type: 'POST',
        data: {
            action: 'get_dashboard_data',
            csrf_token: CSRF_TOKEN
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                updateDashboard(data.data);
            } else {
                showNotification(data.message, 'danger');
                clearInterval(refreshTimer); // Aktualisierung stoppen bei Fehler
            }
        },
        error: function() {
            showNotification('Fehler beim Laden der Daten', 'danger');
            clearInterval(refreshTimer); // Aktualisierung stoppen bei Fehler
        }
    });
}

// Dashboard-UI aktualisieren
function updateDashboard(data) {
    // Spielerzahl aktualisieren
    $('#playerCount').text(data.player_count);
    
    // Aktive Spielerliste aktualisieren
    updatePlayerTable(data.active_players);
    
    // Kürzlich abgemeldete Spieler aktualisieren
    updateRecentPlayersTable(data.recent_players);
}

// Aktive Spielerliste aktualisieren
function updatePlayerTable(players) {
    const tableBody = $('#activePlayersTable tbody');
    tableBody.empty();
    
    if (players.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="3" class="text-center">${translations.no_players_online}</td>
            </tr>
        `);
        return;
    }
    
    players.forEach(function(player) {
        tableBody.append(`
            <tr>
                <td>${player.name}</td>
                <td>${player.unique_id}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#confirmModal" 
                        data-action="kick_player" 
                        data-id="${player.unique_id}" 
                        data-name="${player.name}" 
                        data-confirm-text="${translations.confirm_kick.replace('{name}', player.name)}">
                        <i class="bi bi-person-x"></i> ${translations.kick}
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#banModal" 
                        data-unique-id="${player.unique_id}" 
                        data-player-name="${player.name}">
                        <i class="bi bi-shield-x"></i> ${translations.ban}
                    </button>
                </td>
            </tr>
        `);
    });
}

// Kürzlich abgemeldete Spieler aktualisieren
function updateRecentPlayersTable(players) {
    const tableBody = $('#recentPlayersTable tbody');
    tableBody.empty();
    
    if (players.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="4" class="text-center">${translations.no_recent_players}</td>
            </tr>
        `);
        return;
    }
    
    players.forEach(function(player) {
        tableBody.append(`
            <tr>
                <td>${player.player_name}</td>
                <td>${player.unique_id}</td>
                <td>
                    ${formatTimestamp(player.last_seen, 'relative')}
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-danger offline-player-ban" 
                        data-bs-toggle="modal" 
                        data-bs-target="#banModal" 
                        data-unique-id="${player.unique_id}" 
                        data-player-name="${player.player_name}">
                        <i class="bi bi-shield-x"></i> ${translations.ban}
                    </button>
                </td>
            </tr>
        `);
    });
}
