/**
 * Backpacks Page Initialization
 * Handles proper initialization order for pack builder components
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        console.log('Initializing Backpacks page...');
        
        // Initialize achievement manager first
        if (window.BTT_USER_ID && !window.achievementManager) {
            window.achievementManager = new AchievementManager();
        }
        
        // Initialize tab switching
        initializeTabSwitching();
        
        // Delay pack builder initialization to ensure DOM is ready
        setTimeout(function() {
            // Check if we're in builder view and elements exist
            if ($('#view-builder').hasClass('active') || $('#view-builder').is(':visible')) {
                console.log('Builder view is active, elements should be available');
            }
            
            // Initialize pack builder components
            if (window.PackBuilderCRUD && !window.PackBuilderCRUD.initialized) {
                window.PackBuilderCRUD.init();
                window.PackBuilderCRUD.initialized = true;
            }
            
            // Initialize main pack builder
            if (window.PackBuilder && !window.PackBuilder.initialized) {
                window.PackBuilder.init();
                window.PackBuilder.initialized = true;
            }
        }, 100);
    });
    
    function initializeTabSwitching() {
        // Tab switching with proper element visibility
        $('.pack-tab').off('click').on('click', function() {
            const view = $(this).data('view');
            
            // Update tabs
            $('.pack-tab').removeClass('active').addClass('btn-ghost');
            $(this).removeClass('btn-ghost').addClass('active');
            
            // Update views
            $('.pack-view').removeClass('active').hide();
            $(`#view-${view}`).addClass('active').show();
            
            // If switching to builder, ensure elements are initialized
            if (view === 'builder') {
                setTimeout(function() {
                    // Re-check for required elements
                    const requiredElements = [
                        '#pack-name',
                        '#pack-description', 
                        '#pack-capacity',
                        '#pack-base-weight',
                        '#btn-save-pack',
                        '#sections-list'
                    ];
                    
                    let allFound = true;
                    requiredElements.forEach(selector => {
                        if ($(selector).length === 0) {
                            console.warn('Missing element after tab switch:', selector);
                            allFound = false;
                        }
                    });
                    
                    if (allFound) {
                        console.log('All required elements found in builder view');
                        
                        // Re-bind events if needed
                        if (window.PackBuilderCRUD) {
                            window.PackBuilderCRUD.bindEvents();
                        }
                    }
                }, 50);
            }
        });
    }
    
})(jQuery);