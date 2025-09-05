/**
 * BeyondTrailTales Frontend JavaScript
 * API utilities and DOM helpers
 */

(function() {
    'use strict';

    // API Helper
    window.BTTApi = {
        get: async function(route, params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const url = `${window.BTT.apiUrl}?route=${route}${queryString ? '&' + queryString : ''}`;
            
            try {
                const response = await fetch(url, {
                    credentials: 'include' // Changed from 'same-origin' to ensure cookies are sent
                });
                const data = await response.json();
                
                // Handle both formats: {success: true, data: ...} and direct arrays/objects
                if (!response.ok) {
                    const errorMsg = (data && data.message) || data.error || 'Request failed';
                    throw new Error(errorMsg);
                }
                
                // If response has success field, use the wrapped format
                if (data && typeof data.success !== 'undefined') {
                    if (!data.success) {
                        throw new Error(data.message || data.error || 'Request failed');
                    }
                    return data.data;
                }
                
                // Otherwise, return data directly (for ajax-handler.php format)
                return data;
            } catch (error) {
                console.error('API Error:', error);
                BTTUtils.showToast(error.message, 'error');
                throw error;
            }
        },

        post: async function(route, body = {}, files = {}) {
            console.log('BTTApi.post called:', {route, body, files});
            const formData = new FormData();
            
            // Add regular data
            for (const key in body) {
                if (body[key] !== null && body[key] !== undefined) {
                    formData.append(key, body[key]);
                }
            }
            
            // Add files
            for (const key in files) {
                if (files[key]) {
                    formData.append(key, files[key]);
                }
            }
            
            const url = `${window.BTT.apiUrl}?route=${route}`;
            console.log('BTTApi.post URL:', url);
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include' // Changed to ensure cookies are sent
                });
                const data = await response.json();
                console.log('BTTApi.post response:', {ok: response.ok, status: response.status, data});
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Request failed');
                }
                
                return data.data;
            } catch (error) {
                console.error('BTTApi.post ERROR:', error);
                BTTUtils.showToast(error.message, 'error');
                throw error;
            }
        },

        put: async function(route, id, body = {}, files = {}) {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            
            // Add regular data
            for (const key in body) {
                if (body[key] !== null && body[key] !== undefined) {
                    formData.append(key, body[key]);
                }
            }
            
            // Add files
            for (const key in files) {
                if (files[key]) {
                    formData.append(key, files[key]);
                }
            }
            
            const url = `${window.BTT.apiUrl}?route=${route}&id=${id}`;
            console.log('BTTApi.put called with URL:', url);
            console.log('BTTApi.put files:', files);
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include' // Changed to ensure cookies are sent
                });
                const data = await response.json();
                console.log('BTTApi.put response:', {ok: response.ok, status: response.status, data});
                console.log('BTTApi.put response data details:', JSON.stringify(data, null, 2));
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Request failed');
                }
                
                return data.data;
            } catch (error) {
                console.error('API Error:', error);
                BTTUtils.showToast(error.message, 'error');
                throw error;
            }
        },

        delete: async function(route, id, params = {}) {
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            
            const queryString = new URLSearchParams(params).toString();
            const url = `${window.BTT.apiUrl}?route=${route}&id=${id}${queryString ? '&' + queryString : ''}`;
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include' // Changed to ensure cookies are sent
                });
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Request failed');
                }
                
                return data.data;
            } catch (error) {
                console.error('API Error:', error);
                BTTUtils.showToast(error.message, 'error');
                throw error;
            }
        }
    };

    // Utility Functions
    window.BTTUtils = {
        showToast: function(message, type = 'info') {
            const container = document.querySelector('.toast-container') || 
                             document.querySelector('#toast-container') ||
                             this.createToastContainer();
            
            const toast = document.createElement('div');
            // Modern toast classes with fallbacks
            const toastClasses = ['modern-toast', 'toast', `toast-${type}`, `alert-${type}`];
            toast.className = toastClasses.join(' ');
            toast.textContent = message;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'polite');
            
            container.appendChild(toast);
            
            // Modern animation with fallback
            requestAnimationFrame(() => {
                toast.classList.add('toast-show', 'modern-toast-show');
            });
            
            setTimeout(() => {
                toast.classList.add('toast-hide', 'modern-toast-hide');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        },

        createToastContainer: function() {
            const container = document.createElement('div');
            container.className = 'toast-container modern-toast-container';
            container.id = 'toast-container';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-label', 'Notifications');
            document.body.appendChild(container);
            return container;
        },

        formatDate: function(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        showModal: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Modern modal classes with fallbacks
                modal.classList.add('active', 'modern-modal-active', 'modal-show');
                modal.setAttribute('aria-hidden', 'false');
                modal.style.display = 'flex';
                
                // Modern focus management with trap
                this.setupModalFocusTrap(modal);
                
                // Focus first focusable element
                const firstInput = modal.querySelector('input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
                
                // Prevent body scroll
                document.body.style.overflow = 'hidden';
            }
        },

        hideModal: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Modern modal classes with fallbacks
                modal.classList.remove('active', 'modern-modal-active', 'modal-show');
                modal.classList.add('modal-hide');
                modal.setAttribute('aria-hidden', 'true');
                
                // Restore body scroll
                document.body.style.overflow = '';
                
                // Clean up after animation
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.classList.remove('modal-hide');
                    this.removeModalFocusTrap(modal);
                }, 300);
            }
        },

        setupModalFocusTrap: function(modal) {
            if (modal.hasAttribute('data-focus-trap')) return;
            modal.setAttribute('data-focus-trap', 'true');
            
            const focusableElements = modal.querySelectorAll(
                'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            
            if (focusableElements.length === 0) return;
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            const trapFocus = (e) => {
                if (e.key !== 'Tab') return;
                
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            };
            
            modal.addEventListener('keydown', trapFocus);
            modal._focusTrap = trapFocus;
        },

        removeModalFocusTrap: function(modal) {
            if (modal._focusTrap) {
                modal.removeEventListener('keydown', modal._focusTrap);
                delete modal._focusTrap;
                modal.removeAttribute('data-focus-trap');
            }
        },

        clearForm: function(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.reset();
                // Clear any error messages
                form.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            }
        },

        validatePhotoAltText: function(fileInput, altTextInput) {
            if (fileInput.files && fileInput.files[0]) {
                if (!altTextInput.value.trim()) {
                    altTextInput.setCustomValidity('Alt text is required when uploading a photo (ADA compliance)');
                    return false;
                } else {
                    altTextInput.setCustomValidity('');
                    return true;
                }
            }
            altTextInput.setCustomValidity('');
            return true;
        }
    };

    // Modal close handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            BTTUtils.hideModal(e.target.id);
        }
        if (e.target.classList.contains('modal-close')) {
            const modal = e.target.closest('.modal');
            if (modal) BTTUtils.hideModal(modal.id);
        }
    });

    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                BTTUtils.hideModal(modal.id);
            });
        }
    });

})();
