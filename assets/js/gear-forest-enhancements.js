/**
 * Gear Page Forest Theme Enhancements
 * Smooth animations, transitions, and UX improvements
 * @version 1.0.0
 */

(function($, window, document) {
    'use strict';

    /**
     * Forest Theme Enhancements for Gear Page
     */
    window.GearForestEnhancements = {
        
        /**
         * Initialize all enhancements
         */
        init: function() {
            this.initAnimations();
            this.initInteractions();
            this.initParallaxEffects();
            this.initAccessibilityEnhancements();
        },

        /**
         * Initialize smooth animations
         */
        initAnimations: function() {
            // Stagger animation for filter chips
            $('.filter-chip').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 50) + 'ms',
                    'animation': 'fadeInUp 0.6s ease forwards'
                });
            });

            // Animate gear cards on load
            this.animateGearCards();

            // Animate stats on scroll
            this.initCounterAnimations();
        },

        /**
         * Animate gear cards with stagger effect
         */
        animateGearCards: function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            // Initially hide cards for animation
            $('.gear-card').each(function(index) {
                $(this).css({
                    'opacity': '0',
                    'transform': 'translateY(30px)',
                    'transition': 'all 0.6s ease'
                });
                observer.observe(this);
            });
        },

        /**
         * Initialize interactive elements
         */
        initInteractions: function() {
            // Enhanced hover effects for gear cards
            $('.gear-card').on('mouseenter', function() {
                $(this).find('.gear-icon').css('transform', 'scale(1.1) rotate(5deg)');
                $(this).find('.gear-name').css('color', '#4caf50');
            }).on('mouseleave', function() {
                $(this).find('.gear-icon').css('transform', 'scale(1) rotate(0deg)');
                $(this).find('.gear-name').css('color', '#ffffff');
            });

            // Tab interaction enhancements  
            $('.gear-tab').on('mouseenter', function() {
                if (!$(this).hasClass('active')) {
                    $(this).css({
                        'transform': 'translateY(-3px)',
                        'box-shadow': '0 4px 16px rgba(76, 175, 80, 0.15)'
                    });
                }
            }).on('mouseleave', function() {
                if (!$(this).hasClass('active')) {
                    $(this).css({
                        'transform': 'translateY(0)',
                        'box-shadow': 'none'
                    });
                }
            });

            // Filter chip interaction enhancements
            $('.filter-chip').on('mouseenter', function() {
                if (!$(this).hasClass('active')) {
                    $(this).css('transform', 'translateY(-3px) scale(1.05)');
                }
            }).on('mouseleave', function() {
                if (!$(this).hasClass('active')) {
                    $(this).css('transform', 'translateY(0) scale(1)');
                }
            });

            // Search bar focus animations
            $('#gear-search-main').on('focus', function() {
                $(this).closest('.search-bar').addClass('focused');
                $(this).closest('.search-bar').find('.search-icon').css({
                    'transform': 'scale(1.2)',
                    'color': '#4caf50'
                });
            }).on('blur', function() {
                $(this).closest('.search-bar').removeClass('focused');
                $(this).closest('.search-bar').find('.search-icon').css({
                    'transform': 'scale(1)',
                    'color': ''
                });
            });

            // Add ripple effect to buttons
            this.addRippleEffect();
        },

        /**
         * Add ripple effect to clickable elements
         */
        addRippleEffect: function() {
            $('.btn, .filter-chip, .gear-card').on('click', function(e) {
                const button = $(this);
                const ripple = $('<div class="ripple"></div>');
                
                button.append(ripple);
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.css({
                    'width': size + 'px',
                    'height': size + 'px',
                    'left': x + 'px',
                    'top': y + 'px',
                    'position': 'absolute',
                    'border-radius': '50%',
                    'background': 'rgba(255, 255, 255, 0.3)',
                    'transform': 'scale(0)',
                    'animation': 'ripple 0.6s ease-out',
                    'pointer-events': 'none'
                });
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
            
            // Add ripple animation CSS
            if (!$('#ripple-styles').length) {
                $('<style id="ripple-styles">')
                    .text('@keyframes ripple { to { transform: scale(2); opacity: 0; } }')
                    .appendTo('head');
            }
        },

        /**
         * Initialize parallax effects for hero section
         */
        initParallaxEffects: function() {
            $(window).on('scroll', () => {
                const scrolled = $(window).scrollTop();
                const hero = $('.hero-forest.gear-hero');
                
                if (hero.length) {
                    hero.css('transform', `translateY(${scrolled * 0.1}px)`);
                }
                
                // Animate floating particles
                $('.hero-forest::before').css('transform', `translateY(${scrolled * -0.2}px) rotate(${scrolled * 0.05}deg)`);
            });
        },

        /**
         * Initialize counter animations for stats
         */
        initCounterAnimations: function() {
            const animateCounter = (element, target, duration = 1000) => {
                const startValue = 0;
                const increment = target / (duration / 16); // 60fps
                let currentValue = startValue;
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= target) {
                        currentValue = target;
                        clearInterval(timer);
                    }
                    element.text(Math.floor(currentValue));
                }, 16);
            };

            // Observe stat values for animation
            const statObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const $statValue = $(entry.target);
                        const targetValue = parseInt($statValue.text()) || 0;
                        animateCounter($statValue, targetValue);
                        statObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            $('.stat-value').each(function() {
                statObserver.observe(this);
            });
        },

        /**
         * Initialize accessibility enhancements
         */
        initAccessibilityEnhancements: function() {
            // Add keyboard navigation for filter chips
            $('.filter-chip').attr('tabindex', '0').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });

            // Focus management for modals
            $(document).on('shown.modal', '.modal', function() {
                $(this).find('input, select, textarea, button').first().focus();
            });

            // Announce filter changes to screen readers
            $('.filter-chip').on('click', function() {
                const category = $(this).data('category');
                const announcement = `Filter changed to ${category === 'all' ? 'all gear' : category}`;
                
                // Create temporary announcement element
                const $announcement = $('<div>')
                    .attr('aria-live', 'polite')
                    .attr('aria-atomic', 'true')
                    .addClass('sr-only')
                    .text(announcement);
                
                $('body').append($announcement);
                setTimeout(() => $announcement.remove(), 1000);
            });
        },

        /**
         * Add loading animations
         */
        showLoadingState: function() {
            $('.gear-grid').css('opacity', '0.5');
            $('.loading-spinner').show().css({
                'animation': 'pulse 1.5s ease-in-out infinite'
            });
        },

        /**
         * Hide loading animations
         */
        hideLoadingState: function() {
            $('.gear-grid').css('opacity', '1');
            $('.loading-spinner').hide();
        },

        /**
         * Add success feedback animation
         */
        showSuccessFeedback: function(message) {
            const $feedback = $(`
                <div class="success-feedback">
                    <div class="success-icon">✓</div>
                    <div class="success-message">${message}</div>
                </div>
            `).css({
                'position': 'fixed',
                'top': '2rem',
                'right': '2rem',
                'background': 'linear-gradient(135deg, #4caf50 0%, #2e7d32 100%)',
                'color': 'white',
                'padding': '1rem 2rem',
                'border-radius': '12px',
                'box-shadow': '0 8px 32px rgba(76, 175, 80, 0.3)',
                'display': 'flex',
                'align-items': 'center',
                'gap': '0.75rem',
                'z-index': '10000',
                'transform': 'translateX(400px)',
                'transition': 'all 0.5s ease'
            });

            $('body').append($feedback);
            
            setTimeout(() => {
                $feedback.css('transform', 'translateX(0)');
            }, 100);

            setTimeout(() => {
                $feedback.css('transform', 'translateX(400px)');
                setTimeout(() => $feedback.remove(), 500);
            }, 3000);
        }
    };

    /**
     * Enhanced GearManager integration
     */
    if (window.GearManager) {
        // Override loading states
        const originalSetLoading = window.GearManager.setLoading;
        window.GearManager.setLoading = function(loading) {
            if (loading) {
                GearForestEnhancements.showLoadingState();
            } else {
                GearForestEnhancements.hideLoadingState();
            }
            originalSetLoading.call(this, loading);
        };

        // Override success messages
        const originalShowSuccess = window.GearManager.showSuccess;
        window.GearManager.showSuccess = function(message) {
            GearForestEnhancements.showSuccessFeedback(message);
            originalShowSuccess.call(this, message);
        };

        // Enhance render items
        const originalRenderItems = window.GearManager.renderItems;
        window.GearManager.renderItems = function() {
            originalRenderItems.call(this);
            // Re-animate cards after rendering
            setTimeout(() => {
                GearForestEnhancements.animateGearCards();
            }, 100);
        };
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Wait a bit for other scripts to load
        setTimeout(() => {
            GearForestEnhancements.init();
        }, 300);
    });

    // Add CSS for screen reader only content
    if (!$('#sr-only-styles').length) {
        $('<style id="sr-only-styles">')
            .text(`
                .sr-only {
                    position: absolute !important;
                    width: 1px !important;
                    height: 1px !important;
                    padding: 0 !important;
                    margin: -1px !important;
                    overflow: hidden !important;
                    clip: rect(0, 0, 0, 0) !important;
                    white-space: nowrap !important;
                    border: 0 !important;
                }
            `)
            .appendTo('head');
    }

})(jQuery, window, document);