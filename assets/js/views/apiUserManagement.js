let currentUserId = null;

document.addEventListener("DOMContentLoaded", function () {
  loadUsersData();

  document.getElementById("userForm").addEventListener("submit", function (e) {
    e.preventDefault();
    saveUser();
  });

  document
    .getElementById("confirmDeleteBtn")
    .addEventListener("click", function () {
      deleteUser(currentUserId);
    });
});

function loadUsersData() {
  fetch("api/user_operations.php?action=get")
    .then((response) => response.json())
    .then((data) => {
      // Destroy existing DataTable if it exists
      if ($.fn.DataTable.isDataTable("#usersTable")) {
        $("#usersTable").DataTable().destroy();
      }

      const tbody = document.getElementById("usersTableBody");
      tbody.innerHTML = "";

      data.forEach((user) => {
        const statusBadge =
          user.role === "admin"
            ? '<span class="badge bg-danger">Admin</span>'
            : user.role === "operator"
            ? '<span class="badge bg-warning">Operator</span>'
            : '<span class="badge bg-info">Viewer</span>';

        const row = `
                    <tr>
                        <td>
                            <button class="btn btn-sm btn-info me-1" onclick="editUser('${user.username}')"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="event.preventDefault(); showDeleteModal('${user.username}', '${user.fullName}')"><i class="fa fa-trash"></i></button>
                        </td>
                        <td>${user.fullName}</td>
                        <td>${user.username}</td>
                        <td>${user.role}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
        tbody.innerHTML += row;
      });

      // Initialize DataTable after data is loaded
      $("#usersTable").DataTable({
        responsive: true,
        pageLength: 3,
        lengthMenu: [
          [3, 10, 25, 50, 100],
          [3, 10, 25, 50, 100],
        ],
        searching: true,
        paging: true,
        info: true,
        language: {
          lengthMenu: "Show _MENU_ entries",
          info: "Showing _START_ to _END_ of _TOTAL_ entries",
          infoEmpty: "Showing 0 to 0 of 0 entries",
          infoFiltered: "(filtered from _MAX_ total entries)",
          search: "Search:",
          paginate: {
            first: "<<",
            last: ">>",
            next: ">",
            previous: "<",
          },
        },
      });
    })
    .catch((error) => {
      showToast("Error", "Failed to load users data", "error");
    });
}

function saveUser() {
  const formData = new FormData(document.getElementById("userForm"));
  const isEdit = document.getElementById("userId").value !== "";
  formData.append("action", isEdit ? "update" : "add");

  fetch("api/user_operations.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showToast("Success", data.message, "success");
        loadUsersData();
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("addUserModal")
        );
        if (modal) {
          modal.hide();
        }
      } else {
        showToast("Error", data.error || "Operation failed", "error");
      }
    })
    .catch((error) => {
      showToast("Error", "Network error occurred", "error");
    });
}

function editUser(username) {
  fetch("api/user_operations.php?action=get")
    .then((response) => response.json())
    .then((data) => {
      const user = data.find((u) => u.username === username);
      if (user) {
        document.getElementById("userId").value = user.username;
        document.getElementById("fullName").value = user.fullName;
        document.getElementById("username").value = user.username;
        document.getElementById("username").readOnly = true;
        document.getElementById("password").required = false;
        document.getElementById("passwordHelp").style.display = "block";
        document.getElementById("role").value = user.role;

        document.getElementById("addUserModalLabel").innerHTML =
          '<i class="fa fa-edit me-2"></i>Edit User';
        new bootstrap.Modal(document.getElementById("addUserModal")).show();
      }
    });
}

function showDeleteModal(username, fullName) {
  currentUserId = username;
  document.getElementById("deleteUserName").textContent = fullName;
  new bootstrap.Modal(document.getElementById("deleteModal")).show();
}

function deleteUser(username) {
  const formData = new FormData();
  formData.append("action", "delete");
  formData.append("username", username);

  fetch("api/user_operations.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("deleteModal")
      );
      if (modal) {
        modal.hide();
      }

      if (data.success) {
        showToast("Success", data.message, "success");
        loadUsersData();
      } else {
        showToast("Error", data.error || "Delete failed", "error");
      }
    })
    .catch((error) => {
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("deleteModal")
      );
      if (modal) {
        modal.hide();
      }
      showToast("Error", "Network error occurred", "error");
    });
}

function resetForm() {
  document.getElementById("userForm").reset();
  document.getElementById("userId").value = "";
  document.getElementById("username").readOnly = false;
  document.getElementById("password").required = true;
  document.getElementById("passwordHelp").style.display = "none";
  document.getElementById("addUserModalLabel").innerHTML =
    '<i class="fa fa-plus me-2"></i>Add User';
}

function showToast(title, message, type) {
  const toastType = type === 'error' ? 'error' : type;
  Toast[toastType](message);
}

document
  .getElementById("addUserModal")
  .addEventListener("hidden.bs.modal", function () {
    resetForm();
  });

document
  .getElementById("addUserModal")
  .addEventListener("hide.bs.modal", function () {
    setTimeout(() => {
      document.body.classList.remove("modal-open");
      const backdrop = document.querySelector(".modal-backdrop");
      if (backdrop) {
        backdrop.remove();
      }
    }, 100);
  });

document
  .getElementById("deleteModal")
  .addEventListener("hide.bs.modal", function () {
    setTimeout(() => {
      document.body.classList.remove("modal-open");
      const backdrop = document.querySelector(".modal-backdrop");
      if (backdrop) {
        backdrop.remove();
      }
    }, 100);
  });
