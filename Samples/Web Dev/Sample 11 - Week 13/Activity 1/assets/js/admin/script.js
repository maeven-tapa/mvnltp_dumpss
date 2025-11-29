document.addEventListener("DOMContentLoaded", () => {

  initializeSidebarToggle();
  preventBackNavigation();

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
  let adminDatePicker = null;
  let allDoctors = [];


  const filterButtons = document.querySelectorAll(".filter-btn");


  filterButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      filterButtons.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      currentActiveFilter = btn.dataset.status;
      currentPage = 1;
      renderTable();
    });
  });


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


  function initializeAdminDatePicker() {
    if (adminDatePicker) {
      adminDatePicker.updateAvailableDates([]);
    } else {
      adminDatePicker = new BookingDatePicker(dateInput, []);
    }
  }


  addBtn.addEventListener("click", () => {
    openModal("Add Appointment");
    setTimeout(() => initializeAdminDatePicker(), 0);
  });


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


  dateInput.addEventListener("change", updateAvailableTimes);

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


    if (!selectedDoctor) {
      dateTimeSection.classList.remove('visible');
      dateInput.removeAttribute('required');
      timeSelect.removeAttribute('required');
      return;
    }


    dateTimeSection.classList.add('visible');
    dateInput.setAttribute('required', 'required');
    timeSelect.setAttribute('required', 'required');


    const doctor = doctors.find(d => d.name === selectedDoctor);
    if (doctor && doctor.available_dates && adminDatePicker) {
      const availableDates = getAvailableDatesForDoctor(doctor.available_dates);
      adminDatePicker.updateAvailableDates(availableDates);
    }


    if (!selectedDate) {
      return;
    }


    populateTimeSlotsForDoctor(selectedDoctor, selectedDate);
  }


  function populateTimeSlotsForDoctor(doctorName, selectedDate) {

    const doctor = doctors.find(d => d.name === doctorName);

    if (!doctor || !doctor.available_times) {

      const defaultTimes = generateTimeSlots(['8-17']);
      defaultTimes.forEach(time => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = formatTime(time);
        timeSelect.appendChild(option);
      });
      return;
    }


    const timeSlots = generateTimeSlots(doctor.available_times);


    fetch(`api_appointments.php?action=getBookedTimes&date=${selectedDate}&doctor=${doctorName}`)
      .then(response => response.json())
      .then(data => {
        const bookedTimes = data.data || [];


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


    if (appointment.status === "Completed" || appointment.status === "Cancelled") {
      statusDropdown.disabled = true;
      editBtn.style.display = "none";
    }


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

    renderPageNumbers(currentPage, totalPages);
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



});
