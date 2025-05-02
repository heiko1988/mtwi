/**
 * MTWI - Rollenverwaltung JavaScript
 * 
 * Ermöglicht die Verwaltung von Rollen und deren Berechtigungen.
 * Hierarchische Berechtigungsstruktur:
 * - Master Admin kann alle Rollen bearbeiten
 * - Administrator kann nur Moderator und Betrachter bearbeiten
 * - Moderator kann nur Betrachter bearbeiten
 */

// Objekt zum Speichern der aktuellen Berechtigungen
let currentPermissions = {};
let currentUserRole = '';
let permissionDescriptions = {};

// Gruppierung der Berechtigungen nach Funktionsbereich
const permissionGroups = {
    'dashboard': {
        title: 'Dashboard',
        description: 'Berechtigungen für das Dashboard und die Serversteuerung',
        permissions: ['dashboard_view', 'dashboard_server_control']
    },
    'players': {
        title: 'Spielerverwaltung',
        description: 'Berechtigungen für die Verwaltung von Spielern, Bans und Kicks',
        permissions: ['player_view', 'player_list', 'player_history', 'player_ban', 'player_kick']
    },
    'chat': {
        title: 'Chat',
        description: 'Berechtigungen für den Live-Chat',
        permissions: ['chat_view', 'chat_send']
    },
    'server': {
        title: 'Server',
        description: 'Berechtigungen für Serverstatistiken und Banlisten',
        permissions: ['ban_list', 'server_stats']
    },
    'settings': {
        title: 'Einstellungen',
        description: 'Berechtigungen für die Einstellungen des Systems',
        permissions: ['settings_view', 'settings_account', 'settings_server', 'settings_api', 'settings_admins', 'settings_roles']
    },
    'admin': {
        title: 'Administration',
        description: 'Berechtigungen für die Verwaltung von Administratoren',
        permissions: ['admin_add', 'admin_edit', 'admin_delete', 'master_admin_edit']
    }
};

// Beschreibungen der einzelnen Berechtigungen
function initPermissionDescriptions() {
    permissionDescriptions = {
        'dashboard_view': 'Dashboard anzeigen',
        'dashboard_server_control': 'Server starten/stoppen/neustarten',
        'player_view': 'Spielerdetails anzeigen',
        'player_list': 'Liste aktiver Spieler anzeigen',
        'player_history': 'Spielerhistorie anzeigen',
        'player_ban': 'Spieler bannen',
        'player_kick': 'Spieler kicken',
        'chat_view': 'Chat anzeigen',
        'chat_send': 'Chat-Nachrichten senden',
        'ban_list': 'Banliste anzeigen',
        'server_stats': 'Serverstatistiken anzeigen',
        'settings_view': 'Einstellungen anzeigen',
        'settings_account': 'Kontoeinstellungen bearbeiten',
        'settings_server': 'Servereinstellungen bearbeiten',
        'settings_api': 'API-Einstellungen bearbeiten',
        'settings_admins': 'Administratoren verwalten',
        'settings_roles': 'Rollen und Berechtigungen verwalten',
        'admin_add': 'Administratoren hinzufügen',
        'admin_edit': 'Administratoren bearbeiten',
        'admin_delete': 'Administratoren löschen',
        'master_admin_edit': 'Master-Admin bearbeiten'
    };
}

// Berechtigungen vom Server laden
function loadPermissions() {
    console.log('Loading permissions...');
    $.ajax({
        url: 'ajax_handler.php',
        type: 'POST',
        data: {
            action: 'get_permissions',
            csrf_token: CSRF_TOKEN
        },
        beforeSend: function() {
            // Ladeanzeige
            $('#rolePermissionsContainer').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Laden...</span></div></div>');
        },
        success: function(response) {
            try {
                console.log('Got response:', response);
                const data = JSON.parse(response);
                
                if (data.success) {
                    console.log('Permissions loaded successfully, current role:', data.data.current_role);
                    // Globale Variablen setzen
                    currentPermissions = data.data.permissions;
                    currentUserRole = data.data.current_role;
                    
                    // Beschreibungen initialisieren
                    initPermissionDescriptions();
                    
                    // UI rendern
                    renderPermissionUI();
                } else {
                    console.error('Error loading permissions:', data.message);
                    showNotification(data.message || 'Fehler beim Laden der Berechtigungen', 'danger');
                    $('#rolePermissionsContainer').html('<div class="alert alert-danger">Fehler beim Laden der Berechtigungen</div>');
                }
            } catch (error) {
                console.error('Fehler beim Parsen der JSON-Antwort:', error, response);
                $('#rolePermissionsContainer').html('<div class="alert alert-danger">Fehler beim Verarbeiten der Antwort</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            $('#rolePermissionsContainer').html('<div class="alert alert-danger">Serverfehler beim Laden der Berechtigungen</div>');
        }
    });
}

// Rollen rendern, die der aktuelle Benutzer bearbeiten darf
function renderPermissionUI() {
    const container = $('#rolePermissionsContainer');
    container.empty();
    
    console.log('Render UI, current role:', currentUserRole);
    
    // Liste der Rollen erstellen
    const roles = Object.keys(currentPermissions.roles);
    const editableRoles = getEditableRoles(currentUserRole, roles);
    
    console.log('Editable roles:', editableRoles);
    
    // Wenn keine bearbeitbaren Rollen vorhanden sind, Meldung anzeigen
    if (editableRoles.length === 0) {
        container.append(`<div class="alert alert-info">Sie haben keine Berechtigungen, Rollen zu bearbeiten.</div>`);
        return;
    }
    
    // Rollenauswahl hinzufügen
    const tabsHtml = `
        <ul class="nav nav-tabs mb-4" id="rolesTabs" role="tablist">
            ${editableRoles.map((role, index) => `
                <li class="nav-item" role="presentation">
                    <button class="nav-link ${index === 0 ? 'active' : ''}" 
                       id="role-${role}-tab" 
                       data-bs-toggle="tab" 
                       data-bs-target="#role-${role}" 
                       role="tab" 
                       aria-controls="role-${role}"
                       aria-selected="${index === 0 ? 'true' : 'false'}">
                        ${getRoleName(role)}
                    </button>
                </li>
            `).join('')}
        </ul>
    `;
    
    container.append(tabsHtml);
    
    // Tab-Inhalte für jede Rolle erstellen
    const tabContent = $('<div class="tab-content" id="rolesTabContent"></div>');
    
    editableRoles.forEach((role, index) => {
        const roleTab = $(`<div class="tab-pane fade ${index === 0 ? 'show active' : ''}" id="role-${role}" role="tabpanel" aria-labelledby="role-${role}-tab"></div>`);
        
        // Debug-Informationen hinzufügen
        roleTab.append(`<div class="debug-info mb-2" style="display: none;">Debug: Role = ${role}, Index = ${index}</div>`);
        
        // Gruppierte Berechtigungen anzeigen
        Object.keys(permissionGroups).forEach(groupKey => {
            const group = permissionGroups[groupKey];
            
            const permissionCard = $(`
                <div class="card mb-3">
                    <div class="card-header bg-secondary">
                        <h5 class="mb-0 text-white">${group.title}</h5>
                        <small class="text-white-50">${group.description}</small>
                    </div>
                    <div class="card-body">
                        <div class="row" id="${groupKey}-permissions-${role}"></div>
                    </div>
                </div>
            `);
            
            const permissionContainer = permissionCard.find(`#${groupKey}-permissions-${role}`);
            
            // Checkboxen für jede Berechtigung in der Gruppe
            group.permissions.forEach(permissionKey => {
                const hasPermission = currentPermissions.roles[role].permissions[permissionKey] === true;
                const permissionName = permissionDescriptions[permissionKey] || permissionKey;
                
                // Bestimmen, ob die Berechtigung bearbeitbar ist (ein Admin darf keine Admin-Rechte für Mods hinzufügen)
                const isAdminPermission = permissionKey.startsWith('admin_') || 
                                       permissionKey === 'settings_admins' || 
                                       permissionKey === 'master_admin_edit';
                const isDisabled = (currentUserRole === 'admin' && isAdminPermission && role === 'moderator');
                
                permissionContainer.append(`
                    <div class="col-md-6 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   id="${permissionKey}-${role}" 
                                   data-role="${role}" 
                                   data-permission="${permissionKey}" 
                                   ${hasPermission ? 'checked' : ''}
                                   ${isDisabled ? 'disabled' : ''}>
                            <label class="form-check-label" for="${permissionKey}-${role}">
                                ${permissionName}
                                ${isDisabled ? '<small class="text-muted">(Nur Master Admin kann diese Berechtigung ändern)</small>' : ''}
                            </label>
                        </div>
                    </div>
                `);
            });
            
            roleTab.append(permissionCard);
        });
        
        tabContent.append(roleTab);
    });
    
    container.append(tabContent);
    
    // Bootstrap 5 Tab-Initialisierung 
    const triggerTabList = document.querySelectorAll('#rolesTabs button')
    triggerTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', event => {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    // Event Listener für den Speichern-Button
    $('#saveRolesBtn').off('click').on('click', savePermissions);
}

// Bestimmt, welche Rollen der Benutzer bearbeiten darf
function getEditableRoles(userRole, allRoles) {
    // Rollengewichte definieren (höher = mehr Macht)
    const roleWeights = {
        'master': 4,
        'admin': 3,
        'moderator': 2,
        'viewer': 1
    };
    
    const userRoleWeight = roleWeights[userRole] || 0;
    
    // Nur Rollen mit niedrigerem Gewicht als der aktuelle Benutzer sind bearbeitbar
    // Master Admin kann alle Rollen bearbeiten (auch seine eigene)
    // Andere Rollen dürfen nur niedrigere Rollen bearbeiten
    return allRoles.filter(role => {
        const roleWeight = roleWeights[role] || 0;
        return userRole === 'master' || (roleWeight < userRoleWeight);
    }).sort((a, b) => roleWeights[b] - roleWeights[a]); // Sortieren nach Gewicht
}

// Benutzerfreundlichen Namen für Rolle zurückgeben
function getRoleName(roleKey) {
    const roleNames = {
        'master': 'Master Admin',
        'admin': 'Administrator',
        'moderator': 'Moderator',
        'viewer': 'Betrachter'
    };
    
    return roleNames[roleKey] || roleKey;
}

// Berechtigungen speichern
function savePermissions() {
    const updatedPermissions = JSON.parse(JSON.stringify(currentPermissions));
    const editableRoles = getEditableRoles(currentUserRole, Object.keys(currentPermissions.roles));
    
    // Berechtigungen aus dem UI aktualisieren
    editableRoles.forEach(role => {
        Object.keys(permissionGroups).forEach(groupKey => {
            permissionGroups[groupKey].permissions.forEach(permissionKey => {
                const checkbox = $(`#${permissionKey}-${role}`);
                if (checkbox.length && !checkbox.prop('disabled')) {
                    // Nur nicht-deaktivierte Checkboxen berücksichtigen
                    updatedPermissions.roles[role].permissions[permissionKey] = checkbox.prop('checked');
                }
            });
        });
    });
    
    // Validierung: Ein Administrator darf keine Admin-Rechte an Moderatoren vergeben
    if (currentUserRole === 'admin') {
        let hasInvalidChanges = false;
        
        // Prüfen, ob Moderator Admin-Rechte bekommen sollte
        if (editableRoles.includes('moderator')) {
            const modPermissions = updatedPermissions.roles['moderator'].permissions;
            const currentModPermissions = currentPermissions.roles['moderator'].permissions;
            
            // Liste der Admin-Berechtigungen
            const adminPermissions = [
                'settings_admins', 'admin_add', 'admin_edit', 'admin_delete', 'master_admin_edit'
            ];
            
            // Prüfen, ob ein Admin versucht, Admin-Rechte an Moderator zu vergeben
            adminPermissions.forEach(perm => {
                if (modPermissions[perm] && !currentModPermissions[perm]) {
                    // Versuch, eine neue Admin-Berechtigung zu gewähren
                    hasInvalidChanges = true;
                    // Auf aktuellen Status zurücksetzen
                    modPermissions[perm] = currentModPermissions[perm];
                }
            });
        }
        
        if (hasInvalidChanges) {
            showNotification('Sie dürfen keine Administrator-Berechtigungen an Moderatoren vergeben. Einige Änderungen wurden zurückgesetzt.', 'warning');
        }
    }
    
    // Speicherbutton deaktivieren während des Speicherns
    $('#saveRolesBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Speichern...');
    
    // AJAX-Anfrage zum Speichern der Berechtigungen
    $.ajax({
        url: 'ajax_handler.php',
        type: 'POST',
        data: {
            action: 'save_permissions',
            permissions: JSON.stringify(updatedPermissions),
            csrf_token: CSRF_TOKEN
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                
                if (data.success) {
                    showNotification('Berechtigungen erfolgreich gespeichert', 'success');
                    // Berechtigungen neu laden
                    loadPermissions();
                } else {
                    showNotification(data.message || 'Fehler beim Speichern der Berechtigungen', 'danger');
                }
            } catch (error) {
                console.error('Fehler beim Parsen der JSON-Antwort:', error);
                showNotification('Fehler beim Verarbeiten der Antwort', 'danger');
            }
        },
        error: function() {
            showNotification('Serverfehler', 'danger');
        },
        complete: function() {
            $('#saveRolesBtn').prop('disabled', false).html('<i class="bi bi-save"></i> Speichern');
        }
    });
}

// Skript initialisieren
$(document).ready(function() {
    // Nur ausführen, wenn der Rollenverwaltungs-Tab existiert
    if ($('#role-management-tab').length) {
        console.log('Role management tab exists');
        
        // CSS für den Dunkelmodus anpassen
        if (document.body.classList.contains('dark-mode') || 
            localStorage.getItem('mtwi_theme') === 'dark') {
            // Custom CSS für bessere Lesbarkeit im Dunkelmodus
            $('head').append(`
                <style>
                    #role-management .card-header.bg-secondary h5,
                    #role-management .card-header.bg-secondary small {
                        color: #fff !important;
                    }
                    #role-management .form-check-label {
                        color: var(--bs-body-color) !important;
                    }
                    #role-management .text-muted {
                        color: var(--bs-gray-500) !important;
                    }
                </style>
            `);
        }
        
        // Berechtigungen laden, wenn der Tab angezeigt wird
        $('#role-management-tab').on('shown.bs.tab', function() {
            console.log('Role management tab shown, loading permissions');
            loadPermissions();
        });
        
        // Bootstrap 5 Tab-Ereignisse für die Rollen-Tabs
        $(document).on('click', '#rolesTabs button', function (e) {
            e.preventDefault();
            console.log('Role tab clicked:', $(this).attr('id'));
            $(this).tab('show');
        });
        
        // Tab-Wechsel-Ereignis für Debugging
        $(document).on('shown.bs.tab', 'button[data-bs-toggle="tab"]', function (e) {
            console.log('Tab shown:', $(e.target).data('bs-target'));
        });
        
        // Wenn der Tab bereits aktiv ist (z.B. nach Seitenneuladen mit localStorage)
        if ($('#role-management-tab').hasClass('active') || 
            localStorage.getItem('mtwi_active_settings_tab') === 'role-management') {
            console.log('Role management tab is active, loading permissions');
            loadPermissions();
        }
        
        // Debug: Zeige Tab-Status nach dem Laden
        setTimeout(function() {
            console.log('Active tabs:', $('.tab-pane.active').attr('id'));
        }, 1000);
    }
});
