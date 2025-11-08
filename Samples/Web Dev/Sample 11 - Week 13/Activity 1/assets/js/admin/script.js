document.addEventListener("DOMContentLoaded", () => {
  
  const addBtn = document.getElementById("addAdminBtn");
  const modal = document.getElementById("adminModal");
  const cancelBtn = document.getElementById("adminCancelBtn");
  const form = document.getElementById("adminForm");
  const modalTitle = document.getElementById("adminModalTitle");
  const tableBody = document.querySelector("#adminTable tbody");
  const searchInput = document.getElementById("adminSearch");
  
  const bookingTypeSelect = document.getElementById("adminBookingType");
  const registeredFields = document.getElementById("registeredFields");
  const guestFields = document.getElementById("guestFields");
  const doctorSelect = document.getElementById("adminDoctor");
  const dateInput = document.getElementById("adminDate");
  const timeSelect = document.getElementById("adminTime");
  const statusSelectModal = document.getElementById("adminStatus");

  let editingId = null; 
  let appointments = []; 
  let doctors = [];
  let currentPage = 1;
  let itemsPerPage = 15;
  let sortColumn = 'date';
  let sortDirection = 'asc';
  let currentActiveFilter = 'all';
  
  // Get all filter buttons
  const filterButtons = document.querySelectorAll(".filter-btn");
  
  // Set up filter button listeners
  filterButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      filterButtons.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      currentActiveFilter = btn.dataset.status;
      currentPage = 1;
      renderTable();
    });
  });
  
  // Ensure the first filter button (All) is marked as active on initial load
  if (filterButtons[0]) {
    filterButtons[0].classList.add("active");
    currentActiveFilter = filterButtons[0].dataset.status;
  }

  loadAppointments();
  loadAvailableDoctors();

  addBtn.addEventListener("click", () => openModal("Add Appointment"));
  cancelBtn.addEventListener("click", closeModal);
  
  window.addEventListener("click", e => { 
    if (e.target === modal) closeModal(); 
  });

  form.addEventListener("submit", handleFormSubmit);

  // Initialize custom date picker for admin form
  let adminDatePicker = null;
  function initializeAdminDatePicker() {
    if (adminDatePicker) {
      adminDatePicker.updateAvailableDates([]);
    } else {
      adminDatePicker = new CustomDatePicker(dateInput, []);
    }
  }

  // Call it on modal open
  addBtn.addEventListener("click", () => {
    openModal("Add Appointment");
    setTimeout(() => initializeAdminDatePicker(), 0);
  });

  // Handle booking type change
  bookingTypeSelect.addEventListener("change", () => {
    if (bookingTypeSelect.value === "registered") {
      registeredFields.style.display = "block";
      guestFields.style.display = "none";
    } else {
      registeredFields.style.display = "none";
      guestFields.style.display = "block";
    }
  });

  doctorSelect.addEventListener("change", updateAvailableTimes);
  dateInput.addEventListener("change", () => {
    loadAvailableDoctors();
    updateAvailableTimes();
  });

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      currentPage = 1; 
      renderTable();
    });
  }

  document.getElementById("prevPage").addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      renderTable();
    }
  });

  document.getElementById("nextPage").addEventListener("click", () => {
    const filteredData = getFilteredAppointments();
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

  document.querySelectorAll("th.sortable").forEach(th => {
    th.addEventListener("click", () => {
      const column = th.dataset.sort;
      
      if (sortColumn === column) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        sortColumn = column;
        sortDirection = 'asc';
      }
      
      document.querySelectorAll("th.sortable").forEach(header => {
        header.classList.remove('asc', 'desc');
      });
      th.classList.add(sortDirection);
      
      currentPage = 1;
      renderTable();
    });
  });


  // Load appointments from server
  function loadAppointments() {
    fetch('api_appointments.php?action=getAppointments')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          appointments = data.data;
          updateSummaryCards();
          renderTable();
        } else {
          console.error('Failed to load appointments:', data.message);
        }
      })
      .catch(error => console.error('Error loading appointments:', error));
  }

  // Load available doctors from server
  function loadAvailableDoctors() {
    const selectedDate = dateInput.value;
    const url = selectedDate 
      ? `api_appointments.php?action=getAvailableDoctors&date=${selectedDate}`
      : 'api_appointments.php?action=getAvailableDoctors';
    
    fetch(url)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          doctors = data.data;
          updateDoctorDropdown();
        } else {
          console.error('Failed to load doctors:', data.message);
        }
      })
      .catch(error => console.error('Error loading doctors:', error));
  }

  // Update doctor dropdown with available doctors
  function updateDoctorDropdown() {
    const currentValue = doctorSelect.value;
    doctorSelect.innerHTML = '<option value="" disabled selected>Select doctor</option>';
    
    doctors.forEach(doctor => {
      const option = document.createElement('option');
      option.value = doctor.name;
      option.textContent = doctor.name;
      doctorSelect.appendChild(option);
    });
    
    if (currentValue && doctors.some(d => d.name === currentValue)) {
      doctorSelect.value = currentValue;
    }
  }

  function updateSummaryCards() {
    const total = appointments.length;
    const pending = appointments.filter(apt => apt.status === 'Pending').length;
    const confirmed = appointments.filter(apt => apt.status === 'Confirmed').length;
    const cancelled = appointments.filter(apt => apt.status === 'Cancelled').length;

    document.getElementById("totalAppointments").textContent = total;
    document.getElementById("pendingAppointments").textContent = pending;
    document.getElementById("confirmedAppointments").textContent = confirmed;
    document.getElementById("cancelledAppointments").textContent = cancelled;
  }

  function openModal(title, appointmentData = null) {
    modalTitle.textContent = title;
    modal.classList.add("show");

    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const dd = String(today.getDate()).padStart(2, "0");
    dateInput.setAttribute("min", `${yyyy}-${mm}-${dd}`);
    
    if (appointmentData) {
      // Set booking type and show/hide appropriate fields
      const bookingType = appointmentData.booking_type || 'guest';
      document.getElementById("adminBookingType").value = bookingType;
      
      if (bookingType === 'registered') {
        registeredFields.style.display = "block";
        guestFields.style.display = "none";
        document.getElementById("adminUserId").value = appointmentData.user_id || '';
      } else {
        registeredFields.style.display = "none";
        guestFields.style.display = "block";
        document.getElementById("adminGuestName").value = appointmentData.guest_name || '';
        document.getElementById("adminGuestEmail").value = appointmentData.guest_email || '';
        document.getElementById("adminGuestContact").value = appointmentData.guest_contact || '';
      }

      document.getElementById("adminPetName").value = appointmentData.pet_name || '';
      document.getElementById("adminDoctor").value = appointmentData.vet || '';
      document.getElementById("adminService").value = appointmentData.service || '';
      document.getElementById("adminDate").value = appointmentData.appt_date || '';
      document.getElementById("adminTime").value = appointmentData.appt_time || '';
      
      editingId = appointmentData.id;
      setTimeout(() => updateAvailableTimes(), 0);
    }
    else {
      // Set defaults for new appointment
      document.getElementById("adminBookingType").value = 'guest';
      registeredFields.style.display = "none";
      guestFields.style.display = "block";
      editingId = null;
      form.reset();
    }
  }

  function closeModal() {
    modal.classList.remove("show");
    form.reset();
    editingId = null; 
  }

  function handleFormSubmit(e) {
    e.preventDefault();

    const bookingType = document.getElementById("adminBookingType").value;
    const petName = capitalizeWords(document.getElementById("adminPetName").value.trim());
    const doctor = document.getElementById("adminDoctor").value.trim();
    const service = document.getElementById("adminService").value.trim();
    const date = document.getElementById("adminDate").value;
    const time = document.getElementById("adminTime").value;
    
    // If editing, keep the existing status; if new, default to Pending
    let status = 'Pending';
    if (editingId) {
      const existingAppointment = appointments.find(apt => apt.id === editingId);
      status = existingAppointment ? existingAppointment.status : 'Pending';
    }

    if (!petName || !doctor || !service || !date || !time) {
      toast.warning("Please fill in all required fields");
      return;
    }

    const formData = new FormData();
    formData.append('action', editingId ? 'updateAppointment' : 'addAppointment');
    formData.append('booking_type', bookingType);
    
    if (bookingType === 'registered') {
      const userId = document.getElementById("adminUserId").value.trim();
      if (!userId) {
        toast.warning("Please enter User ID");
        return;
      }
      formData.append('user_id', userId);
    } else {
      const guestName = document.getElementById("adminGuestName").value.trim();
      const guestEmail = document.getElementById("adminGuestEmail").value.trim();
      const guestContact = document.getElementById("adminGuestContact").value.trim();
      
      if (!guestName || !guestEmail || !guestContact) {
        toast.warning("Please fill in all guest information");
        return;
      }
      
      formData.append('guest_name', guestName);
      formData.append('guest_email', guestEmail);
      formData.append('guest_contact', guestContact);
    }
    
    formData.append('pet_name', petName);
    formData.append('vet', doctor);
    formData.append('service', service);
    formData.append('date', date);
    formData.append('time', time);
    formData.append('status', status);

    if (editingId) {
      formData.append('id', editingId);
    }

    fetch('api_appointments.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        toast.success(data.message);
        closeModal();
        loadAppointments();
      } else {
        toast.error('Error: ' + data.message);
      }
    })
    .catch(error => console.error('Error:', error));
  }

  function timeToMinutes(timeStr) {
    const [hours, minutes] = timeStr.split(":").map(Number);
    return hours * 60 + minutes;
  }

  function updateAvailableTimes() {
    const selectedDoctor = doctorSelect.value;
    const selectedDate = dateInput.value;
    const dateTimeSection = document.querySelector('.date-time-section');

    timeSelect.innerHTML = '<option value="" disabled selected>Select time</option>';

    // If no doctor selected, hide date-time section
    if (!selectedDoctor) {
      dateTimeSection.classList.remove('visible');
      dateInput.removeAttribute('required');
      timeSelect.removeAttribute('required');
      return;
    }

    // Doctor selected, show date-time section
    dateTimeSection.classList.add('visible');
    dateInput.setAttribute('required', 'required');
    timeSelect.setAttribute('required', 'required');

    // Update date picker with available dates for this doctor
    const doctor = doctors.find(d => d.name === selectedDoctor);
    if (doctor && doctor.available_dates && adminDatePicker) {
      const availableDates = getAvailableDatesForDoctor(doctor.available_dates);
      adminDatePicker.updateAvailableDates(availableDates);
    }

    // If no date selected, don't populate times yet
    if (!selectedDate) {
      return;
    }

    // Populate time slots based on doctor availability
    populateTimeSlotsForDoctor(selectedDoctor, selectedDate);
  }

  // Populate time slots based on doctor's schedule
  function populateTimeSlotsForDoctor(doctorName, selectedDate) {
    // Find the doctor from the loaded doctors list
    const doctor = doctors.find(d => d.name === doctorName);
    
    if (!doctor || !doctor.available_times) {
      // Fallback to default hours if no doctor info
      const defaultTimes = generateTimeSlots(['8-17']);
      defaultTimes.forEach(time => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = formatTime(time);
        timeSelect.appendChild(option);
      });
      return;
    }

    // Generate time slots based on doctor's available_times
    const timeSlots = generateTimeSlots(doctor.available_times);

    // Fetch booked times for this doctor and date
    fetch(`api_appointments.php?action=getBookedTimes&date=${selectedDate}&doctor=${doctorName}`)
      .then(response => response.json())
      .then(data => {
        const bookedTimes = data.data || [];

        // Populate dropdown with available times only
        timeSlots.forEach(time => {
          if (!bookedTimes.includes(time)) {
            const option = document.createElement('option');
            option.value = time;
            option.textContent = formatTime(time);
            timeSelect.appendChild(option);
          }
        });
      })
      .catch(err => {
        console.error('Error fetching booked times:', err);
        // If fetch fails, still show all available times
        timeSlots.forEach(time => {
          const option = document.createElement('option');
          option.value = time;
          option.textContent = formatTime(time);
          timeSelect.appendChild(option);
        });
      });
  }

  function renderTable() {
    tableBody.innerHTML = '';

    let data = getFilteredAppointments();
    data = sortAppointments(data);

    const totalItems = data.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    const pageData = data.slice(startIndex, endIndex);

    pageData.forEach((appointment) => {
      const row = createTableRow(appointment);
      tableBody.appendChild(row);
    });

    updatePaginationUI(totalItems, startIndex, endIndex, totalPages);

    if (totalItems === 0) {
      const emptyRow = document.createElement('tr');
      emptyRow.innerHTML = '<td colspan="8" style="text-align: center; padding: 30px; color: #999;">No appointments found</td>';
      tableBody.appendChild(emptyRow);
    }
  }

  function getFilteredAppointments() {
    let filtered = [...appointments];

    const searchTerm = searchInput.value.toLowerCase();
    if (searchTerm) {
      filtered = filtered.filter(apt => {
        return (
          apt.pet_name.toLowerCase().includes(searchTerm) ||
          apt.service.toLowerCase().includes(searchTerm) ||
          (apt.vet && apt.vet.toLowerCase().includes(searchTerm)) ||
          (apt.guest_name && apt.guest_name.toLowerCase().includes(searchTerm)) ||
          (apt.user_name && apt.user_name.toLowerCase().includes(searchTerm))
        );
      });
    }

    // Use the currentActiveFilter variable instead of DOM query
    if (currentActiveFilter && currentActiveFilter.toLowerCase() !== 'all') {
      filtered = filtered.filter(apt => apt.status.toLowerCase() === currentActiveFilter.toLowerCase());
    }

    return filtered;
  }

  function sortAppointments(data) {
    return data.sort((a, b) => {
      let aVal, bVal;

      if (sortColumn === 'date') {
        aVal = new Date(`${a.appt_date}T${a.appt_time}`);
        bVal = new Date(`${b.appt_date}T${b.appt_time}`);
      } else if (sortColumn === 'time') {
        aVal = timeToMinutes(a.appt_time);
        bVal = timeToMinutes(b.appt_time);
      }

      if (sortDirection === 'asc') {
        return aVal > bVal ? 1 : -1;
      } else {
        return aVal < bVal ? 1 : -1;
      }
    });
  }

  function createTableRow(appointment) {
    const row = document.createElement("tr");
    
    // Determine owner/customer name based on booking type
    let ownerName = '-';
    if (appointment.booking_type === 'guest') {
      ownerName = appointment.guest_name || '-';
    } else if (appointment.booking_type === 'registered') {
      ownerName = appointment.user_name || '-';
    }
    
    row.innerHTML = `
      <td>${appointment.pet_name}</td>
      <td>${ownerName}</td>
      <td>${appointment.vet || '-'}</td>
      <td>${appointment.service}</td>
      <td>${appointment.appt_date}</td>
      <td>${appointment.appt_time}</td>
      <td>
        <select class="status-dropdown" data-appt-id="${appointment.id}">
          <option value="Pending" ${appointment.status === "Pending" ? "selected" : ""}>Pending</option>
          <option value="Confirmed" ${appointment.status === "Confirmed" ? "selected" : ""}>Confirmed</option>
          <option value="Completed" ${appointment.status === "Completed" ? "selected" : ""}>Completed</option>
          <option value="Cancelled" ${appointment.status === "Cancelled" ? "selected" : ""}>Cancelled</option>
        </select>
      </td>
      <td>
        <div style="display:flex; gap:8px;">
          <button class="btn edit-btn" data-appt-id="${appointment.id}">Edit</button>
          <button class="btn delete-btn" data-appt-id="${appointment.id}">Delete</button>
        </div>
      </td>
    `;

    const statusDropdown = row.querySelector(".status-dropdown");
    const editBtn = row.querySelector(".edit-btn");
    const deleteBtn = row.querySelector('.delete-btn');

    // disable dropdown for finished states
    if (appointment.status === "Completed" || appointment.status === "Cancelled") {
      statusDropdown.disabled = true;
      editBtn.style.display = "none";
    }

    // status change from dropdown
    statusDropdown.addEventListener("change", () => {
      const newStatus = statusDropdown.value;
      
      if (newStatus === "Cancelled" || newStatus === "Completed") {
        const confirmAction = confirm("Are you sure you want to confirm this action? This cannot be undone.");
        if (!confirmAction) {
          statusDropdown.value = appointment.status;
          return;
        }
      }
      
      if (newStatus === "Confirmed") {
        const confirmAction = confirm("Are you sure you want to confirm this appointment?");
        if (!confirmAction) {
          statusDropdown.value = appointment.status;
          return;
        }
      }
      
      if (newStatus === "Pending") {
        const confirmAction = confirm("Are you sure you want to set this appointment to Pending?");
        if (!confirmAction) {
          statusDropdown.value = appointment.status;
          return;
        }
      }

      updateAppointmentStatus(appointment.id, newStatus, statusDropdown);
    });

    editBtn.addEventListener("click", () => {
      openModal("Edit Appointment", appointment);
    });

    deleteBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to archive this appointment?')) {
        deleteAppointment(appointment.id);
      }
    });

    updateDropdownColor(statusDropdown);
    return row;
  }

  function updateAppointmentStatus(appointmentId, status, dropdown) {
    const formData = new FormData();
    formData.append('action', 'updateAppointment');
    formData.append('id', appointmentId);
    formData.append('status', status);

    fetch('api_appointments.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        if (status === "Confirmed" || status === "Cancelled" || status === "Completed" || status === "Pending") {
          window.location.reload();
        } else {
          loadAppointments();
        }
      } else {
        toast.error('Error: ' + data.message);
        // Revert the dropdown
        const apt = appointments.find(a => a.id === appointmentId);
        if (apt) {
          dropdown.value = apt.status;
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      toast.error('Error updating appointment');
    });
  }

  function deleteAppointment(appointmentId) {
    const formData = new FormData();
    formData.append('action', 'deleteAppointment');
    formData.append('id', appointmentId);

    fetch('api_appointments.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        loadAppointments();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => console.error('Error:', error));
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

  function capitalizeWords(text) {
    return text.replace(/\b\w/g, char => char.toUpperCase());
  }

  function timeToMinutes(timeStr) {
    const [hours, minutes] = timeStr.split(":").map(Number);
    return hours * 60 + minutes;
  }

  function updateDropdownColor(dropdown) {
    const status = dropdown.value;
    let bg = "";

    switch (status) {
      case "Pending":
        bg = "#ffb74d";
        break;
      case "Confirmed":
        bg = "#4caf50";
        break;
      case "Completed":
        bg = "#2196f3";
        break;
      case "Cancelled":
        bg = "#f44336";
        break;
      default:
        bg = "gray";
    }

    dropdown.style.backgroundColor = bg;
    dropdown.style.color = "white";
  }

  // --- Sidebar toggle and nav wiring ---
  const leftPanel = document.getElementById('leftPanel');
  const panelToggle = document.getElementById('panelToggle');

  if (leftPanel && panelToggle) {
  // initialize collapsed state
  leftPanel.classList.add('closed');
  leftPanel.setAttribute('aria-hidden', 'true');
  panelToggle.setAttribute('aria-expanded', 'false');
  panelToggle.innerHTML = '▶';

    panelToggle.addEventListener('click', () => {
      const isClosed = leftPanel.classList.contains('closed');
      if (isClosed) {
        // expand
        leftPanel.classList.remove('closed');
        leftPanel.setAttribute('aria-hidden', 'false');
        panelToggle.setAttribute('aria-expanded', 'true');
        // show left-pointing arrow to indicate collapse action
        panelToggle.innerHTML = '◀';
      } else {
        // collapse
        leftPanel.classList.add('closed');
        leftPanel.setAttribute('aria-hidden', 'true');
        panelToggle.setAttribute('aria-expanded', 'false');
        // show right-pointing arrow / hamburger to indicate expand action
        panelToggle.innerHTML = '▶';
      }
    });

    // basic stub handlers for sidebar buttons
    document.querySelectorAll('.side-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        if (btn.classList.contains('logout-btn')) {
          if (confirm('Are you sure you want to log out?')) {
            window.location.href = '../../auth/logout.php';
          }
          return;
        }

        const target = btn.dataset.target;
        if (target === 'dashboard') {
          window.location.href = 'dashboard.php';
        } else if (target === 'users') {
          window.location.href = 'users.php';
        } else if (target === 'doctors') {
          window.location.href = 'doctors.php';
        }
      });
    });
  }
});
