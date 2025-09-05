/**
 * Pack Section Toggle Functionality
 * Makes the section show/hide arrows functional
 */

(function($) {
    'use strict';

    const SectionToggles = {
        init: function() {
            console.log('🔽 Initializing section toggles...');
            this.bindEvents();
            this.setupInitialState();
        },

        bindEvents: function() {
            const self = this;

            // Handle section toggle clicks - using correct class name
            $(document).off('click', '.btn-section-toggle, .section-toggle').on('click', '.btn-section-toggle, .section-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $toggle = $(this);
                const $section = $toggle.closest('.pack-section');
                
                self.toggleSection($section);
            });

            // Also handle clicking on the section header (except inputs)
            $(document).off('click', '.section-header').on('click', '.section-header', function(e) {
                // Don't toggle if clicking on input fields or buttons
                if ($(e.target).is('input, button, .btn') || $(e.target).closest('input, button, .btn').length) {
                    return;
                }
                
                e.preventDefault();
                const $section = $(this).closest('.pack-section');
                self.toggleSection($section);
            });
        },

        toggleSection: function($section) {
            const $content = $section.find('.section-content');
            const $toggle = $section.find('.btn-section-toggle, .section-toggle');
            
            if ($section.hasClass('collapsed')) {
                // Expand section
                $section.removeClass('collapsed');
                $content.slideDown(200);
                $toggle.html('▼');
                $toggle.attr('aria-expanded', 'true');
                
                console.log('Expanded section:', $section.data('section'));
            } else {
                // Collapse section
                $section.addClass('collapsed');
                $content.slideUp(200);
                $toggle.html('▶');
                $toggle.attr('aria-expanded', 'false');
                
                console.log('Collapsed section:', $section.data('section'));
            }
        },

        setupInitialState: function() {
            // Set up initial ARIA attributes for both class names
            $('.btn-section-toggle, .section-toggle').each(function() {
                const $toggle = $(this);
                const $section = $toggle.closest('.pack-section');
                const isCollapsed = $section.hasClass('collapsed');
                
                $toggle.attr('aria-expanded', !isCollapsed);
                $toggle.attr('aria-label', 'Toggle section');
                $toggle.attr('role', 'button');
                $toggle.attr('tabindex', '0');
                
                // Set initial arrow direction
                if (isCollapsed) {
                    $toggle.html('▶');
                } else {
                    $toggle.html('▼');
                }
            });

            // Handle keyboard navigation
            $(document).off('keydown', '.btn-section-toggle, .section-toggle').on('keydown', '.btn-section-toggle, .section-toggle', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
        },

        // Expand all sections
        expandAll: function() {
            $('.pack-section.collapsed').each((i, section) => {
                this.toggleSection($(section));
            });
        },

        // Collapse all sections
        collapseAll: function() {
            $('.pack-section').not('.collapsed').each((i, section) => {
                this.toggleSection($(section));
            });
        }
    };

    // Auto-initialize when document is ready
    $(document).ready(function() {
        // Wait a bit for other scripts to load
        setTimeout(() => {
            SectionToggles.init();
        }, 500);
    });

    // Make available globally
    window.SectionToggles = SectionToggles;

})(jQuery);