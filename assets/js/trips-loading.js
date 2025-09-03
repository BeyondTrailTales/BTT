/**
 * Trips Loading States Enhancement
 * Provides smooth loading states and skeleton screens for trips
 */

(function() {
    'use strict';
    
    // Loading skeleton template for trip cards
    const createSkeletonCard = () => {
        return `
            <article class="trip-card skeleton-card" aria-hidden="true">
                <div class="skeleton-header">
                    <div class="skeleton skeleton-title"></div>
                </div>
                <div class="skeleton skeleton-image"></div>
                <div class="skeleton skeleton-text"></div>
                <div class="skeleton skeleton-text" style="width: 60%;"></div>
                <div class="trip-card-stats">
                    <div class="skeleton skeleton-stat"></div>
                    <div class="skeleton skeleton-stat"></div>
                    <div class="skeleton skeleton-stat"></div>
                </div>
            </article>
        `;
    };
    
    // Show loading skeletons
    window.showTripSkeletons = function(count = 6) {
        const grid = document.getElementById('trip-grid');
        if (!grid) return;
        
        let skeletons = '';
        for (let i = 0; i < count; i++) {
            skeletons += createSkeletonCard();
        }
        
        grid.innerHTML = skeletons;
        grid.setAttribute('aria-busy', 'true');
    };
    
    // Hide loading state
    window.hideTripSkeletons = function() {
        const grid = document.getElementById('trip-grid');
        if (!grid) return;
        
        const skeletons = grid.querySelectorAll('.skeleton-card');
        skeletons.forEach(skeleton => {
            skeleton.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => skeleton.remove(), 300);
        });
        
        grid.setAttribute('aria-busy', 'false');
    };
    
    // Add skeleton styles if not already present
    if (!document.getElementById('skeleton-styles')) {
        const styles = document.createElement('style');
        styles.id = 'skeleton-styles';
        styles.textContent = `
            .skeleton-card {
                pointer-events: none;
                user-select: none;
            }
            
            .skeleton {
                background: linear-gradient(
                    90deg,
                    rgba(255, 255, 255, 0.05) 25%,
                    rgba(255, 255, 255, 0.1) 50%,
                    rgba(255, 255, 255, 0.05) 75%
                );
                background-size: 200% 100%;
                animation: shimmer 1.5s infinite;
                border-radius: var(--radius-md);
            }
            
            .skeleton-header {
                padding: 1rem;
            }
            
            .skeleton-title {
                height: 24px;
                width: 70%;
                margin-bottom: 0.5rem;
            }
            
            .skeleton-image {
                height: 200px;
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .skeleton-text {
                height: 16px;
                margin: 0 1rem 0.5rem;
            }
            
            .skeleton-stat {
                height: 40px;
                flex: 1;
            }
            
            @keyframes shimmer {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }
            
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    console.log('✅ Trips loading enhancements initialized');
})();
