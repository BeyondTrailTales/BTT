/**
 * Trips Form Validation
 * Specific validation rules for trip forms
 */

(function() {
    'use strict';
    
    // Trip-specific validation rules
    const tripValidators = {
        // Validate date range
        validateDateRange: function(startDate, endDate) {
            if (!startDate || !endDate) return true;
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            return end >= start;
        },
        
        // Validate photo alt text when photo is present
        validatePhotoAlt: function(photoInput, altTextInput) {
            if (photoInput && photoInput.files && photoInput.files.length > 0) {
                return altTextInput && altTextInput.value.trim().length > 0;
            }
            return true;
        },
        
        // Validate trip duration
        validateDuration: function(startDate, endDate, tripType) {
            if (!startDate || !tripType) return true;
            
            const start = new Date(startDate);
            const end = endDate ? new Date(endDate) : start;
            const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            
            // Check if duration matches trip type
            switch(tripType) {
                case 'day_hike':
                    return days === 1;
                case 'overnight':
                    return days === 2;
                case 'weekend':
                    return days >= 2 && days <= 4;
                default:
                    return true;
            }
        },
        
        // Validate permit info when permit is required
        validatePermit: function(permitRequired, permitInfo) {
            if (permitRequired === '1' || permitRequired === 1) {
                return permitInfo && permitInfo.trim().length > 0;
            }
            return true;
        }
    };
    
    // Enhance trip form validation
    document.addEventListener('DOMContentLoaded', function() {
        const tripForm = document.getElementById('trip-form');
        if (!tripForm) return;
        
        // Add custom validation on form submit
        tripForm.addEventListener('submit', function(e) {
            const errors = [];
            
            // Get form elements
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const tripType = document.getElementById('trip_type');
            const photoInput = document.getElementById('photo');
            const altText = document.getElementById('photo_alt_text');
            const permitReq = document.getElementById('permit_required');
            const permitInfo = document.getElementById('permit_info');
            
            // Validate date range
            if (!tripValidators.validateDateRange(startDate?.value, endDate?.value)) {
                errors.push('End date cannot be before start date');
                endDate?.classList.add('is-invalid');
            }
            
            // Validate photo alt text
            if (!tripValidators.validatePhotoAlt(photoInput, altText)) {
                errors.push('Alt text is required when uploading a photo (ADA compliance)');
                altText?.classList.add('is-invalid');
            }
            
            // Validate trip duration
            if (!tripValidators.validateDuration(startDate?.value, endDate?.value, tripType?.value)) {
                const typeText = tripType.options[tripType.selectedIndex].text;
                errors.push(`Date range doesn't match selected trip type (${typeText})`);
                tripType?.classList.add('is-invalid');
            }
            
            // Validate permit info
            if (!tripValidators.validatePermit(permitReq?.value, permitInfo?.value)) {
                errors.push('Permit information is required when permit is needed');
                permitInfo?.classList.add('is-invalid');
            }
            
            // If there are errors, prevent submission
            if (errors.length > 0) {
                e.preventDefault();
                e.stopPropagation();
                
                // Show errors
                const errorContainer = document.getElementById('form-errors');
                if (errorContainer) {
                    errorContainer.innerHTML = `
                        <div class="alert alert-error">
                            <strong>Please fix the following errors:</strong>
                            <ul>${errors.map(err => `<li>${err}</li>`).join('')}</ul>
                        </div>
                    `;
                    errorContainer.hidden = false;
                    errorContainer.focus();
                }
            }
        });
        
        // Real-time date validation
        const endDateInput = document.getElementById('end_date');
        const startDateInput = document.getElementById('start_date');
        
        if (endDateInput && startDateInput) {
            endDateInput.addEventListener('change', function() {
                if (!tripValidators.validateDateRange(startDateInput.value, endDateInput.value)) {
                    endDateInput.setCustomValidity('End date cannot be before start date');
                    endDateInput.classList.add('is-invalid');
                } else {
                    endDateInput.setCustomValidity('');
                    endDateInput.classList.remove('is-invalid');
                }
            });
            
            startDateInput.addEventListener('change', function() {
                if (endDateInput.value) {
                    endDateInput.dispatchEvent(new Event('change'));
                }
            });
        }
        
        // Photo alt text validation
        const photoInput = document.getElementById('photo');
        const altTextInput = document.getElementById('photo_alt_text');
        
        if (photoInput && altTextInput) {
            photoInput.addEventListener('change', function() {
                if (photoInput.files && photoInput.files.length > 0) {
                    altTextInput.setAttribute('required', 'required');
                    altTextInput.setAttribute('aria-required', 'true');
                    
                    // Focus alt text field
                    if (!altTextInput.value) {
                        altTextInput.focus();
                    }
                } else {
                    altTextInput.removeAttribute('required');
                    altTextInput.removeAttribute('aria-required');
                }
            });
        }
        
        console.log('✅ Trips form validation initialized');
    });
    
    // Expose validators for external use
    window.TripValidators = tripValidators;
})();
