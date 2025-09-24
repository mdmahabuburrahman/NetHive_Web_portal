<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-ban"></i>
            </span> Web Blocking
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
                    echo $greeting . ', Welcome to Web Blocking Management';
                    ?>
                    <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
                <li class="breadcrumb-item connection-status">
                    <span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>
                </li>
            </ul>
        </nav>
    </div>

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-end align-items-center">
            <small class="text-muted me-3">Configure router for DNS blocking (blocks external DNS, enables remote requests)</small>
            <button class="btn btn-success btn-sm" onclick="webBlockingManager.setupDNSRedirect()" id="setupDnsBtn" disabled>
                <i class="fa fa-cog me-2"></i>Setup DNS
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Custom Domain Blocking -->
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Custom Domain Blocking</h4>
                    <p class="card-description">Add specific domains to block</p>

                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="customDomain" placeholder="Enter domain (e.g., facebook.com)">
                        <button class="btn btn-primary btn-sm" type="button" onclick="webBlockingManager.addCustomDomain()">
                            <i class="fa fa-plus"></i> Add Domain
                        </button>
                    </div>

                    <div class="table-responsive custom-domains-table">
                        <table class="table table-striped" id="customDomainsTable">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">
                                        <i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to manage custom domains.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Blocking -->
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Category Blocking</h4>
                    <p class="card-description">Enable/disable predefined categories</p>

                    <div class="category-list">

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Porn" data-category="Porn">
                            <label class="form-check-label" for="category-Porn">
                                <i class="fa fa-eye-slash text-dark me-2"></i>Adult Content Only
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Adware_Malware_Porn" data-category="Adware_Malware_Porn">
                            <label class="form-check-label" for="category-Adware_Malware_Porn">
                                <i class="fa fa-eye-slash text-dark me-2"></i>Adware & Malware + Adult Content
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Social" data-category="Social">
                            <label class="form-check-label" for="category-Social">
                                <i class="fa fa-users text-info me-2"></i>Social Media Only
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Adware_Malware_Social" data-category="Adware_Malware_Social">
                            <label class="form-check-label" for="category-Adware_Malware_Social">
                                <i class="fa fa-users text-info me-2"></i>Adware & Malware + Social Media
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Adware_Malware" data-category="Adware_Malware">
                            <label class="form-check-label" for="category-Adware_Malware">
                                <i class="fa fa-ban text-warning me-2"></i>Adware & Malware
                            </label>
                        </div>


                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Adware_Malware_Fakenews" data-category="Adware_Malware_Fakenews">
                            <label class="form-check-label" for="category-Adware_Malware_Fakenews">
                                <i class="fa fa-shield text-danger me-2"></i>Adware & Malware + Fake News
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Fakenews" data-category="Fakenews">
                            <label class="form-check-label" for="category-Fakenews">
                                <i class="fa fa-shield text-danger me-2"></i>Fake News Only
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Adware_Malware_Gambling" data-category="Adware_Malware_Gambling">
                            <label class="form-check-label" for="category-Adware_Malware_Gambling">
                                <i class="fa fa-diamond text-secondary me-2"></i>Adware & Malware + Gambling
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Gambling" data-category="Gambling">
                            <label class="form-check-label" for="category-Gambling">
                                <i class="fa fa-diamond text-secondary me-2"></i>Gambling Only
                            </label>
                        </div>


                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input category-toggle" type="checkbox" id="category-Adware_Malware_Fakenews_Gambling_Porn_Social" data-category="Adware_Malware_Fakenews_Gambling_Porn_Social">
                            <label class="form-check-label" for="category-Adware_Malware_Fakenews_Gambling_Porn_Social">
                                <i class="fa fa-ban text-danger me-2"></i>All Categories
                            </label>
                        </div>
                    </div>

                    <div class="text-center mt-3" id="categoryStatus">
                        <small class="text-muted">
                            <i class="fa fa-info-circle me-1"></i>Connect to a NAS device to manage categories
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- DNS Rules Status -->
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Active DNS Blocking Rules</h4>
                        <div>
                            <button class="btn btn-warning btn-sm me-2" onclick="webBlockingManager.refreshRules()">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="webBlockingManager.clearAllBlocks()">
                                <i class="fa fa-trash"></i> Clear All
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive dns-rules-table">
                        <table class="table table-striped" id="dnsRulesTable">
                            <thead>
                                <tr>
                                    <th>Domain</th>
                                    <th>Address</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to view DNS rules.
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