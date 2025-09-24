<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa fa-home"></i>
            </span> Dashboard
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>Overview <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
                <li class="breadcrumb-item connection-status">
                    <span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>
                </li>

            </ul>
        </nav>
    </div>
    <div class="row">
        <div class="col-md-6 stretch-card grid-margin">
            <div class="card bg-gradient-danger card-img-holder text-white">
                <div class="card-body">
                    <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                    <h4 class="font-weight-normal mb-3">Active Users <i class="mdi mdi-chart-line mdi-24px float-end"></i></h4>
                    <h2 class="active-users-count">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6 stretch-card grid-margin">
            <div class="card bg-gradient-info card-img-holder text-white">
                <div class="card-body">
                    <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                    <h4 class="font-weight-normal mb-3">Total Users <i class="mdi mdi-bookmark-outline mdi-24px float-end"></i></h4>
                    <h2 class="total-users-count">0</h2>
                </div>
            </div>
        </div>
        <!-- <div class="col-md-4 stretch-card grid-margin">
            <div class="card bg-gradient-success card-img-holder text-white">
                <div class="card-body">
                    <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="circle-image" />
                    <h4 class="font-weight-normal mb-3">Income <i class="mdi mdi-diamond mdi-24px float-end"></i></h4>
                    <h2 class="income-amount">0.00Tk</h2>
                </div>
            </div>
        </div> -->
    </div>
    <div class="row">
        <!-- NAS System Resources Card -->
        <div class="col-md-4 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">NAS System Resources</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>CPU Usage</td>
                                    <td style="width: 70%">
                                        <div class="progress">
                                            <div class="progress-bar bg-gradient-success cpu-usage" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                0%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Memory</td>
                                    <td style="width: 70%">
                                        <div class="progress">
                                            <div class="progress-bar bg-gradient-info memory-usage" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                0%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>HDD</td>
                                    <td style="width: 70%">
                                        <div class="progress">
                                            <div class="progress-bar bg-gradient-warning hdd-usage" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                0%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Info Card -->
        <div class="col-md-4 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">System Info</h4>
                    <div class="table-responsive">
                        <table class="table system-info-table">
                            <tbody>
                                <tr>
                                    <td>Uptime</td>
                                    <td class="uptime">Loading...</td>
                                </tr>
                                <!-- <tr>
                                    <td>Board Name</td>
                                    <td class="board-name">Loading...</td>
                                </tr> -->
                                <tr>
                                    <td>Model</td>
                                    <td class="model">Loading...</td>
                                </tr>
                                <tr>
                                    <td>RouterOS</td>
                                    <td class="routeros-version">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Log Card -->
        <div class="col-md-4 stretch-card grid-margin">
            <div class="card" style="height: 300px;">
                <div class="card-body" style="display: flex; flex-direction: column; height: 100%;">
                    <h4 class="card-title" style="margin-bottom: 15px;">App Log</h4>
                    <div class="table-responsive" style="flex: 1; overflow-y: auto;">
                        <table class="table app-log-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated by JavaScript -->
                                <tr>
                                    <td colspan="2">Loading logs...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-7 grid-margin stretch-card">
            <div class="card" style="height: 400px;">
                <div class="card-body">
                    <div class="clearfix">
                        <h4 class="card-title float-start">Interface Traffic</h4>
                        <div class="float-end">
                            <select id="interface-selector" class="form-select form-select-sm" style="width: 150px; display: inline-block;">
                                <option value="">Loading interfaces...</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="interface-traffic-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5 grid-margin stretch-card">
            <div class="card" style="height: 400px;">
                <div class="card-body" style="display: flex; flex-direction: column; height: 100%;">
                    <h4 class="card-title" style="margin-bottom: 15px;">Hotspot Logs</h4>
                    <div class="table-responsive" style="flex: 1; overflow-y: auto; max-height: 320px;">
                        <table class="table hotspot-log-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User (IP)</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated by JavaScript -->
                                <tr>
                                    <td colspan="3">Loading logs...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>