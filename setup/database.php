<?php
// Formular-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbType = $_POST['db_type'];
    $dbData = [
        'type' => $dbType
    ];
    
    if ($dbType === 'sqlite') {
        $dbData['file'] = $_POST['sqlite_file'];
    } else {
        $dbData['host'] = $_POST['mysql_host'];
        $dbData['name'] = $_POST['mysql_database'];
        $dbData['user'] = $_POST['mysql_username'];
        $dbData['pass'] = $_POST['mysql_password'];
    }
    
    // Datenbankverbindung testen
    $connectionSuccess = false;
    $errorMessage = '';
    
    try {
        if ($dbType === 'sqlite') {
            $dbPath = '../' . $dbData['file'];
            $dbDir = dirname($dbPath);
            
            // Verzeichnis erstellen, falls es nicht existiert
            if (!file_exists($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            // Verbindung testen
            $pdo = new PDO('sqlite:' . $dbPath);
            $connectionSuccess = true;
        } else {
            // MySQL-Verbindung testen
            $dsn = "mysql:host={$dbData['host']};dbname={$dbData['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbData['user'], $dbData['pass']);
            $connectionSuccess = true;
        }
    } catch (PDOException $e) {
        $errorMessage = "Datenbankverbindung fehlgeschlagen: " . $e->getMessage();
    }
    
    if ($connectionSuccess) {
        // Fortschritt speichern
        saveSetupProgress(3, $dbData);
        
        // Weiterleiten zum nächsten Schritt
        header('Location: ?step=4');
        exit;
    }
}

// Gespeicherte Daten laden
$savedData = getSetupProgress(3);
?>

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Datenbank-Einrichtung</h3>
        <p class="card-text">
            Wählen Sie den Datenbanktyp und geben Sie die erforderlichen Verbindungsinformationen ein.
        </p>
        
        <?php if (isset($errorMessage) && !empty($errorMessage)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>
        
        <form method="post" action="?step=3" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Datenbanktyp:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="db_type" id="db_type_sqlite" value="sqlite" 
                           <?php echo (!isset($savedData['type']) || $savedData['type'] === 'sqlite') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="db_type_sqlite">
                        SQLite (empfohlen für einfache Installationen)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="db_type" id="db_type_mysql" value="mysql"
                           <?php echo (isset($savedData['type']) && $savedData['type'] === 'mysql') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="db_type_mysql">
                        MySQL / MariaDB (für größere Installationen)
                    </label>
                </div>
            </div>
            
            <!-- SQLite-Einstellungen -->
            <div id="sqlite_settings" class="mb-3">
                <label for="sqlite_file" class="form-label">SQLite-Datei:</label>
                <div class="input-group">
                    <span class="input-group-text">./</span>
                    <input type="text" class="form-control" id="sqlite_file" name="sqlite_file" 
                           value="<?php echo isset($savedData['file']) ? $savedData['file'] : 'data/mtwi.sqlite'; ?>" required>
                    <div class="invalid-feedback">
                        Bitte geben Sie einen Dateipfad für die SQLite-Datenbank an.
                    </div>
                </div>
                <small class="form-text text-muted">
                    Relativer Pfad zur SQLite-Datenbankdatei. Die Verzeichnisse werden automatisch erstellt.
                </small>
            </div>
            
            <!-- MySQL-Einstellungen -->
            <div id="mysql_settings" class="mb-4" style="display: none;">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="mysql_host" class="form-label">Host:</label>
                        <input type="text" class="form-control" id="mysql_host" name="mysql_host"
                               value="<?php echo isset($savedData['host']) ? $savedData['host'] : 'localhost'; ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie den MySQL-Host an.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="mysql_database" class="form-label">Datenbankname:</label>
                        <input type="text" class="form-control" id="mysql_database" name="mysql_database"
                               value="<?php echo isset($savedData['name']) ? $savedData['name'] : 'mtwi'; ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie den Namen der MySQL-Datenbank an.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="mysql_username" class="form-label">Benutzername:</label>
                        <input type="text" class="form-control" id="mysql_username" name="mysql_username"
                               value="<?php echo isset($savedData['user']) ? $savedData['user'] : ''; ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie den MySQL-Benutzernamen an.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="mysql_password" class="form-label">Passwort:</label>
                        <input type="password" class="form-control" id="mysql_password" name="mysql_password"
                               value="<?php echo isset($savedData['pass']) ? $savedData['pass'] : ''; ?>">
                    </div>
                </div>
                <small class="form-text text-muted mt-2">
                    Stellen Sie sicher, dass die Datenbank existiert und der Benutzer die erforderlichen Rechte besitzt.
                </small>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="?step=2" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
                <button type="submit" class="btn btn-primary">
                    Weiter <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dbTypeRadios = document.querySelectorAll('input[name="db_type"]');
    const sqliteSettings = document.getElementById('sqlite_settings');
    const mysqlSettings = document.getElementById('mysql_settings');
    
    // Funktion zum Umschalten der Datenbankeinstellungen
    function toggleDbSettings() {
        const selectedType = document.querySelector('input[name="db_type"]:checked').value;
        
        if (selectedType === 'sqlite') {
            sqliteSettings.style.display = 'block';
            mysqlSettings.style.display = 'none';
            
            // SQLite-Validierung aktivieren, MySQL-Validierung deaktivieren
            document.getElementById('sqlite_file').setAttribute('required', '');
            document.getElementById('mysql_host').removeAttribute('required');
            document.getElementById('mysql_database').removeAttribute('required');
            document.getElementById('mysql_username').removeAttribute('required');
        } else {
            sqliteSettings.style.display = 'none';
            mysqlSettings.style.display = 'block';
            
            // MySQL-Validierung aktivieren, SQLite-Validierung deaktivieren
            document.getElementById('sqlite_file').removeAttribute('required');
            document.getElementById('mysql_host').setAttribute('required', '');
            document.getElementById('mysql_database').setAttribute('required', '');
            document.getElementById('mysql_username').setAttribute('required', '');
        }
    }
    
    // Event-Listener für Radiobuttons
    dbTypeRadios.forEach(function(radio) {
        radio.addEventListener('change', toggleDbSettings);
    });
    
    // Initial aufrufen
    toggleDbSettings();
});
</script>
