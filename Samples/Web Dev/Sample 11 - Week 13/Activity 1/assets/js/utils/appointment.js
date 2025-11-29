


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


function parseTimeRange(timeRange) {
  if (!timeRange) return [8, 17];

  const cleanStr = timeRange.toLowerCase().replace(/\s+/g, '').replace(/am|pm/g, '');
  const parts = cleanStr.split('-').map(s => s.trim());

  if (parts.length === 2) {
    let start = parseInt(parts[0], 10);
    let end = parseInt(parts[1], 10);

    if (!isNaN(start) && !isNaN(end)) {



      if (end <= start) {

        if (start >= 12 && end <= 5) {
          end = end + 12;
        }

        else if (end === start) {
          return [8, 17];
        }
      }
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

    const dateStr = currentDate.getFullYear() + '-' +
                    String(currentDate.getMonth() + 1).padStart(2, '0') + '-' +
                    String(currentDate.getDate()).padStart(2, '0');
    const dayName = currentDate.toLocaleDateString('en-US', { weekday: 'long' });

    if (availableDayNumbers.includes(dayOfWeek)) {
      console.log(`✓ ${dateStr} (${dayName}) - AVAILABLE`);
      availableDates.push(dateStr);
    }
  }

  console.log('Final Available Dates:', availableDates);
  return availableDates;
}


function setAvailableDateRanges(dateInput, availableDates) {
  if (!dateInput) return;

  if (!availableDates || availableDates.length === 0) {
    dateInput.setAttribute('min', new Date().toISOString().split('T')[0]);
    return;
  }


  const sortedDates = [...availableDates].sort();
  dateInput.setAttribute('min', sortedDates[0]);
  dateInput.setAttribute('max', sortedDates[sortedDates.length - 1]);
}


function getBookedTimesForDate(selectedDate, appointments) {
  if (!Array.isArray(appointments)) {
    return [];
  }

  return appointments
    .filter(appt => appt.appt_date === selectedDate && appt.status !== 'cancelled')
    .map(appt => appt.appt_time);
}


function isDateAvailable(date, availableDates) {
  if (!availableDates || availableDates.length === 0) {
    return true;
  }
  const isAvailable = availableDates.includes(date);
  console.log(`Checking if ${date} is available:`, isAvailable, 'Available dates:', availableDates);
  return isAvailable;
}


function setupDatePicker(dateInput, availableDates) {
  if (!dateInput) return;

  console.log('Validating date picker with available dates:', availableDates);


  setAvailableDateRanges(dateInput, availableDates);


  dateInput.dataset.availableDates = JSON.stringify(availableDates);


  dateInput.removeEventListener('change', dateInput._changeHandler);
  dateInput.removeEventListener('input', dateInput._inputHandler);
  dateInput.removeEventListener('focus', dateInput._focusHandler);
  dateInput.removeEventListener('blur', dateInput._blurHandler);


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


  dateInput.addEventListener('change', dateInput._changeHandler);
  dateInput.addEventListener('input', dateInput._inputHandler);
  dateInput.addEventListener('focus', dateInput._focusHandler);
  dateInput.addEventListener('blur', dateInput._blurHandler);


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


function enhanceDatePickerDisplay(dateInput, availableDates) {
  if (!availableDates || availableDates.length === 0) return;


  const sortedDates = [...availableDates].sort();


  dateInput.setAttribute('min', sortedDates[0]);
  dateInput.setAttribute('max', sortedDates[sortedDates.length - 1]);
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
