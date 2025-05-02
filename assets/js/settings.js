$(document).ready(function() {
    console.log('Settings.js geladen');
    
    // Verbesserte Tab-Aktivierung für die Einstellungsseite
    function activateTab(tabId) {
        console.log('Aktiviere Tab:', tabId);
        
        // Prüfen, ob der Tab existiert
        const tabElement = document.getElementById(tabId + '-tab');
        if (!tabElement) {
            console.warn('Tab-Element nicht gefunden:', tabId);
            return false;
        }
        
        // Bootstrap 5 Tab API verwenden
        try {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
            
            // Speichern des aktiven Tabs
            localStorage.setItem('mtwi_active_settings_tab', tabId);
            console.log('Tab aktiviert:', tabId);
            return true;
        } catch (e) {
            console.error('Fehler beim Aktivieren des Tabs:', e);
            return false;
        }
    }
    
    // Tabs initialisieren
    function initTabs() {
        // Prüfen, ob ein Tab in der URL angegeben ist
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const hash = window.location.hash.substring(1);
        
        let activeTabId = '';
        
        if (hash) {
            activeTabId = hash;
            console.debug('Hash gefunden:', hash);
        } else if (tabParam) {
            activeTabId = tabParam;
            console.debug('Tab-Parameter gefunden:', tabParam);
        } else {
            // Wenn kein Tab angegeben ist, versuche den gespeicherten Tab zu verwenden
            activeTabId = localStorage.getItem('mtwi_active_settings_tab');
            console.debug('Gespeicherter Tab:', activeTabId);
        }
        
        // Wenn kein Tab gefunden wurde, den ersten Tab aktivieren
        if (!activeTabId || !activateTab(activeTabId)) {
            // Ersten verfügbaren Tab finden und aktivieren
            const firstTab = document.querySelector('#settingsTabs a[data-bs-toggle="tab"]');
            if (firstTab) {
                const tabId = firstTab.getAttribute('href').substring(1);
                activateTab(tabId);
            }
        }
        
        // Event-Listener für Tab-Wechsel
        $('#settingsTabs a').on('shown.bs.tab', function(e) {
            const id = $(e.target).attr('href').substring(1);
            console.log('Tab changed to:', id);
            localStorage.setItem('mtwi_active_settings_tab', id);
        });
    }
    
    // Tabs initialisieren
    initTabs();
    
    // Bootstrap 5 Tab-Hilfe - Ersatz für das entfernte Inline-Script
    $(document).on('shown.bs.tab', 'a[data-bs-toggle="tab"]', function (e) {
        // Alte Tab-Inhalte verstecken
        $('.tab-pane').removeClass('show active');
        
        // Neue Tab-Inhalte anzeigen
        var target = $(e.target).attr('data-bs-target');
        if (!target) {
            target = $(e.target).attr('href');
        }
        
        $(target).addClass('show active');
        console.log('Tab switched to: ' + target);
    });
    
    // Sprachauswahl auf der Einstellungsseite
    $('#language').on('change', function() {
        const newLanguage = $(this).val();
        console.log('Sprache geändert zu:', newLanguage);
        
        // Sprache in Session speichern
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'set_language',
                language: newLanguage,
                csrf_token: CSRF_TOKEN
            },
            success: function(response) {
                console.log('Sprachänderung erfolgreich', response);
                // Seite neu laden, um die Sprachänderung zu übernehmen
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Fehler bei Sprachänderung:', error);
            }
        });
    });
    
    // Theme-Umschalter - sowohl in der Navigationsleiste als auch in den Einstellungen
    $(document).on('click', '#toggle-theme', function(e) {
        e.preventDefault();
        
        const currentTheme = $('body').attr('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        console.log('Theme geändert zu:', newTheme);
        
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
            },
            success: function(response) {
                console.log('Theme-Änderung erfolgreich', response);
            },
            error: function(xhr, status, error) {
                console.error('Fehler bei Theme-Änderung:', error);
            }
        });
    });
    
    // Allgemeine Einstellungen speichern
    $('#generalSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const language = $('#language').val();
        const refreshInterval = $('#refresh_interval').val();
        
        // AJAX-Anfrage zum Speichern der allgemeinen Einstellungen
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'save_general_settings',
                language: language,
                refresh_interval: refreshInterval,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $(e.target).find('button').prop('disabled', true);
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        showNotification(data.message || 'Einstellungen gespeichert', 'success');
                        // Seite neu laden, um die Änderungen zu übernehmen
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification(data.message || 'Fehler beim Speichern der Einstellungen', 'danger');
                    }
                } catch (error) {
                    console.error('Fehler beim Verarbeiten der Antwort:', error);
                    showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', status, error);
                showNotification('Serverfehler', 'danger');
            },
            complete: function() {
                $(e.target).find('button').prop('disabled', false);
            }
        });
    });
    
    // API-Verbindung testen
    $('#testConnectionButton').on('click', function() {
        console.log('Verbindung testen Button geklickt');
        const apiUrl = $('#api_url').val();
        const apiPassword = $('#api_password').val();

        if (!apiUrl || !apiPassword) {
            $('#apiTestResult').html('<div class="alert alert-danger mt-2">Bitte URL und Passwort eingeben!</div>');
            return;
        }

        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'setup_step',
                step: 2,
                api_url: apiUrl,
                api_password: apiPassword,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $('#apiSettingsForm button').prop('disabled', true);
                $('#apiTestResult').html('');
            },
            success: function(response) {
                console.log('AJAX success:', response);
                let alertType = 'danger';
                let message = 'Unbekannter Fehler';
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        alertType = 'success';
                        message = data.message;
                    } else {
                        message = data.message;
                    }
                } catch (error) {
                    message = 'Fehler beim Verarbeiten der Antwort';
                }
                $('#apiTestResult').html('<div class="alert alert-' + alertType + ' mt-2">' + message + '</div>');
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error);
                $('#apiTestResult').html('<div class="alert alert-danger mt-2">Serverfehler</div>');
            },
            complete: function() {
                $('#apiSettingsForm button').prop('disabled', false);
            }
        });
    });
    
    // Passwort ändern
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const currentPassword = $('#current_password').val();
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        
        // Passwörter überprüfen
        if (newPassword !== confirmPassword) {
            showNotification('Passwörter stimmen nicht überein', 'danger');
            return;
        }
        
        // AJAX-Anfrage
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'change_password',
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $(e.target).find('button').prop('disabled', true);
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        $('#changePasswordForm')[0].reset();
                    } else {
                        showNotification(data.message, 'danger');
                    }
                } catch (error) {
                    showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
                }
            },
            error: function() {
                showNotification('Serverfehler', 'danger');
            },
            complete: function() {
                $(e.target).find('button').prop('disabled', false);
            }
        });
    });
});
