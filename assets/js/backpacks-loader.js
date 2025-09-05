/**
 * Backpacks Page Loader
 * Manages initialization to prevent timeouts and conflicts
 */

(function() {
    'use strict';
    
    console.log('Backpacks loader starting...');
    
    // Wait for jQuery to be available
    let jqueryWaitCount = 0;
    const maxJqueryWait = 50; // 5 seconds
    
    function waitForJQuery() {
        if (typeof jQuery === 'undefined' && jqueryWaitCount < maxJqueryWait) {
            jqueryWaitCount++;
            setTimeout(waitForJQuery, 100);
            return;
        }
        
        if (typeof jQuery === 'undefined') {
            console.error('jQuery failed to load after 5 seconds');
            return;
        }
        
        console.log('jQuery loaded, initializing backpacks...');
        initializeBackpacks();
    }
    
    function initializeBackpacks() {
        // Check if we're on the backpacks page
        if (document.body.dataset.page !== 'backpacks') {
            return;
        }
        
        // Initialize in sequence to prevent conflicts
        jQuery(document).ready(function($) {
            console.log('Document ready, starting full backpack builder initialization...');
            
            // Step 1: Initialize Pack Builder Core (if available)
            if (window.PackBuilder && typeof window.PackBuilder.init === 'function') {
                try {
                    console.log('Initializing PackBuilder core...');
                    window.PackBuilder.init();
                } catch (e) {
                    console.error('PackBuilder init error:', e);
                }
            }
            
            // Step 2: Initialize Drag and Drop after a short delay
            setTimeout(function() {
                if (window.PackBuilderDragDrop && typeof window.PackBuilderDragDrop.init === 'function') {
                    try {
                        console.log('Initializing drag and drop...');
                        window.PackBuilderDragDrop.init();
                    } catch (e) {
                        console.error('Drag and drop init error:', e);
                    }
                }
            }, 200);
            
            // Step 3: Initialize Section Management
            setTimeout(function() {
                if (window.SectionManager && typeof window.SectionManager.init === 'function') {
                    try {
                        console.log('Initializing section management...');
                        window.SectionManager.init();
                    } catch (e) {
                        console.error('Section management init error:', e);
                    }
                }
            }, 400);
            
            // Step 4: Initialize Pack Builder CRUD
            setTimeout(function() {
                if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.init === 'function') {
                    try {
                        console.log('Initializing PackBuilderCRUD...');
                        window.PackBuilderCRUD.init();
                    } catch (e) {
                        console.error('PackBuilderCRUD init error:', e);
                    }
                }
            }, 600);
            
            // Step 5: Load packs and gear after everything is initialized
            setTimeout(function() {
                console.log('Loading initial data...');
                
                // Load gear library first (most important for pack builder)
                if (window.PackBuilder && typeof window.PackBuilder.loadGearLibrary === 'function') {
                    console.log('Loading gear library...');
                    window.PackBuilder.loadGearLibrary().then(() => {
                        // Force render if we're on builder view
                        if (window.PackBuilder.state && window.PackBuilder.state.currentView === 'builder') {
                            window.PackBuilder.renderGearLibrary();
                        }
                    }).catch(e => {
                        console.error('Gear library load error:', e);
                        // Force render with fallback data
                        if (window.PackBuilder.renderGearLibrary) {
                            window.PackBuilder.renderGearLibrary();
                        }
                    });
                }
                
                // Load existing packs
                if (window.PackBuilder && typeof window.PackBuilder.loadPacks === 'function') {
                    window.PackBuilder.loadPacks();
                } else if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.loadExistingPacks === 'function') {
                    window.PackBuilderCRUD.loadExistingPacks();
                }
            }, 1000);
            
            // Force gear library render when switching to builder view
            setTimeout(function() {
                $(document).on('click', '.pack-tab[data-view="builder"], .view-tab[data-view="builder"]', function() {
                    setTimeout(() => {
                        if (window.PackBuilder && typeof window.PackBuilder.renderGearLibrary === 'function') {
                            console.log('Force rendering gear library for builder view');
                            window.PackBuilder.renderGearLibrary();
                        }
                    }, 100);
                });
            }, 1200);
        });
    }
    
    // Start the process
    waitForJQuery();
})();