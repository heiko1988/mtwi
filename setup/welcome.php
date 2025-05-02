<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Willkommen beim Setup-Assistenten</h3>
        <p class="card-text">
            Willkommen beim Setup-Assistenten für das Motor Town Web Interface (MTWI). 
            Dieser Assistent führt Sie durch die Einrichtung Ihrer MTWI-Installation in wenigen einfachen Schritten.
        </p>
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            Der Assistent wird Ihnen helfen:
            <ul class="mb-0 mt-2">
                <li>Die Systemvoraussetzungen zu prüfen</li>
                <li>Die Datenbank einzurichten</li>
                <li>Die Server-Verbindung zu konfigurieren</li>
                <li>Ein Admin-Konto zu erstellen</li>
            </ul>
        </div>
    </div>
</div>

<form method="post" action="?step=2">
    <div class="form-group mb-4">
        <label for="language">Sprache für die Installation:</label>
        <select name="language" id="language" class="form-select">
            <?php foreach ($languages as $code => $name): ?>
                <option value="<?php echo $code; ?>" <?php echo $code === $language ? 'selected' : ''; ?>><?php echo $name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="d-flex justify-content-between">
        <div></div>
        <button type="submit" class="btn btn-primary">
            Weiter <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>

<?php
// Verarbeite das Formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['setup_language'] = $_POST['language'];
    $_SESSION['setup_completed_steps'][1] = true;
    header('Location: ?step=2');
    exit;
}
?>
