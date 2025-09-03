/**
 * Performance Optimization Module
 * Lazy loading, animation optimization, and performance monitoring
 */

class PerformanceOptimizer {
    constructor() {
        this.config = {
            lazyLoadOffset: 100,
            animationThreshold: 60, // FPS threshold
            enablePrefetch: true,
            enableImageOptimization: true,
            enableCodeSplitting: true,
            enableAnimationOptimization: true,
            cacheDuration: 3600000 // 1 hour
        };
        
        this.observers = {
            intersection: null,
            performance: null,
            resize: null
        };
        
        this.metrics = {
            fps: 60,
            loadTime: 0,
            memoryUsage: 0,
            animationsReduced: false
        };
        
        this.lazyLoadQueue = new Set();
        this.prefetchQueue = new Set();
        this.animationFrameCallbacks = new Map();
        
        this.init();
    }
    
    /**
     * Initialize performance optimizer
     */
    init() {
        console.log('Initializing Performance Optimizer...');
        
        // Setup lazy loading
        this.setupLazyLoading();
        
        // Setup animation optimization
        this.setupAnimationOptimization();
        
        // Setup resource prefetching
        this.setupPrefetching();
        
        // Monitor performance
        this.startPerformanceMonitoring();
        
        // Setup responsive image loading
        this.setupResponsiveImages();
        
        // Initialize code splitting
        this.initCodeSplitting();
        
        // Optimize CSS animations
        this.optimizeCSSAnimations();
        
        console.log('✅ Performance Optimizer initialized');
    }
    
    /**
     * Setup lazy loading for images and content
     */
    setupLazyLoading() {
        // Create Intersection Observer
        this.observers.intersection = new IntersectionObserver(
            (entries) => this.handleLazyLoad(entries),
            {
                rootMargin: `${this.config.lazyLoadOffset}px`,
                threshold: 0.01
            }
        );
        
        // Find and observe lazy-load elements
        this.observeLazyElements();
        
        // Setup lazy loading for dynamically added content
        this.setupMutationObserver();
    }
    
    /**
     * Observe elements for lazy loading
     */
    observeLazyElements() {
        // Images with data-src
        document.querySelectorAll('img[data-src], img[data-lazy]').forEach(img => {
            this.observers.intersection.observe(img);
            this.lazyLoadQueue.add(img);
        });
        
        // Background images
        document.querySelectorAll('[data-bg]').forEach(element => {
            this.observers.intersection.observe(element);
            this.lazyLoadQueue.add(element);
        });
        
        // Iframes
        document.querySelectorAll('iframe[data-src]').forEach(iframe => {
            this.observers.intersection.observe(iframe);
            this.lazyLoadQueue.add(iframe);
        });
        
        // Content sections
        document.querySelectorAll('[data-lazy-content]').forEach(section => {
            this.observers.intersection.observe(section);
            this.lazyLoadQueue.add(section);
        });
    }
    
    /**
     * Handle lazy load intersection
     */
    handleLazyLoad(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                
                if (element.tagName === 'IMG') {
                    this.loadImage(element);
                } else if (element.tagName === 'IFRAME') {
                    this.loadIframe(element);
                } else if (element.hasAttribute('data-bg')) {
                    this.loadBackgroundImage(element);
                } else if (element.hasAttribute('data-lazy-content')) {
                    this.loadContent(element);
                }
                
                // Stop observing
                this.observers.intersection.unobserve(element);
                this.lazyLoadQueue.delete(element);
            }
        });
    }
    
    /**
     * Load image with fade-in animation
     */
    loadImage(img) {
        const src = img.dataset.src || img.dataset.lazy;
        const srcset = img.dataset.srcset;
        
        if (!src) return;
        
        // Create temp image to preload
        const tempImg = new Image();
        
        tempImg.onload = () => {
            // Apply sources
            if (src) img.src = src;
            if (srcset) img.srcset = srcset;
            
            // Add loaded class for animation
            img.classList.add('lazy-loaded');
            
            // Remove data attributes
            delete img.dataset.src;
            delete img.dataset.lazy;
            delete img.dataset.srcset;
            
            // Trigger custom event
            img.dispatchEvent(new CustomEvent('lazyloaded'));
        };
        
        tempImg.onerror = () => {
            img.classList.add('lazy-error');
            console.error('Failed to load image:', src);
        };
        
        // Start loading
        tempImg.src = src;
    }
    
    /**
     * Load iframe
     */
    loadIframe(iframe) {
        const src = iframe.dataset.src;
        if (src) {
            iframe.src = src;
            delete iframe.dataset.src;
            iframe.classList.add('lazy-loaded');
        }
    }
    
    /**
     * Load background image
     */
    loadBackgroundImage(element) {
        const bg = element.dataset.bg;
        if (bg) {
            const tempImg = new Image();
            
            tempImg.onload = () => {
                element.style.backgroundImage = `url(${bg})`;
                element.classList.add('lazy-loaded');
                delete element.dataset.bg;
            };
            
            tempImg.src = bg;
        }
    }
    
    /**
     * Load lazy content via AJAX
     */
    async loadContent(element) {
        const url = element.dataset.lazyContent;
        if (!url) return;
        
        try {
            // Show loading state
            element.classList.add('loading');
            
            const response = await fetch(url);
            const content = await response.text();
            
            // Insert content
            element.innerHTML = content;
            
            // Remove loading state
            element.classList.remove('loading');
            element.classList.add('lazy-loaded');
            
            delete element.dataset.lazyContent;
            
            // Re-observe new lazy elements
            this.observeLazyElements();
        } catch (error) {
            console.error('Failed to load content:', error);
            element.classList.add('lazy-error');
        }
    }
    
    /**
     * Setup animation optimization
     */
    setupAnimationOptimization() {
        // Monitor FPS
        this.monitorFPS();
        
        // Optimize scroll performance
        this.optimizeScroll();
        
        // Setup will-change optimization
        this.setupWillChange();
        
        // Reduce animations on low-end devices
        this.detectLowEndDevice();
    }
    
    /**
     * Monitor FPS and adjust quality
     */
    monitorFPS() {
        let lastTime = performance.now();
        let frames = 0;
        let fps = 60;
        
        const measureFPS = () => {
            frames++;
            const currentTime = performance.now();
            
            if (currentTime >= lastTime + 1000) {
                fps = Math.round((frames * 1000) / (currentTime - lastTime));
                this.metrics.fps = fps;
                
                // Adjust quality based on FPS
                if (fps < 30) {
                    this.reduceAnimations();
                } else if (fps > 50 && this.metrics.animationsReduced) {
                    this.restoreAnimations();
                }
                
                frames = 0;
                lastTime = currentTime;
            }
            
            requestAnimationFrame(measureFPS);
        };
        
        requestAnimationFrame(measureFPS);
    }
    
    /**
     * Reduce animations for better performance
     */
    reduceAnimations() {
        if (this.metrics.animationsReduced) return;
        
        console.log('Reducing animations due to low FPS:', this.metrics.fps);
        
        document.body.classList.add('reduce-animations');
        this.metrics.animationsReduced = true;
        
        // Inject reduced animation styles
        this.injectReducedAnimationStyles();
        
        // Notify user if needed
        if (window.showInfo) {
            window.showInfo('Animations reduced for better performance');
        }
    }
    
    /**
     * Restore animations
     */
    restoreAnimations() {
        if (!this.metrics.animationsReduced) return;
        
        console.log('Restoring animations, FPS:', this.metrics.fps);
        
        document.body.classList.remove('reduce-animations');
        this.metrics.animationsReduced = false;
    }
    
    /**
     * Optimize scroll performance
     */
    optimizeScroll() {
        let scrollTimeout;
        let isScrolling = false;
        
        const handleScroll = () => {
            if (!isScrolling) {
                document.body.classList.add('is-scrolling');
                isScrolling = true;
            }
            
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                document.body.classList.remove('is-scrolling');
                isScrolling = false;
            }, 150);
        };
        
        // Use passive listener for better performance
        window.addEventListener('scroll', handleScroll, { passive: true });
    }
    
    /**
     * Setup will-change optimization
     */
    setupWillChange() {
        // Add will-change to elements that will animate
        document.querySelectorAll('[data-animate]').forEach(element => {
            element.addEventListener('mouseenter', () => {
                element.style.willChange = 'transform, opacity';
            });
            
            element.addEventListener('animationend', () => {
                element.style.willChange = 'auto';
            });
        });
    }
    
    /**
     * Setup resource prefetching
     */
    setupPrefetching() {
        if (!this.config.enablePrefetch) return;
        
        // Prefetch links on hover
        document.addEventListener('mouseover', (e) => {
            const link = e.target.closest('a[href]');
            if (link && !this.prefetchQueue.has(link.href)) {
                this.prefetchResource(link.href);
            }
        });
        
        // Prefetch visible links
        this.prefetchVisibleLinks();
    }
    
    /**
     * Prefetch resource
     */
    prefetchResource(url) {
        if (this.prefetchQueue.has(url)) return;
        
        this.prefetchQueue.add(url);
        
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    }
    
    /**
     * Prefetch visible links
     */
    prefetchVisibleLinks() {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const link = entry.target;
                        if (link.href && !this.prefetchQueue.has(link.href)) {
                            // Delay prefetch to avoid overwhelming
                            setTimeout(() => {
                                this.prefetchResource(link.href);
                            }, 1000);
                        }
                    }
                });
            },
            { rootMargin: '50px' }
        );
        
        // Observe important links
        document.querySelectorAll('a[data-prefetch], .nav-link, .primary-action').forEach(link => {
            observer.observe(link);
        });
    }
    
    /**
     * Setup responsive images
     */
    setupResponsiveImages() {
        if (!this.config.enableImageOptimization) return;
        
        // Convert images to use srcset
        document.querySelectorAll('img[data-responsive]').forEach(img => {
            const src = img.src || img.dataset.src;
            if (!src) return;
            
            // Generate srcset
            const srcset = this.generateSrcset(src);
            if (srcset) {
                img.srcset = srcset;
                img.sizes = img.dataset.sizes || '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw';
            }
        });
        
        // Setup WebP fallback
        this.setupWebPFallback();
    }
    
    /**
     * Generate srcset for image
     */
    generateSrcset(src) {
        const sizes = [320, 640, 1024, 1920];
        const srcset = sizes.map(size => {
            const url = src.replace(/\.(jpg|jpeg|png)$/i, `-${size}w.$1`);
            return `${url} ${size}w`;
        }).join(', ');
        
        return srcset;
    }
    
    /**
     * Setup WebP fallback
     */
    setupWebPFallback() {
        // Check WebP support
        const webpSupport = this.checkWebPSupport();
        
        webpSupport.then(supported => {
            if (supported) {
                document.body.classList.add('webp');
                this.convertImagesToWebP();
            } else {
                document.body.classList.add('no-webp');
            }
        });
    }
    
    /**
     * Check WebP support
     */
    checkWebPSupport() {
        return new Promise((resolve) => {
            const webp = new Image();
            webp.onload = webp.onerror = () => {
                resolve(webp.height === 2);
            };
            webp.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
        });
    }
    
    /**
     * Convert images to WebP
     */
    convertImagesToWebP() {
        document.querySelectorAll('img[data-webp]').forEach(img => {
            const webpSrc = img.dataset.webp;
            if (webpSrc) {
                img.src = webpSrc;
            }
        });
    }
    
    /**
     * Initialize code splitting
     */
    initCodeSplitting() {
        if (!this.config.enableCodeSplitting) return;
        
        // Setup dynamic imports for routes
        this.setupRouteSplitting();
        
        // Load components on demand
        this.setupComponentLazyLoading();
    }
    
    /**
     * Setup route-based code splitting
     */
    setupRouteSplitting() {
        // Map routes to their modules
        const routeModules = {
            '/trips': () => import('./modules/trips-module.js'),
            '/backpacks': () => import('./modules/backpacks-module.js'),
            '/gear': () => import('./modules/gear-module.js')
        };
        
        // Load module for current route
        const currentPath = window.location.pathname;
        const moduleLoader = routeModules[currentPath];
        
        if (moduleLoader) {
            moduleLoader()
                .then(module => {
                    console.log('Loaded module for', currentPath);
                    if (module.default && typeof module.default.init === 'function') {
                        module.default.init();
                    }
                })
                .catch(error => {
                    console.error('Failed to load module:', error);
                });
        }
    }
    
    /**
     * Setup component lazy loading
     */
    setupComponentLazyLoading() {
        // Lazy load heavy components
        const lazyComponents = document.querySelectorAll('[data-lazy-component]');
        
        const componentObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        const component = element.dataset.lazyComponent;
                        
                        this.loadComponent(component, element);
                        componentObserver.unobserve(element);
                    }
                });
            },
            { rootMargin: '100px' }
        );
        
        lazyComponents.forEach(element => {
            componentObserver.observe(element);
        });
    }
    
    /**
     * Load component dynamically
     */
    async loadComponent(componentName, element) {
        try {
            const module = await import(`./components/${componentName}.js`);
            
            if (module.default) {
                const component = new module.default(element);
                component.render();
            }
            
            element.classList.add('component-loaded');
        } catch (error) {
            console.error(`Failed to load component ${componentName}:`, error);
        }
    }
    
    /**
     * Optimize CSS animations
     */
    optimizeCSSAnimations() {
        // Use GPU acceleration for transforms
        this.addGPUAcceleration();
        
        // Optimize animation timing
        this.optimizeAnimationTiming();
        
        // Remove animations on print
        this.setupPrintOptimization();
    }
    
    /**
     * Add GPU acceleration
     */
    addGPUAcceleration() {
        const styles = `
            .gpu-accelerated,
            [data-animate],
            .transition-transform {
                transform: translateZ(0);
                will-change: transform;
            }
            
            .transition-opacity {
                will-change: opacity;
            }
        `;
        
        this.injectStyles('gpu-acceleration', styles);
    }
    
    /**
     * Optimize animation timing
     */
    optimizeAnimationTiming() {
        // Use RAF for JavaScript animations
        window.requestAnimationFrame = window.requestAnimationFrame || 
                                     window.webkitRequestAnimationFrame ||
                                     window.mozRequestAnimationFrame ||
                                     function(callback) {
                                         return setTimeout(callback, 1000 / 60);
                                     };
    }
    
    /**
     * Setup print optimization
     */
    setupPrintOptimization() {
        window.addEventListener('beforeprint', () => {
            document.body.classList.add('print-mode');
        });
        
        window.addEventListener('afterprint', () => {
            document.body.classList.remove('print-mode');
        });
    }
    
    /**
     * Start performance monitoring
     */
    startPerformanceMonitoring() {
        // Monitor load time
        window.addEventListener('load', () => {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            this.metrics.loadTime = loadTime;
            console.log('Page load time:', loadTime + 'ms');
        });
        
        // Monitor memory usage
        if (performance.memory) {
            setInterval(() => {
                this.metrics.memoryUsage = performance.memory.usedJSHeapSize / 1048576; // Convert to MB
                
                // Warn if memory usage is high
                if (this.metrics.memoryUsage > 100) {
                    console.warn('High memory usage:', this.metrics.memoryUsage.toFixed(2) + 'MB');
                }
            }, 10000);
        }
        
        // Monitor long tasks
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.duration > 50) {
                        console.warn('Long task detected:', entry);
                    }
                }
            });
            
            observer.observe({ entryTypes: ['longtask'] });
        }
    }
    
    /**
     * Detect low-end device
     */
    detectLowEndDevice() {
        const isLowEnd = 
            // Check connection
            (navigator.connection && 
             (navigator.connection.saveData || 
              navigator.connection.effectiveType === '2g' ||
              navigator.connection.effectiveType === 'slow-2g')) ||
            // Check memory
            (navigator.deviceMemory && navigator.deviceMemory < 4) ||
            // Check CPU cores
            (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4);
        
        if (isLowEnd) {
            console.log('Low-end device detected, optimizing performance');
            document.body.classList.add('low-end-device');
            this.config.animationThreshold = 30;
            this.reduceAnimations();
        }
    }
    
    /**
     * Setup mutation observer for dynamic content
     */
    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            // Check for lazy load elements
                            if (node.matches && 
                                (node.matches('img[data-src]') || 
                                 node.matches('[data-bg]') ||
                                 node.matches('[data-lazy-content]'))) {
                                this.observers.intersection.observe(node);
                            }
                            
                            // Check for child lazy elements
                            if (node.querySelectorAll) {
                                this.observeLazyElements();
                            }
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Inject styles
     */
    injectStyles(id, styles) {
        if (document.getElementById(`perf-${id}`)) return;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = `perf-${id}`;
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
    
    /**
     * Inject reduced animation styles
     */
    injectReducedAnimationStyles() {
        const styles = `
            .reduce-animations *,
            .reduce-animations *::before,
            .reduce-animations *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            
            .reduce-animations .spinner,
            .reduce-animations .loading-spinner {
                animation-duration: 1s !important;
            }
            
            @media print {
                * {
                    animation: none !important;
                    transition: none !important;
                }
            }
            
            .is-scrolling * {
                pointer-events: none !important;
            }
            
            .lazy-loaded {
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .low-end-device .transition-all {
                transition: none !important;
            }
            
            .low-end-device [data-animate] {
                animation: none !important;
            }
        `;
        
        this.injectStyles('reduced-animations', styles);
    }
    
    /**
     * Get performance metrics
     */
    getMetrics() {
        return {
            ...this.metrics,
            lazyLoadPending: this.lazyLoadQueue.size,
            prefetchedResources: this.prefetchQueue.size
        };
    }
    
    /**
     * Clear caches
     */
    clearCaches() {
        this.lazyLoadQueue.clear();
        this.prefetchQueue.clear();
        this.animationFrameCallbacks.clear();
        
        // Clear browser caches if possible
        if ('caches' in window) {
            caches.keys().then(names => {
                names.forEach(name => {
                    caches.delete(name);
                });
            });
        }
    }
}

// Initialize performance optimizer
window.performanceOptimizer = new PerformanceOptimizer();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PerformanceOptimizer;
}
