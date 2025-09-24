let currentNasId = null;

$(document).ready(function () {
  loadNasData();

  // Reset modal to Add mode when opened via Add button
  $(document).on("click", '[data-bs-target="#addNasModal"]', function () {
    resetForm();
  });

  // Reset form when modal is closed
  $("#addNasModal").on("hidden.bs.modal", function () {
    resetForm();
  });

  // Logo file preview
  $("#logoUpload").on("change", function (e) {
    const file = e.target.files[0];
    const preview = $("#logoPreview");
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        preview.html(`<img src="${e.target.result}" class="img-fluid" style="max-height:80px;" />`);
      };
      reader.readAsDataURL(file);
    } else {
      preview.html('<span class="text-muted">No logo uploaded</span>');
    }
  });

  // Form submission
  $("#nasForm").on("submit", function (e) {
    e.preventDefault();
    saveNas();
  });

  // Delete confirmation
  $("#confirmDeleteBtn").on("click", function () {
    deleteNas(currentNasId);
  });


});

window.loadNasData = function() {
  console.log('Loading NAS data...');
  fetch("api/nas_operations.php?action=get")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log('NAS data loaded:', data);
      
      // Destroy existing DataTable if it exists
      if ($.fn.DataTable.isDataTable("#nasTable")) {
        $("#nasTable").DataTable().destroy();
      }

      const tbody = document.getElementById("nasTableBody");
      tbody.innerHTML = "";

      if (Array.isArray(data) && data.length > 0) {
        data.forEach((nas) => {
          const logoDisplay = nas.logo
            ? `<img src="${nas.logo}" alt="Logo" style="width: 24px; height: 24px; margin-right: 8px; border-radius: 4px;">`
            : `<i class="fa fa-server" style="margin-right: 8px; color: #6c757d;"></i>`;

          const row = `
                      <tr>
                          <td>
                              <button class="btn btn-sm btn-info me-1" onclick="window.editNas('${nas.id}')" title="Edit NAS"><i class="fa fa-edit"></i></button>
                              <button class="btn btn-sm btn-danger" onclick="window.showDeleteModal('${nas.id}', '${nas.nas_name}')" title="Delete NAS"><i class="fa fa-trash"></i></button>
                          </td>
                          <td>${logoDisplay}${nas.nas_name}</td>
                          <td>${nas.hotspot_name}</td>
                          <td>${nas.nas_ip_port}</td>
                      </tr>
                  `;
          tbody.innerHTML += row;
        });
      } else {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No NAS configured</td></tr>';
      }

      // Initialize DataTable after data is loaded
      $("#nasTable").DataTable({
        responsive: true,
        pageLength: 5,
        lengthMenu: [
          [5, 10, 25, 50, 100],
          [5, 10, 25, 50, 100],
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
      console.error('Error loading NAS data:', error);
      showToast("Error", "Failed to load NAS data: " + error.message, "error");
    });
}

window.saveNas = function() {
  console.log('Saving NAS...');
  
  // Validate required fields
  const requiredFields = ['nasName', 'nasIpPort', 'username', 'password', 'hotspotName'];
  for (let field of requiredFields) {
    const element = document.getElementById(field);
    if (!element || !element.value.trim()) {
      showToast('Error', `${field.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())} is required`, 'error');
      return;
    }
  }
  
  const formData = new FormData(document.getElementById("nasForm"));
  const isEdit = document.getElementById("nasId").value !== "";
  formData.append("action", isEdit ? "update" : "add");

  // Handle live report checkbox
  formData.set(
    "live_report",
    document.getElementById("liveReportSwitch").checked ? "on" : "off"
  );

  // Handle logo upload
  const logoFile = document.getElementById("logoUpload").files[0];
  if (logoFile) {
    const reader = new FileReader();
    reader.onload = function (e) {
      formData.append("logo", e.target.result);
      submitForm(formData, isEdit);
    };
    reader.readAsDataURL(logoFile);
  } else {
    submitForm(formData, isEdit);
  }
}

window.submitForm = function(formData, isEdit) {
  console.log('Submitting form...', isEdit ? 'Update' : 'Add');
  
  fetch("api/nas_operations.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log('Form submission response:', data);
      if (data.success) {
        showToast("Success", data.message, "success");
        loadNasData();
        $('#addNasModal').modal('hide');
      } else {
        showToast("Error", data.error || "Operation failed", "error");
      }
    })
    .catch((error) => {
      console.error('Form submission error:', error);
      showToast("Error", "Network error occurred: " + error.message, "error");
    });
}

window.editNas = function(id) {
  console.log('Editing NAS:', id);
  
  fetch("api/nas_operations.php?action=get")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      const nas = data.find((n) => n.id === id);
      if (nas) {
        console.log('Found NAS for editing:', nas);
        
        document.getElementById("nasId").value = nas.id;
        document.getElementById("nasName").value = nas.nas_name || '';
        document.getElementById("nasIpPort").value = nas.nas_ip_port || '';
        document.getElementById("username").value = nas.username || '';
        document.getElementById("password").value = nas.password || '';
        document.getElementById("hotspotName").value = nas.hotspot_name || '';
        document.getElementById("dnsName").value = nas.dns_name || '';
        document.getElementById("currency").value = nas.currency || 'BDT';
        document.getElementById("sessionTimeout").value = nas.session_timeout || '';
        document.getElementById("liveReportSwitch").checked = nas.live_report === "on";

        if (nas.logo) {
          document.getElementById(
            "logoPreview"
          ).innerHTML = `<img src="${nas.logo}" class="img-fluid" style="max-height: 80px;">`;
        } else {
          document.getElementById("logoPreview").innerHTML = '<span class="text-muted">No logo uploaded</span>';
        }

        document.getElementById("addNasModalLabel").innerHTML = '<i class="fa fa-edit me-2"></i>Edit NAS';
        
        // Show modal
        $('#addNasModal').modal('show');
      } else {
        showToast('Error', 'NAS not found', 'error');
      }
    })
    .catch((error) => {
      console.error('Error loading NAS for edit:', error);
      showToast('Error', 'Failed to load NAS data for editing', 'error');
    });
}

window.showDeleteModal = function(id, name) {
  console.log('Showing delete modal for:', id, name);
  
  if (!id || !name) {
    showToast('Error', 'Invalid NAS data', 'error');
    return;
  }
  
  currentNasId = id;
  document.getElementById("deleteNasName").textContent = name;
  
  $('#deleteModal').modal('show');
}

window.deleteNas = function(id) {
  console.log('Deleting NAS:', id);
  
  if (!id) {
    showToast('Error', 'Invalid NAS ID', 'error');
    return;
  }
  
  const formData = new FormData();
  formData.append("action", "delete");
  formData.append("id", id);

  fetch("api/nas_operations.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log('Delete response:', data);
      if (data.success) {
        showToast("Success", data.message, "success");
        loadNasData();
        $('#deleteModal').modal('hide');
      } else {
        showToast("Error", data.error || "Delete failed", "error");
      }
    })
    .catch((error) => {
      console.error('Delete error:', error);
      showToast("Error", "Network error occurred: " + error.message, "error");
    });
}

window.resetForm = function() {
  document.getElementById("nasForm").reset();
  document.getElementById("nasId").value = "";
  document.getElementById("logoPreview").innerHTML =
    '<span class="text-muted">No logo uploaded</span>';
  document.getElementById("addNasModalLabel").innerHTML =
    '<i class="fa fa-plus me-2"></i>Add NAS';
}

window.showToast = function(title, message, type) {
  if (window.Toast) {
    const toastType = type === 'error' ? 'error' : type;
    Toast[toastType](message);
  } else {
    alert(message);
  }
}
