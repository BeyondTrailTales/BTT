/**
 * Enhanced Gear Search & Categorization
 * Implements fuzzy search with Fuse.js and advanced filtering
 */

class GearSearchManager {
    constructor() {
        this.gearItems = [];
        this.filteredItems = [];
        this.fuse = null;
        this.searchDebounce = null;
        this.filters = {
            category: 'all',
            weight: { min: 0, max: Infinity },
            brand: 'all',
            season: 'all',
            priceRange: 'all'
        };
        
        this.categories = {
            shelter: { name: 'Shelter', icon: '⛺', color: '#ef4444' },
            sleep: { name: 'Sleep System', icon: '🛏️', color: '#f59e0b' },
            cooking: { name: 'Cooking', icon: '🔥', color: '#eab308' },
            clothing: { name: 'Clothing', icon: '👕', color: '#84cc16' },
            hydration: { name: 'Hydration', icon: '💧', color: '#06b6d4' },
            navigation: { name: 'Navigation', icon: '🧭', color: '#3b82f6' },
            safety: { name: 'Safety', icon: '🚑', color: '#8b5cf6' },
            hygiene: { name: 'Hygiene', icon: '🧼', color: '#ec4899' },
            electronics: { name: 'Electronics', icon: '🔋', color: '#6366f1' },
            tools: { name: 'Tools', icon: '🔧', color: '#10b981' },
            food: { name: 'Food Storage', icon: '🥫', color: '#f97316' },
            misc: { name: 'Miscellaneous', icon: '📦', color: '#6b7280' }
        };
        
        this.init();
    }
    
    async init() {
        // Load Fuse.js if not already loaded
        await this.loadFuseJS();
        
        // Initialize UI components
        this.initializeUI();
        
        // Load gear data
        await this.loadGearData();
        
        // Setup event listeners
        this.setupEventListeners();
    }
    
    /**
     * Load Fuse.js library dynamically
     */
    async loadFuseJS() {
        if (typeof Fuse !== 'undefined') return;
        
        return new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/fuse.js@6.6.2/dist/fuse.min.js';
            script.onload = resolve;
            document.head.appendChild(script);
        });
    }
    
    /**
     * Initialize UI components
     */
    initializeUI() {
        // Add search enhancement to existing search inputs
        const searchInputs = document.querySelectorAll('#gear-search, #gear-library-search');
        searchInputs.forEach(input => {
            this.enhanceSearchInput(input);
        });
        
        // Add category filter buttons
        this.createCategoryFilters();
        
        // Add advanced filter panel
        this.createAdvancedFilters();
    }
    
    /**
     * Enhance search input with suggestions
     */
    enhanceSearchInput(input) {
        if (!input) return;
        
        // Create suggestions dropdown
        const wrapper = document.createElement('div');
        wrapper.className = 'search-wrapper';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        const suggestions = document.createElement('div');
        suggestions.className = 'search-suggestions';
        suggestions.style.display = 'none';
        wrapper.appendChild(suggestions);
        
        // Add search icon feedback
        const searchIcon = document.createElement('div');
        searchIcon.className = 'search-status-icon';
        searchIcon.innerHTML = '🔍';
        wrapper.appendChild(searchIcon);
    }
    
    /**
     * Create category filter buttons
     */
    createCategoryFilters() {
        const filterContainers = document.querySelectorAll('.category-filters');
        
        filterContainers.forEach(container => {
            container.innerHTML = '';
            
            // All categories button
            const allBtn = this.createFilterButton('all', 'All', '🌐');
            allBtn.classList.add('active');
            container.appendChild(allBtn);
            
            // Category buttons
            Object.entries(this.categories).forEach(([key, category]) => {
                const btn = this.createFilterButton(key, category.name, category.icon);
                container.appendChild(btn);
            });
        });
    }
    
    /**
     * Create a filter button
     */
    createFilterButton(value, label, icon) {
        const button = document.createElement('button');
        button.className = 'cat-filter';
        button.dataset.category = value;
        button.innerHTML = `<span class="filter-icon">${icon}</span> <span class="filter-label">${label}</span>`;
        button.style.setProperty('--filter-color', this.categories[value]?.color || '#6b7280');
        return button;
    }
    
    /**
     * Create advanced filters panel
     */
    createAdvancedFilters() {
        const gearLibrary = document.querySelector('.gear-library-card');
        if (!gearLibrary) return;
        
        const filtersPanel = document.createElement('div');
        filtersPanel.className = 'advanced-filters-panel';
        filtersPanel.innerHTML = `
            <div class="filters-header">
                <h4>Advanced Filters</h4>
                <button class="btn-text" id="clear-filters">Clear All</button>
            </div>
            
            <div class="filter-group">
                <label>Weight Range</label>
                <div class="weight-range-slider">
                    <input type="range" id="weight-min" min="0" max="2000" value="0" step="10">
                    <input type="range" id="weight-max" min="0" max="2000" value="2000" step="10">
                    <div class="weight-range-display">
                        <span id="weight-min-display">0g</span> - 
                        <span id="weight-max-display">2000g</span>
                    </div>
                </div>
            </div>
            
            <div class="filter-group">
                <label>Brand</label>
                <select id="brand-filter" class="filter-select">
                    <option value="all">All Brands</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Season</label>
                <div class="season-filters">
                    <button class="season-btn" data-season="all">All</button>
                    <button class="season-btn" data-season="3-season">3-Season</button>
                    <button class="season-btn" data-season="4-season">4-Season</button>
                    <button class="season-btn" data-season="summer">Summer</button>
                    <button class="season-btn" data-season="winter">Winter</button>
                </div>
            </div>
            
            <div class="filter-group">
                <label>Quick Filters</label>
                <div class="quick-filters">
                    <button class="quick-filter" data-filter="ultralight">
                        <span>⚡</span> Ultralight (&lt;10g)
                    </button>
                    <button class="quick-filter" data-filter="essential">
                        <span>⭐</span> Essentials
                    </button>
                    <button class="quick-filter" data-filter="worn">
                        <span>👕</span> Worn Items
                    </button>
                    <button class="quick-filter" data-filter="consumable">
                        <span>🍎</span> Consumables
                    </button>
                </div>
            </div>
        `;
        
        // Insert after library header
        const libraryHeader = gearLibrary.querySelector('.library-header');
        if (libraryHeader) {
            libraryHeader.after(filtersPanel);
        }
    }
    
    /**
     * Load gear data
     */
    async loadGearData() {
        try {
            // Show loading state
            this.showLoadingState();
            
            // Fetch gear data from API
            const response = await fetch('/api/routes/gear.php?action=list');
            const data = await response.json();
            
            if (data.success) {
                this.gearItems = data.gear || [];
                this.initializeFuse();
                this.renderGearItems(this.gearItems);
                
                // Update brand filter options
                this.updateBrandFilter();
            }
        } catch (error) {
            console.error('Failed to load gear data:', error);
            
            // Use demo data as fallback
            this.gearItems = this.getDemoGearData();
            this.initializeFuse();
            this.renderGearItems(this.gearItems);
        } finally {
            this.hideLoadingState();
        }
    }
    
    /**
     * Initialize Fuse.js for fuzzy search
     */
    initializeFuse() {
        if (typeof Fuse === 'undefined') return;
        
        const options = {
            keys: [
                { name: 'name', weight: 0.3 },
                { name: 'brand', weight: 0.2 },
                { name: 'category', weight: 0.2 },
                { name: 'description', weight: 0.1 },
                { name: 'tags', weight: 0.2 }
            ],
            threshold: 0.3,
            includeScore: true,
            minMatchCharLength: 2,
            shouldSort: true
        };
        
        this.fuse = new Fuse(this.gearItems, options);
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Search input with debounce
        const searchInputs = document.querySelectorAll('#gear-search, #gear-library-search');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                clearTimeout(this.searchDebounce);
                this.searchDebounce = setTimeout(() => {
                    this.handleSearch(e.target.value);
                }, 150);
            });
            
            // Instant feedback for typing
            input.addEventListener('keydown', () => {
                const icon = input.parentElement.querySelector('.search-status-icon');
                if (icon) {
                    icon.innerHTML = '⏳';
                    icon.classList.add('searching');
                }
            });
        });
        
        // Category filters
        document.addEventListener('click', (e) => {
            if (e.target.closest('.cat-filter')) {
                const button = e.target.closest('.cat-filter');
                this.handleCategoryFilter(button);
            }
            
            if (e.target.closest('.season-btn')) {
                const button = e.target.closest('.season-btn');
                this.handleSeasonFilter(button);
            }
            
            if (e.target.closest('.quick-filter')) {
                const button = e.target.closest('.quick-filter');
                this.handleQuickFilter(button);
            }
        });
        
        // Weight range sliders
        const weightMin = document.getElementById('weight-min');
        const weightMax = document.getElementById('weight-max');
        
        if (weightMin && weightMax) {
            const updateWeightFilter = () => {
                this.filters.weight.min = parseInt(weightMin.value);
                this.filters.weight.max = parseInt(weightMax.value);
                
                // Update display
                document.getElementById('weight-min-display').textContent = `${weightMin.value}g`;
                document.getElementById('weight-max-display').textContent = `${weightMax.value}g`;
                
                this.applyFilters();
            };
            
            weightMin.addEventListener('input', updateWeightFilter);
            weightMax.addEventListener('input', updateWeightFilter);
        }
        
        // Brand filter
        const brandFilter = document.getElementById('brand-filter');
        if (brandFilter) {
            brandFilter.addEventListener('change', (e) => {
                this.filters.brand = e.target.value;
                this.applyFilters();
            });
        }
        
        // Clear filters
        const clearBtn = document.getElementById('clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }
    }
    
    /**
     * Handle search query
     */
    handleSearch(query) {
        const icon = document.querySelector('.search-status-icon');
        
        if (!query || query.length === 0) {
            // Reset to show all items
            this.filteredItems = this.gearItems;
            if (icon) {
                icon.innerHTML = '🔍';
                icon.classList.remove('searching');
            }
        } else {
            // Perform fuzzy search
            if (this.fuse) {
                const results = this.fuse.search(query);
                this.filteredItems = results.map(result => result.item);
                
                if (icon) {
                    icon.innerHTML = this.filteredItems.length > 0 ? '✅' : '❌';
                    icon.classList.remove('searching');
                }
                
                // Show search feedback
                this.showSearchFeedback(query, this.filteredItems.length);
            } else {
                // Fallback to simple search
                this.filteredItems = this.gearItems.filter(item => {
                    const searchStr = `${item.name} ${item.brand} ${item.category}`.toLowerCase();
                    return searchStr.includes(query.toLowerCase());
                });
            }
        }
        
        this.applyFilters();
    }
    
    /**
     * Handle category filter
     */
    handleCategoryFilter(button) {
        // Remove active class from all buttons
        button.parentElement.querySelectorAll('.cat-filter').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        button.classList.add('active');
        
        // Update filter
        this.filters.category = button.dataset.category;
        
        // Apply filters
        this.applyFilters();
        
        // Show toast feedback
        const categoryName = button.querySelector('.filter-label').textContent;
        showInfo(`Filtering by ${categoryName}`);
    }
    
    /**
     * Handle season filter
     */
    handleSeasonFilter(button) {
        // Toggle active state
        const isActive = button.classList.contains('active');
        
        // Clear other season buttons if not "all"
        if (button.dataset.season !== 'all') {
            button.parentElement.querySelectorAll('.season-btn').forEach(btn => {
                btn.classList.remove('active');
            });
        }
        
        // Toggle or activate button
        if (!isActive) {
            button.classList.add('active');
            this.filters.season = button.dataset.season;
        } else {
            button.classList.remove('active');
            this.filters.season = 'all';
        }
        
        this.applyFilters();
    }
    
    /**
     * Handle quick filter
     */
    handleQuickFilter(button) {
        const filter = button.dataset.filter;
        
        button.classList.toggle('active');
        
        switch(filter) {
            case 'ultralight':
                if (button.classList.contains('active')) {
                    this.filters.weight.max = 10;
                } else {
                    this.filters.weight.max = 2000;
                }
                break;
                
            case 'essential':
                // Filter by essential tag
                break;
                
            case 'worn':
                // Filter by worn category
                break;
                
            case 'consumable':
                // Filter by consumable tag
                break;
        }
        
        this.applyFilters();
    }
    
    /**
     * Apply all active filters
     */
    applyFilters() {
        let items = this.filteredItems.length > 0 ? this.filteredItems : this.gearItems;
        
        // Category filter
        if (this.filters.category !== 'all') {
            items = items.filter(item => item.category === this.filters.category);
        }
        
        // Weight filter
        items = items.filter(item => {
            const weight = item.weight || 0;
            return weight >= this.filters.weight.min && weight <= this.filters.weight.max;
        });
        
        // Brand filter
        if (this.filters.brand !== 'all') {
            items = items.filter(item => item.brand === this.filters.brand);
        }
        
        // Season filter
        if (this.filters.season !== 'all') {
            items = items.filter(item => {
                return item.seasons && item.seasons.includes(this.filters.season);
            });
        }
        
        // Render filtered items
        this.renderGearItems(items);
        
        // Update count display
        this.updateItemCount(items.length, this.gearItems.length);
    }
    
    /**
     * Clear all filters
     */
    clearAllFilters() {
        // Reset filter state
        this.filters = {
            category: 'all',
            weight: { min: 0, max: 2000 },
            brand: 'all',
            season: 'all',
            priceRange: 'all'
        };
        
        // Reset UI
        document.querySelectorAll('.cat-filter, .season-btn, .quick-filter').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Reset "All" buttons
        document.querySelector('.cat-filter[data-category="all"]')?.classList.add('active');
        document.querySelector('.season-btn[data-season="all"]')?.classList.add('active');
        
        // Reset sliders
        const weightMin = document.getElementById('weight-min');
        const weightMax = document.getElementById('weight-max');
        if (weightMin) weightMin.value = 0;
        if (weightMax) weightMax.value = 2000;
        
        // Reset brand
        const brandFilter = document.getElementById('brand-filter');
        if (brandFilter) brandFilter.value = 'all';
        
        // Clear search
        document.querySelectorAll('#gear-search, #gear-library-search').forEach(input => {
            input.value = '';
        });
        
        this.filteredItems = [];
        this.applyFilters();
        
        showSuccess('All filters cleared');
    }
    
    /**
     * Render gear items
     */
    renderGearItems(items) {
        const container = document.getElementById('gear-items') || 
                         document.getElementById('gear-items-container');
                         
        if (!container) return;
        
        if (items.length === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>No gear items found</h3>
                    <p>Try adjusting your filters or search query</p>
                    <button class="btn btn-secondary" onclick="gearSearchManager.clearAllFilters()">
                        Clear Filters
                    </button>
                </div>
            `;
            return;
        }
        
        const itemsHTML = items.map(item => this.createGearItemHTML(item)).join('');
        container.innerHTML = itemsHTML;
        
        // Make items draggable
        if (window.dragDropManager) {
            container.querySelectorAll('.gear-item').forEach(item => {
                item.setAttribute('draggable', 'true');
                item.setAttribute('aria-label', item.querySelector('.gear-item-name')?.textContent);
            });
        }
    }
    
    /**
     * Create gear item HTML
     */
    createGearItemHTML(item) {
        const category = this.categories[item.category] || this.categories.misc;
        const weight = item.weight ? `${item.weight}g` : 'N/A';
        
        return `
            <div class="gear-item" data-gear-id="${item.id}" data-weight="${item.weight || 0}">
                <div class="gear-item-handle drag-handle">☰</div>
                <div class="gear-item-icon" style="background-color: ${category.color}20; color: ${category.color}">
                    ${category.icon}
                </div>
                <div class="gear-item-details">
                    <div class="gear-item-name">${item.name}</div>
                    <div class="gear-item-meta">
                        ${item.brand ? `<span class="gear-brand">${item.brand}</span>` : ''}
                        <span class="gear-weight">${weight}</span>
                    </div>
                </div>
                <button class="gear-item-add" onclick="addGearToPack(${item.id})" aria-label="Add to pack">
                    <span>➕</span>
                </button>
            </div>
        `;
    }
    
    /**
     * Update brand filter options
     */
    updateBrandFilter() {
        const brandFilter = document.getElementById('brand-filter');
        if (!brandFilter) return;
        
        // Get unique brands
        const brands = [...new Set(this.gearItems.map(item => item.brand).filter(Boolean))];
        brands.sort();
        
        // Update options
        brandFilter.innerHTML = '<option value="all">All Brands</option>';
        brands.forEach(brand => {
            const option = document.createElement('option');
            option.value = brand;
            option.textContent = brand;
            brandFilter.appendChild(option);
        });
    }
    
    /**
     * Update item count display
     */
    updateItemCount(shown, total) {
        const countDisplay = document.getElementById('gear-total-count');
        if (countDisplay) {
            countDisplay.textContent = shown === total ? 
                `${total}` : `${shown} of ${total}`;
        }
    }
    
    /**
     * Show search feedback
     */
    showSearchFeedback(query, resultCount) {
        const message = resultCount > 0 ? 
            `Found ${resultCount} items matching "${query}"` :
            `No items found for "${query}"`;
            
        const type = resultCount > 0 ? 'info' : 'warning';
        
        // Don't show toast for every keystroke, just update UI
        this.updateItemCount(resultCount, this.gearItems.length);
    }
    
    /**
     * Show loading state
     */
    showLoadingState() {
        const container = document.getElementById('gear-items') || 
                         document.getElementById('gear-items-container');
        if (container) {
            showSkeleton(container, 'list', 8);
        }
    }
    
    /**
     * Hide loading state
     */
    hideLoadingState() {
        const container = document.getElementById('gear-items') || 
                         document.getElementById('gear-items-container');
        if (container) {
            hideSkeleton(container);
        }
    }
    
    /**
     * Get demo gear data
     */
    getDemoGearData() {
        return [
            { id: 1, name: 'Ultralight Tent', brand: 'ZPacks', category: 'shelter', weight: 450, seasons: ['3-season'] },
            { id: 2, name: 'Sleeping Bag', brand: 'Western Mountaineering', category: 'sleep', weight: 820, seasons: ['3-season'] },
            { id: 3, name: 'Backpack', brand: 'Hyperlite', category: 'misc', weight: 900, seasons: ['all'] },
            { id: 4, name: 'Stove', brand: 'MSR', category: 'cooking', weight: 73, seasons: ['all'] },
            { id: 5, name: 'Water Filter', brand: 'Sawyer', category: 'hydration', weight: 57, seasons: ['all'] },
            { id: 6, name: 'Rain Jacket', brand: 'Patagonia', category: 'clothing', weight: 230, seasons: ['all'] },
            { id: 7, name: 'First Aid Kit', brand: 'Adventure Medical', category: 'safety', weight: 200, seasons: ['all'] },
            { id: 8, name: 'Headlamp', brand: 'Petzl', category: 'electronics', weight: 85, seasons: ['all'] },
            { id: 9, name: 'Trekking Poles', brand: 'Black Diamond', category: 'tools', weight: 480, seasons: ['all'] },
            { id: 10, name: 'Bear Canister', brand: 'BearVault', category: 'food', weight: 935, seasons: ['all'] }
        ];
    }
}

// Initialize gear search manager
window.gearSearchManager = new GearSearchManager();

// Global function for adding gear to pack
window.addGearToPack = function(gearId) {
    // Find gear item
    const item = window.gearSearchManager.gearItems.find(g => g.id === gearId);
    if (!item) return;
    
    // Show success toast with undo
    showToastWithUndo(
        `Added ${item.name} to pack`,
        () => {
            // Undo action
            console.log('Removing item from pack:', gearId);
            showInfo('Item removed from pack');
        }
    );
    
    // Trigger pack update
    if (window.updatePackWeight) {
        window.updatePackWeight();
    }
};
