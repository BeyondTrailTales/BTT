/**
 * Toast Notifications System
 * Provides user feedback through non-intrusive toast messages
 */

(function() {
    'use strict';
    
    const ToastManager = {
        container: null,
        queue: [],
        
        init: function() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                this.container.setAttribute('role', 'region');
                this.container.setAttribute('aria-live', 'polite');
                this.container.setAttribute('aria-label', 'Notifications');
                document.body.appendChild(this.container);
            }
        },
        
        show: function(message, type = 'info', duration = 3000) {
            this.init();
            
            const toast = this.createToast(message, type);
            this.container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Auto dismiss
            if (duration > 0) {
                setTimeout(() => this.dismiss(toast), duration);
            }
            
            return toast;
        },
        
        createToast: function(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icons = {
                'success': '✓',
                'error': '✗',
                'warning': '⚠',
                'info': 'ℹ'
            };
            
            toast.innerHTML = `
                <span class="toast-icon">${icons[type] || icons.info}</span>
                <span class="toast-message">${message}</span>
                <button class="toast-close" aria-label="Close notification">×</button>
            `;
            
            // Close button handler
            toast.querySelector('.toast-close').addEventListener('click', () => {
                this.dismiss(toast);
            });
            
            return toast;
        },
        
        dismiss: function(toast) {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        },
        
        success: function(message, duration) {
            return this.show(message, 'success', duration);
        },
        
        error: function(message, duration) {
            return this.show(message, 'error', duration);
        },
        
        warning: function(message, duration) {
            return this.show(message, 'warning', duration);
        },
        
        info: function(message, duration) {
            return this.show(message, 'info', duration);
        }
    };
    
    // Add toast styles
    const style = document.createElement('style');
    style.textContent = `
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        }
        
        .toast {
            background: rgba(10, 40, 24, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            pointer-events: all;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
        }
        
        .toast-success {
            border-color: rgba(74, 222, 128, 0.3);
        }
        
        .toast-success .toast-icon {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
        }
        
        .toast-error {
            border-color: rgba(239, 83, 80, 0.3);
        }
        
        .toast-error .toast-icon {
            background: rgba(239, 83, 80, 0.2);
            color: #ef5350;
        }
        
        .toast-warning {
            border-color: rgba(255, 193, 7, 0.3);
        }
        
        .toast-warning .toast-icon {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .toast-info {
            border-color: rgba(33, 150, 243, 0.3);
        }
        
        .toast-info .toast-icon {
            background: rgba(33, 150, 243, 0.2);
            color: #2196f3;
        }
        
        .toast-message {
            flex: 1;
            color: #e0e0e0;
            font-size: 0.875rem;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: #9ca3af;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
            pointer-events: all;
        }
        
        .toast-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
        }
    `;
    document.head.appendChild(style);
    
    // Export to window
    window.Toast = ToastManager;
    
})();
