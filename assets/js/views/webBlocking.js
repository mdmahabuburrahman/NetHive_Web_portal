class WebBlockingManager {
  constructor() {
    this.dataTable = null;
    this.currentData = {
      custom_domains: [],
      active_category: null,
    };
  }

  async loadBlockedDomains() {
    try {
      const response = await fetch(
        `api/webBlocking_operations.php?action=get_blocked_domains&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.currentData = {
          custom_domains: result.custom_domains || [],
          active_category: result.active_category || null,
        };
        this.renderCustomDomains();
        this.updateCategoryStates();
      } else {
        this.showError("Failed to load blocked domains: " + result.error);
      }
    } catch (error) {
      console.error("Error loading blocked domains:", error);
      this.showError("Failed to load blocked domains");
    }
  }

  async loadDnsRules() {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      this.renderDnsRules([]);
      return;
    }

    try {
      const response = await fetch(
        `api/webBlocking_operations.php?action=get_dns_rules&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderDnsRules(result.rules || []);
      } else {
        this.showError("Failed to load DNS rules: " + result.error);
        this.renderDnsRules([]);
      }
    } catch (error) {
      console.error("Error loading DNS rules:", error);
      this.renderDnsRules([]);
    }
  }

  async addCustomDomain() {
    // Check if NAS is connected
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      this.showError("Please connect to a NAS device first");
      return;
    }

    const domainInput = document.getElementById("customDomain");
    const domain = domainInput.value.trim();

    if (!domain) {
      this.showError("Please enter a domain name");
      return;
    }

    if (!this.isValidDomain(domain)) {
      this.showError("Please enter a valid domain name");
      return;
    }

    try {
      const response = await fetch("api/webBlocking_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "add_custom_domain",
          nas_id: window.nasManager.getCurrentNasId(),
          domain: domain,
        }),
      });

      const result = await response.json();

      if (result.success) {
        domainInput.value = "";
        this.showSuccess(
          result.message || `Domain "${domain}" added successfully`
        );
        await this.loadBlockedDomains();
        await this.loadDnsRules();
      } else {
        this.showError("Failed to add domain: " + result.error);
      }
    } catch (error) {
      console.error("Error adding domain:", error);
      this.showError("Failed to add domain");
    }
  }

  async removeCustomDomain(domain) {
    // Check if NAS is connected
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      this.showError("Please connect to a NAS device first");
      return;
    }

    // Use modal instead of confirm
    if (
      !(await this.showConfirmModal(
        "Remove Domain",
        `Are you sure you want to remove "${domain}" from blocked domains?`
      ))
    ) {
      return;
    }

    try {
      const response = await fetch("api/webBlocking_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "remove_custom_domain",
          nas_id: window.nasManager.getCurrentNasId(),
          domain: domain,
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(
          result.message || `Domain "${domain}" removed successfully`
        );
        await this.loadBlockedDomains();
        await this.loadDnsRules();
      } else {
        this.showError("Failed to remove domain: " + result.error);
      }
    } catch (error) {
      console.error("Error removing domain:", error);
      this.showError("Failed to remove domain");
    }
  }

  async toggleCategory(category, enabled) {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      this.showError("Please connect to a NAS device first");
      return;
    }

    const categoryName = this.getCategoryDisplayName(category);
    const checkbox = $(`#category-${category}`);
    const label = $(`label[for="category-${category}"]`);

    // Show loading state
    checkbox.prop("disabled", true);
    label.addClass("loading-category");

    // Add loading text to the category name
    const textNode = label.find("i").next().get(0);
    const originalText = textNode ? textNode.nodeValue : categoryName;
    if (textNode) {
      textNode.nodeValue = ` ${categoryName} ${
        enabled ? "(Adding adlist...)" : "(Removing...)"
      }`;
    }

    if (enabled) {
      this.showInfo(`Adding ${categoryName} adlist to router...`);
    } else {
      this.showInfo(`Removing ${categoryName} adlist from router...`);
    }

    try {
      const response = await fetch("api/webBlocking_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "toggle_category",
          nas_id: window.nasManager.getCurrentNasId(),
          category: category,
          enabled: enabled,
        }),
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const result = await response.json();

      if (result.success) {
        this.showSuccess(
          result.message ||
            `${categoryName} ${enabled ? "enabled" : "disabled"} successfully`
        );
        await this.loadBlockedDomains();
        await this.loadDnsRules();
      } else {
        // Revert checkbox state on error
        checkbox.prop("checked", !enabled);
        this.showError("Failed to toggle category: " + result.error);
      }
    } catch (error) {
      console.error("Error toggling category:", error);
      // Revert checkbox state on error
      checkbox.prop("checked", !enabled);

      let errorMessage = "Failed to toggle category: ";
      if (error.message.includes("HTTP 500")) {
        errorMessage += "Server error occurred. Please check server logs.";
      } else if (error.message.includes("JSON")) {
        errorMessage += "Invalid server response. Please try again.";
      } else {
        errorMessage += error.message || "Network error occurred";
      }

      this.showError(errorMessage);
    } finally {
      // Remove loading state
      checkbox.prop("disabled", false);
      label.removeClass("loading-category");

      // Restore original category name
      const iconElement = label.find("i");
      const textNode = iconElement.length > 0 ? iconElement.next().get(0) : null;
      if (textNode) {
        textNode.nodeValue = ` ${categoryName}`;
      }
    }
  }

  async clearAllBlocks() {
    // Check if NAS is connected
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      this.showError("Please connect to a NAS device first");
      return;
    }

    // Use modal instead of confirm
    if (
      !(await this.showConfirmModal(
        "Clear All Blocks",
        "Are you sure you want to clear all blocking rules? This will remove all custom domains and disable all categories."
      ))
    ) {
      return;
    }

    try {
      const response = await fetch("api/webBlocking_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "clear_all_blocks",
          nas_id: window.nasManager.getCurrentNasId(),
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(
          result.message || "All blocking rules cleared successfully"
        );
        // Reset category states
        $(".category-toggle").prop("checked", false).prop("disabled", false);
        $(".form-check").removeClass("bg-success bg-opacity-25 rounded p-2");
        // Reload data
        await this.loadBlockedDomains();
        await this.loadDnsRules();
      } else {
        this.showError("Failed to clear blocks: " + result.error);
      }
    } catch (error) {
      console.error("Error clearing blocks:", error);
      this.showError("Failed to clear blocks");
    }
  }

  async refreshRules() {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      this.showError("Please connect to a NAS device first");
      return;
    }
    await this.loadDnsRules();
    this.showSuccess("DNS rules refreshed");
  }

  async checkDnsStatus() {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      return;
    }

    try {
      const response = await fetch(
        `api/webBlocking_operations.php?action=check_dns_status&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.updateDnsButton(result.configured);
      }
    } catch (error) {
      console.error("Error checking DNS status:", error);
    }
  }

  updateDnsButton(configured) {
    const btn = $("#setupDnsBtn");
    if (configured) {
      btn
        .removeClass("btn-success")
        .addClass("btn-danger")
        .html('<i class="fa fa-times me-2"></i>Remove DNS Setup');
    } else {
      btn
        .removeClass("btn-danger")
        .addClass("btn-success")
        .html('<i class="fa fa-cog me-2"></i>Setup DNS');
    }
  }

  async setupDNSRedirect() {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      this.showError("Please connect to a NAS device first");
      return;
    }

    const btn = $("#setupDnsBtn");
    const isRemove = btn.hasClass("btn-danger");

    const action = isRemove ? "remove_dns_setup" : "setup_dns_redirect";
    const title = isRemove
      ? "Remove DNS Configuration"
      : "Setup DNS Configuration";
    const message = isRemove
      ? "This will remove DNS blocking configuration from your router. Continue?"
      : "This will configure your router to block external DNS requests and enable DNS blocking. Continue?";

    const confirmed = await this.showConfirmModal(title, message);
    if (!confirmed) {
      return;
    }

    this.showLoadingOverlay(
      isRemove ? "Removing DNS setup..." : "Configuring DNS setup..."
    );

    try {
      const response = await fetch("api/webBlocking_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: action,
          nas_id: window.nasManager.getCurrentNasId(),
        }),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        await this.checkDnsStatus();
      } else {
        this.showError(
          "Failed to " +
            (isRemove ? "remove" : "setup") +
            " DNS: " +
            result.error
        );
      }
    } catch (error) {
      console.error("Error with DNS operation:", error);
      this.showError(
        "Failed to " +
          (isRemove ? "remove" : "setup") +
          " DNS: " +
          error.message
      );
    } finally {
      this.hideLoadingOverlay();
    }
  }

  renderCustomDomains() {
    const tbody = $("#customDomainsTable tbody");
    tbody.empty();

    if (
      !this.currentData.custom_domains ||
      this.currentData.custom_domains.length === 0
    ) {
      tbody.html(
        '<tr><td colspan="3" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No custom domains blocked</td></tr>'
      );
      return;
    }

    this.currentData.custom_domains.forEach((domain) => {
      const row = `
        <tr>
          <td>
            <button class="btn btn-sm btn-danger delete-domain-btn" data-domain="${domain}">
              <i class="fa fa-trash"></i>
            </button>
          </td>
          <td>${domain}</td>
          <td><span class="badge bg-danger">Blocked</span></td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  renderDnsRules(rules) {
    // Destroy existing DataTable first
    if ($.fn.DataTable.isDataTable("#dnsRulesTable")) {
      $("#dnsRulesTable").DataTable().destroy();
    }

    const tbody = $("#dnsRulesTable tbody");
    tbody.empty();

    if (!rules || rules.length === 0) {
      tbody.html(
        '<tr><td colspan="4" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No WebBlocking rules active</td></tr>'
      );
      return;
    }

    rules.forEach((rule) => {
      const categoryBadge = this.getCategoryBadge(rule.category);
      const statusBadge =
        rule.status === "Active"
          ? '<span class="badge bg-success">Active</span>'
          : '<span class="badge bg-danger">Blocked</span>';

      const row = `
        <tr>
          <td>${rule.name || "-"}</td>
          <td>${rule.address || "-"}</td>
          <td>${categoryBadge}</td>
          <td>${statusBadge}</td>
        </tr>
      `;
      tbody.append(row);
    });

    this.initDataTable("#dnsRulesTable");
  }

  updateCategoryStates() {
    const activeCategory = this.currentData.active_category;

    $(".category-toggle").each(function () {
      const category = $(this).data("category");
      const isEnabled = activeCategory === category;
      $(this).prop("checked", isEnabled);

      // Add green background for active category
      const formCheck = $(this).closest(".form-check");
      if (isEnabled) {
        formCheck.addClass("bg-success bg-opacity-25 rounded p-2");
      } else {
        formCheck.removeClass("bg-success bg-opacity-25 rounded p-2");
      }

      // Disable other checkboxes if one is active
      if (activeCategory && !isEnabled) {
        $(this).prop("disabled", true);
      } else if (!activeCategory) {
        $(this).prop("disabled", false);
      }
    });
  }

  getCategoryBadge(category) {
    const badges = {
      Custom: '<span class="badge bg-primary">Custom Domain</span>',
      "Category List": '<span class="badge bg-success">Category List</span>',
    };

    return (
      badges[category] ||
      `<span class="badge bg-light text-dark">${category}</span>`
    );
  }

  getCategoryDisplayName(category) {
    const names = {
      Adware_Malware: "Adware & Malware",
      Adware_Malware_Fakenews: "Adware & Malware + Fake News",
      Fakenews: "Fake News Only",
      Adware_Malware_Gambling: "Adware & Malware + Gambling",
      Gambling: "Gambling Only",
      Adware_Malware_Porn: "Adware & Malware + Adult Content",
      Porn: "Adult Content Only",
      Adware_Malware_Social: "Adware & Malware + Social Media",
      Social: "Social Media Only",
      Adware_Malware_Fakenews_Gambling_Porn_Social: "All Categories",
    };

    return names[category] || category;
  }

  isValidDomain(domain) {
    const domainRegex =
      /^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](?:\.[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9])*$/;
    return domainRegex.test(domain);
  }

  initDataTable(selector) {
    if ($.fn.DataTable.isDataTable(selector)) {
      $(selector).DataTable().destroy();
    }

    $(selector).DataTable({
      responsive: true,
      pageLength: 25,
      order: [[1, "asc"]],
      language: {
        emptyTable: "No data available",
        zeroRecords: "No matching records found",
      },
      dom:
        '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
        '<"row"<"col-sm-12"tr>>' +
        '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      drawCallback: function () {
        // Ensure buttons work after table redraw
        $(selector + " .btn")
          .off("click")
          .on("click", function (e) {
            e.preventDefault();
            const action = $(this).attr("onclick");
            if (action) {
              eval(action);
            }
          });
      },
    });
  }

  destroyDataTables() {
    if ($.fn.DataTable.isDataTable("#dnsRulesTable")) {
      $("#dnsRulesTable").DataTable().destroy();
    }
  }

  clearAllData() {
    $("#customDomainsTable tbody").html(
      '<tr><td colspan="3" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to manage custom domains.</td></tr>'
    );
    $("#dnsRulesTable tbody").html(
      '<tr><td colspan="4" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to view DNS rules.</td></tr>'
    );

    // Reset category toggles
    $(".category-toggle").prop("checked", false).prop("disabled", false);
    $(".form-check").removeClass("bg-success bg-opacity-25 rounded p-2");
    $("#categoryStatus").html(
      '<small class="text-muted"><i class="fa fa-info-circle me-1"></i>Connect to a NAS device to manage categories</small>'
    );

    // Reset DNS button
    this.updateDnsButton(false);
  }

  async handleNasConnection() {
    await this.loadBlockedDomains();
    await this.loadDnsRules();
    await this.checkDnsStatus();
  }

  showSuccess(message) {
    if (window.Toast) {
      window.Toast.success(message);
    } else {
      console.log("Success:", message);
    }
  }

  showError(message) {
    if (window.Toast) {
      window.Toast.error(message);
    } else {
      console.error("Error:", message);
    }
  }

  showInfo(message) {
    if (window.Toast) {
      window.Toast.info(message);
    } else {
      console.log("Info:", message);
    }
  }

  async showConfirmModal(title, message) {
    return new Promise((resolve) => {
      $(".modal").remove();
      $(".modal-backdrop").remove();
      $("body").removeClass("modal-open").css("padding-right", "");

      const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">${title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p>${message}</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmActionBtn">Confirm</button>
              </div>
            </div>
          </div>
        </div>
      `;

      $("body").append(modalHtml);

      const modal = new bootstrap.Modal(
        document.getElementById("confirmModal")
      );
      let resolved = false;

      $("#confirmActionBtn").on("click", function () {
        if (!resolved) {
          resolved = true;
          modal.hide();
          setTimeout(() => resolve(true), 100);
        }
      });

      $("#confirmModal").on("hidden.bs.modal", function () {
        if (!resolved) {
          resolved = true;
          resolve(false);
        }
        $(this).remove();
        $(".modal-backdrop").remove();
        $("body").removeClass("modal-open").css("padding-right", "");
      });

      modal.show();
    });
  }

  async showInputModal(title, message, placeholder = "") {
    return new Promise((resolve) => {
      $(".modal").remove();
      $(".modal-backdrop").remove();
      $("body").removeClass("modal-open").css("padding-right", "");

      const modalHtml = `
        <div class="modal fade" id="inputModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">${title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p>${message}</p>
                <input type="text" class="form-control" id="inputValue" placeholder="${placeholder}" value="${placeholder}">
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="inputActionBtn">OK</button>
              </div>
            </div>
          </div>
        </div>
      `;

      $("body").append(modalHtml);

      const modal = new bootstrap.Modal(document.getElementById("inputModal"));
      let resolved = false;

      $("#inputActionBtn").on("click", function () {
        if (!resolved) {
          resolved = true;
          const value = $("#inputValue").val().trim();
          modal.hide();
          setTimeout(() => resolve(value), 100);
        }
      });

      $("#inputValue").on("keypress", function (e) {
        if (e.which === 13) {
          $("#inputActionBtn").click();
        }
      });

      $("#inputModal").on("hidden.bs.modal", function () {
        if (!resolved) {
          resolved = true;
          resolve(null);
        }
        $(this).remove();
        $(".modal-backdrop").remove();
        $("body").removeClass("modal-open").css("padding-right", "");
      });

      modal.show();
      setTimeout(() => $("#inputValue").focus().select(), 500);
    });
  }

  showLoadingOverlay(message = "Processing...") {
    const overlayHtml = `
      <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <div class="loading-text mt-3">${message}</div>
        </div>
      </div>
    `;

    $("#loadingOverlay").remove();
    $("body").append(overlayHtml);
  }

  hideLoadingOverlay() {
    $("#loadingOverlay").remove();
  }
}

$(document).ready(function () {
  setTimeout(() => {
    console.log("Creating WebBlockingManager instance...");
    window.webBlockingManager = new WebBlockingManager();

    // Handle Enter key in domain input
    $("#customDomain").on("keypress", function (e) {
      if (e.which === 13) {
        webBlockingManager.addCustomDomain();
      }
    });

    // Handle delete domain buttons with event delegation
    $(document).on("click", ".delete-domain-btn", function () {
      const domain = $(this).data("domain");
      webBlockingManager.removeCustomDomain(domain);
    });

    // Handle category toggle switches
    $(document).on("change", ".category-toggle", function () {
      const category = $(this).data("category");
      const enabled = $(this).is(":checked");
      const categoryName = webBlockingManager.getCategoryDisplayName(category);

      if (enabled) {
        // Check if another category is already active
        if (
          webBlockingManager.currentData.active_category &&
          webBlockingManager.currentData.active_category !== category
        ) {
          $(this).prop("checked", false);
          webBlockingManager.showError(
            "Please disable the current category first before enabling another one"
          );
          return;
        }

        webBlockingManager
          .showConfirmModal(
            `Enable ${categoryName}`,
            `This will add the ${categoryName} adlist URL to your MikroTik router. Only one category can be active at a time. Continue?`
          )
          .then((confirmed) => {
            if (confirmed) {
              webBlockingManager.toggleCategory(category, enabled);
            } else {
              $(this).prop("checked", false);
            }
          });
      } else {
        webBlockingManager.toggleCategory(category, enabled);
      }
    });

    $(document).on("nas:connected", function (event, nasId, nasName) {
      console.log("NAS connected in web blocking page:", nasId, nasName);
      updateConnectionStatus(true);
      if (window.webBlockingManager) {
        window.webBlockingManager.handleNasConnection();
      }
    });

    $(document).on("nas:disconnected", function () {
      console.log("NAS disconnected in web blocking page");
      updateConnectionStatus(false);
      if (window.webBlockingManager) {
        window.webBlockingManager.destroyDataTables();
        window.webBlockingManager.clearAllData();
      }
    });

    setTimeout(() => {
      if (window.nasManager && window.nasManager.getConnectionStatus()) {
        console.log("Web blocking page: Found existing connection");
        updateConnectionStatus(true);
        if (window.webBlockingManager) {
          window.webBlockingManager.handleNasConnection();
        }
      } else {
        updateConnectionStatus(false);
      }
    }, 200);
  }, 500);
});

function updateConnectionStatus(connected) {
  let statusHtml;
  if (connected) {
    statusHtml = `<span class="badge bg-success"><i class="fa fa-check"></i> Connected</span>`;
    // Enable interfaces
    $("#customDomain").prop("disabled", false);
    $(".card .btn").prop("disabled", false);
    $("#setupDnsBtn").prop("disabled", false);
    // Category toggles will be handled by updateCategoryStates()
    $(".card-body").removeClass("nas-disconnected");
    $("#categoryStatus")
      .removeClass("processing")
      .html(
        '<small class="text-success"><i class="fa fa-check me-1"></i>Categories available - Click to enable/disable</small>'
      );
  } else {
    statusHtml = `<span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>`;
    // Disable interfaces
    $("#customDomain").prop("disabled", true);
    $(".card .btn:not(.btn-close)").prop("disabled", true);
    $("#setupDnsBtn").prop("disabled", true);
    $(".category-toggle").prop("disabled", true).prop("checked", false);
    $(".card-body").addClass("nas-disconnected");
    $(".form-check-label").removeClass("loading-category");
    $("#categoryStatus")
      .removeClass("processing")
      .html(
        '<small class="text-muted"><i class="fa fa-info-circle me-1"></i>Connect to a NAS device to manage categories</small>'
      );
  }

  $(".connection-status").html(statusHtml);
}
