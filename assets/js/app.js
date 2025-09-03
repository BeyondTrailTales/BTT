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
                
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Request failed');
                }
                
                return data.data;
            } catch (error) {
                console.error('API Error:', error);
                BTTUtils.showToast(error.message, 'error');
                throw error;
            }
        },

        post: async function(route, body = {}, files = {}) {
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
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include' // Changed to ensure cookies are sent
                });
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Request failed');
                }
                
                return data.data;
            } catch (error) {
                console.error('API Error:', error);
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
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include' // Changed to ensure cookies are sent
                });
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Request failed');
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
                    throw new Error(data.error || 'Request failed');
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
            const container = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast alert-${type}`;
            toast.textContent = message;
            toast.setAttribute('role', 'alert');
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
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
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                
                // Focus management
                const firstInput = modal.querySelector('input, select, textarea, button');
                if (firstInput) firstInput.focus();
            }
        },

        hideModal: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
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
