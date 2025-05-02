/**
 * Motor Town Web Interface (MTWI) - Dashboard-Skript
 */

let refreshTimer;
let dashboardChatTimer;

$(document).ready(function() {
    // Server-Control-Script initialisieren (falls nicht schon eingebunden)
    if (typeof window.serverControlLoaded === 'undefined') {
        $.getScript('assets/js/server_control.js', function(){ window.serverControlLoaded = true; });
    }

    // Initial Daten laden
    refreshData();
    
    // Regelmäßiges Aktualisieren einrichten
    refreshTimer = setInterval(refreshData, REFRESH_INTERVAL);
    
    // Dashboard Live-Chat Funktionen
    if ($('#dashboardLiveChatMessages').length > 0) {
        // CSS fix für bessere Lesbarkeit im Chat
        $('<style>\n#dashboardLiveChatMessages a { color: #0dcaf0; }\n#dashboardLiveChatMessages .text-muted, #dashboardLiveChatMessages .text-center.text-muted { color: #adb5bd !important; }\n</style>').appendTo('head');
        
        // Initial Chat laden
        fetchDashboardChatMessages();
        
        // Regelmäßiges Aktualisieren des Chats
        dashboardChatTimer = setInterval(fetchDashboardChatMessages, 3000);
    }
    
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
    
    // Dashboard Live-Chat Auto-Scroll Toggle
    $('#dashboardLiveChatAutoScroll').on('change', function() {
        if ($(this).is(':checked')) {
            scrollDashboardChatToBottom();
        }
    });
});

// Hilfsfunktionen für den Dashboard Live-Chat
function scrollDashboardChatToBottom() {
    var box = document.getElementById('dashboardLiveChatMessages');
    if (box) box.scrollTop = box.scrollHeight;
}

function isDashboardAutoScrollEnabled() {
    return $('#dashboardLiveChatAutoScroll').is(':checked');
}

// Dashboard Live-Chat Nachrichten abrufen
function fetchDashboardChatMessages() {
    if (!$('#dashboardLiveChatMessages').length) return;
    
    var box = document.getElementById('dashboardLiveChatMessages');
    // Vor dem Update: Prüfen, ob der User schon ganz unten ist (max 40px Abstand)
    var wasAtBottom = false;
    if (box) {
        wasAtBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 40;
    }
    
    $.ajax({
        url: 'ajax_handler.php',
        type: 'POST',
        data: {
            action: 'get_live_chat_messages',
            csrf_token: CSRF_TOKEN
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                if (data.success && Array.isArray(data.data)) {
                    let html = '';
                    // Nachrichten umdrehen, damit die neuesten oben stehen
                    const messages = [...data.data].reverse();
                    
                    // Alle Nachrichten in chronologischer Reihenfolge anzeigen
                    messages.forEach(msg => {
                        if (msg.author && msg.author.toLowerCase() === 'admin') {
                            const zeit = msg.timestamp ? `<span style="color:#fff;font-size:0.85em;margin-right:4px;">[${msg.timestamp}]</span> ` : '';
                            html += `<div style="margin-bottom:2px;display:block;">${zeit}<span class='fw-bold' style="background:#ffe066;color:#222;border-radius:0.25em;padding:2px 6px;font-size:0.9em;">admin</span>: ${msg.text}</div>`;
                        } else {
                            const zeit = msg.timestamp ? `<span style="color:#fff;font-size:0.85em;margin-right:4px;">[${msg.timestamp}]</span> ` : '';
                            html += `<div style="margin-bottom:2px;display:block;">${zeit}<span class='fw-bold' style="font-size:0.9em;">${msg.author||'Server'}</span>: ${msg.text}</div>`;
                        }
                    });
                    
                    $('#dashboardLiveChatMessages').html(html);
                    // Nur scrollen, wenn Auto-Scroll aktiv ODER User war vorher schon ganz unten
                    if (isDashboardAutoScrollEnabled() || wasAtBottom) scrollDashboardChatToBottom();
                } else {
                    $('#dashboardLiveChatMessages').html('<div class="text-center text-muted">' + translations.no_messages + '</div>');
                }
            } catch (e) {
                $('#dashboardLiveChatMessages').html('<div class="text-center text-danger">Fehler beim Laden!</div>');
            }
        },
        error: function() {
            $('#dashboardLiveChatMessages').html('<div class="text-center text-danger">Serverfehler!</div>');
        }
    });
}

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
    if (typeof data.ban_count !== 'undefined') {
        $('#banCount').text(data.ban_count);
    }
    // Server-Ressourcen aktualisieren
    if (typeof data.cpu_percent !== 'undefined') {
        // CPU-Auslastung
        $('#cpuUsageText').text(data.cpu_percent + '%');
        $('#cpuUsageBar').css('width', data.cpu_percent + '%');
        
        // Speicherauslastung (RAM)
        $('#memoryUsageText').text(data.memory_percent + '%');
        $('#memoryUsageBar').css('width', data.memory_percent + '%');
        
        // RAM-Details formatieren und anzeigen
        const ramUsedGB = (data.ram_used_mb / 1024).toFixed(1);
        const ramTotalGB = (data.ram_total_mb / 1024).toFixed(1);
        $('#memoryDetails').text(ramUsedGB + ' GB / ' + ramTotalGB + ' GB');
        
        // Festplatten-Auslastung 
        $('#diskUsageText').text(data.disk_percent + '%');
        $('#diskUsageBar').css('width', data.disk_percent + '%');
        
        // Festplatten-Details formatieren und anzeigen
        const diskUsedGB = data.disk_used_gb.toFixed(1);
        const diskTotalGB = data.disk_total_gb.toFixed(1);
        $('#diskDetails').text(diskUsedGB + ' GB / ' + diskTotalGB + ' GB');
    }
    
    // Aktive Spielerliste aktualisieren
    updatePlayerTable(data.active_players, data.steam_profiles || {});
    
    // Kürzlich abgemeldete Spieler aktualisieren
    updateRecentPlayersTable(data.recent_players, data.steam_profiles || {});
}

// Aktive Spielerliste aktualisieren
function updatePlayerTable(players, steamProfiles) {
    const tableBody = $('#activePlayersTable tbody');
    tableBody.empty();
    
    if (!players || players.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="5" class="text-center">${translations.no_players_online}</td>
            </tr>
        `);
        return;
    }
    
    players.forEach(function(player) {
        // Steam-Profildaten abrufen, falls vorhanden
        const steamProfile = steamProfiles[player.unique_id] || {};
        const steamName = steamProfile.profile_name || '-';
        const avatarUrl = steamProfile.avatar_url || 'assets/img/default-avatar.png';
        
        // HTML für die Zeile erstellen
        let rowHtml = `
            <tr>
                <td><img src="${avatarUrl}" class="player-avatar rounded-circle" alt="Avatar" width="32" height="32"></td>
                <td>${player.name}</td>
                <td>${steamName}</td>
                <td><a href="https://steamcommunity.com/profiles/${player.unique_id}" target="_blank" title="Steam-Profil anzeigen">${player.unique_id}</a></td>
            `;
        
        // Prüfen, ob die Aktionen-Spalte angezeigt werden soll
        if ($('#activePlayersTable thead th:nth-child(5)').length > 0) {
            // Aktionen-Spalte gefunden, also Buttons anzeigen
            rowHtml += `
                <td class="text-end">
                    ${HAS_KICK_PERMISSION ? `
                    <button type="button" class="btn btn-sm btn-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#confirmModal" 
                        data-action="kick_player" 
                        data-id="${player.unique_id}" 
                        data-name="${player.name}" 
                        data-confirm-text="${translations.confirm_kick.replace('{name}', player.name)}">
                        <i class="bi bi-person-x"></i> ${translations.kick}
                    </button>` : ''}
                    ${HAS_BAN_PERMISSION ? `
                    <button type="button" class="btn btn-sm btn-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#banModal" 
                        data-unique-id="${player.unique_id}" 
                        data-player-name="${player.name}">
                        <i class="bi bi-shield-x"></i> ${translations.ban}
                    </button>` : ''}
                </td>`;
        }
        
        // Zeile abschließen
        rowHtml += `</tr>`;
        
        tableBody.append(rowHtml);
    });
}

// Kürzlich abgemeldete Spieler aktualisieren
function updateRecentPlayersTable(players, steamProfiles) {
    const tableBody = $('#recentPlayersTable tbody');
    tableBody.empty();
    
    if (!players || players.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="6" class="text-center">${translations.no_recent_players}</td>
            </tr>
        `);
        return;
    }
    
    players.forEach(function(player) {
        const lastSeen = formatTimestamp(player.last_seen);
        
        // Steam-Profildaten abrufen, falls vorhanden
        const steamProfile = steamProfiles[player.unique_id] || {};
        const steamName = steamProfile.profile_name || '-';
        const avatarUrl = steamProfile.avatar_url || 'assets/img/default-avatar.png';
        
        // HTML für die Zeile erstellen
        let rowHtml = `
            <tr>
                <td><img src="${avatarUrl}" class="player-avatar rounded-circle" alt="Avatar" width="32" height="32"></td>
                <td>${player.player_name}</td>
                <td>${steamName}</td>
                <td><a href="https://steamcommunity.com/profiles/${player.unique_id}" target="_blank" title="Steam-Profil anzeigen">${player.unique_id}</a></td>
                <td>${lastSeen}</td>`;
        
        // Prüfen, ob die Aktionen-Spalte angezeigt werden soll
        if ($('#recentPlayersTable thead th:nth-child(6)').length > 0) {
            // Aktionen-Spalte gefunden, also Button anzeigen
            rowHtml += `
                <td class="text-end">
                    ${HAS_BAN_PERMISSION ? `
                    <button type="button" class="btn btn-sm btn-danger offline-player-ban" 
                        data-bs-toggle="modal" 
                        data-bs-target="#banModal" 
                        data-unique-id="${player.unique_id}" 
                        data-player-name="${player.player_name}">
                        <i class="bi bi-shield-x"></i> ${translations.ban}
                    </button>` : ''}
                </td>`;
        }
        
        // Zeile abschließen
        rowHtml += `</tr>`;
        
        tableBody.append(rowHtml);
    });
}
