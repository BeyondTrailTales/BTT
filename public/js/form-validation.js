/**
 * Form Validation System
 * Client-side validation with focus management and ARIA support
 */

class FormValidator {
    constructor() {
        this.forms = new Map();
        this.validators = {
            required: this.validateRequired,
            email: this.validateEmail,
            minLength: this.validateMinLength,
            maxLength: this.validateMaxLength,
            pattern: this.validatePattern,
            number: this.validateNumber,
            date: this.validateDate,
            url: this.validateUrl,
            phone: this.validatePhone,
            match: this.validateMatch,
            custom: this.validateCustom
        };
        
        this.init();
    }
    
    init() {
        // Auto-initialize forms with data-validate attribute
        document.querySelectorAll('form[data-validate]').forEach(form => {
            this.initializeForm(form);
        });
        
        // Add global styles
        this.injectStyles();
    }
    
    /**
     * Initialize form validation
     */
    initializeForm(form) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }
        
        if (!form) return;
        
        // Store form config
        this.forms.set(form, {
            fields: new Map(),
            submitHandler: null,
            options: {
                validateOnBlur: true,
                validateOnInput: false,
                showSuccessState: true,
                focusFirstError: true,
                scrollToError: true
            }
        });
        
        // Setup event listeners
        this.setupFormListeners(form);
        
        // Initialize fields
        form.querySelectorAll('[data-validate], [required]').forEach(field => {
            this.setupField(form, field);
        });
    }
    
    /**
     * Setup form event listeners
     */
    setupFormListeners(form) {
        // Prevent HTML5 validation
        form.setAttribute('novalidate', 'true');
        
        // Submit handler
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit(form, e);
        });
        
        // Reset handler
        form.addEventListener('reset', () => {
            this.clearValidation(form);
        });
    }
    
    /**
     * Setup field validation
     */
    setupField(form, field) {
        const formData = this.forms.get(form);
        if (!formData) return;
        
        // Parse validation rules
        const rules = this.parseRules(field);
        
        // Store field config
        formData.fields.set(field, {
            rules: rules,
            valid: null,
            message: '',
            touched: false
        });
        
        // Add ARIA attributes
        field.setAttribute('aria-invalid', 'false');
        
        // Create feedback elements
        this.createFeedbackElements(field);
        
        // Setup event listeners
        if (formData.options.validateOnBlur) {
            field.addEventListener('blur', () => {
                this.validateField(field, form);
                this.markTouched(field, form);
            });
        }
        
        if (formData.options.validateOnInput) {
            field.addEventListener('input', debounce(() => {
                if (this.isTouched(field, form)) {
                    this.validateField(field, form);
                }
            }, 300));
        }
        
        // Character counter for maxlength
        if (field.hasAttribute('maxlength')) {
            this.setupCharacterCounter(field);
        }
    }
    
    /**
     * Parse validation rules from field
     */
    parseRules(field) {
        const rules = {};
        
        // Required
        if (field.hasAttribute('required')) {
            rules.required = true;
        }
        
        // Type-based validation
        const type = field.type;
        if (type === 'email') {
            rules.email = true;
        } else if (type === 'url') {
            rules.url = true;
        } else if (type === 'tel') {
            rules.phone = true;
        } else if (type === 'number') {
            rules.number = true;
        }
        
        // Data attributes
        const validateAttr = field.dataset.validate;
        if (validateAttr) {
            validateAttr.split(' ').forEach(rule => {
                const [name, value] = rule.split(':');
                rules[name] = value || true;
            });
        }
        
        // Min/Max length
        if (field.hasAttribute('minlength')) {
            rules.minLength = parseInt(field.getAttribute('minlength'));
        }
        if (field.hasAttribute('maxlength')) {
            rules.maxLength = parseInt(field.getAttribute('maxlength'));
        }
        
        // Pattern
        if (field.hasAttribute('pattern')) {
            rules.pattern = field.getAttribute('pattern');
        }
        
        // Min/Max for numbers
        if (field.hasAttribute('min')) {
            rules.min = parseFloat(field.getAttribute('min'));
        }
        if (field.hasAttribute('max')) {
            rules.max = parseFloat(field.getAttribute('max'));
        }
        
        return rules;
    }
    
    /**
     * Validate field
     */
    validateField(field, form) {
        const formData = this.forms.get(form);
        if (!formData) return true;
        
        const fieldData = formData.fields.get(field);
        if (!fieldData) return true;
        
        const value = field.value.trim();
        let isValid = true;
        let message = '';
        
        // Check each validation rule
        for (const [rule, param] of Object.entries(fieldData.rules)) {
            const validator = this.validators[rule];
            if (validator) {
                const result = validator.call(this, value, param, field);
                if (result !== true) {
                    isValid = false;
                    message = result;
                    break;
                }
            }
        }
        
        // Update field state
        fieldData.valid = isValid;
        fieldData.message = message;
        
        // Update UI
        this.updateFieldUI(field, isValid, message, formData.options);
        
        return isValid;
    }
    
    /**
     * Validation rules
     */
    validateRequired(value) {
        if (value.length === 0) {
            return 'This field is required';
        }
        return true;
    }
    
    validateEmail(value) {
        if (value.length === 0) return true;
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!pattern.test(value)) {
            return 'Please enter a valid email address';
        }
        return true;
    }
    
    validateMinLength(value, min) {
        if (value.length === 0) return true;
        if (value.length < min) {
            return `Must be at least ${min} characters`;
        }
        return true;
    }
    
    validateMaxLength(value, max) {
        if (value.length > max) {
            return `Must be no more than ${max} characters`;
        }
        return true;
    }
    
    validatePattern(value, pattern) {
        if (value.length === 0) return true;
        const regex = new RegExp(pattern);
        if (!regex.test(value)) {
            return 'Please match the required format';
        }
        return true;
    }
    
    validateNumber(value) {
        if (value.length === 0) return true;
        if (isNaN(value)) {
            return 'Please enter a valid number';
        }
        return true;
    }
    
    validateDate(value) {
        if (value.length === 0) return true;
        const date = new Date(value);
        if (isNaN(date.getTime())) {
            return 'Please enter a valid date';
        }
        return true;
    }
    
    validateUrl(value) {
        if (value.length === 0) return true;
        try {
            new URL(value);
            return true;
        } catch {
            return 'Please enter a valid URL';
        }
    }
    
    validatePhone(value) {
        if (value.length === 0) return true;
        const pattern = /^[\d\s\-\+\(\)]+$/;
        if (!pattern.test(value) || value.length < 10) {
            return 'Please enter a valid phone number';
        }
        return true;
    }
    
    validateMatch(value, targetSelector, field) {
        const target = field.form.querySelector(targetSelector);
        if (!target) return true;
        if (value !== target.value) {
            return 'Values do not match';
        }
        return true;
    }
    
    validateCustom(value, validator) {
        if (typeof validator === 'function') {
            return validator(value);
        }
        return true;
    }
    
    /**
     * Update field UI based on validation
     */
    updateFieldUI(field, isValid, message, options) {
        // Remove existing states
        field.classList.remove('is-valid', 'is-invalid');
        
        // Get feedback elements
        const validFeedback = field.parentElement.querySelector('.valid-feedback');
        const invalidFeedback = field.parentElement.querySelector('.invalid-feedback');
        
        if (isValid) {
            if (options.showSuccessState && field.value.trim().length > 0) {
                field.classList.add('is-valid');
                field.setAttribute('aria-invalid', 'false');
                
                if (validFeedback) {
                    validFeedback.textContent = 'Looks good!';
                }
            }
        } else {
            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
            
            if (invalidFeedback) {
                invalidFeedback.textContent = message;
                
                // Associate error with field for screen readers
                const errorId = `${field.id || field.name}-error`;
                invalidFeedback.id = errorId;
                field.setAttribute('aria-describedby', errorId);
            }
        }
    }
    
    /**
     * Create feedback elements
     */
    createFeedbackElements(field) {
        const parent = field.parentElement;
        
        if (!parent.querySelector('.valid-feedback')) {
            const validDiv = document.createElement('div');
            validDiv.className = 'valid-feedback';
            parent.appendChild(validDiv);
        }
        
        if (!parent.querySelector('.invalid-feedback')) {
            const invalidDiv = document.createElement('div');
            invalidDiv.className = 'invalid-feedback';
            invalidDiv.setAttribute('role', 'alert');
            invalidDiv.setAttribute('aria-live', 'polite');
            parent.appendChild(invalidDiv);
        }
    }
    
    /**
     * Setup character counter
     */
    setupCharacterCounter(field) {
        const maxLength = parseInt(field.getAttribute('maxlength'));
        if (!maxLength) return;
        
        // Create counter element
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        field.parentElement.appendChild(counter);
        
        // Update counter
        const updateCounter = () => {
            const current = field.value.length;
            const remaining = maxLength - current;
            
            counter.textContent = `${current} / ${maxLength}`;
            
            // Update classes
            counter.classList.remove('warning', 'danger');
            if (remaining <= 10) {
                counter.classList.add('warning');
            }
            if (remaining <= 0) {
                counter.classList.add('danger');
            }
        };
        
        // Initial update
        updateCounter();
        
        // Listen for changes
        field.addEventListener('input', updateCounter);
    }
    
    /**
     * Handle form submission
     */
    async handleSubmit(form, event) {
        const formData = this.forms.get(form);
        if (!formData) return;
        
        // Show loading state on submit button
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn && window.setButtonLoading) {
            window.setButtonLoading(submitBtn, 'Validating...');
        }
        
        // Validate all fields
        let isValid = true;
        let firstInvalidField = null;
        
        formData.fields.forEach((fieldData, field) => {
            const fieldValid = this.validateField(field, form);
            if (!fieldValid && !firstInvalidField) {
                firstInvalidField = field;
            }
            isValid = isValid && fieldValid;
        });
        
        // Remove button loading state
        if (submitBtn && window.removeButtonLoading) {
            window.removeButtonLoading(submitBtn);
        }
        
        if (!isValid) {
            // Focus first invalid field
            if (formData.options.focusFirstError && firstInvalidField) {
                // Scroll to field
                if (formData.options.scrollToError) {
                    firstInvalidField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
                
                // Focus after scroll
                setTimeout(() => {
                    firstInvalidField.focus();
                    
                    // Announce error for screen readers
                    const errorMessage = firstInvalidField.parentElement.querySelector('.invalid-feedback')?.textContent;
                    if (errorMessage) {
                        this.announceError(`Validation error: ${errorMessage}`);
                    }
                }, 300);
            }
            
            // Show error toast
            if (window.showError) {
                window.showError('Please fix the errors in the form');
            }
            
            return false;
        }
        
        // If custom submit handler exists, use it
        if (formData.submitHandler) {
            return formData.submitHandler(form, event);
        }
        
        // Otherwise, submit normally
        form.submit();
        return true;
    }
    
    /**
     * Mark field as touched
     */
    markTouched(field, form) {
        const formData = this.forms.get(form);
        if (!formData) return;
        
        const fieldData = formData.fields.get(field);
        if (fieldData) {
            fieldData.touched = true;
        }
    }
    
    /**
     * Check if field is touched
     */
    isTouched(field, form) {
        const formData = this.forms.get(form);
        if (!formData) return false;
        
        const fieldData = formData.fields.get(field);
        return fieldData ? fieldData.touched : false;
    }
    
    /**
     * Clear validation state
     */
    clearValidation(form) {
        const formData = this.forms.get(form);
        if (!formData) return;
        
        formData.fields.forEach((fieldData, field) => {
            // Reset field data
            fieldData.valid = null;
            fieldData.message = '';
            fieldData.touched = false;
            
            // Reset UI
            field.classList.remove('is-valid', 'is-invalid');
            field.setAttribute('aria-invalid', 'false');
            
            // Clear feedback
            const parent = field.parentElement;
            const validFeedback = parent.querySelector('.valid-feedback');
            const invalidFeedback = parent.querySelector('.invalid-feedback');
            
            if (validFeedback) validFeedback.textContent = '';
            if (invalidFeedback) invalidFeedback.textContent = '';
        });
    }
    
    /**
     * Set custom validator
     */
    setCustomValidator(form, field, validator) {
        const formData = this.forms.get(form);
        if (!formData) return;
        
        const fieldData = formData.fields.get(field);
        if (fieldData) {
            fieldData.rules.custom = validator;
        }
    }
    
    /**
     * Set submit handler
     */
    setSubmitHandler(form, handler) {
        const formData = this.forms.get(form);
        if (formData) {
            formData.submitHandler = handler;
        }
    }
    
    /**
     * Announce error for screen readers
     */
    announceError(message) {
        const announcement = document.createElement('div');
        announcement.className = 'sr-only';
        announcement.setAttribute('role', 'alert');
        announcement.setAttribute('aria-live', 'assertive');
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            announcement.remove();
        }, 1000);
    }
    
    /**
     * Inject validation styles
     */
    injectStyles() {
        if (document.getElementById('form-validation-styles')) return;
        
        const styles = `
            .field-loading {
                position: relative;
                pointer-events: none;
                opacity: 0.7;
            }
            
            .field-loading::after {
                content: '';
                position: absolute;
                right: 0.75rem;
                top: 50%;
                transform: translateY(-50%);
                width: 1rem;
                height: 1rem;
                border: 2px solid rgba(107, 114, 128, 0.3);
                border-top-color: #6b7280;
                border-radius: 50%;
                animation: field-spinner 0.6s linear infinite;
            }
            
            @keyframes field-spinner {
                to { transform: translateY(-50%) rotate(360deg); }
            }
            
            .sr-only {
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = 'form-validation-styles';
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
}

// Debounce helper
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize form validator
window.formValidator = new FormValidator();

// Convenience functions
window.validateForm = function(form) {
    return window.formValidator.initializeForm(form);
};

window.clearFormValidation = function(form) {
    return window.formValidator.clearValidation(form);
};
