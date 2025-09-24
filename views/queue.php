<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-list"></i>
            </span> Simple Queue Management
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
                    echo $greeting . ', Welcome to Simple Queue Management';
                    ?>
                </li>
                <li class="breadcrumb-item connection-status">
                    <span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>
                </li>

            </ul>
        </nav>
    </div>

    <!-- Connection Status Alert -->
    <div id="connection-alert" class="alert alert-warning alert-dismissible fade show" role="alert" style="display: none;">
        <i class="fa fa-exclamation-triangle me-2"></i>
        <span id="connection-message">Not connected to any NAS device</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Simple Queues</h4>
                        <button class="btn btn-gradient-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSimpleQueueModal">
                            <i class="fa fa-plus me-2"></i>Add Simple Queue
                        </button>
                    </div>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>
                        <strong>Drag and Drop:</strong> To reorder queues. Changes are applied immediately to MikroTik.
                    </div>
                    <div id="sortable-simple-queues" class="sortable-queue-list">
                        <div class="text-center p-4 text-muted">
                            <i class="fa fa-info-circle me-2"></i>
                            Please connect to a NAS device to view simple queues.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Add Simple Queue Modal -->
<div class="modal fade compact-modal" id="addSimpleQueueModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Add Simple Queue</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="simpleQueueForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control form-control-sm" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target</label>
                        <select class="form-select form-select-sm" name="target" id="targetSelect">
                            <option value="">Select active client or enter IP</option>
                        </select>
                        <input type="text" class="form-control form-control-sm mt-1" name="target_manual" placeholder="Or enter IP manually" style="display:none;">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Upload</label>
                                <input type="text" class="form-control form-control-sm" name="max-limit-upload" placeholder="1M">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Download</label>
                                <input type="text" class="form-control form-control-sm" name="max-limit-download" placeholder="10M">
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="disabled" id="simpleQueueDisabled" style="margin-left:0;">
                        <label class="form-check-label" for="simpleQueueDisabled">
                            Start Disabled
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus me-1"></i>Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Edit Modal -->
<div class="modal fade compact-modal" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="editModalTitle">Edit Queue</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                <div class="modal-body" id="editModalBody">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-save me-1"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Confirm Action</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>