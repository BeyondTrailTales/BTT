/**
 * Sticky Gear Controls
 * Handles scroll events and keyboard navigation for gear library controls
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', () => {
    initializeStickyControls();
    initializeKeyboardNavigation();
});

// Helper function for debouncing
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

// Initialize sticky controls behavior
function initializeStickyControls() {
    const libraryScroll = document.querySelector('.gear-library-scroll');
    const controls = document.querySelector('.gear-controls-sticky');

    if (!libraryScroll || !controls) return;

    const handleScroll = debounce(() => {
        if (libraryScroll.scrollTop > 0) {
            controls.classList.add('scrolled');
        } else {
            controls.classList.remove('scrolled');
        }
    }, 10);

    libraryScroll.addEventListener('scroll', handleScroll);
}

// Initialize keyboard navigation
function initializeKeyboardNavigation() {
    const tabs = document.querySelectorAll('.cat-tab[role="tab"]');
    if (!tabs.length) return;

    // Store tab references
    let tabRefs = Array.from(tabs);
    
    tabs.forEach(tab => {
        tab.addEventListener('keydown', (e) => {
            let targetTab;
            
            switch (e.key) {
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    targetTab = getPreviousTab(tabRefs, tab);
                    break;
                    
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    targetTab = getNextTab(tabRefs, tab);
                    break;
                    
                case 'Home':
                    e.preventDefault();
                    targetTab = tabRefs[0];
                    break;
                    
                case 'End':
                    e.preventDefault();
                    targetTab = tabRefs[tabRefs.length - 1];
                    break;
                    
                case ' ':
                case 'Enter':
                    e.preventDefault();
                    activateTab(tab);
                    break;
            }
            
            if (targetTab) {
                targetTab.focus();
                if (e.key.startsWith('Arrow')) {
                    activateTab(targetTab);
                }
            }
        });

        // Handle click events
        tab.addEventListener('click', () => {
            activateTab(tab);
        });
    });
}

// Get the next tab in the list
function getNextTab(tabs, currentTab) {
    const currentIndex = tabs.indexOf(currentTab);
    return tabs[currentIndex + 1] || tabs[0];
}

// Get the previous tab in the list
function getPreviousTab(tabs, currentTab) {
    const currentIndex = tabs.indexOf(currentTab);
    return tabs[currentIndex - 1] || tabs[tabs.length - 1];
}

// Activate a tab
function activateTab(tab) {
    // Get all tabs in the same group
    const tabs = document.querySelectorAll('.cat-tab[role="tab"]');
    
    // Deactivate all tabs
    tabs.forEach(t => {
        t.setAttribute('aria-selected', 'false');
        t.classList.remove('active');
        t.tabIndex = -1;
    });
    
    // Activate the selected tab
    tab.setAttribute('aria-selected', 'true');
    tab.classList.add('active');
    tab.tabIndex = 0;
    
    // Update aria-pressed state for filter chips if needed
    if (tab.closest('.filter-chip')) {
        const wasPressed = tab.getAttribute('aria-pressed') === 'true';
        tab.setAttribute('aria-pressed', (!wasPressed).toString());
    }
}

// Initialize ResizeObserver to handle container size changes
if (window.ResizeObserver) {
    const resizeObserver = new ResizeObserver(debounce((entries) => {
        for (const entry of entries) {
            if (entry.target.classList.contains('panel-right')) {
                const scrollContainer = entry.target.querySelector('.gear-library-scroll');
                if (scrollContainer) {
                    // Update max-height based on container size
                    const containerHeight = entry.target.offsetHeight;
                    const controlsHeight = entry.target.querySelector('.gear-controls-sticky').offsetHeight;
                    scrollContainer.style.maxHeight = `${containerHeight - controlsHeight}px`;
                }
            }
        }
    }, 100));

    // Observe the panel
    const panel = document.querySelector('.panel-right');
    if (panel) {
        resizeObserver.observe(panel);
    }
}
