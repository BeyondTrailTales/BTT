/**
 * Accessibility Enhancements for BeyondTrailTales
 * Improves keyboard navigation, ARIA attributes, and screen reader support
 */

(function() {
    'use strict';
    
    // Add ARIA live regions for dynamic content
    function setupLiveRegions() {
        const regions = document.querySelectorAll('[data-live-region]');
        regions.forEach(region => {
            if (!region.getAttribute('aria-live')) {
                region.setAttribute('aria-live', 'polite');
                region.setAttribute('aria-atomic', 'true');
            }
        });
    }
    
    // Enhance focus management
    function enhanceFocusManagement() {
        // Add visible focus indicators
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-nav');
            }
        });
        
        document.addEventListener('mousedown', function() {
            document.body.classList.remove('keyboard-nav');
        });
    }
    
    // Improve form accessibility
    function enhanceFormAccessibility() {
        // Add aria-describedby to form fields with help text
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach(group => {
            const input = group.querySelector('.form-control');
            const helpText = group.querySelector('.form-text');
            
            if (input && helpText) {
                const helpId = 'help-' + Math.random().toString(36).substr(2, 9);
                helpText.id = helpId;
                input.setAttribute('aria-describedby', helpId);
            }
            
            // Add aria-required for required fields
            const requiredInputs = group.querySelectorAll('[required]');
            requiredInputs.forEach(input => {
                input.setAttribute('aria-required', 'true');
            });
        });
    }
    
    // Skip link functionality
    function setupSkipLinks() {
        const skipLink = document.querySelector('.skip-link');
        if (skipLink) {
            skipLink.addEventListener('click', function(e) {
                const target = document.querySelector(skipLink.getAttribute('href'));
                if (target) {
                    target.tabIndex = -1;
                    target.focus();
                }
            });
        }
    }
    
    // Initialize accessibility enhancements
    document.addEventListener('DOMContentLoaded', function() {
        setupLiveRegions();
        enhanceFocusManagement();
        enhanceFormAccessibility();
        setupSkipLinks();
        
        // Add keyboard-nav styles
        if (!document.getElementById('a11y-styles')) {
            const style = document.createElement('style');
            style.id = 'a11y-styles';
            style.textContent = `
                .keyboard-nav *:focus {
                    outline: 2px solid var(--forest-mint) !important;
                    outline-offset: 2px !important;
                }
                
                .sr-only {
                    position: absolute;
                    width: 1px;
                    height: 1px;
                    padding: 0;
                    margin: -1px;
                    overflow: hidden;
                    clip: rect(0, 0, 0, 0);
                    white-space: nowrap;
                    border: 0;
                }
            `;
            document.head.appendChild(style);
        }
        
        console.log('✅ Accessibility enhancements initialized');
    });
})();
