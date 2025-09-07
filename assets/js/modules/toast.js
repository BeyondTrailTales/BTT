/**
 * Toast Notification Module
 * Simple, clean toast notifications for user feedback
 */

(function(window) {
    'use strict';

    const Toast = {
        container: null,
        queue: [],
        isProcessing: false,

        /**
         * Initialize toast system
         */
        init() {
            if (this.container) return;

            // Create container
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(this.container);

            // Add styles if not already present
            if (!document.getElementById('toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    .toast-container {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 9999;
                        pointer-events: none;
                    }
                    
                    .toast {
                        background: rgba(26, 58, 46, 0.95);
                        color: #d8f3dc;
                        padding: 16px 24px;
                        border-radius: 8px;
                        margin-bottom: 10px;
                        min-width: 300px;
                        max-width: 500px;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                        backdrop-filter: blur(10px);
                        border: 1px solid rgba(82, 183, 136, 0.3);
                        pointer-events: auto;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    
                    .toast.show {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    
                    .toast.hide {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    
                    .toast-icon {
                        font-size: 1.5em;
                        flex-shrink: 0;
                    }
                    
                    .toast-content {
                        flex: 1;
                    }
                    
                    .toast-title {
                        font-weight: 600;
                        margin-bottom: 4px;
                    }
                    
                    .toast-message {
                        font-size: 0.9em;
                        opacity: 0.9;
                    }
                    
                    .toast.toast-success {
                        background: rgba(45, 106, 79, 0.95);
                        border-color: rgba(82, 183, 136, 0.5);
                    }
                    
                    .toast.toast-error {
                        background: rgba(214, 40, 40, 0.95);
                        border-color: rgba(230, 57, 70, 0.5);
                    }
                    
                    .toast.toast-warning {
                        background: rgba(247, 127, 0, 0.95);
                        border-color: rgba(255, 214, 10, 0.5);
                    }
                    
                    .toast.toast-info {
                        background: rgba(69, 123, 157, 0.95);
                        border-color: rgba(116, 198, 157, 0.5);
                    }
                    
                    @media (max-width: 640px) {
                        .toast-container {
                            left: 10px;
                            right: 10px;
                            top: 10px;
                        }
                        
                        .toast {
                            min-width: auto;
                            max-width: none;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        },

        /**
         * Show a toast notification
         */
        show(message, type = 'info', duration = 4000) {
            this.init();

            // Add to queue
            this.queue.push({ message, type, duration });
            
            // Process queue
            if (!this.isProcessing) {
                this.processQueue();
            }
        },

        /**
         * Process notification queue
         */
        async processQueue() {
            if (this.queue.length === 0) {
                this.isProcessing = false;
                return;
            }

            this.isProcessing = true;
            const { message, type, duration } = this.queue.shift();

            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            // Get icon based on type
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            // Parse message - could be string or object with title/message
            let title = '';
            let content = message;
            
            if (typeof message === 'object') {
                title = message.title || '';
                content = message.message || message.content || '';
            }

            toast.innerHTML = `
                <div class="toast-icon">${icons[type]}</div>
                <div class="toast-content">
                    ${title ? `<div class="toast-title">${this.escapeHtml(title)}</div>` : ''}
                    <div class="toast-message">${this.escapeHtml(content)}</div>
                </div>
            `;

            // Click to dismiss
            toast.addEventListener('click', () => {
                this.hideToast(toast);
            });

            // Add to container
            this.container.appendChild(toast);

            // Trigger animation
            await this.delay(10);
            toast.classList.add('show');

            // Auto hide
            await this.delay(duration);
            await this.hideToast(toast);

            // Process next
            this.processQueue();
        },

        /**
         * Hide and remove toast
         */
        async hideToast(toast) {
            if (!toast.parentNode) return;
            
            toast.classList.add('hide');
            await this.delay(300);
            toast.remove();
        },

        /**
         * Utility methods
         */
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Convenience methods
         */
        success(message, duration) {
            this.show(message, 'success', duration);
        },

        error(message, duration) {
            this.show(message, 'error', duration);
        },

        warning(message, duration) {
            this.show(message, 'warning', duration);
        },

        info(message, duration) {
            this.show(message, 'info', duration);
        }
    };

    // Export to window
    window.Toast = Toast;
    window.showToast = Toast.show.bind(Toast);

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Toast.init());
    } else {
        Toast.init();
    }

})(window);