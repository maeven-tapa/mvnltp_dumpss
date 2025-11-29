






function initializeSidebarToggle() {
  const leftPanel = document.getElementById('leftPanel');
  const panelToggle = document.getElementById('panelToggle');

  if (!leftPanel || !panelToggle) {
    console.warn('Sidebar elements not found');
    return;
  }


  leftPanel.classList.add('closed');
  leftPanel.setAttribute('aria-hidden', 'true');
  panelToggle.setAttribute('aria-expanded', 'false');
  panelToggle.innerHTML = '☰';


  function toggleSidebar(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
    }

    const isClosed = leftPanel.classList.contains('closed');
    console.log('Toggle clicked. Current state:', isClosed ? 'closed' : 'open');

    if (isClosed) {

      console.log('Expanding sidebar');
      leftPanel.classList.remove('closed');
      leftPanel.setAttribute('aria-hidden', 'false');
      panelToggle.setAttribute('aria-expanded', 'true');
      panelToggle.innerHTML = '✕';
    } else {

      console.log('Collapsing sidebar');
      leftPanel.classList.add('closed');
      leftPanel.setAttribute('aria-hidden', 'true');
      panelToggle.setAttribute('aria-expanded', 'false');
      panelToggle.innerHTML = '☰';
    }
  }


  panelToggle.addEventListener('click', toggleSidebar, true);
  panelToggle.addEventListener('mousedown', (e) => {
    e.preventDefault();
  }, true);


  setupSidebarButtons();
}


function setupSidebarButtons() {
  document.querySelectorAll('.side-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.classList.contains('logout-btn')) {
        showLogoutConfirmation();
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


function showLogoutConfirmation() {

  const existingModal = document.getElementById('logoutConfirmModal');
  if (existingModal) {
    existingModal.remove();
  }

  const logoutModal = document.createElement('div');
  logoutModal.id = 'logoutConfirmModal';
  logoutModal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    pointer-events: auto;
  `;

  const modalContent = document.createElement('div');
  modalContent.style.cssText = `
    background-color: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    max-width: 400px;
    text-align: center;
    position: relative;
    z-index: 10001;
    pointer-events: auto;
  `;

  const title = document.createElement('h3');
  title.textContent = 'Confirm Logout';
  title.style.cssText = 'margin-top: 0; margin-bottom: 15px; color: #333;';

  const message = document.createElement('p');
  message.textContent = 'Are you sure you want to logout? You will need to log in again to access your account.';
  message.style.cssText = 'margin-bottom: 25px; color: #666; font-size: 15px;';

  const buttonContainer = document.createElement('div');
  buttonContainer.style.cssText = 'display: flex; gap: 10px; justify-content: center;';

  const confirmBtn = document.createElement('button');
  confirmBtn.id = 'logoutConfirmBtn';
  confirmBtn.type = 'button';
  confirmBtn.textContent = 'Logout';
  confirmBtn.style.cssText = 'padding: 10px 20px; background-color: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; pointer-events: auto;';

  const cancelBtn = document.createElement('button');
  cancelBtn.id = 'logoutCancelBtn';
  cancelBtn.type = 'button';
  cancelBtn.textContent = 'Cancel';
  cancelBtn.style.cssText = 'padding: 10px 20px; background-color: #95a5a6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; pointer-events: auto;';

  buttonContainer.appendChild(confirmBtn);
  buttonContainer.appendChild(cancelBtn);

  modalContent.appendChild(title);
  modalContent.appendChild(message);
  modalContent.appendChild(buttonContainer);

  logoutModal.appendChild(modalContent);
  document.body.appendChild(logoutModal);


  function closeModal() {
    const modal = document.getElementById('logoutConfirmModal');
    if (modal && modal.parentNode) {
      modal.parentNode.removeChild(modal);
    }
  }


  logoutModal.addEventListener('click', function(e) {
    if (e.target === logoutModal) {
      e.stopPropagation();
      e.preventDefault();
    }
  });


  confirmBtn.onclick = function(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    try {
      localStorage.clear();
      sessionStorage.clear();
    } catch (err) {
      console.error('Error clearing storage:', err);
    }
    window.location.href = '../../auth/logout.php';
    return false;
  };


  cancelBtn.onclick = function(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    closeModal();
    return false;
  };
}


function preventBackNavigation() {
  window.history.pushState(null, null, window.location.href);
  window.addEventListener('popstate', function() {
    window.history.pushState(null, null, window.location.href);
  });
}






function parseTimeRange(timeRange) {
  if (!timeRange) return [8, 17];

  const cleanStr = timeRange.toLowerCase().replace(/\s+/g, '').replace(/am|pm/g, '');
  const parts = cleanStr.split('-').map(s => s.trim());

  if (parts.length === 2) {
    const start = parseInt(parts[0], 10);
    const end = parseInt(parts[1], 10);

    if (!isNaN(start) && !isNaN(end)) {
      return [start, end];
    }
  }

  return [8, 17];
}


function generateTimeSlots(availableTimes) {
  const slots = [];

  if (!availableTimes) {
    return slots;
  }

  const timeRanges = Array.isArray(availableTimes) ? availableTimes : [availableTimes];

  timeRanges.forEach(timeRange => {
    const [startHour, endHour] = parseTimeRange(timeRange);


    for (let hour = startHour; hour < endHour; hour++) {
      slots.push(`${hour.toString().padStart(2, '0')}:00`);
    }
  });

  return slots;
}


function getAvailableDatesForDoctor(availableDays, daysInFuture = 60) {
  if (!availableDays || availableDays.length === 0) {
    console.warn('No available days provided');
    return [];
  }

  const availableDates = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const dayOfWeekMap = {
    'monday': 1,
    'tuesday': 2,
    'wednesday': 3,
    'thursday': 4,
    'friday': 5,
    'saturday': 6,
    'sunday': 0
  };

  const availableDayNumbers = availableDays.map(day => dayOfWeekMap[day.toLowerCase()]);

  for (let i = 0; i < daysInFuture; i++) {
    const currentDate = new Date(today);
    currentDate.setDate(today.getDate() + i);

    const dayOfWeek = currentDate.getDay();
    const dateStr = currentDate.getFullYear() + '-' +
      String(currentDate.getMonth() + 1).padStart(2, '0') + '-' +
      String(currentDate.getDate()).padStart(2, '0');

    if (availableDayNumbers.includes(dayOfWeek)) {
      availableDates.push(dateStr);
    }
  }

  return availableDates;
}


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


function timeToMinutes(timeStr) {
  const [hours, minutes] = timeStr.split(":").map(Number);
  return hours * 60 + minutes;
}






function updateDropdownColor(dropdown, status) {
  let bg = "";

  if (!status && dropdown) {
    status = dropdown.value;
  }

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

  if (dropdown) {
    dropdown.style.backgroundColor = bg;
    dropdown.style.color = "white";
  }

  return bg;
}






function updatePaginationUI(currentPage, totalItems, itemsPerPage, startIndex, endIndex, totalPages) {
  document.getElementById("showingStart").textContent = totalItems > 0 ? startIndex + 1 : 0;
  document.getElementById("showingEnd").textContent = endIndex;
  document.getElementById("totalRecords").textContent = totalItems;

  document.getElementById("prevPage").disabled = currentPage === 1;
  document.getElementById("nextPage").disabled = currentPage === totalPages || totalPages === 0;

  renderPageNumbers(currentPage, totalPages);
}


function renderPageNumbers(currentPage, totalPages) {
  const pageNumbersDiv = document.getElementById("pageNumbers");
  if (!pageNumbersDiv) return;

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

        window.currentPage = page;
        window.renderTable?.();
      });
    }
    pageNumbersDiv.appendChild(pageBtn);
  });
}






function capitalizeWords(text) {
  return text.replace(/\b\w/g, char => char.toUpperCase());
}


function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>"']/g, function(c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}


function escapeHtmlForToast(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}


function closeErrorBox() {
  const errorBox = document.getElementById('errorBox');
  if (errorBox) {
    errorBox.style.animation = 'slideUp 0.3s ease-out forwards';
    setTimeout(() => {
      errorBox.remove();
    }, 300);
  }
}






function setupPasswordToggle(toggleButtonId, passwordInputId) {
  const toggle = document.getElementById(toggleButtonId);
  const pwd = document.getElementById(passwordInputId);

  if (!toggle || !pwd) return;

  toggle.addEventListener('click', () => {
    const isPassword = pwd.getAttribute('type') === 'password';
    pwd.setAttribute('type', isPassword ? 'text' : 'password');
    toggle.src = isPassword ? '../assets/images/eye-open.png' : '../assets/images/eye-close.png';
    toggle.alt = isPassword ? 'Hide Password' : 'Show Password';
  });
}


function setupSignupFormValidation() {
  const signupForm = document.getElementById('signupForm');
  const pwd = document.getElementById('password');
  const cpwd = document.getElementById('confirmPassword');
  const contactInput = document.getElementById('contact');

  if (!signupForm) return;

  signupForm.addEventListener('submit', (e) => {

    if (pwd && cpwd && pwd.value !== cpwd.value) {
      e.preventDefault();
      alert('Passwords do not match.');
      return false;
    }


    if (contactInput) {
      const contact = contactInput.value.trim();
      const digitsOnly = /^\d{7,15}$/.test(contact);
      if (!digitsOnly) {
        e.preventDefault();
        alert('Please enter a valid contact number (7-15 digits).');
        return false;
      }
    }

    return true;
  });
}






function setupCarousel(slidesSelector = '.slide', indicatorsSelector = '.indicator', intervalMs = 4000) {
  const slides = document.querySelectorAll(slidesSelector);
  const indicators = document.querySelectorAll(indicatorsSelector);

  if (slides.length === 0) return;

  let current = 0;

  function activate(index) {
    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === index);
    });
    indicators.forEach((ind, i) => {
      ind.classList.toggle('active', i === index);
    });
    current = index;
  }

  let timer = setInterval(() => {
    const next = (current + 1) % slides.length;
    activate(next);
  }, intervalMs);

  indicators.forEach((ind, i) => {
    ind.addEventListener('click', () => {
      activate(i);
      clearInterval(timer);
      timer = setInterval(() => {
        const next = (current + 1) % slides.length;
        activate(next);
      }, intervalMs);
    });
  });
}






function handleFilter(btn, filterButtons) {
  filterButtons.forEach(b => b.classList.remove("active"));
  btn.classList.add("active");
}


function setupTableSearchAndFilter(searchInputId, tableBodySelector, statusClass = 'status') {
  const searchInput = document.getElementById(searchInputId);
  const tableBody = document.querySelector(tableBodySelector);

  if (!searchInput || !tableBody) return;

  searchInput.addEventListener('input', () => {
    const searchTerm = searchInput.value.toLowerCase();
    const rows = tableBody.querySelectorAll('tr');

    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  });
}






function initializeApp() {

  initializeSidebarToggle();


  preventBackNavigation();


  setupPasswordToggle('togglePassword', 'password');
  setupPasswordToggle('toggleConfirm', 'confirmPassword');


  setupCarousel();


  setupSignupFormValidation();
}


document.addEventListener('DOMContentLoaded', function() {

  if (document.getElementById('leftPanel')) {
    initializeApp();
  }
});
