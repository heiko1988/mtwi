    </div><!-- /.container-fluid -->

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
        
        // Debug-Info für die Seitennavigation
        console.log('Footer script loaded. Current page: ' + (typeof currentPage !== 'undefined' ? currentPage : 'unknown'));
        
        // Tabs aktivieren, falls wir auf der Settings-Seite sind
        if (typeof currentPage !== 'undefined' && currentPage === 'settings') {
            $(document).ready(function() {
                console.log('Initializing settings tabs from footer');
                
                // Prüfen, ob ein Tab in der URL angegeben ist
                const urlParams = new URLSearchParams(window.location.search);
                const tabParam = urlParams.get('tab');
                const hash = window.location.hash.substring(1);
                
                let activeTabId = '';
                
                if (hash) {
                    activeTabId = hash;
                    console.debug('Hash gefunden:', hash);
                } else if (tabParam) {
                    activeTabId = tabParam;
                    console.debug('Tab-Parameter gefunden:', tabParam);
                } else {
                    // Wenn kein Tab angegeben ist, versuche den gespeicherten Tab zu verwenden oder wähle den ersten
                    activeTabId = localStorage.getItem('mtwi_active_tab') || 'account';
                    console.debug('Gespeicherter/Erster Tab:', activeTabId);
                }
                
                // Aktuellen Tab aktivieren
                if (activeTabId) {
                    console.debug('Versuche Tab zu aktivieren:', activeTabId);
                    const tabElement = document.getElementById(activeTabId + '-tab');
                    if (tabElement) {
                        const tab = new bootstrap.Tab(tabElement);
                        tab.show();
                    } else {
                        console.debug('Tab-Element nicht gefunden:', activeTabId);
                        // Ersten Tab aktivieren
                        const firstTab = document.querySelector('#settingsTabs a[data-bs-toggle="tab"]');
                        if (firstTab) {
                            const tab = new bootstrap.Tab(firstTab);
                            tab.show();
                        }
                    }
                }
                
                // Prüfen, ob ein Tab aktiv ist, ansonsten den ersten verfügbaren Tab aktivieren
                setTimeout(function() {
                    const anyActive = document.querySelector('#settingsTabsContent .tab-pane.active');
                    if (!anyActive) {
                        const firstTab = document.querySelector('#settingsTabs a[data-bs-toggle="tab"]');
                        if (firstTab) {
                            const tab = new bootstrap.Tab(firstTab);
                            tab.show();
                        }
                    }
                }, 300);
                
                // Tab-Wechsel in localStorage speichern
                $('#settingsTabs a').on('shown.bs.tab', function(e) {
                    var id = $(e.target).attr('href').substring(1);
                    console.log('Tab changed to: ' + id);
                    localStorage.setItem('mtwi_active_settings_tab', id);
                });
            });
        }
    </script>
</body>
</html>
