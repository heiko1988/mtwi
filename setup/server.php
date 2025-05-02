<?php
// Formular-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverData = [
        'chat_username' => $_POST['chat_username'],
        'chat_url' => rtrim($_POST['chat_url'], '/'),
        'chat_port' => $_POST['chat_port'],
        'chat_password' => $_POST['chat_password'],
        'steam_api_key' => $_POST['steam_api_key'],
        'api_url' => rtrim($_POST['api_url'], '/'),
        'api_password' => $_POST['api_password']
    ];
    
    // Verbindung zum Chat-Server testen
    $testConnection = false;
    $errorMessage = '';
    
    if (!empty($serverData['chat_url']) && !empty($serverData['chat_port'])) {
        $url = $serverData['chat_url'] . ':' . $serverData['chat_port'] . '/api/info';
        
        $auth = base64_encode($serverData['chat_username'] . ':' . $serverData['chat_password']);
        
        $options = [
            'http' => [
                'header' => "Authorization: Basic $auth\r\n",
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        
        // Versuchen, eine Verbindung herzustellen
        $result = @file_get_contents($url, false, $context);
        
        if ($result !== false) {
            $testConnection = true;
        } else {
            // Wenn die Verbindung fehlschlägt, speichern wir trotzdem die Daten
            // und informieren den Benutzer, dass die Verbindung überprüft werden muss
            $errorMessage = "Warnung: Verbindung zum Chat-Server konnte nicht hergestellt werden. Überprüfen Sie die Einstellungen später.";
        }
    }
    
    // Fortschritt speichern
    saveSetupProgress(4, $serverData);
    
    // Weiterleiten zum nächsten Schritt
    header('Location: ?step=5' . ($errorMessage ? '&warning=1' : ''));
    exit;
}

// Gespeicherte Daten laden
$savedData = getSetupProgress(4);
?>

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Server-Konfiguration</h3>
        <p class="card-text">
            Konfigurieren Sie die Verbindung zum Motor Town Chat-Server und weiteren Diensten.
        </p>
        
        <?php if (isset($errorMessage) && !empty($errorMessage)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>
        
        <form method="post" action="?step=4" class="needs-validation" novalidate>
            <!-- Chat-Server-Einstellungen -->
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Motor Town Chat-Server</h5>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="chat_url" class="form-label">Server-URL:</label>
                        <div class="input-group">
                            <span class="input-group-text">http://</span>
                            <input type="text" class="form-control" id="chat_url" name="chat_url" 
                                   value="<?php echo isset($savedData['chat_url']) ? str_replace('http://', '', $savedData['chat_url']) : ''; ?>" required>
                            <div class="invalid-feedback">
                                Bitte geben Sie die Server-URL ein.
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Beispiel: play.example.com (ohne http:// und ohne Port)
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label for="chat_port" class="form-label">Port:</label>
                        <input type="text" class="form-control" id="chat_port" name="chat_port" 
                               value="<?php echo isset($savedData['chat_port']) ? $savedData['chat_port'] : '5005'; ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie den Server-Port ein.
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="chat_username" class="form-label">Benutzername:</label>
                        <input type="text" class="form-control" id="chat_username" name="chat_username" 
                               value="<?php echo isset($savedData['chat_username']) ? $savedData['chat_username'] : 'admin'; ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie den Benutzernamen ein.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="chat_password" class="form-label">Passwort:</label>
                        <input type="password" class="form-control" id="chat_password" name="chat_password" 
                               value="<?php echo isset($savedData['chat_password']) ? $savedData['chat_password'] : ''; ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie das Server-Passwort ein.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Steam-API-Einstellungen -->
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Steam API</h5>
                <div class="mb-3">
                    <label for="steam_api_key" class="form-label">Steam API-Schlüssel:</label>
                    <input type="text" class="form-control" id="steam_api_key" name="steam_api_key" 
                           value="<?php echo isset($savedData['steam_api_key']) ? $savedData['steam_api_key'] : ''; ?>">
                    <small class="form-text text-muted">
                        Optional. Wird für erweiterte Steam-Spielerinformationen verwendet. 
                        Kann auf <a href="https://steamcommunity.com/dev/apikey" target="_blank">steamcommunity.com/dev/apikey</a> erstellt werden.
                    </small>
                </div>
            </div>
            
            <!-- MTWI-API-Einstellungen -->
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Motor Town API</h5>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="api_url" class="form-label">API-URL:</label>
                        <div class="input-group">
                            <span class="input-group-text">http://</span>
                            <input type="text" class="form-control" id="api_url" name="api_url" 
                                   value="<?php echo isset($savedData['api_url']) ? str_replace('http://', '', $savedData['api_url']) : ''; ?>">
                        </div>
                        <small class="form-text text-muted">
                            Optional. Nur für erweiterte Funktionen erforderlich.
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="api_password" class="form-label">API-Passwort:</label>
                        <input type="password" class="form-control" id="api_password" name="api_password" 
                               value="<?php echo isset($savedData['api_password']) ? $savedData['api_password'] : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="?step=3" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück
                </a>
                <button type="submit" class="btn btn-primary">
                    Weiter <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>
