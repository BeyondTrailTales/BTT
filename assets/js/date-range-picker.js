/**
 * TripAdvisor-style Date Range Picker
 * Simple single calendar with clear start/end highlighting
 */

class DateRangePicker {
  constructor(inputElement, options = {}) {
    this.input = inputElement;
    this.popup = document.getElementById('date-picker-popup');
    this.startDateInput = document.getElementById('start_date');
    this.endDateInput = document.getElementById('end_date');
    this.statusElement = document.getElementById('selection-status');
    
    this.options = {
      format: 'YYYY-MM-DD',
      displayFormat: 'MMM DD, YYYY',
      ...options
    };
    
    this.selectedStartDate = null;
    this.selectedEndDate = null;
    this.currentMonth = new Date();
    this.selectionStep = 'start'; // 'start' or 'end'
    
    this.init();
  }
  
  init() {
    this.createCalendar();
    this.attachEventListeners();
    this.loadExistingDates();
  }
  
  createCalendar() {
    const calendarContainer = document.getElementById('main-calendar');
    if (!calendarContainer) return;
    
    // Create calendar header with navigation
    calendarContainer.innerHTML = this.createCalendarHeader();
    
    // Create calendar grid
    calendarContainer.appendChild(this.createCalendarGrid());
  }
  
  createCalendarHeader() {
    return `
      <div class="calendar-header">
        <button type="button" class="nav-btn prev-month">‹</button>
        <span class="current-month">${this.formatMonth(this.currentMonth)}</span>
        <button type="button" class="nav-btn next-month">›</button>
      </div>
    `;
  }
  
  createCalendarGrid() {
    const grid = document.createElement('div');
    grid.className = 'date-picker-calendar';
    
    // Add day headers
    const dayHeaders = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    dayHeaders.forEach(day => {
      const header = document.createElement('div');
      header.className = 'calendar-day-header';
      header.textContent = day;
      grid.appendChild(header);
    });
    
    // Add calendar days
    this.populateCalendarDays(grid, this.currentMonth);
    
    return grid;
  }
  
  populateCalendarDays(grid, month) {
    const startOfMonth = new Date(month.getFullYear(), month.getMonth(), 1);
    const endOfMonth = new Date(month.getFullYear(), month.getMonth() + 1, 0);
    const startDate = new Date(startOfMonth);
    startDate.setDate(startDate.getDate() - startOfMonth.getDay());
    
    // Clear existing days (but keep headers)
    const existingDays = grid.querySelectorAll('.calendar-day');
    existingDays.forEach(day => day.remove());
    
    for (let i = 0; i < 42; i++) {
      const date = new Date(startDate);
      date.setDate(startDate.getDate() + i);
      
      const dayElement = document.createElement('div');
      dayElement.className = 'calendar-day';
      dayElement.textContent = date.getDate();
      dayElement.setAttribute('data-date', this.formatDate(date));
      
      // Add classes for styling
      if (date.getMonth() !== month.getMonth()) {
        dayElement.classList.add('other-month');
      }
      
      if (this.isToday(date)) {
        dayElement.classList.add('today');
      }
      
      if (this.isStartDate(date)) {
        dayElement.classList.add('start-date');
      }
      
      if (this.isEndDate(date)) {
        dayElement.classList.add('end-date');
      }
      
      if (this.isInRange(date)) {
        dayElement.classList.add('in-range');
      }
      
      dayElement.addEventListener('click', () => this.selectDate(date));
      grid.appendChild(dayElement);
    }
  }
  
  attachEventListeners() {
    // Input click to show popup
    this.input.addEventListener('click', () => this.showPopup());
    
    // Navigation buttons
    document.addEventListener('click', (e) => {
      if (e.target.matches('.prev-month')) {
        this.previousMonth();
      } else if (e.target.matches('.next-month')) {
        this.nextMonth();
      }
    });
    
    // Apply and clear buttons
    const applyBtn = document.getElementById('apply-dates');
    const clearBtn = document.getElementById('clear-dates');
    
    if (applyBtn) {
      applyBtn.addEventListener('click', () => this.applyDates());
    }
    
    if (clearBtn) {
      clearBtn.addEventListener('click', () => this.clearDates());
    }
    
    // Close popup when clicking outside
    document.addEventListener('click', (e) => {
      if (!this.popup.contains(e.target) && e.target !== this.input) {
        this.hidePopup();
      }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (this.popup.classList.contains('active')) {
        if (e.key === 'Escape') {
          this.hidePopup();
        }
      }
    });
  }
  
  showPopup() {
    this.popup.classList.add('active');
    this.updateCalendars();
  }
  
  hidePopup() {
    this.popup.classList.remove('active');
    this.clearSelectionHint();
  }
  
  showSelectionHint(message) {
    // Create or update hint element
    let hint = this.popup.querySelector('.selection-hint');
    if (!hint) {
      hint = document.createElement('div');
      hint.className = 'selection-hint';
      this.popup.querySelector('.date-picker-header').appendChild(hint);
    }
    
    hint.textContent = message;
    hint.classList.add('active');
    
    // Auto-clear hint after 3 seconds
    setTimeout(() => {
      this.clearSelectionHint();
    }, 3000);
  }
  
  clearSelectionHint() {
    const hint = this.popup.querySelector('.selection-hint');
    if (hint) {
      hint.classList.remove('active');
    }
  }
  
  updateStatus(message) {
    if (this.statusElement) {
      this.statusElement.textContent = message;
    }
  }
  
  selectDate(date) {
    if (this.selectionStep === 'start' || (this.selectedStartDate && this.selectedEndDate)) {
      // Start fresh selection
      this.selectedStartDate = new Date(date);
      this.selectedEndDate = null;
      this.selectionStep = 'end';
      this.updateStatus('Select check-out date');
      
    } else if (this.selectionStep === 'end') {
      // Complete the selection
      if (date >= this.selectedStartDate) {
        this.selectedEndDate = new Date(date);
      } else {
        // Swap if end is before start
        this.selectedEndDate = this.selectedStartDate;
        this.selectedStartDate = new Date(date);
      }
      
      this.selectionStep = 'complete';
      this.updateStatus('Dates selected');
      
      // Auto-apply and close after brief delay
      setTimeout(() => {
        this.applyDates();
        this.hidePopup();
      }, 500);
    }
    
    this.updateCalendars();
    this.updateInputDisplay();
  }
  
  applyDates() {
    if (this.selectedStartDate) {
      this.startDateInput.value = this.formatDate(this.selectedStartDate);
    }
    
    if (this.selectedEndDate) {
      this.endDateInput.value = this.formatDate(this.selectedEndDate);
    }
    
    this.updateInputDisplay();
    this.hidePopup();
  }
  
  clearDates() {
    this.selectedStartDate = null;
    this.selectedEndDate = null;
    this.selectionStep = 'start';
    this.startDateInput.value = '';
    this.endDateInput.value = '';
    this.input.value = '';
    this.updateStatus('Select check-in date');
    this.updateCalendars();
  }
  
  updateInputDisplay() {
    let displayText = '';
    
    if (this.selectedStartDate) {
      displayText = this.formatDisplayDate(this.selectedStartDate);
      
      if (this.selectedEndDate) {
        displayText += ' — ' + this.formatDisplayDate(this.selectedEndDate);
      }
    }
    
    this.input.value = displayText;
    this.input.setAttribute('placeholder', displayText || 'Select trip dates');
  }
  
  updateCalendars() {
    const calendars = document.querySelectorAll('.date-picker-calendar');
    calendars.forEach(calendar => {
      this.populateCalendarDays(calendar, this.currentMonth);
    });
  }
  
  previousMonth() {
    this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
    this.updateMonthDisplay();
    this.updateCalendars();
  }
  
  nextMonth() {
    this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
    this.updateMonthDisplay();
    this.updateCalendars();
  }
  
  updateMonthDisplay() {
    const monthDisplays = document.querySelectorAll('.current-month');
    monthDisplays.forEach(display => {
      display.textContent = this.formatMonth(this.currentMonth);
    });
  }
  
  loadExistingDates() {
    const startValue = this.startDateInput.value;
    const endValue = this.endDateInput.value;
    
    if (startValue) {
      this.selectedStartDate = new Date(startValue + 'T00:00:00');
    }
    
    if (endValue) {
      this.selectedEndDate = new Date(endValue + 'T00:00:00');
    }
    
    this.updateInputDisplay();
  }
  
  // Utility methods
  formatDate(date) {
    return date.toISOString().split('T')[0];
  }
  
  formatDisplayDate(date) {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }
  
  formatMonth(date) {
    return date.toLocaleDateString('en-US', {
      month: 'long',
      year: 'numeric'
    });
  }
  
  isToday(date) {
    const today = new Date();
    return date.toDateString() === today.toDateString();
  }
  
  isSelected(date) {
    return (this.selectedStartDate && date.toDateString() === this.selectedStartDate.toDateString()) ||
           (this.selectedEndDate && date.toDateString() === this.selectedEndDate.toDateString());
  }
  
  isInRange(date) {
    if (!this.selectedStartDate) return false;
    
    if (this.selectedEndDate) {
      return date > this.selectedStartDate && date < this.selectedEndDate;
    }
    
    // If only start date selected, don't highlight range yet
    return false;
  }
  
  isStartDate(date) {
    return this.selectedStartDate && date.toDateString() === this.selectedStartDate.toDateString();
  }
  
  isEndDate(date) {
    return this.selectedEndDate && date.toDateString() === this.selectedEndDate.toDateString();
  }
}

// Initialize date range picker when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  const dateRangeInput = document.getElementById('date_range');
  if (dateRangeInput) {
    window.tripDatePicker = new DateRangePicker(dateRangeInput);
  }
});