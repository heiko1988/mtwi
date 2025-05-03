/**
 * Motor Town Web Interface (MTWI) - Bannlisten-Skript
 */

let refreshTimer;

$(document).ready(function() {
    // Initial Daten laden
    refreshData();
    
    // Regelmäßiges Aktualisieren einrichten
    refreshTimer = setInterval(refreshData, REFRESH_INTERVAL);
    
    // Event-Listener für das Ban-Formular
    $('#addPendingBanForm').on('submit', function(e) {
        e.preventDefault();
        
        const steamId = $('#pendingBanSteamId').val();
        const playerName = $('#pendingBanPlayerName').val();
        const reason = $('#pendingBanReason').val();
        let duration = $('#pendingBanDuration').val();
        
        // Validierung
        if (!steamId) {
            showNotification('Bitte geben Sie eine Steam-ID ein', 'danger');
            return;
        }
        
        if (!playerName) {
            showNotification('Bitte geben Sie einen Spielernamen ein', 'danger');
            return;
        }
        
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
        
        // AJAX-Anfrage zum Hinzufügen des ausstehenden Bans
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'add_pending_ban',
                unique_id: steamId,
                player_name: playerName,
                reason: reason,
                duration: duration,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $('#pendingBanSubmitButton').prop('disabled', true);
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        $('#addPendingBanModal').modal('hide');
                        $('#addPendingBanForm')[0].reset();
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
                $('#pendingBanSubmitButton').prop('disabled', false);
            }
        });
    });
    
    // Custom Duration ein-/ausblenden
    $('#pendingBanDuration').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customDurationContainer').removeClass('d-none');
        } else {
            $('#customDurationContainer').addClass('d-none');
        }
    });
    
    // Gleiche Funktionalität für das normale Ban-Formular
    $('#banDuration').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#banCustomDurationContainer').removeClass('d-none');
        } else {
            $('#banCustomDurationContainer').addClass('d-none');
        }
    });
});

// Daten vom Server laden
function refreshData() {
    $.ajax({
        url: 'ajax_handler.php',
        type: 'POST',
        data: {
            action: 'get_ban_list',
            csrf_token: CSRF_TOKEN
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                updateBanLists(data.data);
                initBanTimers(); // Timer initialisieren
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

// Ban-Listen aktualisieren
function updateBanLists(data) {
    // Aktive Bans aktualisieren
    updateActiveBansTable(data.active_bans);
    
    // Ausstehende Bans aktualisieren
    updatePendingBansTable(data.pending_bans);
}

// Aktive Bans aktualisieren
function updateActiveBansTable(bans) {
    const tableBody = $('#activeBansTable tbody');
    tableBody.empty();
    
    if (bans.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="5" class="text-center">${translations.no_active_bans}</td>
            </tr>
        `);
        return;
    }
    
    bans.forEach(function(ban) {
        // Ablaufzeit formatieren (oder "Permanent" anzeigen)
        const expires = ban.is_permanent 
            ? `<span class="badge bg-danger">${translations.duration_permanent}</span>` 
            : `<span class="ban-timer status-active" data-expires-at="${ban.expires_at}"></span>`;
            
        tableBody.append(`
            <tr>
                <td>${ban.player_name}</td>
                <td>${ban.unique_id}</td>
                <td>${ban.reason || '-'}</td>
                <td>${expires}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#confirmModal" 
                        data-action="unban_player" 
                        data-id="${ban.unique_id}" 
                        data-name="${ban.player_name}" 
                        data-confirm-text="${translations.confirm_unban.replace('{name}', ban.player_name)}">
                        <i class="bi bi-shield-check"></i> ${translations.unban_player}
                    </button>
                </td>
            </tr>
        `);
    });
}

// Ausstehende Bans aktualisieren
function updatePendingBansTable(bans) {
    const tableBody = $('#pendingBansTable tbody');
    tableBody.empty();
    
    if (bans.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="5" class="text-center">${translations.no_pending_bans}</td>
            </tr>
        `);
        return;
    }
    
    bans.forEach(function(ban) {
        // Ablaufzeit formatieren (oder "Permanent" anzeigen)
        const expires = ban.is_permanent 
            ? `<span class="badge bg-danger">${translations.duration_permanent}</span>` 
            : formatTimestamp(ban.expires_at);
            
        tableBody.append(`
            <tr>
                <td>${ban.player_name}</td>
                <td>${ban.unique_id}</td>
                <td>${ban.reason || '-'}</td>
                <td>${expires}</td>
                <td class="text-end">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#confirmModal" 
                            data-action="remove_pending_ban" 
                            data-id="${ban.id}" 
                            data-name="${ban.player_name}" 
                            data-confirm-text="${translations.confirm_unban.replace('{name}', ban.player_name)}">
                            <i class="bi bi-shield-check"></i> ${translations.unban_player}
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#confirmModal" 
                            data-action="remove_pending_ban" 
                            data-id="${ban.id}" 
                            data-name="${ban.player_name}" 
                            data-confirm-text="${translations.confirm_remove_ban.replace('{name}', ban.player_name)}">
                            <i class="bi bi-trash"></i> ${translations.remove}
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}
