<div class="row mb-4">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i><span data-i18n="server_control"><?php echo t('server_control'); ?></span></h5>
                <span class="text-muted small" id="serverStatusTime"></span>
            </div>
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3 mb-3 mb-md-0">
                    <span id="serverStatusBadge" class="badge bg-secondary">Status: ...</span>
                </div>
                <div class="btn-group" role="group" aria-label="Server Control">
                    <button type="button" class="btn btn-success" id="btnServerStart" data-i18n="server_start"><i class="bi bi-play-fill"></i> <?php echo t('server_start'); ?></button>
                    <button type="button" class="btn btn-warning" id="btnServerRestart" data-i18n="server_restart"><i class="bi bi-arrow-repeat"></i> <?php echo t('server_restart'); ?></button>
                    <button type="button" class="btn btn-danger" id="btnServerStop" data-i18n="server_stop"><i class="bi bi-stop-fill"></i> <?php echo t('server_stop'); ?></button>
                    
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo t('dashboard'); ?></h5>
                <span id="lastUpdateTime" class="text-muted small"></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <i class="bi bi-people display-4"></i>
                            <div class="stat-number" id="playerCount">0</div>
                            <div class="stat-label"><?php echo t('player_count'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <i class="bi bi-shield-lock display-4"></i>
                            <div class="stat-number" id="banCount">0</div>
                            <div class="stat-label"><?php echo t('active_bans'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Aktive Spieler -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('active_players'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="activePlayersTable">
                        <thead>
                            <tr>
                                <th><?php echo t('name'); ?></th>
                                <th>Steam ID</th>
                                <th class="text-end"><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="3" class="text-center"><?php echo t('loading'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kürzlich abgemeldete Spieler -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('recent_players'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="recentPlayersTable">
                        <thead>
                            <tr>
                                <th><?php echo t('name'); ?></th>
                                <th>Steam ID</th>
                                <th><?php echo t('last_seen'); ?></th>
                                <th class="text-end"><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center"><?php echo t('loading'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Server-Stop/Restart Bestätigungsmodal -->
<?php include __DIR__ . '/partials/server_confirm_modal.html'; ?>

<!-- Ban-Modal -->
<div class="modal fade" id="banModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo t('ban_player'); ?>: <span id="banPlayerName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="banPlayerForm">
                <div class="modal-body">
                    <input type="hidden" id="banUniqueId" name="unique_id">
                    
                    <div class="mb-3">
                        <label for="banReason" class="form-label"><?php echo t('reason'); ?></label>
                        <input type="text" class="form-control" id="banReason" name="reason">
                    </div>
                    
                    <div class="mb-3">
                        <label for="banDuration" class="form-label"><?php echo t('ban_duration'); ?></label>
                        <select class="form-select" id="banDuration" name="duration">
                            <option value="60">1 <?php echo t('duration_minute'); ?></option>
                            <option value="600">10 <?php echo t('duration_minutes'); ?></option>
                            <option value="1800">30 <?php echo t('duration_minutes'); ?></option>
                            <option value="3600">1 <?php echo t('duration_hour'); ?></option>
                            <option value="7200">2 <?php echo t('duration_hours'); ?></option>
                            <option value="43200">12 <?php echo t('duration_hours'); ?></option>
                            <option value="86400">24 <?php echo t('duration_hours'); ?></option>
                            <option value="172800">2 <?php echo t('duration_days'); ?></option>
                            <option value="604800">7 <?php echo t('duration_days'); ?></option>
                            <option value="2592000">30 <?php echo t('duration_days'); ?></option>
                            <option value="0"><?php echo t('duration_permanent'); ?></option>
                            <option value="custom"><?php echo t('duration_custom'); ?></option>
                        </select>
                    </div>
                    
                    <div id="customDurationContainer" class="mb-3 d-none">
                        <div class="input-group">
                            <input type="number" class="form-control" id="customDurationValue" min="1" value="10">
                            <select class="form-select" id="customDurationUnit">
                                <option value="minutes"><?php echo t('duration_minutes'); ?></option>
                                <option value="hours"><?php echo t('duration_hours'); ?></option>
                                <option value="days"><?php echo t('duration_days'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                    <button type="submit" class="btn btn-danger" id="banSubmitButton"><?php echo t('ban'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Übersetzungen für JavaScript
    const translations = {
        no_players_online: '<?php echo t('no_players_online'); ?>',
        no_recent_players: '<?php echo t('no_recent_players'); ?>',
        kick: '<?php echo t('kick'); ?>',
        ban: '<?php echo t('ban'); ?>',
        confirm_kick: '<?php echo t('confirm_kick'); ?>',
        confirm_ban: '<?php echo t('confirm_ban'); ?>',
        // Server Control
        server_control: '<?php echo t('server_control'); ?>',
        server_start: '<?php echo t('server_start'); ?>',
        server_restart: '<?php echo t('server_restart'); ?>',
        server_stop: '<?php echo t('server_stop'); ?>',
        // Server Control Modal
        server_stop_title: '<?php echo t('server_stop_title'); ?>',
        server_stop_confirm: '<?php echo t('server_stop_confirm'); ?>',
        server_restart_title: '<?php echo t('server_restart_title'); ?>',
        server_restart_confirm: '<?php echo t('server_restart_confirm'); ?>',
        yes_execute: '<?php echo t('yes_execute'); ?>'
    };
</script>
