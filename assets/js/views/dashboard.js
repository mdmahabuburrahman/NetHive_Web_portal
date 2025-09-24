console.log("Dashboard JS loaded");

$(document).ready(function () {
  console.log("Document ready");

  let trafficChart;
  let trafficData = { rx: [], tx: [], labels: [] };
  let selectedNasId = null;
  let connectionStatus = false;

  initDashboard();

  function initDashboard() {
    console.log("Init dashboard");

    // Listen for NAS connection events from global manager
    $(document).on("nas:connected", function (event, nasId) {
      console.log("NAS connected:", nasId);
      selectedNasId = nasId;
      connectionStatus = true;
      updateConnectionStatus();
      loadDashboardData();
      loadInterfaces();
      if (trafficChart) {
        updateTrafficChart();
      }
    });

    $(document).on("nas:disconnected", function () {
      console.log("NAS disconnected");
      connectionStatus = false;
      updateConnectionStatus();
      clearDashboardData();
    });

    // Initialize chart with delay to avoid conflicts
    setTimeout(function () {
      try {
        initTrafficChart();
      } catch (e) {
        console.log("Chart init error:", e);
      }
    }, 2000);

    setInterval(function () {
      if (connectionStatus) {
        loadDashboardData();
      }
    }, 30000);

    setInterval(function () {
      if (connectionStatus && trafficChart) {
        updateTrafficChart();
      }
    }, 10000);
  }

  function updateConnectionStatus() {
    let statusHtml;
    if (connectionStatus) {
      statusHtml = `<span class="badge bg-success"><i class="fa fa-check"></i> Connected</span>`;
    } else {
      statusHtml = `<span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>`;
    }

    if ($(".connection-status").length === 0) {
      $(".breadcrumb").append(
        '<li class="breadcrumb-item connection-status">' + statusHtml + "</li>"
      );
    } else {
      $(".connection-status").html(statusHtml);
    }
  }

  function clearDashboardData() {
    $(".active-users-count, .total-users-count").text("0");
    $(".income-amount").text("0.00 BDT");
    updateProgressBar(".cpu-usage", 0);
    updateProgressBar(".memory-usage", 0);
    updateProgressBar(".hdd-usage", 0);
    $(".uptime, .board-name, .model, .routeros-version").text("Not connected");
    $(".hotspot-log-table tbody").html(
      '<tr><td colspan="3">Not connected</td></tr>'
    );
    $(".app-log-table tbody").html(
      '<tr><td colspan="2">Not connected</td></tr>'
    );
    $("#interface-selector").html('<option value="">Not connected</option>');
    if (trafficChart) {
      trafficChart.data.labels = [];
      trafficChart.data.datasets[0].data = [];
      trafficChart.data.datasets[1].data = [];
      trafficChart.update();
    }
  }

  function loadDashboardData() {
    loadSystemResources();
    loadSystemInfo();
    loadHotspotUsers();
    loadHotspotLogs();
    loadAppLogs();
  }

  function loadSystemResources() {
    const url = selectedNasId
      ? `api/dashboard_operations.php?action=system_resources&nas_id=${selectedNasId}`
      : "api/dashboard_operations.php?action=system_resources";

    $.get(url).done(function (response) {
      const data = JSON.parse(response);
      if (data.success !== false) {
        updateProgressBar(".cpu-usage", data.cpu || 0);
        updateProgressBar(".memory-usage", data.memory || 0);
        updateProgressBar(".hdd-usage", data.hdd || 0);
      }
    });
  }

  function loadSystemInfo() {
    const url = selectedNasId
      ? `api/dashboard_operations.php?action=system_info&nas_id=${selectedNasId}`
      : "api/dashboard_operations.php?action=system_info";

    $.get(url)
      .done(function (response) {
        const data = JSON.parse(response);
        if (data.success !== false) {
          $(".uptime").text(formatUptime(data.uptime) || "Unknown");
          $(".board-name").text(data.board_name || "Unknown");
          $(".model").text(data.model || "Unknown");
          $(".routeros-version").text(data.version || "Unknown");
        } else {
          $(".uptime, .board-name, .model, .routeros-version").text("Error");
        }
      })
      .fail(function () {
        $(".uptime, .board-name, .model, .routeros-version").text("Error");
      });
  }

  function loadHotspotUsers() {
    const url = selectedNasId
      ? `api/dashboard_operations.php?action=hotspot_users&nas_id=${selectedNasId}`
      : "api/dashboard_operations.php?action=hotspot_users";

    $.get(url)
      .done(function (response) {
        const data = JSON.parse(response);
        if (data.success !== false) {
          $(".active-users-count").text(data.active || 0);
          $(".total-users-count").text(data.total || 0);
        } else {
          $(".active-users-count, .total-users-count").text("0");
        }
      })
      .fail(function () {
        $(".active-users-count, .total-users-count").text("0");
      });
  }

  function loadHotspotLogs() {
    const url = selectedNasId
      ? `api/dashboard_operations.php?action=hotspot_logs&nas_id=${selectedNasId}`
      : "api/dashboard_operations.php?action=hotspot_logs";

    $.get(url)
      .done(function (response) {
        const data = JSON.parse(response);
        let html = "";

        if (data.success !== false && Array.isArray(data) && data.length > 0) {
          data.forEach(function (log) {
            html += `<tr>
                            <td>${log.time || ""}</td>
                            <td>${log.user_ip || "-"}</td>
                            <td>${log.message || ""}</td>
                        </tr>`;
          });
        } else {
          html = '<tr><td colspan="3">No logs available</td></tr>';
        }

        $(".hotspot-log-table tbody").html(html);
      })
      .fail(function () {
        $(".hotspot-log-table tbody").html(
          '<tr><td colspan="3">Error loading logs</td></tr>'
        );
      });
  }

  function loadAppLogs() {
    const url = selectedNasId
      ? `api/dashboard_operations.php?action=app_logs&nas_id=${selectedNasId}`
      : "api/dashboard_operations.php?action=app_logs";

    $.get(url)
      .done(function (response) {
        const data = JSON.parse(response);
        let html = "";

        if (data.success !== false && Array.isArray(data) && data.length > 0) {
          data.forEach(function (log) {
            html += `<tr>
                            <td>${log.time || ""}</td>
                            <td>${log.message || ""}</td>
                        </tr>`;
          });
        } else {
          html = '<tr><td colspan="2">No logs available</td></tr>';
        }

        $(".app-log-table tbody").html(html);
      })
      .fail(function () {
        $(".app-log-table tbody").html(
          '<tr><td colspan="2">Error loading logs</td></tr>'
        );
      });
  }

  function loadInterfaces() {
    const url = selectedNasId
      ? `api/dashboard_operations.php?action=interfaces&nas_id=${selectedNasId}`
      : "api/dashboard_operations.php?action=interfaces";

    $.get(url)
      .done(function (response) {
        const data = JSON.parse(response);
        let options = "";

        if (data.success !== false && Array.isArray(data)) {
          data.forEach(function (iface) {
            options += `<option value="${iface}">${iface}</option>`;
          });
          $("#interface-selector").html(options);
        } else {
          $("#interface-selector").html(
            '<option value="">No interfaces available</option>'
          );
        }
      })
      .fail(function () {
        $("#interface-selector").html(
          '<option value="">Error loading interfaces</option>'
        );
      });
  }

  function initTrafficChart() {
    // Destroy existing chart if it exists
    if (trafficChart) {
      trafficChart.destroy();
    }

    const ctx = document
      .getElementById("interface-traffic-chart")
      .getContext("2d");
    trafficChart = new Chart(ctx, {
      type: "line",
      data: {
        labels: [],
        datasets: [
          {
            label: "RX (bits/sec)",
            data: [],
            borderColor: "rgb(75, 192, 192)",
            backgroundColor: "rgba(75, 192, 192, 0.3)",
            fill: true,
            tension: 0.1,
          },
          {
            label: "TX (bits/sec)",
            data: [],
            borderColor: "rgb(255, 99, 132)",
            backgroundColor: "rgba(255, 99, 132, 0.3)",
            fill: true,
            tension: 0.1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatBits(value);
              },
            },
          },
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function (context) {
                return (
                  context.dataset.label + ": " + formatBits(context.parsed.y)
                );
              },
            },
          },
        },
      },
    });

    // Start updating chart after initialization
    setTimeout(function () {
      updateTrafficChart();
    }, 1000);
  }

  function updateTrafficChart() {
    if (!trafficChart) return;

    const selectedInterface = $("#interface-selector").val();
    if (!selectedInterface) return;

    const url = selectedNasId
      ? `api/dashboard_operations.php?action=interface_traffic&interface=${selectedInterface}&nas_id=${selectedNasId}`
      : `api/dashboard_operations.php?action=interface_traffic&interface=${selectedInterface}`;

    $.get(url).done(function (response) {
      const data = JSON.parse(response);
      if (data.success !== false) {
        const now = new Date().toLocaleTimeString();

        // Data already comes as bytes per second from monitor-traffic
        const rxRate = data.rx_bytes || 0;
        const txRate = data.tx_bytes || 0;

        trafficData.labels.push(now);
        trafficData.rx.push(rxRate);
        trafficData.tx.push(txRate);

        if (trafficData.labels.length > 20) {
          trafficData.labels.shift();
          trafficData.rx.shift();
          trafficData.tx.shift();
        }

        trafficChart.data.labels = trafficData.labels;
        trafficChart.data.datasets[0].data = trafficData.rx;
        trafficChart.data.datasets[1].data = trafficData.tx;
        trafficChart.update();
      }
    });
  }

  function updateProgressBar(selector, value) {
    const $bar = $(selector);
    $bar
      .css("width", value + "%")
      .attr("aria-valuenow", value)
      .text(value + "%");

    $bar.removeClass(
      "bg-gradient-success bg-gradient-warning bg-gradient-danger"
    );
    if (value < 50) {
      $bar.addClass("bg-gradient-success");
    } else if (value < 80) {
      $bar.addClass("bg-gradient-warning");
    } else {
      $bar.addClass("bg-gradient-danger");
    }
  }

  function formatBytes(bytes) {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  }

  function formatBits(bits) {
    if (bits === 0) return "0 bps";
    const k = 1000;
    const sizes = ["bps", "Kbps", "Mbps", "Gbps"];
    const i = Math.floor(Math.log(bits) / Math.log(k));
    return parseFloat((bits / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  }

  function formatUptime(uptime) {
    if (!uptime) return "Unknown";

    // Parse RouterOS uptime format like "3w1d2h34m56s"
    const regex = /(\d+)([wdhms])/g;
    let match;
    let result = [];

    while ((match = regex.exec(uptime)) !== null) {
      const value = parseInt(match[1]);
      const unit = match[2];

      switch (unit) {
        case "w":
          result.push(value + " week" + (value > 1 ? "s" : ""));
          break;
        case "d":
          result.push(value + " day" + (value > 1 ? "s" : ""));
          break;
        case "h":
          result.push(value + " hour" + (value > 1 ? "s" : ""));
          break;
        case "m":
          result.push(value + " minute" + (value > 1 ? "s" : ""));
          break;
        case "s":
          result.push(value + " second" + (value > 1 ? "s" : ""));
          break;
      }
    }

    return result.length > 0 ? result.join(", ") : uptime;
  }

  $(".income-amount").text("0.00 BDT");

  // Check for existing connection with delay
  setTimeout(() => {
    if (window.nasManager && window.nasManager.getConnectionStatus()) {
      console.log("Dashboard: Found existing connection");
      updateConnectionStatus();
      loadDashboardData();
      loadInterfaces();
    } else {
      updateConnectionStatus();
      clearDashboardData();
    }
  }, 200);

  // Interface selector event
  $(document).on("change", "#interface-selector", function () {
    trafficData = { rx: [], tx: [], labels: [] };
    if (trafficChart) {
      updateTrafficChart();
    }
  });
});
