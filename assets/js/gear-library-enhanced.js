/**
 * Enhanced Gear Library
 * Handles the fixed-height scrollable gear library with sticky header
 */

const GearLibraryEnhanced = {
    init() {
        this.bindEventListeners();
        this.initializeFilters();
        this.setupScrollHandling();
    },

    bindEventListeners() {
        // Search input handling
        const searchInput = document.getElementById('gear-search');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => {
                this.filterGearItems(e.target.value);
            }, 300));
        }

        // Filter chips
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                chip.classList.toggle('active');
                this.applyFilters();
            });
        });

        // Category tabs
        document.querySelectorAll('.cat-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this.applyFilters();
            });
        });
    },

    initializeFilters() {
        this.filters = {
            search: '',
            favorites: false,
            lightweight: false,
            new: false,
            category: 'all'
        };
    },

    setupScrollHandling() {
        const gearContainer = document.querySelector('.gear-items-container');
        if (!gearContainer) return;

        // Add intersection observer for lazy loading
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadMoreGearItems();
                    }
                });
            },
            {
                root: gearContainer,
                threshold: 0.1
            }
        );

        // Observe the last item for infinite scroll
        const lastItem = gearContainer.querySelector('.gear-grid > :last-child');
        if (lastItem) {
            observer.observe(lastItem);
        }

        // Handle smooth scrolling during drag operations
        gearContainer.addEventListener('dragover', (e) => {
            const rect = gearContainer.getBoundingClientRect();
            const scrollZone = 50; // pixels from top/bottom for auto-scroll

            if (e.clientY - rect.top < scrollZone) {
                // Scroll up
                gearContainer.scrollBy({
                    top: -10,
                    behavior: 'smooth'
                });
            } else if (rect.bottom - e.clientY < scrollZone) {
                // Scroll down
                gearContainer.scrollBy({
                    top: 10,
                    behavior: 'smooth'
                });
            }
        });
    },

    filterGearItems(searchTerm) {
        this.filters.search = searchTerm.toLowerCase();
        this.applyFilters();
    },

    applyFilters() {
        const activeCategory = document.querySelector('.cat-tab.active')?.dataset.category || 'all';
        const activeFilters = Array.from(document.querySelectorAll('.filter-chip.active'))
            .map(chip => chip.dataset.filter);

        document.querySelectorAll('.gear-grid > *').forEach(item => {
            const matches = this.itemMatchesFilters(item, {
                ...this.filters,
                category: activeCategory,
                activeFilters
            });
            item.style.display = matches ? '' : 'none';
        });

        this.updateEmptyState();
    },

    itemMatchesFilters(item, filters) {
        const itemData = item.dataset;
        
        // Search text
        if (filters.search && !item.textContent.toLowerCase().includes(filters.search)) {
            return false;
        }

        // Category
        if (filters.category !== 'all' && itemData.category !== filters.category) {
            return false;
        }

        // Active filter chips
        if (filters.activeFilters.length > 0) {
            return filters.activeFilters.some(filter => itemData[filter] === 'true');
        }

        return true;
    },

    updateEmptyState() {
        const gearGrid = document.querySelector('.gear-grid');
        const visibleItems = gearGrid.querySelectorAll('*:not([style*="display: none"])');

        if (visibleItems.length === 0) {
            if (!gearGrid.querySelector('.empty-state')) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state';
                emptyState.innerHTML = `
                    <p>No gear items match your filters</p>
                    <button onclick="GearLibraryEnhanced.resetFilters()" class="btn-reset-filters">
                        Reset Filters
                    </button>
                `;
                gearGrid.appendChild(emptyState);
            }
        } else {
            const emptyState = gearGrid.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }
        }
    },

    resetFilters() {
        // Reset search
        const searchInput = document.getElementById('gear-search');
        if (searchInput) {
            searchInput.value = '';
        }

        // Reset filter chips
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.classList.remove('active');
        });

        // Reset category tabs
        const allTab = document.querySelector('.cat-tab[data-category="all"]');
        if (allTab) {
            document.querySelectorAll('.cat-tab').forEach(tab => tab.classList.remove('active'));
            allTab.classList.add('active');
        }

        // Reset filters object
        this.initializeFilters();

        // Apply reset
        this.applyFilters();
    },

    async loadMoreGearItems() {
        // Implementation for loading more items
        // This would be connected to your backend API
    }
};

// Utility function for debouncing
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

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    GearLibraryEnhanced.init();
});
