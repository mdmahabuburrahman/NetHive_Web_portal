/**
 * Navbar NAS Connection Manager
 * Handles NAS device selection and connection in navbar across specific pages
 */

class NavbarConnectionManager {
  constructor() {
    this.currentNasId = localStorage.getItem("nas_current_id");
    this.isConnected = localStorage.getItem("nas_connected") === "true";
    this.connectedNasId = localStorage.getItem("nas_connected_id");
    this.lastActivity =
      parseInt(localStorage.getItem("nas_last_activity")) || Date.now();
    this.idleTimeout = 30 * 60 * 1000; // 30 minutes
    this.init();
  }

  init() {
    console.log("Initializing Navbar Connection Manager...");
    this.bindEvents();
    this.loadNasDevices();
  }

  bindEvents() {
    // Desktop NAS selector change
    $("#global-nas-selector").on("change", (e) => {
      this.handleNasSelection(e.target.value);
    });

    // Mobile NAS selector change
    $("#mobile-nas-selector").on("change", (e) => {
      this.handleNasSelection(e.target.value);
      // Sync with desktop selector
      $("#global-nas-selector").val(e.target.value);
    });

    // Desktop connect button
    $("#global-connect-btn").on("click", () => {
      this.handleConnect();
    });

    // Mobile connect button
    $("#mobile-connect-btn").on("click", () => {
      this.handleConnect();
    });

    // Update activity on user interaction
    $(document).on("click keypress scroll", () => {
      this.updateActivity();
    });
  }

  handleNasSelection(newNasId) {
    // If changing to different NAS while connected, disconnect first
    if (
      this.isConnected &&
      this.connectedNasId &&
      newNasId !== this.connectedNasId
    ) {
      this.disconnect();
    }

    this.currentNasId = newNasId;
    localStorage.setItem("nas_current_id", newNasId);
    console.log("Selected NAS:", this.currentNasId);
    

  }

  handleConnect() {
    if (this.isConnected && this.connectedNasId === this.currentNasId) {
      this.disconnect();
    } else {
      this.connectToNas();
    }
  }

  async loadNasDevices() {
    try {
      const response = await fetch(
        "api/queue_operations.php?action=get_nas_devices"
      );
      const devices = await response.json();

      // Populate both desktop and mobile selectors
      const selectors = ["#global-nas-selector", "#mobile-nas-selector"];
      selectors.forEach(selectorId => {
        const selector = $(selectorId);
        selector.empty();
        selector.append('<option value="">Select NAS Device...</option>');

        devices.forEach((device) => {
          selector.append(
            `<option value="${device.id}">${device.name} (${device.ip})</option>`
          );
        });

        // Restore previous selection
        if (this.currentNasId) {
          selector.val(this.currentNasId);
        }
      });

      // NAS devices loaded successfully

      // Check if still connected and not idle
      if (this.isConnected && !this.isIdle()) {
        setTimeout(() => {
          this.updateConnectButton(true);
          $(document).trigger("nas:connected", [this.connectedNasId]);
        }, 50);
      } else if (this.isConnected && this.isIdle()) {
        this.disconnect();
        this.showAlert("Connection expired due to inactivity", "warning");
      } else {
        this.updateConnectButton(false);
      }
    } catch (error) {
      console.error("Error loading NAS devices:", error);
      this.showAlert("Error loading NAS devices", "danger");
    }
  }



  async connectToNas() {
    if (!this.currentNasId) {
      this.showAlert("Please select a NAS device first", "warning");
      return;
    }

    const connectBtns = ["#global-connect-btn", "#mobile-connect-btn"];
    const originalHtml = $("#global-connect-btn").html();
    
    // Disable both buttons
    connectBtns.forEach(btnId => {
      $(btnId).prop("disabled", true);
      $(btnId).html('<i class="fa fa-spinner fa-spin"></i> Connecting...');
    });

    try {
      const response = await fetch(
        `api/queue_operations.php?action=connect&nas_id=${this.currentNasId}`
      );
      const result = await response.json();

      if (result.success) {
        this.isConnected = true;
        this.connectedNasId = this.currentNasId;
        this.updateActivity();
        this.saveConnectionState();
        this.showAlert(`Connected to ${result.nas_name}`, "success");

        // Trigger connection event for other components
        $(document).trigger("nas:connected", [
          this.currentNasId,
          result.nas_name,
        ]);
      } else {
        this.isConnected = false;
        this.updateConnectButton(false);
        this.showAlert(`Connection failed: ${result.error}`, "danger");
        $(document).trigger("nas:disconnected");
      }
    } catch (error) {
      console.error("Connection error:", error);
      this.showAlert("Connection error occurred", "danger");
      this.isConnected = false;
      this.updateConnectButton(false);
      $(document).trigger("nas:disconnected");
    } finally {
      // Re-enable both buttons
      connectBtns.forEach(btnId => {
        $(btnId).prop("disabled", false);
      });
      
      if (!this.isConnected) {
        connectBtns.forEach(btnId => {
          $(btnId).html(originalHtml);
        });
      } else {
        this.updateConnectButton(true);
      }
    }
  }

  showAlert(message, type = "info", duration = 5000) {
    const toastType = type === "danger" ? "error" : type;
    Toast[toastType](message, duration);
  }

  disconnect() {
    this.isConnected = false;
    this.connectedNasId = null;
    this.clearConnectionState();
    this.updateConnectButton(false);
    $(document).trigger("nas:disconnected");
    this.showAlert("Disconnected from NAS", "info");
  }

  saveConnectionState() {
    localStorage.setItem("nas_connected", "true");
    localStorage.setItem("nas_connected_id", this.connectedNasId);
    localStorage.setItem("nas_last_activity", Date.now().toString());
  }

  clearConnectionState() {
    localStorage.removeItem("nas_connected");
    localStorage.removeItem("nas_connected_id");
    localStorage.removeItem("nas_last_activity");
  }

  updateActivity() {
    this.lastActivity = Date.now();
    if (this.isConnected) {
      localStorage.setItem("nas_last_activity", this.lastActivity.toString());
    }
  }

  isIdle() {
    return Date.now() - this.lastActivity > this.idleTimeout;
  }

  updateConnectButton(connected) {
    const btns = ["#global-connect-btn", "#mobile-connect-btn"];
    btns.forEach(btnId => {
      const btn = $(btnId);
      if (connected) {
        btn.removeClass("btn-outline-primary").addClass("btn-outline-danger");
        btn.html('<i class="fa fa-unlink"></i> Disconnect');
      } else {
        btn.removeClass("btn-outline-danger").addClass("btn-outline-primary");
        btn.html('<i class="fa fa-plug"></i> Connect');
      }
    });
  }

  // Public methods for other components
  getCurrentNasId() {
    return this.currentNasId;
  }

  getConnectionStatus() {
    return this.isConnected;
  }
}

// Initialize Navbar Connection Manager globally
$(document).ready(function () {
  console.log("Initializing Navbar Connection Manager...");
  window.nasManager = new NavbarConnectionManager();
});
