document.addEventListener("DOMContentLoaded", () => {
  
  const modal = document.getElementById("doctorModal");
  const cancelBtn = document.getElementById("doctorCancelBtn");
  const form = document.getElementById("doctorForm");
  const modalTitle = document.getElementById("doctorModalTitle");
  const tableBody = document.querySelector("#doctorTable tbody");
  const searchInput = document.getElementById("doctorSearch");
  const addDoctorBtn = document.getElementById("addDoctorBtn");
  
  let editingVetId = null; 
  let doctors = []; 
  let currentPage = 1;
  let itemsPerPage = 15;

  // Initialize sidebar and shared functionality
  initializeSidebarToggle();
  preventBackNavigation();

  loadDoctors();

  addDoctorBtn.addEventListener("click", () => openModal("Add Doctor"));
  cancelBtn.addEventListener("click", closeModal);
  
  window.addEventListener("click", e => { 
    if (e.target === modal) closeModal(); 
  });

  form.addEventListener("submit", handleFormSubmit);

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      currentPage = 1; 
      renderTable();
    });
  }

  const filterButtons = document.querySelectorAll(".filter-btn");
  filterButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      handleFilter(btn, filterButtons);
      currentPage = 1;
      renderTable();
    });
  });

  document.getElementById("prevPage").addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      renderTable();
    }
  });

  document.getElementById("nextPage").addEventListener("click", () => {
    const filteredData = getFilteredDoctors();
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    if (currentPage < totalPages) {
      currentPage++;
      renderTable();
    }
  });

  document.getElementById("itemsPerPage").addEventListener("change", (e) => {
    itemsPerPage = e.target.value === 'all' ? Infinity : parseInt(e.target.value);
    currentPage = 1; 
    renderTable();
  });

  // Load doctors from server
  function loadDoctors() {
    fetch('api_doctors.php?action=getDoctors')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          doctors = data.data;
          updateSummaryCards();
          renderTable();
        } else {
          console.error('Failed to load doctors:', data.message);
        }
      })
      .catch(error => console.error('Error loading doctors:', error));
  }

  function openModal(title, doctorData = null) {
    modalTitle.textContent = title;
    modal.classList.add("show");

    if (doctorData) {
      document.getElementById("vetId").value = doctorData.vet_id || '';
      document.getElementById("doctorName").value = doctorData.name || '';
      document.getElementById("doctorEmail").value = doctorData.email || '';
      document.getElementById("doctorContact").value = doctorData.contact || '';
      
      // Uncheck all checkboxes first
      document.querySelectorAll('.time-checkbox').forEach(cb => cb.checked = false);
      document.querySelectorAll('.day-checkbox').forEach(cb => cb.checked = false);
      
      // Check the appropriate time checkboxes
      const times = Array.isArray(doctorData.available_times) ? doctorData.available_times : (doctorData.available_times ? doctorData.available_times.split(',') : []);
      times.forEach(time => {
        const checkbox = document.querySelector(`.time-checkbox[value="${time.trim()}"]`);
        if (checkbox) checkbox.checked = true;
      });
      
      // Check the appropriate day checkboxes
      const days = Array.isArray(doctorData.available_dates) ? doctorData.available_dates : (doctorData.available_dates ? doctorData.available_dates.split(',') : []);
      days.forEach(day => {
        const checkbox = document.querySelector(`.day-checkbox[value="${day.trim()}"]`);
        if (checkbox) checkbox.checked = true;
      });
      
      editingVetId = doctorData.vet_id;
    } else {
      form.reset();
      document.querySelectorAll('.time-checkbox').forEach(cb => cb.checked = false);
      document.querySelectorAll('.day-checkbox').forEach(cb => cb.checked = false);
      editingVetId = null;
    }
  }

  function closeModal() {
    modal.classList.remove("show");
    form.reset();
    editingVetId = null; 
  }

  function handleFormSubmit(e) {
    e.preventDefault();

    const name = document.getElementById("doctorName").value.trim();
    const email = document.getElementById("doctorEmail").value.trim();
    const contact = document.getElementById("doctorContact").value.trim();
    const status = 'On Duty'; // Default status
    
    // Get selected times from checkboxes
    const selectedTimes = Array.from(document.querySelectorAll('.time-checkbox:checked')).map(cb => cb.value);
    const available_times = selectedTimes.join(',');
    
    // Get selected days from checkboxes
    const selectedDays = Array.from(document.querySelectorAll('.day-checkbox:checked')).map(cb => cb.value);
    const available_dates = selectedDays.join(',');

    if (!name || !email || !contact) {
      toast.warning("Please fill in all required fields");
      return;
    }

    if (selectedTimes.length === 0) {
      toast.warning("Please select at least one available time");
      return;
    }

    if (selectedDays.length === 0) {
      toast.warning("Please select at least one available day");
      return;
    }

    const formData = new FormData();
    
    if (editingVetId) {
      // Update doctor
      formData.append('action', 'updateDoctor');
      formData.append('vet_id', editingVetId);
    } else {
      // Add new doctor
      formData.append('action', 'addDoctor');
    }

    formData.append('name', name);
    formData.append('email', email);
    formData.append('contact', contact);
    formData.append('status', status);
    formData.append('available_dates', available_dates);
    formData.append('available_times', available_times);

    fetch('api_doctors.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        toast.success(data.message);
        closeModal();
        loadDoctors();
      } else {
        toast.error('Error: ' + data.message);
      }
    })
    .catch(error => console.error('Error:', error));
  }

  function renderTable() {
    tableBody.innerHTML = '';

    let data = getFilteredDoctors();

    const totalItems = data.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    const pageData = data.slice(startIndex, endIndex);

    pageData.forEach((doctor) => {
      const row = createTableRow(doctor);
      tableBody.appendChild(row);
    });

    updatePaginationUI(totalItems, startIndex, endIndex, totalPages);

    if (totalItems === 0) {
      const emptyRow = document.createElement('tr');
      emptyRow.innerHTML = '<td colspan="8" style="text-align: center; padding: 30px; color: #999;">No doctors found</td>';
      tableBody.appendChild(emptyRow);
    }
  }

  function getFilteredDoctors() {
    let filtered = [...doctors];

    const searchTerm = searchInput.value.toLowerCase();
    if (searchTerm) {
      filtered = filtered.filter(doctor => {
        return (
          doctor.name.toLowerCase().includes(searchTerm) ||
          doctor.email.toLowerCase().includes(searchTerm) ||
          doctor.contact.toLowerCase().includes(searchTerm)
        );
      });
    }

    const activeFilter = document.querySelector(".filter-btn.active");
    const statusFilter = activeFilter?.dataset.status?.toLowerCase() || 'all';
    if (statusFilter !== 'all') {
      filtered = filtered.filter(doctor => doctor.status.toLowerCase() === statusFilter);
    }

    return filtered;
  }

  function createTableRow(doctor) {
    const row = document.createElement("tr");
    
    const availableDays = Array.isArray(doctor.available_dates) 
      ? doctor.available_dates.join(', ') 
      : (doctor.available_dates || 'N/A');
    
    const availableTimes = Array.isArray(doctor.available_times) 
      ? doctor.available_times.join(', ') 
      : (doctor.available_times || 'N/A');

    row.innerHTML = `
      <td>${doctor.name}</td>
      <td>${doctor.email}</td>
      <td>${doctor.contact}</td>
      <td>
        <select class="status-dropdown" data-vet-id="${doctor.vet_id}">
          <option value="On Duty" ${doctor.status === "On Duty" ? "selected" : ""}>✓ On Duty</option>
          <option value="Off Duty" ${doctor.status === "Off Duty" ? "selected" : ""}>○ Off Duty</option>
          <option value="On Leave" ${doctor.status === "On Leave" ? "selected" : ""}>✋ On Leave</option>
        </select>
      </td>
      <td>${availableDays}</td>
      <td>${availableTimes}</td>
      <td>${new Date(doctor.created_at).toLocaleDateString()}</td>
      <td>
        <button class="btn edit-btn" data-vet-id="${doctor.vet_id}" style="margin-right: 5px;">Edit</button>
        <button class="btn delete-btn" data-vet-id="${doctor.vet_id}" style="background-color: #f44336;">Delete</button>
      </td>
    `;

    const statusDropdown = row.querySelector(".status-dropdown");
    const editBtn = row.querySelector(".edit-btn");
    const deleteBtn = row.querySelector(".delete-btn");

    // Update dropdown color based on status
    updateDropdownColor(statusDropdown, doctor.status);

    // Status change from dropdown
    statusDropdown.addEventListener("change", () => {
      const newStatus = statusDropdown.value;
      const oldStatus = doctor.status;

      if (newStatus !== oldStatus) {
        const confirmAction = confirm(`Are you sure you want to change status to "${newStatus}"?`);
        if (!confirmAction) {
          statusDropdown.value = oldStatus;
          updateDropdownColor(statusDropdown, oldStatus);
          return;
        }

        updateDoctorStatus(doctor.vet_id, newStatus, statusDropdown);
      }
    });

    editBtn.addEventListener("click", () => {
      openModal("Edit Doctor", doctor);
    });

    deleteBtn.addEventListener("click", () => {
      if (confirm("Are you sure you want to delete this doctor?")) {
        deleteDoctor(doctor.vet_id);
      }
    });

    return row;
  }

  function deleteDoctor(vet_id) {
    const formData = new FormData();
    formData.append('action', 'deleteDoctor');
    formData.append('vet_id', vet_id);

    fetch('api_doctors.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        toast.success(data.message);
        loadDoctors();
      } else {
        toast.error('Error: ' + data.message);
      }
    })
    .catch(error => console.error('Error:', error));
  }

  function updateDoctorStatus(vet_id, newStatus, dropdown) {
    // Find the current doctor to get their details
    const doctor = doctors.find(d => d.vet_id === vet_id);
    if (!doctor) return;

    const formData = new FormData();
    formData.append('action', 'updateDoctor');
    formData.append('vet_id', vet_id);
    formData.append('name', doctor.name);
    formData.append('email', doctor.email);
    formData.append('contact', doctor.contact);
    formData.append('status', newStatus);
    formData.append('available_dates', Array.isArray(doctor.available_dates) ? doctor.available_dates.join(',') : doctor.available_dates);
    formData.append('available_times', Array.isArray(doctor.available_times) ? doctor.available_times.join(',') : doctor.available_times);

    fetch('api_doctors.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        updateDropdownColor(dropdown, newStatus);
        loadDoctors();
      } else {
        toast.error('Error: ' + data.message);
        // Revert the dropdown
        if (doctor) {
          dropdown.value = doctor.status;
          updateDropdownColor(dropdown, doctor.status);
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      toast.error('Error updating doctor status');
    });
  }

  function updateDropdownColor(dropdown, status) {
    let bg = "";

    switch (status) {
      case "On Duty":
        bg = "#4caf50";
        break;
      case "Off Duty":
        bg = "#ff9800";
        break;
      case "On Leave":
        bg = "#2196f3";
        break;
      default:
        bg = "gray";
    }

    dropdown.style.backgroundColor = bg;
    dropdown.style.color = "white";
  }

  function updatePaginationUI(totalItems, startIndex, endIndex, totalPages) {
    document.getElementById("showingStart").textContent = totalItems > 0 ? startIndex + 1 : 0;
    document.getElementById("showingEnd").textContent = endIndex;
    document.getElementById("totalRecords").textContent = totalItems;

    document.getElementById("prevPage").disabled = currentPage === 1;
    document.getElementById("nextPage").disabled = currentPage === totalPages || totalPages === 0;

    renderPageNumbers(totalPages);
  }

  function renderPageNumbers(totalPages) {
    const pageNumbersDiv = document.getElementById("pageNumbers");
    pageNumbersDiv.innerHTML = '';

    if (totalPages <= 1) return;

    const maxVisible = 7;
    let pages = [];

    if (totalPages <= maxVisible) {
      pages = Array.from({ length: totalPages }, (_, i) => i + 1);
    } else {
      if (currentPage <= 3) {
        pages = [1, 2, 3, 4, '...', totalPages];
      } else if (currentPage >= totalPages - 2) {
        pages = [1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
      } else {
        pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
      }
    }

    pages.forEach(page => {
      const pageBtn = document.createElement('div');
      if (page === '...') {
        pageBtn.className = 'page-number ellipsis';
        pageBtn.textContent = '...';
      } else {
        pageBtn.className = 'page-number';
        if (page === currentPage) {
          pageBtn.classList.add('active');
        }
        pageBtn.textContent = page;
        pageBtn.addEventListener('click', () => {
          currentPage = page;
          renderTable();
        });
      }
      pageNumbersDiv.appendChild(pageBtn);
    });
  }

  function updateSummaryCards() {
    let total = 0;
    let on_duty = 0;
    let off_duty = 0;
    let on_leave = 0;

    doctors.forEach(doctor => {
      total++;
      if (doctor.status === 'On Duty') {
        on_duty++;
      } else if (doctor.status === 'Off Duty') {
        off_duty++;
      } else if (doctor.status === 'On Leave') {
        on_leave++;
      }
    });

    document.getElementById("totalDoctors").textContent = total;
    document.getElementById("onDutyDoctors").textContent = on_duty;
    document.getElementById("offDutyDoctors").textContent = off_duty;
    document.getElementById("onLeaveDoctors").textContent = on_leave;
  }

  function handleFilter(btn, filterButtons) {
    filterButtons.forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
  }

});
