<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-server"></i>
            </span> NAS Management
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=settings">Settings</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    NAS Management
                    <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
            </ul>
        </nav>
    </div>

    <div class="row mb-3">
        <div class="col-12 text-end">
            <button class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#addNasModal">
                <i class="fa fa-plus me-1"></i> Add NAS
            </button>
        </div>
    </div>
    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">NAS List</h4>
                    <div class="table-responsive">
                        <table id="nasTable" class="table">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>NAS Name</th>
                                    <th>Hotspot Name</th>
                                    <th>IP:Port</th>
                                </tr>
                            </thead>
                            <tbody id="nasTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add/Edit NAS Modal -->
    <div class="modal fade" id="addNasModal" tabindex="-1" aria-labelledby="addNasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="nasForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addNasModalLabel"><i class="fa fa-plus me-2"></i>Add NAS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="nasId" name="id">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card form-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3"><i class="fa fa-server me-2"></i>Router</h5>
                                        <div class="mb-2">
                                            <label class="form-label">NAS Name</label>
                                            <input type="text" class="form-control" id="nasName" name="nas_name" placeholder="Enter NAS Name" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">NAS IP:Port</label>
                                            <input type="text" class="form-control" id="nasIpPort" name="nas_ip_port" placeholder="192.168.88.1:8728" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter Username" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter Password" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card form-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3"><i class="fa fa-wifi me-2"></i>Hotspot Info</h5>
                                        <div class="mb-2">
                                            <label class="form-label">Hotspot Name</label>
                                            <input type="text" class="form-control" id="hotspotName" name="hotspot_name" placeholder="Enter Hotspot Name" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">DNS Name</label>
                                            <input type="text" class="form-control" id="dnsName" name="dns_name" placeholder="Enter DNS Name">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Currency</label>
                                            <input type="text" class="form-control" id="currency" name="currency" value="BDT">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Session Timeout</label>
                                            <input type="text" class="form-control" id="sessionTimeout" name="session_timeout" placeholder="e.g. 1h 30m">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Live Report</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <label class="form-check-label mb-0" for="liveReportSwitch">Enable</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="liveReportSwitch" name="live_report" style="margin-left: 5px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card form-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3"><i class="fa fa-image me-2"></i>Upload Logo</h5>
                                        <div class="mb-2">
                                            <label class="form-label">Logo File</label>
                                            <input type="file" class="form-control" id="logoUpload" accept="image/*">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Preview</label>
                                            <div id="logoPreview" class="logo-preview-container">
                                                <span class="text-muted">No logo uploaded</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gradient-primary" id="saveNasBtn">Save NAS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="fa fa-trash me-2"></i>Delete NAS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this NAS?</p>
                    <p><strong id="deleteNasName"></strong></p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>


</div>