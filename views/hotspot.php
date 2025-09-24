<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-wifi"></i>
            </span> Hotspot
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
                    echo $greeting . ', Welcome to Hotspot Management';
                    ?>
                    <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
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
                    <ul class="nav nav-tabs" id="hotspotTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab"><i class="fa fa-users me-2" style="color:#b66dff;"></i>Users</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="profiles-tab" data-bs-toggle="tab" data-bs-target="#profiles" type="button" role="tab"><i class="fa fa-id-card me-2" style="color:#b66dff;"></i>User Profiles</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab"><i class="fa fa-user-circle me-2" style="color:#b66dff;"></i>Active Users</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="hosts-tab" data-bs-toggle="tab" data-bs-target="#hosts" type="button" role="tab"><i class="fa fa-server me-2" style="color:#b66dff;"></i>Hosts</button>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="hotspotTabContent">
                        <div class="tab-pane fade show active" id="users" role="tabpanel">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <button class="btn btn-primary btn-sm" onclick="showGenerateVoucherModal()"><i class="fa fa-ticket me-2"></i>Generate Vouchers</button>
                                    <button class="btn btn-success btn-sm ms-2" onclick="printVouchers()" id="printVouchersBtn"><i class="fa fa-print me-2"></i>Print Vouchers</button>
                                </div>
                                <div class="table-responsive">
                                    <table id="usersTable" class="table">
                                        <thead>
                                            <tr>
                                                <th>Action</th>
                                                <th>Name</th>
                                                <th>Profile</th>
                                                <th>Server</th>
                                                <th>MAC Address</th>
                                                <th>Bytes In</th>
                                                <th>Bytes Out</th>
                                                <th>Comment</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">
                                                    <i class="fa fa-info-circle me-2"></i>
                                                    Please connect to a NAS device to view hotspot users.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="profiles" role="tabpanel">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <button class="btn btn-primary btn-sm" onclick="showAddUserProfileModal()"><i class="fa fa-plus me-2"></i>Add User Profile</button>
                                </div>
                                <div class="table-responsive">
                                    <table id="profilesTable" class="table">
                                        <thead>
                                            <tr>
                                                <th>Action</th>
                                                <th>Name</th>
                                                <th>Shared Users</th>
                                                <th>Rate Limit</th>
                                                <th>Expire Mode</th>
                                                <th>Validity</th>
                                                <th>User Lock</th>
                                                <th>Server Lock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">
                                                    <i class="fa fa-info-circle me-2"></i>
                                                    Please connect to a NAS device to view hotspot profiles.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="active" role="tabpanel">
                            <div class="row">
                                <div class="table-responsive">
                                    <table id="activeUsersTable" class="table">
                                        <thead>
                                            <tr>
                                                <th>Action</th>
                                                <th>User</th>
                                                <th>Address</th>
                                                <th>MAC Address</th>
                                                <th>Uptime</th>
                                                <th>Bytes In</th>
                                                <th>Bytes Out</th>
                                                <th>Session Time Left</th>
                                                <th>Login By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">
                                                    <i class="fa fa-info-circle me-2"></i>
                                                    Please connect to a NAS device to view active users.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="hosts" role="tabpanel">
                            <div class="row">
                                <div class="table-responsive">
                                    <table id="hostsTable" class="table">
                                        <thead>
                                            <tr>
                                                <th>Action</th>
                                                <th>MAC Address</th>
                                                <th>Address</th>
                                                <th>To Address</th>
                                                <th>Server</th>
                                                <th>Authorized</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    <i class="fa fa-info-circle me-2"></i>
                                                    Please connect to a NAS device to view hosts.
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
</div>

<!-- Generate Voucher Modal -->
<div class="modal fade" id="generateVoucherModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Vouchers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="generateVoucherForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control form-control-sm" name="qty" min="1" max="100" value="10" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Server</label>
                            <select class="form-select form-select-sm" name="server">
                                <option value="">Default</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select form-select-sm" name="user" required>
                                <option value="up">Username & Password</option>
                                <option value="vc">Voucher Code</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Length</label>
                            <select class="form-select form-select-sm" name="userl" required>
                                <option value="4">4 Characters</option>
                                <option value="5">5 Characters</option>
                                <option value="6">6 Characters</option>
                                <option value="7">7 Characters</option>
                                <option value="8">8 Characters</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prefix</label>
                            <input type="text" class="form-control form-control-sm" name="prefix" placeholder="Optional">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Character Type</label>
                            <select class="form-select form-select-sm" name="char" required>
                                <option value="lower">Lowercase</option>
                                <option value="upper">Uppercase</option>
                                <option value="upplow">Mixed Case</option>
                                <option value="num">Numbers Only</option>
                                <option value="mix">Numbers + Lowercase</option>
                                <option value="mix1">Numbers + Uppercase</option>
                                <option value="mix2">Numbers + Mixed Case</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile</label>
                            <select class="form-select form-select-sm" name="profile" required>
                                <option value="">Select Profile</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time Limit</label>
                            <input type="text" class="form-control form-control-sm" name="timelimit" placeholder="1h">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data Limit</label>
                            <input type="text" class="form-control form-control-sm" name="datalimit" placeholder="1G">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Comment</label>
                            <input type="text" class="form-control form-control-sm" name="gcomment" placeholder="Optional">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="generateVouchers()" id="generateBtn">Generate</button>
            </div>
        </div>
    </div>
</div>
<!-- Add User Profile Modal -->
<div class="modal fade" id="addUserProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add User Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills nav-fill mb-3" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">General</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="details-tab" data-bs-toggle="pill" data-bs-target="#details" type="button" role="tab">Details</button>
                    </li>
                </ul>
                <div class="tab-content" id="profileTabContent">
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <form id="addUserProfileForm">
                            <input type="hidden" name="profile_id" value="">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control form-control-sm" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address Pool</label>
                                <select class="form-select form-select-sm" name="address_pool">
                                    <option value="">None</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Shared Users</label>
                                <input type="number" class="form-control form-control-sm" name="shared_users" min="1" value="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rate Limit</label>
                                <input type="text" class="form-control form-control-sm" name="rate_limit" placeholder="example: 512k/1M">
                                <small class="text-muted">example 512k/1M</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Parent Queue</label>
                                <select class="form-select form-select-sm" name="parent_queue">
                                    <option value="">None</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="details" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label">Expired Mode</label>
                            <select class="form-select form-select-sm" name="expired_mode" onchange="toggleValidityField(this.value)">
                                <option value="0">None</option>
                                <option value="rem">Remove</option>
                                <option value="ntf">Notice</option>
                                <option value="remc">Remove & Record</option>
                                <option value="ntfc">Notice & Record</option>
                            </select>
                            <small class="text-muted">None: User without expiry time; Remove: User will be deleted after expiry time; Notice: user can't login after time expired and get notification after trying to login again.; Record: Record user data after login.</small>
                        </div>
                        <div class="mb-3" id="validityField" style="display: none;">
                            <label class="form-label">Validity Period</label>
                            <input type="text" class="form-control form-control-sm" name="validity" placeholder="1d">
                            <small class="text-muted">Time period like 1h, 1d, 1w</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lock User</label>
                            <select class="form-select form-select-sm" name="lock_user">
                                <option value="Disable">Disable</option>
                                <option value="Enable">Enable</option>
                            </select>
                            <small class="text-muted">Lock the MAC Address of user after login.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lock Server</label>
                            <select class="form-select form-select-sm" name="lock_server">
                                <option value="Disable">Disable</option>
                                <option value="Enable">Enable</option>
                            </select>
                            <small class="text-muted">Lock the MAC Address of user after login.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveUserProfile()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Print Voucher Modal -->
<div class="modal fade" id="printVoucherModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Vouchers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Profile</label>
                    <select class="form-select form-select-sm" id="printProfileSelect">
                        <option value="">All Profiles</option>
                    </select>
                    <small class="text-muted">Select a specific profile or leave empty to print all users</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="executePrint()">Print</button>
            </div>
        </div>
    </div>
</div>