/**
 * BeyondTrailTales - Duolingo-Style Notifications
 * Playful, engaging feedback system for user actions
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';

    // Duolingo Notifications System
    class DuoNotifications {
        constructor() {
            this.toastContainer = null;
            this.activePopup = null;
            this.init();
        }

        init() {
            // Create toast container
            this.createToastContainer();
            
            // Bind to common events
            this.bindEvents();
            
            console.log('🦉 Duolingo notifications system ready!');
        }

        createToastContainer() {
            if (!this.toastContainer) {
                this.toastContainer = document.createElement('div');
                this.toastContainer.className = 'duo-toast-container';
                document.body.appendChild(this.toastContainer);
            }
        }

        bindEvents() {
            // Listen for successful saves
            document.addEventListener('btt:save:success', (e) => {
                this.showSaveSuccess(e.detail);
            });

            document.addEventListener('btt:save:error', (e) => {
                this.showSaveError(e.detail);
            });

            // Listen for trip saves
            document.addEventListener('trip:saved', (e) => {
                this.showTripSaved(e.detail);
            });

            // Listen for backpack saves
            document.addEventListener('backpack:saved', (e) => {
                this.showBackpackSaved(e.detail);
            });

            // Listen for gear additions
            document.addEventListener('gear:added', (e) => {
                this.showGearAdded(e.detail);
            });
        }

        // Toast Notifications
        showToast(options = {}) {
            const toast = document.createElement('div');
            toast.className = `duo-toast ${options.type || 'success'}`;
            
            const icon = this.getIcon(options.type || 'success');
            
            toast.innerHTML = `
                <div class="duo-toast-icon">${icon}</div>
                <div class="duo-toast-content">
                    <div class="duo-toast-title">${options.title || 'Success!'}</div>
                    <div class="duo-toast-message">${options.message || 'Action completed successfully'}</div>
                </div>
                <button class="duo-toast-close" onclick="this.parentElement.remove()">×</button>
            `;

            this.toastContainer.appendChild(toast);

            // Show toast
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            // Auto remove after delay
            const duration = options.duration || 4000;
            setTimeout(() => {
                this.removeToast(toast);
            }, duration);

            return toast;
        }

        removeToast(toast) {
            if (toast && toast.parentNode) {
                toast.classList.add('hide');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }

        // Success Popup (for major actions)
        showPopup(options = {}) {
            if (this.activePopup) {
                this.closePopup();
            }

            const overlay = document.createElement('div');
            overlay.className = `duo-success-popup ${options.type || 'success'}`;
            
            const icon = this.getIcon(options.type || 'success');
            
            overlay.innerHTML = `
                <div class="duo-popup-content">
                    <div class="duo-popup-header">
                        <div class="duo-popup-icon">${icon}</div>
                        <h3 class="duo-popup-title">${options.title || 'Success!'}</h3>
                    </div>
                    <div class="duo-popup-body">
                        <p class="duo-popup-message">${options.message || 'Your action was completed successfully!'}</p>
                        <div class="duo-popup-actions">
                            <button class="btn btn-primary duo-btn" onclick="DuoNotify.closePopup()">
                                ${options.buttonText || 'Continue'}
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            this.activePopup = overlay;

            // Show popup
            setTimeout(() => {
                overlay.classList.add('show');
            }, 100);

            // Auto close after delay
            if (options.autoClose !== false) {
                setTimeout(() => {
                    this.closePopup();
                }, options.duration || 3000);
            }

            return overlay;
        }

        closePopup() {
            if (this.activePopup) {
                this.activePopup.classList.remove('show');
                setTimeout(() => {
                    if (this.activePopup && this.activePopup.parentNode) {
                        this.activePopup.parentNode.removeChild(this.activePopup);
                    }
                    this.activePopup = null;
                }, 300);
            }
        }

        // Specific notification methods
        showSaveSuccess(data = {}) {
            this.showToast({
                type: 'success',
                title: 'Saved Successfully! 🎉',
                message: data.message || 'Your changes have been saved.',
                duration: 3000
            });
        }

        showSaveError(data = {}) {
            this.showToast({
                type: 'error',
                title: 'Save Failed 😞',
                message: data.message || 'Failed to save changes. Please try again.',
                duration: 5000
            });
        }

        showTripSaved(data = {}) {
            this.showPopup({
                type: 'success',
                title: 'Trip Saved! 🗺️',
                message: `Your trip "${data.name || 'Adventure'}" has been saved successfully!`,
                buttonText: 'View Trip',
                duration: 4000
            });
        }

        showBackpackSaved(data = {}) {
            this.showPopup({
                type: 'success',
                title: 'Backpack Saved! 🎒',
                message: `Your pack "${data.name || 'Pack'}" has been saved successfully!`,
                buttonText: 'View Packs',
                duration: 4000
            });
        }

        showGearAdded(data = {}) {
            this.showToast({
                type: 'success',
                title: 'Gear Added! 📦',
                message: `${data.name || 'Item'} has been added to your gear library.`,
                duration: 3000
            });
        }

        showLoading(message = 'Saving...') {
            return this.showToast({
                type: 'info',
                title: 'Working... ⚡',
                message: message,
                duration: 10000 // Long duration, should be manually closed
            });
        }

        // Confirmation dialog
        showConfirmation(options = {}) {
            return new Promise((resolve) => {
                if (this.activePopup) {
                    this.closePopup();
                }

                const overlay = document.createElement('div');
                overlay.className = 'duo-success-popup warning';
                
                overlay.innerHTML = `
                    <div class="duo-popup-content">
                        <div class="duo-popup-header">
                            <div class="duo-popup-icon">⚠️</div>
                            <h3 class="duo-popup-title">${options.title || 'Confirm Action'}</h3>
                        </div>
                        <div class="duo-popup-body">
                            <p class="duo-popup-message">${options.message || 'Are you sure you want to proceed?'}</p>
                            <div class="duo-popup-actions">
                                <button class="btn btn-secondary duo-btn" onclick="DuoNotify.resolveConfirmation(false)">
                                    ${options.cancelText || 'Cancel'}
                                </button>
                                <button class="btn btn-primary duo-btn" onclick="DuoNotify.resolveConfirmation(true)">
                                    ${options.confirmText || 'Confirm'}
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);
                this.activePopup = overlay;
                this.confirmationResolver = resolve;

                // Show popup
                setTimeout(() => {
                    overlay.classList.add('show');
                }, 100);
            });
        }

        resolveConfirmation(result) {
            if (this.confirmationResolver) {
                this.confirmationResolver(result);
                this.confirmationResolver = null;
            }
            this.closePopup();
        }

        // Utility methods
        getIcon(type) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️',
                loading: '⏳'
            };
            return icons[type] || icons.success;
        }

        // Quick methods for common actions
        success(title, message, options = {}) {
            return this.showToast({
                type: 'success',
                title,
                message,
                ...options
            });
        }

        error(title, message, options = {}) {
            return this.showToast({
                type: 'error',
                title,
                message,
                duration: 5000,
                ...options
            });
        }

        warning(title, message, options = {}) {
            return this.showToast({
                type: 'warning',
                title,
                message,
                duration: 5000,
                ...options
            });
        }

        info(title, message, options = {}) {
            return this.showToast({
                type: 'info',
                title,
                message,
                ...options
            });
        }

        // Integration with existing BTT systems
        integrateWithForms() {
            // Intercept form submissions
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form.classList.contains('duo-notify-on-submit')) {
                    const loadingToast = this.showLoading('Saving your changes...');
                    
                    // Remove loading toast when form submission completes
                    form.addEventListener('btt:form:success', () => {
                        this.removeToast(loadingToast);
                    }, { once: true });

                    form.addEventListener('btt:form:error', () => {
                        this.removeToast(loadingToast);
                    }, { once: true });
                }
            });
        }

        // Apply theme class to body
        activateTheme() {
            document.body.classList.add('duo-theme');
        }

        deactivateTheme() {
            document.body.classList.remove('duo-theme');
        }
    }

    // Initialize and expose globally
    const DuoNotify = new DuoNotifications();
    
    // Expose to global scope
    window.DuoNotifications = DuoNotifications;
    window.DuoNotify = DuoNotify;

    // Auto-activate theme
    document.addEventListener('DOMContentLoaded', () => {
        DuoNotify.activateTheme();
        DuoNotify.integrateWithForms();
    });

    // If DOM already loaded
    if (document.readyState === 'loading') {
        // Do nothing, event listener above will handle
    } else {
        DuoNotify.activateTheme();
        DuoNotify.integrateWithForms();
    }

})();