/**
 * BTT Compatibility Layer - Modern Design System Bridge
 * Ensures seamless transition between legacy and modern CSS classes
 * Provides unified utilities for consistent behavior across all components
 * 
 * @package BeyondTrailTales
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Design System Compatibility Bridge
     * Maps legacy classes to modern equivalents and provides fallbacks
     */
    window.BTTCompat = {
        
        // Class mapping for automated migration
        CLASS_MAPS: {
            // Button mappings
            'btn': ['modern-btn', 'btn'],
            'btn-primary': ['modern-btn-primary', 'btn-primary'],
            'btn-secondary': ['modern-btn-secondary', 'btn-secondary'], 
            'btn-icon': ['modern-btn-icon', 'btn-icon'],
            'btn-ghost': ['modern-btn-ghost', 'btn-ghost'],
            
            // Card mappings
            'card': ['modern-card', 'card'],
            'card-header': ['modern-card-header', 'card-header'],
            'card-body': ['modern-card-body', 'card-body'],
            'card-footer': ['modern-card-footer', 'card-footer'],
            'trip-card': ['modern-card', 'trip-card'],
            'gear-card': ['modern-card', 'gear-card'],
            
            // List mappings
            'list-item': ['modern-list-item', 'list-item'],
            'list-item-content': ['modern-list-item-content', 'list-item-content'],
            
            // Badge mappings
            'badge': ['modern-badge', 'badge'],
            'badge-success': ['modern-badge-success', 'badge-success'],
            'badge-warning': ['modern-badge-warning', 'badge-warning'],
            'badge-error': ['modern-badge-error', 'badge-error'],
            
            // Modal mappings
            'modal': ['modern-modal', 'modal'],
            'modal-dialog': ['modern-modal-dialog', 'modal-dialog'],
            'modal-content': ['modern-modal-content', 'modal-content'],
            'modal-header': ['modern-modal-header', 'modal-header'],
            'modal-body': ['modern-modal-body', 'modal-body'],
            'modal-footer': ['modern-modal-footer', 'modal-footer'],
            
            // Form mappings
            'form-group': ['modern-form-group', 'form-group'],
            'form-control': ['modern-form-control', 'form-control'],
            'form-label': ['modern-form-label', 'form-label'],
            'form-error': ['modern-form-error', 'form-error'],
            
            // Navigation mappings
            'nav': ['modern-nav', 'nav'],
            'nav-link': ['modern-nav-link', 'nav-link'],
            'nav-item': ['modern-nav-item', 'nav-item'],
            'dropdown-menu': ['modern-dropdown-menu', 'dropdown-menu'],
            
            // Forest theme legacy mappings
            'forest-section': ['modern-card', 'pack-section'],
            'forest-section-header': ['modern-card-header', 'section-header'],
            'forest-item': ['modern-list-item', 'pack-item'],
            'forest-gear-item': ['modern-card', 'gear-item'],
            'forest-btn': ['modern-btn', 'btn'],
            'forest-placeholder': ['modern-placeholder', 'dropzone-placeholder']
        },

        /**
         * Apply modern classes to element with fallbacks
         */
        applyModernClasses: function(element, legacyClass) {
            if (!element || !legacyClass) return element;
            
            const modernClasses = this.CLASS_MAPS[legacyClass];
            if (modernClasses) {
                // Add all mapped classes for maximum compatibility
                element.classList.add(...modernClasses);
            } else {
                // Fallback: add both modern prefix and original
                element.classList.add(`modern-${legacyClass}`, legacyClass);
            }
            
            return element;
        },

        /**
         * Enhanced element creation with modern classes
         */
        createElement: function(tagName, className, attributes = {}) {
            const element = document.createElement(tagName);
            
            if (className) {
                const classes = className.split(' ').map(cls => cls.trim()).filter(cls => cls);
                classes.forEach(cls => {
                    this.applyModernClasses(element, cls);
                });
            }
            
            // Apply attributes
            Object.keys(attributes).forEach(key => {
                if (key === 'innerHTML') {
                    element.innerHTML = attributes[key];
                } else if (key === 'textContent') {
                    element.textContent = attributes[key];
                } else {
                    element.setAttribute(key, attributes[key]);
                }
            });
            
            return element;
        },

        /**
         * Modern-compatible element selection
         */
        querySelector: function(selector) {
            // Try modern classes first, then fallback to legacy
            const modernSelector = this.translateSelector(selector);
            
            let element = document.querySelector(modernSelector);
            if (!element && modernSelector !== selector) {
                element = document.querySelector(selector);
            }
            
            return element;
        },

        querySelectorAll: function(selector) {
            const modernSelector = this.translateSelector(selector);
            
            let elements = document.querySelectorAll(modernSelector);
            if (elements.length === 0 && modernSelector !== selector) {
                elements = document.querySelectorAll(selector);
            }
            
            return elements;
        },

        /**
         * Translate CSS selector to modern equivalent
         */
        translateSelector: function(selector) {
            let modernSelector = selector;
            
            // Replace class selectors with modern equivalents
            Object.keys(this.CLASS_MAPS).forEach(legacyClass => {
                const modernClasses = this.CLASS_MAPS[legacyClass];
                const legacyPattern = new RegExp(`\\.${legacyClass}(?![\\w-])`, 'g');
                
                if (modernSelector.match(legacyPattern)) {
                    // Use the first (most modern) class for selection
                    modernSelector = modernSelector.replace(legacyPattern, `.${modernClasses[0]}`);
                }
            });
            
            return modernSelector;
        },

        /**
         * Modern button creation with enhanced interactions
         */
        createButton: function(text, type = 'primary', options = {}) {
            const button = this.createElement('button', `btn btn-${type}`, {
                type: options.type || 'button',
                'aria-label': options.ariaLabel || text
            });
            
            if (options.icon) {
                const icon = document.createElement('span');
                icon.className = 'btn-icon';
                icon.setAttribute('aria-hidden', 'true');
                icon.textContent = options.icon;
                button.appendChild(icon);
                
                if (text) {
                    const textSpan = document.createElement('span');
                    textSpan.textContent = text;
                    button.appendChild(textSpan);
                }
            } else {
                button.textContent = text;
            }
            
            // Add click handler with modern interactions
            if (options.onClick) {
                button.addEventListener('click', (e) => {
                    // Modern button feedback
                    button.classList.add('btn-active');
                    setTimeout(() => button.classList.remove('btn-active'), 150);
                    
                    options.onClick(e);
                });
            }
            
            return button;
        },

        /**
         * Modern card creation with enhanced structure
         */
        createCard: function(options = {}) {
            const card = this.createElement('div', 'card');
            
            if (options.header) {
                const header = this.createElement('div', 'card-header');
                if (typeof options.header === 'string') {
                    header.innerHTML = options.header;
                } else {
                    header.appendChild(options.header);
                }
                card.appendChild(header);
            }
            
            if (options.body) {
                const body = this.createElement('div', 'card-body');
                if (typeof options.body === 'string') {
                    body.innerHTML = options.body;
                } else {
                    body.appendChild(options.body);
                }
                card.appendChild(body);
            }
            
            if (options.footer) {
                const footer = this.createElement('div', 'card-footer');
                if (typeof options.footer === 'string') {
                    footer.innerHTML = options.footer;
                } else {
                    footer.appendChild(options.footer);
                }
                card.appendChild(footer);
            }
            
            // Add hover effects for interactive cards
            if (options.interactive) {
                card.classList.add('card-interactive');
                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');
            }
            
            return card;
        },

        /**
         * Modern toast notification system
         */
        showToast: function(message, type = 'info', options = {}) {
            const container = this.getToastContainer();
            
            const toast = this.createElement('div', 'toast', {
                'role': 'alert',
                'aria-live': 'polite'
            });
            
            // Apply type-specific classes
            this.applyModernClasses(toast, `toast-${type}`);
            
            const content = document.createElement('div');
            content.className = 'toast-content';
            
            if (options.icon) {
                const icon = document.createElement('span');
                icon.className = 'toast-icon';
                icon.setAttribute('aria-hidden', 'true');
                icon.textContent = options.icon;
                content.appendChild(icon);
            }
            
            const messageElement = document.createElement('span');
            messageElement.className = 'toast-message';
            messageElement.textContent = message;
            content.appendChild(messageElement);
            
            if (options.dismissible !== false) {
                const closeButton = this.createElement('button', 'toast-close', {
                    'aria-label': 'Close notification',
                    textContent: '×'
                });
                
                closeButton.addEventListener('click', () => {
                    this.dismissToast(toast);
                });
                
                content.appendChild(closeButton);
            }
            
            toast.appendChild(content);
            container.appendChild(toast);
            
            // Animate in
            requestAnimationFrame(() => {
                toast.classList.add('toast-show');
            });
            
            // Auto-dismiss
            if (options.duration !== false) {
                const duration = options.duration || 5000;
                setTimeout(() => {
                    this.dismissToast(toast);
                }, duration);
            }
            
            return toast;
        },

        /**
         * Get or create toast container
         */
        getToastContainer: function() {
            let container = document.querySelector('.toast-container, .modern-toast-container');
            
            if (!container) {
                container = this.createElement('div', 'toast-container', {
                    'aria-live': 'polite',
                    'aria-label': 'Notifications'
                });
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
            
            return container;
        },

        /**
         * Dismiss toast with animation
         */
        dismissToast: function(toast) {
            toast.classList.add('toast-hide');
            setTimeout(() => {
                toast.remove();
            }, 300);
        },

        /**
         * Modern form validation with enhanced UX
         */
        validateForm: function(form, rules = {}) {
            if (!form) return { valid: false, errors: ['Form not found'] };
            
            const errors = [];
            const formData = new FormData(form);
            
            // Clear existing errors
            form.querySelectorAll('.form-error, .modern-form-error').forEach(error => {
                error.textContent = '';
                error.style.display = 'none';
            });
            
            form.querySelectorAll('.form-control, .modern-form-control').forEach(field => {
                field.classList.remove('is-invalid', 'form-invalid');
                field.removeAttribute('aria-invalid');
            });
            
            // Apply validation rules
            Object.keys(rules).forEach(fieldName => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                const rule = rules[fieldName];
                const value = formData.get(fieldName);
                
                if (!field) return;
                
                // Required validation
                if (rule.required && (!value || value.toString().trim() === '')) {
                    this.addFieldError(field, rule.requiredMessage || `${fieldName} is required`);
                    errors.push(rule.requiredMessage || `${fieldName} is required`);
                    return;
                }
                
                // Skip other validations if field is empty and not required
                if (!value || value.toString().trim() === '') return;
                
                // Pattern validation
                if (rule.pattern && !rule.pattern.test(value)) {
                    this.addFieldError(field, rule.patternMessage || `${fieldName} format is invalid`);
                    errors.push(rule.patternMessage || `${fieldName} format is invalid`);
                }
                
                // Custom validation
                if (rule.custom && !rule.custom(value, formData)) {
                    this.addFieldError(field, rule.customMessage || `${fieldName} is invalid`);
                    errors.push(rule.customMessage || `${fieldName} is invalid`);
                }
            });
            
            return { valid: errors.length === 0, errors };
        },

        /**
         * Add field validation error
         */
        addFieldError: function(field, message) {
            field.classList.add('is-invalid', 'form-invalid');
            field.setAttribute('aria-invalid', 'true');
            
            const errorId = `${field.name || field.id}-error`;
            let errorElement = document.getElementById(errorId);
            
            if (!errorElement) {
                errorElement = this.createElement('div', 'form-error', {
                    id: errorId,
                    role: 'alert'
                });
                field.parentNode.appendChild(errorElement);
            }
            
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            field.setAttribute('aria-describedby', errorId);
        },

        /**
         * Initialize compatibility layer on DOM elements
         */
        initializeElement: function(element) {
            if (!element) return;
            
            // Auto-upgrade legacy classes
            const legacyElements = element.querySelectorAll('[class*="forest-"], [class*="btn"], [class*="card"], [class*="modal"]');
            
            legacyElements.forEach(el => {
                const classes = Array.from(el.classList);
                classes.forEach(className => {
                    if (this.CLASS_MAPS[className]) {
                        this.applyModernClasses(el, className);
                    }
                });
            });
            
            // Initialize interactive elements
            this.initializeButtons(element);
            this.initializeCards(element);
            this.initializeModals(element);
        },

        /**
         * Initialize modern button interactions
         */
        initializeButtons: function(container) {
            const buttons = container.querySelectorAll('.btn, .modern-btn');
            
            buttons.forEach(button => {
                if (button.hasAttribute('data-btt-initialized')) return;
                button.setAttribute('data-btt-initialized', 'true');
                
                // Add ripple effect for modern buttons
                button.addEventListener('click', (e) => {
                    const ripple = document.createElement('span');
                    ripple.className = 'btn-ripple';
                    
                    const rect = button.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                    `;
                    
                    button.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
            });
        },

        /**
         * Initialize modern card interactions
         */
        initializeCards: function(container) {
            const cards = container.querySelectorAll('.card, .modern-card');
            
            cards.forEach(card => {
                if (card.hasAttribute('data-btt-initialized')) return;
                card.setAttribute('data-btt-initialized', 'true');
                
                // Add hover animations for interactive cards
                if (card.classList.contains('card-interactive')) {
                    card.addEventListener('mouseenter', () => {
                        card.style.transform = 'translateY(-2px)';
                    });
                    
                    card.addEventListener('mouseleave', () => {
                        card.style.transform = 'translateY(0)';
                    });
                }
            });
        },

        /**
         * Initialize modern modal enhancements
         */
        initializeModals: function(container) {
            const modals = container.querySelectorAll('.modal, .modern-modal');
            
            modals.forEach(modal => {
                if (modal.hasAttribute('data-btt-initialized')) return;
                modal.setAttribute('data-btt-initialized', 'true');
                
                // Enhanced modal accessibility and interactions
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                
                if (!modal.getAttribute('aria-labelledby')) {
                    const title = modal.querySelector('.modal-title, .modern-modal-title');
                    if (title && !title.id) {
                        title.id = 'modal-title-' + Date.now();
                    }
                    if (title) {
                        modal.setAttribute('aria-labelledby', title.id);
                    }
                }
            });
        },

        /**
         * Debounce utility for performance optimization
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func.apply(this, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(this, args);
            };
        },

        /**
         * Throttle utility for performance optimization
         */
        throttle: function(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    };

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.BTTCompat.initializeElement(document.body);
        });
    } else {
        window.BTTCompat.initializeElement(document.body);
    }

    // Auto-initialize on dynamic content
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    window.BTTCompat.initializeElement(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

})();