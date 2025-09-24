<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-bar-chart"></i>
            </span> Reports
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">
                    Overview <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
                <li class="breadcrumb-item connection-status">
                    <span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>
                </li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">System Reports</h4>
                        <button class="btn btn-primary btn-sm refresh-btn" onclick="refreshReports()">
                            <i class="fa fa-refresh me-2"></i>Refresh
                        </button>
                    </div>
                    
                    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="vouchers-tab" data-bs-toggle="tab" data-bs-target="#vouchers" type="button" role="tab">
                                <i class="fa fa-ticket me-2"></i>Voucher Report
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bandwidth-tab" data-bs-toggle="tab" data-bs-target="#bandwidth" type="button" role="tab">
                                <i class="fa fa-tachometer me-2"></i>Bandwidth Usage
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                                <i class="fa fa-file-text me-2"></i>System Logs
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="reportTabContent">
                        <!-- Voucher Report Tab -->
                        <div class="tab-pane fade show active" id="vouchers" role="tabpanel">
                            <div class="row mb-4">
                                <div class="col-md-4 stretch-card">
                                    <div class="card bg-gradient-danger card-img-holder text-white">
                                        <div class="card-body">
                                            <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                            <h4 class="font-weight-normal mb-3">Total Vouchers <i class="mdi mdi-ticket mdi-24px float-end"></i></h4>
                                            <h2 id="totalVouchersCount">0</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 stretch-card">
                                    <div class="card bg-gradient-info card-img-holder text-white">
                                        <div class="card-body">
                                            <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                            <h4 class="font-weight-normal mb-3">Active Vouchers <i class="mdi mdi-check-circle mdi-24px float-end"></i></h4>
                                            <h2 id="activeVouchersCount">0</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 stretch-card">
                                    <div class="card bg-gradient-warning card-img-holder text-white">
                                        <div class="card-body">
                                            <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                            <h4 class="font-weight-normal mb-3">Disabled Vouchers <i class="mdi mdi-close-circle mdi-24px float-end"></i></h4>
                                            <h2 id="disabledVouchersCount">0</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="table-responsive">
                                        <table id="voucherReportTable" class="table table-striped display">
                                            <thead>
                                                <tr>
                                                    <th>Username</th>
                                                    <th>Profile</th>
                                                    <th>Created Date</th>
                                                    <th>Time Limit</th>
                                                    <th>Data Limit</th>
                                                    <th>Status</th>
                                                    <th>Comment</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">
                                                        <i class="fa fa-info-circle me-2"></i>
                                                        Please connect to a NAS device to view voucher reports.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="chart-container">
                                        <canvas id="voucherChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bandwidth Usage Tab -->
                        <div class="tab-pane fade" id="bandwidth" role="tabpanel">
                            <div class="row mb-4">
                                <div class="col-md-4 stretch-card">
                                    <div class="card bg-gradient-success card-img-holder text-white">
                                        <div class="card-body">
                                            <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                            <h4 class="font-weight-normal mb-3">Total Download <i class="mdi mdi-download mdi-24px float-end"></i></h4>
                                            <h2 id="totalRxUsage">0 B</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 stretch-card">
                                    <div class="card bg-gradient-warning card-img-holder text-white">
                                        <div class="card-body">
                                            <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                            <h4 class="font-weight-normal mb-3">Total Upload <i class="mdi mdi-upload mdi-24px float-end"></i></h4>
                                            <h2 id="totalTxUsage">0 B</h2>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 stretch-card">
                                    <div class="card bg-gradient-info card-img-holder text-white">
                                        <div class="card-body">
                                            <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                                            <h4 class="font-weight-normal mb-3">Total Usage <i class="mdi mdi-chart-line mdi-24px float-end"></i></h4>
                                            <h2 id="totalBandwidthUsage">0 B</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table id="bandwidthReportTable" class="table table-striped display">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>IP Address</th>
                                            <th>Uptime</th>
                                            <th>Download</th>
                                            <th>Upload</th>
                                            <th>Total Usage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fa fa-info-circle me-2"></i>
                                                Please connect to a NAS device to view bandwidth usage.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- System Logs Tab -->
                        <div class="tab-pane fade" id="logs" role="tabpanel">
                            <div class="table-responsive">
                                <table id="systemLogsTable" class="table table-striped display">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Topics</th>
                                            <th>Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                <i class="fa fa-info-circle me-2"></i>
                                                Please connect to a NAS device to view system logs.
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
</div>