<?php
// Prüfung der Systemanforderungen
$requirements = [
    'php_version' => [
        'title' => 'PHP Version 7.4+',
        'check' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'current' => PHP_VERSION,
        'required' => '7.4.0 oder höher',
        'hint' => 'Aktualisieren Sie PHP auf Version 7.4 oder höher. Kontaktieren Sie Ihren Hosting-Anbieter oder führen Sie ein System-Update durch.',
        'severity' => 'critical'
    ],
    'pdo_extension' => [
        'title' => 'PDO Extension',
        'check' => extension_loaded('pdo'),
        'current' => extension_loaded('pdo') ? 'Installiert' : 'Nicht installiert',
        'required' => 'Installiert',
        'hint' => 'Installieren Sie die PDO-Erweiterung für PHP: <code>sudo apt-get install php-pdo</code> (Debian/Ubuntu) oder <code>yum install php-pdo</code> (CentOS/RHEL).',
        'severity' => 'critical'
    ],
    'pdo_sqlite' => [
        'title' => 'PDO SQLite Extension',
        'check' => extension_loaded('pdo_sqlite'),
        'current' => extension_loaded('pdo_sqlite') ? 'Installiert' : 'Nicht installiert',
        'required' => 'Installiert (für SQLite)',
        'hint' => 'Für SQLite-Datenbanken: <code>sudo apt-get install php-sqlite3</code> (Debian/Ubuntu) oder <code>yum install php-sqlite3</code> (CentOS/RHEL).',
        'severity' => 'warning'
    ],
    'pdo_mysql' => [
        'title' => 'PDO MySQL Extension',
        'check' => extension_loaded('pdo_mysql'),
        'current' => extension_loaded('pdo_mysql') ? 'Installiert' : 'Nicht installiert',
        'required' => 'Installiert (für MySQL)',
        'hint' => 'Für MySQL-Datenbanken: <code>sudo apt-get install php-mysql</code> (Debian/Ubuntu) oder <code>yum install php-mysql</code> (CentOS/RHEL).',
        'severity' => 'warning'
    ],
    'file_permissions' => [
        'title' => 'Dateiberechtigungen',
        'check' => is_writable('../') && (is_writable('../config') || !file_exists('../config')),
        'current' => is_writable('../') && (is_writable('../config') || !file_exists('../config')) ? 'Schreibbar' : 'Nicht schreibbar',
        'required' => 'Schreibbare Verzeichnisse',
        'hint' => 'Setzen Sie die richtigen Berechtigungen: <code>chmod -R 755 ..</code> und <code>chown -R www-data:www-data ..</code> (ersetzen Sie www-data durch Ihren Webserver-Benutzer).',
        'severity' => 'critical'
    ],
    'data_permissions' => [
        'title' => 'Datenverzeichnis',
        'check' => is_dir('../data') && is_writable('../data') || !is_dir('../data') && is_writable('../'),
        'current' => is_dir('../data') && is_writable('../data') || !is_dir('../data') && is_writable('../') ? 'Schreibbar' : 'Nicht schreibbar',
        'required' => 'Schreibbares data-Verzeichnis',
        'hint' => 'Erstellen Sie das Datenverzeichnis und setzen Sie Berechtigungen: <code>mkdir -p ../data</code> und <code>chmod 775 ../data</code>',
        'severity' => 'critical'
    ],
    'curl_extension' => [
        'title' => 'cURL Extension',
        'check' => extension_loaded('curl'),
        'current' => extension_loaded('curl') ? 'Installiert' : 'Nicht installiert',
        'required' => 'Installiert (für API-Anfragen)',
        'hint' => 'Installieren Sie die cURL-Erweiterung: <code>sudo apt-get install php-curl</code> (Debian/Ubuntu) oder <code>yum install php-curl</code> (CentOS/RHEL).',
        'severity' => 'critical'
    ],
    'json_extension' => [
        'title' => 'JSON Extension',
        'check' => extension_loaded('json'),
        'current' => extension_loaded('json') ? 'Installiert' : 'Nicht installiert',
        'required' => 'Installiert',
        'hint' => 'Installieren Sie die JSON-Erweiterung: <code>sudo apt-get install php-json</code> (Debian/Ubuntu) oder <code>yum install php-json</code> (CentOS/RHEL).',
        'severity' => 'critical'
    ],
    'mbstring_extension' => [
        'title' => 'mbstring Extension',
        'check' => extension_loaded('mbstring'),
        'current' => extension_loaded('mbstring') ? 'Installiert' : 'Nicht installiert',
        'required' => 'Installiert',
        'hint' => 'Installieren Sie die mbstring-Erweiterung: <code>sudo apt-get install php-mbstring</code> (Debian/Ubuntu) oder <code>yum install php-mbstring</code> (CentOS/RHEL).',
        'severity' => 'warning'
    ],
    'fileinfo_extension' => [
        'title' => 'FileInfo Extension',
        'check' => extension_loaded('fileinfo'),
        'current' => extension_loaded('fileinfo') ? 'Installiert' : 'Nicht installiert',
        'required' => 'Installiert',
        'hint' => 'Installieren Sie die FileInfo-Erweiterung: <code>sudo apt-get install php-fileinfo</code> (Debian/Ubuntu) oder <code>yum install php-fileinfo</code> (CentOS/RHEL).',
        'severity' => 'warning'
    ]
];

// Serverdaten ermitteln
$serverInfo = [
    'os' => PHP_OS,
    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt',
    'ip' => $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'Unbekannt',
    'php_sapi' => php_sapi_name(),
    'hostname' => gethostname() ?: 'Unbekannt',
    'disk_free' => function_exists('disk_free_space') ? round(disk_free_space('../') / (1024*1024*1024), 2) . ' GB' : 'Unbekannt'
];

// Überprüfen, ob alle kritischen Anforderungen erfüllt sind
$allRequirementsMet = true;
$criticalRequirementsMet = true;

foreach ($requirements as $requirement) {
    if (!$requirement['check']) {
        $allRequirementsMet = false;
        if ($requirement['severity'] === 'critical') {
            $criticalRequirementsMet = false;
        }
    }
}

// Fortschritt speichern, wenn alle kritischen Anforderungen erfüllt sind
if ($allRequirementsMet && isset($_POST['continue'])) {
    // Dieser Code wird nie ausgeführt, da die Formularverarbeitung in index.php erfolgt
    // Die verbleibende Logik dient nur zur Anzeige des Formulars
}
?>

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Systemanforderungen</h3>
        <p class="card-text">
            Bevor wir fortfahren, überprüfen wir, ob Ihr Server alle erforderlichen Voraussetzungen erfüllt.
        </p>
        
        <div class="d-flex justify-content-end mb-3">
            <a href="?step=2&refresh=1" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise"></i> Erneut prüfen
            </a>
        </div>
        
        <!-- Serverinformationen -->
        <div class="card mb-4 bg-light">
            <div class="card-header">
                <h5 class="mb-0">Serverinformationen</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between bg-light">
                                <span>Betriebssystem:</span>
                                <strong><?php echo $serverInfo['os']; ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between bg-light">
                                <span>Webserver:</span>
                                <strong><?php echo $serverInfo['software']; ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between bg-light">
                                <span>PHP SAPI:</span>
                                <strong><?php echo $serverInfo['php_sapi']; ?></strong>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between bg-light">
                                <span>Server-Name:</span>
                                <strong><?php echo $serverInfo['hostname']; ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between bg-light">
                                <span>Server-IP:</span>
                                <strong><?php echo $serverInfo['ip']; ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between bg-light">
                                <span>Freier Speicherplatz:</span>
                                <strong><?php echo $serverInfo['disk_free']; ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Systemanforderungen Tabelle -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30%;">Anforderung</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 25%;">Aktuell</th>
                        <th style="width: 30%;">Erforderlich</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requirements as $key => $requirement): ?>
                    <tr class="<?php echo !$requirement['check'] ? ($requirement['severity'] === 'critical' ? 'table-danger' : 'table-warning') : ''; ?>">
                        <td>
                            <strong><?php echo $requirement['title']; ?></strong>
                        </td>
                        <td>
                            <?php if ($requirement['check']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> OK</span>
                            <?php else: ?>
                                <span class="badge <?php echo $requirement['severity'] === 'critical' ? 'bg-danger' : 'bg-warning'; ?>">
                                    <i class="bi bi-x-lg"></i> 
                                    <?php echo $requirement['severity'] === 'critical' ? 'Kritisch' : 'Warnung'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $requirement['current']; ?></td>
                        <td><?php echo $requirement['required']; ?></td>
                    </tr>
                    <?php if (!$requirement['check']): ?>
                    <tr class="<?php echo $requirement['severity'] === 'critical' ? 'table-danger' : 'table-warning'; ?>">
                        <td colspan="4" class="small">
                            <div class="alert <?php echo $requirement['severity'] === 'critical' ? 'alert-danger' : 'alert-warning'; ?> py-2 mb-0">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Hinweis:</strong> <?php echo $requirement['hint']; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Status-Meldung -->
        <?php if (!$criticalRequirementsMet): ?>
        <div class="alert alert-danger mt-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Kritische Anforderungen nicht erfüllt!</strong> Bitte beheben Sie die Probleme, bevor Sie fortfahren können.
        </div>
        <?php elseif (!$allRequirementsMet): ?>
        <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Warnung:</strong> Einige empfohlene Anforderungen sind nicht erfüllt. Sie können die Installation fortsetzen, aber möglicherweise funktionieren nicht alle Funktionen ordnungsgemäß.
        </div>
        <?php else: ?>
        <div class="alert alert-success mt-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Sehr gut!</strong> Alle Systemanforderungen sind erfüllt. Sie können mit der Installation fortfahren.
        </div>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="?step=2">
    <div class="d-flex justify-content-between">
        <a href="?step=1" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Zurück
        </a>
        <button type="submit" class="btn btn-primary" <?php echo !$criticalRequirementsMet ? 'disabled' : ''; ?>>
            Weiter <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>

<?php if (!$criticalRequirementsMet): ?>
<div class="mt-3 text-center">
    <p class="text-muted">
        <i class="bi bi-info-circle"></i> 
        Beheben Sie die kritischen Probleme und klicken Sie auf 
        <a href="?step=2&refresh=1" class="text-decoration-none">Erneut prüfen</a>, 
        um fortzufahren.
    </p>
</div>
<?php endif; ?>
