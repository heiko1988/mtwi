/**
 * Motor Town Web Interface (MTWI) - JavaScript-Berechtigungsprüfungen
 */

// Globale Variable für Benutzerberechtigungen
let userPermissions = [];

/**
 * Prüft, ob der aktuelle Benutzer eine bestimmte Berechtigung hat
 * 
 * @param {string} permissionKey - Schlüssel der zu prüfenden Berechtigung
 * @return {boolean} true, wenn der Benutzer die Berechtigung hat, sonst false
 */
function hasPermission(permissionKey) {
    // Wenn keine Berechtigungen definiert sind, zeige Fehler in der Konsole
    if (!Array.isArray(userPermissions)) {
        console.warn('Berechtigungen nicht als Array definiert');
        return false;
    }
    
    // Master-Admin hat alle Berechtigungen
    if (userPermissions.includes('master_admin')) {
        return true;
    }
    
    // Admin hat alle Berechtigungen außer Master-Admin-spezifische
    if (userPermissions.includes('admin') && permissionKey !== 'admin_management') {
        return true;
    }
    
    // Prüfen, ob die spezifische Berechtigung vorhanden ist
    return userPermissions.includes(permissionKey);
}

/**
 * Setzt die Berechtigungen für den aktuellen Benutzer
 * 
 * @param {Array} permissions - Array mit Berechtigungen
 */
function setUserPermissions(permissions) {
    userPermissions = permissions;
    console.log('Benutzerberechtigungen gesetzt:', permissions);
}

/**
 * Initialisiert die Berechtigungen, indem sie vom Server abgerufen werden
 */
function initPermissions() {
    // Standard-Berechtigungen für die Spieler-Seite
    setUserPermissions(['player_list', 'player_details', 'player_ban']);
    
    // Hier könnten die Berechtigungen vom Server abgerufen werden
    // $.ajax({
    //     url: 'ajax_handler.php',
    //     type: 'POST',
    //     data: {
    //         action: 'get_user_permissions',
    //         csrf_token: CSRF_TOKEN
    //     },
    //     success: function(response) {
    //         try {
    //             const data = JSON.parse(response);
    //             if (data.success && Array.isArray(data.data)) {
    //                 setUserPermissions(data.data);
    //             }
    //         } catch (e) {
    //             console.error('Fehler beim Parsen der Berechtigungen:', e);
    //         }
    //     },
    //     error: function() {
    //         console.error('Fehler beim Abrufen der Berechtigungen');
    //     }
    // });
}

// Berechtigungen initialisieren, wenn die Seite geladen ist
$(document).ready(function() {
    initPermissions();
});
