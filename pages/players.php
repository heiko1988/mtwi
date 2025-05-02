<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo t('player_info'); ?></h5>
                <button class="btn btn-sm btn-primary" id="refreshPlayerData">
                    <i class="bi bi-arrow-clockwise"></i> <?php echo t('refresh'); ?>
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="playerTable">
                        <thead>
                            <tr>
                                <th><?php echo t('player_name'); ?></th>
                                <th><?php echo t('steam_id'); ?></th>
                                <th><?php echo t('last_seen'); ?></th>
                                <th><?php echo t('taxi_level'); ?></th>
                                <th><?php echo t('bus_level'); ?></th>
                                <th><?php echo t('wrecker_level'); ?></th>
                                <th><?php echo t('police_level'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                    <?php echo t('loading_player_data'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Spieler-Details Modal -->
<div class="modal fade" id="playerDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="playerDetailsTitle"><?php echo t('player_details'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="playerDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Diese Variable wird vom JavaScript-Code verwendet
    const currentPage = 'players';
</script>
