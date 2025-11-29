

class BookingDatePicker {
  constructor(inputElement, availableDates = []) {
    this.inputElement = inputElement;
    this.availableDates = new Set(availableDates);
    this.currentMonth = new Date();
    this.selectedDate = null;
    this.isOpen = false;

    this.init();
  }

  init() {

    this.container = document.createElement('div');
    this.container.className = 'booking-date-picker-container';
    this.container.style.display = 'none';
    this.container.innerHTML = this.getPickerHTML();


    this.wrapper = document.createElement('div');
    this.wrapper.className = 'booking-date-picker-wrapper';
    this.inputElement.parentNode.insertBefore(this.wrapper, this.inputElement);
    this.wrapper.appendChild(this.inputElement);


    this.wrapper.appendChild(this.container);


    const setContainerWidth = () => {
      const inputWidth = this.inputElement.offsetWidth;
      this.container.style.width = inputWidth + 'px';
    };


    setContainerWidth();
    window.addEventListener('resize', setContainerWidth);


    if (this.inputElement.type === 'date') {
      this.inputElement.type = 'text';

      if (!this.inputElement.placeholder) {
        this.inputElement.placeholder = 'YYYY-MM-DD';
      }
    }


    this.inputElement.addEventListener('click', (e) => {
      e.stopPropagation();
      e.preventDefault();
      this.toggle();
    });

    this.inputElement.addEventListener('mousedown', (e) => {
      e.preventDefault();
    });





    this.container.addEventListener('click', (e) => {
      e.stopPropagation();
    });


    this.closeHandler = (e) => {
      if (this.isOpen && e.target !== this.inputElement &&
          !this.container.contains(e.target) &&
          !this.wrapper.contains(e.target)) {
        this.close();
      }
    };
    document.addEventListener('click', this.closeHandler);


    this.container.querySelector('.prev-month').addEventListener('click', () => this.previousMonth());
    this.container.querySelector('.next-month').addEventListener('click', () => this.nextMonth());

    this.renderCalendar();
  }

  getPickerHTML() {
    return `
      <div class="booking-date-picker-widget">
        <div class="booking-date-picker-header">
          <button type="button" class="prev-month" title="Previous month">◀</button>
          <div class="current-month"></div>
          <button type="button" class="next-month" title="Next month">▶</button>
        </div>
        <div class="booking-date-picker-weekdays">
          <div class="weekday">Sun</div>
          <div class="weekday">Mon</div>
          <div class="weekday">Tue</div>
          <div class="weekday">Wed</div>
          <div class="weekday">Thu</div>
          <div class="weekday">Fri</div>
          <div class="weekday">Sat</div>
        </div>
        <div class="booking-date-picker-dates"></div>
      </div>
    `;
  }

  renderCalendar() {
    const year = this.currentMonth.getFullYear();
    const month = this.currentMonth.getMonth();


    const monthName = this.currentMonth.toLocaleString('en-US', { month: 'long', year: 'numeric' });
    this.container.querySelector('.current-month').textContent = monthName;


    const datesContainer = this.container.querySelector('.booking-date-picker-dates');
    datesContainer.innerHTML = '';


    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();


    for (let i = 0; i < firstDay; i++) {
      const emptyCell = document.createElement('div');
      emptyCell.className = 'booking-date-cell empty';
      datesContainer.appendChild(emptyCell);
    }


    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);

      const dateStr = date.getFullYear() + '-' +
                      String(date.getMonth() + 1).padStart(2, '0') + '-' +
                      String(date.getDate()).padStart(2, '0');
      const dateCell = document.createElement('button');
      dateCell.type = 'button';
      dateCell.className = 'booking-date-cell';
      dateCell.textContent = day;

      const isAvailable = this.availableDates.has(dateStr);
      const isToday = this.isToday(date);
      const isSelected = this.inputElement.value === dateStr;

      if (isAvailable) {
        dateCell.classList.add('available');
        dateCell.title = `${dateStr} - Available`;
      } else {
        dateCell.classList.add('unavailable');
        dateCell.disabled = true;
        dateCell.title = `${dateStr} - Not available`;
      }

      if (isToday) {
        dateCell.classList.add('today');
      }

      if (isSelected) {
        dateCell.classList.add('selected');
      }

      dateCell.addEventListener('click', () => this.selectDate(dateStr));
      datesContainer.appendChild(dateCell);
    }
  }

  isToday(date) {
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
  }

  selectDate(dateStr) {
    this.inputElement.value = dateStr;
    this.inputElement.classList.add('valid-date');
    this.inputElement.classList.remove('invalid-date');


    const event = new Event('change', { bubbles: true });
    this.inputElement.dispatchEvent(event);


    this.close();
  }

  previousMonth() {
    this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
    this.renderCalendar();
  }

  nextMonth() {
    this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
    this.renderCalendar();
  }

  toggle() {
    console.log('Toggle called - isOpen:', this.isOpen);
    this.isOpen ? this.close() : this.open();
  }

  open() {
    console.log('Opening booking date picker');
    this.isOpen = true;
    this.container.style.display = 'block';
    this.inputElement.style.display = 'none';
    this.renderCalendar();


    setTimeout(() => {
      const calendarHeight = this.container.offsetHeight;
      this.wrapper.style.marginBottom = (calendarHeight + 20) + 'px';
      this.wrapper.classList.add('expanded');
    }, 0);
  }

  close() {
    console.log('Closing booking date picker');
    this.isOpen = false;
    this.container.style.display = 'none';
    this.inputElement.style.display = 'block';
    this.wrapper.classList.remove('expanded');
    this.wrapper.style.marginBottom = '0px';
  }

  updateAvailableDates(newAvailableDates) {
    this.availableDates = new Set(newAvailableDates);
    this.renderCalendar();
  }
}


window.BookingDatePicker = BookingDatePicker;
