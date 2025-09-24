class HotspotManager {
  constructor() {
    this.init();
  }

  init() {
    this.bindEvents();
  }

  bindEvents() {
    // Listen for NAS connection events
    $(document).on("nas:connected", (e, nasId, nasName) => {
      console.log("NAS connected:", nasId, nasName);
      this.handleNasConnection();
    });
  }

  handleNasConnection() {
    // Destroy existing DataTables to prevent conflicts
    this.destroyDataTables();
    // Force clear all data first
    this.clearAllData();
    // Add small delay then load fresh data
    setTimeout(() => {
      this.loadAllData();
    }, 100);
  }

  destroyDataTables() {
    const tables = [
      "#usersTable",
      "#profilesTable",
      "#activeUsersTable",
      "#hostsTable",
    ];
    tables.forEach((tableId) => {
      if ($.fn.DataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
      }
    });
  }

  initDataTable(tableId) {
    if ($.fn.DataTable.isDataTable(tableId)) {
      $(tableId).DataTable().destroy();
    }
    $(tableId).DataTable({
      responsive: true,
      pageLength: 25,
      order: [[1, "asc"]],
    });
  }

  async loadAllData() {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) return;

    await Promise.all([
      this.loadUsers(),
      this.loadProfiles(),
      this.loadActiveUsers(),
      this.loadHosts(),
    ]);
  }

  async loadUsers() {
    try {
      const response = await fetch(
        `api/hotspot_operations.php?action=get_users&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderUsers(result.data);
      } else {
        this.showError("#usersTable", result.error);
      }
    } catch (error) {
      console.error("Error loading users:", error);
      this.showError("#usersTable", "Failed to load users");
    }
  }

  async loadProfiles() {
    try {
      const response = await fetch(
        `api/hotspot_operations.php?action=get_profiles&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderProfiles(result.data);
      } else {
        this.showError("#profilesTable", result.error);
      }
    } catch (error) {
      console.error("Error loading profiles:", error);
      this.showError("#profilesTable", "Failed to load profiles");
    }
  }

  async loadActiveUsers() {
    try {
      const response = await fetch(
        `api/hotspot_operations.php?action=get_active_users&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderActiveUsers(result.data);
      } else {
        this.showError("#activeUsersTable", result.error);
      }
    } catch (error) {
      console.error("Error loading active users:", error);
      this.showError("#activeUsersTable", "Failed to load active users");
    }
  }

  async loadHosts() {
    try {
      const response = await fetch(
        `api/hotspot_operations.php?action=get_hosts&nas_id=${window.nasManager.getCurrentNasId()}`
      );
      const result = await response.json();

      if (result.success) {
        this.renderHosts(result.data);
      } else {
        this.showError("#hostsTable", result.error);
      }
    } catch (error) {
      console.error("Error loading hosts:", error);
      this.showError("#hostsTable", "Failed to load hosts");
    }
  }

  renderUsers(users) {
    const tbody = $("#usersTable tbody");
    tbody.empty();

    if (!users || users.length === 0) {
      tbody.html(
        '<tr><td colspan="9" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No users found</td></tr>'
      );
      return;
    }

    const filteredUsers = users.filter(user => !user.name.toLowerCase().includes('default'));
    
    if (filteredUsers.length === 0) {
      tbody.html(
        '<tr><td colspan="9" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No users found</td></tr>'
      );
      return;
    }

    filteredUsers.forEach((user) => {
      const row = `
                <tr>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="hotspotManager.deleteUser('${
                          user[".id"]
                        }')"><i class="fa fa-trash"></i></button>
                    </td>
                    <td>${user.name || "-"}</td>
                    <td><span class="badge bg-primary">${
                      user.profile || "-"
                    }</span></td>
                    <td>${user.server || "-"}</td>
                    <td>${user["mac-address"] || "-"}</td>
                    <td>${this.formatBytes(user["bytes-in"] || 0)}</td>
                    <td>${this.formatBytes(user["bytes-out"] || 0)}</td>
                    <td>${user.comment || "-"}</td>
                    <td><span class="badge ${
                      user.disabled === "true" ? "bg-danger" : "bg-success"
                    }">${
        user.disabled === "true" ? "Disabled" : "Enabled"
      }</span></td>
                </tr>
            `;
      tbody.append(row);
    });

    this.initDataTable("#usersTable");
  }

  renderProfiles(profiles) {
    const tbody = $("#profilesTable tbody");
    tbody.empty();

    if (!profiles || profiles.length === 0) {
      tbody.html(
        '<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No profiles found</td></tr>'
      );
      return;
    }

    const filteredProfiles = profiles.filter(profile => !profile.name.toLowerCase().includes('default'));
    
    if (filteredProfiles.length === 0) {
      tbody.html(
        '<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No profiles found</td></tr>'
      );
      return;
    }

    filteredProfiles.forEach((profile) => {
      // Parse on-login script to extract expire mode and validity
      const onLogin = profile["on-login"] || "";
      let expiredMode = "None";
      let validity = "-";
      let lockUser = "Disable";
      let lockServer = "Disable";

      if (onLogin.includes(",rem,")) expiredMode = "Remove";
      else if (onLogin.includes(",ntf,")) expiredMode = "Notice";
      else if (onLogin.includes(",remc,")) expiredMode = "Remove & Record";
      else if (onLogin.includes(",ntfc,")) expiredMode = "Notice & Record";

      const validityMatch = onLogin.match(/interval="([^"]+)"/);
      if (validityMatch) {
        validity = validityMatch[1];
      }

      if (onLogin.includes("mac-address=$mac")) lockUser = "Enable";
      if (onLogin.includes("server=$srv")) lockServer = "Enable";

      const row = `
                <tr>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="hotspotManager.deleteProfile('${
                          profile[".id"]
                        }')"><i class="fa fa-trash"></i></button>
                    </td>
                    <td>${profile.name || "-"}</td>
                    <td>${profile["shared-users"] || "-"}</td>
                    <td>${profile["rate-limit"] || "-"}</td>
                    <td>${expiredMode}</td>
                    <td>${validity}</td>
                    <td>${lockUser}</td>
                    <td>${lockServer}</td>
                </tr>
            `;
      tbody.append(row);
    });

    this.initDataTable("#profilesTable");
  }

  renderActiveUsers(activeUsers) {
    const tbody = $("#activeUsersTable tbody");
    tbody.empty();

    if (!activeUsers || activeUsers.length === 0) {
      tbody.html(
        '<tr><td colspan="9" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No active users found</td></tr>'
      );
      return;
    }

    activeUsers.forEach((user) => {
      const row = `
                <tr>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="hotspotManager.disconnectUser('${
                          user[".id"]
                        }')"><i class="fa fa-sign-out"></i></button>
                    </td>
                    <td>${user.user || "-"}</td>
                    <td>${user.address || "-"}</td>
                    <td>${user["mac-address"] || "-"}</td>
                    <td>${user.uptime || "-"}</td>
                    <td>${this.formatBytes(user["bytes-in"] || 0)}</td>
                    <td>${this.formatBytes(user["bytes-out"] || 0)}</td>
                    <td>${user["session-time-left"] || "-"}</td>
                    <td>${user["login-by"] || "-"}</td>
                </tr>
            `;
      tbody.append(row);
    });

    this.initDataTable("#activeUsersTable");
  }

  renderHosts(hosts) {
    const tbody = $("#hostsTable tbody");
    tbody.empty();

    if (!hosts || hosts.length === 0) {
      tbody.html(
        '<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No hosts found</td></tr>'
      );
      return;
    }

    hosts.forEach((host) => {
      const row = `
                <tr>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="hotspotManager.deleteHost('${
                          host[".id"]
                        }')"><i class="fa fa-trash"></i></button>
                    </td>
                    <td>${host["mac-address"] || "-"}</td>
                    <td>${host.address || "-"}</td>
                    <td>${host["to-address"] || "-"}</td>
                    <td>${host.server || "-"}</td>
                    <td><span class="badge ${
                      host.authorized === "true" ? "bg-success" : "bg-warning"
                    }">${host.authorized === "true" ? "Yes" : "No"}</span></td>
                </tr>
            `;
      tbody.append(row);
    });

    this.initDataTable("#hostsTable");
  }

  clearAllData() {
    $("#usersTable tbody").html(
      '<tr><td colspan="9" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to view hotspot users.</td></tr>'
    );
    $("#profilesTable tbody").html(
      '<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to view hotspot profiles.</td></tr>'
    );
    $("#activeUsersTable tbody").html(
      '<tr><td colspan="9" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to view active users.</td></tr>'
    );
    $("#hostsTable tbody").html(
      '<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>Please connect to a NAS device to view hosts.</td></tr>'
    );
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
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  }

  // Refresh methods that properly handle existing DataTables
  refreshUsers() {
    if ($.fn.DataTable.isDataTable("#usersTable")) {
      $("#usersTable").DataTable().destroy();
    }
    this.loadUsers();
  }

  refreshProfiles() {
    if ($.fn.DataTable.isDataTable("#profilesTable")) {
      $("#profilesTable").DataTable().destroy();
    }
    this.loadProfiles();
  }

  refreshActiveUsers() {
    if ($.fn.DataTable.isDataTable("#activeUsersTable")) {
      $("#activeUsersTable").DataTable().destroy();
    }
    this.loadActiveUsers();
  }

  refreshHosts() {
    if ($.fn.DataTable.isDataTable("#hostsTable")) {
      $("#hostsTable").DataTable().destroy();
    }
    this.loadHosts();
  }

  async deleteUser(userId) {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      if (window.Toast)
        window.Toast.warning("Please connect to a NAS device first");
      return;
    }

    try {
      const response = await fetch("api/hotspot_operations.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "delete_user",
          nas_id: window.nasManager.getCurrentNasId(),
          user_id: userId,
        }),
      });
      const result = await response.json();

      if (result.success) {
        if (window.Toast) window.Toast.success("User deleted successfully");
        // Remove the row from the table immediately
        const table = $("#usersTable").DataTable();
        const row = $(
          `button[onclick="hotspotManager.deleteUser('${userId}')"]`
        ).closest("tr");
        table.row(row).remove().draw();

        // If no rows left, show the empty message
        if (table.data().count() === 0) {
          $("#usersTable tbody").html(
            '<tr><td colspan="9" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No users found</td></tr>'
          );
        }
      } else {
        if (window.Toast)
          window.Toast.error(result.error || "Failed to delete user");
      }
    } catch (error) {
      if (window.Toast) window.Toast.error("Failed to delete user");
    }
  }

  async deleteProfile(profileId) {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      if (window.Toast)
        window.Toast.warning("Please connect to a NAS device first");
      return;
    }

    try {
      const response = await fetch("api/hotspot_operations.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "delete_profile",
          nas_id: window.nasManager.getCurrentNasId(),
          profile_id: profileId,
        }),
      });
      const result = await response.json();

      if (result.success) {
        if (window.Toast) window.Toast.success("Profile deleted successfully");
        // Remove the row from the table immediately
        const table = $("#profilesTable").DataTable();
        const row = $(
          `button[onclick="hotspotManager.deleteProfile('${profileId}')"]`
        ).closest("tr");
        table.row(row).remove().draw();

        // If no rows left, show the empty message
        if (table.data().count() === 0) {
          $("#profilesTable tbody").html(
            '<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No profiles found</td></tr>'
          );
        }
      } else {
        if (window.Toast)
          window.Toast.error(result.error || "Failed to delete profile");
      }
    } catch (error) {
      if (window.Toast) window.Toast.error("Failed to delete profile");
    }
  }

  async disconnectUser(userId) {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      if (window.Toast)
        window.Toast.warning("Please connect to a NAS device first");
      return;
    }

    try {
      const response = await fetch("api/hotspot_operations.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "disconnect_user",
          nas_id: window.nasManager.getCurrentNasId(),
          user_id: userId,
        }),
      });
      const result = await response.json();

      if (result.success) {
        if (window.Toast)
          window.Toast.success("User disconnected successfully");
        // Remove the row from the table immediately
        const table = $("#activeUsersTable").DataTable();
        const row = $(
          `button[onclick="hotspotManager.disconnectUser('${userId}')"]`
        ).closest("tr");
        table.row(row).remove().draw();

        // If no rows left, show the empty message
        if (table.data().count() === 0) {
          $("#activeUsersTable tbody").html(
            '<tr><td colspan="9" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No active users found</td></tr>'
          );
        }
      } else {
        if (window.Toast)
          window.Toast.error(result.error || "Failed to disconnect user");
      }
    } catch (error) {
      if (window.Toast) window.Toast.error("Failed to disconnect user");
    }
  }

  async deleteHost(hostId) {
    if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
      if (window.Toast)
        window.Toast.warning("Please connect to a NAS device first");
      return;
    }

    try {
      const response = await fetch("api/hotspot_operations.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "delete_host",
          nas_id: window.nasManager.getCurrentNasId(),
          host_id: hostId,
        }),
      });
      const result = await response.json();

      if (result.success) {
        if (window.Toast) window.Toast.success("Host deleted successfully");
        // Remove the row from the table immediately
        const table = $("#hostsTable").DataTable();
        const row = $(
          `button[onclick="hotspotManager.deleteHost('${hostId}')"]`
        ).closest("tr");
        table.row(row).remove().draw();

        // If no rows left, show the empty message
        if (table.data().count() === 0) {
          $("#hostsTable tbody").html(
            '<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-info-circle me-2"></i>No hosts found</td></tr>'
          );
        }
      } else {
        if (window.Toast)
          window.Toast.error(result.error || "Failed to delete host");
      }
    } catch (error) {
      if (window.Toast) window.Toast.error("Failed to delete host");
    }
  }
}

// User Profile Modal Functions
function showAddUserProfileModal() {
  if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
    if (window.Toast) {
      window.Toast.warning("Please connect to a NAS device first");
    }
    return;
  }

  resetAddUserProfileForm();
  loadAddressPoolsAndQueues();
  $("#addUserProfileModal").modal("show");
}

function showEditUserProfileModal(profileId) {
  if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
    if (window.Toast) {
      window.Toast.warning("Please connect to a NAS device first");
    }
    return;
  }

  resetAddUserProfileForm();
  $("#addUserProfileModal .modal-title").text("Edit User Profile");
  loadAddressPoolsAndQueues().then(() => {
    loadUserProfileData(profileId);
  });
  $("#addUserProfileModal").modal("show");
}

function resetAddUserProfileForm() {
  $("#addUserProfileForm")[0].reset();
  $('#addUserProfileModal input[name="profile_id"]').val("");
  $('#addUserProfileModal select[name="expired_mode"]').val("0");
  $('#addUserProfileModal select[name="lock_user"]').val("Disable");
  $('#addUserProfileModal select[name="lock_server"]').val("Disable");
  $("#addUserProfileModal #validityField").hide();
  $("#addUserProfileModal .modal-title").text("Add User Profile");
  $("#general-tab").tab("show");
}

function toggleValidityField(expiredMode) {
  if (expiredMode && expiredMode !== "0") {
    $("#addUserProfileModal #validityField").show();
  } else {
    $("#addUserProfileModal #validityField").hide();
  }
}

async function loadAddressPoolsAndQueues() {
  try {
    const nasId = window.nasManager.getCurrentNasId();

    const poolsResponse = await fetch(
      `api/hotspot_operations.php?action=get_address_pools&nas_id=${nasId}`
    );
    const poolsResult = await poolsResponse.json();

    if (poolsResult.success) {
      const poolSelect = $('#addUserProfileModal select[name="address_pool"]');
      poolSelect.find("option:not(:first)").remove();
      poolsResult.data.forEach((pool) => {
        poolSelect.append(`<option value="${pool.name}">${pool.name}</option>`);
      });
    }

    const queuesResponse = await fetch(
      `api/hotspot_operations.php?action=get_parent_queues&nas_id=${nasId}`
    );
    const queuesResult = await queuesResponse.json();

    if (queuesResult.success) {
      const queueSelect = $('#addUserProfileModal select[name="parent_queue"]');
      queueSelect.find("option:not(:first)").remove();
      queuesResult.data.forEach((queue) => {
        queueSelect.append(
          `<option value="${queue.name}">${queue.name}</option>`
        );
      });
    }
  } catch (error) {
    console.error("Error loading pools and queues:", error);
  }
}

async function loadUserProfileData(profileId) {
  try {
    const nasId = window.nasManager.getCurrentNasId();
    const response = await fetch(
      `api/hotspot_operations.php?action=get_user_profile&nas_id=${nasId}&profile_id=${profileId}`
    );
    const result = await response.json();

    if (result.success && result.data) {
      const profile = result.data;
      $('#addUserProfileModal input[name="profile_id"]').val(profileId);
      $('#addUserProfileModal input[name="name"]').val(profile.name || "");
      $('#addUserProfileModal select[name="address_pool"]').val(
        profile["address-pool"] === "none" ? "" : profile["address-pool"] || ""
      );
      $('#addUserProfileModal input[name="shared_users"]').val(
        profile["shared-users"] || ""
      );
      $('#addUserProfileModal input[name="rate_limit"]').val(
        profile["rate-limit"] || ""
      );
      $('#addUserProfileModal select[name="parent_queue"]').val(
        profile["parent-queue"] === "none" ? "" : profile["parent-queue"] || ""
      );

      const onLogin = profile["on-login"] || "";
      let expiredMode = "0";
      let validity = "";
      let lockUser = "Disable";
      let lockServer = "Disable";

      // Parse exactly like helper files - :put (",...,...,...,...,,...,...,...,")
      const putMatch = onLogin.match(/:put \("([^"]+)"\)/);
      if (putMatch) {
        const putData = putMatch[1].split(",");
        // Helper file structure: ,expmode,price,validity,sprice,,lockuser,lockserver,
        if (putData.length >= 8) {
          if (putData[1]) expiredMode = putData[1]; // Index 1: expmode
          if (putData[3]) validity = putData[3];     // Index 3: validity
          if (putData[6]) lockUser = putData[6];     // Index 6: lockuser
          if (putData[7]) lockServer = putData[7];   // Index 7: lockserver
        }
      }

      $('#addUserProfileModal select[name="expired_mode"]').val(expiredMode);
      toggleValidityField(expiredMode);
      $('#addUserProfileModal input[name="validity"]').val(validity);
      $('#addUserProfileModal select[name="lock_user"]').val(lockUser);
      $('#addUserProfileModal select[name="lock_server"]').val(lockServer);
    }
  } catch (error) {
    console.error("Error loading profile data:", error);
    if (window.Toast) {
      window.Toast.error("Failed to load profile data");
    }
  }
}

async function saveUserProfile() {
  const form = $("#addUserProfileForm")[0];
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const formData = new FormData();
  const profileId = $('#addUserProfileModal input[name="profile_id"]').val();
  const action = profileId ? "update_user_profile" : "add_user_profile";
  formData.append("action", action);
  formData.append("nas_id", window.nasManager.getCurrentNasId());
  if (profileId) {
    formData.append("profile_id", profileId);
  }

  const name = $('#addUserProfileModal input[name="name"]').val();
  const addressPool = $(
    '#addUserProfileModal select[name="address_pool"]'
  ).val();
  const sharedUsers = $(
    '#addUserProfileModal input[name="shared_users"]'
  ).val();
  const rateLimit = $('#addUserProfileModal input[name="rate_limit"]').val();
  const parentQueue = $(
    '#addUserProfileModal select[name="parent_queue"]'
  ).val();

  formData.append("name", name);
  formData.append("address_pool", addressPool);
  formData.append("shared_users", sharedUsers);
  formData.append("rate_limit", rateLimit);
  formData.append("parent_queue", parentQueue);

  formData.append(
    "expired_mode",
    $('#addUserProfileModal select[name="expired_mode"]').val()
  );
  formData.append(
    "validity",
    $('#addUserProfileModal input[name="validity"]').val()
  );
  formData.append(
    "lock_user",
    $('#addUserProfileModal select[name="lock_user"]').val()
  );
  formData.append(
    "lock_server",
    $('#addUserProfileModal select[name="lock_server"]').val()
  );

  try {
    const response = await fetch("api/hotspot_operations.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      $("#addUserProfileModal").modal("hide");
      const successMessage = profileId
        ? "User profile updated successfully"
        : "User profile added successfully";
      if (window.Toast) {
        window.Toast.success(successMessage);
      }

      // Immediate refresh without timeout
      if (window.hotspotManager) {
        window.hotspotManager.refreshProfiles();
      }
    } else {
      const errorMessage = profileId
        ? "Failed to update user profile"
        : "Failed to add user profile";
      if (window.Toast) {
        window.Toast.error(result.error || errorMessage);
      }
    }
  } catch (error) {
    console.error("Error saving user profile:", error);
    const profileId = $('#addUserProfileModal input[name="profile_id"]').val();
    const errorMessage = profileId
      ? "Failed to update user profile"
      : "Failed to add user profile";
    if (window.Toast) {
      window.Toast.error(errorMessage);
    }
  }
}

// Generate Voucher Modal Functions
function showGenerateVoucherModal() {
  if (!window.nasManager || !window.nasManager.getConnectionStatus()) {
    if (window.Toast) {
      window.Toast.warning("Please connect to a NAS device first");
    }
    return;
  }

  resetGenerateVoucherForm();
  loadVoucherProfiles();
  loadVoucherServers();
  $("#generateVoucherModal").modal("show");
}

function resetGenerateVoucherForm() {
  $("#generateVoucherForm")[0].reset();
  $('input[name="qty"]').val("10");
  $('select[name="user"]').val("vc");
  $('select[name="userl"]').val("6");
  $('select[name="char"]').val("mix");
}

async function loadVoucherProfiles() {
  try {
    const nasId = window.nasManager.getCurrentNasId();
    const response = await fetch(
      `api/hotspot_operations.php?action=get_profiles&nas_id=${nasId}`
    );
    const result = await response.json();

    if (result.success) {
      const profileSelect = $('#generateVoucherModal select[name="profile"]');
      profileSelect.find("option:not(:first)").remove();
      const filteredProfiles = result.data.filter(profile => !profile.name.toLowerCase().includes('default'));
      filteredProfiles.forEach((profile) => {
        profileSelect.append(
          `<option value="${profile.name}">${profile.name}</option>`
        );
      });
    }
  } catch (error) {
    console.error("Error loading profiles:", error);
  }
}

async function loadVoucherServers() {
  try {
    const nasId = window.nasManager.getCurrentNasId();
    const response = await fetch(
      `api/hotspot_operations.php?action=get_servers&nas_id=${nasId}`
    );
    const result = await response.json();

    if (result.success) {
      const serverSelect = $('#generateVoucherModal select[name="server"]');
      serverSelect.find("option:not(:first)").remove();
      result.data.forEach((server) => {
        serverSelect.append(
          `<option value="${server.name}">${server.name}</option>`
        );
      });
    }
  } catch (error) {
    console.log("Servers not available, using default only");
  }
}

async function generateVouchers() {
  const form = $("#generateVoucherForm")[0];
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const formData = new FormData();
  formData.append("action", "generate");
  formData.append("nas_id", window.nasManager.getCurrentNasId());
  formData.append("qty", $('input[name="qty"]').val());
  formData.append("server", $('select[name="server"]').val());
  formData.append("user", $('select[name="user"]').val());
  formData.append("userl", $('select[name="userl"]').val());
  formData.append("prefix", $('input[name="prefix"]').val());
  formData.append("char", $('select[name="char"]').val());
  formData.append("profile", $('select[name="profile"]').val());
  formData.append("timelimit", $('input[name="timelimit"]').val());
  formData.append("datalimit", $('input[name="datalimit"]').val());
  formData.append("gcomment", $('input[name="gcomment"]').val());

  try {
    const response = await fetch("api/hotspot_voucher.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      $("#generateVoucherModal").modal("hide");
      if (window.Toast) {
        window.Toast.success(
          `${result.data.count} vouchers generated successfully`
        );
      }
      // Immediate refresh without timeout
      if (window.hotspotManager) {
        window.hotspotManager.refreshUsers();
      }
    } else {
      if (window.Toast) {
        window.Toast.error(result.error || "Failed to generate vouchers");
      }
    }
  } catch (error) {
    console.error("Error generating vouchers:", error);
    if (window.Toast) {
      window.Toast.error("Failed to generate vouchers");
    }
  }
}

function printVouchers() {
  $("#printVoucherModal").modal("show");
  const nasId = window.nasManager.getCurrentNasId();
  
  fetch(`api/hotspot_operations.php?action=get_profiles&nas_id=${nasId}`)
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        const select = $("#printProfileSelect");
        select.empty().append('<option value="">All Profiles</option>');
        const filteredProfiles = result.data.filter(profile => !profile.name.toLowerCase().includes('default'));
        filteredProfiles.forEach(profile => {
          select.append(`<option value="${profile.name}">${profile.name}</option>`);
        });
      }
    });
}

function executePrint() {
  const nasId = window.nasManager.getCurrentNasId();
  const selectedProfile = $("#printProfileSelect").val();
  let printUrl;
  
  if (selectedProfile) {
    printUrl = `api/hotspot_print_voucher.php?profile=${selectedProfile}&nas_id=${nasId}&d=1`;
  } else {
    printUrl = `api/hotspot_print_voucher.php?all=1&nas_id=${nasId}&d=1`;
  }
  
  $("#printVoucherModal").modal("hide");
  window.open(printUrl, "_blank");
}

$(document).ready(function () {
  setTimeout(() => {
    console.log("Creating HotspotManager instance...");
    window.hotspotManager = new HotspotManager();

    $(document).on("nas:connected", function (event, nasId, nasName) {
      console.log("NAS connected in hotspot page:", nasId, nasName);
      updateConnectionStatus(true);
      if (window.hotspotManager) {
        window.hotspotManager.handleNasConnection();
      }
    });

    $(document).on("nas:disconnected", function () {
      console.log("NAS disconnected in hotspot page");
      updateConnectionStatus(false);
      if (window.hotspotManager) {
        window.hotspotManager.destroyDataTables();
        window.hotspotManager.clearAllData();
      }
    });

    setTimeout(() => {
      if (window.nasManager && window.nasManager.getConnectionStatus()) {
        console.log("Hotspot page: Found existing connection");
        updateConnectionStatus(true);
        if (window.hotspotManager) {
          window.hotspotManager.loadAllData();
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
  } else {
    statusHtml = `<span class="badge bg-danger"><i class="fa fa-times"></i> Disconnected</span>`;
  }

  $(".connection-status").html(statusHtml);
}
