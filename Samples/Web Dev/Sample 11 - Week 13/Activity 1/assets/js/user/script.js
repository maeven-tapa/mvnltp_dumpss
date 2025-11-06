// adapted from pages/user/script.js — now uses server endpoints (appointments.php)
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
  const today = new Date().toISOString().split("T")[0];
  if (dateInput) dateInput.setAttribute("min", today);

  let editRow = null;

  addBtn.addEventListener("click", () => {
    modalTitle.textContent = "Book New Appointment";
    form.reset();
    editRow = null;
    modal.classList.add("show");
    modal.classList.remove("hidden");
  });

  cancelBtn.addEventListener("click", closeModal);
  window.addEventListener("click", e => {
    if (e.target === modal) closeModal();
  });

  function closeModal() {
    modal.classList.remove("show");
    setTimeout(() => modal.classList.add("hidden"), 300);
  }

  // Load appointments from server
  function loadAppointments() {
    fetch('appointments.php')
      .then(r => r.json())
      .then(data => {
        if (!Array.isArray(data)) return;
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

  function formatTime(time24) {
    if (!time24) return '';
    let [h, m] = time24.split(':');
    h = parseInt(h, 10);
    m = parseInt(m, 10);
    const modifier = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')} ${modifier}`;
  }

  function to24Hour(time12) {
    if (!time12.includes(' ')) return time12;
    let [time, modifier] = time12.split(' ');
    let [h, m] = time.split(':').map(Number);
    if (modifier.toUpperCase() === 'PM' && h !== 12) h += 12;
    if (modifier.toUpperCase() === 'AM' && h === 12) h = 0;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; });
  }

  // initial load
  loadAppointments();

});
