/**
 * Loading Transitions and Animations
 * Provides smooth loading states and transitions for BeyondTrailTales
 */

(function() {
    'use strict';
    
    // Loading overlay management
    const LoadingManager = {
        activeLoaders: new Set(),
        
        show: function(id = 'global') {
            this.activeLoaders.add(id);
            
            let loader = document.getElementById(`loader-${id}`);
            if (!loader) {
                loader = this.createLoader(id);
            }
            
            loader.classList.add('active');
            document.body.classList.add('loading');
        },
        
        hide: function(id = 'global') {
            this.activeLoaders.delete(id);
            
            const loader = document.getElementById(`loader-${id}`);
            if (loader) {
                loader.classList.remove('active');
                
                setTimeout(() => {
                    if (!loader.classList.contains('active')) {
                        loader.remove();
                    }
                }, 300);
            }
            
            if (this.activeLoaders.size === 0) {
                document.body.classList.remove('loading');
            }
        },
        
        createLoader: function(id) {
            const loader = document.createElement('div');
            loader.id = `loader-${id}`;
            loader.className = 'loading-overlay';
            loader.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p class="loading-text">Loading...</p>
                </div>
            `;
            document.body.appendChild(loader);
            return loader;
        }
    };
    
    // Page transition effects
    const PageTransitions = {
        fadeIn: function(element, duration = 300) {
            element.style.opacity = '0';
            element.style.display = 'block';
            
            const start = performance.now();
            
            const fade = (timestamp) => {
                const elapsed = timestamp - start;
                const progress = Math.min(elapsed / duration, 1);
                
                element.style.opacity = progress;
                
                if (progress < 1) {
                    requestAnimationFrame(fade);
                }
            };
            
            requestAnimationFrame(fade);
        },
        
        fadeOut: function(element, duration = 300) {
            const start = performance.now();
            const initialOpacity = parseFloat(window.getComputedStyle(element).opacity);
            
            const fade = (timestamp) => {
                const elapsed = timestamp - start;
                const progress = Math.min(elapsed / duration, 1);
                
                element.style.opacity = initialOpacity * (1 - progress);
                
                if (progress < 1) {
                    requestAnimationFrame(fade);
                } else {
                    element.style.display = 'none';
                }
            };
            
            requestAnimationFrame(fade);
        },
        
        slideIn: function(element, direction = 'left', duration = 300) {
            const translations = {
                'left': 'translateX(-100%)',
                'right': 'translateX(100%)',
                'top': 'translateY(-100%)',
                'bottom': 'translateY(100%)'
            };
            
            element.style.transform = translations[direction];
            element.style.display = 'block';
            element.style.transition = `transform ${duration}ms ease-out`;
            
            setTimeout(() => {
                element.style.transform = 'translate(0, 0)';
            }, 10);
        },
        
        slideOut: function(element, direction = 'left', duration = 300) {
            const translations = {
                'left': 'translateX(-100%)',
                'right': 'translateX(100%)',
                'top': 'translateY(-100%)',
                'bottom': 'translateY(100%)'
            };
            
            element.style.transition = `transform ${duration}ms ease-in`;
            element.style.transform = translations[direction];
            
            setTimeout(() => {
                element.style.display = 'none';
            }, duration);
        }
    };
    
    // Skeleton loader utilities
    const SkeletonLoader = {
        create: function(type = 'text', options = {}) {
            const skeleton = document.createElement('div');
            skeleton.className = `skeleton skeleton-${type}`;
            
            if (options.width) skeleton.style.width = options.width;
            if (options.height) skeleton.style.height = options.height;
            
            return skeleton;
        },
        
        replace: function(container, count = 3, type = 'text') {
            container.innerHTML = '';
            for (let i = 0; i < count; i++) {
                container.appendChild(this.create(type));
            }
        },
        
        restore: function(container, content) {
            container.innerHTML = content;
        }
    };
    
    // Add loading styles
    const style = document.createElement('style');
    style.textContent = `
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(10, 40, 24, 0.9);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        .loading-spinner {
            text-align: center;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(74, 222, 128, 0.2);
            border-top-color: #4ade80;
            border-radius: 50%;
            margin: 0 auto;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: #4ade80;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        
        .skeleton {
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0.05) 25%, 
                rgba(255, 255, 255, 0.1) 50%, 
                rgba(255, 255, 255, 0.05) 75%
            );
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }
        
        .skeleton-text {
            height: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .skeleton-card {
            height: 100px;
            margin-bottom: 1rem;
        }
        
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        body.loading {
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);
    
    // Export to window
    window.LoadingManager = LoadingManager;
    window.PageTransitions = PageTransitions;
    window.SkeletonLoader = SkeletonLoader;
    
})();
