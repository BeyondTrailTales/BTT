/**
 * Loading States and Transitions Manager
 * Handles skeleton screens, button loading states, and view transitions
 */

class LoadingManager {
    constructor() {
        this.activeLoaders = new Set();
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.init();
    }

    init() {
        // Listen for reduced motion preference changes
        window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => {
            this.reducedMotion = e.matches;
        });
    }

    /**
     * Show skeleton loader for a container
     * @param {string|Element} container - Container element or selector
     * @param {string} type - Type of skeleton (card, list, form, table, pack-builder)
     * @param {number} count - Number of skeleton items to show
     */
    showSkeleton(container, type = 'card', count = 3) {
        const element = typeof container === 'string' ? document.querySelector(container) : container;
        if (!element) return;

        // Store original content
        element.dataset.originalContent = element.innerHTML;
        
        // Generate skeleton HTML based on type
        let skeletonHTML = '';
        
        switch(type) {
            case 'card':
                skeletonHTML = this.generateCardSkeleton(count);
                break;
            case 'list':
                skeletonHTML = this.generateListSkeleton(count);
                break;
            case 'form':
                skeletonHTML = this.generateFormSkeleton();
                break;
            case 'table':
                skeletonHTML = this.generateTableSkeleton(count);
                break;
            case 'pack-builder':
                skeletonHTML = this.generatePackBuilderSkeleton();
                break;
            default:
                skeletonHTML = this.generateCardSkeleton(count);
        }
        
        // Add skeleton container with fade-in animation
        element.innerHTML = `<div class="skeleton-container">${skeletonHTML}</div>`;
        
        // Announce loading state to screen readers
        this.announceLoading('Loading content...');
        
        return element;
    }

    /**
     * Hide skeleton loader and restore content
     * @param {string|Element} container - Container element or selector
     * @param {string} newContent - Optional new content to replace with
     */
    hideSkeleton(container, newContent = null) {
        const element = typeof container === 'string' ? document.querySelector(container) : container;
        if (!element) return;

        if (newContent) {
            // Fade out skeleton and fade in new content
            this.fadeTransition(element, () => {
                element.innerHTML = newContent;
            });
        } else if (element.dataset.originalContent) {
            // Restore original content
            this.fadeTransition(element, () => {
                element.innerHTML = element.dataset.originalContent;
                delete element.dataset.originalContent;
            });
        }
        
        // Announce completion to screen readers
        this.announceLoading('Content loaded');
    }

    /**
     * Add loading state to a button
     * @param {string|Element} button - Button element or selector
     * @param {string} loadingText - Optional loading text
     */
    setButtonLoading(button, loadingText = 'Loading...') {
        const btn = typeof button === 'string' ? document.querySelector(button) : button;
        if (!btn) return;

        // Store original state
        btn.dataset.originalText = btn.innerHTML;
        btn.dataset.originalDisabled = btn.disabled;
        
        // Add loading class and disable
        btn.classList.add('btn-loading');
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        
        // Update text if not using CSS spinner
        if (loadingText && !btn.classList.contains('btn-icon-only')) {
            btn.innerHTML = loadingText;
        }
        
        this.activeLoaders.add(btn);
    }

    /**
     * Remove loading state from a button
     * @param {string|Element} button - Button element or selector
     * @param {string} successText - Optional success text to show briefly
     */
    removeButtonLoading(button, successText = null) {
        const btn = typeof button === 'string' ? document.querySelector(button) : button;
        if (!btn || !this.activeLoaders.has(btn)) return;

        btn.classList.remove('btn-loading');
        btn.removeAttribute('aria-busy');
        
        if (successText) {
            // Show success state briefly
            btn.innerHTML = successText;
            btn.classList.add('btn-success-state');
            
            setTimeout(() => {
                btn.innerHTML = btn.dataset.originalText || '';
                btn.disabled = btn.dataset.originalDisabled === 'true';
                btn.classList.remove('btn-success-state');
                delete btn.dataset.originalText;
                delete btn.dataset.originalDisabled;
            }, 2000);
        } else {
            // Restore original state immediately
            btn.innerHTML = btn.dataset.originalText || '';
            btn.disabled = btn.dataset.originalDisabled === 'true';
            delete btn.dataset.originalText;
            delete btn.dataset.originalDisabled;
        }
        
        this.activeLoaders.delete(btn);
    }

    /**
     * Show loading overlay on a section
     * @param {string|Element} section - Section element or selector
     * @param {string} message - Loading message
     */
    showLoadingOverlay(section, message = 'Loading...') {
        const element = typeof section === 'string' ? document.querySelector(section) : section;
        if (!element) return;

        // Ensure relative positioning for overlay
        if (getComputedStyle(element).position === 'static') {
            element.style.position = 'relative';
        }

        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div>
                <div class="loading-overlay__spinner"></div>
                <div class="loading-overlay__text">${message}</div>
            </div>
        `;
        
        element.appendChild(overlay);
        element.setAttribute('aria-busy', 'true');
        
        return overlay;
    }

    /**
     * Hide loading overlay
     * @param {string|Element} section - Section element or selector
     */
    hideLoadingOverlay(section) {
        const element = typeof section === 'string' ? document.querySelector(section) : section;
        if (!element) return;

        const overlay = element.querySelector('.loading-overlay');
        if (overlay) {
            this.fadeOut(overlay, () => {
                overlay.remove();
                element.removeAttribute('aria-busy');
            });
        }
    }

    /**
     * Animate view transition
     * @param {Element} fromView - Element to transition from
     * @param {Element} toView - Element to transition to
     * @param {string} type - Transition type (fade, slide-left, slide-right, slide-up)
     */
    transitionViews(fromView, toView, type = 'fade') {
        if (this.reducedMotion) {
            // Simple instant transition for reduced motion
            fromView.style.display = 'none';
            toView.style.display = 'block';
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            const duration = 300;
            
            // Prepare toView
            toView.style.display = 'block';
            toView.style.opacity = '0';
            
            switch(type) {
                case 'slide-left':
                    toView.style.transform = 'translateX(100%)';
                    break;
                case 'slide-right':
                    toView.style.transform = 'translateX(-100%)';
                    break;
                case 'slide-up':
                    toView.style.transform = 'translateY(100%)';
                    break;
            }
            
            // Trigger reflow
            toView.offsetHeight;
            
            // Add transitions
            fromView.style.transition = `opacity ${duration}ms ease-out, transform ${duration}ms ease-out`;
            toView.style.transition = `opacity ${duration}ms ease-out, transform ${duration}ms ease-out`;
            
            // Animate out fromView
            fromView.style.opacity = '0';
            if (type.startsWith('slide')) {
                const direction = type.includes('left') ? '-100%' : type.includes('right') ? '100%' : '0';
                const axis = type.includes('up') ? 'Y' : 'X';
                fromView.style.transform = `translate${axis}(${direction})`;
            }
            
            // Animate in toView
            toView.style.opacity = '1';
            toView.style.transform = 'translate(0, 0)';
            
            // Cleanup after animation
            setTimeout(() => {
                fromView.style.display = 'none';
                fromView.style.transition = '';
                fromView.style.opacity = '';
                fromView.style.transform = '';
                toView.style.transition = '';
                resolve();
            }, duration);
        });
    }

    /**
     * Create progress bar
     * @param {string|Element} container - Container for progress bar
     * @param {number} initialValue - Initial progress value (0-100)
     */
    createProgressBar(container, initialValue = 0) {
        const element = typeof container === 'string' ? document.querySelector(container) : container;
        if (!element) return;

        const progressBar = document.createElement('div');
        progressBar.className = 'progress-bar';
        progressBar.innerHTML = `
            <div class="progress-bar__track">
                <div class="progress-bar__fill" style="width: ${initialValue}%"></div>
            </div>
            <div class="progress-bar__text">${initialValue}%</div>
        `;
        
        element.appendChild(progressBar);
        
        return {
            update: (value) => this.updateProgressBar(progressBar, value),
            remove: () => progressBar.remove()
        };
    }

    /**
     * Update progress bar value
     * @param {Element} progressBar - Progress bar element
     * @param {number} value - New progress value (0-100)
     */
    updateProgressBar(progressBar, value) {
        const fill = progressBar.querySelector('.progress-bar__fill');
        const text = progressBar.querySelector('.progress-bar__text');
        
        if (fill && text) {
            fill.style.width = `${value}%`;
            text.textContent = `${value}%`;
            
            // Announce significant progress updates to screen readers
            if (value === 0 || value === 25 || value === 50 || value === 75 || value === 100) {
                this.announceLoading(`Progress: ${value}%`);
            }
        }
    }

    // Helper methods for generating skeleton HTML
    generateCardSkeleton(count) {
        let html = '<div class="skeleton-grid">';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-card">
                    <div class="skeleton skeleton-card__image"></div>
                    <div class="skeleton skeleton-card__title"></div>
                    <div class="skeleton skeleton-card__subtitle"></div>
                    <div class="skeleton skeleton-card__text"></div>
                    <div class="skeleton skeleton-card__text"></div>
                    <div class="skeleton-card__actions">
                        <div class="skeleton skeleton-card__button"></div>
                        <div class="skeleton skeleton-card__button"></div>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }

    generateListSkeleton(count) {
        let html = '<div class="skeleton-list">';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-list-item">
                    <div class="skeleton skeleton-list-item__avatar"></div>
                    <div class="skeleton-list-item__content">
                        <div class="skeleton skeleton-list-item__title"></div>
                        <div class="skeleton skeleton-list-item__text"></div>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }

    generateFormSkeleton() {
        return `
            <div class="skeleton-form">
                <div class="skeleton skeleton-form__label"></div>
                <div class="skeleton skeleton-form__input"></div>
                <div class="skeleton skeleton-form__label"></div>
                <div class="skeleton skeleton-form__input"></div>
                <div class="skeleton skeleton-form__label"></div>
                <div class="skeleton skeleton-form__textarea"></div>
                <div class="skeleton skeleton-form__button"></div>
            </div>
        `;
    }

    generateTableSkeleton(rows) {
        let html = '<div class="skeleton-table">';
        for (let i = 0; i < rows; i++) {
            html += `
                <div class="skeleton-table__row">
                    <div class="skeleton skeleton-table__cell skeleton-table__cell--narrow"></div>
                    <div class="skeleton skeleton-table__cell"></div>
                    <div class="skeleton skeleton-table__cell"></div>
                    <div class="skeleton skeleton-table__cell skeleton-table__cell--narrow"></div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }

    generatePackBuilderSkeleton() {
        return `
            <div class="skeleton-pack-builder">
                <div class="skeleton-gear-library">
                    <div class="skeleton skeleton-gear-library__search"></div>
                    <div class="skeleton-gear-library__filters">
                        <div class="skeleton skeleton-gear-library__filter"></div>
                        <div class="skeleton skeleton-gear-library__filter"></div>
                        <div class="skeleton skeleton-gear-library__filter"></div>
                    </div>
                    <div class="skeleton-gear-library__items">
                        <div class="skeleton skeleton-gear-item"></div>
                        <div class="skeleton skeleton-gear-item"></div>
                        <div class="skeleton skeleton-gear-item"></div>
                        <div class="skeleton skeleton-gear-item"></div>
                    </div>
                </div>
                <div class="skeleton-card">
                    <div class="skeleton skeleton-card__title"></div>
                    ${this.generateListSkeleton(4)}
                </div>
                <div class="skeleton-weight-summary">
                    <div class="skeleton skeleton-weight-summary__title"></div>
                    <div class="skeleton-weight-summary__stat">
                        <div class="skeleton skeleton-weight-summary__label"></div>
                        <div class="skeleton skeleton-weight-summary__value"></div>
                    </div>
                    <div class="skeleton-weight-summary__stat">
                        <div class="skeleton skeleton-weight-summary__label"></div>
                        <div class="skeleton skeleton-weight-summary__value"></div>
                    </div>
                    <div class="skeleton-weight-summary__stat">
                        <div class="skeleton skeleton-weight-summary__label"></div>
                        <div class="skeleton skeleton-weight-summary__value"></div>
                    </div>
                </div>
            </div>
        `;
    }

    // Utility methods
    fadeTransition(element, callback) {
        if (this.reducedMotion) {
            callback();
            return;
        }
        
        element.style.transition = 'opacity 200ms ease-out';
        element.style.opacity = '0';
        
        setTimeout(() => {
            callback();
            element.style.opacity = '1';
            setTimeout(() => {
                element.style.transition = '';
            }, 200);
        }, 200);
    }

    fadeOut(element, callback) {
        if (this.reducedMotion) {
            callback();
            return;
        }
        
        element.style.transition = 'opacity 200ms ease-out';
        element.style.opacity = '0';
        
        setTimeout(() => {
            callback();
        }, 200);
    }

    announceLoading(message) {
        // Create or update screen reader announcement
        let announcement = document.querySelector('.sr-loading-announcement');
        if (!announcement) {
            announcement = document.createElement('div');
            announcement.className = 'sr-loading-announcement';
            announcement.setAttribute('role', 'status');
            announcement.setAttribute('aria-live', 'polite');
            document.body.appendChild(announcement);
        }
        announcement.textContent = message;
    }
}

// Initialize global loading manager
window.loadingManager = new LoadingManager();

// Convenience functions
window.showSkeleton = (container, type, count) => window.loadingManager.showSkeleton(container, type, count);
window.hideSkeleton = (container, content) => window.loadingManager.hideSkeleton(container, content);
window.setButtonLoading = (button, text) => window.loadingManager.setButtonLoading(button, text);
window.removeButtonLoading = (button, successText) => window.loadingManager.removeButtonLoading(button, successText);
window.showLoadingOverlay = (section, message) => window.loadingManager.showLoadingOverlay(section, message);
window.hideLoadingOverlay = (section) => window.loadingManager.hideLoadingOverlay(section);

// Auto-apply to forms with data-async attribute
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-async]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('[type="submit"]');
            if (submitBtn) {
                window.setButtonLoading(submitBtn, 'Submitting...');
            }
        });
    });
    
    // Auto-apply to buttons with data-loading-text
    document.querySelectorAll('button[data-loading-text]').forEach(button => {
        button.addEventListener('click', function() {
            if (!this.classList.contains('btn-loading')) {
                window.setButtonLoading(this, this.dataset.loadingText);
            }
        });
    });
});
