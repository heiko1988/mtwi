// Server Control Script for MTWI Dashboard
$(document).ready(function() {
    // Variable für letzten bekannten Status
    let lastKnownStatus = null;
    let firstLoad = true;
    
    // Initial Status laden
    fetchServerStatus(true);
    let serverStatusTimer = setInterval(function() { 
        fetchServerStatus(false); 
    }, 10000); // alle 10s

    $('#btnServerStart').on('click', function() {
        sendServerCommand('start');
    });
    let pendingServerAction = null;
    $('#btnServerStop').on('click', function() {
        pendingServerAction = 'stop';
        $('#serverConfirmModalLabel').text(translations.server_stop_title || 'Server stoppen');
        $('#serverConfirmModalText').text(translations.server_stop_confirm || 'Bist du sicher, dass du den Server stoppen möchtest?');
        $('#serverConfirmModalConfirm').text(translations.yes_execute || 'Ja, ausführen');
        $('#serverConfirmModal').modal('show');
    });
    $('#btnServerRestart').on('click', function() {
        pendingServerAction = 'restart';
        $('#serverConfirmModalLabel').text(translations.server_restart_title || 'Server neustarten');
        $('#serverConfirmModalText').text(translations.server_restart_confirm || 'Bist du sicher, dass du den Server neustarten möchtest?');
        $('#serverConfirmModalConfirm').text(translations.yes_execute || 'Ja, ausführen');
        $('#serverConfirmModal').modal('show');
    });
    $('#serverConfirmModalConfirm').on('click', function() {
        if (pendingServerAction) {
            sendServerCommand(pendingServerAction);
            pendingServerAction = null;
            $('#serverConfirmModal').modal('hide');
        }
    });

    // Automatische Übersetzung aller data-i18n-Elemente
    function updateI18n() {
        $('[data-i18n]').each(function() {
            const key = $(this).data('i18n');
            if (translations[key]) {
                $(this).html(translations[key]);
            }
        });
    }
    updateI18n();

    function sendServerCommand(cmd) {
        // Bei Befehlen immer 'loading' anzeigen
        setServerBadge('loading');
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'server_control',
                command: cmd,
                csrf_token: CSRF_TOKEN
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showNotification(data.message, 'success');
                    fetchServerStatus(true);
                } else {
                    showNotification(data.message, 'danger');
                    setServerBadge('error', data.message);
                }
            },
            error: function() {
                showNotification('Verbindung zum Server fehlgeschlagen!', 'danger');
                setServerBadge('error', 'Verbindung fehlgeschlagen');
            }
        });
    }

    function fetchServerStatus(isForceUpdate) {
        // Nur beim ersten Laden oder bei erzwungener Aktualisierung "Lädt..." anzeigen
        if (firstLoad || isForceUpdate) {
            setServerBadge('loading');
            firstLoad = false;
        }
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'server_control',
                command: 'status',
                csrf_token: CSRF_TOKEN
            },
            dataType: 'json',
            success: function(data) {
                if (data.success && data.data && data.data.status) {
                    // Status speichern
                    lastKnownStatus = data.data.status;
                    setServerBadge(data.data.status, data.data.detail);
                } else {
                    setServerBadge('error', data.message);
                }
                // Zeitstempel immer aktualisieren
                $('#serverStatusTime').text(new Date().toLocaleTimeString());
            },
            error: function() {
                setServerBadge('error', 'Verbindung fehlgeschlagen');
            }
        });
    }

    function setServerBadge(status, detail) {
        let badge = $('#serverStatusBadge');
        badge.removeClass('bg-success bg-danger bg-warning bg-info bg-secondary bg-dark');
        let text = '';
        switch(status) {
            case 'running':
                badge.addClass('bg-success');
                text = 'Status: Online';
                break;
            case 'stopped':
                badge.addClass('bg-danger');
                text = 'Status: Offline';
                break;
            case 'restarting':
                badge.addClass('bg-warning');
                text = 'Status: Neustart...';
                break;
            case 'loading':
                badge.addClass('bg-info');
                text = 'Status: Lädt...';
                break;
            case 'error':
                badge.addClass('bg-dark');
                text = 'Status: Fehler';
                break;
            default:
                badge.addClass('bg-secondary');
                text = 'Status: ...';
        }
        badge.text(text + (detail ? ' ('+detail+')' : ''));
    }
});
