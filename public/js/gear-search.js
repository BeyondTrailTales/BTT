/**
 * Gear Search & Filtering System
 * Advanced search with Fuse.js, categories, and filters
 */

class GearSearchSystem {
    constructor() {
        this.gearItems = [];
        this.filteredItems = [];
        this.fuseInstance = null;
        this.activeFilters = {
            category: '',
            weight: { min: 0, max: Infinity },
            custom: null,
            hidden: false,
            searchQuery: ''
        };
        
        // Gear categories with metadata
        this.categories = {
            shelter: {
                name: 'Shelter',
                icon: '⛺',
                subcategories: ['Tents', 'Tarps', 'Bivy', 'Hammocks', 'Stakes', 'Guylines']
            },
            sleep: {
                name: 'Sleep System',
                icon: '🛏️',
                subcategories: ['Sleeping Bags', 'Quilts', 'Pads', 'Pillows', 'Liners']
            },
            cooking: {
                name: 'Cooking',
                icon: '🍳',
                subcategories: ['Stoves', 'Pots', 'Utensils', 'Water Treatment', 'Food Storage']
            },
            clothing: {
                name: 'Clothing',
                icon: '👕',
                subcategories: ['Base Layers', 'Insulation', 'Rain Gear', 'Headwear', 'Footwear']
            },
            navigation: {
                name: 'Navigation',
                icon: '🧭',
                subcategories: ['Maps', 'Compass', 'GPS', 'Altimeter', 'Watch']
            },
            hygiene: {
                name: 'Hygiene',
                icon: '🧼',
                subcategories: ['Toiletries', 'Towel', 'Toilet Paper', 'Trowel', 'Sanitizer']
            },
            'first-aid': {
                name: 'First Aid',
                icon: '🏥',
                subcategories: ['Medications', 'Bandages', 'Tools', 'Emergency']
            },
            electronics: {
                name: 'Electronics',
                icon: '📱',
                subcategories: ['Phone', 'Camera', 'Batteries', 'Chargers', 'Lights']
            },
            water: {
                name: 'Water',
                icon: '💧',
                subcategories: ['Bottles', 'Bladders', 'Filters', 'Purification', 'Storage']
            },
            'food-storage': {
                name: 'Food Storage',
                icon: '🥫',
                subcategories: ['Bear Cans', 'Bags', 'Containers', 'Ziplock']
            },
            repair: {
                name: 'Repair',
                icon: '🔧',
                subcategories: ['Tape', 'Sewing', 'Adhesives', 'Tools', 'Spare Parts']
            },
            other: {
                name: 'Other',
                icon: '📦',
                subcategories: ['Miscellaneous']
            }
        };
        
        this.init();
    }
    
    /**
     * Initialize the search system
     */
    async init() {
        console.log('Initializing Gear Search System...');
        
        // Load Fuse.js if not already loaded
        if (typeof Fuse === 'undefined') {
            await this.loadFuseJS();
        }
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Initialize Fuse instance
        this.initializeFuse();
        
        // Load initial gear data
        await this.loadGearData();
        
        // Build category filters
        this.buildCategoryFilters();
        
        // Apply initial filters
        this.applyFilters();
        
        console.log('✅ Gear Search System initialized');
    }
    
    /**
     * Load Fuse.js library dynamically
     */
    loadFuseJS() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/fuse.js@6.6.2/dist/fuse.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    /**
     * Initialize Fuse.js instance
     */
    initializeFuse() {
        if (typeof Fuse === 'undefined') {
            console.error('Fuse.js not loaded');
            return;
        }
        
        const options = {
            keys: [
                { name: 'name', weight: 0.5 },
                { name: 'category', weight: 0.3 },
                { name: 'notes', weight: 0.2 },
                { name: 'brand', weight: 0.2 },
                { name: 'model', weight: 0.2 },
                { name: 'tags', weight: 0.3 }
            ],
            threshold: 0.3,
            location: 0,
            distance: 100,
            includeScore: true,
            shouldSort: true,
            minMatchCharLength: 2,
            findAllMatches: true
        };
        
        this.fuseInstance = new Fuse(this.gearItems, options);
    }
    
    /**
     * Load gear data from server
     */
    async loadGearData() {
        try {
            // Show loading state
            this.showLoadingState();
            
            const response = await fetch('/ajax/get_gear.php');
            const data = await response.json();
            
            if (data.success) {
                this.gearItems = data.items || [];
                
                // Enrich gear items with additional metadata
                this.gearItems = this.gearItems.map(item => ({
                    ...item,
                    weightClass: this.getWeightClass(item.weight),
                    categoryInfo: this.categories[item.category] || this.categories.other,
                    tags: this.generateTags(item)
                }));
                
                // Update Fuse instance with new data
                if (this.fuseInstance) {
                    this.fuseInstance.setCollection(this.gearItems);
                }
                
                console.log(`Loaded ${this.gearItems.length} gear items`);
            } else {
                console.error('Failed to load gear data');
            }
        } catch (error) {
            console.error('Error loading gear:', error);
        } finally {
            this.hideLoadingState();
        }
    }
    
    /**
     * Setup event listeners for search and filters
     */
    setupEventListeners() {
        // Search input
        const searchInputs = document.querySelectorAll('#gear-search, #gear-library-search, #global-search');
        searchInputs.forEach(input => {
            input.addEventListener('input', debounce((e) => {
                this.activeFilters.searchQuery = e.target.value;
                this.applyFilters();
            }, 300));
            
            // Clear search button
            input.addEventListener('search', (e) => {
                if (e.target.value === '') {
                    this.activeFilters.searchQuery = '';
                    this.applyFilters();
                }
            });
        });
        
        // Category filter
        const categoryFilter = document.getElementById('gear-category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.activeFilters.category = e.target.value;
                this.applyFilters();
            });
        }
        
        // Weight range filters
        const weightMin = document.getElementById('weight-min');
        const weightMax = document.getElementById('weight-max');
        
        if (weightMin) {
            weightMin.addEventListener('input', debounce((e) => {
                this.activeFilters.weight.min = parseFloat(e.target.value) || 0;
                this.applyFilters();
            }, 500));
        }
        
        if (weightMax) {
            weightMax.addEventListener('input', debounce((e) => {
                this.activeFilters.weight.max = parseFloat(e.target.value) || Infinity;
                this.applyFilters();
            }, 500));
        }
        
        // Custom/System gear toggle
        const viewModeRadios = document.querySelectorAll('input[name="gear-view-mode"]');
        viewModeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.activeFilters.custom = 
                    e.target.value === 'custom' ? true :
                    e.target.value === 'default' ? false :
                    e.target.value === 'hidden' ? 'hidden' :
                    null;
                this.applyFilters();
            });
        });
        
        // Sort options
        const sortSelect = document.getElementById('gear-sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.sortItems(e.target.value);
            });
        }
        
        // Quick filters (tags)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('gear-tag')) {
                const tag = e.target.dataset.tag;
                this.filterByTag(tag);
            }
        });
    }
    
    /**
     * Apply all active filters
     */
    applyFilters() {
        let items = [...this.gearItems];
        
        // Apply search query using Fuse.js
        if (this.activeFilters.searchQuery && this.fuseInstance) {
            const searchResults = this.fuseInstance.search(this.activeFilters.searchQuery);
            items = searchResults.map(result => result.item);
        }
        
        // Apply category filter
        if (this.activeFilters.category) {
            items = items.filter(item => item.category === this.activeFilters.category);
        }
        
        // Apply weight filter
        items = items.filter(item => {
            const weight = parseFloat(item.weight) || 0;
            return weight >= this.activeFilters.weight.min && 
                   weight <= this.activeFilters.weight.max;
        });
        
        // Apply custom/system filter
        if (this.activeFilters.custom !== null) {
            if (this.activeFilters.custom === 'hidden') {
                items = items.filter(item => item.hidden === true);
            } else {
                items = items.filter(item => item.is_custom === this.activeFilters.custom);
            }
        }
        
        this.filteredItems = items;
        
        // Update UI
        this.renderFilteredItems();
        this.updateFilterStats();
    }
    
    /**
     * Sort items by specified criteria
     */
    sortItems(sortBy) {
        switch (sortBy) {
            case 'name':
                this.filteredItems.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'weight':
                this.filteredItems.sort((a, b) => (a.weight || 0) - (b.weight || 0));
                break;
            case 'category':
                this.filteredItems.sort((a, b) => {
                    const catCompare = a.category.localeCompare(b.category);
                    return catCompare !== 0 ? catCompare : a.name.localeCompare(b.name);
                });
                break;
            case 'recent':
                this.filteredItems.sort((a, b) => 
                    new Date(b.created_at || 0) - new Date(a.created_at || 0)
                );
                break;
        }
        
        this.renderFilteredItems();
    }
    
    /**
     * Filter by tag
     */
    filterByTag(tag) {
        // Add tag to search query
        const searchInput = document.getElementById('gear-library-search') || 
                          document.getElementById('gear-search');
        if (searchInput) {
            searchInput.value = tag;
            this.activeFilters.searchQuery = tag;
            this.applyFilters();
        }
    }
    
    /**
     * Build category filter UI
     */
    buildCategoryFilters() {
        const container = document.getElementById('category-quick-filters');
        if (!container) return;
        
        // Clear existing
        container.innerHTML = '';
        
        // Add "All" button
        const allBtn = document.createElement('button');
        allBtn.className = 'category-pill active';
        allBtn.innerHTML = '🌐 All';
        allBtn.onclick = () => this.selectCategory('');
        container.appendChild(allBtn);
        
        // Add category buttons
        Object.entries(this.categories).forEach(([key, cat]) => {
            const btn = document.createElement('button');
            btn.className = 'category-pill';
            btn.dataset.category = key;
            btn.innerHTML = `${cat.icon} ${cat.name}`;
            btn.onclick = () => this.selectCategory(key);
            container.appendChild(btn);
        });
    }
    
    /**
     * Select category filter
     */
    selectCategory(category) {
        // Update active filter
        this.activeFilters.category = category;
        
        // Update UI
        document.querySelectorAll('.category-pill').forEach(btn => {
            btn.classList.toggle('active', 
                btn.dataset.category === category || (!category && btn.textContent.includes('All'))
            );
        });
        
        // Update select if exists
        const select = document.getElementById('gear-category-filter');
        if (select) {
            select.value = category;
        }
        
        // Apply filters
        this.applyFilters();
    }
    
    /**
     * Render filtered items
     */
    renderFilteredItems() {
        const container = document.getElementById('gear-items-container');
        if (!container) return;
        
        if (this.filteredItems.length === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <div class="no-results-text">No gear items found</div>
                    <div class="no-results-subtext">
                        Try adjusting your filters or search terms
                    </div>
                    <button class="btn-secondary" onclick="gearSearch.clearFilters()">
                        Clear Filters
                    </button>
                </div>
            `;
            return;
        }
        
        // Render items
        const itemsHTML = this.filteredItems.map(item => this.renderGearItem(item)).join('');
        container.innerHTML = itemsHTML;
        
        // Add hover effects
        this.addItemInteractions();
    }
    
    /**
     * Render single gear item
     */
    renderGearItem(item) {
        const weightClass = item.weightClass || this.getWeightClass(item.weight);
        const categoryInfo = item.categoryInfo || this.categories[item.category] || this.categories.other;
        
        return `
            <div class="gear-item-card ${item.is_custom ? 'custom-gear' : ''} ${item.hidden ? 'hidden-gear' : ''}"
                 data-gear-id="${item.id}"
                 data-category="${item.category}"
                 data-weight="${item.weight}"
                 draggable="true">
                
                <div class="gear-item-header">
                    <span class="gear-category-icon">${categoryInfo.icon}</span>
                    <span class="gear-item-name">${this.highlightSearchTerm(item.name)}</span>
                    ${item.is_custom ? '<span class="custom-badge">Custom</span>' : ''}
                </div>
                
                <div class="gear-item-body">
                    <div class="gear-weight ${weightClass}">
                        <span class="weight-value">${item.weight}g</span>
                        <span class="weight-oz">(${this.gramsToOz(item.weight)}oz)</span>
                    </div>
                    
                    ${item.brand ? `<div class="gear-brand">${item.brand}</div>` : ''}
                    ${item.notes ? `<div class="gear-notes">${this.truncate(item.notes, 50)}</div>` : ''}
                    
                    <div class="gear-tags">
                        ${this.generateTagsHTML(item)}
                    </div>
                </div>
                
                <div class="gear-item-actions">
                    <button class="btn-icon" title="Add to pack" onclick="gearSearch.addToPack(${item.id})">
                        <i>➕</i>
                    </button>
                    <button class="btn-icon" title="Edit" onclick="gearSearch.editItem(${item.id})">
                        <i>✏️</i>
                    </button>
                    <button class="btn-icon" title="${item.hidden ? 'Show' : 'Hide'}" 
                            onclick="gearSearch.toggleHidden(${item.id})">
                        <i>${item.hidden ? '👁️' : '👁️‍🗨️'}</i>
                    </button>
                </div>
            </div>
        `;
    }
    
    /**
     * Update filter statistics
     */
    updateFilterStats() {
        // Update count
        const countEl = document.getElementById('gear-total-count');
        if (countEl) {
            countEl.textContent = this.filteredItems.length;
        }
        
        // Update total weight
        const weightEl = document.getElementById('gear-total-weight');
        if (weightEl) {
            const totalWeight = this.filteredItems.reduce((sum, item) => sum + (item.weight || 0), 0);
            weightEl.textContent = `${totalWeight}g`;
        }
        
        // Update view indicator
        const viewEl = document.getElementById('gear-view-indicator');
        if (viewEl) {
            if (this.activeFilters.custom === true) {
                viewEl.textContent = 'Custom Gear';
            } else if (this.activeFilters.custom === false) {
                viewEl.textContent = 'System Gear';
            } else if (this.activeFilters.custom === 'hidden') {
                viewEl.textContent = 'Hidden Items';
            } else {
                viewEl.textContent = 'All Gear';
            }
        }
    }
    
    /**
     * Helper: Get weight class
     */
    getWeightClass(weight) {
        const w = parseFloat(weight) || 0;
        if (w < 50) return 'ultralight';
        if (w < 200) return 'light';
        if (w < 500) return 'medium';
        return 'heavy';
    }
    
    /**
     * Helper: Convert grams to ounces
     */
    gramsToOz(grams) {
        return ((parseFloat(grams) || 0) * 0.035274).toFixed(1);
    }
    
    /**
     * Helper: Generate tags for item
     */
    generateTags(item) {
        const tags = [];
        
        // Weight class tag
        tags.push(this.getWeightClass(item.weight));
        
        // Category tag
        if (item.category) {
            tags.push(item.category);
        }
        
        // Custom tags from notes
        if (item.notes) {
            const noteTags = item.notes.match(/#\w+/g) || [];
            tags.push(...noteTags.map(t => t.substring(1)));
        }
        
        return tags;
    }
    
    /**
     * Helper: Generate tags HTML
     */
    generateTagsHTML(item) {
        const tags = item.tags || this.generateTags(item);
        return tags.slice(0, 3).map(tag => 
            `<span class="gear-tag" data-tag="${tag}">#${tag}</span>`
        ).join('');
    }
    
    /**
     * Helper: Highlight search term in text
     */
    highlightSearchTerm(text) {
        if (!this.activeFilters.searchQuery) return text;
        
        const regex = new RegExp(`(${this.activeFilters.searchQuery})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    /**
     * Helper: Truncate text
     */
    truncate(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    /**
     * Clear all filters
     */
    clearFilters() {
        this.activeFilters = {
            category: '',
            weight: { min: 0, max: Infinity },
            custom: null,
            hidden: false,
            searchQuery: ''
        };
        
        // Clear UI
        const searchInputs = document.querySelectorAll('#gear-search, #gear-library-search');
        searchInputs.forEach(input => input.value = '');
        
        const categoryFilter = document.getElementById('gear-category-filter');
        if (categoryFilter) categoryFilter.value = '';
        
        const viewModeRadios = document.querySelectorAll('input[name="gear-view-mode"]');
        viewModeRadios.forEach(radio => {
            radio.checked = radio.value === 'both';
        });
        
        // Apply filters
        this.applyFilters();
    }
    
    /**
     * Add item interactions
     */
    addItemInteractions() {
        const items = document.querySelectorAll('.gear-item-card');
        
        items.forEach(item => {
            // Drag start
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('text/plain', item.dataset.gearId);
                item.classList.add('dragging');
            });
            
            // Drag end
            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
            });
            
            // Double click to add
            item.addEventListener('dblclick', () => {
                const gearId = item.dataset.gearId;
                this.addToPack(gearId);
            });
        });
    }
    
    /**
     * Add item to current pack
     */
    addToPack(gearId) {
        // Integrate with pack builder
        if (window.PackBuilder && window.PackBuilder.addGearItem) {
            window.PackBuilder.addGearItem(gearId);
        }
        
        // Show feedback
        if (window.showSuccess) {
            window.showSuccess('Item added to pack');
        }
    }
    
    /**
     * Edit gear item
     */
    editItem(gearId) {
        const item = this.gearItems.find(g => g.id == gearId);
        if (!item) return;
        
        // Open edit modal or panel
        console.log('Edit item:', item);
        // TODO: Implement edit modal
    }
    
    /**
     * Toggle hidden status
     */
    toggleHidden(gearId) {
        const item = this.gearItems.find(g => g.id == gearId);
        if (!item) return;
        
        item.hidden = !item.hidden;
        
        // Save to server
        fetch('/ajax/update_gear.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: gearId, hidden: item.hidden })
        });
        
        // Re-render
        this.applyFilters();
    }
    
    /**
     * Show loading state
     */
    showLoadingState() {
        const container = document.getElementById('gear-items-container');
        if (container) {
            container.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading gear library...</p>
                </div>
            `;
        }
    }
    
    /**
     * Hide loading state
     */
    hideLoadingState() {
        // Loading state will be replaced by renderFilteredItems
    }
}

// Debounce helper
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

// Initialize gear search system
window.gearSearch = new GearSearchSystem();
