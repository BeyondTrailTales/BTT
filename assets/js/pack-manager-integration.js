/**
 * Pack Manager Integration
 * Bridges the Pack Manager Pro with the existing page structure
 */

// Wait for DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Pack Manager Pro if available
    if (typeof PackManagerPro !== 'undefined') {
        // Map old function calls to new Pack Manager Pro
        window.PackManager = PackManagerPro;
        
        // Initialize the pro version
        PackManagerPro.init();
        
        console.log('✅ Pack Manager Pro loaded successfully!');
    } else {
        console.warn('⚠️ Pack Manager Pro not found, falling back to basic functionality');
    }
    
// Override old functions with new implementations
    window.createBackpack = function() {
        console.log('Opening Pack Manager Pro...');
        if (window.PackManagerPro && window.PackManagerPro.openBuilder) {
            window.PackManagerPro.openBuilder();
        } else if (window.PackManager && window.PackManager.createNew) {
            window.PackManager.createNew();
        } else {
            console.error('Pack Manager Pro not loaded!');
            // Fallback to old modal
            if (window.openModal) {
                window.openModal();
            }
        }
    };
});

// Ensure compatibility with existing page buttons
window.createNew = function() {
    if (window.PackManager && window.PackManager.createNew) {
        window.PackManager.createNew();
    }
};

window.quickStart = function() {
    if (window.PackManager && window.PackManager.quickStart) {
        window.PackManager.quickStart();
    }
};

window.showTour = function() {
    if (window.PackManager && window.PackManager.showTour) {
        window.PackManager.showTour();
    }
};
