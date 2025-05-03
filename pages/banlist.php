<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo t('ban_list'); ?></h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPendingBanModal">
                    <i class="bi bi-plus-circle"></i> <?php echo t('ban_player'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Aktive Bans -->
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('active_bans'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="activeBansTable">
                        <thead>
                            <tr>
                                <th><?php echo t('name'); ?></th>
                                <th>Steam ID</th>
                                <th><?php echo t('reason'); ?></th>
                                <th><?php echo t('expires_at'); ?></th>
                                <th class="text-end"><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center"><?php echo t('loading'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ausstehende Bans -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('pending_bans'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="pendingBansTable">
                        <thead>
                            <tr>
                                <th><?php echo t('name'); ?></th>
                                <th>Steam ID</th>
                                <th><?php echo t('reason'); ?></th>
                                <th><?php echo t('expires_at'); ?></th>
                                <th class="text-end"><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center"><?php echo t('loading'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Ausstehenden Ban hinzufügen -->
<div class="modal fade" id="addPendingBanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo t('ban_player'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPendingBanForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pendingBanSteamId" class="form-label">Steam ID</label>
                        <input type="text" class="form-control" id="pendingBanSteamId" name="unique_id" required>
                        <div class="form-text"><?php echo t('steam_id_description'); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pendingBanPlayerName" class="form-label"><?php echo t('name'); ?></label>
                        <input type="text" class="form-control" id="pendingBanPlayerName" name="player_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pendingBanReason" class="form-label"><?php echo t('reason'); ?></label>
                        <input type="text" class="form-control" id="pendingBanReason" name="reason">
                    </div>
                    
                    <div class="mb-3">
                        <label for="pendingBanDuration" class="form-label"><?php echo t('ban_duration'); ?></label>
                        <select class="form-select" id="pendingBanDuration" name="duration">
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
                    <button type="submit" class="btn btn-danger" id="pendingBanSubmitButton"><?php echo t('ban'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Übersetzungen für JavaScript
    const translations = {
        no_active_bans: '<?php echo t('no_active_bans'); ?>',
        no_pending_bans: '<?php echo t('no_pending_bans'); ?>',
        confirm_unban: '<?php echo t('confirm_unban'); ?>',
        confirm_remove_ban: 'Möchtest du den ausstehenden Ban für {name} wirklich entfernen?',
        remove: 'Entfernen',
        unban_player: '<?php echo t('unban_player'); ?>'
    };
</script>
