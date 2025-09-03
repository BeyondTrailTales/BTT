/**
 * Toast Notification System
 * Provides visual feedback with undo functionality
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = new Map();
        this.undoStack = [];
        this.init();
    }
    
    init() {
        // Create or find toast container
        this.container = document.querySelector('.toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            this.container.setAttribute('role', 'region');
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-label', 'Notifications');
            document.body.appendChild(this.container);
        }
        
        // Add styles
        this.injectStyles();
        
        // Listen for keyboard shortcuts
        this.initKeyboardShortcuts();
    }
    
    /**
     * Show a toast notification
     */
    show(message, options = {}) {
        const defaults = {
            type: 'info', // info, success, warning, error
            duration: 5000,
            icon: null,
            action: null, // { label: 'Undo', callback: fn }
            persistent: false,
            position: 'bottom-right'
        };
        
        const config = { ...defaults, ...options };
        
        // Create toast element
        const toast = document.createElement('div');
        const toastId = `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        toast.id = toastId;
        toast.className = `toast toast--${config.type} toast--${config.position} toast--animate-in`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', config.type === 'error' ? 'assertive' : 'polite');
        
        // Build toast content
        const icon = config.icon || this.getDefaultIcon(config.type);
        
        let actionHtml = '';
        if (config.action) {
            actionHtml = `
                <button class="toast__action" data-toast-id="${toastId}">
                    ${config.action.label}
                </button>
            `;
        }
        
        toast.innerHTML = `
            <div class="toast__content">
                ${icon ? `<span class="toast__icon">${icon}</span>` : ''}
                <span class="toast__message">${message}</span>
            </div>
            <div class="toast__actions">
                ${actionHtml}
                <button class="toast__close" aria-label="Close notification" data-toast-id="${toastId}">
                    <span>×</span>
                </button>
            </div>
        `;
        
        // Add to container
        this.container.appendChild(toast);
        
        // Store toast reference
        this.toasts.set(toastId, {
            element: toast,
            config: config,
            timeout: null
        });
        
        // Setup event handlers
        this.setupToastHandlers(toastId, config);
        
        // Auto-remove if not persistent
        if (!config.persistent) {
            const timeout = setTimeout(() => {
                this.remove(toastId);
            }, config.duration);
            
            this.toasts.get(toastId).timeout = timeout;
        }
        
        return toastId;
    }
    
    /**
     * Setup toast event handlers
     */
    setupToastHandlers(toastId, config) {
        const toastData = this.toasts.get(toastId);
        if (!toastData) return;
        
        const toast = toastData.element;
        
        // Close button
        const closeBtn = toast.querySelector('.toast__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.remove(toastId);
            });
        }
        
        // Action button
        const actionBtn = toast.querySelector('.toast__action');
        if (actionBtn && config.action) {
            actionBtn.addEventListener('click', () => {
                // Store undo action if provided
                if (config.action.undoCallback) {
                    this.undoStack.push({
                        label: config.action.label,
                        callback: config.action.undoCallback,
                        timestamp: Date.now()
                    });
                }
                
                // Execute action
                if (config.action.callback) {
                    config.action.callback();
                }
                
                // Remove toast
                this.remove(toastId);
            });
        }
        
        // Pause on hover
        toast.addEventListener('mouseenter', () => {
            if (toastData.timeout) {
                clearTimeout(toastData.timeout);
                toastData.timeout = null;
            }
        });
        
        toast.addEventListener('mouseleave', () => {
            if (!config.persistent && !toastData.timeout) {
                toastData.timeout = setTimeout(() => {
                    this.remove(toastId);
                }, 2000);
            }
        });
    }
    
    /**
     * Remove a toast
     */
    remove(toastId) {
        const toastData = this.toasts.get(toastId);
        if (!toastData) return;
        
        const toast = toastData.element;
        
        // Clear timeout if exists
        if (toastData.timeout) {
            clearTimeout(toastData.timeout);
        }
        
        // Animate out
        toast.classList.add('toast--animate-out');
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            this.toasts.delete(toastId);
        }, 300);
    }
    
    /**
     * Remove all toasts
     */
    clear() {
        this.toasts.forEach((data, id) => {
            this.remove(id);
        });
    }
    
    /**
     * Show success toast
     */
    success(message, options = {}) {
        return this.show(message, { ...options, type: 'success' });
    }
    
    /**
     * Show error toast
     */
    error(message, options = {}) {
        return this.show(message, { ...options, type: 'error', duration: 8000 });
    }
    
    /**
     * Show warning toast
     */
    warning(message, options = {}) {
        return this.show(message, { ...options, type: 'warning' });
    }
    
    /**
     * Show info toast
     */
    info(message, options = {}) {
        return this.show(message, { ...options, type: 'info' });
    }
    
    /**
     * Show toast with undo action
     */
    showWithUndo(message, undoCallback, options = {}) {
        return this.show(message, {
            ...options,
            action: {
                label: 'Undo',
                callback: undoCallback,
                undoCallback: undoCallback
            }
        });
    }
    
    /**
     * Get default icon for toast type
     */
    getDefaultIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }
    
    /**
     * Initialize keyboard shortcuts
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Z for undo
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                const lastUndo = this.undoStack.pop();
                if (lastUndo) {
                    e.preventDefault();
                    lastUndo.callback();
                    this.info('Action undone');
                }
            }
            
            // Escape to clear all toasts
            if (e.key === 'Escape' && e.ctrlKey) {
                e.preventDefault();
                this.clear();
            }
        });
    }
    
    /**
     * Inject required styles
     */
    injectStyles() {
        if (document.getElementById('toast-styles')) return;
        
        const styles = `
            .toast-container {
                position: fixed;
                bottom: 1rem;
                right: 1rem;
                z-index: 10000;
                pointer-events: none;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                max-width: 420px;
            }
            
            @media (max-width: 640px) {
                .toast-container {
                    left: 1rem;
                    right: 1rem;
                    max-width: none;
                }
            }
            
            .toast {
                pointer-events: auto;
                background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(249,250,251,0.98) 100%);
                backdrop-filter: blur(20px) saturate(1.8);
                border: 1px solid rgba(255,255,255,0.5);
                border-radius: 0.75rem;
                box-shadow: 
                    0 10px 25px rgba(0,0,0,0.1),
                    0 4px 10px rgba(0,0,0,0.06),
                    inset 0 1px 0 rgba(255,255,255,0.6);
                padding: 1rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-width: 300px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .toast:hover {
                transform: translateY(-2px);
                box-shadow: 
                    0 12px 30px rgba(0,0,0,0.12),
                    0 5px 12px rgba(0,0,0,0.08),
                    inset 0 1px 0 rgba(255,255,255,0.7);
            }
            
            .toast__content {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex: 1;
            }
            
            .toast__icon {
                font-size: 1.25rem;
                flex-shrink: 0;
            }
            
            .toast__message {
                color: #1f2937;
                font-size: 0.875rem;
                line-height: 1.4;
                font-weight: 500;
            }
            
            .toast__actions {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-left: 1rem;
            }
            
            .toast__action {
                padding: 0.375rem 0.75rem;
                background: transparent;
                border: 1px solid rgba(16,185,129,0.3);
                border-radius: 0.375rem;
                color: #10b981;
                font-size: 0.75rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                white-space: nowrap;
            }
            
            .toast__action:hover {
                background: rgba(16,185,129,0.1);
                border-color: #10b981;
                transform: translateY(-1px);
            }
            
            .toast__close {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: transparent;
                border: none;
                color: #9ca3af;
                font-size: 1.25rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                flex-shrink: 0;
            }
            
            .toast__close:hover {
                background: rgba(0,0,0,0.05);
                color: #4b5563;
                transform: rotate(90deg);
            }
            
            /* Toast types */
            .toast--success {
                border-left: 4px solid #10b981;
            }
            
            .toast--success .toast__icon {
                color: #10b981;
            }
            
            .toast--error {
                border-left: 4px solid #ef4444;
            }
            
            .toast--error .toast__icon {
                color: #ef4444;
            }
            
            .toast--error .toast__message {
                color: #991b1b;
            }
            
            .toast--warning {
                border-left: 4px solid #f59e0b;
            }
            
            .toast--warning .toast__icon {
                color: #f59e0b;
            }
            
            .toast--info {
                border-left: 4px solid #3b82f6;
            }
            
            .toast--info .toast__icon {
                color: #3b82f6;
            }
            
            /* Animations */
            .toast--animate-in {
                animation: toastSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .toast--animate-out {
                animation: toastSlideOut 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                opacity: 0;
                transform: translateX(100%);
            }
            
            @keyframes toastSlideIn {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes toastSlideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
            
            /* Position variants */
            .toast--top-right {
                /* Container would need to be repositioned */
            }
            
            .toast--top-center {
                /* Container would need to be repositioned */
            }
            
            .toast--bottom-center {
                /* Container would need to be repositioned */
            }
            
            /* Dark mode */
            @media (prefers-color-scheme: dark) {
                .toast {
                    background: linear-gradient(135deg, rgba(31,41,55,0.98) 0%, rgba(17,24,39,0.98) 100%);
                    border-color: rgba(75,85,99,0.5);
                }
                
                .toast__message {
                    color: #f3f4f6;
                }
                
                .toast__close {
                    color: #6b7280;
                }
                
                .toast__close:hover {
                    background: rgba(255,255,255,0.1);
                    color: #d1d5db;
                }
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = 'toast-styles';
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
}

// Initialize global toast manager
window.toastManager = new ToastManager();

// Convenience functions
window.showToast = function(message, options) {
    return window.toastManager.show(message, options);
};

window.showSuccess = function(message, options) {
    return window.toastManager.success(message, options);
};

window.showError = function(message, options) {
    return window.toastManager.error(message, options);
};

window.showWarning = function(message, options) {
    return window.toastManager.warning(message, options);
};

window.showInfo = function(message, options) {
    return window.toastManager.info(message, options);
};

window.showToastWithUndo = function(message, undoCallback, options) {
    return window.toastManager.showWithUndo(message, undoCallback, options);
};
