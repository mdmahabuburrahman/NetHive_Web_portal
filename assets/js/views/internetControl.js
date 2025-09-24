class InternetControlManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initDataTable();
    }

    bindEvents() {
        $(document).on('nas:connected', (e, nasId, nasName) => {
            this.handleNasConnection();
        });

        $(document).on('nas:disconnected', () => {
            this.handleNasDisconnection();
        });

        $('#selectedUser').on('change', (e) => {
            this.handleUserSelection(e.target.value);
        });

        $('#internetControlForm').on('submit', (e) => {
            e.preventDefault();
            this.controlInternet();
        });

        $('#internetControlModal').on('show.bs.modal', () => {
            this.loadActiveUsers();
        });
    }

    handleNasConnection() {
        this.loadPools();
        this.updateConnectionStatus(true);
    }

    handleNasDisconnection() {
        this.clearData();
        this.updateConnectionStatus(false);
    }

    updateConnectionStatus(connected) {
        const statusHtml = connected 
            ? '<span class="badge bg-success"><i class="fa fa-check"></i> Connected</span>'
            : '<span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>';
        $('.connection-status').html(statusHtml);
        
        // Enable/disable control button based on connection status
        $('#controlInternetBtn').prop('disabled', !connected);
    }

    async loadPools() {
        if (!window.nasManager?.getConnectionStatus()) return;

        try {
            const response = await fetch(`api/internetControl_operations.php?action=get_pools&nas_id=${window.nasManager.getCurrentNasId()}`);
            const result = await response.json();

            if (result.success) {
                this.renderPools(result.data);
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            console.error('Error loading pools:', error);
            this.showError('Failed to load hotspot pools');
        }
    }

    async loadActiveUsers() {
        if (!window.nasManager?.getConnectionStatus()) return;

        try {
            const response = await fetch(`api/internetControl_operations.php?action=get_active_users&nas_id=${window.nasManager.getCurrentNasId()}`);
            const result = await response.json();

            if (result.success) {
                this.populateUserDropdown(result.data);
            } else {
                $('#selectedUser').html('<option value="">No active users found</option>');
            }
        } catch (error) {
            console.error('Error loading active users:', error);
            $('#selectedUser').html('<option value="">Error loading users</option>');
        }
    }

    async handleUserSelection(userValue) {
        if (!userValue) {
            $('#poolRangeContainer').hide();
            $('#warningAlert').hide();
            return;
        }

        const [username, ip] = userValue.split('|');
        
        try {
            const response = await fetch('api/internetControl_operations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_user_pool&nas_id=${window.nasManager.getCurrentNasId()}&user_ip=${ip}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                $('#poolRange').val(result.data.pool_range);
                $('#poolRangeContainer').show();
                $('#warningAlert').show();
            } else {
                window.Toast?.error(result.error);
            }
        } catch (error) {
            console.error('Error getting user pool:', error);
            window.Toast?.error('Failed to get user pool information');
        }
    }

    async controlInternet() {
        const selectedUser = $('#selectedUser').val();
        if (!selectedUser) return;

        const [username, ip] = selectedUser.split('|');
        const poolRange = $('#poolRange').val();

        try {
            const response = await fetch('api/internetControl_operations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=control_internet&nas_id=${window.nasManager.getCurrentNasId()}&user_ip=${ip}&pool_range=${poolRange}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.Toast?.success('Internet access controlled successfully');
                $('#internetControlModal').modal('hide');
                this.loadPools();
            } else {
                window.Toast?.error(result.error);
            }
        } catch (error) {
            console.error('Error controlling internet:', error);
            window.Toast?.error('Failed to control internet access');
        }
    }

    renderPools(pools) {
        const tbody = $('#poolsTable tbody');
        tbody.empty();

        if (!pools || pools.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No hotspot pools found</td></tr>');
            return;
        }

        pools.forEach(pool => {
            const statusClass = pool.status === 'Active' ? 'bg-success' : 'bg-secondary';
            const row = `
                <tr>
                    <td><strong>${pool.name}</strong></td>
                    <td><code>${pool.ranges}</code></td>
                    <td><span class="badge bg-primary">${pool.profile}</span></td>
                    <td>${pool.bandwidth_usage}</td>
                    <td><span class="badge bg-info">${pool.active_users}</span></td>
                    <td><span class="badge ${statusClass}">${pool.status}</span></td>
                </tr>
            `;
            tbody.append(row);
        });

        this.initDataTable();
    }

    populateUserDropdown(users) {
        const select = $('#selectedUser');
        select.empty();
        
        if (!users || users.length === 0) {
            select.append('<option value="">No active users found</option>');
            return;
        }

        select.append('<option value="">Select a user...</option>');
        users.forEach(user => {
            select.append(`<option value="${user.username}|${user.ip}">${user.display}</option>`);
        });
    }

    initDataTable() {
        if ($.fn.DataTable.isDataTable('#poolsTable')) {
            $('#poolsTable').DataTable().destroy();
        }
        
        const rowCount = $('#poolsTable tbody tr').length;
        const hasData = $('#poolsTable tbody tr td').first().attr('colspan') === undefined;
        
        if (rowCount > 0 && hasData) {
            $('#poolsTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'asc']]
            });
        }
    }

    clearData() {
        if ($.fn.DataTable.isDataTable('#poolsTable')) {
            $('#poolsTable').DataTable().destroy();
        }
        
        $('#poolsTable tbody').html('<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to view hotspot pools.</td></tr>');
    }

    showError(message) {
        $('#poolsTable tbody').html(`<tr><td colspan="6" class="text-center text-danger"><i class="fa fa-exclamation-triangle me-2"></i>${message}</td></tr>`);
    }
}

$(document).ready(function() {
    setTimeout(() => {
        window.internetControlManager = new InternetControlManager();
        
        if (window.nasManager?.getConnectionStatus()) {
            window.internetControlManager.handleNasConnection();
        }
    }, 500);
});