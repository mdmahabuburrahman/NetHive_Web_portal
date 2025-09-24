class UserLogsManager {
  constructor() {
    this.currentPage = 1;
    this.pageSize = 100;
    this.totalPages = 0;
    this.nas = '';
    this.date = '';
    this.searchTerm = '';
    this.searchTimeout = null;
    this.init();
  }

  init() {
    const urlParams = new URLSearchParams(window.location.search);
    this.nas = urlParams.get('nas') || '';
    this.date = urlParams.get('date') || '';
    
    if (this.nas && this.date && $('#logTable').length) {
      this.loadLogs();
    }

    this.bindEvents();
  }

  bindEvents() {
    $('#pageSize').on('change', () => {
      this.pageSize = parseInt($('#pageSize').val());
      this.currentPage = 1;
      this.loadLogs();
    });

    $('#searchLogs').on('input', (e) => {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.searchTerm = e.target.value;
        this.currentPage = 1;
        this.loadLogs();
      }, 300);
    });
  }

  async loadLogs(page = 1) {
    if (!this.nas || !this.date) return;
    
    this.currentPage = page;
    this.showLoading();

    try {
      const params = new URLSearchParams({
        nas: this.nas,
        date: this.date,
        page: page,
        limit: this.pageSize,
        search: this.searchTerm
      });
      params.delete('action');
      params.append('action', 'get_logs');

      const response = await fetch(`api/userlogs_operations.php?action=get_logs&${params}`);
      const result = await response.json();

      if (result.success) {
        this.renderTable(result.data);
        this.renderPagination(result.total_pages, result.page, result.total);
        this.updateStats(result.total, result.file_size);
      } else {
        this.showError(result.error);
      }
    } catch (error) {
      this.showError('Failed to load logs: ' + error.message);
    }
  }

  showLoading() {
    $('#loadingSpinner').show();
    $('#logTableContainer').hide();
    $('#pagination').hide();
  }

  renderTable(logs) {
    const tbody = $('#logTableBody');
    tbody.empty();

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#logTable')) {
      $('#logTable').DataTable().destroy();
    }

    if (logs.length === 0) {
      tbody.append('<tr><td colspan="9" class="text-center text-muted">No logs found</td></tr>');
    } else {
      logs.forEach(log => {
        tbody.append(`
          <tr>
            <td>${this.escapeHtml(log.time)}</td>
            <td>${this.escapeHtml(log.nas_name)}</td>
            <td>${this.escapeHtml(log.user)}</td>
            <td>${this.escapeHtml(log.mac)}</td>
            <td>${this.escapeHtml(log.src_ip)}</td>
            <td>${this.escapeHtml(log.src_port)}</td>
            <td>${this.escapeHtml(log.dst_ip)}</td>
            <td>${this.escapeHtml(log.dst_port)}</td>
            <td>${this.escapeHtml(log.protocol)}</td>
          </tr>
        `);
      });
      
      // Initialize DataTable only when we have data
      $('#logTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        order: [[0, 'desc']],
        columnDefs: [
          { orderable: true, targets: '_all' }
        ],
        language: {
          emptyTable: 'No logs found'
        }
      });
    }

    $('#loadingSpinner').hide();
    $('#logTableContainer').show();
  }

  renderPagination(totalPages, currentPage, totalEntries) {
    this.totalPages = totalPages;
    const pagination = $('#paginationControls');
    const info = $('#paginationInfo');
    
    pagination.empty();
    
    if (totalPages <= 1) {
      $('#pagination').hide();
      return;
    }

    const start = (currentPage - 1) * this.pageSize + 1;
    const end = Math.min(currentPage * this.pageSize, totalEntries);
    info.text(`Showing ${start}-${end} of ${totalEntries.toLocaleString()} entries`);

    // Previous button
    pagination.append(`
      <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${currentPage - 1}">‹</a>
      </li>
    `);

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
      pagination.append('<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>');
      if (startPage > 2) pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
    }

    for (let i = startPage; i <= endPage; i++) {
      pagination.append(`
        <li class="page-item ${i === currentPage ? 'active' : ''}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
      pagination.append(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
    }

    // Next button
    pagination.append(`
      <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${currentPage + 1}">›</a>
      </li>
    `);

    // Bind click events
    pagination.find('a').on('click', (e) => {
      e.preventDefault();
      const page = parseInt($(e.target).data('page'));
      if (page && page !== currentPage && page >= 1 && page <= totalPages) {
        this.loadLogs(page);
      }
    });

    $('#pagination').show();
  }

  updateStats(total, fileSize) {
    const fileSizeMB = (fileSize / 1024 / 1024).toFixed(2);
    $('#totalEntries').text(`${total.toLocaleString()} entries (${fileSizeMB} MB)`);
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  showError(message) {
    $('#logTableBody').html(`<tr><td colspan="9" class="text-center text-danger">${message}</td></tr>`);
    $('#loadingSpinner').hide();
    $('#logTableContainer').show();
    $('#pagination').hide();
  }
}

$(document).ready(function () {
  window.userLogsManager = new UserLogsManager();

  // Check logging status on connected NAS
  function checkLoggingStatus() {
    const nasId = window.nasManager ? window.nasManager.getCurrentNasId() : null;
    const isConnected = window.nasManager ? window.nasManager.getConnectionStatus() : false;
    
    if (!nasId || !isConnected) {
      $('#configureLogging').prop('disabled', true)
        .removeClass('btn-success btn-primary')
        .addClass('btn-outline-secondary');
      return;
    }
    
    $.ajax({
      url: 'api/userlogs_operations.php',
      method: 'POST',
      data: {
        action: 'checkLoggingStatus',
        nasId: nasId
      },
      success: function(response) {
        if (response.success) {
          if (response.configured) {
            $('#configureLogging').prop('disabled', true)
              .removeClass('btn-outline-secondary btn-warning')
              .addClass('btn-success');
          } else {
            $('#configureLogging').prop('disabled', false)
              .removeClass('btn-outline-secondary btn-success')
              .addClass('btn-primary');
          }
        } else {
          $('#configureLogging').prop('disabled', true)
            .removeClass('btn-success btn-primary')
            .addClass('btn-outline-secondary');
        }
      },
      error: function() {
        $('#configureLogging').prop('disabled', true)
          .removeClass('btn-success btn-primary')
          .addClass('btn-outline-secondary');
      }
    });
  }

  // Configure logging
  $('#configureLogging').click(function() {
    const nasId = window.nasManager ? window.nasManager.getCurrentNasId() : null;
    
    if (!nasId) {
      showResultModal('Error', 'Please connect to a NAS device first', 'danger');
      return;
    }
    
    // Show confirmation modal
    $('#configureModal').modal('show');
  });
  
  // Confirm configuration
  $('#confirmConfigure').click(function() {
    const nasId = window.nasManager ? window.nasManager.getCurrentNasId() : null;
    
    $('#configureModal').modal('hide');
    
    $.ajax({
      url: 'api/userlogs_operations.php',
      method: 'POST',
      data: {
        action: 'configureLogging',
        nasId: nasId
      },
      success: function(response) {
        if (response.success) {
          showResultModal('Success', response.message, 'success');
          checkLoggingStatus(); // Recheck status
        } else {
          showResultModal('Error', response.message, 'danger');
        }
      },
      error: function() {
        showResultModal('Error', 'Error configuring logging', 'danger');
      }
    });
  });
  
  // Show result modal
  function showResultModal(title, message, type) {
    $('#resultTitle').text(title);
    $('#resultMessage').text(message);
    $('#resultModal').modal('show');
  }

  // Listen for NAS connection events
  $(document).on("nas:connected", function (event, nasId) {
    console.log("NAS connected in user logs page:", nasId);
    updateConnectionStatus(true);
    autoSelectNasDevice(nasId);
    checkLoggingStatus(); // Check logging status when NAS connects
  });

  $(document).on("nas:disconnected", function () {
    console.log("NAS disconnected in user logs page");
    updateConnectionStatus(false);
    // Clear user logs data here when implemented
  });

  // Check for existing connection with delay
  setTimeout(() => {
    if (window.nasManager && window.nasManager.getConnectionStatus()) {
      console.log("User logs page: Found existing connection");
      updateConnectionStatus(true);
      const currentNasId = window.nasManager.getCurrentNasId();
      autoSelectNasDevice(currentNasId); // Auto-select the connected NAS
      checkLoggingStatus();
    } else {
      updateConnectionStatus(false);
    }
  }, 1000);
});

// Auto-select NAS device based on connected NAS ID
function autoSelectNasDevice(nasId) {
  if (!nasId || !window.nasDetailsForLogs) {
    return;
  }
  
  const connectedNas = window.nasDetailsForLogs.find(nas => nas.id === nasId);
  if (connectedNas) {
    const nasName = connectedNas.nas_name;
    console.log('Auto-selecting NAS device:', nasName);
    
    $('#nas option').each(function() {
      if ($(this).val() === nasName) {
        $('#nas').val(nasName);
        return false;
      }
    });
  }
}

// Update connection status in breadcrumb
function updateConnectionStatus(connected) {
  let statusHtml;
  if (connected) {
    statusHtml = `<span class="badge bg-success"><i class="fa fa-check"></i> Connected</span>`;
  } else {
    statusHtml = `<span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>`;
  }

  $(".connection-status").html(statusHtml);
}


