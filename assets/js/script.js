/**
 * Motor Town Web Interface (MTWI) - Hauptskript
 */

// Globale Variable für den aktiven Tab in den Einstellungen
// Prüfen, ob die Variable bereits definiert ist, um doppelte Deklarationen zu vermeiden
if (typeof settingsTabToShow === 'undefined') {
    var settingsTabToShow = '';
}

// Funktion zum Aktivieren eines Tabs in den Einstellungen
function activateSettingsTab(tabName) {
    console.log('activateSettingsTab aufgerufen mit: ' + tabName);
    // Tab-Aktivierung auf später verschieben, um sicherzustellen, dass das DOM bereit ist
    settingsTabToShow = tabName;
    
    // Direkte Bootstrap-API-Aufrufe für sofortige Aktivierung, falls DOM bereits geladen
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        try {
            console.log('Versuche Tab direkt zu aktivieren: ' + tabName);
            const tabElement = document.getElementById(tabName + '-tab');
            if (tabElement) {
                // Boostrap 5 Tab API - direkter Aufruf
                var tab = new bootstrap.Tab(tabElement);
                tab.show();
                // Auch im lokalen Speicher speichern
                localStorage.setItem('mtwi_active_settings_tab', tabName);
                console.log('Tab direkt aktiviert: ' + tabName);
            } else {
                console.log('Tab-Element nicht gefunden: ' + tabName);
            }
        } catch (e) {
            console.error('Fehler beim direkten Aktivieren des Tabs:', e);
        }
    }
}

// Dokument-Bereit-Event
$(document).ready(function() {
    // Tab aktivieren, falls URL-Parameter vorhanden
    if (window.location.href.includes('page=settings')) {
        // Tab aus URL-Parameter auslesen, falls vorhanden
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            // Warten, bis DOM vollständig geladen
            setTimeout(function() {
                // Hash aus URL entfernen
                if (window.location.hash) {
                    history.replaceState(null, null, window.location.pathname + window.location.search);
                }
                
                // Tab-Aktivierung versuchen
                $('#' + tabParam + '-tab').tab('show');
                // Auch im lokalen Speicher speichern
                localStorage.setItem('mtwi_active_settings_tab', tabParam);
            }, 300);
        }
    }
    
    // Falls ein Tab explizit aktiviert werden soll (z.B. nach Formular-Submit)
    if (settingsTabToShow) {
        setTimeout(function() {
            $('#' + settingsTabToShow + '-tab').tab('show');
            localStorage.setItem('mtwi_active_settings_tab', settingsTabToShow);
            console.log('Tab aktiviert: ' + settingsTabToShow);
            settingsTabToShow = '';
        }, 300);
    }
    // Theme-Umschalter
    $('#toggle-theme').on('click', function(e) {
        e.preventDefault();
        
        const currentTheme = $('body').attr('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        // Icon aktualisieren
        $(this).find('i').removeClass('bi-moon bi-sun').addClass(newTheme === 'dark' ? 'bi-sun' : 'bi-moon');
        
        // Theme-CSS wechseln
        $('#theme-css').attr('href', 'assets/css/' + newTheme + '.css');
        
        // Data-Attribut aktualisieren
        $('body').attr('data-theme', newTheme);
        
        // Theme in Session speichern
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'set_theme',
                theme: newTheme,
                csrf_token: CSRF_TOKEN
            }
        });
    });
    
    // Bestätigungsdialog-Handler
    $('#confirmModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const action = button.data('action');
        const id = button.data('id');
        const name = button.data('name');
        const confirmText = button.data('confirm-text');
        
        $('#confirmModalText').text(confirmText);
        
        $('#confirmModalButton').off('click').on('click', function() {
            // AJAX-Anfrage für die Aktion
            $.ajax({
                url: 'ajax_handler.php',
                type: 'POST',
                data: {
                    action: action,
                    id: id,
                    csrf_token: CSRF_TOKEN
                },
                beforeSend: function() {
                    $('#confirmModalButton').prop('disabled', true);
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        
                        if (data.success) {
                            showNotification(data.message, 'success');
                            
                            // Seite neu laden oder DOM aktualisieren
                            if (typeof refreshData === 'function') {
                                refreshData();
                            } else {
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            }
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
                    $('#confirmModalButton').prop('disabled', false);
                    $('#confirmModal').modal('hide');
                }
            });
        });
    });
    
    // Ban-Modal-Events
    $('#banModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const uniqueId = button.data('unique-id');
        const playerName = button.data('player-name');
        
        $('#banPlayerName').text(playerName);
        $('#banUniqueId').val(uniqueId);
        
        // Custom Duration ein-/ausblenden
        $('#banDuration').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#customDurationContainer').removeClass('d-none');
            } else {
                $('#customDurationContainer').addClass('d-none');
            }
        });
    });
});

// Benachrichtigung anzeigen
function showNotification(message, type = 'info') {
    // Alert-Element erstellen
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // In den Container einfügen (vor dem ersten Kind)
    $('.main-container').prepend(alertHtml);
    
    // Nach 5 Sekunden automatisch ausblenden
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

// Zeitstempel formatieren
function formatTimestamp(timestamp, format = null) {
    const date = new Date(timestamp * 1000);
    
    if (format === 'relative') {
        // Relative Zeit (z.B. "vor 5 Minuten")
        const now = new Date();
        const diffSeconds = Math.floor((now - date) / 1000);
        
        if (diffSeconds < 60) {
            return `${diffSeconds} ${diffSeconds === 1 ? 'Sekunde' : 'Sekunden'} ago`;
        }
        
        const diffMinutes = Math.floor(diffSeconds / 60);
        if (diffMinutes < 60) {
            return `${diffMinutes} ${diffMinutes === 1 ? 'Minute' : 'Minuten'} ago`;
        }
        
        const diffHours = Math.floor(diffMinutes / 60);
        if (diffHours < 24) {
            return `${diffHours} ${diffHours === 1 ? 'Stunde' : 'Stunden'} ago`;
        }
        
        const diffDays = Math.floor(diffHours / 24);
        return `${diffDays} ${diffDays === 1 ? 'Tag' : 'Tage'} ago`;
    }
    
    // Standardformat (localeString basierend auf der Sprache)
    const options = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit'
    };
    
    return date.toLocaleString(CURRENT_LANG === 'de' ? 'de-DE' : 'en-US', options);
}

// Timer für verbleibende Ban-Zeit
function initBanTimers() {
    // Alle Timer-Elemente durchgehen
    $('.ban-timer').each(function() {
        const expiresAt = parseInt($(this).data('expires-at'));
        const timerElement = $(this);
        
        // Wenn permanent, nichts tun
        if (expiresAt === 0) {
            timerElement.text('∞');
            return;
        }
        
        // Timer-Funktion
        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const remaining = expiresAt - now;
            
            if (remaining <= 0) {
                timerElement.text('Abgelaufen');
                timerElement.removeClass('status-active').addClass('status-expired');
                return;
            }
            
            // Zeit formatieren
            const days = Math.floor(remaining / 86400);
            const hours = Math.floor((remaining % 86400) / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            
            let timeString = '';
            
            if (days > 0) {
                timeString += `${days}d `;
            }
            
            timeString += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            timerElement.text(timeString);
        }
        
        // Initial aktualisieren
        updateTimer();
        
        // Alle 1 Sekunde aktualisieren
        setInterval(updateTimer, 1000);
    });
}
