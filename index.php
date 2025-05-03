<?php
/**
 * Motor Town Web Interface (MTWI) - Hauptindex
 */

// Prüfen, ob das Setup bereits durchgeführt wurde
if (!file_exists('config/config.php')) {
    header('Location: setup/index.php');
    exit;
}

// Prüfen, ob die Konfiguration setup_completed enthält
$config = include('config/config.php');
if (!isset($config['settings']['setup_completed']) || $config['settings']['setup_completed'] !== true) {
    header('Location: setup/index.php');
    exit;
}

try {
    // Initialisierung
    require_once 'includes/init.php';

    // Seite abrufen
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

    // Spracheinstellung ändern, falls angefordert
    if (isset($_GET['set_language']) && in_array($_GET['set_language'], ['de', 'en'])) {
        $_SESSION['language'] = $_GET['set_language'];
        // Bei Umleitung Parameter beibehalten
        $redirectUrl = 'index.php?page=' . $page;
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Seitenumleitung bei Logout
    if ($page === 'logout' && isLoggedIn()) {
        // Session leeren und zur Login-Seite umleiten
        session_unset();
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }

    // Header einbinden
    require_once 'includes/_header.php';

    // Seite einbinden, falls vorhanden
    $pagePath = 'pages/' . $page . '.php';
    if (file_exists($pagePath)) {
        // Prüfen, ob der Benutzer auf diese Seite zugreifen darf
        $hasAccess = true;
        
        // Zugriffsprüfungen für die verschiedenen Seiten
        if (isLoggedIn()) {
            switch ($page) {
                case 'dashboard':
                    $hasAccess = hasPermission('dashboard_view');
                    break;
                case 'player_overview':
                    $hasAccess = hasPermission('player_view');
                    break;
                case 'banlist':
                    $hasAccess = hasPermission('player_ban');
                    break;
                case 'chat':
                    $hasAccess = hasPermission('chat_view');
                    break;
                case 'settings':
                    $hasAccess = hasPermission('settings_view');
                    break;
                // Standardseiten (Login, Logout usw.) sind immer zugänglich
            }
        }
        
        if ($hasAccess) {
            require_once $pagePath;
        } else {
            // Zugriff verweigert
            displayError(t('access_denied'), t('access_denied_message'));
        }
    } else {
        // 404-Fehler
        echo '<div class="container mt-5"><div class="alert alert-danger">Seite nicht gefunden (404).</div></div>';
    }
    // Footer einbinden
    require_once 'includes/_footer.php';

} catch (Exception $e) {
    echo '<div style="max-width:600px;margin:40px auto;font-family:sans-serif">';
    echo '<div class="alert alert-danger" style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:20px;border-radius:5px">';
    echo $e->getMessage();
    echo '</div></div>';
    exit;
}
