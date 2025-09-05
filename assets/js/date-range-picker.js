/**
 * Date Range Picker for Trip Form
 * Lightweight date range picker with calendar UI
 */

class DateRangePicker {
  constructor(inputElement, options = {}) {
    this.input = inputElement;
    this.popup = document.getElementById('date-picker-popup');
    this.startDateInput = document.getElementById('start_date');
    this.endDateInput = document.getElementById('end_date');
    
    this.options = {
      format: 'YYYY-MM-DD',
      displayFormat: 'MMM DD, YYYY',
      ...options
    };
    
    this.selectedStartDate = null;
    this.selectedEndDate = null;
    this.currentMonth = new Date();
    
    this.init();
  }
  
  init() {
    this.createCalendars();
    this.attachEventListeners();
    this.loadExistingDates();
  }
  
  createCalendars() {
    const startCalendarContainer = document.getElementById('start-calendar');
    const endCalendarContainer = document.getElementById('end-calendar');
    
    if (!startCalendarContainer || !endCalendarContainer) return;
    
    // Create calendar headers
    startCalendarContainer.innerHTML = this.createCalendarHeader('Start Date');
    endCalendarContainer.innerHTML = this.createCalendarHeader('End Date');
    
    // Create calendar grids
    startCalendarContainer.appendChild(this.createCalendarGrid('start'));
    endCalendarContainer.appendChild(this.createCalendarGrid('end'));
  }
  
  createCalendarHeader(title) {
    return `
      <div class="calendar-header">
        <h4 class="calendar-title">${title}</h4>
        <div class="calendar-nav">
          <button type="button" class="nav-btn prev-month" data-calendar="start">‹</button>
          <span class="current-month">${this.formatMonth(this.currentMonth)}</span>
          <button type="button" class="nav-btn next-month" data-calendar="start">›</button>
        </div>
      </div>
    `;
  }
  
  createCalendarGrid(type) {
    const grid = document.createElement('div');
    grid.className = 'date-picker-calendar';
    grid.setAttribute('data-calendar', type);
    
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
      
      if (this.isSelected(date)) {
        dayElement.classList.add('selected');
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
  }
  
  selectDate(date) {
    if (!this.selectedStartDate || (this.selectedStartDate && this.selectedEndDate)) {
      // Start new selection
      this.selectedStartDate = new Date(date);
      this.selectedEndDate = null;
    } else if (date >= this.selectedStartDate) {
      // Select end date
      this.selectedEndDate = new Date(date);
    } else {
      // Selected date is before start date, make it the new start date
      this.selectedEndDate = this.selectedStartDate;
      this.selectedStartDate = new Date(date);
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
    this.startDateInput.value = '';
    this.endDateInput.value = '';
    this.input.value = '';
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
    if (!this.selectedStartDate || !this.selectedEndDate) return false;
    return date >= this.selectedStartDate && date <= this.selectedEndDate;
  }
}

// Initialize date range picker when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  const dateRangeInput = document.getElementById('date_range');
  if (dateRangeInput) {
    window.tripDatePicker = new DateRangePicker(dateRangeInput);
  }
});