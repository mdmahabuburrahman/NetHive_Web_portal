<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-cog"></i>
            </span> Settings
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
                    echo $greeting . ', Welcome to System Settings';
                    ?>
                    <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
            </ul>
        </nav>
    </div>
    <div class="row">
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title"><i class="fa fa-cogs me-2"></i>General Settings</h4>
                    <p class="card-description">Configure system-wide settings and preferences</p>
                    <div class="d-grid">
                        <button class="btn btn-gradient-primary" disabled>
                            <i class="fa fa-cog me-2"></i>Coming Soon
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title"><i class="fa fa-server me-2"></i>NAS Management</h4>
                    <p class="card-description">Configure and manage Network Access Servers</p>
                    <div class="d-grid">
                        <a href="index.php?page=nasManagement" class="btn btn-gradient-primary">
                            <i class="fa fa-server me-2"></i>Manage NAS
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title"><i class="fa fa-users me-2"></i>API User Management</h4>
                    <p class="card-description">Manage API users and their permissions</p>
                    <div class="d-grid">
                        <a href="index.php?page=apiUserManagement" class="btn btn-gradient-primary">
                            <i class="fa fa-users me-2"></i>Manage API Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title"><i class="fa fa-database me-2"></i>System Backup</h4>
                    <p class="card-description">Backup and restore system configuration</p>
                    <div class="d-grid">
                        <button class="btn btn-gradient-primary" disabled>
                            <i class="fa fa-download me-2"></i>Coming Soon
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>