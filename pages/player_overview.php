<?php
/**
 * Motor Town Web Interface (MTWI) - Spielerübersicht
 */
?>
<!-- Online-Spieler Tabelle -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-success text-white">
                <h5 class="mb-0"><?php echo t('online_players'); ?> <span id="onlinePlayerCount" class="badge bg-light text-dark">0</span></h5>
                <button class="btn btn-sm btn-light" id="refreshOverviewData">
                    <i class="bi bi-arrow-clockwise"></i> <?php echo t('refresh'); ?>
                </button>
            </div>
            <div class="card-body">
                <!-- Paginierung (oben) -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="pagination-info" id="onlinePaginationInfo">Seite 1 von 1</div>
                    <div class="btn-group" id="onlinePaginationTop">
                        <button class="btn btn-sm btn-outline-secondary page-prev" disabled>&laquo;</button>
                        <button class="btn btn-sm btn-outline-secondary page-1 active">1</button>
                        <button class="btn btn-sm btn-outline-secondary page-next" disabled>&raquo;</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="onlinePlayersTable">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="name" data-sort-default="asc"><?php echo t('player_name'); ?></th>
                                <th class="sortable" data-sort="steam_id"><?php echo t('steam_id'); ?></th>
                                <th class="sortable" data-sort="last_seen" data-sort-type="date"><?php echo t('last_seen'); ?></th>
                                <th class="sortable" data-sort="company"><?php echo t('company'); ?></th>
                                <th class="sortable" data-sort="taxi_level" data-sort-type="number"><?php echo t('taxi_level'); ?></th>
                                <th class="sortable" data-sort="bus_level" data-sort-type="number"><?php echo t('bus_level'); ?></th>
                                <th class="sortable" data-sort="wrecker_level" data-sort-type="number"><?php echo t('wrecker_level'); ?></th>
                                <th class="sortable" data-sort="police_level" data-sort-type="number"><?php echo t('police_level'); ?></th>
                                <th class="sortable" data-sort="driver_level" data-sort-type="number"><?php echo t('driver_level'); ?></th>
                                <th class="sortable" data-sort="truck_level" data-sort-type="number"><?php echo t('truck_level'); ?></th>
                                <!-- Aktionsspalte ausgeblendet -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="10" class="text-center">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                    <?php echo t('loading_player_data'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Paginierung (unten) -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="records-per-page">
                        <?php echo t('show'); ?> <select id="onlinePlayersPerPage" class="form-select form-select-sm d-inline w-auto">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select> <?php echo t('entries'); ?>
                    </div>
                    <div class="btn-group" id="onlinePaginationBottom">
                        <button class="btn btn-sm btn-outline-secondary page-prev" disabled>&laquo;</button>
                        <button class="btn btn-sm btn-outline-secondary page-1 active">1</button>
                        <button class="btn btn-sm btn-outline-secondary page-next" disabled>&raquo;</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Offline-Spieler Tabelle -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-secondary text-white">
                <h5 class="mb-0"><?php echo t('offline_players'); ?> <span id="offlinePlayerCount" class="badge bg-light text-dark">0</span></h5>
            </div>
            <div class="card-body">
                <!-- Paginierung (oben) -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="pagination-info" id="offlinePaginationInfo">Seite 1 von 1</div>
                    <div class="btn-group" id="offlinePaginationTop">
                        <button class="btn btn-sm btn-outline-secondary page-prev" disabled>&laquo;</button>
                        <button class="btn btn-sm btn-outline-secondary page-1 active">1</button>
                        <button class="btn btn-sm btn-outline-secondary page-next" disabled>&raquo;</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="offlinePlayersTable">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="name" data-sort-default="asc"><?php echo t('player_name'); ?></th>
                                <th class="sortable" data-sort="steam_id"><?php echo t('steam_id'); ?></th>
                                <th class="sortable" data-sort="last_seen" data-sort-type="date"><?php echo t('last_seen'); ?></th>
                                <th class="sortable" data-sort="company"><?php echo t('company'); ?></th>
                                <th class="sortable" data-sort="taxi_level" data-sort-type="number"><?php echo t('taxi_level'); ?></th>
                                <th class="sortable" data-sort="bus_level" data-sort-type="number"><?php echo t('bus_level'); ?></th>
                                <th class="sortable" data-sort="wrecker_level" data-sort-type="number"><?php echo t('wrecker_level'); ?></th>
                                <th class="sortable" data-sort="police_level" data-sort-type="number"><?php echo t('police_level'); ?></th>
                                <th class="sortable" data-sort="driver_level" data-sort-type="number"><?php echo t('driver_level'); ?></th>
                                <th class="sortable" data-sort="truck_level" data-sort-type="number"><?php echo t('truck_level'); ?></th>
                                <!-- Aktionsspalte ausgeblendet -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="10" class="text-center">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                    <?php echo t('loading_player_data'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Paginierung (unten) -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="records-per-page">
                        <?php echo t('show'); ?> <select id="offlinePlayersPerPage" class="form-select form-select-sm d-inline w-auto">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select> <?php echo t('entries'); ?>
                    </div>
                    <div class="btn-group" id="offlinePaginationBottom">
                        <button class="btn btn-sm btn-outline-secondary page-prev" disabled>&laquo;</button>
                        <button class="btn btn-sm btn-outline-secondary page-1 active">1</button>
                        <button class="btn btn-sm btn-outline-secondary page-next" disabled>&raquo;</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Player Details Modal -->
<div class="modal fade" id="playerDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header bg-dark border-secondary">
                <h5 class="modal-title" id="playerDetailsTitle">Spielerdetails</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-dark p-0">
                <div class="container-fluid p-3">
                    <div class="row g-3">
                        <!-- Spielerinformationen -->
                        <div class="col-md-12">
                            <div class="card bg-dark border-secondary">
                                <div class="card-header bg-dark bg-gradient text-light border-secondary">
                                    <h5 class="mb-0"><?php echo t('player_info'); ?></h5>
                                </div>
                                <div class="card-body bg-dark text-light">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-dark table-borderless">
                                                <tr>
                                                    <th scope="row"><?php echo t('player_name'); ?>:</th>
                                                    <td id="playerDetailName">-</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><?php echo t('steam_id'); ?>:</th>
                                                    <td id="playerDetailSteamId">-</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><?php echo t('last_seen'); ?>:</th>
                                                    <td id="playerDetailLastSeen">-</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><?php echo t('company'); ?>:</th>
                                                    <td id="playerDetailCompany">-</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-dark table-borderless">
                                                <tr>
                                                    <th scope="row"><?php echo t('taxi_level'); ?>:</th>
                                                    <td><span id="playerDetailTaxiLevel" class="badge bg-primary">-</span></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><?php echo t('bus_level'); ?>:</th>
                                                    <td><span id="playerDetailBusLevel" class="badge bg-primary">-</span></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><?php echo t('police_level'); ?>:</th>
                                                    <td><span id="playerDetailPoliceLevel" class="badge bg-primary">-</span></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><?php echo t('wrecker_level'); ?>:</th>
                                                    <td><span id="playerDetailWreckerLevel" class="badge bg-primary">-</span></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><?php echo t('truck_level'); ?>:</th>
                                                    <td><span id="playerDetailTruckLevel" class="badge bg-primary">-</span></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row"><?php echo t('driver_level'); ?>:</th>
                                                    <td><span id="playerDetailDriverLevel" class="badge bg-primary">-</span></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fahrzeuge -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-dark bg-gradient text-light border-secondary">
                                    <h5 class="mb-0"><?php echo t('player_vehicles'); ?></h5>
                                </div>
                                <div class="card-body bg-dark text-light">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <div class="card bg-dark border-secondary text-center p-3">
                                                <div class="card-title"><?php echo t('vehicle_stats'); ?></div>
                                                <div class="mb-2">
                                                    <div class="text-muted"><?php echo t('total_vehicles'); ?></div>
                                                    <h2 id="playerTotalVehicles" class="text-warning">-</h2>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card bg-dark border-secondary text-center p-3">
                                                <div class="card-title"><?php echo t('most_used_vehicle'); ?></div>
                                                <h2 id="playerMostUsedVehicle" class="text-warning">-</h2>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-dark table-striped table-hover" id="playerVehiclesTable">
                                            <thead>
                                                <tr>
                                                    <th class="sortable" data-sort="vehicle_name"><?php echo t('vehicle_name'); ?></th>
                                                    <th class="sortable" data-sort="vehicle_id"><?php echo t('vehicle_id'); ?></th>
                                                    <th class="sortable" data-sort="last_used"><?php echo t('last_used'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="3" class="text-center" id="noVehiclesFound">
                                                        <?php echo t('no_vehicles_found'); ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Spieleraktivitäten -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-dark bg-gradient text-light border-secondary">
                                    <h5 class="mb-0"><?php echo t('player_activities'); ?></h5>
                                </div>
                                <div class="card-body bg-dark text-light">
                                    <div class="table-responsive">
                                        <table class="table table-dark table-striped" id="playerActivitiesTable">
                                            <thead>
                                                <tr>
                                                    <th class="sortable" data-sort="activity_time"><?php echo t('activity_time'); ?></th>
                                                    <th class="sortable" data-sort="activity_type"><?php echo t('activity_type'); ?></th>
                                                    <th><?php echo t('activity_details'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="3" class="text-center" id="noActivitiesFound">
                                                        <?php echo t('no_activities_found'); ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal"><?php echo t('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Diese Variable wird vom JavaScript-Code verwendet
    const currentPage = 'player_overview';
</script>

<!-- CSS für Tabellensortierung -->
<link rel="stylesheet" href="assets/css/table-sort.css">
<!-- CSS für Spielerübersicht -->
<link rel="stylesheet" href="assets/css/player_overview.css">
