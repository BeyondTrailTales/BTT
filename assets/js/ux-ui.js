/**
 * BeyondTrailTales UX UI Utilities
 * Reusable UI components for the backpacker-focused interface
 * jQuery-based, accessible, and performant
 */

(function(window, $) {
    'use strict';
    
    // Create namespace
    window.UX = window.UX || {};
    
    /**
     * Toast Notifications
     * Usage: UX.toast('Your pack is saved!', 'success');
     */
    UX.toast = (function() {
        let container = null;
        let toastCount = 0;
        
        function init() {
            if (!container) {
                container = $('<div>')
                    .addClass('toast-container')
                    .attr('role', 'region')
                    .attr('aria-live', 'polite')
                    .attr('aria-label', 'Notifications')
                    .appendTo('body');
                    
                // Add to UX refresh container if exists
                if ($('body').hasClass('ux-refresh')) {
                    container.addClass('ux-refresh');
                }
            }
        }
        
        function show(message, type = 'info', duration = 3000) {
            init();
            
            const toastId = 'toast-' + (++toastCount);
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            const $toast = $('<div>')
                .addClass('toast toast-' + type)
                .attr('id', toastId)
                .attr('role', 'status')
                .html(`
                    <span class="toast-icon" aria-hidden="true">${icons[type] || icons.info}</span>
                    <span class="toast-message">${message}</span>
                    <button class="toast-close" aria-label="Dismiss notification">×</button>
                `)
                .hide();
            
            container.append($toast);
            $toast.fadeIn(300);
            
            // Auto-dismiss
            if (duration > 0) {
                setTimeout(() => dismiss(toastId), duration);
            }
            
            // Close button handler
            $toast.find('.toast-close').on('click', () => dismiss(toastId));
            
            return toastId;
        }
        
        function dismiss(toastId) {
            $('#' + toastId).fadeOut(300, function() {
                $(this).remove();
            });
        }
        
        return {
            show: show,
            success: (msg, duration) => show(msg, 'success', duration),
            error: (msg, duration) => show(msg, 'error', duration || 5000),
            warning: (msg, duration) => show(msg, 'warning', duration),
            info: (msg, duration) => show(msg, 'info', duration)
        };
    })();
    
    /**
     * Loading Overlay
     * Usage: UX.loading.show(); ... UX.loading.hide();
     */
    UX.loading = (function() {
        let overlay = null;
        let stack = 0;
        
        function init() {
            if (!overlay) {
                overlay = $('<div>')
                    .addClass('loading-overlay')
                    .attr('role', 'status')
                    .attr('aria-live', 'polite')
                    .html(`
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p class="loading-text">Loading...</p>
                        </div>
                    `)
                    .hide()
                    .appendTo('body');
                    
                if ($('body').hasClass('ux-refresh')) {
                    overlay.addClass('ux-refresh');
                }
            }
        }
        
        function show(text = 'Loading...') {
            init();
            stack++;
            
            if (stack === 1) {
                overlay.find('.loading-text').text(text);
                overlay.fadeIn(200);
                $('body').addClass('loading-active');
            }
        }
        
        function hide() {
            if (stack > 0) {
                stack--;
                
                if (stack === 0 && overlay) {
                    overlay.fadeOut(200);
                    $('body').removeClass('loading-active');
                }
            }
        }
        
        return {
            show: show,
            hide: hide
        };
    })();
    
    /**
     * Accordion/Collapsible
     * Usage: UX.accordion.init('.accordion-container');
     */
    UX.accordion = (function() {
        function init(selector = '.accordion') {
            $(selector).each(function() {
                const $accordion = $(this);
                
                // Add ARIA attributes
                $accordion.attr('role', 'region');
                
                $accordion.find('.accordion-item').each(function(index) {
                    const $item = $(this);
                    const $header = $item.find('.accordion-header');
                    const $content = $item.find('.accordion-content');
                    const itemId = $item.attr('id') || 'accordion-item-' + index;
                    const contentId = itemId + '-content';
                    
                    // Set IDs
                    $item.attr('id', itemId);
                    $content.attr('id', contentId);
                    
                    // Add ARIA attributes
                    $header
                        .attr('role', 'button')
                        .attr('tabindex', '0')
                        .attr('aria-expanded', $item.hasClass('active') ? 'true' : 'false')
                        .attr('aria-controls', contentId);
                    
                    $content
                        .attr('role', 'region')
                        .attr('aria-labelledby', itemId);
                    
                    // Click handler
                    $header.on('click', function() {
                        toggle($item);
                    });
                    
                    // Keyboard handler
                    $header.on('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            toggle($item);
                        }
                    });
                });
            });
        }
        
        function toggle($item) {
            const $header = $item.find('.accordion-header');
            const $content = $item.find('.accordion-content');
            const isOpen = $item.hasClass('active');
            
            if (isOpen) {
                $item.removeClass('active');
                $header.attr('aria-expanded', 'false');
                $content.slideUp(300);
            } else {
                // Close siblings if needed
                if ($item.parent().data('accordion-single')) {
                    $item.siblings('.active').each(function() {
                        const $sibling = $(this);
                        $sibling.removeClass('active');
                        $sibling.find('.accordion-header').attr('aria-expanded', 'false');
                        $sibling.find('.accordion-content').slideUp(300);
                    });
                }
                
                $item.addClass('active');
                $header.attr('aria-expanded', 'true');
                $content.slideDown(300);
            }
        }
        
        return {
            init: init
        };
    })();
    
    /**
     * Step Wizard
     * Usage: UX.stepper.init('#trip-wizard');
     */
    UX.stepper = (function() {
        function init(selector) {
            const $wizard = $(selector);
            if (!$wizard.length) return;
            
            const $steps = $wizard.find('.step');
            const $contents = $wizard.find('.step-content');
            const $prevBtn = $wizard.find('.btn-prev');
            const $nextBtn = $wizard.find('.btn-next');
            const $submitBtn = $wizard.find('.btn-submit');
            
            let currentStep = 0;
            const totalSteps = $steps.length;
            
            // Initialize
            showStep(0);
            
            // Navigation handlers
            $prevBtn.on('click', () => {
                if (currentStep > 0) {
                    showStep(currentStep - 1);
                }
            });
            
            $nextBtn.on('click', () => {
                if (validateStep(currentStep) && currentStep < totalSteps - 1) {
                    showStep(currentStep + 1);
                }
            });
            
            // Step click handler
            $steps.on('click', function() {
                const stepIndex = $(this).index();
                if (stepIndex < currentStep || validateStep(currentStep)) {
                    showStep(stepIndex);
                }
            });
            
            function showStep(index) {
                currentStep = index;
                
                // Update steps
                $steps.removeClass('active completed')
                    .attr('aria-current', 'false');
                
                $steps.each(function(i) {
                    if (i < index) {
                        $(this).addClass('completed');
                    } else if (i === index) {
                        $(this).addClass('active')
                            .attr('aria-current', 'step');
                    }
                });
                
                // Update content
                $contents.hide().attr('aria-hidden', 'true');
                $contents.eq(index).show().attr('aria-hidden', 'false');
                
                // Update buttons
                $prevBtn.prop('disabled', index === 0);
                $nextBtn.toggle(index < totalSteps - 1);
                $submitBtn.toggle(index === totalSteps - 1);
                
                // Focus management
                $contents.eq(index).find('input, select, textarea').first().focus();
            }
            
            function validateStep(index) {
                const $content = $contents.eq(index);
                const $required = $content.find('[required]');
                let isValid = true;
                
                $required.each(function() {
                    if (!$(this).val()) {
                        $(this).addClass('error');
                        isValid = false;
                    } else {
                        $(this).removeClass('error');
                    }
                });
                
                if (!isValid) {
                    UX.toast.error('Please fill in all required fields');
                }
                
                return isValid;
            }
            
            return {
                next: () => $nextBtn.click(),
                prev: () => $prevBtn.click(),
                goTo: showStep
            };
        }
        
        return {
            init: init
        };
    })();
    
    /**
     * Debounced Autosave
     * Usage: UX.autosave(function() { saveData(); }, 1000);
     */
    UX.autosave = function(callback, delay = 1000) {
        let timer = null;
        
        return function() {
            const context = this;
            const args = arguments;
            
            clearTimeout(timer);
            
            timer = setTimeout(() => {
                callback.apply(context, args);
            }, delay);
        };
    };
    
    /**
     * Skeleton Loader
     * Usage: UX.skeleton.show('#content'); ... UX.skeleton.hide('#content');
     */
    UX.skeleton = (function() {
        function show(selector, count = 3) {
            const $container = $(selector);
            $container.empty();
            
            for (let i = 0; i < count; i++) {
                $container.append(`
                    <div class="skeleton-card">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text"></div>
                    </div>
                `);
            }
        }
        
        function hide(selector) {
            $(selector).find('.skeleton-card').remove();
        }
        
        return {
            show: show,
            hide: hide
        };
    })();
    
    /**
     * Quick Add Menu
     * Creates a floating action button with quick actions
     */
    UX.quickAdd = (function() {
        let menu = null;
        let isOpen = false;
        
        function init() {
            if (menu) return;
            
            menu = $(`
                <div class="quick-add-container">
                    <button class="btn-quick-add" aria-label="Quick actions menu" aria-expanded="false">
                        <span class="quick-add-icon">➕</span>
                    </button>
                    <div class="quick-add-menu" hidden>
                        <a href="/BTT/trips?action=new" class="quick-add-item">
                            <span class="quick-add-item-icon">🗺️</span>
                            <span>Start Trip</span>
                        </a>
                        <a href="/BTT/backpacks?action=quick-pack" class="quick-add-item">
                            <span class="quick-add-item-icon">⚡</span>
                            <span>Quick Pack</span>
                        </a>
                        <a href="/BTT/backpacks?view=gear-library&action=add" class="quick-add-item">
                            <span class="quick-add-item-icon">📦</span>
                            <span>Add Gear</span>
                        </a>
                    </div>
                </div>
            `);
            
            $('body').append(menu);
            
            const $button = menu.find('.btn-quick-add');
            const $menu = menu.find('.quick-add-menu');
            
            // Toggle menu
            $button.on('click', function() {
                isOpen = !isOpen;
                
                if (isOpen) {
                    $button.attr('aria-expanded', 'true');
                    $menu.removeAttr('hidden').fadeIn(200);
                    $button.find('.quick-add-icon').text('✕');
                } else {
                    $button.attr('aria-expanded', 'false');
                    $menu.attr('hidden', true).fadeOut(200);
                    $button.find('.quick-add-icon').text('➕');
                }
            });
            
            // Close on outside click
            $(document).on('click', function(e) {
                if (isOpen && !$(e.target).closest('.quick-add-container').length) {
                    $button.click();
                }
            });
            
            // Close on escape
            $(document).on('keydown', function(e) {
                if (isOpen && e.key === 'Escape') {
                    $button.click();
                    $button.focus();
                }
            });
        }
        
        return {
            init: init
        };
    })();
    
    /**
     * Initialize all components on DOM ready
     */
    $(document).ready(function() {
        // Only initialize if UX refresh is enabled
        if ($('body').hasClass('ux-refresh')) {
            // Initialize accordions
            UX.accordion.init();
            
            // Initialize quick add menu
            UX.quickAdd.init();
            
            // Initialize any wizards
            $('.wizard').each(function() {
                UX.stepper.init('#' + $(this).attr('id'));
            });
        }
    });
    
    // Expose to window
    window.UX = UX;
    
})(window, jQuery);
