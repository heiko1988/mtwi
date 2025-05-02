<?php
// Formular-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validierung
    $errors = [];
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $passwordConfirm = trim($_POST['password_confirm']);
    $language = $_POST['language'];
    $theme = $_POST['theme'];
    
    if (empty($username)) {
        $errors[] = "Benutzername darf nicht leer sein.";
    }
    
    if (empty($password)) {
        $errors[] = "Passwort darf nicht leer sein.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Das Passwort muss mindestens 8 Zeichen lang sein.";
    }
    
    if ($password !== $passwordConfirm) {
        $errors[] = "Die Passwörter stimmen nicht überein.";
    }
    
    if (empty($errors)) {
        // Daten speichern
        $adminData = [
            'username' => $username,
            'password' => $password, // Wird später gehasht
            'language' => $language,
            'theme' => $theme
        ];
        
        saveSetupProgress(5, $adminData);
        
        // Weiterleiten zum nächsten Schritt
        header('Location: ?step=6');
        exit;
    }
}

// Gespeicherte Daten laden
$savedData = getSetupProgress(5);

// Verfügbare Sprachen
$availableLanguages = [
    'de' => 'Deutsch',
    'en' => 'English'
];

// Verfügbare Themes
$availableThemes = [
    'light' => 'Light Mode',
    'dark' => 'Dark Mode'
];
?>

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Admin-Konto erstellen</h3>
        <p class="card-text">
            Erstellen Sie ein Administratorkonto für die Verwaltung Ihres Motor Town Web Interface.
        </p>
        
        <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['warning']) && $_GET['warning'] == '1'): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Die Verbindung zum Server konnte nicht hergestellt werden. Sie können die Einrichtung trotzdem fortsetzen und die Verbindung später überprüfen.
        </div>
        <?php endif; ?>
        
        <form method="post" action="?step=5" class="needs-validation" novalidate>
            <!-- Admin-Konto -->
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Administrator-Zugangsdaten</h5>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Benutzername:</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($savedData['username']) ? $savedData['username'] : 'admin'; ?>" required>
                        <div class="invalid-feedback">
                            Bitte geben Sie einen Benutzernamen ein.
                        </div>
                        <small class="form-text text-muted">
                            Dies ist Ihr Hauptadministrator-Konto (Master Admin).
                        </small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Passwort:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                   value="<?php echo isset($savedData['password']) ? $savedData['password'] : ''; ?>" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Bitte geben Sie ein Passwort ein (mindestens 8 Zeichen).
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Passwort bestätigen:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                   value="<?php echo isset($savedData['password']) ? $savedData['password'] : ''; ?>" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Bitte bestätigen Sie Ihr Passwort.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Einstellungen -->
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Standardeinstellungen</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="language" class="form-label">Standardsprache:</label>
                        <select class="form-select" id="language" name="language">
                            <?php foreach ($availableLanguages as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo (isset($savedData['language']) && $savedData['language'] === $code) || (!isset($savedData['language']) && $code === 'de') ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="theme" class="form-label">Standard-Theme:</label>
                        <select class="form-select" id="theme" name="theme">
                            <?php foreach ($availableThemes as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo (isset($savedData['theme']) && $savedData['theme'] === $code) || (!isset($savedData['theme']) && $code === 'light') ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="?step=4" class="btn btn-secondary">
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
    // Passwort anzeigen/verbergen
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    const passwordConfirm = document.getElementById('password_confirm');
    
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
    
    togglePasswordConfirm.addEventListener('click', function() {
        const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordConfirm.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
    
    // Passwort-Stärke prüfen
    password.addEventListener('input', function() {
        if (this.value.length < 8) {
            this.setCustomValidity('Das Passwort muss mindestens 8 Zeichen lang sein.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Passwort-Übereinstimmung prüfen
    passwordConfirm.addEventListener('input', function() {
        if (this.value !== password.value) {
            this.setCustomValidity('Die Passwörter stimmen nicht überein.');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>
