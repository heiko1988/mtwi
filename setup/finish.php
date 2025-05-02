<?php
// Installation abgeschlossen markieren
$installationSuccess = isset($_SESSION['installation_success']) && $_SESSION['installation_success'];
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Wenn die Installation erfolgreich war und wir auf der Abschlussseite sind,
// markieren wir den Schritt als abgeschlossen
if ($installationSuccess) {
    $_SESSION['setup_completed_steps'][6] = true;
    // Die Weiterleitung erfolgt über einen GET-Parameter in der index.php
}

// Alle Schritte für die Zusammenfassung laden
$dbSettings = getSetupProgress(3);
$serverSettings = getSetupProgress(4);
$adminSettings = getSetupProgress(5);
?>

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">
            <?php if ($installationSuccess): ?>
            <i class="bi bi-check-circle-fill text-success me-2"></i> Installation abgeschlossen
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errorMessage)): ?>
            <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Fehler bei der Installation
            <?php else: ?>
            Installation abschließen
            <?php endif; ?>
        </h3>
        
        <?php if (!$installationSuccess && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-danger mb-4">
            <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$installationSuccess): ?>
        <p class="card-text">
            Bitte überprüfen Sie die folgenden Einstellungen und klicken Sie auf "Installation abschließen", um die Installation durchzuführen.
        </p>
        
        <div class="mb-4">
            <h5 class="border-bottom pb-2">Zusammenfassung</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mt-3">Datenbank</h6>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Typ:</span>
                            <strong><?php echo $dbSettings['type'] === 'sqlite' ? 'SQLite' : 'MySQL'; ?></strong>
                        </li>
                        <?php if ($dbSettings['type'] === 'sqlite'): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Datei:</span>
                            <strong><?php echo $dbSettings['file']; ?></strong>
                        </li>
                        <?php else: ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Host:</span>
                            <strong><?php echo $dbSettings['host']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Datenbank:</span>
                            <strong><?php echo $dbSettings['name']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Benutzer:</span>
                            <strong><?php echo $dbSettings['user']; ?></strong>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h6 class="mt-3">Chat-Server</h6>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>URL:</span>
                            <strong><?php echo $serverSettings['chat_url']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Port:</span>
                            <strong><?php echo $serverSettings['chat_port']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Benutzername:</span>
                            <strong><?php echo $serverSettings['chat_username']; ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mt-3">Administrator</h6>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Benutzername:</span>
                            <strong><?php echo $adminSettings['username']; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Standardsprache:</span>
                            <strong><?php echo $adminSettings['language'] === 'de' ? 'Deutsch' : 'English'; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Theme:</span>
                            <strong><?php echo $adminSettings['theme'] === 'light' ? 'Light Mode' : 'Dark Mode'; ?></strong>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h6 class="mt-3">Weitere Einstellungen</h6>
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Steam API-Key:</span>
                            <strong><?php echo !empty($serverSettings['steam_api_key']) ? 'Konfiguriert' : 'Nicht konfiguriert'; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>API-URL:</span>
                            <strong><?php echo !empty($serverSettings['api_url']) ? $serverSettings['api_url'] : 'Nicht konfiguriert'; ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <form method="post" action="?step=6">
            <div class="d-flex justify-content-between">
                <a href="?step=5" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg"></i> Installation abschließen
                </button>
            </div>
        </form>
        
        <?php else: ?>
        
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>
            Die Installation wurde erfolgreich abgeschlossen! Sie können sich jetzt mit Ihren Administrator-Zugangsdaten anmelden.
        </div>
        
        <div class="text-center mb-4">
            <a href="?finish=complete" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Zum Login
            </a>
        </div>
        
        <div class="alert alert-warning">
            <i class="bi bi-shield-lock-fill me-2"></i>
            <strong>Wichtiger Sicherheitshinweis:</strong> Aus Sicherheitsgründen sollten Sie den Setup-Ordner entfernen oder umbenennen, nachdem Sie sich angemeldet haben.
        </div>
        
        <div class="card bg-light mb-4">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle-fill me-2"></i> Nächste Schritte</h5>
                <ol>
                    <li>Melden Sie sich mit Ihren Administrator-Zugangsdaten an.</li>
                    <li>Überprüfen Sie die Verbindung zum Chat-Server in den Einstellungen.</li>
                    <li>Passen Sie bei Bedarf weitere Einstellungen an.</li>
                    <li>Erstellen Sie zusätzliche Benutzerkonten für andere Administratoren oder Moderatoren.</li>
                    <li>Explorieren Sie die Funktionen des Motor Town Web Interface.</li>
                </ol>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</div>
