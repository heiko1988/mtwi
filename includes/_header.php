<?php
/**
 * Motor Town Web Interface (MTWI) - Header-Template
 */
// Minimaler Header im Setup-Modus, um Fehler zu vermeiden
if (isset($currentPage) && $currentPage === 'setup') {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MTWI Setup</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
    <?php
    // Kein weiteres Template/Navigation im Setup-Modus
    return;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app_name'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- Steam ID Responsive CSS -->
    <link href="assets/css/steam-id-responsive.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link href="assets/css/<?php echo $currentTheme; ?>.css" rel="stylesheet" id="theme-css">
    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.png">
</head>
<body data-theme="<?php echo $currentTheme; ?>">

<?php if (isLoggedIn() && $currentPage != 'setup'): ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <?php echo t('app_name'); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <?php if (hasPermission('dashboard_view')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                            <i class="bi bi-speedometer2"></i> <?php echo t('dashboard'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('player_view')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'player_overview' ? 'active' : ''; ?>" href="index.php?page=player_overview">
                            <i class="bi bi-people"></i> <?php echo t('player_overview_title'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('player_ban')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'banlist' ? 'active' : ''; ?>" href="index.php?page=banlist">
                            <i class="bi bi-shield-x"></i> <?php echo t('ban_list'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('chat_view')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'chat' ? 'active' : ''; ?>" href="index.php?page=chat">
                            <i class="bi bi-chat-dots"></i> <?php echo t('chat'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('settings_view')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'settings' ? 'active' : ''; ?>" href="index.php?page=settings">
                            <i class="bi bi-gear"></i> <?php echo t('settings'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-translate"></i> <?php echo strtoupper($currentLang); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item <?php echo $currentLang == 'de' ? 'active' : ''; ?>" href="index.php?page=<?php echo $currentPage; ?>&set_language=de">Deutsch</a></li>
                            <li><a class="dropdown-item <?php echo $currentLang == 'en' ? 'active' : ''; ?>" href="index.php?page=<?php echo $currentPage; ?>&set_language=en">English</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="toggle-theme">
                            <i class="bi <?php echo $currentTheme == 'dark' ? 'bi-sun' : 'bi-moon'; ?>"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=logout">
                            <i class="bi bi-box-arrow-right"></i> <?php echo t('logout'); ?> (<?php echo htmlspecialchars($_SESSION['username']); ?>)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>

<div class="container-fluid main-container">
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
