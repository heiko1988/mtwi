<?php
/**
 * Motor Town Web Interface (MTWI) - Rollenverwaltung
 */

// Nur Master-Admin darf auf die Rollenverwaltung zugreifen
if (!isMasterAdmin()) {
    displayError(t('access_denied'), t('access_denied_message'));
    return;
}

// Rolle aktualisieren
if (isset($_POST['update_role']) && isset($_POST['role_id'])) {
    $roleId = $_POST['role_id'];
    $role = getRole($roleId);
    
    if (!$role) {
        setFlashMessage(t('role_not_found'), 'danger');
        header('Location: index.php?page=settings&tab=roles');
        exit;
    }
    
    // Berechtigungen aus dem Formular sammeln
    $permissions = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'perm_') === 0) {
            $permName = substr($key, 5);
            $permissions[$permName] = ($value === '1');
        }
    }
    
    // Log the permissions being sent for debugging
    error_log("Permissions being updated for role {$roleId}: " . json_encode($permissions));
    
    // Rolle aktualisieren
    if (updateRolePermissions($roleId, $permissions)) {
        // Erfolg anzeigen und weiterleiten
        echo "<script type='text/javascript'>
            // Erfolgsmeldung
            showNotification('" . t('role_updated') . "', 'success');
            
            // Weiterleitung nach kurzer Verzögerung
            setTimeout(function() {
                window.location.href = 'index.php?page=settings#roles';
            }, 500);
        </script>";
    } else {
        // Fehlermeldung anzeigen
        echo '<script>
            showNotification("'.t('role_update_failed').'", "danger");
        </script>';
    }
    
    // Nicht weitermachen, JavaScript übernimmt die Weiterleitung
    exit;
}

// Neue Rolle erstellen
if (isset($_POST['create_role']) && isset($_POST['new_role_id']) && isset($_POST['new_role_name'])) {
    $roleId = trim($_POST['new_role_id']);
    $roleName = trim($_POST['new_role_name']);
    
    if (empty($roleId) || empty($roleName)) {
        setFlashMessage(t('role_fields_required'), 'danger');
        header('Location: index.php?page=settings&tab=roles');
        exit;
    }
    
    // Rolle erstellen (zunächst ohne Berechtigungen)
    if (createRole($roleId, $roleName)) {
        setFlashMessage(t('role_created'), 'success');
    } else {
        setFlashMessage(t('role_creation_failed'), 'danger');
    }
    
    header('Location: index.php?page=settings&tab=roles');
    exit;
}

// Rolle löschen
if (isset($_POST['delete_role']) && isset($_POST['role_id'])) {
    $roleId = $_POST['role_id'];
    
    // Rolle löschen
    if (deleteRole($roleId)) {
        setFlashMessage(t('role_deleted'), 'success');
    } else {
        setFlashMessage(t('role_deletion_failed'), 'danger');
    }
    
    header('Location: index.php?page=settings&tab=roles');
    exit;
}

// Alle Rollen abrufen
$roles = getAllRoles(true);
?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?php echo t('role_management'); ?></h5>
    </div>
    <div class="card-body">
        <p class="mb-4"><?php echo t('role_management_description'); ?></p>
        
        <!-- Neue Rolle erstellen -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><?php echo t('create_new_role'); ?></h6>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=settings&tab=roles">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="new_role_id"><?php echo t('role_id'); ?></label>
                                <input type="text" class="form-control" id="new_role_id" name="new_role_id" required 
                                    pattern="[a-z0-9_]+" title="<?php echo t('role_id_format'); ?>">
                                <div class="form-text"><?php echo t('role_id_help'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="new_role_name"><?php echo t('role_name'); ?></label>
                                <input type="text" class="form-control" id="new_role_name" name="new_role_name" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="create_role" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> <?php echo t('create_role'); ?>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Rollenliste und Berechtigungen -->
        <ul class="nav nav-tabs" id="roleTabs" role="tablist">
            <?php $first = true; foreach ($roles as $roleId => $roleData): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                        id="role-<?php echo $roleId; ?>-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#role-<?php echo $roleId; ?>" 
                        type="button" 
                        role="tab" 
                        aria-controls="role-<?php echo $roleId; ?>" 
                        aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
                        <?php echo htmlspecialchars($roleData['name']); ?>
                    </button>
                </li>
            <?php $first = false; endforeach; ?>
        </ul>
        
        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="roleTabContent">
            <?php $first = true; foreach ($roles as $roleId => $roleData): ?>
                <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
                    id="role-<?php echo $roleId; ?>" 
                    role="tabpanel" 
                    aria-labelledby="role-<?php echo $roleId; ?>-tab">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><?php echo htmlspecialchars($roleData['name']); ?> (<?php echo $roleId; ?>)</h5>
                        
                        <?php if (!in_array($roleId, ['master', 'admin', 'moderator', 'viewer'])): ?>
                            <form method="post" action="index.php?page=settings&tab=roles" class="d-inline" onsubmit="return confirm('<?php echo t('confirm_delete_role'); ?>');">
                                <input type="hidden" name="role_id" value="<?php echo $roleId; ?>">
                                <button type="submit" name="delete_role" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash"></i> <?php echo t('delete'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="index.php?page=settings&tab=roles">
                        <input type="hidden" name="role_id" value="<?php echo $roleId; ?>">
                        
                        <div class="row">
                            <!-- Dashboard-Berechtigungen -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0"><?php echo t('dashboard_permissions'); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_dashboard_view_<?php echo $roleId; ?>" 
                                                name="perm_dashboard_view" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['dashboard_view']) && $roleData['permissions']['dashboard_view']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_dashboard_view_<?php echo $roleId; ?>">
                                                <?php echo t('dashboard_view_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_dashboard_server_control_<?php echo $roleId; ?>" 
                                                name="perm_dashboard_server_control" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['dashboard_server_control']) && $roleData['permissions']['dashboard_server_control']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_dashboard_server_control_<?php echo $roleId; ?>">
                                                <?php echo t('dashboard_server_control_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_server_stats_<?php echo $roleId; ?>" 
                                                name="perm_server_stats" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['server_stats']) && $roleData['permissions']['server_stats']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_server_stats_<?php echo $roleId; ?>">
                                                <?php echo t('server_stats_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_player_list_<?php echo $roleId; ?>" 
                                                name="perm_player_list" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['player_list']) && $roleData['permissions']['player_list']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_player_list_<?php echo $roleId; ?>">
                                                <?php echo t('player_list_permission'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Spieler-Moderationsberechtigungen -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0"><?php echo t('player_moderation_permissions'); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_player_kick_<?php echo $roleId; ?>" 
                                                name="perm_player_kick" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['player_kick']) && $roleData['permissions']['player_kick']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_player_kick_<?php echo $roleId; ?>">
                                                <?php echo t('player_kick_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_player_ban_<?php echo $roleId; ?>" 
                                                name="perm_player_ban" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['player_ban']) && $roleData['permissions']['player_ban']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_player_ban_<?php echo $roleId; ?>">
                                                <?php echo t('player_ban_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_ban_list_<?php echo $roleId; ?>" 
                                                name="perm_ban_list" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['ban_list']) && $roleData['permissions']['ban_list']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_ban_list_<?php echo $roleId; ?>">
                                                <?php echo t('ban_list_permission'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Chat-Berechtigungen -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0"><?php echo t('chat_permissions'); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_chat_view_<?php echo $roleId; ?>" 
                                                name="perm_chat_view" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['chat_view']) && $roleData['permissions']['chat_view']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_chat_view_<?php echo $roleId; ?>">
                                                <?php echo t('chat_view_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_chat_send_<?php echo $roleId; ?>" 
                                                name="perm_chat_send" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['chat_send']) && $roleData['permissions']['chat_send']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_chat_send_<?php echo $roleId; ?>">
                                                <?php echo t('chat_send_permission'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Einstellungsberechtigungen -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0"><?php echo t('settings_permissions'); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_settings_view_<?php echo $roleId; ?>" 
                                                name="perm_settings_view" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['settings_view']) && $roleData['permissions']['settings_view']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_settings_view_<?php echo $roleId; ?>">
                                                <?php echo t('settings_view_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_settings_account_<?php echo $roleId; ?>" 
                                                name="perm_settings_account" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['settings_account']) && $roleData['permissions']['settings_account']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_settings_account_<?php echo $roleId; ?>">
                                                <?php echo t('settings_account_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_settings_server_<?php echo $roleId; ?>" 
                                                name="perm_settings_server" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['settings_server']) && $roleData['permissions']['settings_server']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_settings_server_<?php echo $roleId; ?>">
                                                <?php echo t('settings_server_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_settings_api_<?php echo $roleId; ?>" 
                                                name="perm_settings_api" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['settings_api']) && $roleData['permissions']['settings_api']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_settings_api_<?php echo $roleId; ?>">
                                                <?php echo t('settings_api_permission'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Admin-Verwaltung -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0"><?php echo t('admin_management_permissions'); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_settings_admins_<?php echo $roleId; ?>" 
                                                name="perm_settings_admins" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['settings_admins']) && $roleData['permissions']['settings_admins']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_settings_admins_<?php echo $roleId; ?>">
                                                <?php echo t('settings_admins_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_admin_add_<?php echo $roleId; ?>" 
                                                name="perm_admin_add" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['admin_add']) && $roleData['permissions']['admin_add']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_admin_add_<?php echo $roleId; ?>">
                                                <?php echo t('admin_add_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_admin_edit_<?php echo $roleId; ?>" 
                                                name="perm_admin_edit" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['admin_edit']) && $roleData['permissions']['admin_edit']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_admin_edit_<?php echo $roleId; ?>">
                                                <?php echo t('admin_edit_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_admin_delete_<?php echo $roleId; ?>" 
                                                name="perm_admin_delete" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['admin_delete']) && $roleData['permissions']['admin_delete']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_admin_delete_<?php echo $roleId; ?>">
                                                <?php echo t('admin_delete_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_master_admin_edit_<?php echo $roleId; ?>" 
                                                name="perm_master_admin_edit" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['master_admin_edit']) && $roleData['permissions']['master_admin_edit']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_master_admin_edit_<?php echo $roleId; ?>">
                                                <?php echo t('master_admin_edit_permission'); ?>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                id="perm_settings_roles_<?php echo $roleId; ?>" 
                                                name="perm_settings_roles" 
                                                value="1" 
                                                <?php echo (isset($roleData['permissions']['settings_roles']) && $roleData['permissions']['settings_roles']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_settings_roles_<?php echo $roleId; ?>">
                                                <?php echo t('settings_roles_permission'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="update_role" class="btn btn-primary">
                                <i class="bi bi-save"></i> <?php echo t('save_changes'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php $first = false; endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Speichern des aktiven Tabs
    document.addEventListener('DOMContentLoaded', function() {
        const triggerTabList = [].slice.call(document.querySelectorAll('#roleTabs button'));
        triggerTabList.forEach(function(triggerEl) {
            triggerEl.addEventListener('click', function(event) {
                localStorage.setItem('activeRoleTab', event.target.getAttribute('id'));
            });
        });
        
        // Wiederherstellung des gespeicherten Tabs
        const activeTab = localStorage.getItem('activeRoleTab');
        if (activeTab) {
            const tab = document.querySelector('#' + activeTab);
            if (tab) {
                const bsTab = new bootstrap.Tab(tab);
                bsTab.show();
            }
        }
    });
</script>
