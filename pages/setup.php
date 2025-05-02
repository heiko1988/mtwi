<?php
// Basisverzeichnis für alle Includes und Pfade setzen
if (!defined('MTWI_ROOT')) {
    define('MTWI_ROOT', dirname(__DIR__));
}

// Sicherstellen, dass das config-Verzeichnis existiert (nur im Release-Ordner!)
$configDir = MTWI_ROOT . '/config';
if (!is_dir($configDir)) {
    mkdir($configDir, 0775, true);
}

// Sicherstellen, dass das data-Verzeichnis existiert und beschreibbar ist
$dataDir = MTWI_ROOT . '/data';
if (!is_dir($dataDir)) {
    if (!@mkdir($dataDir, 0775, true)) {
        $_SESSION['data_write_error'] =
            'Das Verzeichnis <code>' . htmlspecialchars($dataDir) . '</code> konnte nicht erstellt werden! ' .
            'Bitte erstellen Sie das Verzeichnis manuell und geben Sie dem Webserver-Benutzer Schreibrechte.';
    }
}
if (!is_writable($dataDir)) {
    $_SESSION['data_write_error'] =
        'Das Verzeichnis <code>' . htmlspecialchars($dataDir) . '</code> ist nicht beschreibbar! ' .
        'Bitte geben Sie dem Webserver-Benutzer Schreibrechte.';
}

// Setup-spezifische Initialisierung, falls Sprachdatei/Funktionen noch nicht geladen
if (!function_exists('t')) {
    require_once MTWI_ROOT . '/includes/functions.php';
}

if (!isset($currentLang)) {
    $currentLang = 'de'; // oder 'en' als Fallback
}

if (!isset($lang)) {
    $langFile = MTWI_ROOT . '/lang/' . $currentLang . '.php';
    if (file_exists($langFile)) {
        require $langFile;
    } else {
        $lang = [];
    }
}
if (!isset($currentTheme)) {
    $currentTheme = 'light';
}

$setup_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$total_steps = 5; // Jetzt 5 Schritte mit Chat-Server

// Setup-Konfiguration aus der Session laden oder initialisieren
if (!isset($_SESSION['setup_config'])) {
    $_SESSION['setup_config'] = [];
}
$config = $_SESSION['setup_config'];

// Form-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step1_submit'])) {
        // Schritt 1: Sprache & Basiseinstellungen
        $config['settings']['default_language'] = $_POST['language'];
        $config['settings']['default_theme'] = $_POST['theme'];
        $config['settings']['refresh_interval'] = 7; // Immer 7 Sekunden
        // Datenbank-Konfiguration für SQLite setzen, falls nicht vorhanden
        if (!isset($config['database'])) {
            $config['database'] = [
                'type' => 'sqlite',
                'file' => MTWI_ROOT . '/data/mtwi.sqlite',
                'host' => 'localhost',
                'name' => 'mtwi',
                'user' => 'root',
                'pass' => '',
            ];
        }
        if (!isset($config['api'])) {
            $config['api'] = [
                'url' => '',
                'password' => '',
            ];
        }
        // Neue Admin-Struktur mit Master-Admin und admins-Array
        if (!isset($config['master_admin'])) {
            $config['master_admin'] = [
                'username' => '',
                'password' => '',
            ];
        }
        if (!isset($config['admins'])) {
            $config['admins'] = [];
        }
        if (!isset($config['chat_server'])) {
            $config['chat_server'] = [
                'username' => '',
                'url' => '',
                'port' => '',
                'password' => '',
            ];
        }
        // Vorherige Config zusammenführen, damit keine Werte verloren gehen
        if (isset($_SESSION['setup_config'])) {
            $config = array_replace_recursive($_SESSION['setup_config'], $config);
        }
        // Konfiguration nur in der Session speichern (Datei erst am Ende schreiben)
        $_SESSION['setup_config'] = $config;
        // Datenbanktabellen erzeugen, falls Datei neu
        $sqliteFile = $config['database']['file'];
        if (!file_exists($sqliteFile)) {
            try {
                $db = new PDO('sqlite:' . $sqliteFile);
                $db->exec("CREATE TABLE bans (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    unique_id TEXT NOT NULL,
                    player_name TEXT NOT NULL,
                    reason TEXT,
                    created_at INTEGER NOT NULL,
                    duration INTEGER NOT NULL,
                    expires_at INTEGER,
                    is_active INTEGER DEFAULT 0,
                    is_permanent INTEGER DEFAULT 0
                );");
                $db->exec("CREATE TABLE player_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    unique_id TEXT NOT NULL,
                    player_name TEXT NOT NULL,
                    first_seen INTEGER NOT NULL,
                    last_seen INTEGER NOT NULL,
                    last_action TEXT
                );");
                $db->exec("CREATE TABLE logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    timestamp INTEGER NOT NULL,
                    type TEXT NOT NULL,
                    message TEXT NOT NULL,
                    details TEXT
                );");
                $db->exec("CREATE TABLE quick_messages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    message TEXT NOT NULL,
                    created_at INTEGER NOT NULL,
                    sort_order INTEGER NOT NULL DEFAULT 0
                );");
            } catch (Exception $e) {
                $db_init_error = $e->getMessage();
            }
        }
        // In der Session speichern
        $_SESSION['language'] = $_POST['language'];
        $_SESSION['theme'] = $_POST['theme'];
        
        // Setze einen Redirect-Wert, der später per JavaScript ausgeführt wird
        $redirect_to = 'index.php?page=setup&step=2';
        header('Location: ' . $redirect_to);
        exit;

    } elseif (isset($_POST['step2_submit'])) {
        // Schritt 2: API-Verbindung
        $apiUrl = $_POST['api_url'];
        $apiPassword = $_POST['api_password'];
        
        // API-Einstellungen speichern
        $config['api']['url'] = $apiUrl;
        $config['api']['password'] = $apiPassword;
        
        // Vorherige Config zusammenführen
        if (isset($_SESSION['setup_config'])) {
            $config = array_replace_recursive($_SESSION['setup_config'], $config);
        }
        
        $_SESSION['setup_config'] = $config;
        header('Location: index.php?page=setup&step=3');
        exit;

    } elseif (isset($_POST['step3_submit'])) {
        // Schritt 3: Master-Admin-Zugangsdaten
        $username = trim($_POST['admin_username']);
        $password = $_POST['admin_password'];
        if ($username !== '' && $password !== '') {
            // Master-Admin-Einstellungen festlegen
            $config['master_admin']['username'] = $username;
            $config['master_admin']['password'] = hashPassword($password);
            
            // Auch als ersten Admin in die admins-Liste eintragen
            $config['admins'][$username] = [
                'password' => hashPassword($password),
                'role' => 'master',
                'active' => true
            ];
            
            // Vorherige Config zusammenführen
            if (isset($_SESSION['setup_config'])) {
                $config = array_replace_recursive($_SESSION['setup_config'], $config);
            }
            
            $_SESSION['setup_config'] = $config;
            header('Location: index.php?page=setup&step=4');
            exit;

        }
    } elseif (isset($_POST['step4_submit'])) {
        // Schritt 4: Chat-Server-Konfiguration (optional)
        $config['chat_server']['url'] = trim($_POST['chat_url']);
        $config['chat_server']['url'] = isset($_POST['chat_url']) ? trim($_POST['chat_url']) : '';
        $config['chat_server']['port'] = isset($_POST['chat_port']) ? trim($_POST['chat_port']) : '';
        $config['chat_server']['username'] = isset($_POST['chat_username']) ? trim($_POST['chat_username']) : '';
        $config['chat_server']['password'] = isset($_POST['chat_password']) ? trim($_POST['chat_password']) : '';
        // Vorherige Config zusammenführen
        if (isset($_SESSION['setup_config'])) {
            $config = array_replace_recursive($_SESSION['setup_config'], $config);
        }
        $_SESSION['setup_config'] = $config;
        // Weiter zu Schritt 5
        header('Location: index.php?page=setup&step=5');
        exit;
    } elseif (isset($_POST['skip_chat'])) {
        // Schritt 4 überspringen
        header('Location: index.php?page=setup&step=5');
        exit;
    } elseif (isset($_POST['finish_setup'])) {
        // Schritt 5: Fertigstellen
        $config['settings']['setup_completed'] = true;
        // Nur noch die Session-Config als Basis nehmen (keine Merge mit leerem $config)
        if (isset($_SESSION['setup_config'])) {
            $config = $_SESSION['setup_config'];
            $config['settings']['setup_completed'] = true;
        }
        // Jetzt die finale Config schreiben
        if (saveConfig($config)) {
            unset($_SESSION['setup_config']);
            // Automatisch anmelden mit Master-Admin-Rechten
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $config['master_admin']['username'];
            $_SESSION['is_master_admin'] = true;
            $_SESSION['admin_role'] = 'master';
            session_write_close();
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate(MTWI_ROOT . '/config/config.php', true);
            }
            header('Location: index.php?page=dashboard');
            exit;
        }
    }
    // Nach jedem Schritt Config in Session speichern
    $_SESSION['setup_config'] = $config;
}

?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mt-4 mb-4 setup-container">
            <div class="card-header text-center">
                <h4><?php echo t('setup'); ?></h4>
            </div>
            <div class="card-body">
                <!-- Schrittanzeige -->
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $setup_step * 25; ?>%;">
                        <?php echo t('step') . ' ' . $setup_step . '/5'; ?>
                    </div>
                </div>
                
                <?php if ($setup_step == 1): ?>
                <!-- Schritt 1: Sprache & Basis-Einstellungen -->
                <div class="setup-step">
                    <h5><?php echo t('setup_step1'); ?></h5>
                    <p class="text-muted mb-4"><?php echo t('setup_intro'); ?></p>
                    
                    <?php if (isset($_SESSION['data_write_error'])): ?>
                    <div class="alert alert-danger text-break">
                        <?php echo $_SESSION['data_write_error']; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($db_init_error)): ?>
                    <div class="alert alert-danger text-break">
                        <?php echo $db_init_error; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['config_write_error'])): ?>
                    <div class="alert alert-danger text-break">
                        <?php echo $_SESSION['config_write_error']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="index.php?page=setup">
                        <div class="mb-3">
                            <label for="language" class="form-label" data-i18n="language_selection"><?php echo t('language_selection'); ?></label>
                            <select class="form-select" id="language" name="language">
                                <option value="de" <?php echo $currentLang == 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                <option value="en" <?php echo $currentLang == 'en' ? 'selected' : ''; ?>>English</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="theme" class="form-label" data-i18n="default_theme"><?php echo t('default_theme'); ?></label>
                            <select class="form-select" id="theme" name="theme">
                                <option value="light" <?php echo $currentTheme == 'light' ? 'selected' : ''; ?>><?php echo t('light_mode'); ?></option>
                                <option value="dark" <?php echo $currentTheme == 'dark' ? 'selected' : ''; ?>><?php echo t('dark_mode'); ?></option>
                            </select>
                        </div>
                        <button type="submit" name="step1_submit" class="btn btn-primary w-100 mt-3" data-i18n="continue"><?php echo t('continue'); ?></button>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if ($setup_step == 2): ?>
                <!-- Schritt 2: API-Verbindung -->
                <div class="setup-step">
                    <h5><?php echo t('setup_step2'); ?></h5>
                    <p class="text-muted mb-4"><?php echo t('setup_step2_info'); ?></p>
                    
                    <form method="post" action="index.php?page=setup&step=2">
                        <div class="mb-3">
                            <label for="api_url" class="form-label"><?php echo t('api_url'); ?></label>
                            <input type="url" class="form-control" id="api_url" name="api_url" placeholder="<?php echo t('api_url_hint'); ?>" value="<?php echo htmlspecialchars($config['api']['url'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="api_password" class="form-label"><?php echo t('api_password'); ?></label>
                            <input type="password" class="form-control" id="api_password" name="api_password" value="<?php echo htmlspecialchars($config['api']['password'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="setup-navigation">
                            <a href="index.php?page=setup&step=1" class="btn btn-secondary"><?php echo t('back'); ?></a>
                            <button type="submit" name="step2_submit" class="btn btn-primary"><?php echo t('next'); ?></button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if ($setup_step == 3): ?>
                <!-- Schritt 3: Admin-Zugangsdaten -->
                <div class="setup-step">
                    <h5><?php echo t('setup_step3'); ?></h5>
                    <p class="text-muted mb-4"><?php echo t('admin_setup'); ?></p>
                    
                    <form method="post" action="index.php?page=setup&step=3">
                        <div class="mb-3">
                            <label for="admin_username" class="form-label"><?php echo t('username'); ?></label>
                            <input type="text" class="form-control" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars($config['admin']['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_password" class="form-label"><?php echo t('password'); ?></label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                            <div class="form-text"><?php echo t('password_hint'); ?></div>
                        </div>
                        
                        <div class="setup-navigation">
                            <a href="index.php?page=setup&step=2" class="btn btn-secondary"><?php echo t('back'); ?></a>
                            <button type="submit" name="step3_submit" class="btn btn-primary"><?php echo t('next'); ?></button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if ($setup_step == 4): ?>
                <!-- Schritt 4: Chat-Server (optional) -->
                <div class="setup-step">
                    <h5><i class="bi bi-chat-dots me-2"></i> Chat-Server (optional)</h5>
                    <form method="post" action="index.php?page=setup&step=4">
                        <div class="mb-3">
                            <label for="chat_url" class="form-label">Chat-Server URL</label>
                            <input type="text" class="form-control" id="chat_url" name="chat_url" placeholder="http://192.168.1.100:5005" value="<?php echo htmlspecialchars($config['chat_server']['url'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="chat_port" class="form-label">Port</label>
                            <input type="number" class="form-control" id="chat_port" name="chat_port" placeholder="0" value="<?php echo htmlspecialchars($config['chat_server']['port'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="chat_username" class="form-label">Benutzername</label>
                            <input type="text" class="form-control" id="chat_username" name="chat_username" placeholder="admin" value="<?php echo htmlspecialchars($config['chat_server']['username'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="chat_password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="chat_password" name="chat_password" placeholder="(optional)">
                            <div class="form-text">Das Passwort für den Zugriff auf den Chat-Log-Server (Basic Auth).</div>
                        </div>
                        <div class="setup-navigation d-flex justify-content-between">
                            <a href="index.php?page=setup&step=3" class="btn btn-secondary"><?php echo t('back'); ?></a>
                            <div>
                                <button type="submit" name="skip_chat" class="btn btn-outline-secondary me-2">Überspringen</button>
                                <button type="submit" name="step4_submit" class="btn btn-primary">Weiter</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php elseif ($setup_step == 5): ?>
                <!-- Schritt 5: Fertigstellen -->
                <div class="setup-step">
                    <h5><?php echo t('setup_step4'); ?></h5>
                    <div class="alert alert-success mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo t('setup_completed_text'); ?>
                    </div>



                    <form method="post" action="index.php?page=setup&step=5">
                        <div class="setup-navigation">
                            <a href="index.php?page=setup&step=4" class="btn btn-secondary"><?php echo t('back'); ?></a>
                            <button type="submit" name="finish_setup" class="btn btn-success"><?php echo t('finish'); ?></button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if (isset($redirect_to)): ?>
                <!-- JavaScript-Weiterleitung -->
                <script>
                    window.location.href = '<?php echo $redirect_to; ?>';
                </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // --- LIVE THEME SWITCH ---
    $('#theme').on('change', function() {
        var theme = $(this).val();
        $('#theme-css').attr('href', 'assets/css/' + theme + '.css');
        $('body').attr('data-theme', theme);
    });

    // --- LIVE LANGUAGE SWITCH ---
    $('#language').on('change', function() {
        var lang = $(this).val();
        // Lade Sprachdatei per AJAX
        $.get('lang/' + lang + '.php', function(data) {
            // Extrahiere $lang-Array (quick&dirty)
            var matches = data.match(/\$lang\s*=\s*(\[.*\]);/s);
            if (!matches) return;
            var langObj = {};
            try {
                langObj = eval('(' + matches[1].replace(/=>/g, ':') + ')');
            } catch (e) { return; }
            // Übersetze alle data-i18n-Elemente
            $('[data-i18n]').each(function() {
                var key = $(this).data('i18n');
                if (langObj[key]) $(this).text(langObj[key]);
            });
        });
    });

    // Optional: Fortschrittsanzeige und Step-Wechsel, falls du das später nutzen möchtest
    /*
    let currentStep = 1;
    const totalSteps = 5;
    function updateProgress() {
        const progressPercent = (currentStep / totalSteps) * 100;
        $('#setupProgress').css('width', progressPercent + '%').text('Schritt ' + currentStep + '/' + totalSteps);
    }
    function showStep(step) {
        $('.setup-step').addClass('d-none');
        $('#step' + step).removeClass('d-none');
        currentStep = step;
        updateProgress();
    }
    */
});
</script>
