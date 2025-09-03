/**
 * Keyboard Shortcuts for BeyondTrailTales
 * Provides keyboard navigation and shortcuts
 */

(function() {
    'use strict';
    
    // Only initialize if jQuery is available
    if (typeof jQuery === 'undefined') {
        console.warn('Keyboard shortcuts: jQuery not loaded, skipping initialization');
        return;
    }
    
    // Keyboard shortcut mappings
    const shortcuts = {
        'Alt+T': () => window.location.href = window.BTT.baseUrl + '/trips',
        'Alt+B': () => window.location.href = window.BTT.baseUrl + '/backpacks',
        'Alt+H': () => window.location.href = window.BTT.baseUrl + '/',
        'Escape': () => {
            // Close any open modals or menus
            jQuery('.modal:visible').modal('hide');
            jQuery('.dropdown.show').removeClass('show');
        }
    };
    
    // Initialize keyboard shortcuts
    jQuery(document).ready(function() {
        jQuery(document).on('keydown', function(e) {
            const key = [];
            if (e.altKey) key.push('Alt');
            if (e.ctrlKey) key.push('Ctrl');
            if (e.shiftKey) key.push('Shift');
            if (e.key && e.key !== 'Alt' && e.key !== 'Control' && e.key !== 'Shift') {
                key.push(e.key.toUpperCase());
            }
            
            const shortcut = key.join('+');
            if (shortcuts[shortcut]) {
                e.preventDefault();
                shortcuts[shortcut]();
            }
        });
        
        console.log('✅ Keyboard shortcuts initialized');
    });
})();
