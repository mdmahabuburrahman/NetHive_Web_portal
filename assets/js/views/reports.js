class ReportsManager {
  constructor() {
    this.init();
  }

  init() {
    this.bindEvents();
    this.initCharts();
  }

  bindEvents() {
    $(document).on("nas:connected", (e, nasId, nasName) => {
      console.log("NAS connected:", nasId, nasName);
      this.handleNasConnection();
    });

    $(document).on("nas:disconnected", () => {
      console.log("NAS disconnected");
      this.handleNasDisconnection();
    });
  }

  handleNasConnection() {
    this.loadAllReports();
    this.updateConnectionStatus(true);
  }

  handleNasDisconnection() {
    this.clearAllData();
    this.updateConnectionStatus(false);
  }

  updateConnectionStatus(connected) {
    let statusHtml;
    if (connected) {
      statusHtml = `<span class="badge bg-success"><i class="fa fa-check"></i> Connected</span>`;
    } else {
      statusHtml = `<span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>`;
    }
    $(".connection-status").html(statusHtml);
  }

  async loadAllReports() {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) return;

    await Promise.all([
      this.loadVoucherReport(),
      this.loadBandwidthUsage(),
      this.loadSystemLogs()
    ]);
  }

  async loadVoucherReport() {
    try {
      const response = await fetch(
        `api/reports_operations.php?action=get_voucher_report&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderVoucherReport(result.data);
      } else {
        this.showError("#voucherReportTable", result.error);
      }
    } catch (error) {
      console.error("Error loading voucher report:", error);
      this.showError("#voucherReportTable", "Failed to load voucher report");
    }
  }

  async loadBandwidthUsage() {
    try {
      const response = await fetch(
        `api/reports_operations.php?action=get_bandwidth_usage&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderBandwidthUsage(result.data);
      } else {
        this.showError("#bandwidthReportTable", result.error);
      }
    } catch (error) {
      console.error("Error loading bandwidth usage:", error);
      this.showError("#bandwidthReportTable", "Failed to load bandwidth usage");
    }
  }

  async loadTrafficReport() {
    try {
      const response = await fetch(
        `api/reports_operations.php?action=get_traffic_report&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderTrafficReport(result.data);
      } else {
        this.showError("#trafficReportTable", result.error);
      }
    } catch (error) {
      console.error("Error loading traffic report:", error);
      this.showError("#trafficReportTable", "Failed to load traffic report");
    }
  }

  async loadSystemLogs() {
    try {
      const response = await fetch(
        `api/reports_operations.php?action=get_system_logs&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderSystemLogs(result.data);
      } else {
        this.showError("#systemLogsTable", result.error);
      }
    } catch (error) {
      console.error("Error loading system logs:", error);
      this.showError("#systemLogsTable", "Failed to load system logs");
    }
  }

  async loadHotspotLogs() {
    try {
      const response = await fetch(
        `api/reports_operations.php?action=get_hotspot_logs&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderHotspotLogs(result.data);
      } else {
        this.showError("#hotspotLogsTable", result.error);
      }
    } catch (error) {
      console.error("Error loading hotspot logs:", error);
      this.showError("#hotspotLogsTable", "Failed to load hotspot logs");
    }
  }

  renderVoucherReport(data) {
    const tbody = $("#voucherReportTable tbody");
    tbody.empty();

    let activeCount = 0;
    let disabledCount = 0;

    if (!data.vouchers || data.vouchers.length === 0) {
      tbody.html(
        '<tr><td colspan="7" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No voucher data found</td></tr>'
      );
    } else {
      data.vouchers.forEach((voucher) => {
        const status = voucher.disabled === 'false' ? 'Active' : 'Disabled';
        const statusClass = voucher.disabled === 'false' ? 'bg-success' : 'bg-danger';
        
        if (voucher.disabled === 'false') activeCount++;
        else disabledCount++;
        
        const row = `
          <tr>
            <td>${voucher.username}</td>
            <td><span class="badge bg-primary">${voucher.profile}</span></td>
            <td>${voucher.created_date}</td>
            <td>${voucher.time_limit}</td>
            <td>${voucher.data_limit}</td>
            <td><span class="badge ${statusClass}">${status}</span></td>
            <td>${voucher.comment}</td>
          </tr>
        `;
        tbody.append(row);
      });
    }

    // Update summary
    $("#totalVouchersCount").text(data.total_vouchers);
    $("#activeVouchersCount").text(activeCount);
    $("#disabledVouchersCount").text(disabledCount);

    if (data.vouchers && data.vouchers.length > 0) {
      this.initDataTable("#voucherReportTable");
    }
    this.updateVoucherChart(data.profile_stats);
  }

  renderBandwidthUsage(data) {
    const tbody = $("#bandwidthReportTable tbody");
    tbody.empty();

    if (!data.users || data.users.length === 0) {
      tbody.html(
        '<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No bandwidth usage data found</td></tr>'
      );
    } else {
      data.users.forEach((user) => {
        const row = `
          <tr>
            <td>${user.user}</td>
            <td>${user.address}</td>
            <td>${user.uptime}</td>
            <td>${user.rx_formatted}</td>
            <td>${user.tx_formatted}</td>
            <td><strong>${user.total_formatted}</strong></td>
          </tr>
        `;
        tbody.append(row);
      });
    }

    // Update summary
    $("#totalRxUsage").text(data.summary.total_rx);
    $("#totalTxUsage").text(data.summary.total_tx);
    $("#totalBandwidthUsage").text(data.summary.total_usage);

    if (data.users && data.users.length > 0) {
      this.initDataTable("#bandwidthReportTable");
    }
  }

  renderTrafficReport(data) {
    const tbody = $("#trafficReportTable tbody");
    tbody.empty();

    if (!data || data.length === 0) {
      tbody.html(
        '<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No traffic data found</td></tr>'
      );
    } else {
      data.forEach((traffic) => {
        const row = `
          <tr>
            <td>${traffic.interface}</td>
            <td>${this.formatBytes(traffic.rx_bytes)}</td>
            <td>${this.formatBytes(traffic.tx_bytes)}</td>
            <td class="text-center">${traffic.rx_packets.toLocaleString()}</td>
            <td class="text-center">${traffic.tx_packets.toLocaleString()}</td>
            <td><span class="badge ${traffic.status === 'true' ? 'bg-success' : 'bg-danger'}">${traffic.status === 'true' ? 'Active' : 'Inactive'}</span></td>
          </tr>
        `;
        tbody.append(row);
      });
    }

    this.initDataTable("#trafficReportTable");
    this.updateTrafficChart(data);
  }

  renderSystemLogs(data) {
    const tbody = $("#systemLogsTable tbody");
    tbody.empty();

    if (!data || data.length === 0) {
      tbody.html(
        '<tr><td colspan="3" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No system logs found</td></tr>'
      );
    } else {
      data.forEach((log) => {
        const row = `
          <tr>
            <td>${log.time}</td>
            <td><span class="badge bg-info">${log.topics}</span></td>
            <td>${log.message}</td>
          </tr>
        `;
        tbody.append(row);
      });
    }

    if (data && data.length > 0) {
      this.initDataTable("#systemLogsTable");
    }
  }

  renderHotspotLogs(data) {
    const tbody = $("#hotspotLogsTable tbody");
    tbody.empty();

    if (!data || data.length === 0) {
      tbody.html(
        '<tr><td colspan="3" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No hotspot logs found</td></tr>'
      );
    } else {
      data.forEach((log) => {
        const row = `
          <tr>
            <td>${log.time}</td>
            <td><span class="badge bg-warning">${log.topics}</span></td>
            <td>${log.message}</td>
          </tr>
        `;
        tbody.append(row);
      });
    }

    this.initDataTable("#hotspotLogsTable");
  }

  initDataTable(tableId) {
    if ($.fn.DataTable.isDataTable(tableId)) {
      $(tableId).DataTable().destroy();
    }
    
    // Only initialize if table has data rows
    const rowCount = $(tableId + ' tbody tr').length;
    const hasData = $(tableId + ' tbody tr td').first().attr('colspan') === undefined;
    
    if (rowCount > 0 && hasData) {
      $(tableId).DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, "desc"]]
      });
    }
  }

  initCharts() {
    this.userChart = null;
    this.trafficChart = null;
  }

  updateVoucherChart(profileStats) {
    const ctx = document.getElementById('voucherChart');
    if (!ctx) return;

    if (this.voucherChart) {
      this.voucherChart.destroy();
    }

    const labels = Object.keys(profileStats);
    const data = Object.values(profileStats).map(stat => stat.total);
    const colors = [
      'rgba(255, 99, 132, 0.8)',
      'rgba(54, 162, 235, 0.8)',
      'rgba(255, 205, 86, 0.8)',
      'rgba(75, 192, 192, 0.8)',
      'rgba(153, 102, 255, 0.8)'
    ];

    this.voucherChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: colors.slice(0, labels.length),
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }

  updateTrafficChart(trafficData) {
    const ctx = document.getElementById('trafficChart');
    if (!ctx) return;

    if (this.trafficChart) {
      this.trafficChart.destroy();
    }

    const labels = trafficData.map(traffic => traffic.interface);
    const rxData = trafficData.map(traffic => traffic.rx_bytes);
    const txData = trafficData.map(traffic => traffic.tx_bytes);

    this.trafficChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          label: 'RX Bytes',
          data: rxData,
          backgroundColor: [
            'rgba(255, 99, 132, 0.5)',
            'rgba(54, 162, 235, 0.5)',
            'rgba(255, 205, 86, 0.5)',
            'rgba(75, 192, 192, 0.5)'
          ],
          borderColor: [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 205, 86, 1)',
            'rgba(75, 192, 192, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          tooltip: {
            callbacks: {
              label: (context) => {
                return context.label + ': ' + this.formatBytes(context.parsed);
              }
            }
          }
        }
      }
    });
  }

  clearAllData() {
    const tables = [
      "#voucherReportTable",
      "#bandwidthReportTable",
      "#systemLogsTable"
    ];

    tables.forEach(tableId => {
      if ($.fn.DataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
      }
      $(tableId + " tbody").html(
        '<tr><td colspan="7" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to view reports.</td></tr>'
      );
    });

    $("#totalVouchersCount, #activeVouchersCount, #disabledVouchersCount").text("0");
    $("#totalRxUsage, #totalTxUsage, #totalBandwidthUsage").text("0 B");

    if (this.voucherChart) {
      this.voucherChart.destroy();
      this.voucherChart = null;
    }
  }

  showError(tableSelector, message) {
    const colCount = $(tableSelector + " thead tr th").length;
    $(tableSelector + " tbody").html(
      `<tr><td colspan="${colCount}" class="text-center text-danger"><i class="fa fa-exclamation-triangle me-2"></i>${message}</td></tr>`
    );
  }

  formatBytes(bytes) {
    if (!bytes || bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  }

  refreshReports() {
    if (window.nasManager && window.nasManager.getConnectionStatus()) {
      this.loadAllReports();
      if (window.Toast) {
        window.Toast.success("Reports refreshed successfully");
      }
    } else {
      if (window.Toast) {
        window.Toast.warning("Please connect to a NAS device first");
      }
    }
  }
}

$(document).ready(function () {
  setTimeout(() => {
    console.log("Creating ReportsManager instance...");
    window.reportsManager = new ReportsManager();

    $(document).on("nas:connected", function (event, nasId, nasName) {
      console.log("NAS connected in reports page:", nasId, nasName);
      if (window.reportsManager) {
        window.reportsManager.handleNasConnection();
      }
    });

    $(document).on("nas:disconnected", function () {
      console.log("NAS disconnected in reports page");
      if (window.reportsManager) {
        window.reportsManager.handleNasDisconnection();
      }
    });

    setTimeout(() => {
      if (window.nasManager && window.nasManager.getConnectionStatus()) {
        console.log("Reports page: Found existing connection");
        if (window.reportsManager) {
          window.reportsManager.handleNasConnection();
        }
      } else {
        if (window.reportsManager) {
          window.reportsManager.updateConnectionStatus(false);
        }
      }
    }, 200);
  }, 500);


});

function refreshReports() {
  if (window.reportsManager) {
    window.reportsManager.refreshReports();
  }
}