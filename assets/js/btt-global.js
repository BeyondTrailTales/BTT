/**
 * BeyondTrailTales Global JavaScript
 * Loads on all pages to provide consistent functionality
 * 
 * Includes:
 * - Unified API client
 * - Toast notifications
 * - Common utilities
 * - Global event handlers
 */

// Load required modules
const globalModules = [
    '/BTT/assets/js/btt-api-unified.js',
    '/BTT/assets/js/modules/toast.js'
];

// Load modules sequentially
async function loadGlobalModules() {
    for (const module of globalModules) {
        try {
            await loadScript(module);
            console.log(`[BTT Global] Loaded: ${module}`);
        } catch (error) {
            console.error(`[BTT Global] Failed to load: ${module}`, error);
        }
    }
    
    // Initialize global features
    initializeGlobalFeatures();
}

// Script loader
function loadScript(src) {
    return new Promise((resolve, reject) => {
        // Check if already loaded
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }
        
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

// Initialize global features
function initializeGlobalFeatures() {
    console.log('[BTT Global] Initializing global features...');
    
    // Set up global error handler
    window.addEventListener('error', (event) => {
        console.error('[BTT Global] Error:', event.error);
    });
    
    // Set up global AJAX error handler
    if (window.BTT_API) {
        const originalRequest = window.BTT_API.request;
        window.BTT_API.request = async function(...args) {
            try {
                return await originalRequest.apply(this, args);
            } catch (error) {
                // Show user-friendly error
                if (error.name === 'AbortError') {
                    window.Toast?.error('Request timed out. Please check your connection.');
                } else if (error.message.includes('NetworkError')) {
                    window.Toast?.error('Network error. Please check your connection.');
                } else {
                    window.Toast?.error(error.message || 'An unexpected error occurred.');
                }
                throw error;
            }
        };
    }
    
    // Add global utilities
    window.BTT = window.BTT || {};
    
    // Format weight utility
    window.BTT.formatWeight = function(grams) {
        if (!grams || grams === 0) return '0g';
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return grams + 'g';
    };
    
    // Format date utility
    window.BTT.formatDate = function(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };
    
    // Debounce utility
    window.BTT.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
    
    // Escape HTML utility
    window.BTT.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Ctrl/Cmd + S to save (prevent default)
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            // Trigger save on current page if available
            const saveBtn = document.querySelector('[data-action="save"], #btn-save, .btn-save');
            if (saveBtn && !saveBtn.disabled) {
                saveBtn.click();
            }
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal.show, .modal:not(.hidden)');
            if (modal) {
                const closeBtn = modal.querySelector('.modal-close, [data-dismiss="modal"]');
                if (closeBtn) {
                    closeBtn.click();
                }
            }
        }
    });
    
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('show');
            document.body.classList.toggle('menu-open');
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                mobileMenu.classList.remove('show');
                document.body.classList.remove('menu-open');
            }
        });
    }
    
    // Initialize tooltips
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', (e) => {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-popup';
            tooltip.textContent = e.target.dataset.tooltip;
            document.body.appendChild(tooltip);
            
            const rect = e.target.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
            
            e.target._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', (e) => {
            if (e.target._tooltip) {
                e.target._tooltip.remove();
                delete e.target._tooltip;
            }
        });
    });
    
    console.log('[BTT Global] Global features initialized');
}

// Load modules when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadGlobalModules);
} else {
    loadGlobalModules();
}