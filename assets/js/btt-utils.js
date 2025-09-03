/**
 * BTT Utilities
 * Common utility functions for BeyondTrailTales
 */

(function(window) {
    'use strict';

    // Initialize BTT namespace
    window.BTT = window.BTT || {};

    /**
     * BTT Utilities object
     */
    const BTTUtils = {
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * Show toast notification
         */
        showToast: function(message, type = 'info', duration = 3000) {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'polite');
            
            // Add icon based on type
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            toast.innerHTML = `
                <span class="toast-icon">${icons[type] || icons.info}</span>
                <span class="toast-message">${this.escapeHtml(message)}</span>
            `;
            
            // Add styles if not already present
            if (!document.getElementById('btt-toast-styles')) {
                const style = document.createElement('style');
                style.id = 'btt-toast-styles';
                style.innerHTML = `
                    .toast-container {
                        position: fixed;
                        top: 80px;
                        right: 20px;
                        z-index: 9999;
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        pointer-events: none;
                    }
                    .toast {
                        pointer-events: auto;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 12px 20px;
                        border-radius: 8px;
                        background: white;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        animation: slideIn 0.3s ease;
                        min-width: 250px;
                        max-width: 400px;
                    }
                    .toast-success {
                        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
                        color: #065f46;
                        border-left: 4px solid #10b981;
                    }
                    .toast-error {
                        background: linear-gradient(135deg, #fee2e2, #fecaca);
                        color: #991b1b;
                        border-left: 4px solid #ef4444;
                    }
                    .toast-warning {
                        background: linear-gradient(135deg, #fef3c7, #fde68a);
                        color: #92400e;
                        border-left: 4px solid #f59e0b;
                    }
                    .toast-info {
                        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
                        color: #1e40af;
                        border-left: 4px solid #3b82f6;
                    }
                    .toast-icon {
                        font-size: 1.2rem;
                    }
                    @keyframes slideIn {
                        from {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                    @keyframes fadeOut {
                        from {
                            opacity: 1;
                        }
                        to {
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Find or create container
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            
            // Add toast to container
            container.appendChild(toast);
            
            // Auto remove after duration
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, duration);
            
            return toast;
        },

        /**
         * Format date
         */
        formatDate: function(date, format = 'short') {
            if (!date) return '';
            const d = new Date(date);
            if (format === 'short') {
                return d.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric',
                    year: 'numeric'
                });
            } else {
                return d.toLocaleDateString('en-US', { 
                    weekday: 'long',
                    month: 'long', 
                    day: 'numeric',
                    year: 'numeric'
                });
            }
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        },

        /**
         * Throttle function
         */
        throttle: function(func, limit) {
            let inThrottle;
            return function(...args) {
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Parse query parameters
         */
        getQueryParams: function() {
            const params = {};
            const searchParams = new URLSearchParams(window.location.search);
            for (const [key, value] of searchParams) {
                params[key] = value;
            }
            return params;
        },

        /**
         * Set query parameter
         */
        setQueryParam: function(key, value) {
            const url = new URL(window.location);
            if (value === null || value === undefined) {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, value);
            }
            window.history.pushState({}, '', url);
        },

        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        /**
         * Format weight
         */
        formatWeight: function(grams, unit = 'auto') {
            if (!grams || grams === 0) return '0g';
            
            if (unit === 'auto') {
                if (grams >= 1000) {
                    return (grams / 1000).toFixed(1) + 'kg';
                } else {
                    return grams + 'g';
                }
            } else if (unit === 'kg') {
                return (grams / 1000).toFixed(2) + 'kg';
            } else if (unit === 'lbs') {
                return (grams * 0.00220462).toFixed(2) + 'lbs';
            } else if (unit === 'oz') {
                return (grams * 0.035274).toFixed(1) + 'oz';
            } else {
                return grams + 'g';
            }
        },

        /**
         * Copy to clipboard
         */
        copyToClipboard: async function(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.showToast('Copied to clipboard!', 'success');
                return true;
            } catch (err) {
                this.showToast('Failed to copy', 'error');
                return false;
            }
        },

        /**
         * Load script dynamically
         */
        loadScript: function(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        /**
         * Load CSS dynamically
         */
        loadCSS: function(href) {
            return new Promise((resolve, reject) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.onload = resolve;
                link.onerror = reject;
                document.head.appendChild(link);
            });
        },

        /**
         * Check if element is in viewport
         */
        isInViewport: function(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        /**
         * Smooth scroll to element
         */
        scrollToElement: function(element, offset = 80) {
            const elementPosition = element.getBoundingClientRect().top + window.scrollY;
            const offsetPosition = elementPosition - offset;
            
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        },

        /**
         * Generate unique ID
         */
        generateId: function(prefix = 'btt') {
            return prefix + '-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        },

        /**
         * Deep clone object
         */
        deepClone: function(obj) {
            if (obj === null || typeof obj !== 'object') return obj;
            if (obj instanceof Date) return new Date(obj);
            if (obj instanceof Array) return obj.map(item => this.deepClone(item));
            if (obj instanceof Object) {
                const clonedObj = {};
                for (const key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        clonedObj[key] = this.deepClone(obj[key]);
                    }
                }
                return clonedObj;
            }
        },

        /**
         * Merge objects deeply
         */
        deepMerge: function(target, ...sources) {
            if (!sources.length) return target;
            const source = sources.shift();
            
            if (this.isObject(target) && this.isObject(source)) {
                for (const key in source) {
                    if (this.isObject(source[key])) {
                        if (!target[key]) Object.assign(target, { [key]: {} });
                        this.deepMerge(target[key], source[key]);
                    } else {
                        Object.assign(target, { [key]: source[key] });
                    }
                }
            }
            
            return this.deepMerge(target, ...sources);
        },

        /**
         * Check if value is object
         */
        isObject: function(item) {
            return item && typeof item === 'object' && !Array.isArray(item);
        },

        /**
         * Local storage wrapper with JSON support
         */
        storage: {
            get: function(key) {
                try {
                    const item = localStorage.getItem(key);
                    return item ? JSON.parse(item) : null;
                } catch (e) {
                    console.error('Error reading from localStorage:', e);
                    return null;
                }
            },
            
            set: function(key, value) {
                try {
                    localStorage.setItem(key, JSON.stringify(value));
                    return true;
                } catch (e) {
                    console.error('Error writing to localStorage:', e);
                    return false;
                }
            },
            
            remove: function(key) {
                try {
                    localStorage.removeItem(key);
                    return true;
                } catch (e) {
                    console.error('Error removing from localStorage:', e);
                    return false;
                }
            },
            
            clear: function() {
                try {
                    localStorage.clear();
                    return true;
                } catch (e) {
                    console.error('Error clearing localStorage:', e);
                    return false;
                }
            }
        }
    };

    // Export to global scope
    window.BTTUtils = BTTUtils;
    window.BTT.utils = BTTUtils;

})(window);
