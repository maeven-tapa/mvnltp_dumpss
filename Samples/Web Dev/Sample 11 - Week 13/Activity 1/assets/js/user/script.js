document.addEventListener("DOMContentLoaded", () => {
  const addBtn = document.getElementById("addBtn");
  const modal = document.getElementById("modal");
  const cancelBtn = document.getElementById("cancelBtn");
  const form = document.getElementById("appointmentForm");
  const modalTitle = document.getElementById("modalTitle");
  const upcomingTableBody = document.querySelector("#upcomingTable tbody");
  const pastTableBody = document.querySelector("#pastTable tbody");

  const receiptPopup = document.getElementById("receiptPopup");
  const closeReceiptBtn = document.getElementById("closeReceiptBtn");
  const receiptPetName = document.getElementById("receiptPetName");
  const receiptService = document.getElementById("receiptService");
  const receiptApptDate = document.getElementById("receiptApptDate");
  const receiptApptTime = document.getElementById("receiptApptTime");
  const receiptVet = document.getElementById("receiptVet");
  const receiptStatus = document.getElementById("receiptStatus");
  const receiptDate = document.getElementById("receiptDate");

  const dateInput = document.getElementById("date");
  const vetSelect = document.getElementById("vet");
  const timeSelect = document.getElementById("time");
  const today = new Date().toISOString().split("T")[0];
  if (dateInput) dateInput.setAttribute("min", today);

  // Add CSS for date-time-section
  const style = document.createElement('style');
  style.textContent = `
    .date-time-section {
      display: none;
    }
    .date-time-section.visible {
      display: block;
    }
  `;
  document.head.appendChild(style);

  let editRow = null;
  let allDoctors = [];
  let allAppointments = [];
  let userDatePicker = null;

  addBtn.addEventListener("click", () => {
    modalTitle.textContent = "Book New Appointment";
    form.reset();
    editRow = null;
    modal.classList.add("show");
    modal.classList.remove("hidden");
    // Initialize custom date picker when modal opens
    setTimeout(() => initializeUserDatePicker(), 0);
  });

  // Initialize custom date picker for user form
  function initializeUserDatePicker() {
    if (userDatePicker) {
      userDatePicker.updateAvailableDates([]);
    } else {
      userDatePicker = new CustomDatePicker(dateInput, []);
    }
  }

  cancelBtn.addEventListener("click", closeModal);
  window.addEventListener("click", e => {
    if (e.target === modal) closeModal();
  });

  // Listen for vet and date changes to update available times
  vetSelect.addEventListener("change", updateAvailableTimes);
  dateInput.addEventListener("change", updateAvailableTimes);

  function closeModal() {
    modal.classList.remove("show");
    setTimeout(() => modal.classList.add("hidden"), 300);
  }

  // Fetch all doctors and their availability
  async function loadAllDoctors() {
    try {
      const response = await fetch('../../pages/admin/api_doctors_public.php?action=getAvailableDoctors');
      const data = await response.json();
      if (data.success && data.data) {
        allDoctors = data.data;
        populateDoctorSelect();
      }
    } catch (err) {
      console.error('Error loading doctors:', err);
    }
  }

  function populateDoctorSelect() {
    vetSelect.innerHTML = '<option value="">No preference</option>';
    allDoctors.forEach(doctor => {
      const option = document.createElement('option');
      option.value = doctor.name_without_prefix;  // Store name without prefix
      option.textContent = doctor.name;  // Display with Dr. prefix
      vetSelect.appendChild(option);
    });
  }

  // Update available times based on selected vet and date
  async function updateAvailableTimes() {
    const selectedVet = vetSelect.value;
    const selectedDate = dateInput.value;
    const dateTimeSection = document.querySelector('.date-time-section');

    timeSelect.innerHTML = '<option value="" disabled selected>Select time</option>';

    // If no vet selected, hide date and time section
    if (!selectedVet) {
      dateTimeSection.classList.remove('visible');
      dateInput.removeAttribute('required');
      timeSelect.removeAttribute('required');
      const today = new Date().toISOString().split("T")[0];
      dateInput.setAttribute("min", today);
      dateInput.removeAttribute("max");
      dateInput.classList.remove('valid-date', 'invalid-date');
      return;
    }

    // Vet selected, show date and time section
    dateTimeSection.classList.add('visible');
    dateInput.setAttribute('required', 'required');
    timeSelect.setAttribute('required', 'required');

    // Update date picker with available dates
    const doctor = allDoctors.find(d => d.name_without_prefix === selectedVet);
    if (doctor && doctor.available_dates && userDatePicker) {
      const availableDates = getAvailableDatesForDoctor(doctor.available_dates);
      userDatePicker.updateAvailableDates(availableDates);
    }

    if (!selectedDate) {
      // Date picker is already initialized with available dates above
      return;
    }

    // Get selected doctor's availability
    let doctorTimes = null;
    if (selectedVet) {
      const doctor = allDoctors.find(d => d.name_without_prefix === selectedVet);
      if (doctor) {
        doctorTimes = doctor.available_times;
      }
    }

    // Fetch booked times for this date and doctor
    let bookedTimes = [];
    if (selectedVet && selectedDate) {
      try {
        const response = await fetch(`../../pages/admin/api_doctors_public.php?action=getBookedTimes&date=${selectedDate}&doctor=${selectedVet}`);
        const bookedData = await response.json();
        bookedTimes = bookedData.data || [];
      } catch (err) {
        console.error('Error fetching booked times:', err);
      }
    }

    // If no vet selected, show default times
    if (!doctorTimes) {
      const defaultTimes = generateTimeSlots(['8-17']);  // 8 AM to 5 PM
      populateTimeOptions(defaultTimes, selectedDate, bookedTimes);
      return;
    }

    // Generate times based on doctor's available time slots
    const times = generateTimeSlots(doctorTimes);
    populateTimeOptions(times, selectedDate, bookedTimes);
  }

  // Populate time dropdown with available times, excluding booked times
  function populateTimeOptions(availableTimes, selectedDate, bookedTimes = []) {
    availableTimes.forEach(time => {
      if (!bookedTimes.includes(time)) {
        const displayTime = formatTime(time);
        const option = document.createElement('option');
        option.value = time;
        option.textContent = displayTime;
        timeSelect.appendChild(option);
      }
    });
  }

  // Load appointments from server
  function loadAppointments() {
    fetch('appointments.php')
      .then(r => r.json())
      .then(data => {
        if (!Array.isArray(data)) return;
        allAppointments = data;
        upcomingTableBody.innerHTML = '';
        pastTableBody.innerHTML = '';
        data.forEach(addRowFromServer);
      })
      .catch(() => { /* ignore load errors for now */ });
  }

  function addRowFromServer(appt) {
    const row = document.createElement('tr');
    row.dataset.id = appt.id;
    const displayTime = formatTime(appt.appt_time);
    const statusLabel = appt.status ? appt.status : 'Pending';
    
    const selectedDateTime = new Date(`${appt.appt_date}T${appt.appt_time}`);
    const now = new Date();
    const isPast = selectedDateTime < now || (appt.status && appt.status.toLowerCase() === 'cancelled');
    
    if (isPast) {
      // Past appointments: no action column
      row.innerHTML = `
        <td>${escapeHtml(appt.pet_name)}</td>
        <td>${escapeHtml(appt.service)}</td>
        <td>${escapeHtml(appt.vet || 'Not assigned')}</td>
        <td>${escapeHtml(appt.appt_date)}</td>
        <td>${escapeHtml(displayTime)}</td>
        <td><span class="status ${statusLabel.toLowerCase()}">${statusLabel}</span></td>
      `;
      pastTableBody.appendChild(row);
    } else {
      // Upcoming appointments: include action column
      row.innerHTML = `
        <td>${escapeHtml(appt.pet_name)}</td>
        <td>${escapeHtml(appt.service)}</td>
        <td>${escapeHtml(appt.vet || 'Not assigned')}</td>
        <td>${escapeHtml(appt.appt_date)}</td>
        <td>${escapeHtml(displayTime)}</td>
        <td><span class="status ${statusLabel.toLowerCase()}">${statusLabel}</span></td>
        <td class="action-col"></td>
      `;
      const actionCell = row.querySelector('.action-col');
      actionCell.innerHTML = `
        <button class="btn edit-btn">Edit</button>
        <button class="btn cancel-btn">Cancel</button>
      `;
      attachRowActions(row);
      upcomingTableBody.appendChild(row);
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const petName = document.getElementById('petName').value.trim();
    const service = document.getElementById('service').value.trim();
    const date = document.getElementById('date').value;
    const timeValue = document.getElementById('time').value;
    const vet = document.getElementById('vet') ? document.getElementById('vet').value.trim() : '';

    if (!petName || !service || !date || !timeValue) {
      alert('Please fill in all fields.');
      return;
    }

    // send to server
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('pet_name', petName);
    formData.append('service', service);
    formData.append('appt_date', date);
    formData.append('appt_time', timeValue);
    formData.append('vet', vet);

    fetch('appointments.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(resp => {
        if (resp && resp.success && resp.appointment) {
          allAppointments.push(resp.appointment);
          addRowFromServer(resp.appointment);
          showReceipt({ petName: resp.appointment.pet_name, service: resp.appointment.service, date: resp.appointment.appt_date, time: formatTime(resp.appointment.appt_time), vet: resp.appointment.vet || 'Not assigned', status: resp.appointment.status });
        } else {
          alert('Unable to create appointment.');
        }
      })
      .catch(() => alert('Unable to create appointment.'));

    closeModal();
    form.reset();
  });

  function attachRowActions(row) {
    const editBtn = row.querySelector('.edit-btn');
    const cancelBtn = row.querySelector('.cancel-btn');
    editBtn.addEventListener('click', () => {
      // keep edits client-side for now
      editRow = row;
      document.getElementById('petName').value = row.cells[0].textContent;
      document.getElementById('service').value = row.cells[1].textContent;
      document.getElementById('vet').value = row.cells[2].textContent === 'Not assigned' ? '' : row.cells[2].textContent;
      document.getElementById('date').value = row.cells[3].textContent;
      document.getElementById('time').value = to24Hour(row.cells[4].textContent);
      modalTitle.textContent = 'Edit Appointment';
      modal.classList.add('show');
      modal.classList.remove('hidden');
      updateAvailableTimes();
    });

    cancelBtn.addEventListener('click', () => {
      if (!confirm('Cancel this appointment?')) return;
      const id = row.dataset.id;
      const fd = new FormData();
      fd.append('action', 'cancel');
      fd.append('id', id);
      fetch('appointments.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(resp => {
          if (resp && resp.success) {
            setStatus(row, 'Cancelled');
            const edit = row.querySelector('.edit-btn'); if (edit) edit.remove();
            const cancel = row.querySelector('.cancel-btn'); if (cancel) cancel.remove();
            allAppointments = allAppointments.map(appt => 
              appt.id == id ? { ...appt, status: 'cancelled' } : appt
            );
            pastTableBody.appendChild(row);
          } else alert('Unable to cancel appointment');
        })
        .catch(() => alert('Unable to cancel appointment'));
    });
  }

  function setStatus(row, status) {
    row.cells[5].innerHTML = `<span class="status ${status.toLowerCase()}">${status}</span>`;
  }

  function showReceipt({ petName, service, date, time, vet, status }) {
    receiptPetName.textContent = petName;
    receiptService.textContent = service;
    receiptApptDate.textContent = date;
    receiptApptTime.textContent = time;
    receiptVet.textContent = vet;
    receiptStatus.textContent = status;
    receiptDate.textContent = new Date().toLocaleDateString();
    receiptPopup.classList.add('show');
    receiptPopup.classList.remove('hidden');
  }

  closeReceiptBtn.addEventListener('click', () => {
    receiptPopup.classList.remove('show');
    setTimeout(() => receiptPopup.classList.add('hidden'), 300);
  });

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; });
  }

  // initial load
  loadAllDoctors();
  loadAppointments();

});
