/**
 * app.js
 * Central shared functions for the Veterinary Management System
 * Includes utility functions used across admin, user, and auth pages
 */

// ============================================================================
// SIDEBAR & NAVIGATION FUNCTIONS
// ============================================================================

/**
 * Initialize sidebar toggle functionality
 * Used by: admin/script.js, admin/doctors_script.js, admin/users_script.js, user/script.js
 */
function initializeSidebarToggle() {
  const leftPanel = document.getElementById('leftPanel');
  const panelToggle = document.getElementById('panelToggle');

  if (!leftPanel || !panelToggle) {
    console.warn('Sidebar elements not found');
    return;
  }

  // Initialize collapsed state
  leftPanel.classList.add('closed');
  leftPanel.setAttribute('aria-hidden', 'true');
  panelToggle.setAttribute('aria-expanded', 'false');
  panelToggle.innerHTML = '☰';

  // Use mousedown and click for better browser compatibility
  function toggleSidebar(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
    }
    
    const isClosed = leftPanel.classList.contains('closed');
    console.log('Toggle clicked. Current state:', isClosed ? 'closed' : 'open');
    
    if (isClosed) {
      // Expand
      console.log('Expanding sidebar');
      leftPanel.classList.remove('closed');
      leftPanel.setAttribute('aria-hidden', 'false');
      panelToggle.setAttribute('aria-expanded', 'true');
      panelToggle.innerHTML = '✕';
    } else {
      // Collapse
      console.log('Collapsing sidebar');
      leftPanel.classList.add('closed');
      leftPanel.setAttribute('aria-hidden', 'true');
      panelToggle.setAttribute('aria-expanded', 'false');
      panelToggle.innerHTML = '☰';
    }
  }

  // Add multiple event listeners for better compatibility
  panelToggle.addEventListener('click', toggleSidebar, true);
  panelToggle.addEventListener('mousedown', (e) => {
    e.preventDefault();
  }, true);

  // Setup sidebar button handlers
  setupSidebarButtons();
}

/**
 * Setup handlers for sidebar navigation buttons
 */
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

/**
 * Show logout confirmation modal
 * Used by: admin/script.js, admin/doctors_script.js, admin/users_script.js, user/script.js
 */
function showLogoutConfirmation() {
  // Remove any existing logout modal first
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

  // Close modal function
  function closeModal() {
    const modal = document.getElementById('logoutConfirmModal');
    if (modal && modal.parentNode) {
      modal.parentNode.removeChild(modal);
    }
  }

  // Prevent clicks on the overlay background from closing the modal
  logoutModal.addEventListener('click', function(e) {
    if (e.target === logoutModal) {
      e.stopPropagation();
      e.preventDefault();
    }
  });

  // Logout handler
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

  // Cancel handler
  cancelBtn.onclick = function(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    closeModal();
    return false;
  };
}

/**
 * Prevent browser back button navigation when logged out
 * Used by: admin/script.js, admin/doctors_script.js, admin/users_script.js, user/script.js
 */
function preventBackNavigation() {
  window.history.pushState(null, null, window.location.href);
  window.addEventListener('popstate', function() {
    window.history.pushState(null, null, window.location.href);
  });
}

// ============================================================================
// APPOINTMENT & TIME FUNCTIONS
// ============================================================================

/**
 * Parse time range string (e.g., "7-12" or "7am-12pm") to hours
 * Returns [startHour, endHour]
 * Used by: admin/script.js, user/script.js, utils/appointment.js
 */
function parseTimeRange(timeRange) {
  if (!timeRange) return [8, 17]; // Default 8 AM to 5 PM

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

/**
 * Generate 1-hour interval time slots within a time range
 * @param {string|array} availableTimes - Either a string like "7-12" or array like ["7-12", "13-17"]
 * @returns {array} Array of time slots in HH:00 format (e.g., ["07:00", "08:00", "09:00"])
 * Used by: admin/script.js, user/script.js, utils/appointment.js
 */
function generateTimeSlots(availableTimes) {
  const slots = [];

  if (!availableTimes) {
    return slots;
  }

  const timeRanges = Array.isArray(availableTimes) ? availableTimes : [availableTimes];

  timeRanges.forEach(timeRange => {
    const [startHour, endHour] = parseTimeRange(timeRange);

    // Generate slots with 1-hour intervals
    for (let hour = startHour; hour < endHour; hour++) {
      slots.push(`${hour.toString().padStart(2, '0')}:00`);
    }
  });

  return slots;
}

/**
 * Get available dates for a doctor based on their available_dates (day names)
 * @param {array} availableDays - Array of day names (e.g., ["Monday", "Tuesday", "Wednesday"])
 * @param {number} daysInFuture - Number of days to generate (default 60)
 * @returns {array} Array of available dates in YYYY-MM-DD format
 * Used by: admin/script.js, user/script.js, utils/appointment.js
 */
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

/**
 * Format time from 24-hour to 12-hour format
 * @param {string} time24 - Time in HH:MM format (24-hour)
 * @returns {string} Time in HH:MM AM/PM format
 * Used by: admin/script.js, user/script.js, utils/appointment.js
 */
function formatTime(time24) {
  if (!time24) return '';

  let [h, m] = time24.split(':');
  h = parseInt(h, 10);
  m = parseInt(m, 10);

  const modifier = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;

  return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')} ${modifier}`;
}

/**
 * Convert 12-hour format to 24-hour format
 * @param {string} time12 - Time in HH:MM AM/PM format
 * @returns {string} Time in HH:MM format (24-hour)
 * Used by: user/script.js
 */
function to24Hour(time12) {
  if (!time12.includes(' ')) return time12;

  let [time, modifier] = time12.split(' ');
  let [h, m] = time.split(':').map(Number);

  if (modifier.toUpperCase() === 'PM' && h !== 12) h += 12;
  if (modifier.toUpperCase() === 'AM' && h === 12) h = 0;

  return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
}

/**
 * Convert minutes to time string
 * Used by: admin/script.js
 */
function timeToMinutes(timeStr) {
  const [hours, minutes] = timeStr.split(":").map(Number);
  return hours * 60 + minutes;
}

// ============================================================================
// DROPDOWN & COLOR FUNCTIONS
// ============================================================================

/**
 * Update dropdown background color based on status
 * Used by: admin/doctors_script.js, admin/script.js
 */
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

// ============================================================================
// PAGINATION FUNCTIONS
// ============================================================================

/**
 * Update pagination UI elements
 * Used by: admin/doctors_script.js, admin/script.js, admin/users_script.js
 */
function updatePaginationUI(currentPage, totalItems, itemsPerPage, startIndex, endIndex, totalPages) {
  document.getElementById("showingStart").textContent = totalItems > 0 ? startIndex + 1 : 0;
  document.getElementById("showingEnd").textContent = endIndex;
  document.getElementById("totalRecords").textContent = totalItems;

  document.getElementById("prevPage").disabled = currentPage === 1;
  document.getElementById("nextPage").disabled = currentPage === totalPages || totalPages === 0;

  renderPageNumbers(currentPage, totalPages);
}

/**
 * Render page number buttons
 * Used by: admin/doctors_script.js, admin/script.js, admin/users_script.js
 */
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
        // This will be handled by the page-specific script
        window.currentPage = page;
        window.renderTable?.();
      });
    }
    pageNumbersDiv.appendChild(pageBtn);
  });
}

// ============================================================================
// TEXT FORMATTING & VALIDATION FUNCTIONS
// ============================================================================

/**
 * Capitalize first letter of each word
 * Used by: admin/script.js
 */
function capitalizeWords(text) {
  return text.replace(/\b\w/g, char => char.toUpperCase());
}

/**
 * Escape HTML special characters
 * Used by: user/script.js
 */
function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>"']/g, function(c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

/**
 * Escape HTML for toast messages
 * Used by: components/toast.js
 */
function escapeHtmlForToast(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Close error box with animation
 * Used by: auth/login.js
 */
function closeErrorBox() {
  const errorBox = document.getElementById('errorBox');
  if (errorBox) {
    errorBox.style.animation = 'slideUp 0.3s ease-out forwards';
    setTimeout(() => {
      errorBox.remove();
    }, 300);
  }
}

// ============================================================================
// PASSWORD & AUTH FUNCTIONS
// ============================================================================

/**
 * Toggle password visibility
 * Used by: auth/login.js, auth/signup.js
 */
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

/**
 * Setup signup form validation
 * Used by: auth/signup.js
 */
function setupSignupFormValidation() {
  const signupForm = document.getElementById('signupForm');
  const pwd = document.getElementById('password');
  const cpwd = document.getElementById('confirmPassword');
  const contactInput = document.getElementById('contact');

  if (!signupForm) return;

  signupForm.addEventListener('submit', (e) => {
    // Confirm password match
    if (pwd && cpwd && pwd.value !== cpwd.value) {
      e.preventDefault();
      alert('Passwords do not match.');
      return false;
    }

    // Validate contact number (digits only, 7-15 characters)
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

// ============================================================================
// CAROUSEL / SLIDER FUNCTIONS
// ============================================================================

/**
 * Setup carousel/slider functionality
 * Used by: auth/login.js
 */
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

// ============================================================================
// FILTER & SEARCH FUNCTIONS
// ============================================================================

/**
 * Handle filter button clicks for tables
 * Used by: admin/doctors_script.js, admin/users_script.js, user/script.js
 */
function handleFilter(btn, filterButtons) {
  filterButtons.forEach(b => b.classList.remove("active"));
  btn.classList.add("active");
}

/**
 * Setup search and filter for appointment tables (user dashboard)
 * Used by: user/script.js
 */
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

// ============================================================================
// INITIALIZE ALL SHARED FUNCTIONALITY
// ============================================================================

/**
 * Initialize all shared app functionality
 * Call this in DOMContentLoaded of pages that use shared features
 */
function initializeApp() {
  // Setup sidebar navigation
  initializeSidebarToggle();

  // Setup logout prevention with back button
  preventBackNavigation();

  // Setup password toggles if they exist
  setupPasswordToggle('togglePassword', 'password');
  setupPasswordToggle('toggleConfirm', 'confirmPassword');

  // Setup carousel if it exists
  setupCarousel();

  // Setup signup validation if it exists
  setupSignupFormValidation();
}

// Auto-initialize on DOM ready if needed
document.addEventListener('DOMContentLoaded', function() {
  // Only initialize if there are sidebar elements present
  if (document.getElementById('leftPanel')) {
    initializeApp();
  }
});
