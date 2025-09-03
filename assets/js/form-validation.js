/**
 * Form Validation for BeyondTrailTales
 * Client-side form validation with ADA compliance
 */

(function() {
    'use strict';
    
    // Validation rules
    const validators = {
        required: (value) => value && value.trim().length > 0,
        email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        minLength: (value, min) => value && value.length >= min,
        maxLength: (value, max) => value && value.length <= max,
        number: (value) => !isNaN(value) && isFinite(value),
        min: (value, min) => Number(value) >= Number(min),
        max: (value, max) => Number(value) <= Number(max)
    };
    
    // Validation messages
    const messages = {
        required: 'This field is required',
        email: 'Please enter a valid email address',
        minLength: 'Please enter at least {min} characters',
        maxLength: 'Please enter no more than {max} characters',
        number: 'Please enter a valid number',
        min: 'Please enter a value greater than or equal to {min}',
        max: 'Please enter a value less than or equal to {max}'
    };
    
    // Validate single field
    function validateField(field) {
        const value = field.value;
        const errors = [];
        
        // Check required
        if (field.hasAttribute('required') && !validators.required(value)) {
            errors.push(messages.required);
        }
        
        // Check email
        if (field.type === 'email' && value && !validators.email(value)) {
            errors.push(messages.email);
        }
        
        // Check minLength
        if (field.hasAttribute('minlength')) {
            const min = field.getAttribute('minlength');
            if (!validators.minLength(value, min)) {
                errors.push(messages.minLength.replace('{min}', min));
            }
        }
        
        // Check maxLength
        if (field.hasAttribute('maxlength')) {
            const max = field.getAttribute('maxlength');
            if (!validators.maxLength(value, max)) {
                errors.push(messages.maxLength.replace('{max}', max));
            }
        }
        
        // Check number fields
        if (field.type === 'number' && value) {
            if (!validators.number(value)) {
                errors.push(messages.number);
            } else {
                if (field.hasAttribute('min')) {
                    const min = field.getAttribute('min');
                    if (!validators.min(value, min)) {
                        errors.push(messages.min.replace('{min}', min));
                    }
                }
                if (field.hasAttribute('max')) {
                    const max = field.getAttribute('max');
                    if (!validators.max(value, max)) {
                        errors.push(messages.max.replace('{max}', max));
                    }
                }
            }
        }
        
        return errors;
    }
    
    // Show field errors
    function showFieldErrors(field, errors) {
        // Remove existing error messages
        clearFieldErrors(field);
        
        if (errors.length > 0) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            
            // Create error container
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = errors[0]; // Show first error
            
            // Insert after field
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
            
            // Update ARIA attributes
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', errorDiv.id || 'error-' + field.id);
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            field.setAttribute('aria-invalid', 'false');
        }
    }
    
    // Clear field errors
    function clearFieldErrors(field) {
        field.classList.remove('is-invalid', 'is-valid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    // Validate entire form
    function validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('input, textarea, select');
        
        fields.forEach(field => {
            const errors = validateField(field);
            showFieldErrors(field, errors);
            if (errors.length > 0) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    // Initialize form validation
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form[data-validate], form.needs-validation');
        
        forms.forEach(form => {
            // Disable HTML5 validation
            form.setAttribute('novalidate', 'true');
            
            // Add blur validation
            const fields = form.querySelectorAll('input, textarea, select');
            fields.forEach(field => {
                field.addEventListener('blur', function() {
                    const errors = validateField(field);
                    showFieldErrors(field, errors);
                });
                
                // Clear errors on input
                field.addEventListener('input', function() {
                    if (field.classList.contains('is-invalid')) {
                        clearFieldErrors(field);
                    }
                });
            });
            
            // Add submit validation
            form.addEventListener('submit', function(e) {
                if (!validateForm(form)) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Focus first invalid field
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                    }
                }
            });
        });
        
        console.log('✅ Form validation initialized');
    });
})();
