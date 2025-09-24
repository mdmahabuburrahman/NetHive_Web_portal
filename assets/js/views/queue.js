/**
 * Queue Management JavaScript - WinBox Style
 * Fixed initialization and drag & drop functionality
 */

class QueueManager {
  constructor() {
    this.sortableInstances = {};
    this.simpleQueues = [];
    this.editingItem = null;
    this.initAttempts = 0;
    this.maxInitAttempts = 10;

    console.log("QueueManager constructor called");
    this.init();
  }

  init() {
    console.log("Initializing Queue Manager...");

    // Wait for DOM to be fully ready
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => {
        console.log("DOM Content Loaded");
        this.delayedInit();
      });
    } else {
      console.log("DOM already ready");
      this.delayedInit();
    }
  }

  delayedInit() {
    // Add a small delay to ensure all scripts are loaded
    setTimeout(() => {
      console.log("Starting delayed initialization...");
      this.bindEvents();
      this.showConnectionAlert();
      this.waitForSortableJS();
    }, 100);
  }

  waitForSortableJS() {
    console.log("Waiting for SortableJS...", "Attempt:", this.initAttempts + 1);

    if (typeof Sortable !== "undefined") {
      console.log("SortableJS is available");
      this.initializeSortables();
    } else {
      this.initAttempts++;
      if (this.initAttempts < this.maxInitAttempts) {
        console.log("SortableJS not ready, retrying in 200ms...");
        setTimeout(() => this.waitForSortableJS(), 200);
      } else {
        console.error(
          "SortableJS failed to load after",
          this.maxInitAttempts,
          "attempts"
        );
      }
    }
  }

  initializeSortables() {
    console.log("Initializing sortables...");
    // Don't initialize here, wait for data to be loaded first
    // Sortables will be initialized after data is loaded in respective load functions
  }

  bindEvents() {
    console.log("Binding events...");

    // Listen for NAS connection events
    $(document).on("nas:connected", (e, data) => {
      console.log("NAS connected:", data.nasName);
      this.loadSimpleQueues();
    });
    
    // Load active clients when modal is shown
    $("#addSimpleQueueModal").on("show.bs.modal", () => {
      this.loadActiveClients();
    });

    // Form submissions
    $("#simpleQueueForm").on("submit", (e) => this.handleSimpleQueueSubmit(e));
    $("#editForm").on("submit", (e) => this.handleEditSubmit(e));
    
    // Target selection handler
    $(document).on("change", "#targetSelect", function() {
      const manualInput = $("input[name='target_manual']");
      if ($(this).val() === "manual") {
        manualInput.show().focus();
      } else {
        manualInput.hide().val("");
      }
    });

    // Modal reset events
    $(".modal").on("hidden.bs.modal", function () {
      $(this).find("form")[0]?.reset();
    });

    console.log("Events bound successfully");
  }

  async loadSimpleQueues() {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) return;

    console.log("Loading simple queues...");
    const container = $("#sortable-simple-queues");
    container.html(`
            <div class="text-center p-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Loading simple queues...
            </div>
        `);

    try {
      const response = await fetch(
        `api/queue_operations.php?action=get_simple_queues&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.simpleQueues = result.data;
        console.log("Loaded simple queues:", this.simpleQueues.length);
        this.renderSimpleQueues();

        // Initialize sortable after a short delay to ensure DOM is ready
        setTimeout(() => {
          this.initSimpleQueueSortable();
        }, 300);
      } else {
        container.html(`
                    <div class="text-center p-4 text-danger">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        Error: ${result.error}
                    </div>
                `);
      }
    } catch (error) {
      console.error("Error loading simple queues:", error);
      container.html(`
                <div class="text-center p-4 text-danger">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    Failed to load simple queues
                </div>
            `);
    }
  }

  renderSimpleQueues() {
    const container = $("#sortable-simple-queues");
    if (this.simpleQueues.length === 0) {
      container.html(`
                <div class="text-center p-4 text-muted">
                    <i class="fa fa-info-circle me-2"></i>
                    No simple queues found. Create your first queue using the "Add Simple Queue" button.
                </div>
            `);
      return;
    }

    const items = this.simpleQueues
      .map((queue, index) => this.createSimpleQueueItem(queue, index))
      .join("");
    container.html(items);
    console.log("Rendered", this.simpleQueues.length, "simple queues");
  }

  createSimpleQueueItem(queue, index) {
    const maxLimits = queue["max-limit"]
      ? queue["max-limit"].split("/")
      : ["-", "-"];
    const burstLimits = queue["burst-limit"]
      ? queue["burst-limit"].split("/")
      : ["-", "-"];
    const priority = parseInt(queue.priority || 8);
    const priorityClass =
      priority <= 2
        ? "priority-high"
        : priority <= 5
        ? "priority-medium"
        : "priority-low";

    return `
            <div class="queue-item simple-queue-item draggable-item ${priorityClass}" data-id="${
      queue[".id"]
    }" data-type="simple" data-index="${index}">
                <div class="queue-item-content">
                    <div class="queue-item-drag-handle" title="Drag to reorder">
                        <i class="fa fa-grip-vertical"></i>
                    </div>
                    <div class="queue-item-icon"></div>
                    <div class="queue-item-name">${
                      queue.name || "Unnamed"
                    }</div>
                    <div class="queue-item-details">
                        <div class="queue-detail-inline">
                            <div class="queue-detail-label">Target</div>
                            <div class="queue-detail-value">${
                              queue.target || "Any"
                            }</div>
                        </div>
                        <div class="queue-detail-inline">
                            <div class="queue-detail-label">Upload</div>
                            <div class="queue-detail-value upload-indicator">${
                              this.formatBandwidth(maxLimits[0]) || "-"
                            }</div>
                        </div>
                        <div class="queue-detail-inline">
                            <div class="queue-detail-label">Download</div>
                            <div class="queue-detail-value download-indicator">${
                              this.formatBandwidth(maxLimits[1]) || "-"
                            }</div>
                        </div>
                        <div class="queue-detail-inline">
                            <div class="queue-detail-label">Priority</div>
                            <div class="queue-detail-value">${priority}</div>
                        </div>
                        <div class="queue-detail-inline">
                            <div class="queue-detail-label">Status</div>
                            <div class="queue-detail-value">
                                <span class="badge ${
                                  queue.disabled === "true"
                                    ? "status-disabled"
                                    : "status-enabled"
                                }">
                                    ${
                                      queue.disabled === "true"
                                        ? "Disabled"
                                        : "Enabled"
                                    }
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="queue-item-actions">
                        <button class="btn btn-sm" onclick="queueManager.editSimpleQueue('${
                          queue[".id"]
                        }')" title="Edit">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm" onclick="queueManager.toggleQueue('simple', '${
                          queue[".id"]
                        }')" title="Toggle Status">
                            <i class="fa ${
                              queue.disabled === "true" ? "fa-play" : "fa-pause"
                            }"></i>
                        </button>
                        <button class="btn btn-sm" onclick="queueManager.deleteSimpleQueue('${
                          queue[".id"]
                        }')" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
  }

  initSimpleQueueSortable() {
    console.log("Initializing simple queue sortable...");

    // Check if SortableJS is available
    if (typeof Sortable === "undefined") {
      console.error("SortableJS library not available");
      return;
    }

    // Destroy existing sortable instance
    if (this.sortableInstances.simpleQueues) {
      this.sortableInstances.simpleQueues.destroy();
      console.log("Destroyed existing simple queue sortable");
    }

    const element = document.getElementById("sortable-simple-queues");
    if (!element) {
      console.error("sortable-simple-queues element not found");
      return;
    }

    // Check if there are draggable items
    const items = element.querySelectorAll(".draggable-item");
    if (items.length === 0) {
      console.log("No draggable items found in simple queues");
      return;
    }

    console.log(
      "Creating Sortable instance for",
      items.length,
      "simple queue items"
    );

    try {
      this.sortableInstances.simpleQueues = new Sortable(element, {
        // Use class selector instead of handle for better compatibility
        filter: ".queue-item-actions, .queue-item-actions *",
        preventOnFilter: false,
        dragClass: "sortable-drag",
        ghostClass: "sortable-ghost",
        chosenClass: "sortable-chosen",
        animation: 150,
        delay: 0,
        delayOnTouchStart: true,
        touchStartThreshold: 5,
        forceFallback: false,
        fallbackClass: "sortable-fallback",
        scrollSensitivity: 30,
        scrollSpeed: 10,
        bubbleScroll: true,

        // Handle drag by the entire item but exclude action buttons
        onChoose: (evt) => {
          console.log(
            "Simple queue item chosen for drag:",
            evt.item.dataset.id
          );
          evt.item.classList.add("dragging");
        },

        onUnchoose: (evt) => {
          console.log("Simple queue item unchoosen");
          evt.item.classList.remove("dragging");
        },

        onStart: (evt) => {
          console.log(
            "Simple queue drag started:",
            evt.item.dataset.id,
            "Old index:",
            evt.oldIndex
          );
          evt.item.classList.add("dragging");
          document.body.classList.add("sorting");
        },

        onEnd: (evt) => {
          console.log(
            "Simple queue drag ended:",
            evt.item.dataset.id,
            "New index:",
            evt.newIndex,
            "Old index:",
            evt.oldIndex
          );
          evt.item.classList.remove("dragging");
          document.body.classList.remove("sorting");

          if (evt.oldIndex !== evt.newIndex) {
            this.handleSimpleQueueReorder(evt);
          }
        },

        onMove: (evt) => {
          // Allow all moves except on action buttons
          const target = evt.related;
          return (
            !target.classList.contains("queue-item-actions") &&
            !target.closest(".queue-item-actions")
          );
        },
      });

      console.log("Simple queue sortable initialized successfully");
    } catch (error) {
      console.error("Error initializing simple queue sortable:", error);
    }
  }

  async handleSimpleQueueReorder(evt) {
    const movedId = evt.item.dataset.id;
    const newIndex = evt.newIndex;
    const oldIndex = evt.oldIndex;

    console.log(
      "Simple queue reorder:",
      movedId,
      "from",
      oldIndex,
      "to",
      newIndex
    );

    if (newIndex === oldIndex) return;

    try {
      const response = await fetch("api/queue_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "reorder_simple_queue",
          nas_id: window.nasManager.getCurrentNasId(),
          queue_id: movedId,
          new_position: newIndex,
          old_position: oldIndex,
        }),
      });

      const result = await response.json();
      if (!result.success) {
        this.showAlert(`Reorder failed: ${result.error}`, "danger");
        this.loadSimpleQueues(); // Reload to revert
      } else {
        this.showAlert("Queue order updated successfully", "success", 2000);
        evt.item.classList.add("drag-feedback");
        setTimeout(() => {
          evt.item.classList.remove("drag-feedback");
        }, 300);
      }
    } catch (error) {
      console.error("Error reordering queue:", error);
      this.showAlert("Error updating queue order", "danger");
      this.loadSimpleQueues(); // Reload to revert
    }
  }

  async loadActiveClients() {
    try {
      const response = await fetch(
        `api/hotspot_operations.php?action=get_active_users&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      const targetSelect = $("#targetSelect");
      targetSelect.find("option:not(:first)").remove();
      targetSelect.append('<option value="manual">Enter IP manually</option>');
      
      if (result.success && result.data) {
        result.data.forEach((client) => {
          const displayText = `${client.address} (${client.user || 'Unknown'})`;
          targetSelect.append(`<option value="${client.address}">${displayText}</option>`);
        });
      }
    } catch (error) {
      console.error("Error loading active clients:", error);
    }
  }

  async handleSimpleQueueSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.action = "add_simple_queue";
    data.nas_id = window.nasManager.getCurrentNasId();
    
    // Handle target selection
    if (data.target === "manual" || !data.target) {
      data.target = data.target_manual || "";
    }
    delete data.target_manual;

    // Combine upload and download limits
    if (data["max-limit-upload"] || data["max-limit-download"]) {
      data["max-limit"] = `${data["max-limit-upload"] || "0"}/${
        data["max-limit-download"] || "0"
      }`;
    }
    if (data["burst-limit-upload"] || data["burst-limit-download"]) {
      data["burst-limit"] = `${data["burst-limit-upload"] || "0"}/${
        data["burst-limit-download"] || "0"
      }`;
    }
    if (data["burst-threshold-upload"] || data["burst-threshold-download"]) {
      data["burst-threshold"] = `${data["burst-threshold-upload"] || "0"}/${
        data["burst-threshold-download"] || "0"
      }`;
    }

    try {
      const response = await fetch("api/queue_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();
      if (result.success) {
        this.showAlert("Simple queue added successfully", "success");
        $("#addSimpleQueueModal").modal("hide");
        e.target.reset();
        await this.loadSimpleQueues();
      } else {
        this.showAlert(`Error: ${result.error}`, "danger");
      }
    } catch (error) {
      console.error("Error adding simple queue:", error);
      this.showAlert("Error adding simple queue", "danger");
    }
  }

  async handleEditSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    // Handle combined limits for simple queues
    if (
      data["max-limit-upload"] !== undefined ||
      data["max-limit-download"] !== undefined
    ) {
      data["max-limit"] = `${data["max-limit-upload"] || "0"}/${
        data["max-limit-download"] || "0"
      }`;
    }
    if (
      data["burst-limit-upload"] !== undefined ||
      data["burst-limit-download"] !== undefined
    ) {
      data["burst-limit"] = `${data["burst-limit-upload"] || "0"}/${
        data["burst-limit-download"] || "0"
      }`;
    }
    if (
      data["burst-threshold-upload"] !== undefined ||
      data["burst-threshold-download"] !== undefined
    ) {
      data["burst-threshold"] = `${data["burst-threshold-upload"] || "0"}/${
        data["burst-threshold-download"] || "0"
      }`;
    }

    try {
      const response = await fetch("api/queue_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();
      if (result.success) {
        this.showAlert("Queue updated successfully", "success");
        $("#editModal").modal("hide");

        // Reload simple queues
        await this.loadSimpleQueues();
      } else {
        this.showAlert(`Error: ${result.error}`, "danger");
      }
    } catch (error) {
      console.error("Error updating queue:", error);
      this.showAlert("Error updating queue", "danger");
    }
  }

  async editSimpleQueue(id) {
    try {
      const response = await fetch(
        `api/queue_operations.php?action=get_simple_queue_details&nas_id=${window.nasManager.getCurrentNasId()}&queue_id=${id}`
      );
      const result = await response.json();

      if (result.success) {
        this.populateEditModal("simple_queue", result.data);
        $("#editModal").modal("show");
      } else {
        this.showAlert(`Error: ${result.error}`, "danger");
      }
    } catch (error) {
      console.error("Error loading simple queue details:", error);
      this.showAlert("Error loading simple queue details", "danger");
    }
  }

  async deleteSimpleQueue(id) {
    this.showConfirmModal(
      "Are you sure you want to delete this simple queue?",
      () => this.confirmDeleteSimpleQueue(id)
    );
  }

  async confirmDeleteSimpleQueue(id) {

    try {
      const response = await fetch("api/queue_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "delete_simple_queue",
          nas_id: window.nasManager.getCurrentNasId(),
          queue_id: id,
        }),
      });

      const result = await response.json();
      if (result.success) {
        this.showAlert("Simple queue deleted successfully", "success");
        await this.loadSimpleQueues();
      } else {
        this.showAlert(`Error: ${result.error}`, "danger");
      }
    } catch (error) {
      console.error("Error deleting simple queue:", error);
      this.showAlert("Error deleting simple queue", "danger");
    }
  }

  // Toggle enable/disable
  async toggleQueue(type, id) {
    try {
      const response = await fetch("api/queue_operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "toggle_queue",
          nas_id: window.nasManager.getCurrentNasId(),
          queue_type: type,
          queue_id: id,
        }),
      });

      const result = await response.json();
      if (result.success) {
        this.showAlert("Queue status updated successfully", "success", 2000);

        // Reload simple queues
        await this.loadSimpleQueues();
      } else {
        this.showAlert(`Error: ${result.error}`, "danger");
      }
    } catch (error) {
      console.error("Error toggling queue:", error);
      this.showAlert("Error updating queue status", "danger");
    }
  }

  populateEditModal(type, data) {
    const modalTitle = $("#editModalTitle");
    const modalBody = $("#editModalBody");

    modalTitle.text("Edit Simple Queue");
    modalBody.html(this.generateSimpleQueueEditForm(data));
  }

  generateSimpleQueueEditForm(data) {
    const maxLimits = data["max-limit"]
      ? data["max-limit"].split("/")
      : ["", ""];

    return `
            <input type="hidden" name="action" value="edit_simple_queue">
            <input type="hidden" name="nas_id" value="${window.nasManager.getCurrentNasId()}">
            <input type="hidden" name="queue_id" value="${data[".id"]}">
            
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" class="form-control form-control-sm" name="name" value="${
                  data.name || ""
                }" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Target</label>
                <input type="text" class="form-control form-control-sm" name="target" value="${
                  data.target || ""
                }">
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="mb-3">
                        <label class="form-label">Upload</label>
                        <input type="text" class="form-control form-control-sm" name="max-limit-upload" value="${
                          maxLimits[0] || ""
                        }">
                    </div>
                </div>
                <div class="col-6">
                    <div class="mb-3">
                        <label class="form-label">Download</label>
                        <input type="text" class="form-control form-control-sm" name="max-limit-download" value="${
                          maxLimits[1] || ""
                        }">
                    </div>
                </div>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="disabled" ${
                  data.disabled === "true" ? "checked" : ""
                }>
                <label class="form-check-label">Disabled</label>
            </div>
        `;
  }

  showConfirmModal(message, onConfirm) {
    $("#confirmMessage").text(message);
    $("#confirmButton").off("click").on("click", () => {
      $("#confirmModal").modal("hide");
      onConfirm();
    });
    $("#confirmModal").modal("show");
  }

  formatBandwidth(value) {
    if (!value || value === "0" || value === "-") return "-";

    // Remove any existing unit suffixes
    const cleanValue = value.toString().replace(/[a-zA-Z]/g, "");
    const numValue = parseFloat(cleanValue);

    if (isNaN(numValue)) return value;

    // Convert based on value size
    if (numValue >= 1000000000) {
      return (numValue / 1000000000).toFixed(1) + " Gbps";
    } else if (numValue >= 1000000) {
      return (numValue / 1000000).toFixed(1) + " Mbps";
    } else if (numValue >= 1000) {
      return (numValue / 1000).toFixed(1) + " Kbps";
    } else {
      return numValue + " bps";
    }
  }

  showConnectionAlert() {
    // Check connection status and show alert if needed
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      this.clearQueues();
    }
  }

  hideConnectionAlert() {
    const alert = $("#connection-alert");
    alert.hide();
  }

  clearQueues() {
    $("#sortable-simple-queues").html(`
      <div class="text-center p-4 text-muted">
        <i class="fa fa-info-circle me-2"></i>
        Please connect to a NAS device to view simple queues.
      </div>
    `);
  }

  showAlert(message, type = "info", duration = 5000) {
    const toastType = type === 'danger' ? 'error' : type;
    Toast[toastType](message, duration);
  }
}

// Initialize queue manager when document is ready
$(document).ready(function () {
  console.log("DOM ready, initializing QueueManager...");

  // Add a delay to ensure all scripts are loaded from footer
  setTimeout(() => {
    console.log("Creating QueueManager instance...");
    window.queueManager = new QueueManager();

    // Listen for NAS connection events
    $(document).on("nas:connected", function (event, nasId) {
      console.log("NAS connected in queue page:", nasId);
      updateConnectionStatus(true);
      if (window.queueManager) {
        window.queueManager.loadSimpleQueues();
      }
    });

    $(document).on("nas:disconnected", function () {
      console.log("NAS disconnected in queue page");
      updateConnectionStatus(false);
      if (window.queueManager) {
        window.queueManager.clearQueues();
      }
    });

    // Check for existing connection with delay
    setTimeout(() => {
      if (window.nasManager && window.nasManager.getConnectionStatus()) {
        console.log("Queue page: Found existing connection");
        updateConnectionStatus(true);
        if (window.queueManager) {
          window.queueManager.loadSimpleQueues();
        }
      } else {
        updateConnectionStatus(false);
      }
    }, 200);
  }, 500);
});

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
