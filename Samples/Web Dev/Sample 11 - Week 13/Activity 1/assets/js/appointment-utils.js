/**
 * appointment-utils.js
 * Shared utility functions for appointment booking across guest and registered users
 */

/**
 * Fetch all available doctors from the server
 */
async function fetchAvailableDoctors() {
  try {
    const response = await fetch('pages/admin/api_doctors_public.php?action=getAvailableDoctors');
    const data = await response.json();
    if (data.success && data.data) {
      return data.data;
    }
    return [];
  } catch (error) {
    console.error('Error fetching doctors:', error);
    return [];
  }
}

/**
 * Parse time range string (e.g., "7-12" or "7am-12pm") to hours
 * Returns [startHour, endHour]
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

  console.log('Available Days from DB:', availableDays);
  
  const availableDayNumbers = availableDays.map(day => {
    const dayNum = dayOfWeekMap[day.toLowerCase()];
    console.log(`Day: ${day} -> Number: ${dayNum}`);
    return dayNum;
  });

  console.log('Available Day Numbers:', availableDayNumbers);

  for (let i = 0; i < daysInFuture; i++) {
    const currentDate = new Date(today);
    currentDate.setDate(today.getDate() + i);
    
    const dayOfWeek = currentDate.getDay();
    const dateStr = currentDate.toISOString().split('T')[0];
    const dayName = currentDate.toLocaleDateString('en-US', { weekday: 'long' });
    
    if (availableDayNumbers.includes(dayOfWeek)) {
      console.log(`✓ ${dateStr} (${dayName}) - AVAILABLE`);
      availableDates.push(dateStr);
    }
  }

  console.log('Final Available Dates:', availableDates);
  return availableDates;
}

/**
 * Populate a date input with available dates for a doctor
 * @param {HTMLElement} dateInput - The date input element
 * @param {array} availableDates - Array of available dates
 */
function setAvailableDateRanges(dateInput, availableDates) {
  if (!dateInput) return;

  if (!availableDates || availableDates.length === 0) {
    dateInput.setAttribute('min', new Date().toISOString().split('T')[0]);
    return;
  }

  // Set min and max to first and last available dates
  const sortedDates = [...availableDates].sort();
  dateInput.setAttribute('min', sortedDates[0]);
  dateInput.setAttribute('max', sortedDates[sortedDates.length - 1]);
}

/**
 * Get booked times for a specific date
 * @param {string} selectedDate - Date in YYYY-MM-DD format
 * @param {array} appointments - Array of appointment objects
 * @returns {array} Array of booked times in HH:MM format
 */
function getBookedTimesForDate(selectedDate, appointments) {
  if (!Array.isArray(appointments)) {
    return [];
  }

  return appointments
    .filter(appt => appt.appt_date === selectedDate && appt.status !== 'cancelled')
    .map(appt => appt.appt_time);
}

/**
 * Check if a specific date is an available date
 * @param {string} date - Date in YYYY-MM-DD format
 * @param {array} availableDates - Array of available dates
 * @returns {boolean}
 */
function isDateAvailable(date, availableDates) {
  if (!availableDates || availableDates.length === 0) {
    return true; // No restrictions
  }
  const isAvailable = availableDates.includes(date);
  console.log(`Checking if ${date} is available:`, isAvailable, 'Available dates:', availableDates);
  return isAvailable;
}

/**
 * Validate and restrict date input
 * NOTE: CustomDatePicker is now initialized by individual dashboard scripts
 * This function just validates the selected date
 */
function setupDatePicker(dateInput, availableDates) {
  if (!dateInput) return;

  console.log('Validating date picker with available dates:', availableDates);

  // Set basic date constraints
  setAvailableDateRanges(dateInput, availableDates);

  // Store available dates on the input for reference
  dateInput.dataset.availableDates = JSON.stringify(availableDates);

  // Remove existing listeners to prevent duplicates
  dateInput.removeEventListener('change', dateInput._changeHandler);
  dateInput.removeEventListener('input', dateInput._inputHandler);
  dateInput.removeEventListener('focus', dateInput._focusHandler);
  dateInput.removeEventListener('blur', dateInput._blurHandler);

  // Create handlers and store them for removal
  dateInput._changeHandler = function() {
    const selectedDate = this.value;
    console.log('Date changed:', selectedDate);
    
    if (selectedDate && !isDateAvailable(selectedDate, availableDates)) {
      alert('The selected date is not available for this doctor.');
      this.value = '';
      this.classList.remove('valid-date', 'invalid-date');
    } else if (selectedDate) {
      this.classList.add('valid-date');
      this.classList.remove('invalid-date');
    }
  };

  dateInput._inputHandler = function() {
    const selectedDate = this.value;
    console.log('Date input:', selectedDate);
    
    if (!selectedDate) {
      this.classList.remove('valid-date', 'invalid-date');
      return;
    }

    if (availableDates.length > 0) {
      if (isDateAvailable(selectedDate, availableDates)) {
        console.log('Date is AVAILABLE - adding green class');
        this.classList.remove('invalid-date');
        this.classList.add('valid-date');
      } else {
        console.log('Date is NOT AVAILABLE - adding red class');
        this.classList.remove('valid-date');
        this.classList.add('invalid-date');
      }
    }
  };

  dateInput._focusHandler = function() {
    this.classList.add('date-picker-active');
  };

  dateInput._blurHandler = function() {
    this.classList.remove('date-picker-active');
  };

  // Add new listeners
  dateInput.addEventListener('change', dateInput._changeHandler);
  dateInput.addEventListener('input', dateInput._inputHandler);
  dateInput.addEventListener('focus', dateInput._focusHandler);
  dateInput.addEventListener('blur', dateInput._blurHandler);

  // Set initial state based on current value
  if (dateInput.value) {
    const currentValue = dateInput.value;
    if (isDateAvailable(currentValue, availableDates)) {
      dateInput.classList.remove('invalid-date');
      dateInput.classList.add('valid-date');
    } else {
      dateInput.classList.remove('valid-date');
      dateInput.classList.add('invalid-date');
    }
  }
}

/**
 * Enhance the date picker to visually show available dates
 * This uses JavaScript to disable unavailable dates in the native date picker
 */
function enhanceDatePickerDisplay(dateInput, availableDates) {
  if (!availableDates || availableDates.length === 0) return;

  // Get date range
  const sortedDates = [...availableDates].sort();

  // Set input constraints
  dateInput.setAttribute('min', sortedDates[0]);
  dateInput.setAttribute('max', sortedDates[sortedDates.length - 1]);
}

/**
 * Format time from 24-hour to 12-hour format
 * @param {string} time24 - Time in HH:MM format (24-hour)
 * @returns {string} Time in HH:MM AM/PM format
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
 */
function to24Hour(time12) {
  if (!time12.includes(' ')) return time12;
  
  let [time, modifier] = time12.split(' ');
  let [h, m] = time.split(':').map(Number);
  
  if (modifier.toUpperCase() === 'PM' && h !== 12) h += 12;
  if (modifier.toUpperCase() === 'AM' && h === 12) h = 0;
  
  return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
}
