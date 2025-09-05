/**
 * BeyondTrailTales Component System
 * Core JavaScript for interactive components
 * Version: 2.0.0
 * ADA Compliant | Performance Optimized
 */

(function(window, document) {
    'use strict';

    // BTT namespace
    window.BTT = window.BTT || {};

    /**
     * Modal Manager
     * Handles modal dialogs with accessibility features
     */
    BTT.Modal = {
        activeModal: null,
        previousFocus: null,

        init() {
            // Auto-initialize modals
            document.querySelectorAll('[data-modal-trigger]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const modalId = trigger.getAttribute('data-modal-trigger');
                    this.open(modalId);
                });
            });

            // Close on backdrop click
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.addEventListener('click', () => this.close());
            });

            // Close on close button click
            document.querySelectorAll('.modal-close').forEach(btn => {
                btn.addEventListener('click', () => this.close());
            });

            // ESC key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.activeModal) {
                    this.close();
                }
            });
        },

        open(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;

            // Store previous focus
            this.previousFocus = document.activeElement;

            // Open modal
            modal.classList.add('active');
            this.activeModal = modal;

            // Focus management
            const focusable = modal.querySelectorAll('button, input, select, textarea, a[href], [tabindex]:not([tabindex="-1"])');
            if (focusable.length) {
                focusable[0].focus();
            }

            // Trap focus
            this.trapFocus(modal);

            // Prevent body scroll
            document.body.style.overflow = 'hidden';

            // Dispatch custom event
            modal.dispatchEvent(new CustomEvent('modal:open'));
        },

        close() {
            if (!this.activeModal) return;

            // Close modal
            this.activeModal.classList.remove('active');

            // Restore body scroll
            document.body.style.overflow = '';

            // Restore focus
            if (this.previousFocus) {
                this.previousFocus.focus();
            }

            // Dispatch custom event
            this.activeModal.dispatchEvent(new CustomEvent('modal:close'));

            // Reset
            this.activeModal = null;
            this.previousFocus = null;
        },

        trapFocus(element) {
            const focusable = element.querySelectorAll('button, input, select, textarea, a[href], [tabindex]:not([tabindex="-1"])');
            const firstFocusable = focusable[0];
            const lastFocusable = focusable[focusable.length - 1];

            element.addEventListener('keydown', (e) => {
                if (e.key !== 'Tab') return;

                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            });
        }
    };

    /**
     * Toast Notification System
     * Shows temporary notifications with auto-dismiss
     */
    BTT.Toast = {
        container: null,
        queue: [],
        
        init() {
            // Create container if it doesn't exist
            if (!document.getElementById('toastContainer')) {
                this.container = document.createElement('div');
                this.container.id = 'toastContainer';
                this.container.className = 'toast-container toast-container-top-right';
                document.body.appendChild(this.container);
            } else {
                this.container = document.getElementById('toastContainer');
            }
        },

        show(message, type = 'info', duration = 5000) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} animate-slide-in`;

            const icons = {
                success: 'check-circle',
                warning: 'exclamation-triangle',
                error: 'times-circle',
                info: 'info-circle'
            };

            const titles = {
                success: 'Success',
                warning: 'Warning',
                error: 'Error',
                info: 'Info'
            };

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${icons[type]}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${titles[type]}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="Close notification">
                    <i class="fas fa-times"></i>
                </button>
                <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
            `;

            // Add close handler
            toast.querySelector('.toast-close').addEventListener('click', () => {
                this.remove(toast);
            });

            // Add to container
            this.container.appendChild(toast);

            // Auto remove
            setTimeout(() => {
                this.remove(toast);
            }, duration);

            // Announce to screen readers
            this.announce(`${titles[type]}: ${message}`);

            return toast;
        },

        remove(toast) {
            toast.classList.add('toast-removing');
            setTimeout(() => {
                toast.remove();
            }, 300);
        },

        announce(message) {
            const announcement = document.createElement('div');
            announcement.className = 'sr-only';
            announcement.setAttribute('role', 'alert');
            announcement.setAttribute('aria-live', 'polite');
            announcement.textContent = message;
            document.body.appendChild(announcement);
            setTimeout(() => announcement.remove(), 1000);
        }
    };

    /**
     * Form Validation System
     * Client-side form validation with accessibility
     */
    BTT.FormValidator = {
        forms: new Map(),

        init() {
            // Auto-initialize forms with validation
            document.querySelectorAll('form[data-validate]').forEach(form => {
                this.attach(form);
            });
        },

        attach(form) {
            const validators = {
                required: (field) => {
                    const value = field.value.trim();
                    if (field.type === 'checkbox') {
                        return field.checked || 'This field is required';
                    }
                    return value !== '' || 'This field is required';
                },
                email: (field) => {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return emailRegex.test(field.value) || 'Please enter a valid email';
                },
                minLength: (field) => {
                    const min = parseInt(field.getAttribute('data-min-length'));
                    return field.value.length >= min || `Minimum ${min} characters required`;
                },
                maxLength: (field) => {
                    const max = parseInt(field.getAttribute('data-max-length'));
                    return field.value.length <= max || `Maximum ${max} characters allowed`;
                },
                pattern: (field) => {
                    const pattern = new RegExp(field.getAttribute('data-pattern'));
                    return pattern.test(field.value) || field.getAttribute('data-pattern-message') || 'Invalid format';
                },
                match: (field) => {
                    const matchFieldId = field.getAttribute('data-match');
                    const matchField = document.getElementById(matchFieldId);
                    return field.value === matchField.value || 'Fields do not match';
                }
            };

            // Store validators for this form
            this.forms.set(form, validators);

            // Add submit handler
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.focusFirstError(form);
                }
            });

            // Add field blur handlers for instant feedback
            form.querySelectorAll('[data-validate-rules]').forEach(field => {
                field.addEventListener('blur', () => {
                    this.validateField(field, validators);
                });

                field.addEventListener('input', () => {
                    if (field.classList.contains('is-invalid')) {
                        this.validateField(field, validators);
                    }
                });
            });
        },

        validateField(field, validators) {
            const rules = field.getAttribute('data-validate-rules').split(' ');
            let isValid = true;
            let errorMessage = '';

            for (const rule of rules) {
                if (validators[rule]) {
                    const result = validators[rule](field);
                    if (result !== true) {
                        isValid = false;
                        errorMessage = result;
                        break;
                    }
                }
            }

            this.updateFieldState(field, isValid, errorMessage);
            return isValid;
        },

        validateForm(form) {
            const validators = this.forms.get(form);
            let isValid = true;

            form.querySelectorAll('[data-validate-rules]').forEach(field => {
                if (!this.validateField(field, validators)) {
                    isValid = false;
                }
            });

            return isValid;
        },

        updateFieldState(field, isValid, errorMessage) {
            const formGroup = field.closest('.form-group');
            const feedback = formGroup ? formGroup.querySelector('.form-feedback') : null;

            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
                field.setAttribute('aria-invalid', 'false');
                
                if (feedback) {
                    feedback.remove();
                }
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                field.setAttribute('aria-invalid', 'true');
                
                if (!feedback && formGroup) {
                    const errorElement = document.createElement('div');
                    errorElement.className = 'form-feedback form-feedback-error';
                    errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errorMessage}`;
                    errorElement.setAttribute('role', 'alert');
                    errorElement.id = `${field.id}-error`;
                    field.setAttribute('aria-describedby', errorElement.id);
                    formGroup.appendChild(errorElement);
                } else if (feedback) {
                    feedback.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errorMessage}`;
                }
            }
        },

        focusFirstError(form) {
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    };

    /**
     * Navigation Controller
     * Handles navigation interactions and mobile menu
     */
    BTT.Navigation = {
        mobileMenuOpen: false,
        
        init() {
            // Mobile menu toggle
            const toggle = document.querySelector('.nav-toggle');
            const mobileMenu = document.querySelector('.nav-mobile');
            
            if (toggle) {
                toggle.addEventListener('click', () => {
                    this.toggleMobileMenu();
                });
            }

            // Dropdown menus
            document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                const toggle = dropdown.querySelector('.nav-dropdown-toggle');
                
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleDropdown(dropdown);
                });
            });

            // Close dropdowns on outside click
            document.addEventListener('click', () => {
                this.closeAllDropdowns();
            });

            // Keyboard navigation
            this.setupKeyboardNavigation();
        },

        toggleMobileMenu() {
            const toggle = document.querySelector('.nav-toggle');
            const mobileMenu = document.querySelector('.nav-mobile');
            
            this.mobileMenuOpen = !this.mobileMenuOpen;
            
            if (toggle) {
                toggle.classList.toggle('active');
            }
            
            if (mobileMenu) {
                mobileMenu.classList.toggle('open');
            }
            
            // Update ARIA
            toggle?.setAttribute('aria-expanded', this.mobileMenuOpen);
            
            // Trap focus when open
            if (this.mobileMenuOpen && mobileMenu) {
                BTT.Modal.trapFocus(mobileMenu);
            }
        },

        toggleDropdown(dropdown) {
            const isOpen = dropdown.classList.contains('open');
            
            // Close all other dropdowns
            this.closeAllDropdowns();
            
            // Toggle this dropdown
            if (!isOpen) {
                dropdown.classList.add('open');
                dropdown.querySelector('.nav-dropdown-toggle').setAttribute('aria-expanded', 'true');
            }
        },

        closeAllDropdowns() {
            document.querySelectorAll('.nav-dropdown.open').forEach(dropdown => {
                dropdown.classList.remove('open');
                dropdown.querySelector('.nav-dropdown-toggle').setAttribute('aria-expanded', 'false');
            });
        },

        setupKeyboardNavigation() {
            // Arrow key navigation for dropdowns
            document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                const items = dropdown.querySelectorAll('.nav-dropdown-item');
                
                items.forEach((item, index) => {
                    item.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            items[Math.min(index + 1, items.length - 1)].focus();
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            items[Math.max(index - 1, 0)].focus();
                        } else if (e.key === 'Escape') {
                            this.closeAllDropdowns();
                            dropdown.querySelector('.nav-dropdown-toggle').focus();
                        }
                    });
                });
            });
        }
    };

    /**
     * Lazy Loading
     * Lazy load images and other media
     */
    BTT.LazyLoad = {
        init() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            this.loadImage(img);
                            observer.unobserve(img);
                        }
                    });
                });

                // Observe all lazy images
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            } else {
                // Fallback for older browsers
                this.loadAllImages();
            }
        },

        loadImage(img) {
            const src = img.getAttribute('data-src');
            if (!src) return;

            img.src = src;
            img.removeAttribute('data-src');
            
            // Add loading animation
            img.classList.add('animate-fade-in');
        },

        loadAllImages() {
            document.querySelectorAll('img[data-src]').forEach(img => {
                this.loadImage(img);
            });
        }
    };

    /**
     * Smooth Scroll
     * Smooth scrolling for anchor links
     */
    BTT.SmoothScroll = {
        init() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    const href = anchor.getAttribute('href');
                    if (href === '#') return;

                    const target = document.querySelector(href);
                    if (!target) return;

                    e.preventDefault();
                    
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    // Update URL
                    history.pushState(null, '', href);

                    // Focus target for accessibility
                    target.setAttribute('tabindex', '-1');
                    target.focus();
                });
            });
        }
    };

    /**
     * Drag and Drop
     * Enhanced drag and drop with visual feedback
     */
    BTT.DragDrop = {
        init() {
            const draggables = document.querySelectorAll('[data-draggable="true"]');
            const dropzones = document.querySelectorAll('[data-dropzone="true"]');

            draggables.forEach(draggable => {
                draggable.addEventListener('dragstart', this.handleDragStart.bind(this));
                draggable.addEventListener('dragend', this.handleDragEnd.bind(this));
            });

            dropzones.forEach(zone => {
                zone.addEventListener('dragover', this.handleDragOver.bind(this));
                zone.addEventListener('drop', this.handleDrop.bind(this));
                zone.addEventListener('dragenter', this.handleDragEnter.bind(this));
                zone.addEventListener('dragleave', this.handleDragLeave.bind(this));
            });
        },

        handleDragStart(e) {
            e.target.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.target.innerHTML);
            e.dataTransfer.setData('elementId', e.target.id);
        },

        handleDragEnd(e) {
            e.target.classList.remove('dragging');
            
            // Remove all drag-over classes
            document.querySelectorAll('.drag-over').forEach(el => {
                el.classList.remove('drag-over');
            });
        },

        handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            return false;
        },

        handleDragEnter(e) {
            e.target.classList.add('drag-over');
        },

        handleDragLeave(e) {
            e.target.classList.remove('drag-over');
        },

        handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            e.preventDefault();

            const elementId = e.dataTransfer.getData('elementId');
            const draggedElement = document.getElementById(elementId);
            
            if (draggedElement && e.target.getAttribute('data-dropzone') === 'true') {
                // Move element to new position
                e.target.appendChild(draggedElement);
                
                // Dispatch custom event
                e.target.dispatchEvent(new CustomEvent('drop:complete', {
                    detail: { element: draggedElement }
                }));
                
                // Show success toast
                BTT.Toast.show('Item moved successfully', 'success');
            }

            e.target.classList.remove('drag-over');
            return false;
        }
    };

    /**
     * Utilities
     * Helper functions
     */
    BTT.Utils = {
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        throttle(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        formatDate(date, format = 'short') {
            const options = {
                short: { month: 'short', day: 'numeric', year: 'numeric' },
                long: { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' },
                time: { hour: '2-digit', minute: '2-digit' }
            };
            return new Date(date).toLocaleDateString('en-US', options[format]);
        },

        formatNumber(num, decimals = 0) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        }
    };

    /**
     * Initialize all components
     */
    BTT.init = function() {
        // Initialize all modules
        BTT.Modal.init();
        BTT.Toast.init();
        BTT.FormValidator.init();
        BTT.Navigation.init();
        BTT.LazyLoad.init();
        BTT.SmoothScroll.init();
        BTT.DragDrop.init();

        // Dispatch ready event
        document.dispatchEvent(new CustomEvent('btt:ready'));
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', BTT.init);
    } else {
        BTT.init();
    }

})(window, document);
