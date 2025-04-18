<?php
/**
 * Motor Town Web Interface (MTWI) - Hauptindex
 */

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
        require_once $pagePath;
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
