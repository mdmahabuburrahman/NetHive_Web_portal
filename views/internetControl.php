<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-shield"></i>
            </span> Internet Control
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">
                    Hotspot Pool Management
                </li>
                <li class="breadcrumb-item connection-status">
                    <span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>
                </li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Hotspot IP Pools</h4>
                        <button class="btn btn-gradient-warning btn-sm" id="controlInternetBtn" data-bs-toggle="modal" data-bs-target="#internetControlModal" disabled>
                            <i class="fa fa-ban me-2"></i>Control Internet Access
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="poolsTable" class="table table-striped display">
                            <thead>
                                <tr>
                                    <th>Pool Name</th>
                                    <th>IP Range</th>
                                    <th>Profile</th>
                                    <th>Bandwidth Usage</th>
                                    <th>Active Users</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fa fa-info-circle me-2"></i>
                                        Please connect to a NAS device to view hotspot pools.
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

<!-- Internet Control Modal -->
<div class="modal fade" id="internetControlModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Control Internet Access</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="internetControlForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Active User *</label>
                        <select class="form-select form-select-sm" name="selected_user" id="selectedUser" required>
                            <option value="">Loading active users...</option>
                        </select>
                        <small class="form-text text-muted">Select a user to control their pool's internet access</small>
                    </div>
                    
                    <div class="mb-3" id="poolRangeContainer" style="display: none;">
                        <label class="form-label">IP Pool Range</label>
                        <input type="text" class="form-control form-control-sm" id="poolRange" readonly>
                        <small class="form-text text-muted">This entire pool will be affected</small>
                    </div>
                    
                    <div class="alert alert-warning" id="warningAlert" style="display: none;">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This will block internet access for the entire IP pool except for the selected user.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm" id="disableInternetBtn">
                        <i class="fa fa-ban me-1"></i>Disable Internet Access
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>