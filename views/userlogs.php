<?php
// Configuration
$log_base_path = '/var/log/remotelogs/hotspotRT_parsed';

// Get available NAS devices
$nas_devices = [];
if (is_dir($log_base_path)) {
    $nas_dirs = scandir($log_base_path);
    foreach ($nas_dirs as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir("$log_base_path/$dir")) {
            $nas_devices[] = $dir;
        }
    }
}

// Get selected NAS and date
$selected_nas = $_GET['nas'] ?? ($nas_devices[0] ?? '');
$selected_date = $_GET['date'] ?? date('Y-m-d');
$load_logs = isset($_GET['nas']) && isset($_GET['date']);

// Check if log file exists for display
$log_file_exists = false;
$total_entries = 0;
if ($load_logs && !empty($selected_nas)) {
    $log_file = "$log_base_path/$selected_nas/$selected_date.log";
    if (file_exists($log_file)) {
        $log_file_exists = true;
        // Quick line count for display
        $file = new SplFileObject($log_file, 'r');
        $file->seek(PHP_INT_MAX);
        $total_entries = $file->key() + 1;
    }
}
?>
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-history"></i>
            </span> User Logs
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">
                    <?php
                    $hour = date('H');
                    $greeting = '';
                    if ($hour >= 5 && $hour < 12) {
                        $greeting = 'Good Morning';
                    } elseif ($hour >= 12 && $hour < 18) {
                        $greeting = 'Good Afternoon';
                    } else {
                        $greeting = 'Good Evening';
                    }
                    echo $greeting . ', Welcome to User Logs';
                    ?>
                    <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
                <li class="breadcrumb-item connection-status">
                    <span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>
                </li>
            </ul>
        </nav>
    </div>
    <!-- Add your user logs content here -->
    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="index.php" class="row g-3">
                        <input type="hidden" name="page" value="userlogs">
                        <div class="col-md-4">
                            <label for="nas" class="form-label">NAS Device</label>
                            <select class="form-select" id="nas" name="nas" style="height: 38px;">
                                <?php foreach ($nas_devices as $nas): ?>
                                    <option value="<?= htmlspecialchars($nas) ?>" <?= $selected_nas === $nas ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nas) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" style="height: 38px;">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2" style="height: 38px;">Load Logs</button>
                            <a href="export_csv.php?nas=<?= urlencode($selected_nas) ?>&date=<?= urlencode($selected_date) ?>" class="btn btn-success me-2" style="height: 38px; line-height: 1.5;">Export CSV</a>
                            <button type="button" id="configureLogging" class="btn btn-outline-secondary" style="height: 38px;"><i class="fa fa-cog"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Configure Logging Modal -->
            <div class="modal fade" id="configureModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Configure Logging</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>This will configure the following logging settings on the selected NAS:</p>
                            <ul>
                                <li>Add syslog186 remote logging action</li>
                                <li>Update default logging to exclude firewall</li>
                                <li>Add firewall logging to remote server</li>
                            </ul>
                            <p><strong>Continue?</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmConfigure">Configure</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Result Modal -->
            <div class="modal fade" id="resultModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="resultTitle">Configuration Result</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p id="resultMessage"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($load_logs && $log_file_exists): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            Logs for <?= htmlspecialchars($selected_nas) ?> on <?= htmlspecialchars($selected_date) ?>
                            <span class="badge bg-secondary ms-2" id="totalEntries"><?= number_format($total_entries) ?> entries</span>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="searchLogs" class="form-control form-control-sm" placeholder="Search logs..." style="width: 200px;">
                            <select id="pageSize" class="form-select form-select-sm" style="width: auto;">
                                <option value="50">50 per page</option>
                                <option value="100" selected>100 per page</option>
                                <option value="200">200 per page</option>
                                <option value="500">500 per page</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="loadingSpinner" class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Loading logs...</div>
                        </div>
                        <div class="table-responsive" id="logTableContainer" style="display: none;">
                            <table class="table table-striped table-bordered log-table mb-0" id="logTable">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Time</th>
                                        <th>NAS Name</th>
                                        <th>User</th>
                                        <th>MAC Address</th>
                                        <th>Source IP</th>
                                        <th>Source Port</th>
                                        <th>Destination IP</th>
                                        <th>Destination Port</th>
                                        <th>Protocol</th>
                                    </tr>
                                </thead>
                                <tbody id="logTableBody">
                                </tbody>
                            </table>
                        </div>
                        <nav id="pagination" class="d-flex justify-content-between align-items-center p-3" style="display: none !important;">
                            <div class="text-muted" id="paginationInfo"></div>
                            <ul class="pagination pagination-sm mb-0" id="paginationControls">
                            </ul>
                        </nav>
                    </div>
                </div>
            <?php elseif ($load_logs && !$log_file_exists): ?>
                <div class="alert alert-info">
                    <?php if (empty($selected_nas)): ?>
                        No NAS devices found or no logs available for the selected date.
                    <?php else: ?>
                        No logs found for <?= htmlspecialchars($selected_nas) ?> on <?= htmlspecialchars($selected_date) ?>.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <!-- <div class="card">
                <div class="card-body">
                    <h4 class="card-title">System Logs</h4> -->
            <!-- Add your logs content here -->
            <!-- </div>
            </div> -->
        </div>
    </div>
</div>