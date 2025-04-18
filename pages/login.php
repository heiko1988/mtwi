<?php
/**
 * Motor Town Web Interface (MTWI) - Login-Seite
 */

// Meldungen-System
$errorMessage = '';
$successMessage = '';

// Login-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Prüfen ob die Anmeldedaten korrekt sind
        if ($username === $config['admin']['username'] && verifyPassword($password, $config['admin']['password'])) {
            // Anmeldung in der Session speichern
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            
            // Erfolgsmeldung
            $successMessage = t('login_successful');
            
            // JavaScript-Weiterleitung statt PHP-Header
            $redirect = true;
        } else {
            $errorMessage = t('invalid_credentials');
        }
    } else {
        $errorMessage = t('enter_username_password');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card mt-5">
            <div class="card-header text-center">
                <h4><?php echo t('login'); ?></h4>
            </div>
            <div class="card-body">
                <?php if ($errorMessage): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($successMessage): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php echo t('loading'); ?></span>
                    </div>
                </div>
                <?php if (isset($redirect) && $redirect): ?>
                <script>
                    // Weiterleitung nach kurzer Verzögerung
                    setTimeout(function() {
                        window.location.href = 'index.php?page=dashboard';
                    }, 1500);
                </script>
                <?php endif; ?>
                <?php else: ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label"><?php echo t('username'); ?></label>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo t('password'); ?></label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                        <label class="form-check-label" for="rememberMe"><?php echo t('remember_me'); ?></label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?php echo t('login'); ?></button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
