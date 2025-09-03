/**
 * Performance Optimizer for BeyondTrailTales
 * Handles lazy loading, debouncing, and performance optimizations
 */

(function() {
    'use strict';
    
    // Debounce function for performance
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Throttle function for scroll events
    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // Lazy load images
    function lazyLoadImages() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
    
    // Initialize performance optimizations
    document.addEventListener('DOMContentLoaded', function() {
        // Lazy load images
        if ('IntersectionObserver' in window) {
            lazyLoadImages();
        }
        
        // Optimize scroll events
        const scrollHandler = throttle(() => {
            // Handle scroll-based operations
        }, 100);
        
        window.addEventListener('scroll', scrollHandler, { passive: true });
        
        // Optimize resize events
        const resizeHandler = debounce(() => {
            // Handle resize-based operations
        }, 250);
        
        window.addEventListener('resize', resizeHandler);
        
        console.log('✅ Performance optimizations initialized');
    });
})();
