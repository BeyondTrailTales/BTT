/**
 * Enhanced Date Picker Functionality
 * Adds quick date selection and better UX for date inputs
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find all date inputs
    const dateInputs = document.querySelectorAll('input[type="date"], .date-input');
    
    dateInputs.forEach(input => {
        // Create wrapper if not already wrapped
        if (!input.closest('.date-input-group')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'date-input-group';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }
        
        // Add quick date options for start date inputs
        if (input.name === 'start_date' || input.id === 'start_date') {
            addQuickDateOptions(input);
        }
        
        // Add date range display for date pairs
        if (input.name === 'start_date' || input.name === 'end_date') {
            setupDateRangeDisplay(input);
        }
        
        // Improve native date picker interaction
        improveDateInput(input);
    });
    
    function addQuickDateOptions(input) {
        const quickOptions = document.createElement('div');
        quickOptions.className = 'quick-date-options';
        
        const options = [
            { label: 'Today', days: 0 },
            { label: 'Tomorrow', days: 1 },
            { label: 'This Weekend', days: 'weekend' },
            { label: 'Next Week', days: 7 },
            { label: 'Next Month', days: 30 }
        ];
        
        options.forEach(option => {
            const btn = document.createElement('button');
            btn.className = 'quick-date-btn';
            btn.textContent = option.label;
            btn.type = 'button';
            
            btn.addEventListener('click', function() {
                const startDate = new Date();
                let endDate = new Date();
                
                if (option.days === 'weekend') {
                    // Find next Saturday
                    const day = startDate.getDay();
                    const daysUntilSaturday = (6 - day + 7) % 7 || 7;
                    startDate.setDate(startDate.getDate() + daysUntilSaturday);
                    endDate = new Date(startDate);
                    endDate.setDate(endDate.getDate() + 1); // Sunday
                } else {
                    startDate.setDate(startDate.getDate() + option.days);
                    endDate = new Date(startDate);
                    endDate.setDate(endDate.getDate() + 1); // Default 2-day trip
                }
                
                // Set the dates
                input.value = formatDateForInput(startDate);
                const endDateInput = document.querySelector('input[name="end_date"], #end_date');
                if (endDateInput) {
                    endDateInput.value = formatDateForInput(endDate);
                }
                
                // Update active button
                document.querySelectorAll('.quick-date-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Trigger change events
                input.dispatchEvent(new Event('change', { bubbles: true }));
                if (endDateInput) {
                    endDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            
            quickOptions.appendChild(btn);
        });
        
        input.parentNode.appendChild(quickOptions);
    }
    
    function setupDateRangeDisplay(input) {
        const form = input.closest('form');
        if (!form) return;
        
        const startInput = form.querySelector('input[name="start_date"], #start_date');
        const endInput = form.querySelector('input[name="end_date"], #end_date');
        
        if (!startInput || !endInput) return;
        
        // Create duration display if not exists
        let durationDisplay = form.querySelector('.trip-duration-display');
        if (!durationDisplay) {
            durationDisplay = document.createElement('div');
            durationDisplay.className = 'trip-duration-display';
            
            // Insert after the date inputs
            const dateSection = endInput.closest('.form-group') || endInput.parentNode;
            dateSection.appendChild(durationDisplay);
        }
        
        // Update duration on change
        function updateDuration() {
            if (startInput.value && endInput.value) {
                const start = new Date(startInput.value);
                const end = new Date(endInput.value);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Include both days
                
                if (diffDays > 0 && diffDays <= 365) {
                    durationDisplay.innerHTML = `Trip Duration: <strong>${diffDays} ${diffDays === 1 ? 'day' : 'days'}</strong>`;
                    durationDisplay.style.display = 'block';
                } else {
                    durationDisplay.style.display = 'none';
                }
            } else {
                durationDisplay.style.display = 'none';
            }
        }
        
        startInput.addEventListener('change', updateDuration);
        endInput.addEventListener('change', updateDuration);
        
        // Initial update
        updateDuration();
    }
    
    function improveDateInput(input) {
        // Add min/max constraints for better UX
        if (!input.min) {
            // Default min to today
            input.min = formatDateForInput(new Date());
        }
        
        if (!input.max) {
            // Default max to 2 years from now
            const maxDate = new Date();
            maxDate.setFullYear(maxDate.getFullYear() + 2);
            input.max = formatDateForInput(maxDate);
        }
        
        // Auto-adjust end date when start date changes
        if (input.name === 'start_date' || input.id === 'start_date') {
            input.addEventListener('change', function() {
                const endInput = document.querySelector('input[name="end_date"], #end_date');
                if (endInput && input.value) {
                    // Set end date min to start date
                    endInput.min = input.value;
                    
                    // If end date is before start date, update it
                    if (endInput.value && endInput.value < input.value) {
                        endInput.value = input.value;
                    }
                    
                    // If no end date set, default to 2 days after start
                    if (!endInput.value) {
                        const startDate = new Date(input.value);
                        startDate.setDate(startDate.getDate() + 1);
                        endInput.value = formatDateForInput(startDate);
                    }
                }
            });
        }
        
        // Add visual feedback
        input.addEventListener('focus', function() {
            input.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            input.classList.remove('focused');
        });
    }
    
    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
});