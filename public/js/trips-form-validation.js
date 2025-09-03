/**
 * Trips Form Validation Integration
 * Custom validation rules and handlers for trip forms
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing trip form validation...');
    
    // Get form references
    const tripForm = document.getElementById('trip-form');
    if (!tripForm) {
        console.log('Trip form not found on this page');
        return;
    }
    
    // Custom date validation
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (startDate && endDate) {
        // Add custom validator for end date
        window.formValidator.setCustomValidator(
            tripForm,
            endDate,
            (value) => {
                if (value && startDate.value) {
                    if (new Date(value) < new Date(startDate.value)) {
                        return 'End date must be after start date';
                    }
                }
                return true;
            }
        );
        
        // Re-validate end date when start date changes
        startDate.addEventListener('change', () => {
            if (endDate.value) {
                window.formValidator.validateField(endDate, tripForm);
            }
        });
    }
    
    // Photo upload validation
    const photoInput = document.getElementById('photo');
    const photoAltText = document.getElementById('photo_alt_text');
    const photoPreview = document.getElementById('photo-preview');
    const previewImage = document.getElementById('preview-image');
    const removePhotoBtn = document.getElementById('remove-photo');
    
    if (photoInput && photoAltText) {
        // Handle photo upload
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file size (max 4MB)
                if (file.size > 4 * 1024 * 1024) {
                    alert('File size must be less than 4MB');
                    photoInput.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    alert('Please upload a JPG, JPEG, or PNG image');
                    photoInput.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    photoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
                
                // Make alt text required when photo is uploaded
                photoAltText.setAttribute('required', 'required');
                
                // Update label to show it's required
                const altTextLabel = photoAltText.previousElementSibling;
                if (altTextLabel && !altTextLabel.querySelector('.required')) {
                    altTextLabel.innerHTML += ' <span class="required">*</span>';
                }
            }
        });
        
        // Remove photo handler
        if (removePhotoBtn) {
            removePhotoBtn.addEventListener('click', function() {
                photoInput.value = '';
                photoPreview.style.display = 'none';
                previewImage.src = '';
                
                // Remove required from alt text
                photoAltText.removeAttribute('required');
                
                // Update label
                const altTextLabel = photoAltText.previousElementSibling;
                if (altTextLabel) {
                    const requiredSpan = altTextLabel.querySelector('.required');
                    if (requiredSpan) {
                        requiredSpan.remove();
                    }
                }
                
                // Clear validation state
                photoAltText.classList.remove('is-invalid', 'is-valid');
            });
        }
    }
    
    // Custom submit handler
    window.formValidator.setSubmitHandler(
        tripForm,
        async (form, event) => {
            // Get form data
            const formData = new FormData(form);
            
            // Get submit button
            const submitBtn = form.querySelector('[type="submit"], #btn-save-trip, .btn-save-trip');
            const originalText = submitBtn ? submitBtn.textContent : '';
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving trip...';
                
                // Add loading class if available
                if (window.setButtonLoading) {
                    window.setButtonLoading(submitBtn, 'Saving...');
                }
            }
            
            try {
                // Submit via AJAX
                const response = await fetch('/ajax/save_trip.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    if (window.showSuccess) {
                        window.showSuccess(result.message || 'Trip saved successfully!');
                    } else {
                        alert('Trip saved successfully!');
                    }
                    
                    // Update trip ID if creating new
                    if (result.trip_id) {
                        const tripIdInput = document.getElementById('trip-id');
                        if (tripIdInput) {
                            tripIdInput.value = result.trip_id;
                        }
                    }
                    
                    // Reload trips list if function exists
                    if (window.loadTrips) {
                        window.loadTrips();
                    }
                    
                    // Switch to my trips view if creating new
                    if (!formData.get('id') && window.switchToMyTrips) {
                        setTimeout(() => {
                            window.switchToMyTrips();
                        }, 1500);
                    }
                } else {
                    // Show error
                    if (window.showError) {
                        window.showError(result.message || 'Failed to save trip');
                    } else {
                        alert(result.message || 'Failed to save trip');
                    }
                }
            } catch (error) {
                console.error('Error saving trip:', error);
                
                if (window.showError) {
                    window.showError('Network error. Please try again.');
                } else {
                    alert('Network error. Please try again.');
                }
            } finally {
                // Restore button state
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    
                    if (window.removeButtonLoading) {
                        window.removeButtonLoading(submitBtn);
                    }
                }
            }
            
            return false; // Prevent default form submission
        }
    );
    
    // Initialize form sections/tabs if they exist
    const formTabs = document.querySelectorAll('.pack-tabs button[role="tab"]');
    const formPanels = document.querySelectorAll('.pack-section[role="tabpanel"]');
    
    formTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active from all tabs
            formTabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            
            // Hide all panels
            formPanels.forEach(panel => {
                panel.hidden = true;
            });
            
            // Activate clicked tab
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            
            // Show corresponding panel
            const targetPanel = document.getElementById(this.getAttribute('aria-controls'));
            if (targetPanel) {
                targetPanel.hidden = false;
                
                // Focus first input in panel for accessibility
                const firstInput = targetPanel.querySelector('input, select, textarea');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            }
        });
    });
    
    // Handle weight unit conversion
    const distanceInput = document.getElementById('distance');
    const distanceUnit = document.getElementById('distance_unit');
    
    if (distanceInput && distanceUnit) {
        distanceUnit.addEventListener('change', function() {
            const currentValue = parseFloat(distanceInput.value) || 0;
            
            if (this.value === 'km' && this.dataset.previousUnit === 'miles') {
                // Convert miles to km
                distanceInput.value = (currentValue * 1.60934).toFixed(1);
            } else if (this.value === 'miles' && this.dataset.previousUnit === 'km') {
                // Convert km to miles
                distanceInput.value = (currentValue / 1.60934).toFixed(1);
            }
            
            this.dataset.previousUnit = this.value;
        });
        
        // Store initial unit
        distanceUnit.dataset.previousUnit = distanceUnit.value;
    }
    
    console.log('✅ Trip form validation initialized');
});
