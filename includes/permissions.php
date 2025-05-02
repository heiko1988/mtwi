<?php
/**
 * Motor Town Web Interface (MTWI) - Berechtigungsfunktionen
 */

/**
 * Prüft, ob der aktuelle Benutzer eine bestimmte Berechtigung hat
 * 
 * @param string $permission Name der zu prüfenden Berechtigung
 * @return bool True wenn der Benutzer die Berechtigung hat, sonst false
 */
function hasPermission($permission) {
    global $config;
    
    // Wenn der Benutzer nicht angemeldet ist, keine Berechtigung
    if (!isLoggedIn()) {
        return false;
    }
    
    // Master-Admin hat immer alle Berechtigungen
    if (isMasterAdmin()) {
        return true;
    }
    
    // Rolle des aktuellen Benutzers abrufen
    $role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'viewer';
    
    // Prüfen, ob die Rolle existiert
    if (!isset($config['permissions']['roles'][$role])) {
        // Fallback auf niedrigste Berechtigungsstufe, wenn Rolle nicht existiert
        $role = 'viewer';
    }
    
    // Prüfen, ob die Berechtigung für diese Rolle existiert
    if (!isset($config['permissions']['roles'][$role]['permissions'][$permission])) {
        return false;
    }
    
    // Berechtigung zurückgeben
    return $config['permissions']['roles'][$role]['permissions'][$permission];
}

/**
 * Prüft, ob der aktuelle Benutzer eine bestimmte Rolle hat
 * 
 * @param string $role Name der zu prüfenden Rolle
 * @return bool True wenn der Benutzer die Rolle hat, sonst false
 */
function hasRole($role) {
    // Wenn der Benutzer nicht angemeldet ist, keine Berechtigung
    if (!isLoggedIn()) {
        return false;
    }
    
    // Master-Admin hat die höchste Rolle
    if ($role === 'master' && isMasterAdmin()) {
        return true;
    }
    
    // Ansonsten die Rolle aus der Session abrufen und vergleichen
    $userRole = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'viewer';
    
    return $userRole === $role;
}

/**
 * Gibt alle verfügbaren Rollen zurück
 * 
 * @param bool $includePermissions Wenn true, werden auch die Berechtigungen zurückgegeben
 * @return array Array mit Rollennamen und ggf. Berechtigungen
 */
function getAllRoles($includePermissions = false) {
    global $config;
    
    if (!isset($config['permissions']['roles'])) {
        return [];
    }
    
    $roles = [];
    
    foreach ($config['permissions']['roles'] as $roleId => $roleData) {
        if ($includePermissions) {
            $roles[$roleId] = $roleData;
        } else {
            $roles[$roleId] = [
                'name' => $roleData['name']
            ];
        }
    }
    
    return $roles;
}

/**
 * Gibt eine bestimmte Rolle mit allen Berechtigungen zurück
 * 
 * @param string $roleId ID der Rolle
 * @return array|null Rolleninformationen oder null wenn die Rolle nicht existiert
 */
function getRole($roleId) {
    global $config;
    
    if (!isset($config['permissions']['roles'][$roleId])) {
        return null;
    }
    
    return $config['permissions']['roles'][$roleId];
}

/**
 * Aktualisiert die Berechtigungen einer Rolle
 * 
 * @param string $roleId ID der zu aktualisierenden Rolle
 * @param array $newPermissions Neue Berechtigungen
 * @return bool True bei Erfolg, false bei Fehler
 */
function updateRolePermissions($roleId, $newPermissions) {
    global $config;
    
    // Sicherstellen, dass nur der Master-Admin Rollen bearbeiten kann
    if (!isMasterAdmin()) {
        return false;
    }
    
    // Prüfen, ob die Rolle existiert
    if (!isset($config['permissions']['roles'][$roleId])) {
        return false;
    }
    
    // Alte Berechtigungen behalten
    $currentPermissions = $config['permissions']['roles'][$roleId]['permissions'];
    
    // Nur die Berechtigungen aktualisieren, die übergeben wurden
    foreach ($newPermissions as $permissionName => $value) {
        // Zulässige Werte sind nur true und false
        $value = (bool)$value;
        $currentPermissions[$permissionName] = $value;
    }
    
    // Berechtigungen aktualisieren
    $config['permissions']['roles'][$roleId]['permissions'] = $currentPermissions;
    
    // Debug-Log für Entwicklung
    error_log("Updating role permissions for {$roleId}. New permissions: " . json_encode($currentPermissions));
    
    // Konfiguration speichern
    $result = saveConfig($config);
    
    if ($result) {
        // Session aktualisieren, falls der aktuelle Benutzer die geänderte Rolle hat
        if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $roleId) {
            // Administratorsitzung neu laden, damit Änderungen sofort wirksam werden
            error_log("Refreshing session for role {$roleId}");
            $_SESSION['permissions_updated'] = true;
        }
    }
    
    return $result;
}

/**
 * Erstellt eine neue Rolle
 * 
 * @param string $roleId ID der neuen Rolle
 * @param string $roleName Name der neuen Rolle
 * @param array $permissions Berechtigungen der neuen Rolle
 * @return bool True bei Erfolg, false bei Fehler
 */
function createRole($roleId, $roleName, $permissions = []) {
    global $config;
    
    // Sicherstellen, dass nur der Master-Admin Rollen erstellen kann
    if (!isMasterAdmin()) {
        return false;
    }
    
    // Prüfen, ob die Rolle bereits existiert
    if (isset($config['permissions']['roles'][$roleId])) {
        return false;
    }
    
    // RoleId bereinigen
    $roleId = strtolower(preg_replace('/[^a-z0-9_]/', '', $roleId));
    
    // Standardberechtigungen (alle auf false)
    $defaultPermissions = [];
    foreach ($config['permissions']['roles']['viewer']['permissions'] as $permissionName => $value) {
        $defaultPermissions[$permissionName] = false;
    }
    
    // Übergebene Berechtigungen übernehmen
    foreach ($permissions as $permissionName => $value) {
        if (isset($defaultPermissions[$permissionName])) {
            $defaultPermissions[$permissionName] = (bool)$value;
        }
    }
    
    // Neue Rolle erstellen
    $config['permissions']['roles'][$roleId] = [
        'name' => $roleName,
        'permissions' => $defaultPermissions
    ];
    
    // Konfiguration speichern
    return saveConfig($config);
}

/**
 * Löscht eine Rolle
 * 
 * @param string $roleId ID der zu löschenden Rolle
 * @return bool True bei Erfolg, false bei Fehler
 */
function deleteRole($roleId) {
    global $config;
    
    // Sicherstellen, dass nur der Master-Admin Rollen löschen kann
    if (!isMasterAdmin()) {
        return false;
    }
    
    // Prüfen, ob die Rolle existiert
    if (!isset($config['permissions']['roles'][$roleId])) {
        return false;
    }
    
    // Die Standardrollen können nicht gelöscht werden
    if (in_array($roleId, ['master', 'admin', 'moderator', 'viewer'])) {
        return false;
    }
    
    // Rolle löschen
    unset($config['permissions']['roles'][$roleId]);
    
    // Konfiguration speichern
    return saveConfig($config);
}

/**
 * Gibt den Benutzernamen des aktuell angemeldeten Benutzers zurück
 * 
 * @return string|null Benutzername oder null, wenn nicht angemeldet
 */
function getCurrentUsername() {
    if (isLoggedIn() && isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    return null;
}
