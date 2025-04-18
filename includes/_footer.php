    </div><!-- /.container -->

    <!-- Modal für Bestätigungsdialoge -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo t('confirm'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmModalText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                    <button type="button" class="btn btn-primary" id="confirmModalButton"><?php echo t('confirm'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (must be loaded first!) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript (depends on jQuery) -->
    <script src="assets/js/script.js"></script>
    
    <!-- Page-specific JavaScript (depends on jQuery) -->
    <?php if (file_exists(MTWI_ROOT . "/assets/js/{$currentPage}.js")): ?>
    <script src="assets/js/<?php echo $currentPage; ?>.js"></script>
    <?php endif; ?>
    <?php if ($currentPage === 'chat' && file_exists(MTWI_ROOT . '/assets/js/live_chat.js')): ?>
    <script src="assets/js/live_chat.js"></script>
    <?php endif; ?>
    <!-- END: All scripts using $ require jQuery above -->

    <!-- CSRF-Token für AJAX-Anfragen -->
    <script>
        const CSRF_TOKEN = '<?php echo generateCsrfToken(); ?>';
        const CURRENT_LANG = '<?php echo $currentLang; ?>';
        const REFRESH_INTERVAL = <?php echo isset($config['settings']['refresh_interval']) ? intval($config['settings']['refresh_interval']) * 1000 : 10000; ?>;
    </script>
</body>
</html>
