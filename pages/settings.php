<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('settings'); ?></h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="api-tab" data-bs-toggle="tab" href="#api" role="tab"><?php echo t('api_settings'); ?></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="chat-server-tab" data-bs-toggle="tab" href="#chat-server" role="tab">Chat-Server (Alpha)</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="account-tab" data-bs-toggle="tab" href="#account" role="tab"><?php echo t('account_settings'); ?></a>
                    </li>
                </ul>
                
                <div class="tab-content" id="settingsTabsContent">
                    <!-- Chat-Server Einstellungen (Alpha) -->
                    <div class="tab-pane fade" id="chat-server" role="tabpanel" aria-labelledby="chat-server-tab">
                        <form id="chatServerSettingsForm">
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Hinweis:</strong> Die Chat-Log-Integration befindet sich in der <b>Alpha</b>-Phase. Fehler und Änderungen vorbehalten!
                            </div>
                            <div class="mb-3">
                                <label for="chat_server_url" class="form-label">Chat-Server URL</label>
                                <input type="url" class="form-control" id="chat_server_url" name="chat_server_url" value="<?php echo isset($config['chat_server']['url']) ? htmlspecialchars($config['chat_server']['url']) : ''; ?>" required placeholder="http://192.168.1.100:5005">
                            </div>
                            <div class="mb-3">
                                <label for="chat_server_port" class="form-label">Port</label>
                                <input type="number" class="form-control" id="chat_server_port" name="chat_server_port" value="<?php echo isset($config['chat_server']['port']) ? intval($config['chat_server']['port']) : 5005; ?>" required min="1" max="65535">
                            </div>
                            <div class="mb-3">
                                <label for="chat_server_username" class="form-label">Benutzername</label>
                                <input type="text" class="form-control" id="chat_server_username" name="chat_server_username" value="<?php echo isset($config['chat_server']['username']) ? htmlspecialchars($config['chat_server']['username']) : ''; ?>" required placeholder="admin">
                            </div>
                            <div class="mb-3">
                                <label for="chat_server_password" class="form-label">Passwort</label>
                                <input type="password" class="form-control" id="chat_server_password" name="chat_server_password" value="<?php echo isset($config['chat_server']['password']) ? htmlspecialchars($config['chat_server']['password']) : ''; ?>" required>
                                <div class="form-text">Das Passwort für den Zugriff auf den Chat-Log-Server (Basic Auth).</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Speichern</button>
                            <button type="button" class="btn btn-secondary ms-2" id="testChatServerButton">Verbindung testen</button>
                            <div id="chatServerTestResult" class="mt-3"></div>
                        </form>
                    </div>
                    <!-- Allgemeine Einstellungen ausblenden -->
                    <!--
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <form id="generalSettingsForm">
                            <div class="mb-3">
                                <label for="language" class="form-label"><?php echo t('language_selection'); ?></label>
                                <select class="form-select" id="language" name="language">
                                    <option value="de" <?php echo $currentLang == 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                    <option value="en" <?php echo $currentLang == 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="theme" class="form-label"><?php echo t('default_theme'); ?></label>
                                <select class="form-select" id="theme" name="theme">
                                    <option value="light" <?php echo $currentTheme == 'light' ? 'selected' : ''; ?>><?php echo t('light_mode'); ?></option>
                                    <option value="dark" <?php echo $currentTheme == 'dark' ? 'selected' : ''; ?>><?php echo t('dark_mode'); ?></option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="refresh_interval" class="form-label"><?php echo t('refresh_interval'); ?></label>
                                <input type="number" class="form-control" id="refresh_interval" name="refresh_interval" min="5" max="60" value="<?php echo $config['settings']['refresh_interval']; ?>">
                                <div class="form-text"><?php echo t('refresh_interval_hint'); ?></div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><?php echo t('save'); ?></button>
                        </form>
                    </div>
                    -->
                    
                    <!-- API-Einstellungen -->
                    <div class="tab-pane fade show active" id="api" role="tabpanel" aria-labelledby="api-tab">
                        <form id="apiSettingsForm">
                            <div class="mb-3">
                                <label for="api_url" class="form-label"><?php echo t('api_url'); ?></label>
                                <input type="url" class="form-control" id="api_url" name="api_url" value="<?php echo htmlspecialchars($config['api']['url']); ?>" required>
                                <div class="form-text" style="background-color: var(--bs-dark); color: var(--bs-light); padding: 8px; border-radius: 4px;">
                                    <strong style="color: var(--bs-light);">Beispiel:</strong> http://example.com:8080<br>
                                    <small style="color: var(--bs-light);">Der Port ist standardmäßig 8080, wenn nicht anders angegeben</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="api_password" class="form-label"><?php echo t('api_password'); ?></label>
                                <input type="password" class="form-control" id="api_password" name="api_password" value="<?php echo htmlspecialchars($config['api']['password']); ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><?php echo t('save'); ?></button>
                            <button type="button" class="btn btn-secondary" id="testConnectionButton"><?php echo t('test_connection'); ?></button>
                            <div id="apiTestResult" class="mt-3"></div>
                        </form>
                    </div>
                    
                    <!-- Kontoeinstellungen -->
                    <div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
                        <form id="changePasswordForm">
                            <div class="mb-3">
                                <label for="current_password" class="form-label"><?php echo t('current_password'); ?></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label"><?php echo t('new_password'); ?></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><?php echo t('confirm_new_password'); ?></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><?php echo t('change_password'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- jQuery (MUSS als erstes geladen werden!) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle mit Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <!-- Settings-spezifisches JavaScript -->
    <script>
    $(document).ready(function() {
    // Chat-Server Einstellungen speichern
    $('#chatServerSettingsForm').on('submit', function(e) {
        e.preventDefault();
        const username = $('#chat_server_username').val();
        const url = $('#chat_server_url').val();
        const port = $('#chat_server_port').val();
        const password = $('#chat_server_password').val();
        if (!username || !url || !port || !password) {
            showNotification('Bitte alle Felder ausfüllen!', 'danger');
            return;
        }
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'save_chat_server_settings',
                chat_server_username: username,
                chat_server_url: url,
                chat_server_port: port,
                chat_server_password: password,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $(e.target).find('button').prop('disabled', true);
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        showNotification('Einstellungen gespeichert', 'success');
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

    // Chat-Server Verbindung testen
    $('#testChatServerButton').on('click', function() {
        const username = $('#chat_server_username').val();
        const url = $('#chat_server_url').val();
        const port = $('#chat_server_port').val();
        const password = $('#chat_server_password').val();
        $('#chatServerTestResult').html('');
        if (!username || !url || !port || !password) {
            $('#chatServerTestResult').html('<div class="alert alert-danger mt-2">Bitte alle Felder ausfüllen!</div>');
            return;
        }
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'test_chat_server_connection',
                chat_server_username: username,
                chat_server_url: url,
                chat_server_port: port,
                chat_server_password: password,
                csrf_token: CSRF_TOKEN
            },
            beforeSend: function() {
                $('#testChatServerButton').prop('disabled', true);
                $('#chatServerTestResult').html('Verbindung wird getestet ...');
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#chatServerTestResult').html('<div class="alert alert-success mt-2">Verbindung erfolgreich!<br>Letzte Logdatei: <b>' + data.data.last_logfile + '</b></div>');
                    } else {
                        $('#chatServerTestResult').html('<div class="alert alert-danger mt-2">' + data.message + '</div>');
                    }
                } catch (error) {
                    $('#chatServerTestResult').html('<div class="alert alert-danger mt-2">Fehler beim Verarbeiten der Antwort</div>');
                }
            },
            error: function() {
                $('#chatServerTestResult').html('<div class="alert alert-danger mt-2">Serverfehler</div>');
            },
            complete: function() {
                $('#testChatServerButton').prop('disabled', false);
            }
        });
    });

    // API-Einstellungen speichern
    $('#apiSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const apiUrl = $('#api_url').val();
        const apiPassword = $('#api_password').val();
        
        // AJAX-Anfrage
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'save_settings',
                api_url: apiUrl,
                api_password: apiPassword,
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
    
    // API-Verbindung testen
    // Robust: Logging und Fehleranzeige
    console.log('Settings.js geladen');
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
            showNotification('<?php echo t('error_password_mismatch'); ?>', 'danger');
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
</script>
