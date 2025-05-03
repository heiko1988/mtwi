<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('settings'); ?></h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <?php if (hasPermission('settings_api')): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="api-tab" data-bs-toggle="tab" href="#api" role="tab"><?php echo t('api_settings'); ?></a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('settings_server')): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="chat-server-tab" data-bs-toggle="tab" href="#chat-server" role="tab">Chat-Server (Alpha)</a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('settings_account')): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="account-tab" data-bs-toggle="tab" href="#account" role="tab"><?php echo t('account_settings'); ?></a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('settings_admins') || isMasterAdmin()): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="admin-management-tab" data-bs-toggle="tab" href="#admin-management" role="tab"><?php echo t('admin_management'); ?></a>
                    </li>
                    <?php endif; ?>
                    
                    <?php /* Rollenverwaltung ausgeblendet
                    <?php if (hasPermission('settings_roles') || isMasterAdmin()): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="role-management-tab" data-bs-toggle="tab" href="#role-management" role="tab">Rollenverwaltung</a>
                    </li>
                    <?php endif; ?>
                    */?>

                </ul>
                
                <!-- Bootstrap 5 Tab-Hilfe-->
                <script>
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
                </script>
                
                <div class="tab-content" id="settingsTabsContent">

                    <!-- Chat-Server Einstellungen (Alpha) -->
                    <div class="tab-pane fade show" id="chat-server" role="tabpanel" aria-labelledby="chat-server-tab">
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
                    <div class="tab-pane fade show" id="api" role="tabpanel" aria-labelledby="api-tab">
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
                    <div class="tab-pane fade show" id="account" role="tabpanel" aria-labelledby="account-tab">
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
                    
                    <?php if (isMasterAdmin() || hasPermission('settings_admins')): ?>
                    <!-- Admin-Verwaltung -->
                    <div class="tab-pane fade show" id="admin-management" role="tabpanel" aria-labelledby="admin-management-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4><?php echo t('admin_accounts'); ?></h4>
                            <button type="button" id="addAdminBtn" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#adminModal">
                                <i class="bi bi-plus-circle"></i> <?php echo t('add_admin'); ?>
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="adminAccountsTable">
                                <thead>
                                    <tr>
                                        <th><?php echo t('username'); ?></th>
                                        <th><?php echo t('role'); ?></th>
                                        <th><?php echo t('status'); ?></th>
                                        <th class="text-end"><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center"><?php echo t('loading'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php /* Rollenverwaltung ausgeblendet 
                    <?php if (hasPermission('settings_roles') || isMasterAdmin()): ?>
                    <!-- Rollenverwaltung -->
                    <div class="tab-pane fade show" id="role-management" role="tabpanel" aria-labelledby="role-management-tab">
                        <div class="mb-4">
                            <h4>Rollenverwaltung</h4>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Hier können Sie die Berechtigungen für jede Rolle anpassen. Die Änderungen werden wirksam, sobald Sie auf "Speichern" klicken.
                            </div>
                            <div id="rolePermissionsContainer">
                                <div class="text-center p-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Wird geladen...</span>
                                    </div>
                                    <p class="mt-3">Berechtigungen werden geladen...</p>
                                </div>
                            </div>
                            
                            <div class="mt-4 d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" id="saveRolesBtn" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Speichern
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php */?>
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
    <!-- Rollenverwaltung JavaScript -->
    <script src="assets/js/role_management.js"></script>
    <!-- Admin-Modal zum Hinzufügen/Bearbeiten von Admins -->
    <?php if (isMasterAdmin() || hasPermission('settings_admins')): ?>
    <div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminModalTitle"><?php echo t('add_admin'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="adminForm">
                    <div class="modal-body">
                        <input type="hidden" id="admin_action" name="admin_action" value="add">
                        <input type="hidden" id="admin_original_username" name="admin_original_username" value="">
                        
                        <div class="mb-3">
                            <label for="admin_username" class="form-label"><?php echo t('username'); ?></label>
                            <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                        </div>
                        
                        <div class="mb-3" id="passwordSection">
                            <label for="admin_password" class="form-label"><?php echo t('password'); ?></label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_role" class="form-label"><?php echo t('role'); ?></label>
                            <select class="form-select" id="admin_role" name="admin_role">
                                <option value="admin"><?php echo t('role_admin'); ?></option>
                                <option value="editor"><?php echo t('role_editor'); ?></option>
                                <option value="viewer"><?php echo t('role_viewer'); ?></option>
                            </select>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="admin_active" name="admin_active" checked>
                            <label class="form-check-label" for="admin_active"><?php echo t('active_account'); ?></label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                        <button type="submit" class="btn btn-primary" id="saveAdminBtn"><?php echo t('save'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Settings-spezifisches JavaScript -->
    <script>
    // Übersetzungsfunktion und Übersetzungen
    function t(key) {
        return translations[key] || key;
    }
    
    // Übersetzungen für JavaScript
    const translations = {
        role_master: '<?php echo t('role_master'); ?>',
        role_admin: '<?php echo t('role_admin'); ?>',
        role_editor: '<?php echo t('role_editor'); ?>',
        role_viewer: '<?php echo t('role_viewer'); ?>',
        active: '<?php echo t('active'); ?>',
        inactive: '<?php echo t('inactive'); ?>',
        master_admin: '<?php echo t('role_master'); ?>',
        edit_admin: '<?php echo t('edit_admin'); ?>',
        add_admin: '<?php echo t('add_admin'); ?>',
        admin_confirm_delete: '<?php echo t('admin_confirm_delete'); ?>'
    };
    
    $(document).ready(function() {
    <?php if (isMasterAdmin() || hasPermission('settings_admins')): ?>
    // Admin-Verwaltung: Admins laden
    function loadAdmins() {
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: {
                action: 'get_admins',
                csrf_token: CSRF_TOKEN
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success && data.data.admins) {
                        updateAdminTable(data.data.admins);
                    } else {
                        showNotification(data.message || 'Fehler beim Laden der Admins', 'danger');
                    }
                } catch (error) {
                    showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
                }
            },
            error: function() {
                showNotification('Serverfehler', 'danger');
            }
        });
    }
    
    // Admin-Tabelle aktualisieren
    function updateAdminTable(admins) {
        const tableBody = $('#adminAccountsTable tbody');
        tableBody.empty();
        
        if (admins.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="4" class="text-center">Keine Administratoren gefunden</td>
                </tr>
            `);
            return;
        }
        
        admins.forEach(function(admin) {
            const roleName = admin.role === 'master' ? t('role_master') : 
                           admin.role === 'admin' ? t('role_admin') : 
                           admin.role === 'editor' ? t('role_editor') : t('role_viewer');
                           
            const statusBadge = admin.active ? 
                `<span class="badge bg-success">${t('active')}</span>` : 
                `<span class="badge bg-danger">${t('inactive')}</span>`;
                
            // Master-Admin kann nicht bearbeitet oder gelöscht werden
            const actionButtons = admin.role === 'master' ? 
                `<span class="text-muted">${t('master_admin')}</span>` : 
                `<button type="button" class="btn btn-sm btn-primary edit-admin-btn" data-bs-toggle="modal" data-bs-target="#adminModal" data-username="${admin.username}" data-role="${admin.role}" data-active="${admin.active}">
                    <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger delete-admin-btn" data-username="${admin.username}">
                    <i class="bi bi-trash"></i>
                </button>`;
            
            tableBody.append(`
                <tr>
                    <td>${admin.username}</td>
                    <td>${roleName}</td>
                    <td>${statusBadge}</td>
                    <td class="text-end">${actionButtons}</td>
                </tr>
            `);
        });
    }
    
    // Admin-Modal vorbereiten
    $('#adminModal').on('show.bs.modal', function(e) {
        const trigger = $(e.relatedTarget);
        const isEdit = trigger.hasClass('edit-admin-btn');
        const modal = $(this);
        
        // Modal-Titel ändern
        modal.find('.modal-title').text(isEdit ? t('edit_admin') : t('add_admin'));
        
        // Hidden-Felder zurücksetzen
        $('#admin_action').val(isEdit ? 'update' : 'add');
        $('#admin_original_username').val(isEdit ? trigger.data('username') : '');
        
        // Formular zurücksetzen und Werte setzen
        $('#adminForm')[0].reset();
        
        if (isEdit) {
            $('#admin_username').val(trigger.data('username'));
            $('#admin_role').val(trigger.data('role'));
            $('#admin_active').prop('checked', trigger.data('active') === true || trigger.data('active') === 'true');
            
            // Bei Bearbeitung ist Passwort optional
            $('#admin_password').removeAttr('required');
            $('#passwordSection').append('<div class="form-text">Leer lassen, um das Passwort nicht zu ändern</div>');
        } else {
            // Bei Neuanlage ist Passwort erforderlich
            $('#admin_password').attr('required', 'required');
            $('#passwordSection .form-text').remove();
        }
    });
    
    // Verbesserte Fehlerbehandlung für Modal-Schließen
    $('#adminModal').on('hidden.bs.modal', function() {
        // Sicherstellen, dass alle Modal-Reste entfernt werden
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });
    
    // Admin hinzufügen/bearbeiten
    $('#adminForm').on('submit', function(e) {
        e.preventDefault();
        
        const action = $('#admin_action').val() === 'update' ? 'update_admin' : 'add_admin';
        const originalUsername = $('#admin_original_username').val();
        const username = $('#admin_username').val();
        const password = $('#admin_password').val();
        const role = $('#admin_role').val();
        const active = $('#admin_active').is(':checked');
        
        // Validierung
        if (!username) {
            showNotification('Bitte Benutzernamen eingeben', 'danger');
            return;
        }
        
        if (action === 'add_admin' && !password) {
            showNotification('Bitte Passwort eingeben', 'danger');
            return;
        }
        
        // AJAX-Anfrage
        const data = {
            action: action,
            username: username,
            role: role,
            active: active.toString(),
            csrf_token: CSRF_TOKEN
        };
        
        if (action === 'update_admin') {
            data.original_username = originalUsername;
        }
        
        if (password) {
            data.password = password;
        }
        
        console.log('Sending admin data:', data);

        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('#saveAdminBtn').prop('disabled', true);
            },
            success: function(response) {
                try {
                    console.log('Response from server:', response);
                    const data = JSON.parse(response);
                    if (data.success) {
                        showNotification(data.message, 'success');
                        
                        // Richtige Reihenfolge für das Modal-Schließen
                        $('#adminModal').modal('hide');
                        
                        // Kurze Verzögerung vor dem Neuladen der Admin-Liste
                        setTimeout(function() {
                            // Admin-Verwaltungs-Tab aktivieren und Admin-Liste neu laden
                            $('#admin-management-tab').tab('show');
                            loadAdmins();
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('padding-right', '');
                        }, 500);
                    } else {
                        showNotification(data.message, 'danger');
                    }
                } catch (error) {
                    console.error('Error parsing JSON:', error, response);
                    showNotification('Fehler beim Verarbeiten der Antwort: ' + error.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error, xhr.responseText);
                showNotification('Serverfehler: ' + error, 'danger');
            },
            complete: function() {
                $('#saveAdminBtn').prop('disabled', false);
            }
        });
    });
    
    // Admin löschen
    $(document).on('click', '.delete-admin-btn', function() {
        const username = $(this).data('username');
        
        if (confirm(t('admin_confirm_delete').replace('{username}', username))) {
            $.ajax({
                url: 'ajax_handler.php',
                type: 'POST',
                data: {
                    action: 'delete_admin',
                    username: username,
                    csrf_token: CSRF_TOKEN
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Admin-Verwaltungs-Tab aktivieren und Admin-Liste neu laden
                            $('#admin-management-tab').tab('show');
                            localStorage.setItem('mtwi_active_settings_tab', 'admin-management');
                            loadAdmins();
                        } else {
                            showNotification(data.message, 'danger');
                        }
                    } catch (error) {
                        showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
                    }
                },
                error: function() {
                    showNotification('Serverfehler', 'danger');
                }
            });
        }
    });
    
    // Initial Admins laden
    loadAdmins();
    <?php endif; ?>
    
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
